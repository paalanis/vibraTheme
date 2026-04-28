<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$nombre      = $_POST['dato_nombre']      ?? '';
$descripcion = $_POST['dato_descripcion'] ?? '';
$costo       = (float)($_POST['dato_costo']   ?? 0);
$venta       = (float)($_POST['dato_venta']   ?? 0);
$codigo      = $_POST['dato_codigo']      ?? '';
$rubro       = (int)($_POST['dato_rubro']     ?? 0);
$pesable     = (int)($_POST['dato_pesable']   ?? 0);
$iva         = (int)($_POST['dato_iva']       ?? 0);

$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_productos
     (id_iva_condicion, id_rubro, nombre, descripcion, precio_costo, precio_venta, codigo, pesable)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'iissddsi',
    $iva, $rubro, $nombre, $descripcion, $costo, $venta, $codigo, $pesable
);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);