<?php
date_default_timezone_set('America/Bogota');
session_name('dotacion_admin');
session_start();

require_once __DIR__ . '/admin_config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('DATA_DIR', __DIR__ . '/../data');

function json_response($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): void {
    json_response(['status' => 'error', 'message' => $message], $status);
}

function read_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function read_json_file(string $name): array {
    $file = DATA_DIR . '/' . $name;
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function write_json_file(string $name, array $data): void {
    $file = DATA_DIR . '/' . $name;
    $tmp = $file . '.tmp';
    $written = @file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($written === false) {
        json_error("No se pudo escribir en data/$name. Verifica los permisos de escritura de la carpeta data/ para el usuario del servidor web.", 500);
    }
    if (!@rename($tmp, $file)) {
        json_error("No se pudo guardar data/$name (falló el rename). Verifica los permisos de escritura de la carpeta data/.", 500);
    }
}

function uuid_v4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function is_admin(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin(): void {
    if (!is_admin()) {
        json_error('No autorizado. Inicia sesión como administrador.', 401);
    }
}
