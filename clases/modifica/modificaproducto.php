<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$id          = (int)($_POST['dato_id']          ?? 0);
$iva         = (int)($_POST['dato_iva']         ?? 0);
$rubro       = (int)($_POST['dato_rubro']       ?? 0);
$nombre      = $_POST['dato_nombre']      ?? '';
$descripcion = $_POST['dato_descripcion'] ?? '';
$costo       = (float)($_POST['dato_costo']     ?? 0);
$venta       = (float)($_POST['dato_venta']     ?? 0);
$codigo      = $_POST['dato_codigo']      ?? '';
$pesable     = (int)($_POST['dato_pesable']     ?? 0);

$stmt = mysqli_prepare($conexion,
    "UPDATE tb_productos
     SET id_iva_condicion=?, id_rubro=?, nombre=?, descripcion=?,
         precio_costo=?, precio_venta=?, codigo=?, pesable=?
     WHERE id_productos=?"
);
mysqli_stmt_bind_param($stmt, 'iissddsis',
    $iva, $rubro, $nombre, $descripcion, $costo, $venta, $codigo, $pesable, $id
);
// Re-bind con tipos correctos:
mysqli_stmt_close($stmt);
$stmt = mysqli_prepare($conexion,
    "UPDATE tb_productos
     SET id_iva_condicion=?, id_rubro=?, nombre=?, descripcion=?,
         precio_costo=?, precio_venta=?, codigo=?, pesable=?
     WHERE id_productos=?"
);
mysqli_stmt_bind_param($stmt, 'iissddsii',
    $iva, $rubro, $nombre, $descripcion, $costo, $venta, $codigo, $pesable, $id
);
echo json_encode(['success' => mysqli_stmt_execute($stmt) ? 'true' : 'false', 'tipo' => 'ticket']);
mysqli_stmt_close($stmt);