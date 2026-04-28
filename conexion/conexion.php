<?php
/**
 * Conexión principal — usa las credenciales del usuario logueado.
 * Cada usuario tiene su propio db_user/db_pass en tb_usuarios.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['basedatos'])) {
    // No hay sesión activa — redirigir al login
    $depth = str_repeat('../', substr_count($_SERVER['SCRIPT_NAME'], '/') - 2);
    header('Location: ' . $depth . 'index.php'); exit();
}

require_once __DIR__ . '/config.php';

$conexion = mysqli_connect(
    DB_HOST,
    $_SESSION['db_user'],
    $_SESSION['db_pass'],
    $_SESSION['basedatos']
);

if (!$conexion) {
    error_log('App DB connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    die(json_encode(['success' => 'false', 'error' => 'Error de conexión']));
}
mysqli_set_charset($conexion, 'utf8mb4');
