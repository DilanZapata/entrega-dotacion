<?php
require_once __DIR__ . '/_lib.php';

$method = $_SERVER['REQUEST_METHOD'];
$solicitudes = read_json_file('solicitudes.json');

if ($method === 'GET') {
    // Consulta pública puntual: una cédula en un periodo (usada por el formulario de entrega/resumen).
    if (isset($_GET['cedula']) && isset($_GET['periodo_id'])) {
        foreach ($solicitudes as $s) {
            if ($s['cedula'] === $_GET['cedula'] && $s['periodo_id'] === $_GET['periodo_id']) {
                json_response($s);
            }
        }
        json_response(null);
    }
    // Listados completos: solo administración.
    require_admin();
    if (isset($_GET['periodo_id'])) {
        $filtradas = array_values(array_filter($solicitudes, fn($s) => $s['periodo_id'] === $_GET['periodo_id']));
        json_response($filtradas);
    }
    json_response($solicitudes);
}

if ($method === 'POST') {
    $input = read_body();
    $cedula = trim($input['cedula'] ?? '');
    $periodoId = trim($input['periodo_id'] ?? '');
    $items = $input['items'] ?? [];

    if ($cedula === '' || $periodoId === '' || empty($items)) {
        json_error('Faltan datos obligatorios (cedula, periodo_id o items).');
    }

    $indice = -1;
    foreach ($solicitudes as $k => $s) {
        if ($s['cedula'] === $cedula && $s['periodo_id'] === $periodoId) {
            $indice = $k;
            break;
        }
    }

    $registro = [
        'id' => $indice !== -1 ? $solicitudes[$indice]['id'] : uuid_v4(),
        'periodo_id' => $periodoId,
        'cedula' => $cedula,
        'nombre' => $input['nombre'] ?? '',
        'cargo' => $input['cargo'] ?? '',
        'genero' => $input['genero'] ?? 'N/A',
        'items' => $items,
        'fecha_solicitud' => date('Y-m-d H:i:s'),
        'estado' => $indice !== -1 ? ($solicitudes[$indice]['estado'] ?? 'pendiente') : 'pendiente',
    ];

    if ($indice !== -1) {
        $solicitudes[$indice] = $registro;
    } else {
        $solicitudes[] = $registro;
    }

    write_json_file('solicitudes.json', $solicitudes);
    json_response(['status' => 'success', 'solicitud' => $registro]);
}

require_admin();

if ($method === 'DELETE') {
    $input = read_body();
    $id = $input['id'] ?? ($_GET['id'] ?? '');
    if ($id === '') json_error('Falta el identificador de la solicitud.');

    $filtered = array_values(array_filter($solicitudes, fn($s) => $s['id'] !== $id));
    if (count($filtered) === count($solicitudes)) json_error('Solicitud no encontrada.', 404);

    write_json_file('solicitudes.json', $filtered);
    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
