<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$cliente   = (int)($_POST['dato_cliente']   ?? 0);
$sucursal  = (int)($_POST['dato_sucursal']  ?? 0);
$factura   = (int)($_POST['dato_factura']   ?? 0);
$condicion = (int)($_POST['dato_condicion'] ?? 0); // futuro: tb_ventas_condicion
$cupon     = $_POST['dato_cupon'] ?? '';           // futuro: tb_ventas_condicion
$cierre    = (int)($_SESSION['cierre']      ?? 0);

mysqli_begin_transaction($conexion);

try {
    // Descuenta stock: inserta cantidad negativa en tb_existencias.
    // VALUES(cantidad) referencia el valor computado del SELECT (compatible MariaDB 10.6).
    $stmt1 = mysqli_prepare($conexion,
        "INSERT INTO tb_existencias (id_productos, cantidad)
         SELECT id_productos, cantidad * -1
         FROM tb_ventas
         WHERE id_sucursal = ? AND numero_factura = ? AND estado = '0' AND id_cierre = ?
         ON DUPLICATE KEY UPDATE tb_existencias.cantidad = tb_existencias.cantidad + VALUES(cantidad)"
    );
    mysqli_stmt_bind_param($stmt1, 'iii', $sucursal, $factura, $cierre);
    if (!mysqli_stmt_execute($stmt1)) {
        throw new Exception('Error actualizando stock');
    }
    mysqli_stmt_close($stmt1);

    // Confirma la venta usando solo columnas que existen en tb_ventas.
    // cupon e id_condicion_venta NO existen en tb_ventas — van en tb_ventas_condicion (pendiente).
    $stmt2 = mysqli_prepare($conexion,
        "UPDATE tb_ventas SET estado = '1'
         WHERE estado = '0' AND id_cierre = ? AND id_sucursal = ?
           AND numero_factura = ? AND id_clientes = ?"
    );
    mysqli_stmt_bind_param($stmt2, 'iiii', $cierre, $sucursal, $factura, $cliente);
    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception('Error confirmando venta');
    }
    mysqli_stmt_close($stmt2);

    mysqli_commit($conexion);
    echo json_encode(['success' => 'true', 'tipo' => 'ticket']);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => 'false', 'error' => $e->getMessage()]);
}
