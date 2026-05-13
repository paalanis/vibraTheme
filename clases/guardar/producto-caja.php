<?php
ob_start(); // captura cualquier output accidental antes del JSON (warnings, BOM, etc.)
session_start();
if (!isset($_SESSION['usuario'])) {
    ob_clean(); header("Location: ../../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/descuentos.php';
if (mysqli_connect_errno()) {
    ob_clean(); echo json_encode(['success' => 'false']); exit();
}

$codigo_cargado = $_REQUEST['dato_codigo'] ?? '';
$sucursal       = (int)($_REQUEST['dato_sucursal'] ?? 1);

if ($codigo_cargado !== '') {
    if (substr($codigo_cargado, 0, 2) === '99') {
        // Producto pesable: barcode contiene cantidad embebida
        $codigo   = substr($codigo_cargado, 0, 7);
        $cantidad = (float)(substr($codigo_cargado, 7, 5)) / 1000;
    } else {
        $codigo   = $codigo_cargado;
        $cantidad = (float)($_REQUEST['dato_cantidad'] ?? 1);
    }
} else {
    $codigo   = $_REQUEST['dato_producto'] ?? '';
    $cantidad = (float)($_REQUEST['dato_cantidad'] ?? 1);
}

// ── Buscar producto — incluir id_marca e id_tipo para resolver descuentos ──
$stmt = mysqli_prepare($conexion,
    "SELECT id_productos, id_marca, id_tipo,
            precio_costo, margen_ganancia,
            ROUND(precio_costo * (1 + margen_ganancia / 100), 2) AS precio_lista_calc
     FROM tb_productos
     WHERE codigo = ?"
);
mysqli_stmt_bind_param($stmt, 's', $codigo);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $id_producto, $id_marca, $id_tipo, $precio_costo, $margen_ganancia, $precio_lista_sql);
$found = (bool)mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$cierre  = (int)($_SESSION['cierre']       ?? 0);
$factura = (int)($_REQUEST['dato_factura'] ?? 0);
$cliente = (int)($_REQUEST['dato_cliente'] ?? 0);

if (!$found) {
    ob_clean(); echo json_encode(['success' => 'no_existe', 'factura' => $factura, 'cliente' => $cliente, 'cierre' => $cierre]);
    exit();
}

$fecha = $_REQUEST['dato_fecha'] ?? '';

// ── Resolver descuento activo para este producto ───────────────────────────
$desc          = descuento_resolver($conexion, (int)$id_producto, (int)$id_marca, (int)$id_tipo, $sucursal);
$precio_lista  = (float)$precio_lista_sql;
$desc_pct      = (float)$desc['porcentaje'];   // 0.0 si no hay descuento
$id_desc_val   = $desc['id_descuento'];         // null si no hay descuento

// precio_venta = precio final que paga el cliente (con descuento aplicado)
$precio_venta   = round($precio_lista * (1 - $desc_pct / 100), 2);
// descuento_monto = $ ahorrados en esta línea
$descuento_monto = round(($precio_lista - $precio_venta) * $cantidad, 2);
$subtotal        = round($precio_venta * $cantidad, 2);

// ── Verificar configuración de stock por sucursal ──────────────────────────
$permite_sin_stock = '1';
$stmt_cfg = mysqli_prepare($conexion,
    "SELECT valor FROM tb_configuracion
     WHERE id_sucursal = ? AND clave = 'permite_venta_sin_stock' LIMIT 1"
);
if ($stmt_cfg) {
    mysqli_stmt_bind_param($stmt_cfg, 'i', $sucursal);
    mysqli_stmt_execute($stmt_cfg);
    mysqli_stmt_bind_result($stmt_cfg, $cfg_valor);
    $permite_sin_stock = mysqli_stmt_fetch($stmt_cfg) ? $cfg_valor : '1';
    mysqli_stmt_close($stmt_cfg);
}

// ── Stock físico disponible ────────────────────────────────────────────────
$stmt_stock = mysqli_prepare($conexion,
    "SELECT COALESCE(cantidad, 0) FROM tb_existencias WHERE id_productos = ?"
);
mysqli_stmt_bind_param($stmt_stock, 'i', $id_producto);
mysqli_stmt_execute($stmt_stock);
mysqli_stmt_bind_result($stmt_stock, $stock_fisico);
$stock_fisico = mysqli_stmt_fetch($stmt_stock) ? (float)$stock_fisico : 0;
mysqli_stmt_close($stmt_stock);

// Descontar lo ya reservado en carritos abiertos (estado=0)
$stmt_carrito = mysqli_prepare($conexion,
    "SELECT COALESCE(SUM(cantidad), 0) FROM tb_ventas
     WHERE id_productos = ? AND id_sucursal = ? AND estado = '0'"
);
mysqli_stmt_bind_param($stmt_carrito, 'ii', $id_producto, $sucursal);
mysqli_stmt_execute($stmt_carrito);
mysqli_stmt_bind_result($stmt_carrito, $en_carrito);
$en_carrito = mysqli_stmt_fetch($stmt_carrito) ? (float)$en_carrito : 0;
mysqli_stmt_close($stmt_carrito);

$stock_actual = $stock_fisico - $en_carrito;
$sin_stock    = ($stock_actual < $cantidad);

if ($sin_stock && $permite_sin_stock === '0') {
    ob_clean(); echo json_encode([
        'success'  => 'sin_stock',
        'factura'  => $factura,
        'cliente'  => $cliente,
        'cierre'   => $cierre,
        'stock'    => $stock_actual,
        'necesita' => $cantidad,
    ]);
    exit();
}

// ── Insertar en tb_ventas con descuento ────────────────────────────────────
$stmt2 = mysqli_prepare($conexion,
    "INSERT INTO tb_ventas
     (fecha, id_clientes, id_sucursal, numero_factura, id_productos, cantidad,
      precio_lista, descuento_pct, descuento_monto, id_descuento,
      precio_venta, subtotal, id_cierre)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt2, 'siiiiddddiddi',
    $fecha, $cliente, $sucursal, $factura, $id_producto,
    $cantidad,
    $precio_lista, $desc_pct, $descuento_monto, $id_desc_val,
    $precio_venta, $subtotal, $cierre
);
mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

ob_clean(); echo json_encode([
    'success'   => 'true',
    'factura'   => $factura,
    'cliente'   => $cliente,
    'cierre'    => $cierre,
    'sin_stock' => $sin_stock,
]);
