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

	$id=$_REQUEST['id'];
	$efectivo=$_REQUEST['efectivo'];
			
	$sql = "INSERT INTO tb_cierres (id_usuario, fecha_apertura)
	VALUES ('$id', CURTIME())";
	mysqli_query($conexion,$sql);    

	$sqlcierre = "SELECT
	tb_cierres.id_cierre AS id_cierre
	FROM
	tb_cierres
	WHERE
	tb_cierres.id_usuario = '$id' and tb_cierres.estado = '0'
	";
	$rscierre = mysqli_query($conexion, $sqlcierre);
	$sql_cierre = mysqli_fetch_assoc($rscierre);

	$cantidad =  mysqli_num_rows($rscierre);

	if ($cantidad > 0) {

		$id_cierre = $sql_cierre['id_cierre'];
		$_SESSION['cierre']=$id_cierre;

		$sql = "INSERT INTO tb_retiros (id_cierres, fecha, monto, tipo)
		VALUES ('$id_cierre', CURTIME(), '$efectivo', '1')";
		mysqli_query($conexion,$sql); 


		$array=array('success'=>'true', 'abrecaja'=>'true');
		echo json_encode($array);
		
	}else{

		$array=array('success'=>'false');
		echo json_encode($array);
	}

		
} //fin else conexion
?>