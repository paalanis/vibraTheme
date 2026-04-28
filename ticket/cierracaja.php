<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
include '../conexion/conexion.php';
// if (mysqli_connect_errno()) {
// 	$array=array('success'=>'false');
// 	echo json_encode($array);
// 	exit();
// }else{


$cierre_actual = $_REQUEST['cierre'];

$sqlcierre = "SELECT
DATE_FORMAT(tb_cierres.fecha_apertura, '%d-%m-%Y %T') as apertura,
tb_condicion_venta.nombre AS tipo,
ROUND(Sum(tb_ventas.subtotal),2) AS monto
FROM
tb_ventas
INNER JOIN tb_condicion_venta ON tb_condicion_venta.id_condicion_venta = tb_ventas.id_condicion_venta
INNER JOIN tb_cierres ON tb_ventas.id_cierre = tb_cierres.id_cierre
WHERE
tb_ventas.id_cierre = '$cierre_actual'
GROUP BY
tb_cierres.fecha_apertura,
tb_ventas.id_cierre,
tb_condicion_venta.nombre";
$rscierre = mysqli_query($conexion, $sqlcierre);
$filas = mysqli_num_rows($rscierre);

$sqlretiros = "SELECT
IF(tb_retiros.tipo = '0', 'Retiros de efectivo', 'Efectivo Inicial') AS tipo2,
round(SUM(IF(tb_retiros.tipo = '0', tb_retiros.monto*-1, tb_retiros.monto)),2) AS monto
FROM
tb_retiros
WHERE
tb_retiros.id_cierres = '$cierre_actual'
GROUP BY
tb_retiros.tipo
ORDER BY
tb_retiros.tipo DESC
";
$rsretiros = mysqli_query($conexion, $sqlretiros);
$filas2 = mysqli_num_rows($rsretiros);

$cajero=$_SESSION['usuario'];

$sql = "CALL mover_ventas(".$cierre_actual.")";
mysqli_query($conexion,$sql); 

        

require __DIR__ . '/ticket/autoload.php'; //Nota: si renombraste la carpeta a algo diferente de "ticket" cambia el nombre en esta línea
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

/*
	Este ejemplo imprime un
	ticket de venta desde una impresora térmica
*/


/*
    Aquí, en lugar de "POS" (que es el nombre de mi impresora)
	escribe el nombre de la tuya. Recuerda que debes compartirla
	desde el panel de control
*/

$nombre_impresora = $_SESSION['puesto'];


$connector = new NetworkPrintConnector($nombre_impresora, 9100);
$printer = new Printer($connector);
#Mando un numero de respuesta para saber que se conecto correctamente.
echo 1;
/*
	Vamos a imprimir un logotipo
	opcional. Recuerda que esto
	no funcionará en todas las
	impresoras

	Pequeña nota: Es recomendable que la imagen no sea
	transparente (aunque sea png hay que quitar el canal alfa)
	y que tenga una resolución baja. En mi caso
	la imagen que uso es de 250 x 250
*/

# Vamos a alinear al centro lo próximo que imprimamos
$printer->setJustification(Printer::JUSTIFY_CENTER);

/*
	Intentaremos cargar e imprimir
	el logo
*/
try{
	$logo = EscposImage::load("../images/logo.png", false);
    $printer->bitImage($logo);
}catch(Exception $e){/*No hacemos nada si hay error*/}

/*
	Ahora vamos a imprimir un encabezado
*/

//$printer->text("\n"."HOLA! Express" . "\n");
$printer->text("Urquiza #1660" . "\n");
$printer->text("Coquimbito Maipú" . "\n");
#La fecha también
date_default_timezone_set("America/Argentina/Mendoza");
$printer->text(date("Y-m-d H:i:s") . "\n");
$printer->text("\n"."------------------------------------------------"."\n");
$printer->setJustification(Printer::JUSTIFY_LEFT);
$printer -> setTextSize(2,2);
$printer->text("CIERRE DE CAJA N° $cierre_actual - $cajero\n");
$printer->selectPrintMode(Printer::MODE_FONT_A);
$printer->text("------------------------------------------------"."\n");
$printer->text("\n");

/*
	Ahora vamos a imprimir los
	productos
*/
	/*Alinear a la izquierda para la cantidad y el nombre*/
$printer->setJustification(Printer::JUSTIFY_LEFT);	

$retiro = 0;
if ($filas2 > 0) {

  while ($sql_retiros = mysqli_fetch_assoc($rsretiros)){
   $tipo2=$sql_retiros['tipo2'];
   $monto2=$sql_retiros['monto'];

   $printer->text("$tipo2: $ $monto2\n");

   $retiro = $retiro + $monto2;

  }
}else{echo ' ';}

$printer->text("------------------------------------------------"."\n");

$total = 0;
if ($filas > 0) {

  while ($sql_cierre = mysqli_fetch_assoc($rscierre)){
   //$apertura=$sql_cierre['apertura'];
   $tipo=$sql_cierre['tipo'];
   $monto=$sql_cierre['monto'];

   $printer->text("Ventas $tipo: $ $monto\n");

   $total = $total + $monto;

  }
}else{echo ' ';}

$totalcaja = $retiro + $total;
$totalcaja = round($totalcaja, 2);
	
    // $printer->text("Producto Galletas\n");
    // $printer->text( "2  pieza    10.00 20.00   \n");
    // $printer->text("Sabrtitas \n");
    // $printer->text( "3  pieza    10.00 30.00   \n");
    // $printer->text("Doritos \n");
    // $printer->text( "5  pieza    10.00 50.00   \n");
/*
	Terminamos de imprimir
	los productos, ahora va el total
*/
$printer->text("------------------------------------------------"."\n");
$printer->text("SUBTOTAL VENTAS: $ $total\n");
$printer->text("TOTAL CIERRE CAJA: $ $totalcaja\n");



/*
	Podemos poner también un pie de página
*/
// $printer->setJustification(Printer::JUSTIFY_CENTER);
// $printer->text("Muchas gracias por su compra\n");



/*Alimentamos el papel 3 veces*/
$printer->feed(3);

/*
	Cortamos el papel. Si nuestra impresora
	no tiene soporte para ello, no generará
	ningún error
*/
$printer->cut();

/*
	Por medio de la impresora mandamos un pulso.
	Esto es útil cuando la tenemos conectada
	por ejemplo a un cajón
*/
$printer->pulse();

/*
	Para imprimir realmente, tenemos que "cerrar"
	la conexión con la impresora. Recuerda incluir esto al final de todos los archivos
*/
$printer->close();

//}
?>