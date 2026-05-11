<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$id     = (int)($_POST['dato_id']     ?? 0);
$nombre = trim($_POST['dato_nombre']  ?? '');
$marca  = (int)($_POST['dato_marca']  ?? 0);
$genero = (int)($_POST['dato_genero'] ?? 0);
$tipo   = (int)($_POST['dato_tipo']   ?? 0);
$talle  = (int)($_POST['dato_talle']  ?? 0);
$color  = (int)($_POST['dato_color']  ?? 0);
$iva    = (int)($_POST['dato_iva']    ?? 0);
$codigo = trim($_POST['dato_codigo']  ?? '');
$foto   = trim($_POST['dato_foto']    ?? '');
$costo  = strlen(trim($_POST['dato_costo']  ?? '')) > 0 ? (float)$_POST['dato_costo']  : null;
$margen = strlen(trim($_POST['dato_margen'] ?? '')) > 0 ? (float)$_POST['dato_margen'] : null;

// Types: s(nombre) i(marca) i(genero) i(tipo) i(talle) i(color) i(iva)
//        s(codigo) s(foto) d(costo) d(margen) i(id)  → 'siiiiiissddi'
$stmt = mysqli_prepare($conexion,
    "UPDATE tb_productos
     SET nombre=?, id_marca=?, id_genero=?, id_tipo=?, id_talle=?, id_color=?,
         id_iva_condicion=?, codigo=?, foto=?, precio_costo=?, margen_ganancia=?
     WHERE id_productos=?"
);
mysqli_stmt_bind_param($stmt, 'siiiiiissddi',
    $nombre, $marca, $genero, $tipo, $talle, $color,
    $iva, $codigo, $foto, $costo, $margen, $id
);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false']);
mysqli_stmt_close($stmt);
