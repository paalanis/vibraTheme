<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();
$id     = (int)($_POST['dato_id']    ?? 0);
$nombre = trim($_POST['dato_nombre'] ?? '');
$stmt = mysqli_prepare($conexion, "UPDATE tb_genero SET nombre=? WHERE id_genero=?");
mysqli_stmt_bind_param($stmt, 'si', $nombre, $id);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
