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

	
	$retiro=$_REQUEST['retiro'];
	$obs=$_REQUEST['obs'];
	$id_cierre = $_SESSION['cierre'];

		$sql = "INSERT INTO tb_retiros (id_cierres, fecha, monto, tipo, descripcion)
		VALUES ('$id_cierre', CURTIME(), '$retiro', '0', '$obs')";
		mysqli_query($conexion,$sql); 

		$array=array('success'=>'true', 'abrecaja'=>'true');
		echo json_encode($array);
		
		
} //fin else conexion
?>