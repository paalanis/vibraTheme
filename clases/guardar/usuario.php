<?php
session_start();

// Solo admins pueden crear usuarios
if (!isset($_SESSION['usuario']) || strtolower($_SESSION['tipo_user'] ?? '') !== 'admin') {
    echo json_encode(['success' => 'false', 'error' => 'Sin permisos']); exit();
}

require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

// ── Leer y sanear inputs ──────────────────────────────────────────────────────
$nombre    = trim($_POST['dato_nombre']    ?? '');
$pass_raw  = $_POST['dato_pass']           ?? '';
$tipo_user = trim($_POST['dato_tipo']      ?? '');
$autoriza  = trim($_POST['dato_autoriza']  ?? '');

// Validaciones básicas
if (empty($nombre) || empty($pass_raw) || empty($tipo_user)) {
    echo json_encode(['success' => 'false', 'error' => 'Campos obligatorios vacíos']); exit();
}

// Normalizar tipo_user: solo 'admin' tiene privilegios elevados
$tipo_user = strtolower($tipo_user);
if (!in_array($tipo_user, ['admin', 'cajero'])) {
    echo json_encode(['success' => 'false', 'error' => 'Tipo de usuario inválido']); exit();
}

// Autoriza: si está vacío se guarda NULL, si tiene valor debe ser numérico
$autoriza_val = null;
if ($autoriza !== '') {
    if (!ctype_digit($autoriza)) {
        echo json_encode(['success' => 'false', 'error' => 'Código de autorización inválido']); exit();
    }
    $autoriza_val = (int)$autoriza;
}

// ── Verificar que el nombre no exista ya ──────────────────────────────────────
$stmt_check = mysqli_prepare($conexion,
    "SELECT id_usuario FROM tb_usuarios WHERE nombre = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt_check, 's', $nombre);
mysqli_stmt_execute($stmt_check);
$id_existe = null;
mysqli_stmt_bind_result($stmt_check, $id_existe);
$existe = mysqli_stmt_fetch($stmt_check);
mysqli_stmt_close($stmt_check);

if ($existe) {
    echo json_encode(['success' => 'false', 'error' => 'El nombre de usuario ya existe']); exit();
}

// ── Hashear contraseña ────────────────────────────────────────────────────────
$pass_hash = password_hash($pass_raw, PASSWORD_BCRYPT, ['cost' => 12]);

// ── Usar las mismas credenciales de BD que el usuario de sesión activo ────────
// Esto evita hardcodear credenciales en el código fuente.
$db_host  = 'localhost';
$db_user  = $_SESSION['db_user']   ?? '';
$db_pass  = $_SESSION['db_pass']   ?? '';
$db_name  = $_SESSION['basedatos'] ?? '';

// ── INSERT ────────────────────────────────────────────────────────────────────
$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_usuarios
       (nombre, pass, tipo_user, data_base, db_host, db_user, db_pass, estado, autoriza)
     VALUES (?, ?, ?, ?, ?, ?, ?, '', ?)"
);

if ($autoriza_val !== null) {
    mysqli_stmt_bind_param($stmt, 'sssssssi',
        $nombre, $pass_hash, $tipo_user, $db_name, $db_host, $db_user, $db_pass, $autoriza_val
    );
} else {
    // Insertar NULL en autoriza usando una variable
    $null_val = null;
    mysqli_stmt_bind_param($stmt, 'ssssssss',
        $nombre, $pass_hash, $tipo_user, $db_name, $db_host, $db_user, $db_pass, $null_val
    );
}

$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => $ok ? 'true' : 'false']);
