<?php
require_once __DIR__ . '/_lib.php';

require_admin();

define('EPP_UPLOAD_DIR', __DIR__ . '/../uploads/epp_scans');

$id = $_GET['id'] ?? '';
if ($id === '') {
    http_response_code(400);
    exit('Falta el identificador.');
}

$registros = read_json_file('epp.json');
$registro = null;
foreach ($registros as $r) {
    if ($r['id'] === $id) { $registro = $r; break; }
}

if (!$registro || empty($registro['archivo_escaneado'])) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$archivo = EPP_UPLOAD_DIR . '/' . $registro['archivo_escaneado'];
if (!file_exists($archivo)) {
    http_response_code(404);
    exit('Archivo no encontrado en el servidor.');
}

$ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'pdf' => 'application/pdf',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="EPP_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $registro['nombre']) . '.' . $ext . '"');
header('Content-Length: ' . filesize($archivo));
readfile($archivo);
exit;
