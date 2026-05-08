<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}
date_default_timezone_set("America/Argentina/Mendoza");
$hoy = date("Y-m-d");

$desde  = $_REQUEST['dato_desde']  ?? $hoy;
$hasta  = $_REQUEST['dato_hasta']  ?? $hoy;
$origen = $_REQUEST['dato_origen'] ?? 'dia';

// Sanitizar fechas
$desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : $hoy;
$hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : $hoy;
$origen = in_array($origen, ['dia', 'acumulado']) ? $origen : 'dia';

// Export Excel
if (isset($_POST['export_data'])) {
    $tabla = ($origen === 'dia') ? 'tb_ventas' : 'tb_ventas_acumulado';
    $condicion_estado = ($origen === 'dia') ? "AND tv.estado = '1'" : '';
    $sql_exp = "SELECT
        tv.numero_factura AS Ticket,
        CONCAT(tc.apellido, ' ', tc.nombre) AS Cliente,
        tp.nombre AS Producto,
        tv.cantidad AS Cantidad,
        tv.precio_venta AS Precio,
        tv.subtotal AS Subtotal,
        DATE_FORMAT(tv.fecha, '%d/%m/%Y %H:%i') AS Fecha
        FROM $tabla tv
        INNER JOIN tb_clientes tc ON tc.id_clientes = tv.id_clientes
        INNER JOIN tb_productos tp ON tp.id_productos = tv.id_productos
        WHERE tv.fecha BETWEEN '$desde 00:00:00' AND '$hasta 23:59:59'
        $condicion_estado
        ORDER BY tv.fecha DESC";
    $rs = mysqli_query($conexion, $sql_exp);
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=ventas_$desde\_$hasta.xls");
    $first = true;
    while ($row = mysqli_fetch_assoc($rs)) {
        if ($first) { echo implode("\t", array_keys($row)) . "\n"; $first = false; }
        echo implode("\t", array_values($row)) . "\n";
    }
    exit;
}

// ── Query resumen por cierre ──────────────────────────────────────────────────
if ($origen === 'dia') {
    $stmt = mysqli_prepare($conexion,
        "SELECT
            tv.id_cierre                              AS cierre,
            DATE_FORMAT(tc.fecha_apertura, '%d/%m/%Y %H:%i') AS apertura,
            COUNT(DISTINCT tv.numero_factura)         AS tickets,
            ROUND(SUM(tv.subtotal), 2)                AS total
         FROM tb_ventas tv
         LEFT JOIN tb_cierres tc ON tc.id_cierre = tv.id_cierre
         WHERE tv.fecha BETWEEN ? AND ?
           AND tv.estado = '1'
         GROUP BY tv.id_cierre
         ORDER BY tv.id_cierre DESC"
    );
    $desde_dt = $desde . ' 00:00:00';
    $hasta_dt = $hasta . ' 23:59:59';
    mysqli_stmt_bind_param($stmt, 'ss', $desde_dt, $hasta_dt);
} else {
    $stmt = mysqli_prepare($conexion,
        "SELECT
            id_cierre                              AS cierre,
            NULL                                   AS apertura,
            COUNT(DISTINCT numero_factura)         AS tickets,
            ROUND(SUM(subtotal), 2)                AS total
         FROM tb_ventas_acumulado
         WHERE fecha BETWEEN ? AND ?
         GROUP BY id_cierre
         ORDER BY id_cierre DESC"
    );
    $desde_dt = $desde . ' 00:00:00';
    $hasta_dt = $hasta . ' 23:59:59';
    mysqli_stmt_bind_param($stmt, 'ss', $desde_dt, $hasta_dt);
}

mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_cierre, $r_apertura, $r_tickets, $r_total);
$datos = [];
while (mysqli_stmt_fetch($stmt)) {
    $datos[] = [
        'cierre'   => $r_cierre,
        'apertura' => $r_apertura,
        'tickets'  => $r_tickets,
        'total'    => $r_total,
    ];
}
mysqli_stmt_close($stmt);

$total_general = array_sum(array_column($datos, 'total'));
$label_origen  = ($origen === 'dia') ? 'Ventas del día' : 'Ventas acumuladas';
?>

<div class="panel panel-default">
  <div class="panel-heading">
    <strong><?php echo htmlspecialchars($label_origen); ?></strong>
    &nbsp;&mdash;&nbsp;
    <?php echo date('d/m/Y', strtotime($desde)); ?>
    <?php if ($desde !== $hasta): ?>
      al <?php echo date('d/m/Y', strtotime($hasta)); ?>
    <?php endif; ?>
  </div>

  <div class="panel-body" style="max-height:400px; overflow-y:auto;">
    <?php if (empty($datos)): ?>
      <p class="text-muted text-center" style="padding:20px 0;">No hay registros para el período seleccionado.</p>
    <?php else: ?>
    <table class="table table-striped table-hover table-condensed" style="margin-bottom:0;">
      <thead>
        <tr class="active">
          <th>N° Cierre</th>
          <?php if ($origen === 'dia'): ?>
          <th>Apertura</th>
          <?php endif; ?>
          <th class="text-center">Tickets</th>
          <th class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($datos as $d): ?>
        <tr>
          <td><?php echo (int)$d['cierre']; ?></td>
          <?php if ($origen === 'dia'): ?>
          <td><?php echo htmlspecialchars($d['apertura'] ?? '—'); ?></td>
          <?php endif; ?>
          <td class="text-center"><?php echo (int)$d['tickets']; ?></td>
          <td class="text-right"><strong>$ <?php echo number_format($d['total'], 2, ',', '.'); ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="active">
          <td colspan="<?php echo ($origen === 'dia') ? 3 : 2; ?>"><strong>Total del período</strong></td>
          <td class="text-right"><strong>$ <?php echo number_format($total_general, 2, ',', '.'); ?></strong></td>
        </tr>
      </tfoot>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($datos)): ?>
<form method="post" id="Exportar_excel">
  <input type="hidden" name="dato_desde"  value="<?php echo htmlspecialchars($desde); ?>">
  <input type="hidden" name="dato_hasta"  value="<?php echo htmlspecialchars($hasta); ?>">
  <input type="hidden" name="dato_origen" value="<?php echo htmlspecialchars($origen); ?>">
  <div class="modal-footer">
    <div class="col-lg-12">
      <div align="right">
        <button type="submit" name="export_data" value="1" class="btn btn-info" id="botonExcel1">
          <span class="glyphicon glyphicon-save"></span> Descargar Excel
        </button>
      </div>
    </div>
  </div>
</form>
<?php endif; ?>
