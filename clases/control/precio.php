<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

$id = (int)($_REQUEST['id_producto'] ?? 0);

$stmt = mysqli_prepare($conexion,
    "SELECT ROUND(precio_costo * (1 + margen_ganancia / 100), 2) AS precio
     FROM tb_productos
     WHERE id_productos = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $precio);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => 'true', 'precio' => $precio ?? 0]);
