<?php
require_once __DIR__ . '/_lib.php';
require_once __DIR__ . '/_inventario.php';

require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$inventario = read_json_file('inventario.json');

if ($method === 'GET') {
    if (isset($_GET['movimientos'])) {
        $movs = read_json_file('inventario_movimientos.json');
        json_response(array_slice(array_reverse($movs), 0, 200));
    }
    usort($inventario, fn($a, $b) => strcmp(inv_clave($a['descripcion'], $a['talla']), inv_clave($b['descripcion'], $b['talla'])));
    json_response($inventario);
}

if ($method === 'POST') {
    $input = read_body();
    $action = $input['action'] ?? '';

    // Carga masiva (desde Excel/CSV): suma al inventario actual o lo reemplaza.
    if ($action === 'cargar') {
        $modo = ($input['modo'] ?? 'sumar') === 'reemplazar' ? 'reemplazar' : 'sumar';

        $carga = [];
        foreach (($input['items'] ?? []) as $it) {
            $desc = trim(preg_replace('/\s+/', ' ', (string)($it['descripcion'] ?? '')));
            $talla = trim((string)($it['talla'] ?? ''));
            $cant = (int)($it['cantidad'] ?? 0);
            if ($desc === '' || $cant <= 0) continue;
            $clave = inv_clave($desc, $talla);
            if (isset($carga[$clave])) {
                $carga[$clave]['cantidad'] += $cant;
            } else {
                $carga[$clave] = ['descripcion' => $desc, 'talla' => $talla, 'cantidad' => $cant];
            }
        }
        if (!$carga) json_error('El archivo no tiene filas válidas (se necesita descripción y cantidad mayor a 0).');

        if ($modo === 'reemplazar') $inventario = [];

        $movs = [];
        foreach ($carga as $clave => $c) {
            $encontrado = false;
            foreach ($inventario as &$inv) {
                if (inv_clave($inv['descripcion'], $inv['talla']) === $clave) {
                    $inv['cantidad'] = (int)$inv['cantidad'] + $c['cantidad'];
                    $inv['actualizado'] = date('Y-m-d H:i:s');
                    $encontrado = true;
                    break;
                }
            }
            unset($inv);
            if (!$encontrado) {
                $inventario[] = ['id' => uuid_v4()] + $c + ['actualizado' => date('Y-m-d H:i:s')];
            }
            $movs[] = [
                'tipo' => $modo === 'reemplazar' ? 'carga (reemplazo)' : 'carga',
                'descripcion' => $c['descripcion'], 'talla' => $c['talla'], 'cantidad' => $c['cantidad'],
                'referencia' => 'Carga de archivo',
            ];
        }

        write_json_file('inventario.json', $inventario);
        inv_agregar_movimientos($movs);
        json_response(['status' => 'success', 'filas' => count($carga)]);
    }

    // Alta o ajuste manual de una prenda.
    if ($action === 'guardar_item') {
        $id = $input['id'] ?? '';
        $desc = trim(preg_replace('/\s+/', ' ', (string)($input['descripcion'] ?? '')));
        $talla = trim((string)($input['talla'] ?? ''));
        $cant = max(0, (int)($input['cantidad'] ?? 0));
        if ($desc === '') json_error('La descripción es obligatoria.');
        $clave = inv_clave($desc, $talla);

        foreach ($inventario as $inv) {
            if ($inv['id'] !== $id && inv_clave($inv['descripcion'], $inv['talla']) === $clave) {
                json_error('Ya existe esa prenda con esa talla en el inventario. Edita la existente.');
            }
        }

        if ($id !== '') {
            $ok = false;
            foreach ($inventario as &$inv) {
                if ($inv['id'] === $id) {
                    $diff = $cant - (int)$inv['cantidad'];
                    $inv['descripcion'] = $desc;
                    $inv['talla'] = $talla;
                    $inv['cantidad'] = $cant;
                    $inv['actualizado'] = date('Y-m-d H:i:s');
                    $ok = true;
                    break;
                }
            }
            unset($inv);
            if (!$ok) json_error('Prenda no encontrada.', 404);
            write_json_file('inventario.json', $inventario);
            if ($diff !== 0) {
                inv_agregar_movimientos([['tipo' => 'ajuste', 'descripcion' => $desc, 'talla' => $talla, 'cantidad' => $diff, 'referencia' => 'Ajuste manual']]);
            }
        } else {
            $inventario[] = ['id' => uuid_v4(), 'descripcion' => $desc, 'talla' => $talla, 'cantidad' => $cant, 'actualizado' => date('Y-m-d H:i:s')];
            write_json_file('inventario.json', $inventario);
            inv_agregar_movimientos([['tipo' => 'alta', 'descripcion' => $desc, 'talla' => $talla, 'cantidad' => $cant, 'referencia' => 'Alta manual']]);
        }
        json_response(['status' => 'success']);
    }

    json_error('Acción no soportada.');
}

if ($method === 'DELETE') {
    $input = read_body();
    $id = $input['id'] ?? '';
    $objetivo = null;
    foreach ($inventario as $inv) {
        if ($inv['id'] === $id) { $objetivo = $inv; break; }
    }
    if (!$objetivo) json_error('Prenda no encontrada.', 404);

    write_json_file('inventario.json', array_values(array_filter($inventario, fn($i) => $i['id'] !== $id)));
    inv_agregar_movimientos([[
        'tipo' => 'baja', 'descripcion' => $objetivo['descripcion'], 'talla' => $objetivo['talla'],
        'cantidad' => -(int)$objetivo['cantidad'], 'referencia' => 'Eliminada del inventario',
    ]]);
    json_response(['status' => 'success']);
}

json_error('Método no soportado.', 405);
