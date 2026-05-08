<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
if (!isset($_SESSION['cierre'])) {
    header("Location: abrecaja.php"); exit();
}
require_once '../../conexion/csrf.php';
date_default_timezone_set("America/Argentina/Mendoza");
$fecha    = date("d-m-Y H:i");
$id       = (int)($_REQUEST['id_producto'] ?? 0);
$cierre2  = (int)($_REQUEST['cierre']      ?? 0);
$factura2 = (int)($_REQUEST['factura']     ?? 0);
$csrf     = csrf_token();
?>

<div class="modal" id="modal_abrecaja" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4><?php echo htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'); ?> - REQUIERE AUTORIZACIÓN - <span class="glyphicon glyphicon-piggy-bank"></span></h4>
      </div>
      <div class="modal-body" id="div_cargando">
        <div class="input-group">
          <span class="input-group-addon"><span class="glyphicon glyphicon-barcode"></span></span>
          <input type="hidden" class="form-control" id="articulo"  value="<?php echo $id; ?>">
          <input type="hidden" class="form-control" id="cierre_"   value="<?php echo $cierre2; ?>">
          <input type="hidden" class="form-control" id="factura_"  value="<?php echo $factura2; ?>">
          <input type="password" class="form-control is-valid" id="codigo" placeholder="Ingrese código de autorización" autocomplete="off">
        </div>
      </div>
      <div class="modal-footer">
        <div id="div_mensaje"></div>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Salir</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">

  $(document).ready(function () {
    $('#modal_abrecaja').modal({ show: true, keyboard: false });
    $('#codigo').focus();
  });

  // BUG CORREGIDO: al cerrar el modal solo recargamos #div_remitos,
  // NO el panel_inicio completo (que sacaba al usuario de la venta).
  $('#modal_abrecaja').on('hidden.bs.modal', function () {
    var factura = $('#factura_').val();
    var cierre  = $('#cierre_').val();
    $('#div_remitos').load('clases/nuevo/facturainsumo.php', { factura: factura, cierre: cierre });
    $('#dato_codigo').focus();
  });

  $(function () {
    $('#codigo').change(function () {

      var factura     = $('#factura_').val();
      var cierre      = $('#cierre_').val();
      var id_producto = $('#articulo').val();
      // BUG CORREGIDO: json_encode garantiza que el valor sea JS válido
      // aunque sea numérico, string o null.
      var autoriza    = <?php echo json_encode($_SESSION['autoriza'] ?? ''); ?>;

      if (String($(this).val()) === String(autoriza)) {

        $('#div_remitos').html('<div class="text-center"><div class="loadingsm"></div></div>');

        $.ajax({
          url      : 'clases/elimina/factura.php',
          data     : { id: id_producto, csrf_token: '<?php echo $csrf; ?>' },
          dataType : 'json',
          type     : 'post',
          success: function (data) {
            if (data.success === 'true') {
              $('#div_mensaje').html(
                '<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                'Se eliminó el producto!</div>'
              );
              setTimeout(function () { $('#mensaje_general').alert('close'); }, 1000);
              setTimeout(function () { $('#modal_abrecaja').modal('hide'); }, 1500);
            } else {
              $('#div_mensaje').html(
                '<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                'Error, reintente.</div>'
              );
              setTimeout(function () { $('#mensaje_general').alert('close'); }, 2000);
              $('#codigo').val('').focus();
            }
          },
          error: function () {
            $('#div_mensaje').html(
              '<div class="alert alert-danger">Error de comunicación.</div>'
            );
            $('#codigo').val('').focus();
          }
        });

      } else {
        alert('Código erróneo');
        $(this).val('').focus();
      }
    });
  });

</script>
