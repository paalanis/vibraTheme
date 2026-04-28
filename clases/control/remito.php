<?php 
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../../index.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
printf("La conexión con el servidor de base de datos falló comuniquese con su administrador: %s\n", mysqli_connect_error());
exit();
}
$remito=$_REQUEST['remito'];
$proveedor=$_REQUEST['proveedor'];
$sqlremito = "SELECT
tb_remitos.numero
FROM
tb_remitos
WHERE
tb_remitos.numero = '$remito' and tb_remitos.id_proveedores = '$proveedor'";
$rsremito = mysqli_query($conexion, $sqlremito); 
$filas = mysqli_num_rows($rsremito);
if ($filas > 0) {
$array=array('success'=>'false'); 
echo json_encode($array);
}else{
$array=array('success'=>'true');
echo json_encode($array);
}
?>