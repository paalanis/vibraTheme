<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../../index.php"); }
include '../../conexion/conexion.php';
date_default_timezone_set("America/Argentina/Mendoza");
$hoy = date("Y-m-d");

$desde  = $_REQUEST['dato_desde']  ?? $hoy;
$hasta  = $_REQUEST['dato_hasta']  ?? $hoy;
$origen = $_REQUEST['dato_origen'] ?? 'dia';

$desde  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)  ? $desde  : $hoy;
$hasta  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)  ? $hasta  : $hoy;
$origen = in_array($origen, ['dia', 'acumulado']) ? $origen : 'dia';

$desde_dt = $desde . ' 00:00:00';
$hasta_dt = $hasta . ' 23:59:59';

if ($origen === 'dia') {
    $stmt = mysqli_prepare($conexion,
        "SELECT
            tv.id_cierre                                     AS cierre,
            DATE_FORMAT(tc.fecha_apertura,'%d/%m/%Y %H:%i') AS apertura,
            COUNT(DISTINCT tv.numero_factura)                AS tickets,
            ROUND(SUM(tv.subtotal), 2)                       AS total
         FROM tb_ventas tv
         INNER JOIN tb_cierres tc ON tc.id_cierre = tv.id_cierre AND tc.estado = '0'
         WHERE tv.fecha BETWEEN ? AND ? AND tv.estado = '1'
         GROUP BY tv.id_cierre ORDER BY tv.id_cierre DESC"
    );
} else {
    $stmt = mysqli_prepare($conexion,
        "SELECT
            COALESCE(va.id_cierre, 0)         AS cierre,
            NULL                              AS apertura,
            COUNT(DISTINCT va.numero_factura) AS tickets,
            ROUND(SUM(va.subtotal), 2)        AS total
         FROM tb_ventas_acumulado va
         WHERE va.fecha BETWEEN ? AND ?
         GROUP BY va.id_cierre ORDER BY va.id_cierre DESC"
    );
}

$datos = [];
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ss', $desde_dt, $hasta_dt);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $r_cierre, $r_apertura, $r_tickets, $r_total);
    while (mysqli_stmt_fetch($stmt)) {
        $datos[] = ['cierre' => $r_cierre, 'apertura' => $r_apertura, 'tickets' => $r_tickets, 'total' => $r_total];
    }
    mysqli_stmt_close($stmt);
}

$total_general = array_sum(array_column($datos, 'total'));
$label_origen  = ($origen === 'dia') ? 'Ventas del día — caja abierta' : 'Ventas acumuladas — cajas cerradas';
?>

<div class="panel panel-default">
  <div class="panel-heading">
    <strong><?php echo htmlspecialchars($label_origen); ?></strong>
    &mdash;
    <?php echo date('d/m/Y', strtotime($desde));
    if ($desde !== $hasta) echo ' al ' . date('d/m/Y', strtotime($hasta)); ?>
  </div>

  <div class="panel-body" style="max-height:420px; overflow-y:auto; padding:0;">
    <?php if (empty($datos)): ?>
      <p class="text-muted text-center" style="padding:24px 0;">No hay registros para el período seleccionado.</p>
    <?php else: ?>
    <table class="table table-striped table-hover table-condensed" style="margin:0;">
      <thead>
        <tr class="active">
          <th>N° Cierre</th>
          <?php if ($origen === 'dia'): ?><th>Apertura</th><?php endif; ?>
          <th class="text-center">Tickets</th>
          <th class="text-right">Total</th>
          <th class="text-center">Detalle</th>
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
          <td class="text-center">
            <button class="btn btn-info btn-xs"
                    onclick="verCierre(<?php echo (int)$d['cierre']; ?>,'<?php echo $origen; ?>')">
              <span class="glyphicon glyphicon-list"></span> Ver tickets
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="active">
          <td colspan="<?php echo ($origen === 'dia') ? 4 : 3; ?>"><strong>Total del período</strong></td>
          <td class="text-right"><strong>$ <?php echo number_format($total_general, 2, ',', '.'); ?></strong></td>
          <td></td>
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
    <div class="col-lg-12 text-right">
      <button type="submit" name="export_data" value="1" class="btn btn-info" id="botonExcel1">
        <span class="glyphicon glyphicon-save"></span> Descargar Excel
      </button>
    </div>
  </div>
</form>
<?php endif; ?>

<script>
function verCierre(cierre, origen) {
    $('#div_reporte').html('<div class="text-center"><div class="loadingsm"></div></div>');
    $('#div_reporte').load('clases/reporte/ventas-cierre.php', { cierre: cierre, origen: origen });
}
</script>
