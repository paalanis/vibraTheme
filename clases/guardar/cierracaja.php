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

	$cierre=$_REQUEST['cierre'];

	//Elimina pruductos pendientes
	$sql = "DELETE FROM tb_ventas WHERE tb_ventas.estado = '0' AND tb_ventas.id_cierre = '$cierre'";
	mysqli_query($conexion,$sql);
			
	$sql = "UPDATE tb_cierres SET tb_cierres.estado = '1', tb_cierres.fecha_cierre = CURTIME() WHERE tb_cierres.id_cierre = '$cierre'";
	mysqli_query($conexion,$sql); 
	
	unset($_SESSION['cierre']);
	$array=array('success'=>'true', 'abrecaja'=>'true');
	echo json_encode($array);
	

		
} //fin else conexion
?>