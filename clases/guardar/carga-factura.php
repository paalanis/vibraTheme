<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']);
    exit();
}

$factura  = (int)($_REQUEST['dato_factura']  ?? 0);
$fecha    = $_REQUEST['dato_fecha']           ?? '';
$cliente  = (int)($_REQUEST['dato_cliente']  ?? 0);
$producto = (int)($_REQUEST['dato_producto'] ?? 0);
$precio   = (float)($_REQUEST['dato_precio'] ?? 0);
$cantidad = (float)($_REQUEST['dato_cantidad'] ?? 0);
$sucursal = (int)($_REQUEST['dato_sucursal'] ?? 0);
$cierre   = (int)($_SESSION['cierre']        ?? 0); // id_cierre es NOT NULL en tb_ventas
$subtotal = round($precio * $cantidad, 2);

// Control de duplicado — prepared statement para evitar SQL injection.
// bind_result en lugar de get_result (compatible con hosting sin mysqlnd).
$stmtc = mysqli_prepare($conexion,
    "SELECT id_ventas FROM tb_ventas
     WHERE id_productos = ? AND id_sucursal = ? AND numero_factura = ?
       AND id_clientes = ? AND estado = '0'"
);
mysqli_stmt_bind_param($stmtc, 'iiii', $producto, $sucursal, $factura, $cliente);
mysqli_stmt_execute($stmtc);
mysqli_stmt_bind_result($stmtc, $id_venta_dup);
$duplicado = (bool)mysqli_stmt_fetch($stmtc);
mysqli_stmt_close($stmtc);

if ($duplicado) {
    echo json_encode(['success' => 'duplicado', 'factura' => $factura, 'cliente' => $cliente]);
    exit();
}

// Inserta en carrito (estado queda en 0 hasta confirmar factura).
// Tipos: s=fecha, i=cliente, i=sucursal, i=factura, i=producto,
//        d=cantidad, d=precio, d=subtotal, i=cierre
$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_ventas
     (fecha, id_clientes, id_sucursal, numero_factura, id_productos, cantidad, precio_venta, subtotal, id_cierre)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'siiiidddi',
    $fecha, $cliente, $sucursal, $factura, $producto, $cantidad, $precio, $subtotal, $cierre
);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => 'true', 'factura' => $factura, 'cliente' => $cliente]);
