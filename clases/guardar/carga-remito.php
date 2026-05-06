<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']);
    exit();
}

$proveedor  = (int)($_REQUEST['dato_proveedor'] ?? 0);
$sucursal   = (int)($_REQUEST['dato_sucursal']  ?? 0);
$remito_num = $_REQUEST['dato_remito'] ?? '';
$remito     = $sucursal . '-' . $remito_num;

mysqli_begin_transaction($conexion);

try {
    // Ingresa stock: suma cantidad a tb_existencias.
    // VALUES(cantidad) referencia el valor a insertar (compatible MySQL 5.6+).
    $stmt1 = mysqli_prepare($conexion,
        "INSERT INTO tb_existencias (id_productos, cantidad)
         SELECT id_productos, cantidad
         FROM tb_remitos
         WHERE estado = '0' AND numero = ? AND id_proveedores = ?
         ON DUPLICATE KEY UPDATE tb_existencias.cantidad = tb_existencias.cantidad + VALUES(cantidad)"
    );
    mysqli_stmt_bind_param($stmt1, 'si', $remito, $proveedor);
    if (!mysqli_stmt_execute($stmt1)) {
        throw new Exception('Error actualizando existencias');
    }
    mysqli_stmt_close($stmt1);

    // Marca el remito como procesado.
    $stmt2 = mysqli_prepare($conexion,
        "UPDATE tb_remitos SET estado = '1'
         WHERE estado = '0' AND numero = ? AND id_proveedores = ?"
    );
    mysqli_stmt_bind_param($stmt2, 'si', $remito, $proveedor);
    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception('Error actualizando estado del remito');
    }
    mysqli_stmt_close($stmt2);

    mysqli_commit($conexion);
    echo json_encode(['success' => 'true']);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => 'false', 'error' => $e->getMessage()]);
}
