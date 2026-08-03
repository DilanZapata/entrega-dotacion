<?php
require_once __DIR__ . '/_lib.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    json_response(read_json_file('config.json'));
}

require_admin();

if ($method === 'POST' || $method === 'PUT') {
    $input = read_body();

    $config = [
        'tallas_pantalon' => array_values($input['tallas_pantalon'] ?? []),
        'tallas_camisa' => array_values($input['tallas_camisa'] ?? []),
        'tipos_calzado' => array_values($input['tipos_calzado'] ?? []),
        'relacion_camisas' => array_values($input['relacion_camisas'] ?? []),
    ];

    write_json_file('config.json', $config);
    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
