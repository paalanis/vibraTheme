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
    // Lee configuración de la sucursal: ¿se permite vender sin stock?
    // Si no hay fila en tb_configuracion, el default es '0' (bloquear).
    $stmt_cfg = mysqli_prepare($conexion,
        "SELECT valor FROM tb_configuracion
         WHERE id_sucursal = ? AND clave = 'permite_venta_sin_stock'"
    );
    mysqli_stmt_bind_param($stmt_cfg, 'i', $sucursal);
    mysqli_stmt_execute($stmt_cfg);
    mysqli_stmt_bind_result($stmt_cfg, $cfg_valor);
    $permite_sin_stock = mysqli_stmt_fetch($stmt_cfg) ? $cfg_valor : '0';
    mysqli_stmt_close($stmt_cfg);

    // Si la sucursal no permite venta sin stock, verificar disponibilidad.
    if ($permite_sin_stock === '0') {
        $stmt_chk = mysqli_prepare($conexion,
            "SELECT p.nombre, v.cantidad, COALESCE(e.cantidad, 0) AS disponible
             FROM tb_ventas v
             INNER JOIN tb_productos p ON p.id_productos = v.id_productos
             LEFT JOIN tb_existencias e ON e.id_productos = v.id_productos
             WHERE v.id_sucursal = ? AND v.numero_factura = ?
               AND v.estado = '0' AND v.id_cierre = ?
               AND COALESCE(e.cantidad, 0) < v.cantidad"
        );
        mysqli_stmt_bind_param($stmt_chk, 'iii', $sucursal, $factura, $cierre);
        mysqli_stmt_execute($stmt_chk);
        mysqli_stmt_bind_result($stmt_chk, $chk_nombre, $chk_cant, $chk_disp);
        $faltantes = [];
        while (mysqli_stmt_fetch($stmt_chk)) {
            $faltantes[] = utf8_encode($chk_nombre)
                         . ' (necesita: ' . (float)$chk_cant
                         . ', disponible: ' . (float)$chk_disp . ')';
        }
        mysqli_stmt_close($stmt_chk);

        if (!empty($faltantes)) {
            throw new Exception('Stock insuficiente: ' . implode(' | ', $faltantes));
        }
    }

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

    // Registra movimientos en tb_movimientos_stock (una fila por producto vendido).
    // cantidad se guarda positiva con tipo='salida' para facilitar reportes.
    $id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
    $stmt_mov = mysqli_prepare($conexion,
        "INSERT INTO tb_movimientos_stock
             (id_producto, tipo, cantidad, referencia_tipo, referencia_id, id_usuario)
         SELECT id_productos, 'salida', cantidad, 'venta', id_ventas, ?
         FROM tb_ventas
         WHERE id_sucursal = ? AND numero_factura = ? AND estado = '0' AND id_cierre = ?"
    );
    mysqli_stmt_bind_param($stmt_mov, 'iiii', $id_usuario, $sucursal, $factura, $cierre);
    if (!mysqli_stmt_execute($stmt_mov)) {
        throw new Exception('Error registrando movimientos de stock');
    }
    mysqli_stmt_close($stmt_mov);

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
