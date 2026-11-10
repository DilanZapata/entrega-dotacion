<?php
// Préstamo de equipos a trabajadores: entrega (con estado y actividad) y devolución.
require_once __DIR__ . '/_lib.php';

require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$prestamos = read_json_file('prestamos.json');
$equipos = read_json_file('equipos.json');

function cambiar_estado_equipos(array &$equipos, array $estadosPorId): void {
    foreach ($equipos as &$e) {
        if (isset($estadosPorId[$e['id']])) $e['estado'] = $estadosPorId[$e['id']];
    }
    unset($e);
}

if ($method === 'GET') {
    usort($prestamos, fn($a, $b) => strcmp($b['fecha_prestamo'], $a['fecha_prestamo']));
    json_response($prestamos);
}

if ($method === 'POST') {
    $input = read_body();

    // ---- Devolución ----
    if (($input['action'] ?? '') === 'devolver') {
        $id = $input['id'] ?? '';
        $indice = -1;
        foreach ($prestamos as $k => $p) {
            if ($p['id'] === $id) { $indice = $k; break; }
        }
        if ($indice === -1) json_error('Préstamo no encontrado.', 404);
        if ($prestamos[$indice]['estado'] !== 'activo') json_error('Este préstamo ya fue devuelto.');

        $devueltos = [];
        foreach (($input['items'] ?? []) as $it) {
            $devueltos[$it['equipo_id'] ?? ''] = $it;
        }

        $nuevosEstados = [];
        foreach ($prestamos[$indice]['items'] as &$item) {
            $d = $devueltos[$item['equipo_id']] ?? [];
            $estado = trim((string)($d['estado_devolucion'] ?? 'Bueno'));
            $item['estado_devolucion'] = $estado;
            $item['observaciones_devolucion'] = trim((string)($d['observaciones'] ?? ''));
            $nuevosEstados[$item['equipo_id']] = stripos($estado, 'fuera de servicio') !== false ? 'fuera_de_servicio' : 'disponible';
        }
        unset($item);

        $prestamos[$indice]['estado'] = 'devuelto';
        $prestamos[$indice]['fecha_devolucion'] = date('Y-m-d H:i:s');
        $prestamos[$indice]['recibe_devolucion'] = trim((string)($input['recibe'] ?? ''));

        cambiar_estado_equipos($equipos, $nuevosEstados);
        write_json_file('equipos.json', $equipos);
        write_json_file('prestamos.json', $prestamos);
        json_response(['status' => 'success', 'prestamo' => $prestamos[$indice]]);
    }

    // ---- Nuevo préstamo ----
    $cedula = trim((string)($input['cedula'] ?? ''));
    $actividad = trim((string)($input['actividad'] ?? ''));
    $solicitados = $input['items'] ?? [];
    if ($cedula === '') json_error('Selecciona el trabajador.');
    if ($actividad === '') json_error('Indica la actividad para la que se prestan los equipos.');
    if (!$solicitados) json_error('Selecciona al menos un equipo.');

    $porId = [];
    foreach ($equipos as $e) $porId[$e['id']] = $e;

    $items = [];
    $nuevosEstados = [];
    foreach ($solicitados as $s) {
        $e = $porId[$s['equipo_id'] ?? ''] ?? null;
        if (!$e) json_error('Uno de los equipos seleccionados ya no existe.');
        if ($e['estado'] !== 'disponible') json_error("El equipo {$e['codigo']} no está disponible.");
        $items[] = [
            'equipo_id' => $e['id'],
            'codigo' => $e['codigo'],
            'tipo' => $e['tipo'],
            'marca' => $e['marca'],
            'serial' => $e['serial'],
            'estado_entrega' => trim((string)($s['estado_entrega'] ?? 'Bueno')),
            'observaciones' => trim((string)($s['observaciones'] ?? '')),
        ];
        $nuevosEstados[$e['id']] = 'prestado';
    }

    $fechaPrevista = trim((string)($input['fecha_devolucion_prevista'] ?? ''));
    $nuevo = [
        'id' => uuid_v4(),
        'cedula' => $cedula,
        'nombre' => $input['nombre'] ?? '',
        'cargo' => $input['cargo'] ?? '',
        'vinculacion' => $input['vinculacion'] ?? '',
        'actividad' => $actividad,
        'lugar' => trim((string)($input['lugar'] ?? '')),
        'entrega' => trim((string)($input['entrega'] ?? '')),
        'fecha_prestamo' => date('Y-m-d H:i:s'),
        'fecha_devolucion_prevista' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPrevista) ? $fechaPrevista : null,
        'items' => $items,
        'estado' => 'activo',
        'fecha_devolucion' => null,
        'recibe_devolucion' => '',
    ];

    cambiar_estado_equipos($equipos, $nuevosEstados);
    $prestamos[] = $nuevo;
    write_json_file('equipos.json', $equipos);
    write_json_file('prestamos.json', $prestamos);
    json_response(['status' => 'success', 'prestamo' => $nuevo]);
}

if ($method === 'DELETE') {
    $input = read_body();
    $id = $input['id'] ?? '';
    $objetivo = null;
    foreach ($prestamos as $p) {
        if ($p['id'] === $id) { $objetivo = $p; break; }
    }
    if (!$objetivo) json_error('Préstamo no encontrado.', 404);

    // Si se elimina un préstamo activo, los equipos vuelven a quedar disponibles.
    if ($objetivo['estado'] === 'activo') {
        $liberar = [];
        foreach ($objetivo['items'] as $it) $liberar[$it['equipo_id']] = 'disponible';
        cambiar_estado_equipos($equipos, $liberar);
        write_json_file('equipos.json', $equipos);
    }

    write_json_file('prestamos.json', array_values(array_filter($prestamos, fn($p) => $p['id'] !== $id)));
    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
