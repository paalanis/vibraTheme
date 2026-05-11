<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']); exit();
}

$fecha      = $_POST['dato_fecha']      ?? '';
$proveedor  = (int)($_POST['dato_proveedor'] ?? 0);
$sucursal   = (int)($_POST['dato_sucursal']  ?? 0);
$remito_num = $_POST['dato_remito']     ?? '';
$obs        = $_POST['dato_obs']        ?? '';
$producto   = $_POST['dato_producto']   ?? '';  // código del producto
$cantidad   = (float)($_POST['dato_cantidad'] ?? 0);

// precio_costo: NULL si vacío (sin cambio), float si fue ingresado
$precio_raw   = trim($_POST['dato_precio']  ?? '');
$precio_costo = ($precio_raw !== '') ? (float)$precio_raw : null;

// margen_ganancia: NULL si vacío
$margen_raw      = trim($_POST['dato_margen'] ?? '');
$margen_ganancia = ($margen_raw !== '') ? (float)$margen_raw : null;

$remito = $sucursal . '-' . $remito_num;

// Buscar id_productos a partir del código
$stmtp = mysqli_prepare($conexion,
    "SELECT id_productos FROM tb_productos WHERE codigo = ?"
);
mysqli_stmt_bind_param($stmtp, 's', $producto);
mysqli_stmt_execute($stmtp);
mysqli_stmt_bind_result($stmtp, $id_producto);
$found = (bool)mysqli_stmt_fetch($stmtp);
mysqli_stmt_close($stmtp);

if (!$found) {
    echo json_encode(['success' => 'false', 'remito' => $remito, 'proveedor' => $proveedor]);
    exit();
}

// Control duplicado (mismo producto en mismo remito pendiente)
$stmtc = mysqli_prepare($conexion,
    "SELECT id_remitos FROM tb_remitos
     WHERE id_proveedores = ? AND id_productos = ? AND numero = ? AND estado = '0'"
);
mysqli_stmt_bind_param($stmtc, 'iis', $proveedor, $id_producto, $remito);
mysqli_stmt_execute($stmtc);
mysqli_stmt_bind_result($stmtc, $id_dup);
$duplicado = (bool)mysqli_stmt_fetch($stmtc);
mysqli_stmt_close($stmtc);

if ($duplicado) {
    echo json_encode(['success' => 'duplicado', 'remito' => $remito, 'proveedor' => $proveedor]);
    exit();
}

// INSERT en tb_remitos con precio_costo y margen_ganancia
// Tipos: s(fecha) i(proveedor) s(remito) s(obs) i(id_producto) d(cantidad) d(precio_costo) d(margen)
$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_remitos
     (fecha, id_proveedores, numero, obs, id_productos, cantidad, precio_costo, margen_ganancia)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'sissiddd',
    $fecha, $proveedor, $remito, $obs, $id_producto,
    $cantidad, $precio_costo, $margen_ganancia
);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => 'true', 'remito' => $remito, 'proveedor' => $proveedor]);
