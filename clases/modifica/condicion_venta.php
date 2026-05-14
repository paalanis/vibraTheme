<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$id        = (int)($_POST['dato_id']       ?? 0);
$nombre    = trim($_POST['dato_nombre']    ?? '');
$descuento = trim($_POST['dato_descuento'] ?? '');
$cupon     = trim($_POST['dato_cupon']     ?? '0');
$dias      = trim($_POST['dato_dias']      ?? '');

// Validaciones
if ($id <= 0 || $nombre === '') {
    echo json_encode(['success' => 'false']); exit();
}

// Normalizar descuento
$descuento_val = ($descuento !== '') ? (float)$descuento : 0.0;

// Normalizar cupón
$cupon_val = ($cupon === '1') ? '1' : '0';

// Normalizar días
$dias_val = '';
if ($dias !== '') {
    $nums = array_filter(array_map('intval', explode(',', $dias)), function($n) {
        return $n >= 1 && $n <= 7;
    });
    sort($nums);
    $dias_val = implode(',', $nums);
}
$dias_bind = $dias_val !== '' ? $dias_val : null;

$stmt = mysqli_prepare($conexion,
    "UPDATE tb_condicion_venta
     SET nombre=?, descuento=?, cupon=?, dias=?
     WHERE id_condicion_venta=?"
);
mysqli_stmt_bind_param($stmt, 'sdssi', $nombre, $descuento_val, $cupon_val, $dias_bind, $id);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
