<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

include '../conexion/conexion.php';

require __DIR__ . '/ticket/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

$nombre_impresora = $_SESSION['puesto'] ?? '192.168.1.105';

try {
    $connector = new NetworkPrintConnector($nombre_impresora, 9100);
    $printer   = new Printer($connector);

    // Pulso al cajón de dinero
    $printer->pulse();
    $printer->close();

    echo 1; // éxito con impresora
} catch (Exception $e) {
    // Impresora no disponible — la caja se abre igual
    error_log('abrecaja.php - Impresora no disponible: ' . $e->getMessage());
    echo 1; // éxito sin impresora (la apertura de caja la maneja index2.php)
}
?>
