<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success' => 'false']); exit(); }

$stmt = mysqli_prepare($conexion, "DELETE FROM tb_remitos WHERE id_remitos = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);