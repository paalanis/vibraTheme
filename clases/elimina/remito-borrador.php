<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$remito    = $_POST['remito']             ?? '';
$proveedor = (int)($_POST['proveedor']    ?? 0);

// Normalizar sucursal: el JS envía "0001-00000001" pero la BD tiene "1-00000001"
$parts  = explode('-', $remito, 2);
$remito = (count($parts) === 2) ? ((int)$parts[0]) . '-' . $parts[1] : $remito;

if ($remito === '' || $proveedor <= 0) {
    echo json_encode(['success' => 'false']); exit();
}

// Solo borra líneas en estado='0' (borradores) — nunca toca remitos confirmados
$stmt = mysqli_prepare($conexion,
    "DELETE FROM tb_remitos WHERE numero = ? AND id_proveedores = ? AND estado = '0'"
);
mysqli_stmt_bind_param($stmt, 'si', $remito, $proveedor);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
