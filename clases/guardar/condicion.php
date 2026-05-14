<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$nombre    = trim($_POST['dato_nombre']    ?? '');
$descuento = trim($_POST['dato_descuento'] ?? '');
$cupon     = trim($_POST['dato_cupon']     ?? '0');
$dias      = trim($_POST['dato_dias']      ?? '');

if ($nombre === '') {
    echo json_encode(['success' => 'false']); exit();
}

$descuento_val = ($descuento !== '') ? (float)$descuento : 0.0;
$cupon_val     = ($cupon === '1') ? '1' : '0';

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
    "INSERT INTO tb_condicion_venta (nombre, descuento, cupon, dias)
     VALUES (?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'sdss', $nombre, $descuento_val, $cupon_val, $dias_bind);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
