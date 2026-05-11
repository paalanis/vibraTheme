<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
csrf_validate();

$nombre = trim($_POST['dato_nombre'] ?? '');
$marca  = (int)($_POST['dato_marca']  ?? 0);
$genero = (int)($_POST['dato_genero'] ?? 0);
$tipo   = (int)($_POST['dato_tipo']   ?? 0);
$talle  = (int)($_POST['dato_talle']  ?? 0);
$color  = (int)($_POST['dato_color']  ?? 0);
$iva    = (int)($_POST['dato_iva']    ?? 0);
$foto   = trim($_POST['dato_foto']    ?? '');
$costo  = strlen(trim($_POST['dato_costo']  ?? '')) > 0 ? (float)$_POST['dato_costo']  : null;
$margen = strlen(trim($_POST['dato_margen'] ?? '')) > 0 ? (float)$_POST['dato_margen'] : null;

// ── Control duplicado por atributos ───────────────────────────────────────
$stmt_dup = mysqli_prepare($conexion,
    "SELECT id_productos, nombre FROM tb_productos
     WHERE LOWER(nombre)=LOWER(?) AND id_marca=? AND id_genero=?
       AND id_tipo=? AND id_talle=? AND id_color=?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt_dup, 'siiiii', $nombre, $marca, $genero, $tipo, $talle, $color);
mysqli_stmt_execute($stmt_dup);
mysqli_stmt_bind_result($stmt_dup, $dup_id, $dup_nombre);
$duplicado = (bool)mysqli_stmt_fetch($stmt_dup);
mysqli_stmt_close($stmt_dup);

if ($duplicado) {
    echo json_encode([
        'success' => 'false',
        'error'   => 'DUPLICADO: ya existe el producto "' .
                     mb_convert_encoding($dup_nombre, 'UTF-8', 'ISO-8859-1') . '"',
    ]);
    exit();
}

// ── Generar EAN-13 secuencial ─────────────────────────────────────────────
function ean13Check(string $base12): int {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$base12[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    return (10 - ($sum % 10)) % 10;
}

$stmt_seq = mysqli_prepare($conexion,
    "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 4, 9) AS UNSIGNED)), 0) + 1
     FROM tb_productos WHERE codigo REGEXP '^200[0-9]{10}$'"
);
mysqli_stmt_execute($stmt_seq);
mysqli_stmt_bind_result($stmt_seq, $next_seq);
mysqli_stmt_fetch($stmt_seq);
mysqli_stmt_close($stmt_seq);

$base12 = '200' . str_pad((int)$next_seq, 9, '0', STR_PAD_LEFT);
$codigo = $base12 . ean13Check($base12);

// ── INSERT ────────────────────────────────────────────────────────────────
// Tipos: s(nombre) i×6(marca,genero,tipo,talle,color,iva) s×2(codigo,foto) d×2(costo,margen)
$stmt = mysqli_prepare($conexion,
    "INSERT INTO tb_productos
     (nombre, id_marca, id_genero, id_tipo, id_talle, id_color,
      id_iva_condicion, codigo, foto, precio_costo, margen_ganancia)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    echo json_encode(['success' => 'false', 'error' => mysqli_error($conexion)]);
    exit();
}

mysqli_stmt_bind_param($stmt, 'siiiiiissdd',
    $nombre, $marca, $genero, $tipo, $talle, $color,
    $iva, $codigo, $foto, $costo, $margen
);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => 'false', 'error' => mysqli_stmt_error($stmt)]);
    mysqli_stmt_close($stmt);
    exit();
}
mysqli_stmt_close($stmt);

echo json_encode(['success' => 'true', 'codigo' => $codigo]);
