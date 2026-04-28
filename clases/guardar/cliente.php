<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$nombre        = $_POST['dato_nombre']       ?? '';
$apellido      = $_POST['dato_apellido']     ?? '';
$dni           = $_POST['dato_dni']          ?? '';
$mail          = $_POST['dato_mail']         ?? '';
$telefono      = $_POST['dato_telefono']     ?? '';
$calle         = $_POST['dato_calle']        ?? '';
$numero        = $_POST['dato_numero']       ?? '';
$localidad     = $_POST['dato_localidad']    ?? '';
$provincia     = $_POST['dato_provincia']    ?? '';
$codigo_postal = $_POST['dato_codigopostal'] ?? '';

$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_clientes
     (nombre, apellido, dni, mail, telefono, calle, numero, localidad, provincia, codigo_postal)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'ssssssssss',
    $nombre, $apellido, $dni, $mail, $telefono,
    $calle, $numero, $localidad, $provincia, $codigo_postal
);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);