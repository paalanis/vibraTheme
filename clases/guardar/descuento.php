<?php
/**
 * clases/guardar/descuento.php — CRUD para tb_descuentos.
 * Acciones: crear | actualizar | toggle | eliminar
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Sin sesión']); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

header('Content-Type: application/json');

$accion = trim($_POST['accion'] ?? '');

// ── Helpers ────────────────────────────────────────────────────────────────
function inputFecha(string $val): ?string {
    $v = trim($val);
    if ($v === '' || $v === '0000-00-00') return null;
    // validar formato YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return null;
    return $v;
}

// ── CREAR ──────────────────────────────────────────────────────────────────
if ($accion === 'crear') {
    $nombre       = trim($_POST['nombre']       ?? '');
    $tipo_alcance = trim($_POST['tipo_alcance'] ?? 'global');
    $id_alcance   = trim($_POST['id_alcance']   ?? '') !== '' ? (int)$_POST['id_alcance'] : null;
    $porcentaje   = (float)($_POST['porcentaje'] ?? 0);
    $fecha_desde  = inputFecha($_POST['fecha_desde'] ?? '');
    $fecha_hasta  = inputFecha($_POST['fecha_hasta'] ?? '');
    $activo       = isset($_POST['activo']) ? 1 : 0;
    $id_sucursal  = trim($_POST['id_sucursal'] ?? '') !== '' ? (int)$_POST['id_sucursal'] : null;
    $creado_por   = (int)($_SESSION['id_usuario'] ?? 0);

    if ($nombre === '' || $porcentaje <= 0 || $porcentaje > 100) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']); exit();
    }
    if (!in_array($tipo_alcance, ['global','marca','tipo','producto'], true)) {
        echo json_encode(['success' => false, 'error' => 'Tipo de alcance inválido']); exit();
    }
    if ($tipo_alcance !== 'global' && $id_alcance === null) {
        echo json_encode(['success' => false, 'error' => 'Debe seleccionar el alcance']); exit();
    }
    if ($tipo_alcance === 'global') $id_alcance = null;

    $condiciones_pago = trim($_POST['condiciones_pago'] ?? '') ?: null;
    $acumulable       = isset($_POST['acumulable']) ? 1 : 0;

    $stmt = mysqli_prepare($conexion,
        "INSERT INTO tb_descuentos
         (nombre, tipo_alcance, id_alcance, porcentaje, fecha_desde, fecha_hasta,
          activo, id_sucursal, condiciones_pago, acumulable, creado_por)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // ssidssiiisi: nombre(s), tipo(s), id_alcance(i), porcentaje(d), fecha_desde(s),
    //              fecha_hasta(s), activo(i), id_sucursal(i), condiciones_pago(s), acumulable(i), creado_por(i)
    mysqli_stmt_bind_param($stmt, 'ssidssiiisi',
        $nombre, $tipo_alcance, $id_alcance, $porcentaje,
        $fecha_desde, $fecha_hasta, $activo, $id_sucursal,
        $condiciones_pago, $acumulable, $creado_por
    );

    if (mysqli_stmt_execute($stmt)) {
        $nuevo_id = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true, 'id' => $nuevo_id]);
    } else {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'error' => $err]);
    }
    exit();
}

// ── ACTUALIZAR ─────────────────────────────────────────────────────────────
if ($accion === 'actualizar') {
    $id           = (int)($_POST['id_descuento'] ?? 0);
    $nombre       = trim($_POST['nombre']        ?? '');
    $tipo_alcance = trim($_POST['tipo_alcance']  ?? 'global');
    $id_alcance   = trim($_POST['id_alcance']    ?? '') !== '' ? (int)$_POST['id_alcance'] : null;
    $porcentaje   = (float)($_POST['porcentaje'] ?? 0);
    $fecha_desde  = inputFecha($_POST['fecha_desde'] ?? '');
    $fecha_hasta  = inputFecha($_POST['fecha_hasta'] ?? '');
    $activo       = isset($_POST['activo']) ? 1 : 0;
    $id_sucursal  = trim($_POST['id_sucursal'] ?? '') !== '' ? (int)$_POST['id_sucursal'] : null;

    if ($id <= 0 || $nombre === '' || $porcentaje <= 0 || $porcentaje > 100) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']); exit();
    }
    if ($tipo_alcance === 'global') $id_alcance = null;

    $condiciones_pago = trim($_POST['condiciones_pago'] ?? '') ?: null;
    $acumulable       = isset($_POST['acumulable']) ? 1 : 0;

    $stmt = mysqli_prepare($conexion,
        "UPDATE tb_descuentos
         SET nombre=?, tipo_alcance=?, id_alcance=?, porcentaje=?,
             fecha_desde=?, fecha_hasta=?, activo=?, id_sucursal=?,
             condiciones_pago=?, acumulable=?
         WHERE id_descuento=?"
    );
    mysqli_stmt_bind_param($stmt, 'ssidssiiisi',
        $nombre, $tipo_alcance, $id_alcance, $porcentaje,
        $fecha_desde, $fecha_hasta, $activo, $id_sucursal,
        $condiciones_pago, $acumulable, $id
    );
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true]);
    } else {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'error' => $err]);
    }
    exit();
}

// ── TOGGLE ACTIVO/PAUSADO ──────────────────────────────────────────────────
if ($accion === 'toggle') {
    $id = (int)($_POST['id_descuento'] ?? 0);
    if ($id <= 0) { echo json_encode(['success' => false]); exit(); }

    $stmt = mysqli_prepare($conexion,
        "UPDATE tb_descuentos SET activo = 1 - activo WHERE id_descuento = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Devolver nuevo estado
    $stmt2 = mysqli_prepare($conexion,
        "SELECT activo FROM tb_descuentos WHERE id_descuento = ?"
    );
    mysqli_stmt_bind_param($stmt2, 'i', $id);
    mysqli_stmt_execute($stmt2);
    $nuevo_activo = null;
    mysqli_stmt_bind_result($stmt2, $nuevo_activo);
    mysqli_stmt_fetch($stmt2);
    mysqli_stmt_close($stmt2);

    echo json_encode(['success' => true, 'activo' => (int)$nuevo_activo]);
    exit();
}

// ── ELIMINAR ───────────────────────────────────────────────────────────────
if ($accion === 'eliminar') {
    $id = (int)($_POST['id_descuento'] ?? 0);
    if ($id <= 0) { echo json_encode(['success' => false]); exit(); }

    $stmt = mysqli_prepare($conexion,
        "DELETE FROM tb_descuentos WHERE id_descuento = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['success' => $ok]);
    exit();
}

echo json_encode(['success' => false, 'error' => 'Acción desconocida']);
