<?php
session_start();
if (!isset($_SESSION['usuario']) || strtolower($_SESSION['tipo_user']) !== 'admin') {
    echo json_encode(['success' => 'false']); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$id_sucursal = (int)($_POST['id_sucursal'] ?? 0);
$clave       = $_POST['clave']             ?? '';
$valor       = $_POST['valor']             ?? '0';

// Claves permitidas — whitelist para evitar escritura arbitraria
$claves_validas = ['permite_venta_sin_stock', 'stock_minimo'];

if ($id_sucursal <= 0 || !in_array($clave, $claves_validas, true)) {
    echo json_encode(['success' => 'false', 'error' => 'Parámetro inválido']); exit();
}

// UPSERT: inserta o actualiza si ya existe
$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_configuracion (id_sucursal, clave, valor)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
);
mysqli_stmt_bind_param($stmt, 'iss', $id_sucursal, $clave, $valor);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
