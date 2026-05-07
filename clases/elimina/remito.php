<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

// $_REQUEST acepta tanto GET como POST
$id = (int)($_REQUEST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success' => 'false']); exit(); }

// AND estado = '0' evita borrar remitos ya confirmados
$stmt = mysqli_prepare($conexion,
    "DELETE FROM tb_remitos WHERE id_remitos = ? AND estado = '0'"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
