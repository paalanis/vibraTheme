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


		
	    $cliente=$_REQUEST['dato_cliente'];
	    $factura=$_REQUEST['dato_factura'];
        $monto=round($_REQUEST['dato_monto'],2);
        $vuelto=round($_REQUEST['dato_vuelto'],2);
        $cierre=$_SESSION['cierre']; 

        $sqlingreso = "SELECT
            tb_ventas.id_ventas AS id,
            tb_productos.nombre AS producto,
            tb_productos.codigo AS codigo,
            tb_productos.presentacion AS presentacion,
            tb_ventas.precio_venta AS precio,
            tb_ventas.cantidad AS cantidad,
            tb_ventas.subtotal AS subtotal,
            CONCAT(tb_clientes.apellido, tb_clientes.nombre) as nombrecliente
            FROM
            tb_ventas
            INNER JOIN tb_productos ON tb_productos.id_productos = tb_ventas.id_productos
            INNER JOIN tb_clientes ON tb_ventas.id_clientes = tb_clientes.id_clientes
            WHERE
            tb_ventas.id_clientes = '$cliente' AND
            tb_ventas.numero_factura = '$factura' AND
            tb_ventas.id_cierre = '$cierre' AND
            tb_ventas.estado = '1'";
        $rsingreso = mysqli_query($conexion, $sqlingreso);
        
        $cantidad =  mysqli_num_rows($rsingreso);

        

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

$nombre_impresora = $_SESSION['puesto'];; 


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
$printer->text("TICKET N° $factura" . "\n");
$printer->setJustification(Printer::JUSTIFY_LEFT);
$printer->text("\n"."------------------------------------------------"."\n");
$printer->text("CANT  DESCRIPCION    P.U   IMP.\n");
$printer->text("------------------------------------------------"."\n");
/*
	Ahora vamos a imprimir los
	productos
*/
	/*Alinear a la izquierda para la cantidad y el nombre*/
$printer->setJustification(Printer::JUSTIFY_LEFT);	

if ($cantidad > 0) { // si existen ingreso con de esa ingreso se muestran, de lo contrario queda en blanco  
        
        $total_fc = 0;  
        
        while ($datos = mysqli_fetch_assoc($rsingreso)){
        $id=$datos['id'];
        $nombrecliente=mb_convert_encoding($datos['nombrecliente'], 'UTF-8', 'ISO-8859-1');
        $producto=mb_convert_encoding($datos['producto'], 'UTF-8', 'ISO-8859-1');
        $codigo=mb_convert_encoding($datos['codigo'], 'UTF-8', 'ISO-8859-1');
        $precio=round($datos['precio'],2);
        $presentacion=mb_convert_encoding($datos['presentacion'], 'UTF-8', 'ISO-8859-1');
        $cantidad=round($datos['cantidad'],3);
        $subtotal=round($datos['subtotal'],2);
        
        $total_fc = round(($total_fc + $subtotal),2);

        $printer->text("$producto\n");
        $printer->text("$cantidad  $presentacion $$precio $$subtotal\n");

    
     }
 }

	
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
$printer->setJustification(Printer::JUSTIFY_RIGHT);
$printer->text("$nombrecliente\n");
$printer->text("TOTAL: $ $total_fc\n");
$printer->text("EFECTIVO: $ $monto\n");
$printer->text("SU VUELTO: $ $vuelto\n");


/*
	Podemos poner también un pie de página
*/
$printer->setJustification(Printer::JUSTIFY_CENTER);
$printer->text("\n"."Muchas gracias por su compra\n");
$printer->text("Documento no válido como factura\n");


/*Alimentamos el papel 3 veces*/
$printer->feed(2);

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