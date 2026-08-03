<?php
require_once __DIR__ . '/_lib.php';

$method = $_SERVER['REQUEST_METHOD'];
$empleados = read_json_file('empleados.json');

if ($method === 'GET') {
    if (isset($_GET['cedula'])) {
        $cedula = trim($_GET['cedula']);
        foreach ($empleados as $e) {
            if ($e['cedula'] === $cedula) json_response($e);
        }
        json_response(null);
    }
    // Listado completo: solo administración.
    require_admin();
    json_response($empleados);
}

require_admin();

if ($method === 'POST') {
    $input = read_body();
    $cedula = trim($input['cedula'] ?? '');
    $nombre = trim($input['nombre'] ?? '');
    $cargo = trim($input['cargo'] ?? '');
    if ($cedula === '' || $nombre === '' || $cargo === '') {
        json_error('Cédula, nombre y cargo son obligatorios.');
    }
    foreach ($empleados as $e) {
        if ($e['cedula'] === $cedula) json_error('Ya existe un empleado con esa cédula.');
    }
    $nuevo = [
        'cedula' => $cedula,
        'nombre' => strtoupper($nombre),
        'cargo' => strtoupper($cargo),
        'genero' => strtoupper($input['genero'] ?? 'HOMBRE'),
        'activo' => true,
    ];
    $empleados[] = $nuevo;
    write_json_file('empleados.json', $empleados);
    json_response(['status' => 'success', 'empleado' => $nuevo]);
}

if ($method === 'PUT') {
    $input = read_body();
    $cedula = $input['cedula'] ?? '';
    if ($cedula === '') json_error('Falta la cédula del empleado.');

    $found = false;
    foreach ($empleados as &$e) {
        if ($e['cedula'] === $cedula) {
            $e['nombre'] = strtoupper($input['nombre'] ?? $e['nombre']);
            $e['cargo'] = strtoupper($input['cargo'] ?? $e['cargo']);
            $e['genero'] = strtoupper($input['genero'] ?? $e['genero']);
            $e['activo'] = array_key_exists('activo', $input) ? (bool)$input['activo'] : $e['activo'];
            $found = true;
            break;
        }
    }
    unset($e);
    if (!$found) json_error('Empleado no encontrado.', 404);

    write_json_file('empleados.json', $empleados);
    json_response(['status' => 'success']);
}

if ($method === 'DELETE') {
    $input = read_body();
    $cedula = $input['cedula'] ?? ($_GET['cedula'] ?? '');
    if ($cedula === '') json_error('Falta la cédula del empleado.');

    $filtered = array_values(array_filter($empleados, fn($e) => $e['cedula'] !== $cedula));
    if (count($filtered) === count($empleados)) json_error('Empleado no encontrado.', 404);

    write_json_file('empleados.json', $filtered);
    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
