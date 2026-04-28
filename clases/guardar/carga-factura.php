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

	$factura=$_REQUEST['dato_factura'];
	$fecha=$_REQUEST['dato_fecha'];
	$cliente=$_REQUEST['dato_cliente'];
	$producto=$_REQUEST['dato_producto'];
	$precio=$_REQUEST['dato_precio'];
	$cantidad=$_REQUEST['dato_cantidad'];
	$sucursal=$_REQUEST['dato_sucursal'];
	
	$subtotal = $precio * $cantidad;

	$sqlcontrol = "SELECT
		tb_ventas.id_ventas
		FROM
		tb_ventas
		WHERE
		tb_ventas.id_productos = '$producto' AND
		tb_ventas.id_sucursal = '$sucursal' AND
		tb_ventas.numero_factura = '$factura' AND
		tb_ventas.id_clientes = '$cliente' AND
		tb_ventas.estado = '0'";

	$rscontrol = mysqli_query($conexion, $sqlcontrol);	
	$filas = mysqli_num_rows($rscontrol);
	if ($filas > 0) {

			$array=array('success'=>'duplicado', 'factura'=>$factura, 'cliente'=>$cliente);
			echo json_encode($array);

	}else{
			
				$sql = "INSERT INTO tb_ventas (fecha, id_clientes, id_sucursal, numero_factura, id_productos, cantidad, precio_venta, subtotal)
				VALUES ('$fecha', '$cliente', '$sucursal', '$factura', '$producto', '$cantidad', '$precio', '$subtotal')";
			mysqli_query($conexion,$sql);    


			$array=array('success'=>'true', 'factura'=>$factura, 'cliente'=>$cliente);
			echo json_encode($array);
	}
		
} //fin else conexion
?>