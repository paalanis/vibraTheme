<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo json_encode(['success' => 'false']);
    exit();
}

$remito    = $_REQUEST['remito']    ?? '';
$proveedor = (int)($_REQUEST['proveedor'] ?? 0);

// El JS construye el número como "0001-00000001" pero carga-producto.php
// castea la sucursal a (int), guardando "1-00000001" en la BD.
// Normalizamos para que la búsqueda coincida.
$parts  = explode('-', $remito, 2);
$remito = (count($parts) === 2) ? ((int)$parts[0]) . '-' . $parts[1] : $remito;

// Distingue tres estados:
// 'borrador' → existe con estado='0' (puede continuar o descartar)
// 'false'    → ya fue confirmado (estado='1'), no se puede reusar
// 'true'     → no existe, disponible

$stmt = mysqli_prepare($conexion,
    "SELECT estado, COUNT(*) AS cant
     FROM tb_remitos
     WHERE numero = ? AND id_proveedores = ?
     GROUP BY estado
     ORDER BY estado ASC
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'si', $remito, $proveedor);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $estado, $cant);
$found = (bool)mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$found) {
    echo json_encode(['success' => 'true']);
} elseif ($estado === '0') {
    echo json_encode(['success' => 'borrador', 'cant' => (int)$cant]);
} else {
    echo json_encode(['success' => 'false']);
}
