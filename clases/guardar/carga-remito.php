<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$proveedor  = (int)($_POST['dato_proveedor'] ?? 0);
$sucursal   = (int)($_POST['dato_sucursal']  ?? 0);
$remito_num = $_POST['dato_remito'] ?? '';
$remito     = $sucursal . '-' . $remito_num;

mysqli_begin_transaction($conexion);

try {
    // 1. Actualiza precio_costo Y margen_ganancia en tb_productos donde el
    //    usuario los modificó en el remito (IS NOT NULL).
    $stmt_precio = mysqli_prepare($conexion,
        "UPDATE tb_productos p
         JOIN tb_remitos r ON r.id_productos = p.id_productos
         SET p.precio_costo     = COALESCE(r.precio_costo,     p.precio_costo),
             p.margen_ganancia  = COALESCE(r.margen_ganancia,  p.margen_ganancia)
         WHERE r.estado = '0' AND r.numero = ? AND r.id_proveedores = ?
           AND (r.precio_costo IS NOT NULL OR r.margen_ganancia IS NOT NULL)"
    );
    mysqli_stmt_bind_param($stmt_precio, 'si', $remito, $proveedor);
    if (!mysqli_stmt_execute($stmt_precio)) {
        throw new Exception('Error actualizando precios en tb_productos');
    }
    mysqli_stmt_close($stmt_precio);

    // 2. Actualiza stock en tb_existencias
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

    // 3. Registra movimientos de stock
    $id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
    $stmt_mov = mysqli_prepare($conexion,
        "INSERT INTO tb_movimientos_stock
             (id_producto, tipo, cantidad, referencia_tipo, referencia_id, id_usuario, obs)
         SELECT id_productos, 'entrada', cantidad, 'remito', id_remitos, ?, numero
         FROM tb_remitos
         WHERE estado = '0' AND numero = ? AND id_proveedores = ?"
    );
    mysqli_stmt_bind_param($stmt_mov, 'isi', $id_usuario, $remito, $proveedor);
    if (!mysqli_stmt_execute($stmt_mov)) {
        throw new Exception('Error registrando movimientos de stock');
    }
    mysqli_stmt_close($stmt_mov);

    // 4. Marca el remito como procesado
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
