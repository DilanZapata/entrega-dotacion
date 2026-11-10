<?php
// Equipos que se prestan (arnés, casco, eslinga...). Cada unidad se lleva por código propio.
require_once __DIR__ . '/_lib.php';

require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$equipos = read_json_file('equipos.json');

function limpiar_fecha($v): ?string {
    $v = trim((string)($v ?? ''));
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
}

if ($method === 'GET') {
    usort($equipos, fn($a, $b) => strcmp($a['tipo'] . $a['codigo'], $b['tipo'] . $b['codigo']));
    json_response($equipos);
}

if ($method === 'POST' || $method === 'PUT') {
    $input = read_body();
    $id = $input['id'] ?? '';
    $codigo = strtoupper(trim((string)($input['codigo'] ?? '')));
    $tipo = trim((string)($input['tipo'] ?? ''));
    if ($codigo === '' || $tipo === '') json_error('El código y el tipo de equipo son obligatorios.');

    foreach ($equipos as $e) {
        if ($e['id'] !== $id && $e['codigo'] === $codigo) json_error('Ya existe un equipo con ese código.');
    }

    $datos = [
        'codigo' => $codigo,
        'tipo' => $tipo,
        'marca' => trim((string)($input['marca'] ?? '')),
        'serial' => trim((string)($input['serial'] ?? '')),
        'fecha_ultima_inspeccion' => limpiar_fecha($input['fecha_ultima_inspeccion'] ?? null),
        'observaciones' => trim((string)($input['observaciones'] ?? '')),
    ];
    $fueraServicio = !empty($input['fuera_de_servicio']);

    if ($id === '') {
        $equipos[] = ['id' => uuid_v4()] + $datos + ['estado' => $fueraServicio ? 'fuera_de_servicio' : 'disponible'];
    } else {
        $ok = false;
        foreach ($equipos as &$e) {
            if ($e['id'] === $id) {
                $e = array_merge($e, $datos);
                // Un equipo prestado solo cambia de estado al registrar la devolución.
                if ($e['estado'] !== 'prestado') {
                    $e['estado'] = $fueraServicio ? 'fuera_de_servicio' : 'disponible';
                }
                $ok = true;
                break;
            }
        }
        unset($e);
        if (!$ok) json_error('Equipo no encontrado.', 404);
    }

    write_json_file('equipos.json', $equipos);
    json_response(['status' => 'success']);
}

if ($method === 'DELETE') {
    $input = read_body();
    $id = $input['id'] ?? '';
    foreach ($equipos as $e) {
        if ($e['id'] === $id && $e['estado'] === 'prestado') {
            json_error('Este equipo está prestado. Registra primero la devolución.');
        }
    }
    $filtrados = array_values(array_filter($equipos, fn($e) => $e['id'] !== $id));
    if (count($filtrados) === count($equipos)) json_error('Equipo no encontrado.', 404);
    write_json_file('equipos.json', $filtrados);
    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
