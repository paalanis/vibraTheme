<?php
/**
 * conexion/descuentos.php — Resolver de descuentos y ofertas v2.
 *
 * Lógica de acumulación (Opción B):
 *   - Reglas NO acumulables (acumulable=0): compiten → gana la de mayor prioridad (cascada),
 *     desempate por mayor %.
 *   - Reglas SÍ acumulables (acumulable=1): se combinan en cascada compuesta.
 *   - Final por producto: MAX(mejor_no_acumulable, combinadas_acumulables).
 *
 * Filtro por condición de pago:
 *   - condiciones_pago IS NULL → aplica a todas las condiciones.
 *   - condiciones_pago = '1,3' → solo aplica si id_condicion está en la lista.
 *   - id_condicion = 0 (no seleccionada aún) → solo reglas con condiciones_pago IS NULL.
 */

// ── Lógica interna de stacking ─────────────────────────────────────────────

/**
 * Recibe un array de reglas ya filtradas y ordenadas (prioridad DESC, porcentaje DESC)
 * y aplica la lógica de acumulación.
 *
 * @return array{porcentaje:float, id_descuento:int|null, nombre:string, acumulado:bool}
 */
function descuento_aplicar_logica(array $rules): array {
    if (empty($rules)) {
        return ['porcentaje' => 0.0, 'id_descuento' => null, 'nombre' => '', 'acumulado' => false];
    }

    $best_nonstackable = null;  // primer no-acumulable en el orden = ganador de cascada
    $stackable_rules   = [];

    foreach ($rules as $r) {
        if ((int)$r['acumulable'] === 1) {
            $stackable_rules[] = $r;
        } elseif ($best_nonstackable === null) {
            $best_nonstackable = $r;
            // No rompemos el loop porque necesitamos todos los acumulables
        }
    }

    // Descuento compuesto de acumulables: 1 - ∏(1 - pct_i/100)
    $compound_pct = 0.0;
    if (!empty($stackable_rules)) {
        $factor = 1.0;
        foreach ($stackable_rules as $r) {
            $factor *= (1.0 - (float)$r['porcentaje'] / 100.0);
        }
        $compound_pct = round((1.0 - $factor) * 100.0, 4);
    }

    $best_ns_pct = $best_nonstackable ? (float)$best_nonstackable['porcentaje'] : 0.0;

    if ($compound_pct >= $best_ns_pct) {
        // Acumulables ganan (o empate: preferimos acumulables)
        $id_desc = (count($stackable_rules) === 1) ? (int)$stackable_rules[0]['id_descuento'] : null;
        $nombre  = implode(' + ', array_column($stackable_rules, 'nombre'));
        return [
            'porcentaje'   => $compound_pct,
            'id_descuento' => $id_desc,
            'nombre'       => $nombre,
            'acumulado'    => count($stackable_rules) > 1,
        ];
    } else {
        // Mejor no-acumulable gana
        return [
            'porcentaje'   => $best_ns_pct,
            'id_descuento' => (int)$best_nonstackable['id_descuento'],
            'nombre'       => (string)$best_nonstackable['nombre'],
            'acumulado'    => false,
        ];
    }
}

// ── Resolver principal (una query por producto) ────────────────────────────

/**
 * Resuelve el descuento activo para un producto con condición de pago conocida.
 *
 * @param  int $id_condicion  ID de tb_condicion_venta. 0 = no seleccionada (solo universales).
 * @return array{porcentaje:float, id_descuento:int|null, nombre:string, acumulado:bool}
 */
function descuento_resolver(
    $conexion,
    int $id_producto,
    int $id_marca,
    int $id_tipo,
    int $id_sucursal  = 0,
    int $id_condicion = 0
): array {
    $hoy  = date('Y-m-d');
    $stmt = mysqli_prepare($conexion,
        "SELECT id_descuento, nombre, porcentaje, acumulable,
                CASE tipo_alcance
                    WHEN 'producto' THEN 4
                    WHEN 'tipo'     THEN 3
                    WHEN 'marca'    THEN 2
                    WHEN 'global'   THEN 1
                    ELSE 0
                END AS prioridad
         FROM tb_descuentos
         WHERE activo = 1
           AND (fecha_desde IS NULL OR fecha_desde <= ?)
           AND (fecha_hasta IS NULL OR fecha_hasta >= ?)
           AND (id_sucursal IS NULL OR id_sucursal = ?)
           AND (condiciones_pago IS NULL OR FIND_IN_SET(?, condiciones_pago))
           AND (
               tipo_alcance = 'global'
            OR (tipo_alcance = 'marca'    AND id_alcance = ?)
            OR (tipo_alcance = 'tipo'     AND id_alcance = ?)
            OR (tipo_alcance = 'producto' AND id_alcance = ?)
           )
         ORDER BY prioridad DESC, porcentaje DESC"
    );
    if (!$stmt) {
        return ['porcentaje' => 0.0, 'id_descuento' => null, 'nombre' => '', 'acumulado' => false];
    }
    // ssiiiii = 7 params
    mysqli_stmt_bind_param($stmt, 'ssiiiii',
        $hoy, $hoy, $id_sucursal, $id_condicion,
        $id_marca, $id_tipo, $id_producto
    );
    mysqli_stmt_execute($stmt);
    $r_id = $r_nom = $r_pct = $r_acum = $r_prio = null;
    mysqli_stmt_bind_result($stmt, $r_id, $r_nom, $r_pct, $r_acum, $r_prio);
    $rules = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rules[] = [
            'id_descuento' => (int)$r_id,
            'nombre'       => (string)$r_nom,
            'porcentaje'   => (float)$r_pct,
            'acumulable'   => (int)$r_acum,
            'prioridad'    => (int)$r_prio,
        ];
    }
    mysqli_stmt_close($stmt);
    return descuento_aplicar_logica($rules);
}

// ── Carga masiva en memoria (para N productos — etiquetas, recálculo carrito) ──

/**
 * Carga todas las reglas activas en un array PHP.
 * Usar con descuento_resolver_local() para procesar N productos sin N queries.
 * No filtra por condición de pago aquí — el filtro se hace en resolver_local().
 */
function descuento_cargar_activos($conexion, int $id_sucursal = 0): array {
    $hoy  = date('Y-m-d');
    $stmt = mysqli_prepare($conexion,
        "SELECT id_descuento, nombre, tipo_alcance, id_alcance, porcentaje,
                acumulable, condiciones_pago,
                CASE tipo_alcance
                    WHEN 'producto' THEN 4
                    WHEN 'tipo'     THEN 3
                    WHEN 'marca'    THEN 2
                    WHEN 'global'   THEN 1
                    ELSE 0
                END AS prioridad
         FROM tb_descuentos
         WHERE activo = 1
           AND (fecha_desde IS NULL OR fecha_desde <= ?)
           AND (fecha_hasta IS NULL OR fecha_hasta >= ?)
           AND (id_sucursal IS NULL OR id_sucursal = ?)
         ORDER BY prioridad DESC, porcentaje DESC"
    );
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, 'ssi', $hoy, $hoy, $id_sucursal);
    mysqli_stmt_execute($stmt);
    $r_id = $r_nom = $r_tipo = $r_alc = $r_pct = $r_acum = $r_cond = $r_prio = null;
    mysqli_stmt_bind_result($stmt, $r_id, $r_nom, $r_tipo, $r_alc, $r_pct, $r_acum, $r_cond, $r_prio);
    $rules = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rules[] = [
            'id_descuento'   => (int)$r_id,
            'nombre'         => (string)$r_nom,
            'tipo_alcance'   => (string)$r_tipo,
            'id_alcance'     => ($r_alc !== null) ? (int)$r_alc : null,
            'porcentaje'     => (float)$r_pct,
            'acumulable'     => (int)$r_acum,
            'condiciones_pago' => $r_cond, // string "1,3" o null
            'prioridad'      => (int)$r_prio,
        ];
    }
    mysqli_stmt_close($stmt);
    return $rules;
}

/**
 * Versión local (sin query) para N productos.
 * $rules = resultado de descuento_cargar_activos().
 *
 * @param  int $id_condicion  0 = solo universales (condiciones_pago IS NULL)
 */
function descuento_resolver_local(
    array $rules,
    int   $id_producto,
    int   $id_marca,
    int   $id_tipo,
    int   $id_condicion = 0
): array {
    $matching = [];
    foreach ($rules as $r) {
        // Filtro por condición de pago
        if ($r['condiciones_pago'] !== null) {
            if ($id_condicion === 0) continue; // condicion no seleccionada: saltear condicionales
            $ids = array_map('intval', explode(',', $r['condiciones_pago']));
            if (!in_array($id_condicion, $ids, true)) continue;
        }
        // Filtro por alcance
        $tipo = $r['tipo_alcance'];
        $alc  = $r['id_alcance'];
        $aplica = false;
        if      ($tipo === 'global')                          { $aplica = true; }
        elseif  ($tipo === 'marca'    && $alc === $id_marca)  { $aplica = true; }
        elseif  ($tipo === 'tipo'     && $alc === $id_tipo)   { $aplica = true; }
        elseif  ($tipo === 'producto' && $alc === $id_producto){ $aplica = true; }
        if ($aplica) $matching[] = $r;
    }
    // $matching ya está ordenado por prioridad DESC, porcentaje DESC (hereda orden de cargar_activos)
    return descuento_aplicar_logica($matching);
}

// ── Recalcular carrito completo ─────────────────────────────────────────────

/**
 * Recalcula los descuentos de todas las líneas pendientes (estado=0) de una factura.
 * Llamar desde recalcular-descuento.php (preview) y guardar/factura.php (final).
 * Debe ejecutarse DENTRO de una transacción activa si se llama desde guardar/factura.php.
 *
 * @return bool  true = OK, false = error de DB
 */
function descuento_recalcular_carrito(
    $conexion,
    int $factura,
    int $cierre,
    int $sucursal,
    int $id_condicion
): bool {
    if ($factura <= 0 || $cierre <= 0) return true; // carrito vacío/inválido

    // Cargar reglas activas una sola vez
    $rules = descuento_cargar_activos($conexion, $sucursal);

    // Obtener todas las líneas del carrito
    $stmt = mysqli_prepare($conexion,
        "SELECT v.id_ventas, v.id_productos, v.precio_lista, v.cantidad,
                p.id_marca, p.id_tipo
         FROM tb_ventas v
         INNER JOIN tb_productos p ON p.id_productos = v.id_productos
         WHERE v.numero_factura = ? AND v.id_cierre = ?
           AND v.estado = '0' AND v.id_sucursal = ?"
    );
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'iii', $factura, $cierre, $sucursal);
    mysqli_stmt_execute($stmt);
    $r_idv = $r_idp = $r_lista = $r_cant = $r_marca = $r_tipo = null;
    mysqli_stmt_bind_result($stmt, $r_idv, $r_idp, $r_lista, $r_cant, $r_marca, $r_tipo);
    $items = [];
    while (mysqli_stmt_fetch($stmt)) {
        $items[] = [
            'id_ventas'    => (int)$r_idv,
            'id_productos' => (int)$r_idp,
            'precio_lista' => (float)$r_lista,
            'cantidad'     => (float)$r_cant,
            'id_marca'     => (int)$r_marca,
            'id_tipo'      => (int)$r_tipo,
        ];
    }
    mysqli_stmt_close($stmt);
    if (empty($items)) return true;

    // Preparar UPDATE fuera del loop (eficiencia)
    $stmt_upd = mysqli_prepare($conexion,
        "UPDATE tb_ventas
         SET descuento_pct   = ?,
             descuento_monto = ?,
             id_descuento    = ?,
             precio_venta    = ?,
             subtotal        = ?
         WHERE id_ventas = ?"
    );
    if (!$stmt_upd) return false;

    foreach ($items as $item) {
        $desc   = descuento_resolver_local(
            $rules,
            $item['id_productos'],
            $item['id_marca'],
            $item['id_tipo'],
            $id_condicion
        );
        $pct    = $desc['porcentaje'];
        $pventa = round($item['precio_lista'] * (1.0 - $pct / 100.0), 2);
        $monto  = round(($item['precio_lista'] - $pventa) * $item['cantidad'], 2);
        $sub    = round($pventa * $item['cantidad'], 2);
        $id_d   = $desc['id_descuento']; // puede ser null (acumulado de múltiples)

        // 'ddiddi': pct(d), monto(d), id_desc(i), pventa(d), sub(d), id_ventas(i)
        mysqli_stmt_bind_param($stmt_upd, 'ddiddi',
            $pct, $monto, $id_d, $pventa, $sub, $item['id_ventas']
        );
        if (!mysqli_stmt_execute($stmt_upd)) {
            mysqli_stmt_close($stmt_upd);
            return false;
        }
    }
    mysqli_stmt_close($stmt_upd);
    return true;
}

// ── Conflictos ─────────────────────────────────────────────────────────────

/**
 * Detecta reglas activas que colisionan: mismo alcance + misma condición de pago
 * + al menos una no-acumulable (= compiten, solo una puede ganar, la otra es inútil).
 */
function descuento_conflictos($conexion, int $id_sucursal = 0): array {
    $hoy  = date('Y-m-d');
    $stmt = mysqli_prepare($conexion,
        "SELECT tipo_alcance,
                id_alcance,
                IFNULL(condiciones_pago, '__TODAS__') AS cond_key,
                condiciones_pago,
                COUNT(*) AS cantidad,
                GROUP_CONCAT(
                    CONCAT(nombre, ' (', porcentaje, '%',
                           IF(acumulable=1,' ✓acum',''), ')')
                    ORDER BY porcentaje DESC SEPARATOR ' · '
                ) AS detalle
         FROM tb_descuentos
         WHERE activo = 1
           AND (fecha_desde IS NULL OR fecha_desde <= ?)
           AND (fecha_hasta IS NULL OR fecha_hasta >= ?)
           AND (id_sucursal IS NULL OR id_sucursal = ?)
         GROUP BY tipo_alcance, id_alcance, IFNULL(condiciones_pago, '__TODAS__')
         HAVING COUNT(*) > 1 AND MIN(acumulable) = 0"
    );
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, 'ssi', $hoy, $hoy, $id_sucursal);
    mysqli_stmt_execute($stmt);
    $r_tipo = $r_alc = $r_ck = $r_cond = $r_cant = $r_det = null;
    mysqli_stmt_bind_result($stmt, $r_tipo, $r_alc, $r_ck, $r_cond, $r_cant, $r_det);
    $conflictos = [];
    while (mysqli_stmt_fetch($stmt)) {
        $conflictos[] = [
            'tipo'             => (string)$r_tipo,
            'alcance'          => $r_alc,
            'condiciones_pago' => $r_cond,
            'cant'             => (int)$r_cant,
            'detalle'          => (string)$r_det,
        ];
    }
    mysqli_stmt_close($stmt);
    return $conflictos;
}

/**
 * Devuelve reglas acumulables activas que se combinarían con una nueva regla.
 * Usar en el ABM para mostrar el preview de stacking antes de guardar.
 *
 * @param  int   $id_excluir      ID de la regla que se está editando (excluir de la lista)
 * @param  string|null $condiciones_pago  Condiciones de la nueva regla
 */
function descuento_preview_acumulacion(
    $conexion,
    int    $id_excluir        = 0,
    ?string $condiciones_pago = null,
    int    $id_sucursal       = 0
): array {
    $hoy  = date('Y-m-d');
    $stmt = mysqli_prepare($conexion,
        "SELECT id_descuento, nombre, porcentaje, condiciones_pago
         FROM tb_descuentos
         WHERE activo = 1
           AND acumulable = 1
           AND id_descuento != ?
           AND (fecha_desde IS NULL OR fecha_desde <= ?)
           AND (fecha_hasta IS NULL OR fecha_hasta >= ?)
           AND (id_sucursal IS NULL OR id_sucursal = ?)
         ORDER BY porcentaje DESC"
    );
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, 'issi', $id_excluir, $hoy, $hoy, $id_sucursal);
    mysqli_stmt_execute($stmt);
    $r_id = $r_nom = $r_pct = $r_cond = null;
    mysqli_stmt_bind_result($stmt, $r_id, $r_nom, $r_pct, $r_cond);
    $todas = [];
    while (mysqli_stmt_fetch($stmt)) {
        $todas[] = [
            'id_descuento'   => (int)$r_id,
            'nombre'         => (string)$r_nom,
            'porcentaje'     => (float)$r_pct,
            'condiciones_pago' => $r_cond,
        ];
    }
    mysqli_stmt_close($stmt);

    // Filtrar: solo las que comparten condicion con la nueva regla
    // Comparten si: ambas son NULL, o al menos tienen un ID en común
    $coincidentes = [];
    foreach ($todas as $r) {
        if ($condiciones_pago === null || $r['condiciones_pago'] === null) {
            // Una o ambas son universales → podrían solaparse
            $coincidentes[] = $r;
        } else {
            $ids_nueva    = array_map('intval', explode(',', $condiciones_pago));
            $ids_existente = array_map('intval', explode(',', $r['condiciones_pago']));
            if (!empty(array_intersect($ids_nueva, $ids_existente))) {
                $coincidentes[] = $r;
            }
        }
    }
    return $coincidentes;
}
