<?php
/**
 * conexion/descuentos.php — Resolver de descuentos y ofertas.
 *
 * Incluir DESPUÉS de conexion.php (requiere $conexion ya inicializado).
 * Solo define funciones, sin efectos secundarios.
 */

/**
 * Resuelve el descuento activo para un producto específico.
 *
 * Cascada de prioridad (mayor número = mayor prioridad):
 *   producto (4) > tipo (3) > marca (2) > global (1)
 * Desempate: mayor porcentaje gana.
 *
 * @return array{porcentaje:float, id_descuento:int|null, nombre:string}
 */
function descuento_resolver(
    $conexion,
    int $id_producto,
    int $id_marca,
    int $id_tipo,
    int $id_sucursal = 0
): array {
    $hoy  = date('Y-m-d');
    $stmt = mysqli_prepare($conexion,
        "SELECT id_descuento, nombre, porcentaje,
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
           AND (
               tipo_alcance = 'global'
            OR (tipo_alcance = 'marca'    AND id_alcance = ?)
            OR (tipo_alcance = 'tipo'     AND id_alcance = ?)
            OR (tipo_alcance = 'producto' AND id_alcance = ?)
           )
         ORDER BY prioridad DESC, porcentaje DESC
         LIMIT 1"
    );
    if (!$stmt) {
        return ['porcentaje' => 0.0, 'id_descuento' => null, 'nombre' => ''];
    }
    mysqli_stmt_bind_param($stmt, 'ssiiii',
        $hoy, $hoy, $id_sucursal,
        $id_marca, $id_tipo, $id_producto
    );
    mysqli_stmt_execute($stmt);
    $r_id = $r_nombre = $r_pct = $r_prio = null;
    mysqli_stmt_bind_result($stmt, $r_id, $r_nombre, $r_pct, $r_prio);
    $found = (bool) mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$found) {
        return ['porcentaje' => 0.0, 'id_descuento' => null, 'nombre' => ''];
    }
    return [
        'porcentaje'   => (float) $r_pct,
        'id_descuento' => (int)   $r_id,
        'nombre'       => (string)$r_nombre,
    ];
}

/**
 * Carga TODAS las reglas activas en memoria.
 * Usar para N productos (ej: etiquetas) — evita N queries.
 */
function descuento_cargar_activos($conexion, int $id_sucursal = 0): array {
    $hoy  = date('Y-m-d');
    $stmt = mysqli_prepare($conexion,
        "SELECT id_descuento, nombre, tipo_alcance, id_alcance, porcentaje,
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
    $r_id = $r_nombre = $r_tipo = $r_alcance = $r_pct = $r_prio = null;
    mysqli_stmt_bind_result($stmt, $r_id, $r_nombre, $r_tipo, $r_alcance, $r_pct, $r_prio);
    $rules = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rules[] = [
            'id_descuento' => (int)   $r_id,
            'nombre'       => (string)$r_nombre,
            'tipo_alcance' => (string)$r_tipo,
            'id_alcance'   => ($r_alcance !== null) ? (int)$r_alcance : null,
            'porcentaje'   => (float) $r_pct,
        ];
    }
    mysqli_stmt_close($stmt);
    return $rules;
}

/**
 * Resuelve el descuento para un producto usando reglas ya cargadas.
 * Cero queries adicionales — para procesar N productos de una vez.
 *
 * @param  array $rules  Resultado de descuento_cargar_activos()
 */
function descuento_resolver_local(
    array $rules,
    int   $id_producto,
    int   $id_marca,
    int   $id_tipo
): array {
    foreach ($rules as $r) {
        $tipo    = $r['tipo_alcance'];
        $alcance = $r['id_alcance'];
        if ($tipo === 'global') {
            // aplica a todos — no filtrar
        } elseif ($tipo === 'marca'    && $alcance !== $id_marca)    { continue; }
        elseif  ($tipo === 'tipo'      && $alcance !== $id_tipo)     { continue; }
        elseif  ($tipo === 'producto'  && $alcance !== $id_producto) { continue; }
        else { continue; }
        return [
            'porcentaje'   => $r['porcentaje'],
            'id_descuento' => $r['id_descuento'],
            'nombre'       => $r['nombre'],
        ];
    }
    return ['porcentaje' => 0.0, 'id_descuento' => null, 'nombre' => ''];
}

/**
 * Detecta conflictos: misma prioridad, mismo alcance, activas al mismo tiempo.
 */
function descuento_conflictos($conexion, int $id_sucursal = 0): array {
    $hoy  = date('Y-m-d');
    $stmt = mysqli_prepare($conexion,
        "SELECT tipo_alcance, id_alcance, COUNT(*) AS cantidad,
                GROUP_CONCAT(
                    CONCAT(nombre, ' (', porcentaje, '%)')
                    ORDER BY porcentaje DESC SEPARATOR ' · '
                ) AS detalle
         FROM tb_descuentos
         WHERE activo = 1
           AND (fecha_desde IS NULL OR fecha_desde <= ?)
           AND (fecha_hasta IS NULL OR fecha_hasta >= ?)
           AND (id_sucursal IS NULL OR id_sucursal = ?)
         GROUP BY tipo_alcance, id_alcance
         HAVING cantidad > 1"
    );
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, 'ssi', $hoy, $hoy, $id_sucursal);
    mysqli_stmt_execute($stmt);
    $r_tipo = $r_alcance = $r_cant = $r_det = null;
    mysqli_stmt_bind_result($stmt, $r_tipo, $r_alcance, $r_cant, $r_det);
    $conflictos = [];
    while (mysqli_stmt_fetch($stmt)) {
        $conflictos[] = [
            'tipo'    => (string)$r_tipo,
            'alcance' => $r_alcance,
            'cant'    => (int)   $r_cant,
            'detalle' => (string)$r_det,
        ];
    }
    mysqli_stmt_close($stmt);
    return $conflictos;
}
