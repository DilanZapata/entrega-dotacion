<?php
require_once __DIR__ . '/_lib.php';

require_admin();

define('EPP_UPLOAD_DIR', __DIR__ . '/../uploads/epp_scans');
$EPP_EXTENSIONS_PERMITIDAS = ['jpg', 'jpeg', 'png', 'pdf'];

$method = $_SERVER['REQUEST_METHOD'];
$registros = read_json_file('epp.json');

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        foreach ($registros as $r) {
            if ($r['id'] === $_GET['id']) json_response($r);
        }
        json_response(null);
    }
    if (isset($_GET['cedula'])) {
        $delEmpleado = array_values(array_filter($registros, fn($r) => $r['cedula'] === $_GET['cedula']));
        usort($delEmpleado, fn($a, $b) => strcmp($b['fecha_generado'], $a['fecha_generado']));
        if (isset($_GET['historial'])) {
            json_response($delEmpleado);
        }
        $abierto = null;
        foreach ($delEmpleado as $r) {
            if ($r['estado'] === 'abierto') { $abierto = $r; break; }
        }
        json_response(['abierto' => $abierto, 'historial' => $delEmpleado]);
    }
    json_response($registros);
}

// Cierre de un formato (sube el escaneado): multipart/form-data.
$esMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;

if ($method === 'POST' && $esMultipart && ($_POST['action'] ?? '') === 'cerrar') {
    $id = $_POST['id'] ?? '';
    if ($id === '') json_error('Falta el identificador del formato.');
    if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        json_error('No se recibió el archivo escaneado correctamente.');
    }

    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $EPP_EXTENSIONS_PERMITIDAS, true)) {
        json_error('Formato de archivo no permitido. Usa JPG, PNG o PDF.');
    }

    $indice = -1;
    foreach ($registros as $k => $r) {
        if ($r['id'] === $id) { $indice = $k; break; }
    }
    if ($indice === -1) json_error('Formato EPP no encontrado.', 404);
    if ($registros[$indice]['estado'] !== 'abierto') json_error('Este formato ya está cerrado.');

    if (!is_dir(EPP_UPLOAD_DIR)) @mkdir(EPP_UPLOAD_DIR, 0775, true);
    $nombreArchivo = 'epp_' . $id . '.' . $ext;
    $destino = EPP_UPLOAD_DIR . '/' . $nombreArchivo;
    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
        json_error('No se pudo guardar el archivo. Verifica los permisos de escritura de uploads/epp_scans/.', 500);
    }

    $registros[$indice]['estado'] = 'cerrado';
    $registros[$indice]['archivo_escaneado'] = $nombreArchivo;
    $registros[$indice]['fecha_cierre'] = date('Y-m-d H:i:s');
    write_json_file('epp.json', $registros);

    json_response(['status' => 'success', 'registro' => $registros[$indice]]);
}

if ($method === 'POST') {
    $input = read_body();
    $cedula = trim($input['cedula'] ?? '');
    if ($cedula === '') json_error('Falta la cédula del empleado.');

    foreach ($registros as $r) {
        if ($r['cedula'] === $cedula && $r['estado'] === 'abierto') {
            json_error('Este empleado ya tiene un formato EPP abierto. Debes cerrarlo (subir el escaneado) antes de generar uno nuevo.', 409);
        }
    }

    $nuevo = [
        'id' => uuid_v4(),
        'cedula' => $cedula,
        'nombre' => $input['nombre'] ?? '',
        'cargo' => $input['cargo'] ?? '',
        'estado' => 'abierto',
        'fecha_generado' => date('Y-m-d H:i:s'),
        'fecha_cierre' => null,
        'archivo_escaneado' => null,
    ];
    $registros[] = $nuevo;
    write_json_file('epp.json', $registros);

    json_response(['status' => 'success', 'registro' => $nuevo]);
}

if ($method === 'DELETE') {
    $input = read_body();
    $id = $input['id'] ?? ($_GET['id'] ?? '');
    if ($id === '') json_error('Falta el identificador del formato.');

    $objetivo = null;
    foreach ($registros as $r) {
        if ($r['id'] === $id) { $objetivo = $r; break; }
    }
    if (!$objetivo) json_error('Formato EPP no encontrado.', 404);

    if (!empty($objetivo['archivo_escaneado'])) {
        $archivo = EPP_UPLOAD_DIR . '/' . $objetivo['archivo_escaneado'];
        if (file_exists($archivo)) @unlink($archivo);
    }

    $filtrados = array_values(array_filter($registros, fn($r) => $r['id'] !== $id));
    write_json_file('epp.json', $filtrados);

    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
