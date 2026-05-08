<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}

include '../conexion/conexion.php';

$cierre_actual = (int)($_SESSION['cierre'] ?? 0);
$cajero        = $_SESSION['usuario'] ?? '';

if ($cierre_actual <= 0) {
    echo 0; exit();
}

// ── Resumen de ventas por condición ──────────────────────────────────────────
// LEFT JOIN: si id_condicion_venta no está seteado, igual aparecen las ventas.
$stmt1 = mysqli_prepare($conexion,
    "SELECT
        DATE_FORMAT(tc.fecha_apertura, '%d-%m-%Y %T') AS apertura,
        COALESCE(tcv.nombre, 'Sin condición') AS tipo,
        ROUND(SUM(tv.subtotal), 2) AS monto
     FROM tb_ventas tv
     LEFT JOIN tb_condicion_venta tcv ON tcv.id_condicion_venta = tv.id_condicion_venta
     INNER JOIN tb_cierres tc ON tv.id_cierre = tc.id_cierre
     WHERE tv.id_cierre = ? AND tv.estado = '1'
     GROUP BY tc.fecha_apertura, tv.id_cierre, tcv.nombre"
);
mysqli_stmt_bind_param($stmt1, 'i', $cierre_actual);
mysqli_stmt_execute($stmt1);
mysqli_stmt_bind_result($stmt1, $r_apertura, $r_tipo, $r_monto);
$rows_ventas = [];
while (mysqli_stmt_fetch($stmt1)) {
    $rows_ventas[] = ['tipo' => $r_tipo, 'monto' => $r_monto];
}
mysqli_stmt_close($stmt1);

// ── Retiros / efectivo inicial ────────────────────────────────────────────────
$stmt2 = mysqli_prepare($conexion,
    "SELECT
        IF(tipo = '0', 'Retiros de efectivo', 'Efectivo Inicial') AS tipo2,
        ROUND(SUM(IF(tipo = '0', monto * -1, monto)), 2) AS monto
     FROM tb_retiros
     WHERE id_cierres = ?
     GROUP BY tipo
     ORDER BY tipo DESC"
);
mysqli_stmt_bind_param($stmt2, 'i', $cierre_actual);
mysqli_stmt_execute($stmt2);
mysqli_stmt_bind_result($stmt2, $r_tipo2, $r_monto2);
$rows_retiros = [];
while (mysqli_stmt_fetch($stmt2)) {
    $rows_retiros[] = ['tipo2' => $r_tipo2, 'monto' => $r_monto2];
}
mysqli_stmt_close($stmt2);

// ── Mueve ventas al acumulado ─────────────────────────────────────────────────
$stmt3 = mysqli_prepare($conexion, "CALL mover_ventas(?)");
mysqli_stmt_bind_param($stmt3, 'i', $cierre_actual);
mysqli_stmt_execute($stmt3);
mysqli_stmt_close($stmt3);

// ── Totales ───────────────────────────────────────────────────────────────────
$retiro = array_sum(array_column($rows_retiros, 'monto'));
$total  = array_sum(array_column($rows_ventas,  'monto'));
$totalcaja = round($retiro + $total, 2);

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
    $printer->text("\n------------------------------------------------\n");

    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->setTextSize(2, 2);
    $printer->text("CIERRE DE CAJA N° $cierre_actual - $cajero\n");
    $printer->selectPrintMode(Printer::MODE_FONT_A);
    $printer->text("------------------------------------------------\n\n");

    foreach ($rows_retiros as $row) {
        $printer->text("{$row['tipo2']}: $ {$row['monto']}\n");
    }

    $printer->text("------------------------------------------------\n");

    foreach ($rows_ventas as $row) {
        $printer->text("Ventas {$row['tipo']}: $ {$row['monto']}\n");
    }

    $printer->text("------------------------------------------------\n");
    $printer->text("SUBTOTAL VENTAS: $ $total\n");
    $printer->text("TOTAL CIERRE CAJA: $ $totalcaja\n");

    $printer->feed(3);
    $printer->cut();
    $printer->pulse();
    $printer->close();

} catch (Exception $e) {
    error_log('ticket/cierracaja.php - Impresora no disponible: ' . $e->getMessage());
}

echo 1;
?>
