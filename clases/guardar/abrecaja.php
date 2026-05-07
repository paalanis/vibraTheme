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

$id       = (int)($_REQUEST['id']       ?? 0);
$efectivo = (float)($_REQUEST['efectivo'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => 'false']);
    exit();
}

// Inserta apertura de caja
$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_cierres (id_usuario, fecha_apertura) VALUES (?, NOW())"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => 'false']);
    mysqli_stmt_close($stmt);
    exit();
}
mysqli_stmt_close($stmt);

// Obtiene el id_cierre recién creado
$stmt2 = mysqli_prepare($conexion,
    "SELECT id_cierre FROM tb_cierres
     WHERE id_usuario = ? AND estado = '0'
     ORDER BY id_cierre DESC LIMIT 1"
);
mysqli_stmt_bind_param($stmt2, 'i', $id);
mysqli_stmt_execute($stmt2);
mysqli_stmt_bind_result($stmt2, $id_cierre);
$found = mysqli_stmt_fetch($stmt2);
mysqli_stmt_close($stmt2);

if (!$found || !$id_cierre) {
    echo json_encode(['success' => 'false']);
    exit();
}

$_SESSION['cierre'] = $id_cierre;

// Registra efectivo inicial en tb_retiros (tipo 1 = apertura)
$stmt3 = mysqli_prepare($conexion,
    "INSERT INTO tb_retiros (id_cierres, fecha, monto, tipo) VALUES (?, NOW(), ?, '1')"
);
mysqli_stmt_bind_param($stmt3, 'id', $id_cierre, $efectivo);
mysqli_stmt_execute($stmt3);
mysqli_stmt_close($stmt3);

echo json_encode(['success' => 'true', 'abrecaja' => 'true']);
?>
