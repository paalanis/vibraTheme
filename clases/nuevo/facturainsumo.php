<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo '<p class="text-danger">Error de conexión.</p>'; exit();
}

$factura = (int)($_REQUEST['factura'] ?? 0);
$cierre  = (int)($_REQUEST['cierre']  ?? 0);

$stmt = mysqli_prepare($conexion,
    "SELECT
        tv.id_ventas   AS id,
        tp.nombre      AS producto,
        tp.codigo      AS codigo,
        tv.precio_venta AS precio,
        tv.cantidad    AS cantidad,
        tv.subtotal    AS subtotal
     FROM tb_ventas tv
     INNER JOIN tb_productos tp ON tp.id_productos = tv.id_productos
     WHERE tv.numero_factura = ? AND tv.id_cierre = ? AND tv.estado = '0'"
);
mysqli_stmt_bind_param($stmt, 'ii', $factura, $cierre);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_producto, $r_codigo, $r_precio, $r_cantidad, $r_subtotal);

$filas     = 0;
$total_fc  = 0;
$rows      = [];
while (mysqli_stmt_fetch($stmt)) {
    $rows[] = [
        'id'       => $r_id,
        'producto' => $r_producto,
        'codigo'   => $r_codigo,
        'precio'   => $r_precio,
        'cantidad' => $r_cantidad,
        'subtotal' => $r_subtotal,
    ];
    $total_fc += $r_subtotal;
    $filas++;
}
mysqli_stmt_close($stmt);
?>

<div class="panel-body" id="Panel1" style="height:200px">
<table class="table table-striped table-hover">
  <thead>
    <tr class="active">
      <th>Código</th>
      <th>Cantidad</th>
      <th>Descripcion</th>
      <th>Precio</th>
      <th>Subtotal</th>
      <th>Eliminar</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $num => $d): ?>
    <tr tabindex="<?php echo $num + 1; ?>">
      <td><?php echo htmlspecialchars($d['codigo'],   ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?php echo round($d['cantidad'], 2); ?></td>
      <td><?php echo htmlspecialchars($d['producto'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?php echo round($d['precio'],   2); ?></td>
      <td><?php echo round($d['subtotal'], 2); ?></td>
      <td>
        <button type="button"
                class="ver_modal-danger btn btn-danger btn-xs"
                data-id="<?php echo (int)$d['id']; ?>"
                data-factura="<?php echo $factura; ?>"
                data-cierre="<?php echo $cierre; ?>">
          <span class="glyphicon glyphicon-remove"></span>
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php if ($filas > 0): ?>
<script>
  $('#dato_cliente').attr('disabled', true);
  $('#subtotal').val(<?php echo round($total_fc, 2); ?>);
  $('#dato_condicion').attr('disabled', false);
</script>
<?php else: ?>
<script>
  $('#dato_cliente').attr('disabled', false);
  $('#subtotal').val(0);
  $('#dato_condicion').attr('disabled', true);
  $('#dato_codigo').focus();
</script>
<?php endif; ?>

<script>
$(document).ready(function () {
  $('#dato_condicion').val('');
  $('#total').val(0);
  $('#dato_monto').val('').attr('disabled', true);
  $('#dato_cupon').val('').attr('disabled', true);
  $('#dato_vuelto').val(0);
  $('#div_producto').html('');
  $('#boton_guardar').attr('disabled', true);
  $('#Panel1').animate({ scrollTop: $(document).height() }, 600);
});

// BUG CORREGIDO: ya no carga en #panel_inicio (que reemplazaba el formulario
// de venta completo). Ahora inyecta el modal en <body> como overlay.
$(document).off('click', '.ver_modal-danger').on('click', '.ver_modal-danger', function () {
  var id_producto = $(this).data('id');
  var factura     = $(this).data('factura');
  var cierre      = $(this).data('cierre');

  $('#div_modal_autoriza').remove(); // limpiar modal anterior si existía

  $.get('clases/nuevo/autoriza.php',
    { id_producto: id_producto, cierre: cierre, factura: factura },
    function (html) {
      $('body').append('<div id="div_modal_autoriza">' + html + '</div>');
    }
  );
});
</script>
