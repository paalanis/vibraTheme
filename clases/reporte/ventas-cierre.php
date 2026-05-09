<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../../index.php"); }
include '../../conexion/conexion.php';

$cierre = (int)($_REQUEST['cierre'] ?? 0);
$origen = in_array($_REQUEST['origen'] ?? '', ['dia','acumulado']) ? $_REQUEST['origen'] : 'dia';

if ($cierre <= 0) { echo '<p class="text-danger">Cierre inválido.</p>'; exit(); }

$tabla = ($origen === 'dia') ? 'tb_ventas' : 'tb_ventas_acumulado';
$filtro_estado = ($origen === 'dia') ? "AND tv.estado = '1'" : '';

$stmt = mysqli_prepare($conexion,
    "SELECT
        tv.numero_factura                            AS ticket,
        CONCAT(tc.apellido, ' ', tc.nombre)         AS cliente,
        DATE_FORMAT(MIN(tv.fecha), '%d/%m/%Y %H:%i') AS fecha,
        COUNT(tv.id_productos)                      AS items,
        ROUND(SUM(tv.subtotal), 2)                  AS total
     FROM $tabla tv
     INNER JOIN tb_clientes tc ON tc.id_clientes = tv.id_clientes
     WHERE tv.id_cierre = ? $filtro_estado
     GROUP BY tv.numero_factura, tc.apellido, tc.nombre
     ORDER BY tv.numero_factura ASC"
);
mysqli_stmt_bind_param($stmt, 'i', $cierre);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_ticket, $r_cliente, $r_fecha, $r_items, $r_total);
$datos = [];
while (mysqli_stmt_fetch($stmt)) {
    $datos[] = ['ticket' => $r_ticket, 'cliente' => $r_cliente, 'fecha' => $r_fecha, 'items' => $r_items, 'total' => $r_total];
}
mysqli_stmt_close($stmt);

$total_cierre = array_sum(array_column($datos, 'total'));
$label = ($origen === 'dia') ? 'Caja abierta' : 'Caja cerrada';
?>

<div class="panel panel-default">
  <div class="panel-heading">
    <button type="button" class="btn btn-default btn-xs" onclick="reporte('ventas')" style="margin-right:8px;">
      <span class="glyphicon glyphicon-arrow-left"></span> Volver
    </button>
    <strong><?php echo $label; ?> — Cierre N° <?php echo $cierre; ?></strong>
  </div>

  <div class="panel-body" style="max-height:380px; overflow-y:auto; padding:0;">
    <?php if (empty($datos)): ?>
      <p class="text-muted text-center" style="padding:24px 0;">No hay tickets en este cierre.</p>
    <?php else: ?>
    <table class="table table-striped table-hover table-condensed" style="margin:0;">
      <thead>
        <tr class="active">
          <th>N° Ticket</th>
          <th>Cliente</th>
          <th>Fecha</th>
          <th class="text-center">Items</th>
          <th class="text-right">Total</th>
          <th class="text-center">Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($datos as $d): ?>
        <tr>
          <td><?php echo (int)$d['ticket']; ?></td>
          <td><?php echo htmlspecialchars($d['cliente']); ?></td>
          <td><?php echo htmlspecialchars($d['fecha']); ?></td>
          <td class="text-center"><?php echo (int)$d['items']; ?></td>
          <td class="text-right"><strong>$ <?php echo number_format($d['total'], 2, ',', '.'); ?></strong></td>
          <td class="text-center">
            <button type="button" class="btn btn-success btn-xs"
                    onclick="verTicket(<?php echo (int)$d['ticket']; ?>,<?php echo $cierre; ?>,'<?php echo $origen; ?>')">
              <span class="glyphicon glyphicon-search"></span> Ver
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="active">
          <td colspan="4"><strong>Total cierre</strong></td>
          <td class="text-right"><strong>$ <?php echo number_format($total_cierre, 2, ',', '.'); ?></strong></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    <?php endif; ?>
  </div>
</div>

<div id="div_ticket_detalle"></div>

<script>
function verTicket(ticket, cierre, origen) {
    $('#div_ticket_detalle').html('<div class="text-center"><div class="loadingsm"></div></div>');
    $('#div_ticket_detalle').load('clases/reporte/ventas-ticket.php', { ticket: ticket, cierre: cierre, origen: origen });
    setTimeout(function(){
        var top = $('#div_ticket_detalle').offset();
        if (top) $('html,body').animate({ scrollTop: top.top - 20 }, 300);
    }, 300);
}
</script>
