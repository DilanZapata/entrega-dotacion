<?php
// Helpers del inventario de prendas, compartidos por inventario.php y entregas.php.

function inv_norm(string $s): string {
    $s = trim(preg_replace('/\s+/', ' ', $s));
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

function inv_clave(string $descripcion, string $talla): string {
    return inv_norm($descripcion) . '|' . inv_norm($talla);
}

function inv_agregar_movimientos(array $nuevos): void {
    if (!$nuevos) return;
    $movs = read_json_file('inventario_movimientos.json');
    foreach ($nuevos as $m) {
        $movs[] = $m + ['fecha' => date('Y-m-d H:i:s')];
    }
    if (count($movs) > 1000) $movs = array_slice($movs, -1000);
    write_json_file('inventario_movimientos.json', $movs);
}

// Descuenta del inventario los items entregados. Solo descuenta lo que exista
// (misma descripción y talla) y nunca deja existencias negativas.
// Devuelve lo realmente descontado, para poder reintegrarlo si la entrega se corrige o elimina.
function inv_descontar(array $items, string $referencia): array {
    $inventario = read_json_file('inventario.json');
    if (!$inventario) return [];

    $descontado = [];
    $movs = [];
    foreach ($items as $it) {
        $cant = (int)($it['cantidad'] ?? 0);
        if ($cant <= 0) continue;
        $clave = inv_clave((string)($it['descripcion'] ?? ''), (string)($it['talla'] ?? ''));

        foreach ($inventario as &$inv) {
            if (inv_clave($inv['descripcion'], $inv['talla']) !== $clave) continue;
            $real = min($cant, (int)$inv['cantidad']);
            if ($real > 0) {
                $inv['cantidad'] = (int)$inv['cantidad'] - $real;
                $inv['actualizado'] = date('Y-m-d H:i:s');
                $descontado[] = ['id' => $inv['id'], 'descripcion' => $inv['descripcion'], 'talla' => $inv['talla'], 'cantidad' => $real];
                $movs[] = ['tipo' => 'entrega', 'descripcion' => $inv['descripcion'], 'talla' => $inv['talla'], 'cantidad' => -$real, 'referencia' => $referencia];
            }
            break;
        }
        unset($inv);
    }

    if ($descontado) {
        write_json_file('inventario.json', $inventario);
        inv_agregar_movimientos($movs);
    }
    return $descontado;
}

// Devuelve al inventario lo que una entrega había descontado.
function inv_reintegrar(array $descontado, string $referencia): void {
    if (!$descontado) return;
    $inventario = read_json_file('inventario.json');
    $movs = [];

    foreach ($descontado as $d) {
        $cant = (int)($d['cantidad'] ?? 0);
        if ($cant <= 0) continue;
        $clave = inv_clave((string)$d['descripcion'], (string)$d['talla']);
        $encontrado = false;

        foreach ($inventario as &$inv) {
            if (($inv['id'] ?? '') === ($d['id'] ?? null) || inv_clave($inv['descripcion'], $inv['talla']) === $clave) {
                $inv['cantidad'] = (int)$inv['cantidad'] + $cant;
                $inv['actualizado'] = date('Y-m-d H:i:s');
                $encontrado = true;
                break;
            }
        }
        unset($inv);

        if (!$encontrado) {
            $inventario[] = [
                'id' => uuid_v4(),
                'descripcion' => $d['descripcion'],
                'talla' => $d['talla'],
                'cantidad' => $cant,
                'actualizado' => date('Y-m-d H:i:s'),
            ];
        }
        $movs[] = ['tipo' => 'reintegro', 'descripcion' => $d['descripcion'], 'talla' => $d['talla'], 'cantidad' => $cant, 'referencia' => $referencia];
    }

    write_json_file('inventario.json', $inventario);
    inv_agregar_movimientos($movs);
}
