<?php
	include 'plantilla.php';
	include '../../conexion/conexion.php';
	if (mysqli_connect_errno()) {
	printf("La conexión con el servidor de base de datos falló comuniquese con su administrador: %s\n", mysqli_connect_error());
	exit();
	}
	
	date_default_timezone_set("America/Argentina/Mendoza");
	$hoy = date("d-m-Y");

	//$dato=$_REQUEST['dato'];	
	$id_cliente = $_REQUEST['id_cliente'];
	$cliente = $_REQUEST['cliente'];
	$desde = $_REQUEST['dato_desde'];
	$hasta = $_REQUEST['dato_hasta'];
	$total = $_REQUEST['total'];

//viajes ---------------------------------------------------------------------------------------
	 $sqlproductos = "SELECT
		DATE_FORMAT(tb_ventas_acumulado.fecha, '%d-%m-%Y')AS fecha,
		tb_usuarios.nombre AS cajero,
		tb_ventas_acumulado.numero_factura AS factura,
		tb_productos.nombre AS producto,
		tb_ventas_acumulado.precio_venta AS `precio`,
		tb_ventas_acumulado.cantidad AS cantidadd,
		tb_ventas_acumulado.subtotal AS total
		FROM
		tb_ventas_acumulado
		INNER JOIN tb_productos ON tb_productos.id_productos = tb_ventas_acumulado.id_productos
		INNER JOIN tb_cierres ON tb_ventas_acumulado.id_cierre = tb_cierres.id_cierre
		INNER JOIN tb_usuarios ON tb_cierres.id_usuario = tb_usuarios.id_usuario
		INNER JOIN tb_clientes ON tb_ventas_acumulado.id_clientes = tb_clientes.id_clientes
		WHERE
		tb_clientes.id_clientes = '$id_cliente' and tb_ventas_acumulado.fecha BETWEEN '$desde 00:00:00' AND '$hasta 23:59:59'
		ORDER BY
		fecha ASC
		";

	$rsproductos = mysqli_query($conexion, $sqlproductos);
	$cantidad =  mysqli_num_rows($rsproductos);

//viajes ---------------------------------------------------------------------------------------


	$pdf = new PDF();
	$title = utf8_decode('Detalle de Cuentas Corrientes');
	$pdf->SetTitle($title);
	$pdf->AliasNbPages();
	$pdf->AddPage();	

    // Arial 12
    $pdf->SetFont('Arial','',12);	
    // Color de fondo
    $pdf->SetFillColor(231,235,218);
    // Título
    $pdf->Cell(0,10,utf8_decode("FECHA : ".$hoy.""),0,1,'L',true);
    // Salto de línea
    $pdf->Ln(4);
    $pdf->Cell(0,10,utf8_decode("CLIENTE : ".$cliente.""),0,1,'L',true);
    $pdf->Ln(4);
    $pdf->Cell(0,10,utf8_decode("PERIODO LIQUIDACIÓN : ".$desde." - ".$hasta.""),0,1,'L',true);
    $pdf->Ln(4);
    $pdf->Cell(0,10,utf8_decode("Total a pagar : $".$total.""),0,1,'L',true);
    $pdf->Ln(10);

 //    $pdf->PrintChapter(''.$liquidacion.'','ANTICIPOS','');

 //    $pdf->SetFillColor(232,232,232);
	// $pdf->SetFont('Arial','',12);
	// $pdf->Cell(47,6,'Fecha',1,0,'C',1);
	// $pdf->Cell(47,6,'Tipo de anticipo',1,0,'C',1);
	// $pdf->Cell(47,6,'Monto',1,0,'C',1);
	// $pdf->Cell(47,6,utf8_decode('Observación'),1,0,'C',1);
	// $pdf->Ln(10);

    
	// //if ($cantidad_anti > 0) { 

 //    while ($datos = mysqli_fetch_assoc($rsanticipos)){
	// $fecha=utf8_decode($datos['fecha']);
	// $tipo=utf8_decode($datos['tipo']);
	// $monto=$datos['monto'];
	// $obs=utf8_decode($datos['obs']);
	// $pdf->SetFont('Arial','',12);
	// $pdf->Cell(70,6, $fecha.' - '.$tipo.' - $ '.$monto.' - '.$obs,0,0,'L');
	// $pdf->Ln(4);

	// 	}	
	//}else { echo 'Sin Satos';}

	$pdf->PrintChapter('Detalle de articulos','','');
	
	$pdf->SetFillColor(232,232,232);
	$pdf->SetFont('Arial','',12);
	$pdf->Cell(23,6,'Fecha',1,0,'C',1);
	$pdf->Cell(25,6,'Cajero',1,0,'C',1);
	$pdf->Cell(16,6,'Factura',1,0,'C',1);
	$pdf->Cell(65,6,'Producto',1,0,'C',1);
	$pdf->Cell(20,6,'PrecioU',1,0,'C',1);
	$pdf->Cell(20,6,'Cantidad',1,0,'C',1);
	$pdf->Cell(20,6,'Total',1,0,'C',1);
	$pdf->Ln(10);

	if ($cantidad > 0) { 
		
	while ($datos = mysqli_fetch_assoc($rsproductos)){
	$fecha=utf8_decode($datos['fecha']);
	$cajero=utf8_decode($datos['cajero']);
	$factura=utf8_decode($datos['factura']);
	$producto=utf8_decode($datos['producto']);
	$precio=utf8_decode($datos['precio']);
	$cantidadd=utf8_decode($datos['cantidadd']);
	$total=utf8_decode($datos['total']);
	
	
	$pdf->SetFont('Arial','',12);
	$pdf->MultiCell(0,6, $fecha.' - '.$cajero.' - '.$factura.' - '.$producto.' - $'.$precio.' - '.$cantidadd.' - $'.$total,0,'J',false);
	$pdf->Ln(4);
		}
	}else { echo 'Sin Satos';}

	$pdf->Output('D','Liquidacion_'.$cliente.'_'.$hoy.'.pdf');
?>