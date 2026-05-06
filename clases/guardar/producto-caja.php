<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']);
    exit();
}

$codigo_cargado = $_REQUEST['dato_codigo'] ?? '';

if ($codigo_cargado !== '') {
    if (substr($codigo_cargado, 0, 2) === '99') {
        $codigo     = substr($codigo_cargado, 0, 7);
        $cualprecio = 'sql';
        $cantidad   = (float)(substr($codigo_cargado, 7, 5)) / 1000;
    } else {
        $codigo     = $codigo_cargado;
        $cualprecio = 'sql';
        $cantidad   = (float)($_REQUEST['dato_cantidad'] ?? 1);
    }
} else {
    $codigo     = $_REQUEST['dato_producto'] ?? '';
    $cualprecio = 'manual';
    $cantidad   = (float)($_REQUEST['dato_cantidad'] ?? 1);
}

// Busca producto por código — prepared statement para evitar SQL injection.
// bind_result en lugar de get_result (compatible con hosting sin mysqlnd).
$stmt = mysqli_prepare($conexion,
    "SELECT precio_venta, id_productos, pesable FROM tb_productos WHERE codigo = ?"
);
mysqli_stmt_bind_param($stmt, 's', $codigo);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $precio_sql, $id_producto, $pesable);
$found = (bool)mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$cierre  = (int)($_SESSION['cierre']       ?? 0);
$factura = (int)($_REQUEST['dato_factura'] ?? 0);
$cliente = (int)($_REQUEST['dato_cliente'] ?? 0);

if ($found) {
    $sucursal = (int)($_REQUEST['dato_sucursal'] ?? 0);
    $fecha    = $_REQUEST['dato_fecha'] ?? '';
    $precio   = ($cualprecio === 'sql')
                    ? (float)$precio_sql
                    : (float)($_REQUEST['dato_precio'] ?? 0);

    if ((int)$pesable === 1) {
        $subtotal = round($precio * $cantidad, 2);
    } else {
        $cantidad = $cantidad * 1000;
        $subtotal = round($precio * $cantidad, 2);
    }

    // Inserta en carrito (estado queda en 0 hasta confirmar factura).
    // Tipos: s=fecha, i=cliente, i=sucursal, i=factura, i=id_producto,
    //        d=cantidad, d=precio, d=subtotal, i=cierre
    $stmt2 = mysqli_prepare($conexion,
        "INSERT INTO tb_ventas
         (fecha, id_clientes, id_sucursal, numero_factura, id_productos, cantidad, precio_venta, subtotal, id_cierre)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt2, 'siiiidddi',
        $fecha, $cliente, $sucursal, $factura, $id_producto, $cantidad, $precio, $subtotal, $cierre
    );
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    echo json_encode(['success' => 'true', 'factura' => $factura, 'cliente' => $cliente, 'cierre' => $cierre]);

} else {
    echo json_encode(['success' => 'no_existe', 'factura' => $factura, 'cliente' => $cliente, 'cierre' => $cierre]);
}
