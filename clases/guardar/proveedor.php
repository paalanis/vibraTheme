<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$nombre    = $_POST['dato_nombre']    ?? '';
$cuit      = $_POST['dato_cuit']      ?? '';
$direccion = $_POST['dato_direccion'] ?? '';
$provincia = $_POST['dato_provincia'] ?? '';
$localidad = $_POST['dato_localidad'] ?? '';
$telefono  = $_POST['dato_telefono']  ?? '';
$mail      = $_POST['dato_mail']      ?? '';

$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_proveedores (nombre, cuit, direccion, provincia, localidad, telefono, mail)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'sssssss',
    $nombre, $cuit, $direccion, $provincia, $localidad, $telefono, $mail
);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);