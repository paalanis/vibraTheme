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


		
	    $id = (int)($_REQUEST['dato_id'] ?? 0);
if ($id <= 0) { echo 0; exit(); }


        $producto = '';
$codigo   = '';
$precio   = '0.00';

$sqlarticulo = "SELECT 
        tb_productos.nombre as producto,
        tb_productos.codigo as codigo,
        tb_productos.precio_venta as precio
        FROM tb_productos
        WHERE tb_productos.id_productos = '$id'";
        $rsarticulo = mysqli_query($conexion, $sqlarticulo);
 
  		$cantidad =  mysqli_num_rows($rsarticulo);

  		if ($cantidad > 0) { // si existen ingreso con de esa ingreso se muestran, de lo contrario queda en blanco  
        
        while ($datos = mysqli_fetch_assoc($rsarticulo)){
        $producto=mb_convert_encoding($datos['producto'], 'UTF-8', 'ISO-8859-1');
        $codigo=$datos['codigo'];	
        $precio=number_format($datos['precio'],2);
    
		     }
		}

		//$codigo = (int)$codigo;

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

$nombre_impresora = $_SESSION['puesto'] ?? '192.168.1.105'; 


try {
$connector = new NetworkPrintConnector($nombre_impresora, 9100);
$printer = new Printer($connector);
#Mando un numero de respuesta para saber que se conecto correctamente.
//echo 1;
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
$printer->setJustification(Printer::JUSTIFY_LEFT);

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


$printer->setJustification(Printer::JUSTIFY_RIGHT);	
//$printer->text("\n");
$printer->text("\n"."$producto" . "\n");
$printer->setJustification(Printer::JUSTIFY_RIGHT);
//$printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
$printer -> setTextSize(3,3);
$printer->text("$ $precio" . "\n");
$printer->selectPrintMode(Printer::MODE_FONT_A);
$printer->setJustification(Printer::JUSTIFY_LEFT);

//$printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
//$printer->text("Text position\n");
$printer->selectPrintMode();
$printer->setBarcodeHeight(32);
$printer->setBarcodeWidth(2);
$hri = array (
    Printer::BARCODE_TEXT_BELOW => "Below"
);
foreach ($hri as $position => $caption) {
    $printer->setBarcodeTextPosition($position);
    $printer->barcode($codigo, Printer::BARCODE_CODE39);
    //$printer->feed();
}


/*Alimentamos el papel 3 veces*/
//$printer->feed(2);

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
//$printer->pulse();

/*
	Para imprimir realmente, tenemos que "cerrar"
	la conexión con la impresora. Recuerda incluir esto al final de todos los archivos
*/
$printer->close();

} catch (Exception $e) {
    error_log(\'frente_gondola.php - Impresora no disponible: ' . $e->getMessage());
}
echo 1;
?>
