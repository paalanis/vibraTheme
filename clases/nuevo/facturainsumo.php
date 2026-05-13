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
        tv.id_ventas        AS id,
        tp.nombre           AS producto,
        tp.codigo           AS codigo,
        tv.precio_lista     AS precio_lista,
        tv.descuento_pct    AS descuento_pct,
        tv.descuento_monto  AS descuento_monto,
        tv.precio_venta     AS precio,
        tv.cantidad         AS cantidad,
        tv.subtotal         AS subtotal,
        d.nombre            AS desc_nombre
     FROM tb_ventas tv
     INNER JOIN tb_productos tp ON tp.id_productos = tv.id_productos
     LEFT JOIN tb_descuentos  d ON  d.id_descuento  = tv.id_descuento
     WHERE tv.numero_factura = ? AND tv.id_cierre = ? AND tv.estado = '0'"
);
mysqli_stmt_bind_param($stmt, 'ii', $factura, $cierre);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,
    $r_id, $r_producto, $r_codigo,
    $r_precio_lista, $r_desc_pct, $r_desc_monto,
    $r_precio, $r_cantidad, $r_subtotal, $r_desc_nombre
);

$filas         = 0;
$total_fc      = 0;
$total_ahorro  = 0;
$rows          = [];
$hay_descuento = false;

while (mysqli_stmt_fetch($stmt)) {
    $precio_lista = ($r_precio_lista > 0) ? (float)$r_precio_lista : (float)$r_precio;
    $desc_pct     = (float)$r_desc_pct;
    $desc_monto   = (float)$r_desc_monto;
    if ($desc_pct > 0) $hay_descuento = true;

    $rows[] = [
        'id'           => $r_id,
        'producto'     => $r_producto,
        'codigo'       => $r_codigo,
        'precio_lista' => $precio_lista,
        'desc_pct'     => $desc_pct,
        'desc_monto'   => $desc_monto,
        'precio'       => (float)$r_precio,
        'cantidad'     => (float)$r_cantidad,
        'subtotal'     => (float)$r_subtotal,
        'desc_nombre'  => $r_desc_nombre ?? '',
    ];
    $total_fc     += (float)$r_subtotal;
    $total_ahorro += $desc_monto;
    $filas++;
}
mysqli_stmt_close($stmt);
?>

<div class="panel-body" id="Panel1" style="height:200px">
<table class="table table-striped table-hover table-condensed">
  <thead>
    <tr class="active">
      <th>Código</th>
      <th>Cant.</th>
      <th>Descripción</th>
      <th>Lista</th>
      <?php if ($hay_descuento): ?>
      <th style="color:#d9534f;">Dto.</th>
      <?php endif; ?>
      <th>Precio</th>
      <th>Subtotal</th>
      <th>&#x2715;</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $num => $d): ?>
    <tr tabindex="<?php echo $num + 1; ?>">
      <td style="font-family:monospace;font-size:11px;"><?php echo htmlspecialchars($d['codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?php echo number_format($d['cantidad'], 2, ',', '.'); ?></td>
      <td><?php echo htmlspecialchars($d['producto'], ENT_QUOTES, 'UTF-8'); ?></td>

      <?php if ($d['desc_pct'] > 0): ?>
      <td style="text-decoration:line-through; color:#999; font-size:11px;">
          $<?php echo number_format($d['precio_lista'], 2, ',', '.'); ?>
      </td>
      <?php else: ?>
      <td style="color:#999; font-size:11px;">
          $<?php echo number_format($d['precio_lista'], 2, ',', '.'); ?>
      </td>
      <?php endif; ?>

      <?php if ($hay_descuento): ?>
      <td style="color:#d9534f; white-space:nowrap;">
          <?php if ($d['desc_pct'] > 0): ?>
          <span title="<?php echo htmlspecialchars($d['desc_nombre'], ENT_QUOTES, 'UTF-8'); ?>">
              −<?php echo number_format($d['desc_pct'], 1); ?>%
              <br><small>−$<?php echo number_format($d['desc_monto'], 2, ',', '.'); ?></small>
          </span>
          <?php else: ?>
          <span class="text-muted">—</span>
          <?php endif; ?>
      </td>
      <?php endif; ?>

      <td><strong>$<?php echo number_format($d['precio'], 2, ',', '.'); ?></strong></td>
      <td>$<?php echo number_format($d['subtotal'], 2, ',', '.'); ?></td>
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
  $('#ahorro').val('<?php echo $total_ahorro > 0 ? number_format($total_ahorro, 2, ',', '.') : '0,00'; ?>');
  <?php if ($total_ahorro > 0): ?>
  $('#lbl_ahorro').show();
  <?php else: ?>
  $('#lbl_ahorro').hide();
  <?php endif; ?>
  $('#dato_condicion').attr('disabled', false);
</script>
<?php else: ?>
<script>
  $('#dato_cliente').attr('disabled', false);
  $('#subtotal').val(0);
  $('#ahorro').val('0,00');
  $('#lbl_ahorro').hide();
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

$(document).off('click', '.ver_modal-danger').on('click', '.ver_modal-danger', function () {
  var id_producto = $(this).data('id');
  var factura     = $(this).data('factura');
  var cierre      = $(this).data('cierre');

  $('#div_modal_autoriza').remove();
  $.get('clases/nuevo/autoriza.php',
    { id_producto: id_producto, cierre: cierre, factura: factura },
    function (html) {
      $('body').append('<div id="div_modal_autoriza">' + html + '</div>');
    }
  );
});
</script>
