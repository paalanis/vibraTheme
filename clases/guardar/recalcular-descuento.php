<?php
/**
 * clases/guardar/recalcular-descuento.php
 * AJAX: recalcula descuentos del carrito activo al seleccionar condición de pago.
 * Devuelve JSON con nuevo subtotal y ahorro total para actualizar la UI.
 */
ob_start();
session_start();
if (!isset($_SESSION['usuario'])) {
    ob_clean(); echo json_encode(['success' => false, 'error' => 'Sin sesión']); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
require_once '../../conexion/descuentos.php';
csrf_validate();

header('Content-Type: application/json');

$factura    = (int)($_POST['dato_factura']  ?? 0);
$cierre     = (int)($_SESSION['cierre']     ?? 0);
$sucursal   = (int)($_POST['dato_sucursal'] ?? 1);
$id_condicion = (int)($_POST['id_condicion'] ?? 0);

if ($factura <= 0 || $id_condicion <= 0) {
    ob_clean();
    echo json_encode(['success' => true, 'subtotal' => 0, 'ahorro' => 0]);
    exit();
}

// Recalcular en transacción (puede afectar múltiples filas)
mysqli_begin_transaction($conexion);
try {
    $ok = descuento_recalcular_carrito($conexion, $factura, $cierre, $sucursal, $id_condicion);
    if (!$ok) throw new Exception('Error al recalcular descuentos');

    // Leer subtotal y ahorro actualizados
    $stmt = mysqli_prepare($conexion,
        "SELECT ROUND(SUM(subtotal), 2)        AS nuevo_subtotal,
                ROUND(SUM(descuento_monto), 2) AS nuevo_ahorro
         FROM tb_ventas
         WHERE numero_factura = ? AND id_cierre = ? AND estado = '0' AND id_sucursal = ?"
    );
    mysqli_stmt_bind_param($stmt, 'iii', $factura, $cierre, $sucursal);
    mysqli_stmt_execute($stmt);
    $nuevo_subtotal = $nuevo_ahorro = null;
    mysqli_stmt_bind_result($stmt, $nuevo_subtotal, $nuevo_ahorro);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conexion);
    ob_clean();
    echo json_encode([
        'success'  => true,
        'subtotal' => (float)($nuevo_subtotal ?? 0),
        'ahorro'   => (float)($nuevo_ahorro   ?? 0),
    ]);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
