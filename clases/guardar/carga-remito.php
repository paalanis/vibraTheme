<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']);
    exit();
}

$proveedor  = (int)($_POST['dato_proveedor'] ?? 0);
$sucursal   = (int)($_POST['dato_sucursal']  ?? 0);
$remito_num = $_POST['dato_remito'] ?? '';
$remito     = $sucursal . '-' . $remito_num;

mysqli_begin_transaction($conexion);

try {
    // 1. Actualiza precio_costo en tb_productos para las líneas donde fue modificado.
    //    Se ejecuta ANTES de cambiar estado a '1' para que el WHERE estado='0' funcione.
    //    Solo actualiza si precio_costo IS NOT NULL (usuario lo modificó).
    $stmt_precio = mysqli_prepare($conexion,
        "UPDATE tb_productos p
         JOIN tb_remitos r ON r.id_productos = p.id_productos
         SET p.precio_costo = r.precio_costo
         WHERE r.estado = '0' AND r.numero = ? AND r.id_proveedores = ?
           AND r.precio_costo IS NOT NULL"
    );
    mysqli_stmt_bind_param($stmt_precio, 'si', $remito, $proveedor);
    if (!mysqli_stmt_execute($stmt_precio)) {
        throw new Exception('Error actualizando precio costo');
    }
    mysqli_stmt_close($stmt_precio);

    // 2. Ingresa stock: suma cantidad a tb_existencias.
    //    VALUES(cantidad) compatible con MariaDB 10.6.
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

    // 3. Marca el remito como procesado.
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
