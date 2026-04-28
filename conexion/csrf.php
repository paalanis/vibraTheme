<?php
/**
 * CSRF Token helpers.
 * - csrf_token()    → genera/devuelve token de la sesión
 * - csrf_validate() → valida el token recibido; termina con 403 si falla
 */

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(): void {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die(json_encode(['success' => 'false', 'error' => 'CSRF token inválido']));
    }
}