<?php
require_once __DIR__ . '/_lib.php';

require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$entregas = read_json_file('entregas.json');

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        foreach ($entregas as $e) {
            if ($e['id'] === $_GET['id']) json_response($e);
        }
        json_response(null);
    }
    if (isset($_GET['periodo_id'])) {
        $filtradas = array_values(array_filter($entregas, fn($e) => $e['periodo_id'] === $_GET['periodo_id']));
        json_response($filtradas);
    }
    json_response($entregas);
}

if ($method === 'POST') {
    $input = read_body();
    $cedula = trim($input['cedula'] ?? '');
    $periodoId = trim($input['periodo_id'] ?? '');
    $items = $input['items_entregados'] ?? [];

    if ($cedula === '' || $periodoId === '' || empty($items)) {
        json_error('Faltan datos obligatorios (cedula, periodo_id o items_entregados).');
    }

    $indice = -1;
    foreach ($entregas as $k => $e) {
        if ($e['cedula'] === $cedula && $e['periodo_id'] === $periodoId) {
            $indice = $k;
            break;
        }
    }

    $registro = [
        'id' => $indice !== -1 ? $entregas[$indice]['id'] : uuid_v4(),
        'periodo_id' => $periodoId,
        'solicitud_id' => $input['solicitud_id'] ?? null,
        'cedula' => $cedula,
        'nombre' => $input['nombre'] ?? '',
        'cargo' => $input['cargo'] ?? '',
        'items_entregados' => $items,
        'fecha_entrega' => date('Y-m-d H:i:s'),
    ];

    if ($indice !== -1) {
        $entregas[$indice] = $registro;
    } else {
        $entregas[] = $registro;
    }
    write_json_file('entregas.json', $entregas);

    // Marcar la solicitud correspondiente (si existe) como entregada.
    $solicitudes = read_json_file('solicitudes.json');
    foreach ($solicitudes as &$s) {
        if ($s['cedula'] === $cedula && $s['periodo_id'] === $periodoId) {
            $s['estado'] = 'entregado';
            break;
        }
    }
    unset($s);
    write_json_file('solicitudes.json', $solicitudes);

    json_response(['status' => 'success', 'entrega' => $registro]);
}

if ($method === 'DELETE') {
    $input = read_body();
    $id = $input['id'] ?? ($_GET['id'] ?? '');
    if ($id === '') json_error('Falta el identificador de la entrega.');

    $objetivo = null;
    foreach ($entregas as $e) {
        if ($e['id'] === $id) { $objetivo = $e; break; }
    }
    if (!$objetivo) json_error('Entrega no encontrada.', 404);

    $filtered = array_values(array_filter($entregas, fn($e) => $e['id'] !== $id));
    write_json_file('entregas.json', $filtered);

    // Revertir el estado de la solicitud asociada a "pendiente".
    $solicitudes = read_json_file('solicitudes.json');
    foreach ($solicitudes as &$s) {
        if ($s['cedula'] === $objetivo['cedula'] && $s['periodo_id'] === $objetivo['periodo_id']) {
            $s['estado'] = 'pendiente';
            break;
        }
    }
    unset($s);
    write_json_file('solicitudes.json', $solicitudes);

    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
