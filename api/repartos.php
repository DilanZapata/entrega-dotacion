<?php
// Reparto del inventario: cantidad fija de cada prenda por trabajador, por periodo.
require_once __DIR__ . '/_lib.php';

require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$repartos = read_json_file('repartos.json');

if ($method === 'GET') {
    if (isset($_GET['periodo_id'])) {
        foreach ($repartos as $r) {
            if ($r['periodo_id'] === $_GET['periodo_id']) json_response($r);
        }
        json_response(null);
    }
    json_response($repartos);
}

if ($method === 'POST') {
    $input = read_body();
    $periodoId = trim($input['periodo_id'] ?? '');
    if ($periodoId === '') json_error('Falta el periodo.');

    $prendas = [];
    foreach (($input['prendas'] ?? []) as $p) {
        $desc = trim((string)($p['descripcion'] ?? ''));
        $cant = (int)($p['cantidad'] ?? 0);
        if ($desc !== '' && $cant > 0) $prendas[] = ['descripcion' => $desc, 'cantidad' => $cant];
    }
    $cedulas = array_values(array_unique(array_filter(array_map('strval', $input['cedulas'] ?? []))));

    if (!$prendas) json_error('Indica cuántas unidades por persona de al menos una prenda.');
    if (!$cedulas) json_error('Selecciona al menos un trabajador.');

    $registro = [
        'id' => uuid_v4(),
        'periodo_id' => $periodoId,
        'prendas' => $prendas,
        'cedulas' => $cedulas,
        'fecha' => date('Y-m-d H:i:s'),
    ];

    $repartos = array_values(array_filter($repartos, fn($r) => $r['periodo_id'] !== $periodoId));
    $repartos[] = $registro;
    write_json_file('repartos.json', $repartos);
    json_response(['status' => 'success', 'reparto' => $registro]);
}

if ($method === 'DELETE') {
    $input = read_body();
    $periodoId = $input['periodo_id'] ?? '';
    write_json_file('repartos.json', array_values(array_filter($repartos, fn($r) => $r['periodo_id'] !== $periodoId)));
    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
