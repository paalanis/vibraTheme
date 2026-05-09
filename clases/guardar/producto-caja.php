<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']); exit();
}

$codigo_cargado = $_REQUEST['dato_codigo'] ?? '';
$sucursal       = (int)($_REQUEST['dato_sucursal'] ?? 1);

if ($codigo_cargado !== '') {
    if (substr($codigo_cargado, 0, 2) === '99') {
        $codigo     = substr($codigo_cargado, 0, 7);
        $cualprecio = 'sql';
        $cantidad   = (float)(substr($codigo_cargado, 7, 5)) / 1000;
    } else {
        $codigo     = $codigo_cargado;
        $cualprecio = 'sql';
        $cantidad   = (float)($_REQUEST['dato_cantidad'] ?? 1);
    }
} else {
    $codigo     = $_REQUEST['dato_producto'] ?? '';
    $cualprecio = 'manual';
    $cantidad   = (float)($_REQUEST['dato_cantidad'] ?? 1);
}

// ── Buscar producto ───────────────────────────────────────────────────────────
$stmt = mysqli_prepare($conexion,
    "SELECT precio_venta, id_productos, pesable FROM tb_productos WHERE codigo = ?"
);
mysqli_stmt_bind_param($stmt, 's', $codigo);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $precio_sql, $id_producto, $pesable);
$found = (bool)mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$cierre  = (int)($_SESSION['cierre']       ?? 0);
$factura = (int)($_REQUEST['dato_factura'] ?? 0);
$cliente = (int)($_REQUEST['dato_cliente'] ?? 0);

if (!$found) {
    echo json_encode(['success' => 'no_existe', 'factura' => $factura, 'cliente' => $cliente, 'cierre' => $cierre]);
    exit();
}

$fecha    = $_REQUEST['dato_fecha'] ?? '';
$precio   = ($cualprecio === 'sql')
                ? (float)$precio_sql
                : (float)($_REQUEST['dato_precio'] ?? 0);
$subtotal = round($precio * $cantidad, 2);

// ── Verificar configuración de stock ─────────────────────────────────────────
$permite_sin_stock = '1'; // default: permite vender sin stock
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

// ── Consultar stock actual ────────────────────────────────────────────────────
$stmt_stock = mysqli_prepare($conexion,
    "SELECT COALESCE(cantidad, 0) FROM tb_existencias WHERE id_productos = ?"
);
mysqli_stmt_bind_param($stmt_stock, 'i', $id_producto);
mysqli_stmt_execute($stmt_stock);
mysqli_stmt_bind_result($stmt_stock, $stock_actual);
$stock_actual = mysqli_stmt_fetch($stmt_stock) ? (float)$stock_actual : 0;
mysqli_stmt_close($stmt_stock);

$sin_stock = ($stock_actual < $cantidad);

// ── Bloquear si config lo exige ───────────────────────────────────────────────
if ($sin_stock && $permite_sin_stock === '0') {
    echo json_encode([
        'success'   => 'sin_stock',
        'factura'   => $factura,
        'cliente'   => $cliente,
        'cierre'    => $cierre,
        'stock'     => $stock_actual,
        'necesita'  => $cantidad,
    ]);
    exit();
}

// ── Insertar en venta ─────────────────────────────────────────────────────────
$stmt2 = mysqli_prepare($conexion,
    "INSERT INTO tb_ventas
     (fecha, id_clientes, id_sucursal, numero_factura, id_productos, cantidad, precio_venta, subtotal, id_cierre)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt2, 'siiiidddi',
    $fecha, $cliente, $sucursal, $factura, $id_producto, $cantidad, $precio, $subtotal, $cierre
);
mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

echo json_encode([
    'success'   => 'true',
    'factura'   => $factura,
    'cliente'   => $cliente,
    'cierre'    => $cierre,
    'sin_stock' => $sin_stock, // aviso visual cuando permite_sin_stock = 1
]);
