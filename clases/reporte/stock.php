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
$rubro    = (int)($_REQUEST['dato_rubro']    ?? 0);
$estado   = $_REQUEST['dato_estado'] ?? 'todos';

// Lee stock_minimo de tb_configuracion.
// try-catch necesario: PHP 8.1+ lanza mysqli_sql_exception en lugar de retornar false
// cuando el usuario DB no tiene permisos o la tabla no existe.
$stock_minimo = 5;
try {
    $stmt_cfg = mysqli_prepare($conexion,
        "SELECT MIN(CAST(valor AS UNSIGNED)) FROM tb_configuracion WHERE clave = 'stock_minimo'"
    );
    mysqli_stmt_execute($stmt_cfg);
    mysqli_stmt_bind_result($stmt_cfg, $cfg_min);
    mysqli_stmt_fetch($stmt_cfg);
    if ($cfg_min !== null && (int)$cfg_min > 0) {
        $stock_minimo = (int)$cfg_min;
    }
    mysqli_stmt_close($stmt_cfg);
} catch (\Throwable $e) {
    $stock_minimo = 5; // default si tb_configuracion no es accesible
}

// Query principal: parte de tb_productos (LEFT JOIN) para incluir productos sin movimientos
$where = ["1=1"];
$params = [];
$types  = '';

if ($producto > 0) {
    $where[]  = "p.id_productos = ?";
    $params[] = $producto;
    $types   .= 'i';
}
if ($rubro > 0) {
    $where[]  = "p.id_rubro = ?";
    $params[] = $rubro;
    $types   .= 'i';
}

$where_sql = implode(' AND ', $where);

$stmt = mysqli_prepare($conexion,
    "SELECT
        p.id_productos,
        p.nombre                        AS producto,
        r.nombre                        AS rubro,
        COALESCE(e.cantidad, 0)         AS cantidad,
        COALESCE(p.precio_costo, 0)     AS precio_costo,
        COALESCE(p.precio_venta, 0)     AS precio_venta
     FROM tb_productos p
     LEFT JOIN tb_existencias e ON e.id_productos = p.id_productos
     LEFT JOIN tb_rubro r ON r.id_rubro = p.id_rubro
     WHERE $where_sql
     ORDER BY p.nombre ASC"
);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_producto, $r_rubro, $r_cantidad, $r_costo, $r_venta);

$datos = [];
while (mysqli_stmt_fetch($stmt)) {
    $datos[] = [
        'id'       => $r_id,
        'producto' => mb_convert_encoding($r_producto ?? '', 'UTF-8', 'ISO-8859-1'),
        'rubro'    => mb_convert_encoding($r_rubro    ?? '', 'UTF-8', 'ISO-8859-1'),
        'cantidad' => (float)$r_cantidad,
        'costo'    => (float)$r_costo,
        'venta'    => (float)$r_venta,
    ];
}
mysqli_stmt_close($stmt);

// Filtro por estado de stock (post-query)
if ($estado !== 'todos') {
    $datos = array_filter($datos, function($d) use ($estado, $stock_minimo) {
        if ($estado === 'sin_stock') return $d['cantidad'] <= 0;
        if ($estado === 'bajo')      return $d['cantidad'] > 0 && $d['cantidad'] <= $stock_minimo;
        if ($estado === 'ok')        return $d['cantidad'] > $stock_minimo;
        return true;
    });
    $datos = array_values($datos);
}

// Export CSV
if (isset($_POST['export_data'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_stock.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 para Excel
    fputcsv($out, ['Producto','Rubro','Stock','Precio Costo','Precio Venta','Margen %'], ';');
    foreach ($datos as $d) {
        $margen = $d['venta'] > 0
            ? round((($d['venta'] - $d['costo']) / $d['venta']) * 100, 1)
            : 0;
        fputcsv($out, [
            $d['producto'], $d['rubro'], $d['cantidad'],
            $d['costo'], $d['venta'], $margen
        ], ';');
    }
    fclose($out);
    exit;
}

// Totales
$total_productos  = count($datos);
$total_valor_costo = array_sum(array_map(fn($d) => $d['cantidad'] * $d['costo'], $datos));
$sin_stock        = count(array_filter($datos, fn($d) => $d['cantidad'] <= 0));
$stock_bajo       = count(array_filter($datos, fn($d) => $d['cantidad'] > 0 && $d['cantidad'] <= $stock_minimo));

// Helper: clase y etiqueta según stock
function stock_clase($cantidad, $minimo) {
    if ($cantidad <= 0)     return ['danger',  'Sin stock'];
    if ($cantidad <= $minimo) return ['warning', 'Stock bajo'];
    return ['success', 'OK'];
}
?>

<?php if (empty($datos)): ?>
  <div class="alert alert-info" style="margin:16px 0">No hay productos que coincidan con los filtros.</div>
  <script>document.getElementById("botonExcel1").style.visibility = "hidden";</script>
<?php else: ?>

<!-- Resumen -->
<div class="row" style="margin:16px 0">
  <div class="col-lg-3">
    <div class="panel panel-default text-center" style="padding:10px">
      <div style="font-size:22px; font-weight:bold"><?php echo $total_productos; ?></div>
      <small class="text-muted">Productos</small>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="panel panel-danger text-center" style="padding:10px">
      <div style="font-size:22px; font-weight:bold; color:#c0392b"><?php echo $sin_stock; ?></div>
      <small class="text-muted">Sin stock</small>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="panel panel-warning text-center" style="padding:10px">
      <div style="font-size:22px; font-weight:bold; color:#e67e22"><?php echo $stock_bajo; ?></div>
      <small class="text-muted">Stock bajo (≤<?php echo $stock_minimo; ?>)</small>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="panel panel-default text-center" style="padding:10px">
      <div style="font-size:18px; font-weight:bold">$<?php echo number_format($total_valor_costo, 2, ',', '.'); ?></div>
      <small class="text-muted">Valor stock a costo</small>
    </div>
  </div>
</div>

<!-- Tabla -->
<div class="panel panel-default">
  <div class="panel-body" style="overflow-y:auto; max-height:400px; padding:0">
    <table class="table table-striped table-hover table-condensed" style="margin:0">
      <thead>
        <tr class="active">
          <th>Producto</th>
          <th>Rubro</th>
          <th style="text-align:right">Stock</th>
          <th style="text-align:right">$ Costo</th>
          <th style="text-align:right">$ Venta</th>
          <th style="text-align:right">Margen %</th>
          <th style="text-align:center">Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($datos as $d):
          [$clase, $etiqueta] = stock_clase($d['cantidad'], $stock_minimo);
          $margen = $d['venta'] > 0
              ? round((($d['venta'] - $d['costo']) / $d['venta']) * 100, 1)
              : 0;
        ?>
        <tr>
          <td><?php echo htmlspecialchars($d['producto']); ?></td>
          <td><?php echo htmlspecialchars($d['rubro']); ?></td>
          <td style="text-align:right"><?php echo number_format($d['cantidad'], 2, ',', '.'); ?></td>
          <td style="text-align:right">$<?php echo number_format($d['costo'], 2, ',', '.'); ?></td>
          <td style="text-align:right">$<?php echo number_format($d['venta'], 2, ',', '.'); ?></td>
          <td style="text-align:right"><?php echo $margen; ?>%</td>
          <td style="text-align:center">
            <span class="label label-<?php echo $clase; ?>"><?php echo $etiqueta; ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>document.getElementById("botonExcel1").style.visibility = "visible";</script>

<?php endif; ?>

<form method="post" id="form_export">
  <input type="hidden" name="dato_producto" value="<?php echo $producto; ?>">
  <input type="hidden" name="dato_rubro"    value="<?php echo $rubro; ?>">
  <input type="hidden" name="dato_estado"   value="<?php echo htmlspecialchars($estado); ?>">
  <div class="modal-footer">
    <div align="right">
      <button type="submit" class="btn btn-info" id="botonExcel1" name="export_data" value="1">
        <span class="glyphicon glyphicon-save"></span> Descargar CSV
      </button>
    </div>
  </div>
</form>
