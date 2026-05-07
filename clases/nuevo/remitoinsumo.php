<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
if (mysqli_connect_errno()) {
    printf("La conexión con el servidor de base de datos falló: %s\n", mysqli_connect_error());
    exit();
}

$remito    = $_REQUEST['remito']    ?? '';
$proveedor = (int)($_REQUEST['proveedor'] ?? 0);

// Normalizar sucursal: el JS envía "0001-00000001" pero la BD tiene "1-00000001"
$parts  = explode('-', $remito, 2);
$remito = (count($parts) === 2) ? ((int)$parts[0]) . '-' . $parts[1] : $remito;

$stmt = mysqli_prepare($conexion,
    "SELECT
       tb_remitos.id_remitos  AS id,
       tb_remitos.numero      AS remito,
       tb_productos.nombre    AS producto,
       tb_remitos.cantidad    AS cantidad,
       tb_remitos.precio_costo AS precio_costo
     FROM tb_remitos
     INNER JOIN tb_productos ON tb_productos.id_productos = tb_remitos.id_productos
     WHERE tb_remitos.estado = '0' AND tb_remitos.numero = ? AND tb_remitos.id_proveedores = ?"
);
mysqli_stmt_bind_param($stmt, 'si', $remito, $proveedor);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_remito, $r_producto, $r_cantidad, $r_precio_costo);

$rows     = [];
$cantidad = 0;
while (mysqli_stmt_fetch($stmt)) {
    $rows[] = [
        'id'           => $r_id,
        'remito'       => mb_convert_encoding($r_remito,    'UTF-8', 'ISO-8859-1'),
        'producto'     => mb_convert_encoding($r_producto,  'UTF-8', 'ISO-8859-1'),
        'cantidad'     => mb_convert_encoding((string)$r_cantidad,    'UTF-8', 'ISO-8859-1'),
        'precio_costo' => $r_precio_costo,
    ];
    $cantidad++;
}
mysqli_stmt_close($stmt);

// Token CSRF para el botón de eliminar (llamada POST desde JS)
$csrf = csrf_token();
?>

<div class="panel panel-default">
<div class="panel-body" id="Panel1" style="height:310px">
<table class="table table-striped table-hover">
  <thead>
    <tr class="active">
      <th>Producto</th>
      <th>Cantidad</th>
      <th>Precio costo</th>
      <th>Eliminar</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($cantidad > 0): ?>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php echo $row['producto']; ?></td>
        <td><?php echo $row['cantidad']; ?></td>
        <td><?php echo $row['precio_costo'] !== null ? '$' . number_format((float)$row['precio_costo'], 2) : '—'; ?></td>
        <td>
          <button type="button" class="ver_modal ver_modal-danger ver_modal-xs"
                  data-id="<?php echo $row['id']; ?>"
                  data-proveedor="<?php echo $proveedor; ?>"
                  data-remito="<?php echo htmlspecialchars($row['remito']); ?>">
            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>

      <script type="text/javascript">
        $('#dato_proveedor').attr('disabled', true);
        $('#dato_sucursal').attr('disabled', true);
        $('#dato_remito').attr('disabled', true);
        $('#boton_guardar').attr('disabled', false);
        $('#boton_producto').attr('disabled', true);
      </script>

    <?php else: ?>
      <tr><td colspan="4">No hay productos cargados.</td></tr>
      <script type="text/javascript">
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

<script type="text/javascript">
(function() {
  var csrfToken = '<?php echo $csrf; ?>';

  $('.ver_modal-danger').click(function() {
    var id_producto = $(this).data('id');
    var proveedor   = $(this).data('proveedor');
    var remito      = $(this).data('remito');

    $('#div_remitos').html('<div class="text-center"><div class="loadingsm"></div></div>');

    // POST con CSRF — elimina/remito.php requiere token válido
    $.ajax({
      url      : "clases/elimina/remito.php",
      data     : {id: id_producto, csrf_token: csrfToken},
      dataType : "json",
      type     : "post",
      success  : function(data) {
        if (data.success === 'true') {
          $('#div_remitos').load('clases/nuevo/remitoinsumo.php',
            {remito: remito, proveedor: proveedor});
          $('#div_remitos').prepend('<div class="alert alert-info alert-dismissible" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
            'Se eliminó el producto.</div>');
          setTimeout(function() { $('.alert').alert('close'); }, 2000);
        } else {
          $('#div_remitos').html('<div class="alert alert-danger" role="alert">Error reintente</div>');
          setTimeout(function() { $('.alert').alert('close'); }, 2000);
        }
      }
    });
  });
})();
</script>
