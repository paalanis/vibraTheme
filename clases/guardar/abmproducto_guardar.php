<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$nombre = trim($_POST['dato_nombre'] ?? '');
$marca  = (int)($_POST['dato_marca']  ?? 0);
$genero = (int)($_POST['dato_genero'] ?? 0);
$tipo   = (int)($_POST['dato_tipo']   ?? 0);
$talle  = (int)($_POST['dato_talle']  ?? 0);
$color  = (int)($_POST['dato_color']  ?? 0);
$iva    = (int)($_POST['dato_iva']    ?? 0);
$codigo = trim($_POST['dato_codigo']  ?? '');
$foto   = trim($_POST['dato_foto']    ?? '');
$costo  = strlen(trim($_POST['dato_costo']  ?? '')) > 0
            ? (float)$_POST['dato_costo']  : null;
$margen = strlen(trim($_POST['dato_margen'] ?? '')) > 0
            ? (float)$_POST['dato_margen'] : null;

$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_productos
     (nombre, id_marca, id_genero, id_tipo, id_talle, id_color, id_iva_condicion,
      codigo, foto, precio_costo, margen_ganancia)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'siiiiiiisdd',
    $nombre, $marca, $genero, $tipo, $talle, $color, $iva,
    $codigo, $foto, $costo, $margen
);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
