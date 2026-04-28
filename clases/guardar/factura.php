<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$cliente   = (int)($_POST['dato_cliente']   ?? 0);
$sucursal  = (int)($_POST['dato_sucursal']  ?? 0);
$factura   = (int)($_POST['dato_factura']   ?? 0);
$condicion = (int)($_POST['dato_condicion'] ?? 0);
$cupon     = $_POST['dato_cupon'] ?? '';
$cierre    = (int)($_SESSION['cierre']      ?? 0);

$stmt1 = mysqli_prepare($conexion,
    "INSERT IGNORE INTO tb_existencias (id_productos, cantidad)
     SELECT tv.id_productos, tv.cantidad * -1
     FROM tb_ventas tv
     WHERE tv.id_sucursal = ? AND tv.numero_factura = ? AND tv.estado = '0' AND tv.id_cierre = ?
     ON DUPLICATE KEY UPDATE tb_existencias.cantidad = tb_existencias.cantidad + (tv.cantidad * -1)"
);
mysqli_stmt_bind_param($stmt1, 'iii', $sucursal, $factura, $cierre);
if (!mysqli_stmt_execute($stmt1)) {
    echo json_encode(['success' => 'false', 'error' => 'Error actualizando stock']);
    mysqli_stmt_close($stmt1); exit();
}
mysqli_stmt_close($stmt1);

$stmt2 = mysqli_prepare($conexion,
    "UPDATE tb_ventas SET estado = '1', cupon = ?, id_condicion_venta = ?
     WHERE estado = '0' AND id_cierre = ? AND id_sucursal = ?
       AND numero_factura = ? AND id_clientes = ?"
);
mysqli_stmt_bind_param($stmt2, 'siiiii', $cupon, $condicion, $cierre, $sucursal, $factura, $cliente);
echo json_encode(['success' => mysqli_stmt_execute($stmt2) ? 'true' : 'false', 'tipo' => 'ticket']);
mysqli_stmt_close($stmt2);