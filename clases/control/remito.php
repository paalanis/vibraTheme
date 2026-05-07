<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']);
    exit();
}

$remito    = $_REQUEST['remito']    ?? '';
$proveedor = (int)($_REQUEST['proveedor'] ?? 0);

// Devuelve false si el remito YA EXISTE (duplicado), true si está disponible.
$stmt = mysqli_prepare($conexion,
    "SELECT id_remitos FROM tb_remitos WHERE numero = ? AND id_proveedores = ?"
);
mysqli_stmt_bind_param($stmt, 'si', $remito, $proveedor);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $id_dup);
$existe = (bool)mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => $existe ? 'false' : 'true']);
