<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

$remito    = $_REQUEST['remito']    ?? '';
$proveedor = (int)($_REQUEST['proveedor'] ?? 0);

// Normalizar sucursal
$parts  = explode('-', $remito, 2);
$remito = (count($parts) === 2) ? ((int)$parts[0]) . '-' . $parts[1] : $remito;

$stmt = mysqli_prepare($conexion,
    "SELECT
       r.id_remitos           AS id,
       r.numero               AS remito,
       p.nombre               AS producto,
       r.cantidad             AS cantidad,
       r.precio_costo         AS precio_costo,
       r.margen_ganancia      AS margen_ganancia,
       ROUND(COALESCE(r.precio_costo,0) *
             (1 + COALESCE(r.margen_ganancia,0) / 100), 2) AS precio_venta_calc
     FROM tb_remitos r
     INNER JOIN tb_productos p ON p.id_productos = r.id_productos
     WHERE r.estado = '0' AND r.numero = ? AND r.id_proveedores = ?"
);
mysqli_stmt_bind_param($stmt, 'si', $remito, $proveedor);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_remito, $r_producto, $r_cantidad,
                               $r_costo, $r_margen, $r_pventa_calc);

$rows    = [];
$cuenta  = 0;
while (mysqli_stmt_fetch($stmt)) {
    $rows[] = [
        'id'          => $r_id,
        'remito'      => mb_convert_encoding($r_remito,   'UTF-8', 'ISO-8859-1'),
        'producto'    => mb_convert_encoding($r_producto, 'UTF-8', 'ISO-8859-1'),
        'cantidad'    => $r_cantidad,
        'costo'       => $r_costo,
        'margen'      => $r_margen,
        'pventa_calc' => $r_pventa_calc,
    ];
    $cuenta++;
}
mysqli_stmt_close($stmt);

$csrf = csrf_token();
?>

<div class="panel panel-default">
<div class="panel-body" id="Panel1" style="height:310px; overflow-y:auto">
<table class="table table-striped table-hover table-condensed">
  <thead>
    <tr class="active">
      <th>Producto</th>
      <th style="text-align:right">Cant.</th>
      <th style="text-align:right">$ Costo</th>
      <th style="text-align:right">Margen %</th>
      <th style="text-align:right">$ Venta</th>
      <th>Eliminar</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($cuenta > 0): ?>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['producto']); ?></td>
        <td style="text-align:right"><?php echo number_format((float)$row['cantidad'], 2, ',', '.'); ?></td>
        <td style="text-align:right">
          <?php echo $row['costo'] !== null ? '$' . number_format((float)$row['costo'], 2, ',', '.') : '—'; ?>
        </td>
        <td style="text-align:right">
          <?php echo $row['margen'] !== null ? number_format((float)$row['margen'], 2, ',', '.') . '%' : '—'; ?>
        </td>
        <td style="text-align:right">
          <?php echo $row['pventa_calc'] > 0 ? '$' . number_format((float)$row['pventa_calc'], 2, ',', '.') : '—'; ?>
        </td>
        <td>
          <button type="button" class="ver_modal-danger btn btn-xs btn-danger"
                  data-id="<?php echo $row['id']; ?>"
                  data-proveedor="<?php echo $proveedor; ?>"
                  data-remito="<?php echo htmlspecialchars($row['remito']); ?>">
            <span class="glyphicon glyphicon-remove"></span>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>

      <script>
        $('#dato_proveedor').attr('disabled', true);
        $('#dato_sucursal').attr('disabled', true);
        $('#dato_remito').attr('disabled', true);
        $('#boton_guardar').attr('disabled', false);
        $('#boton_producto').attr('disabled', true);
      </script>

    <?php else: ?>
      <tr><td colspan="6">No hay productos cargados.</td></tr>
      <script>
        $('#dato_proveedor').attr('disabled', false);
        $('#dato_sucursal').attr('disabled', false);
        $('#dato_remito').attr('disabled', false);
        $('#boton_guardar').attr('disabled', true);
        $('#boton_producto').attr('disabled', true);
      </script>
    <?php endif; ?>
  </tbody>
</table>
</div>
</div>

<script>
(function() {
  var csrfToken = '<?php echo $csrf; ?>';

  $('.ver_modal-danger').click(function() {
    var id_remito  = $(this).data('id');
    var proveedor  = $(this).data('proveedor');
    var remito     = $(this).data('remito');

    $('#div_remitos').html('<div class="text-center"><div class="loadingsm"></div></div>');
    $.ajax({
      url     : "clases/elimina/remito.php",
      data    : {id: id_remito, csrf_token: csrfToken},
      dataType: "json",
      type    : "post",
      success : function(data) {
        if (data.success === 'true') {
          $('#div_remitos').load('clases/nuevo/remitoinsumo.php',
            {remito: remito, proveedor: proveedor});
          setTimeout(function() { $('.alert').alert('close'); }, 2000);
        } else {
          $('#div_remitos').html(
            '<div class="alert alert-danger">Error, reintente.</div>');
        }
      }
    });
  });
})();
</script>
