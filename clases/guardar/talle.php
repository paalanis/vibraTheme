<?php 
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
include '../../conexion/conexion.php';

$dato = $_SERVER['SERVER_ADDR'];

echo $dato;

if (mysqli_connect_errno()) {
	$array=array('success'=>'false');
	echo json_encode($array);
	exit();
}else{

	$nombre=mb_convert_encoding($_REQUEST['dato_nombre'], 'UTF-8', 'ISO-8859-1');
			
	$sql = "INSERT INTO tb_talle (nombre)
	VALUES ('$nombre')";
	mysqli_query($conexion,$sql);    


	$array=array('success'=>'true');
	echo json_encode($array);
		
} //fin else conexion
?>