<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$id        = (int)($_POST['dato_id']          ?? 0);
$nombre    = $_POST['dato_nombre']    ?? '';
$cuit      = $_POST['dato_cuit']      ?? '';
$direccion = $_POST['dato_direccion'] ?? '';
$provincia = $_POST['dato_provincia'] ?? '';
$localidad = $_POST['dato_localidad'] ?? '';
$telefono  = $_POST['dato_telefono']  ?? '';
$mail      = $_POST['dato_mail']      ?? '';

$stmt = mysqli_prepare($conexion,
    "UPDATE tb_proveedores
     SET nombre=?, cuit=?, direccion=?, provincia=?, localidad=?, telefono=?, mail=?
     WHERE id_proveedores=?"
);
mysqli_stmt_bind_param($stmt, 'sssssss i',
    $nombre, $cuit, $direccion, $provincia, $localidad, $telefono, $mail, $id
);
// Bind correcto sin espacio:
mysqli_stmt_close($stmt);
$stmt = mysqli_prepare($conexion,
    "UPDATE tb_proveedores
     SET nombre=?, cuit=?, direccion=?, provincia=?, localidad=?, telefono=?, mail=?
     WHERE id_proveedores=?"
);
mysqli_stmt_bind_param($stmt, 'sssssssi',
    $nombre, $cuit, $direccion, $provincia, $localidad, $telefono, $mail, $id
);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);