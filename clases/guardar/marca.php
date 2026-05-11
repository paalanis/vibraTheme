<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();
$nombre = trim($_POST['dato_nombre'] ?? '');
$stmt = mysqli_prepare($conexion, "INSERT INTO tb_marca (nombre) VALUES (?)");
mysqli_stmt_bind_param($stmt, 's', $nombre);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
