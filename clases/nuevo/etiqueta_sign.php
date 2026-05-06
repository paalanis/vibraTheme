<?php
// clases/nuevo/etiqueta_sign.php
// Firma los requests de QZ Tray con la clave privada RSA
session_start();
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit();
}

$privFile = __DIR__ . '/../../conexion/qz/vibra_private.pem';

if (!file_exists($privFile)) {
    http_response_code(500);
    echo 'ERROR: clave privada no encontrada';
    exit();
}

$data       = $_POST['data'] ?? '';
$privateKey = file_get_contents($privFile);

openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA512);
echo base64_encode($signature);
