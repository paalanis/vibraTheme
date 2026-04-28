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
$id=$_REQUEST['id_producto'];

$sqlprecio = "SELECT
ifnull(tb_productos.precio_venta, '0') AS precio
FROM
tb_productos
WHERE
tb_productos.id_productos = '$id'";
$rsprecio = mysqli_query($conexion, $sqlprecio); 

$datos = mysqli_fetch_assoc($rsprecio)
$precio=$datos['precio'];

$array=array('success'=>'true', 'precio'=>$precio); 
echo json_encode($array);
   
?>