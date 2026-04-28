<?php 
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../../index.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
$array=array('success'=>'false');
echo json_encode($array);
exit();
}else{

$codigo_cargado=$_REQUEST['dato_codigo'];

if($codigo_cargado != ''){
 
	 if (substr($codigo_cargado,0,2) == '99'){
	 	$codigo=substr($codigo_cargado,0,7);
	 	$cualprecio = 'sql';
	 	$cantidad=substr($codigo_cargado,7,5)/1000;
	 }else{
	 	$codigo=$_REQUEST['dato_codigo'];
	 	$cualprecio = 'sql';
	 	$cantidad=$_REQUEST['dato_cantidad'];
	 }

}else{
	 $codigo=$_REQUEST['dato_producto'];
	 $cualprecio = 'manual';
	 $cantidad=$_REQUEST['dato_cantidad'];
}

//buscamos el precio por el codigo

	$sqlproducto = "SELECT
	tb_productos.precio_venta as precio,
	tb_productos.id_productos as id,
	tb_productos.pesable as pesable
	FROM
	tb_productos
	WHERE
	tb_productos.codigo = '$codigo'";
	$rsproducto = mysqli_query($conexion, $sqlproducto);
	$sql_producto = mysqli_fetch_assoc($rsproducto);

	$filas = mysqli_num_rows($rsproducto);

if ($filas > 0) {

	$cierre = $_SESSION['cierre'];
	
	$producto=$sql_producto['id'];
	$cliente=$_REQUEST['dato_cliente'];
	$sucursal=$_REQUEST['dato_sucursal'];
	$factura=$_REQUEST['dato_factura'];
	$fecha=$_REQUEST['dato_fecha'];
	
	if($cualprecio == 'sql'){
	
	 $precio=$sql_producto['precio'];

	}else{
	
	 $precio=$_REQUEST['dato_precio'];
	
	}

	$pesable=$sql_producto['pesable'];
	
	if ($pesable == '1') {
		
	 $subtotal = round($precio*$cantidad,2);
	}else{

	$cantidad=$cantidad*1000;
	$subtotal = round($precio*$cantidad,2);
	}
	
	//echo $cantidad;
	
	//echo $subtotal;

		$sql = "INSERT INTO tb_ventas (fecha, id_clientes, id_sucursal, numero_factura, id_productos, cantidad, precio_venta, subtotal, id_cierre)
		VALUES ('$fecha', '$cliente', '$sucursal', '$factura', '$producto', '$cantidad', '$precio', '$subtotal', '$cierre')";
	mysqli_query($conexion,$sql);    


	$array=array('success'=>'true', 'factura'=>$factura, 'cliente'=>$cliente, 'cierre'=>$cierre);
	echo json_encode($array);

}else {

    $cierre = $_SESSION['cierre'];
    $factura=$_REQUEST['dato_factura'];
    $cliente=$_REQUEST['dato_cliente'];

	$array=array('success'=>'no_existe', 'factura'=>$factura, 'cliente'=>$cliente, 'cierre'=>$cierre);
	echo json_encode($array);

}
} //fin else conexion
?>