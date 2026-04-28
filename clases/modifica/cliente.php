<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$id           = (int)($_POST['dato_id']          ?? 0);
$nombre       = $_POST['dato_nombre']       ?? '';
$apellido     = $_POST['dato_apellido']     ?? '';
$dni          = $_POST['dato_dni']          ?? '';
$telefono     = $_POST['dato_telefono']     ?? '';
$mail         = $_POST['dato_mail']         ?? '';
$calle        = $_POST['dato_calle']        ?? '';
$numero       = $_POST['dato_numero']       ?? '';
$localidad    = $_POST['dato_localidad']    ?? '';
$provincia    = $_POST['dato_provincia']    ?? '';
$codigopostal = $_POST['dato_codigopostal'] ?? '';

$stmt = mysqli_prepare($conexion,
    "UPDATE tb_clientes
     SET nombre=?, apellido=?, dni=?, telefono=?, mail=?,
         calle=?, numero=?, localidad=?, provincia=?, codigo_postal=?
     WHERE id_clientes=?"
);
mysqli_stmt_bind_param($stmt, 'ssssssssssi',
    $nombre, $apellido, $dni, $telefono, $mail,
    $calle, $numero, $localidad, $provincia, $codigopostal, $id
);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);