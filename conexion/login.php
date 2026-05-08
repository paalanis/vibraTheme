<?php
session_start();

// ── Forzar HTTPS ─────────────────────────────────────────────────────────────
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php'); exit();
}

require_once 'config.php';

// ── Brute force: 5 intentos → bloqueo 15 min por IP ──────────────────────────
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
$tmp      = sys_get_temp_dir();
$f_i      = $tmp . '/li_' . md5($ip) . '_i';
$f_b      = $tmp . '/li_' . md5($ip) . '_b';
$max      = 5;
$ban_time = 15 * 60;

$intentos = file_exists($f_i) ? (int)file_get_contents($f_i) : 0;
$bloqueo  = file_exists($f_b) ? (int)file_get_contents($f_b) : 0;

if ($bloqueo > 0) {
    $elapsed = time() - $bloqueo;
    if ($elapsed < $ban_time) {
        $min = ceil(($ban_time - $elapsed) / 60);
        header('Location: ../indexerror.php?bloqueado=' . $min); exit();
    }
    @unlink($f_i); @unlink($f_b);
    $intentos = 0;
}

// ── Validar inputs ────────────────────────────────────────────────────────────
$usuario = trim($_POST['usuario'] ?? '');
$pass    = $_POST['pass']         ?? '';
if (empty($usuario) || empty($pass)) {
    header('Location: ../indexerror.php'); exit();
}

// ── Conexión restringida para autenticar ──────────────────────────────────────
$cx = mysqli_connect(DB_HOST, DB_USER_LOGIN, DB_PASS_LOGIN, DB_NAME_AUTH);
if (!$cx) {
    error_log('Login DB failed: ' . mysqli_connect_error());
    header('Location: ../indexerror.php'); exit();
}
mysqli_set_charset($cx, 'utf8mb4');

// ── Prepared statement sin mysqli_stmt_get_result() ───────────────────────────
// Usamos bind_result para compatibilidad con hostings sin mysqlnd
$stmt = mysqli_prepare($cx,
    "SELECT id_usuario, nombre, data_base, db_user, db_pass,
        tipo_user, estado, pass, autoriza
    FROM tb_usuarios WHERE nombre = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $usuario);
mysqli_stmt_execute($stmt);

$id_usuario = $nombre_db = $data_base = $db_user = $db_pass =
$tipo_user  = $estado    = $pass_hash = null;

mysqli_stmt_bind_result($stmt,
    $id_usuario, $nombre_db, $data_base, $db_user, $db_pass,
    $tipo_user, $estado, $pass_hash, $autoriza
);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
mysqli_close($cx);

// ── Verificar contraseña ──────────────────────────────────────────────────────
if (!$found || !password_verify($pass, $pass_hash)) {
    $intentos++;
    file_put_contents($f_i, $intentos);
    if ($intentos >= $max) {
        file_put_contents($f_b, time());
        @unlink($f_i);
        header('Location: ../indexerror.php?bloqueado=15'); exit();
    }
    header('Location: ../indexerror.php?intento=' . $intentos); exit();
}

// ── Éxito: limpiar contadores ─────────────────────────────────────────────────
@unlink($f_i); @unlink($f_b);

// ── Regenerar sesión ──────────────────────────────────────────────────────────
session_regenerate_id(true);
$sesion_actual   = session_id();
$sesion_anterior = $estado;

// ── Guardar en sesión ─────────────────────────────────────────────────────────
$ip_map = [
    '192.168.1.102' => '192.168.1.104',
    '192.168.1.103' => '192.168.1.105',
];
$_SESSION['puesto']    = $ip_map[$ip] ?? '192.168.1.105';
$_SESSION['ipcliente'] = $ip;
$_SESSION['usuario']   = $nombre_db;
$_SESSION['tipo_user'] = $tipo_user;
$_SESSION['basedatos'] = $data_base;
$_SESSION['db_user']   = $db_user;
$_SESSION['db_pass']   = $db_pass;
$_SESSION['id_usuario']= $id_usuario;
$_SESSION['autoriza'] = $autoriza;

session_write_close();

// ── Destruir sesión anterior del mismo usuario ────────────────────────────────
if ($sesion_anterior && $sesion_anterior !== $sesion_actual) {
    session_id($sesion_anterior);
    session_start();
    session_destroy();
    session_write_close();
    session_id($sesion_actual);
    session_start();
}

// ── Actualizar estado con credenciales propias del usuario ────────────────────
$cx_app = mysqli_connect(DB_HOST, $db_user, $db_pass, $data_base);
if ($cx_app) {
    mysqli_set_charset($cx_app, 'utf8mb4');
    $stmt2 = mysqli_prepare($cx_app,
        "UPDATE tb_usuarios SET estado = ? WHERE id_usuario = ?"
    );
    mysqli_stmt_bind_param($stmt2, 'si', $sesion_actual, $id_usuario);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
    mysqli_close($cx_app);
}

header('Location: ../index2.php'); exit();
