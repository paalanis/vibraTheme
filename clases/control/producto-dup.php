<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';

$nombre     = trim($_REQUEST['nombre']     ?? '');
$marca      = (int)($_REQUEST['marca']      ?? 0);
$genero     = (int)($_REQUEST['genero']     ?? 0);
$tipo       = (int)($_REQUEST['tipo']       ?? 0);
$talle      = (int)($_REQUEST['talle']      ?? 0);
$color      = (int)($_REQUEST['color']      ?? 0);
$id_excluir = (int)($_REQUEST['id_excluir'] ?? 0);

// Requiere nombre y los 5 atributos
if (!$nombre || !$marca || !$genero || !$tipo || !$talle || !$color) {
    echo json_encode(['existe' => false]);
    exit();
}

$stmt = mysqli_prepare($conexion,
    "SELECT p.id_productos, p.nombre
     FROM tb_productos p
     WHERE LOWER(p.nombre) = LOWER(?)
       AND p.id_marca   = ?
       AND p.id_genero  = ?
       AND p.id_tipo    = ?
       AND p.id_talle   = ?
       AND p.id_color   = ?
       AND p.id_productos != ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'siiiiiii', $nombre, $marca, $genero, $tipo, $talle, $color, $id_excluir);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_nombre);
$found = (bool)mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode([
    'existe'  => $found,
    'id'      => $found ? $r_id   : null,
    'nombre'  => $found ? mb_convert_encoding($r_nombre, 'UTF-8', 'ISO-8859-1') : null,
]);
