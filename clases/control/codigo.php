<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';

$codigo     = trim($_REQUEST['codigo']     ?? '');
$id_excluir = (int)($_REQUEST['id_excluir'] ?? 0); // para modificación: excluir el propio producto

if ($codigo === '') {
    echo json_encode(['existe' => false]);
    exit();
}

$stmt = mysqli_prepare($conexion,
    "SELECT id_productos, nombre FROM tb_productos
     WHERE codigo = ? AND id_productos != ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'si', $codigo, $id_excluir);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_nombre);
$found = (bool)mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode([
    'existe'  => $found,
    'nombre'  => $found ? mb_convert_encoding($r_nombre, 'UTF-8', 'ISO-8859-1') : null,
    'id'      => $found ? $r_id : null,
]);
