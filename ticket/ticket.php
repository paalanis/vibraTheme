<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
include '../conexion/conexion.php';

$cliente = (int)($_REQUEST['dato_cliente'] ?? 0);
$factura = (int)($_REQUEST['dato_factura'] ?? 0);
$monto   = round((float)($_REQUEST['dato_monto']  ?? 0), 2);
$vuelto  = round((float)($_REQUEST['dato_vuelto'] ?? 0), 2);
$cierre  = (int)($_SESSION['cierre'] ?? 0);

// ── Trae los productos de la venta ────────────────────────────────────────────
$stmt = mysqli_prepare($conexion,
    "SELECT
        tv.id_ventas                              AS id,
        tp.nombre                                 AS producto,
        tp.codigo                                 AS codigo,
        tp.presentacion                           AS presentacion,
        tv.precio_venta                           AS precio,
        tv.cantidad                               AS cantidad,
        tv.subtotal                               AS subtotal,
        CONCAT(tc.apellido, ' ', tc.nombre)       AS nombrecliente
     FROM tb_ventas tv
     INNER JOIN tb_productos tp ON tp.id_productos = tv.id_productos
     INNER JOIN tb_clientes  tc ON tc.id_clientes  = tv.id_clientes
     WHERE tv.id_clientes = ? AND tv.numero_factura = ?
       AND tv.id_cierre = ? AND tv.estado = '1'"
);
mysqli_stmt_bind_param($stmt, 'iii', $cliente, $factura, $cierre);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,
    $r_id, $r_producto, $r_codigo, $r_presentacion,
    $r_precio, $r_cantidad, $r_subtotal, $r_nombrecliente
);

$rows     = [];
$total_fc = 0;
while (mysqli_stmt_fetch($stmt)) {
    $rows[] = [
        'producto'      => mb_convert_encoding($r_producto,      'UTF-8', 'ISO-8859-1'),
        'presentacion'  => mb_convert_encoding($r_presentacion,  'UTF-8', 'ISO-8859-1'),
        'nombrecliente' => mb_convert_encoding($r_nombrecliente, 'UTF-8', 'ISO-8859-1'),
        'precio'        => round($r_precio,   2),
        'cantidad'      => round($r_cantidad, 3),
        'subtotal'      => round($r_subtotal, 2),
    ];
    $total_fc = round($total_fc + $r_subtotal, 2);
}
mysqli_stmt_close($stmt);

// Variables con fallback por si no hay productos
$nombrecliente = !empty($rows) ? $rows[0]['nombrecliente'] : '';

// ── Impresora térmica ─────────────────────────────────────────────────────────
require __DIR__ . '/ticket/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

$nombre_impresora = $_SESSION['puesto'] ?? '192.168.1.105';

try {
    $connector = new NetworkPrintConnector($nombre_impresora, 9100);
    $printer   = new Printer($connector);

    $printer->setJustification(Printer::JUSTIFY_CENTER);

    try {
        $logo = EscposImage::load("../images/logo.png", false);
        $printer->bitImage($logo);
    } catch (Exception $e) { /* sin logo, continúa */ }

    $printer->text("Urquiza #1660\n");
    $printer->text("Coquimbito Maipú\n");

    date_default_timezone_set("America/Argentina/Mendoza");
    $printer->text(date("Y-m-d H:i:s") . "\n");
    $printer->text("TICKET N° $factura\n");

    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("\n------------------------------------------------\n");
    $printer->text("CANT  DESCRIPCION    P.U   IMP.\n");
    $printer->text("------------------------------------------------\n");

    foreach ($rows as $row) {
        $printer->text("{$row['producto']}\n");
        $printer->text("{$row['cantidad']}  {$row['presentacion']} \${$row['precio']} \${$row['subtotal']}\n");
    }

    $printer->text("------------------------------------------------\n");
    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->text("$nombrecliente\n");
    $printer->text("TOTAL: $ $total_fc\n");
    $printer->text("EFECTIVO: $ $monto\n");
    $printer->text("SU VUELTO: $ $vuelto\n");

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("\nMuchas gracias por su compra\n");
    $printer->text("Documento no válido como factura\n");

    $printer->feed(2);
    $printer->cut();
    $printer->pulse();
    $printer->close();

} catch (Exception $e) {
    error_log('ticket/ticket.php - Impresora no disponible: ' . $e->getMessage());
}

echo 1;
?>
