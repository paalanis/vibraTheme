<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

$producto = (int)($_REQUEST['dato_producto'] ?? 0);
$genero   = (int)($_REQUEST['dato_genero']   ?? 0);  // antes dato_rubro
$estado   = $_REQUEST['dato_estado'] ?? 'todos';

// Lee stock_minimo de tb_configuracion
$stock_minimo = 5;
try {
    $stmt_cfg = mysqli_prepare($conexion,
        "SELECT MIN(CAST(valor AS UNSIGNED)) FROM tb_configuracion WHERE clave = 'stock_minimo'"
    );
    mysqli_stmt_execute($stmt_cfg);
    mysqli_stmt_bind_result($stmt_cfg, $cfg_min);
    mysqli_stmt_fetch($stmt_cfg);
    if ($cfg_min !== null && (int)$cfg_min > 0) $stock_minimo = (int)$cfg_min;
    mysqli_stmt_close($stmt_cfg);
} catch (\Throwable $e) {
    $stock_minimo = 5;
}

// Construye query
$where  = ["1=1"];
$params = [];
$types  = '';

if ($producto > 0) {
    $where[]  = "p.id_productos = ?";
    $params[] = $producto;
    $types   .= 'i';
}
if ($genero > 0) {
    $where[]  = "p.id_genero = ?";  // ← antes id_rubro
    $params[] = $genero;
    $types   .= 'i';
}

$where_sql = implode(' AND ', $where);

// precio_venta se calcula en tiempo real desde costo * (1 + margen/100)
$stmt = mysqli_prepare($conexion,
    "SELECT
        p.id_productos,
        p.nombre,
        g.nombre                                              AS genero,
        COALESCE(e.cantidad, 0)                              AS cantidad,
        COALESCE(p.precio_costo, 0)                          AS precio_costo,
        COALESCE(p.margen_ganancia, 0)                       AS margen,
        ROUND(COALESCE(p.precio_costo,0) *
              (1 + COALESCE(p.margen_ganancia,0) / 100), 2) AS precio_venta
     FROM tb_productos p
     LEFT JOIN tb_existencias e ON e.id_productos = p.id_productos
     LEFT JOIN tb_genero g      ON g.id_genero    = p.id_genero
     WHERE $where_sql
     ORDER BY p.nombre ASC"
);

if (!empty($params)) {
    $bind_args = array_merge([$stmt, $types], $params);
    $refs = [];
    foreach ($bind_args as $k => $v) { $refs[$k] = &$bind_args[$k]; }
    call_user_func_array('mysqli_stmt_bind_param', $refs);
}

mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_producto, $r_genero, $r_cantidad, $r_costo, $r_margen, $r_venta);

$datos = [];
while (mysqli_stmt_fetch($stmt)) {
    $datos[] = [
        'id'       => $r_id,
        'producto' => mb_convert_encoding($r_producto ?? '', 'UTF-8', 'ISO-8859-1'),
        'genero'   => mb_convert_encoding($r_genero   ?? '', 'UTF-8', 'ISO-8859-1'),
        'cantidad' => (float)$r_cantidad,
        'costo'    => (float)$r_costo,
        'margen'   => (float)$r_margen,
        'venta'    => (float)$r_venta,
    ];
}
mysqli_stmt_close($stmt);

// Filtro por estado
if ($estado !== 'todos') {
    $datos = array_values(array_filter($datos, function($d) use ($estado, $stock_minimo) {
        if ($estado === 'sin_stock') return $d['cantidad'] <= 0;
        if ($estado === 'bajo')      return $d['cantidad'] > 0 && $d['cantidad'] <= $stock_minimo;
        if ($estado === 'ok')        return $d['cantidad'] > $stock_minimo;
        return true;
    }));
}

// Export CSV
if (isset($_POST['export_data'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_stock.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Producto','Género','Stock','Precio Costo','Margen %','Precio Venta'], ';');
    foreach ($datos as $d) {
        fputcsv($out, [
            $d['producto'], $d['genero'], $d['cantidad'],
            $d['costo'], $d['margen'], $d['venta']
        ], ';');
    }
    fclose($out);
    exit;
}

// Totales
$total_productos   = count($datos);
$total_valor_costo = 0;
$sin_stock         = 0;
$stock_bajo        = 0;
foreach ($datos as $d) {
    $total_valor_costo += $d['cantidad'] * $d['costo'];
    if ($d['cantidad'] <= 0)                     $sin_stock++;
    elseif ($d['cantidad'] <= $stock_minimo)     $stock_bajo++;
}

function stock_clase($cantidad, $minimo) {
    if ($cantidad <= 0)       return ['danger',  'Sin stock'];
    if ($cantidad <= $minimo) return ['warning', 'Stock bajo'];
    return ['success', 'OK'];
}
?>

<?php if (empty($datos)): ?>
  <div class="alert alert-info" style="margin:16px 0">
    No hay productos que coincidan con los filtros.
  </div>
<?php else: ?>

<div class="row" style="margin:16px 0">
  <div class="col-lg-3">
    <div class="panel panel-default text-center" style="padding:10px">
      <div style="font-size:22px;font-weight:bold"><?php echo $total_productos; ?></div>
      <small class="text-muted">Productos</small>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="panel panel-danger text-center" style="padding:10px">
      <div style="font-size:22px;font-weight:bold;color:#c0392b"><?php echo $sin_stock; ?></div>
      <small class="text-muted">Sin stock</small>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="panel panel-warning text-center" style="padding:10px">
      <div style="font-size:22px;font-weight:bold;color:#e67e22"><?php echo $stock_bajo; ?></div>
      <small class="text-muted">Stock bajo (≤<?php echo $stock_minimo; ?>)</small>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="panel panel-default text-center" style="padding:10px">
      <div style="font-size:18px;font-weight:bold">
        $<?php echo number_format($total_valor_costo, 2, ',', '.'); ?>
      </div>
      <small class="text-muted">Valor stock a costo</small>
    </div>
  </div>
</div>

<div class="panel panel-default">
  <div class="panel-body" style="overflow-y:auto;max-height:400px;padding:0">
    <table class="table table-striped table-hover table-condensed" style="margin:0">
      <thead>
        <tr class="active">
          <th>Producto</th>
          <th>Género</th>
          <th style="text-align:right">Stock</th>
          <th style="text-align:right">$ Costo</th>
          <th style="text-align:right">Margen %</th>
          <th style="text-align:right">$ Venta</th>
          <th style="text-align:center">Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($datos as $d):
          list($clase, $etiq) = stock_clase($d['cantidad'], $stock_minimo);
        ?>
        <tr>
          <td><?php echo htmlspecialchars($d['producto']); ?></td>
          <td><?php echo htmlspecialchars($d['genero']); ?></td>
          <td style="text-align:right"><?php echo number_format($d['cantidad'], 2, ',', '.'); ?></td>
          <td style="text-align:right">$<?php echo number_format($d['costo'],    2, ',', '.'); ?></td>
          <td style="text-align:right"><?php echo number_format($d['margen'],   2, ',', '.'); ?>%</td>
          <td style="text-align:right">$<?php echo number_format($d['venta'],   2, ',', '.'); ?></td>
          <td style="text-align:center">
            <span class="label label-<?php echo $clase; ?>"><?php echo $etiq; ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<form method="post">
  <input type="hidden" name="dato_producto" value="<?php echo $producto; ?>">
  <input type="hidden" name="dato_genero"   value="<?php echo $genero; ?>">
  <input type="hidden" name="dato_estado"   value="<?php echo htmlspecialchars($estado); ?>">
  <div style="text-align:right;margin-top:10px">
    <button type="submit" class="btn btn-info" name="export_data" value="1">
      <span class="glyphicon glyphicon-save"></span> Descargar CSV
    </button>
  </div>
</form>

<?php endif; ?>
