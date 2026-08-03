<?php
require_once __DIR__ . '/_lib.php';

$method = $_SERVER['REQUEST_METHOD'];
$periodos = read_json_file('periodos.json');

if ($method === 'GET') {
    if (isset($_GET['activo'])) {
        foreach ($periodos as $p) {
            if ($p['estado'] === 'activo') json_response($p);
        }
        json_response(null);
    }
    if (isset($_GET['id'])) {
        foreach ($periodos as $p) {
            if ($p['id'] === $_GET['id']) json_response($p);
        }
        json_error('Periodo no encontrado.', 404);
    }
    json_response($periodos);
}

require_admin();

if ($method === 'POST') {
    $input = read_body();
    $nombre = trim($input['nombre'] ?? '');
    if ($nombre === '') json_error('El nombre del periodo es obligatorio.');

    $estado = $input['estado'] ?? 'planificado';
    $id = $input['id'] ?? uuid_v4();

    foreach ($periodos as $p) {
        if ($p['id'] === $id) json_error('Ya existe un periodo con ese identificador.');
    }

    if ($estado === 'activo') {
        foreach ($periodos as &$p) {
            if ($p['estado'] === 'activo') $p['estado'] = 'cerrado';
        }
        unset($p);
    }

    $nuevo = [
        'id' => $id,
        'nombre' => $nombre,
        'estado' => $estado,
        'fecha_inicio' => $input['fecha_inicio'] ?? '',
        'fecha_fin' => $input['fecha_fin'] ?? '',
        'creado' => date('Y-m-d H:i:s'),
    ];
    $periodos[] = $nuevo;
    write_json_file('periodos.json', $periodos);
    json_response(['status' => 'success', 'periodo' => $nuevo]);
}

if ($method === 'PUT') {
    $input = read_body();
    $id = $input['id'] ?? '';
    if ($id === '') json_error('Falta el identificador del periodo.');

    $found = false;
    if (($input['estado'] ?? '') === 'activo') {
        foreach ($periodos as &$p) {
            if ($p['id'] !== $id && $p['estado'] === 'activo') $p['estado'] = 'cerrado';
        }
        unset($p);
    }
    foreach ($periodos as &$p) {
        if ($p['id'] === $id) {
            $p['nombre'] = $input['nombre'] ?? $p['nombre'];
            $p['estado'] = $input['estado'] ?? $p['estado'];
            $p['fecha_inicio'] = $input['fecha_inicio'] ?? $p['fecha_inicio'];
            $p['fecha_fin'] = $input['fecha_fin'] ?? $p['fecha_fin'];
            $found = true;
            break;
        }
    }
    unset($p);
    if (!$found) json_error('Periodo no encontrado.', 404);

    write_json_file('periodos.json', $periodos);
    json_response(['status' => 'success']);
}

if ($method === 'DELETE') {
    $input = read_body();
    $id = $input['id'] ?? ($_GET['id'] ?? '');
    if ($id === '') json_error('Falta el identificador del periodo.');

    $filtered = array_values(array_filter($periodos, fn($p) => $p['id'] !== $id));
    if (count($filtered) === count($periodos)) json_error('Periodo no encontrado.', 404);

    write_json_file('periodos.json', $filtered);
    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
