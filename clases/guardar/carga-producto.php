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

	$fecha=mb_convert_encoding($_REQUEST['dato_fecha'], 'UTF-8', 'ISO-8859-1');
	$proveedor=$_REQUEST['dato_proveedor'];
	$sucursal=$_REQUEST['dato_sucursal'];
	$remito=$_REQUEST['dato_remito'];
	$obs=$_REQUEST['dato_obs'];
	$producto=$_REQUEST['dato_producto'];
	$cantidad=$_REQUEST['dato_cantidad'];

	$remito = $sucursal.'-'.$remito;

	$sqlcontrol = "SELECT
	tb_remitos.id_remitos
	FROM
	tb_remitos
	WHERE
	tb_remitos.id_proveedores = '$proveedor' AND
	tb_remitos.id_productos = '$producto' AND
	tb_remitos.numero = '$remito' AND
	tb_remitos.estado = '0'";

	$rscontrol = mysqli_query($conexion, $sqlcontrol);	
	$filas = mysqli_num_rows($rscontrol);
	if ($filas > 0) {

			$array=array('success'=>'duplicado', 'remito'=>$remito, 'proveedor'=>$proveedor);
			echo json_encode($array);

	}else{
			
			$sql = "INSERT INTO tb_remitos (fecha, id_proveedores, numero, obs, id_productos, cantidad)
			VALUES ('$fecha', '$proveedor', '$remito', '$obs', '$producto', '$cantidad')";
			mysqli_query($conexion,$sql);


			$array=array('success'=>'true', 'remito'=>$remito, 'proveedor'=>$proveedor);
			echo json_encode($array);
	}
		
} //fin else conexion
?>