<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}

include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']);
    exit();
}

// Fuente de verdad: SESSION, no REQUEST
$cierre = (int)($_SESSION['cierre'] ?? 0);

if ($cierre <= 0) {
    echo json_encode(['success' => 'false']);
    exit();
}

mysqli_begin_transaction($conexion);

try {
    // 1. Elimina productos pendientes (carrito no confirmado)
    $stmt1 = mysqli_prepare($conexion,
        "DELETE FROM tb_ventas WHERE estado = '0' AND id_cierre = ?"
    );
    mysqli_stmt_bind_param($stmt1, 'i', $cierre);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    // 2. Mueve ventas confirmadas a tb_ventas_acumulado
    //    Reemplaza el CALL mover_ventas() que fallaba por DEFINER=root en hosting compartido
    $stmt2 = mysqli_prepare($conexion,
        "INSERT INTO tb_ventas_acumulado
            (id_clientes, id_sucursal, numero_factura, id_productos,
             cantidad, precio_venta, subtotal, id_condicion_venta, fecha, estado)
         SELECT
            id_clientes, id_sucursal, numero_factura, id_productos,
            cantidad, precio_venta, subtotal, id_condicion_venta, fecha, estado
         FROM tb_ventas
         WHERE id_cierre = ? AND estado = '1'"
    );
    mysqli_stmt_bind_param($stmt2, 'i', $cierre);
    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception('Error al mover ventas al acumulado');
    }
    mysqli_stmt_close($stmt2);

    // 3. Cierra la caja
    $stmt3 = mysqli_prepare($conexion,
        "UPDATE tb_cierres SET estado = '1', fecha_cierre = NOW() WHERE id_cierre = ?"
    );
    mysqli_stmt_bind_param($stmt3, 'i', $cierre);
    if (!mysqli_stmt_execute($stmt3)) {
        throw new Exception('Error al cerrar la caja');
    }
    mysqli_stmt_close($stmt3);

    mysqli_commit($conexion);
    unset($_SESSION['cierre']);
    echo json_encode(['success' => 'true', 'abrecaja' => 'true']);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    error_log('cierracaja.php: ' . $e->getMessage());
    echo json_encode(['success' => 'false']);
}
?>
