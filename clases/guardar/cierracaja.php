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

// Se usa la SESSION como fuente de verdad — no se acepta el cierre del REQUEST
// para evitar que un usuario manipule el id_cierre en el payload.
$cierre = (int)($_SESSION['cierre'] ?? 0);

if ($cierre <= 0) {
    echo json_encode(['success' => 'false']);
    exit();
}

// Elimina productos pendientes (carrito no confirmado) del cierre
$stmt1 = mysqli_prepare($conexion,
    "DELETE FROM tb_ventas WHERE estado = '0' AND id_cierre = ?"
);
mysqli_stmt_bind_param($stmt1, 'i', $cierre);
mysqli_stmt_execute($stmt1);
mysqli_stmt_close($stmt1);

// Cierra la caja
$stmt2 = mysqli_prepare($conexion,
    "UPDATE tb_cierres SET estado = '1', fecha_cierre = NOW() WHERE id_cierre = ?"
);
mysqli_stmt_bind_param($stmt2, 'i', $cierre);
mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

unset($_SESSION['cierre']);

echo json_encode(['success' => 'true', 'abrecaja' => 'true']);
?>
