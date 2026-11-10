<?php
require_once __DIR__ . '/_lib.php';

$method = $_SERVER['REQUEST_METHOD'];
$empleados = read_json_file('empleados.json');

function fecha_o_null($v): ?string {
    $v = trim((string)($v ?? ''));
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
}

function limite_o_null($v): ?int {
    return ($v !== '' && $v !== null) ? max(1, (int)$v) : null;
}

// Aplica sobre $e los campos opcionales que vengan en $input (vinculación, ARL, límite de camisas).
function aplicar_campos_opcionales(array &$e, array $input): void {
    if (array_key_exists('limite_camisas', $input)) {
        $e['limite_camisas'] = limite_o_null($input['limite_camisas']);
    }
    if (array_key_exists('tipo_vinculacion', $input)) {
        $e['tipo_vinculacion'] = strtoupper((string)$input['tipo_vinculacion']) === 'CONTRATISTA' ? 'CONTRATISTA' : 'EMPLEADO';
    }
    foreach (['arl_nombre', 'arl_numero'] as $campo) {
        if (array_key_exists($campo, $input)) $e[$campo] = trim((string)$input[$campo]);
    }
    foreach (['arl_vigente_hasta', 'arl_ultima_revision'] as $campo) {
        if (array_key_exists($campo, $input)) $e[$campo] = fecha_o_null($input[$campo]);
    }
}

if ($method === 'GET') {
    if (isset($_GET['cedula'])) {
        $cedula = trim($_GET['cedula']);
        foreach ($empleados as $e) {
            if ($e['cedula'] === $cedula) {
                // La consulta por cédula es pública (formulario de solicitud): no exponer datos de ARL.
                if (!is_admin()) {
                    $e = array_intersect_key($e, array_flip(['cedula', 'nombre', 'cargo', 'genero', 'activo', 'limite_camisas']));
                }
                json_response($e);
            }
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
        'limite_camisas' => null,
        'tipo_vinculacion' => 'EMPLEADO',
    ];
    aplicar_campos_opcionales($nuevo, $input);

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
            aplicar_campos_opcionales($e, $input);
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
