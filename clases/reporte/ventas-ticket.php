<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../../index.php"); }
include '../../conexion/conexion.php';

$ticket = (int)($_REQUEST['ticket'] ?? 0);
$cierre = (int)($_REQUEST['cierre'] ?? 0);
$origen = in_array($_REQUEST['origen'] ?? '', ['dia','acumulado']) ? $_REQUEST['origen'] : 'dia';

if ($ticket <= 0 || $cierre <= 0) { echo '<p class="text-danger">Datos inválidos.</p>'; exit(); }

$tabla = ($origen === 'dia') ? 'tb_ventas' : 'tb_ventas_acumulado';
$filtro_estado = ($origen === 'dia') ? "AND tv.estado = '1'" : '';

$stmt = mysqli_prepare($conexion,
    "SELECT
        tp.codigo                                AS codigo,
        tp.nombre                                AS producto,
        tv.cantidad                              AS cantidad,
        tv.precio_venta                          AS precio,
        tv.subtotal                              AS subtotal,
        CONCAT(tc.apellido, ' ', tc.nombre)      AS cliente,
        DATE_FORMAT(tv.fecha, '%d/%m/%Y %H:%i')  AS fecha,
        COALESCE(tcv.nombre, '—')                AS condicion
     FROM $tabla tv
     INNER JOIN tb_productos tp ON tp.id_productos = tv.id_productos
     INNER JOIN tb_clientes  tc ON tc.id_clientes  = tv.id_clientes
     LEFT  JOIN tb_condicion_venta tcv ON tcv.id_condicion_venta = tv.id_condicion_venta
     WHERE tv.numero_factura = ? AND tv.id_cierre = ? $filtro_estado
     ORDER BY tv.id_ventas ASC"
);
mysqli_stmt_bind_param($stmt, 'ii', $ticket, $cierre);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_codigo, $r_producto, $r_cantidad, $r_precio, $r_subtotal, $r_cliente, $r_fecha, $r_condicion);
$datos = [];
while (mysqli_stmt_fetch($stmt)) {
    $datos[] = [
        'codigo'    => $r_codigo,
        'producto'  => $r_producto,
        'cantidad'  => $r_cantidad,
        'precio'    => $r_precio,
        'subtotal'  => $r_subtotal,
        'cliente'   => $r_cliente,
        'fecha'     => $r_fecha,
        'condicion' => $r_condicion,
    ];
}
mysqli_stmt_close($stmt);

$total = array_sum(array_column($datos, 'subtotal'));
$cliente   = !empty($datos) ? $datos[0]['cliente']   : '—';
$fecha     = !empty($datos) ? $datos[0]['fecha']      : '—';
$condicion = !empty($datos) ? $datos[0]['condicion']  : '—';
?>

<div class="panel panel-success" style="margin-top:12px;">
  <div class="panel-heading">
    <button class="btn btn-default btn-xs" onclick="$('#div_ticket_detalle').html('')" style="margin-right:8px;">
      <span class="glyphicon glyphicon-remove"></span> Cerrar
    </button>
    <strong>Ticket N° <?php echo $ticket; ?></strong>
    &mdash; <?php echo htmlspecialchars($cliente); ?>
    &mdash; <?php echo htmlspecialchars($fecha); ?>
    <span class="label label-default" style="margin-left:6px;"><?php echo htmlspecialchars($condicion); ?></span>
  </div>

  <div class="panel-body" style="padding:0;">
    <?php if (empty($datos)): ?>
      <p class="text-muted text-center" style="padding:16px 0;">Sin productos.</p>
    <?php else: ?>
    <table class="table table-condensed table-hover" style="margin:0;">
      <thead>
        <tr class="active">
          <th>Código</th>
          <th>Producto</th>
          <th class="text-center">Cantidad</th>
          <th class="text-right">Precio</th>
          <th class="text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($datos as $d): ?>
        <tr>
          <td><?php echo htmlspecialchars($d['codigo']); ?></td>
          <td><?php echo htmlspecialchars($d['producto']); ?></td>
          <td class="text-center"><?php echo round($d['cantidad'], 3); ?></td>
          <td class="text-right">$ <?php echo number_format($d['precio'], 2, ',', '.'); ?></td>
          <td class="text-right"><strong>$ <?php echo number_format($d['subtotal'], 2, ',', '.'); ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="active">
          <td colspan="4" class="text-right"><strong>Total</strong></td>
          <td class="text-right"><strong>$ <?php echo number_format($total, 2, ',', '.'); ?></strong></td>
        </tr>
      </tfoot>
    </table>
    <?php endif; ?>
  </div>
</div>
