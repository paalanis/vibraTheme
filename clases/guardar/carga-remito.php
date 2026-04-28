<?php 
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
	$array=array('success'=>'false');
	echo json_encode($array);
	exit();
}else{

	
	$proveedor=$_REQUEST['dato_proveedor'];
	$sucursal=$_REQUEST['dato_sucursal'];
	$remito=$_REQUEST['dato_remito'];

	$remito = $sucursal.'-'.$remito;
			
	$sql = "INSERT IGNORE INTO tb_existencias(
      id_productos, 
      cantidad)
	SELECT 
	      tb_remitos.id_productos as id_producto,
	      tb_remitos.cantidad as cantidad
	FROM tb_remitos
	WHERE
	tb_remitos.estado = '0' AND tb_remitos.numero = '$remito' AND tb_remitos.id_proveedores = '$proveedor'
	ON DUPLICATE KEY UPDATE tb_existencias.cantidad = tb_existencias.cantidad+tb_remitos.cantidad";
	mysqli_query($conexion,$sql);    

	$sql = "UPDATE tb_remitos SET tb_remitos.estado = '1' WHERE tb_remitos.estado = '0' AND tb_remitos.numero = '$remito' AND tb_remitos.id_proveedores = '$proveedor'";
	mysqli_query($conexion,$sql);    

	$array=array('success'=>'true');
	echo json_encode($array);
		
} //fin else conexion
?>