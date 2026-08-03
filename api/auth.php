<?php
require_once __DIR__ . '/_lib.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    json_response(['logged_in' => is_admin()]);
}

if ($method === 'POST') {
    $input = read_body();
    $action = $input['action'] ?? 'login';

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        json_response(['status' => 'success']);
    }

    // login
    $password = $input['password'] ?? '';
    if ($password !== '' && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        json_response(['status' => 'success']);
    }

    json_error('Contraseña incorrecta.', 401);
}

json_error('Método no soportado.', 405);
