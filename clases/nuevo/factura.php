<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
if (!isset($_SESSION['cierre'])) {
    header("Location: abrecaja.php"); exit();
}

include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo "Error de conexión: " . mysqli_connect_error();
    exit();
}

date_default_timezone_set("America/Argentina/Mendoza");
$hoy    = date("Y-m-d H:i:s");
$cierre = (int)$_SESSION['cierre'];

// ── Clientes ─────────────────────────────────────────────────────────────────
$rscliente = mysqli_query($conexion,
    "SELECT tb_clientes.id_clientes AS id,
            CONCAT(tb_clientes.apellido,' ',tb_clientes.nombre) AS cliente
     FROM tb_clientes
     ORDER BY prioridad ASC, cliente ASC"
);

// ── Condiciones de venta (solo las del día) ───────────────────────────────────
$rscondicion = mysqli_query($conexion,
    "SELECT id_condicion_venta AS id, nombre AS condicion,
            descuento, cupon
     FROM tb_condicion_venta
     WHERE dias LIKE CONCAT('%',DAYOFWEEK(CURDATE()),'%')
     ORDER BY condicion ASC"
);

// ── Número de factura ─────────────────────────────────────────────────────────
// Paso 1: ¿hay alguna factura en estado=0 (carrito pendiente) para este cierre?
$stmt = mysqli_prepare($conexion,
    "SELECT IFNULL(MAX(numero_factura), 1) AS factura, id_clientes AS cliente
     FROM tb_ventas
     WHERE id_sucursal = 1 AND id_cierre = ? AND estado = '0'"
);
mysqli_stmt_bind_param($stmt, 'i', $cierre);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $factura_raw, $cliente_raw);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$factura  = (int)$factura_raw;
$cliente  = (int)$cliente_raw;
$pendiente = 'no';

if ($factura !== 1) {
    // Hay un carrito pendiente — retomamos ese número
    $pendiente = 'si';
} else {
    // Paso 2: siguiente número en el cierre actual
    $stmt2 = mysqli_prepare($conexion,
        "SELECT IFNULL(MAX(numero_factura) + 1, 1) AS factura
         FROM tb_ventas
         WHERE id_sucursal = 1 AND id_cierre = ?"
    );
    mysqli_stmt_bind_param($stmt2, 'i', $cierre);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_bind_result($stmt2, $factura2);
    mysqli_stmt_fetch($stmt2);
    mysqli_stmt_close($stmt2);

    $factura = (int)$factura2;

    if ($factura === 1) {
        // Paso 3: primer ticket del cierre — buscar último en acumulado global
        $stmt3 = mysqli_prepare($conexion,
            "SELECT IFNULL(MAX(numero_factura) + 1, 1) AS factura
             FROM tb_ventas_acumulado
             WHERE id_sucursal = 1"
        );
        mysqli_stmt_execute($stmt3);
        mysqli_stmt_bind_result($stmt3, $factura3);
        mysqli_stmt_fetch($stmt3);
        mysqli_stmt_close($stmt3);
        $factura = (int)$factura3;
    }
}
?>

<form class="form-horizontal" id="formulario_nuevo" role="form">

  <input type="hidden" class="form-control" id="factura"       value="<?php echo $factura; ?>">
  <input type="hidden" class="form-control" id="cliente"       value="<?php echo $cliente; ?>">
  <input type="hidden" class="form-control" id="dato_sucursal" value="1">
  <input type="hidden" class="form-control" id="pendiente"     value="<?php echo $pendiente; ?>">
  <input type="hidden" class="form-control" id="cierre"        value="<?php echo $cierre; ?>">

  <div class="well bs-component">
    <div class="row">
      <div class="col-lg-12">
        <fieldset>
          <div class="form-group form-group-sm">
            <div class="col-lg-12">
              <div class="col-lg-2">
                <input type="datetime" class="form-control" id="dato_fecha" value="<?php echo $hoy; ?>" disabled>
              </div>
              <label for="inputPassword" class="col-lg-2 control-label">Numero de Ticket</label>
              <div class="col-lg-3">
                <input type="text" class="form-control" id="dato_factura" value="<?php echo $factura; ?>" disabled>
              </div>
              <div class="col-lg-5">
                <select class="form-control" id="dato_cliente" required>
                  <?php while ($row = mysqli_fetch_assoc($rscliente)): ?>
                    <option value="<?php echo (int)$row['id']; ?>">
                      <?php echo htmlspecialchars($row['cliente'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group form-group-md">
            <div class="col-lg-12">
              <label class="col-lg-2 control-label">Cantidad de producto</label>
              <div class="col-lg-2">
                <input type="number" class="form-control" id="dato_cantidad" min="0" value="1">
              </div>
              <div class="col-lg-4">
                <input type="text" class="form-control" id="dato_codigo" autocomplete="off" value="" placeholder="Código">
              </div>
              <div class="col-lg-4">
                <input type="text" class="form-control" id="nombre" value="" autocomplete="off" placeholder="Buscar por nombre o código">
              </div>
            </div>
          </div>
        </fieldset>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-10">
        <div class="form-group form-group-md">
          <div class="col-lg-9" id="div_producto"></div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-10" id="div_duplicado"></div>
    </div>
  </div>

  <div class="well bs-component" style="background-color:#cad0d273">
    <div class="row">
      <fieldset id="div_remitos"></fieldset>
    </div>
  </div>

  <div class="well bs-component" style="background-color:#4f636b4d">
    <div class="row">
      <div class="col-lg-2"></div>
      <div class="col-lg-5">

        <label class="col-lg-3 control-label">Venta</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_condicion" disabled>
            <option value="0"></option>
            <?php
            $cupon     = [];
            $descuento = [];
            while ($row = mysqli_fetch_assoc($rscondicion)):
                $cupon[$row['id']]     = $row['cupon'];
                $descuento[$row['id']] = $row['descuento'];
            ?>
            <option value="<?php echo (int)$row['id']; ?>">
              <?php echo htmlspecialchars($row['condicion'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <label class="col-lg-3 control-label">Cupón</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" id="dato_cupon" value="" disabled>
        </div>
        <label class="col-lg-3 control-label">Monto $</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_monto" value="" disabled>
        </div>
      </div>

      <div class="col-lg-5">
        <fieldset>
          <label class="col-lg-3 control-label">Subtotal $</label>
          <div class="col-lg-9">
            <input type="text" class="form-control" id="subtotal" value="0" readonly>
          </div>
          <div id="lbl_ahorro" style="display:none;">
            <label class="col-lg-3 control-label" style="color:#d9534f;">
              <span class="glyphicon glyphicon-tag"></span> Ahorro $
            </label>
            <div class="col-lg-9">
              <input type="text" class="form-control" id="ahorro" value="0,00" readonly
                     style="color:#d9534f; font-weight:bold;">
            </div>
          </div>
          <label class="col-lg-3 control-label">Total $</label>
          <div class="col-lg-9">
            <input type="text" class="form-control" id="total" value="0" readonly>
          </div>
          <label class="col-lg-3 control-label">Vuelto $</label>
          <div class="col-lg-9">
            <input type="text" class="form-control" id="dato_vuelto" value="0" disabled>
          </div>
        </fieldset>
      </div>
    </div>
  </div>

  <div class="modal-footer">
    <div class="form-group form-group-sm">
      <div class="col-lg-7">
        <div align="center" id="div_mensaje_general"></div>
      </div>
      <div class="col-lg-5">
        <div align="right">
          <button type="button" id="boton_salir"   onclick="inicio()" class="btn btn-default">Salir</button>
          <button type="button" id="boton_guardar" class="btn btn-primary" onclick="nuevo('factura')">Guardar Factura</button>
        </div>
      </div>
    </div>
  </div>
</form>

<script type="text/javascript">

  $(document).ready(function () {
    $('#boton_guardar').attr('disabled', true);
    $("#dato_codigo").focus();

    if ($('#pendiente').val() === 'si') {
      var factura = $('#dato_factura').val();
      var cierre  = $('#cierre').val();
      var cliente = $('#cliente').val();
      $("#dato_cliente").val(cliente);
      $('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: factura, cierre: cierre});
      $("#dato_cantidad").val(1);
      $("#dato_codigo").val('');
      $("#nombre").val('');
      $("#dato_codigo").focus();
      $('#pendiente').val('no');
    }

    $("#formulario_nuevo").keypress(function(e) {
      var code = (e.keyCode ? e.keyCode : e.which);
      if (code === 13 && $('#boton_guardar').prop('disabled') === false) {
        nuevo('factura');
      }
    });
  });

  $(function() {
    $('#dato_codigo').change(function() {
      if ($(this).val() !== '' && $('#dato_cantidad').val() > 0) {
        var pars = '';
        $("#formulario_nuevo").find(':input').each(function() {
          var _id = $(this).attr('id'); if (!_id) return;
          var dato = _id.split('_', 2);
          if (dato[0] === 'dato') {
            pars += 'dato_' + dato[1] + '=' + encodeURIComponent($(this).val()) + '&';
          }
        });

        $("#div_remitos").html('<div class="text-center"><div class="loadingsm"></div></div>');

        $.ajax({
          url      : "clases/guardar/producto-caja.php",
          data     : pars,
          dataType : "json",
          type     : "post",
          success: function(data) {
            switch (data.success) {
              case 'true':
                $('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                $("#dato_cantidad").val(1); $("#dato_codigo").val(''); $("#nombre").val(''); $("#dato_codigo").focus();
                break;
              case 'sin_stock':
                // Restaurar tabla (sacar spinner) y mostrar aviso de stock insuficiente
                $('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                $('#div_duplicado').html('<div id="mensaje_general" class="alert alert-warning alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Sin stock suficiente (disponible: ' + data.stock + ', necesita: ' + data.necesita + ')</div>');
                setTimeout("$('#mensaje_general').alert('close')", 3000);
                $("#dato_cantidad").val(1); $("#dato_codigo").val(''); $("#nombre").val(''); $("#dato_codigo").focus();
                break;
              case 'no_existe':
                $('#div_duplicado').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Producto inexistente!</div>');
                setTimeout("$('#mensaje_general').alert('close')", 2000);
                $('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                $("#dato_cantidad").val(1); $("#dato_codigo").val(''); $("#nombre").val(''); $("#dato_codigo").focus();
                break;
              case 'false':
                $('#div_remitos').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Error reintente!</div>');
                setTimeout("$('#mensaje_general').alert('close')", 2000);
                $("#dato_codigo").focus();
                break;
            }
          },
          error: function() {
            // PHP devolvió error (500, fatal, etc.) — sacar spinner y avisar
            $('#div_remitos').html('');
            $('#div_duplicado').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Error del servidor. Verifique que todos los archivos estén subidos.</div>');
            setTimeout("$('#mensaje_general').alert('close')", 4000);
            $("#dato_cantidad").val(1); $("#dato_codigo").val(''); $("#nombre").val(''); $("#dato_codigo").focus();
          }
        });
      } else {
        alert('Faltan datos');
      }
    });
  });

  $(function() {
    $('#nombre').change(function() {
      var codigo = $(this).val();
      if (codigo !== '') {
        $('#div_producto').html('<div class="text-center"><div class="loadingsm"></div></div>');
        $("#div_producto").load("clases/control/producto.php", {codigo: codigo, buscar: 'nombre'});
      } else {
        $("#div_producto").html('');
        $("#dato_codigo").focus();
      }
    });
  });

  var condicion_cupon     = <?php echo json_encode($cupon ?? []); ?>;
  var condicion_descuento = <?php echo json_encode($descuento ?? []); ?>;

  $(function() {
    var xhrRecalc = null; // para abortar llamada anterior si cambia rápido

    $('#dato_condicion').change(function() {
      var id             = $(this).val();
      var dato_descuento = condicion_descuento[id] || 0;
      var dato_cupon     = condicion_cupon[id];

      if (id === '0') {
        $('#boton_guardar').attr('disabled', true);
        $('#total').val(0); $('#dato_vuelto').val(0); $('#dato_monto').val('');
        $('#dato_monto').attr('disabled', true);
        return;
      }

      // Habilitar campos según tipo de condición
      if (dato_cupon === '1') {
        $('#dato_cupon').attr('disabled', false);
        $('#boton_guardar').attr('disabled', false);
        $('#dato_monto').attr('disabled', true).val('');
        $('#dato_vuelto').val(0);
      } else {
        $('#dato_cupon').attr('disabled', true).val('');
        $('#dato_monto').attr('disabled', false);
        $('#boton_guardar').attr('disabled', true);
      }

      // Abortar recalcul anterior si sigue en vuelo
      if (xhrRecalc) { xhrRecalc.abort(); }

      var factura  = $('#dato_factura').val();
      var cierre   = $('#cierre').val();
      var sucursal = $('#dato_sucursal').val();
      var csrf     = $('meta[name="csrf-token"]').attr('content');

      $('#div_remitos').html('<div class="text-center"><div class="loadingsm"></div></div>');

      xhrRecalc = $.post('clases/guardar/recalcular-descuento.php', {
        id_condicion : id,
        dato_factura : factura,
        dato_sucursal: sucursal,
        csrf_token   : csrf
      }, function(d) {
        xhrRecalc = null;
        // Recargar carrito con precios actualizados
        $('#div_remitos').load(
          'clases/nuevo/facturainsumo.php',
          {factura: factura, cliente: $('#dato_cliente').val(), cierre: cierre},
          function() {
            // facturainsumo.php resetea dato_condicion, total y monto en su ready() —
            // restaurar el estado correcto para esta condición ya seleccionada.
            $('#dato_condicion').val(id); // sin .trigger() para evitar loop infinito

            var nuevoSub = parseFloat($('#subtotal').val()) || 0;
            var total    = nuevoSub + nuevoSub * dato_descuento / 100;
            $('#total').val(total.toFixed(2));

            // Restaurar campos según tipo de condición
            if (dato_cupon === '1') {
              $('#dato_cupon').attr('disabled', false);
              $('#boton_guardar').attr('disabled', false);
              $('#dato_monto').attr('disabled', true).val('');
              $('#dato_vuelto').val(0);
            } else {
              $('#dato_cupon').attr('disabled', true).val('');
              $('#dato_monto').attr('disabled', false);
              $('#boton_guardar').attr('disabled', true); // se habilita al ingresar monto
              $('#dato_monto').focus();
            }
          }
        );
      }, 'json').fail(function(xhr, status) {
        if (status === 'abort') return; // cancelado intencionalmente — nueva llamada ya en vuelo
        xhrRecalc = null;
        // Recalculación falló — recargar el carrito para limpiar el spinner
        // (los precios en DB no cambiaron porque recalcular-descuento.php no hizo commit)
        $('#div_remitos').load(
          'clases/nuevo/facturainsumo.php',
          {factura: factura, cliente: $('#dato_cliente').val(), cierre: cierre},
          function() {
            // Mismo restore que en el path de éxito
            $('#dato_condicion').val(id);
            var sub   = parseFloat($('#subtotal').val()) || 0;
            var total = sub + sub * dato_descuento / 100;
            $('#total').val(total.toFixed(2));
            if (dato_cupon === '1') {
              $('#dato_cupon').attr('disabled', false);
              $('#boton_guardar').attr('disabled', false);
              $('#dato_monto').attr('disabled', true).val('');
              $('#dato_vuelto').val(0);
            } else {
              $('#dato_cupon').attr('disabled', true).val('');
              $('#dato_monto').attr('disabled', false);
              $('#boton_guardar').attr('disabled', true);
              $('#dato_monto').focus();
            }
          }
        );
      });
    });
  });

  $(function() {
    $('#dato_monto').change(function() {
      var efectivo = parseFloat($(this).val());
      var apagar   = parseFloat($('#total').val());
      if (!isNaN(efectivo) && efectivo !== '') {
        var vuelto = Number((efectivo - apagar).toFixed(2));
        if (efectivo >= apagar) {
          $('#dato_vuelto').val(vuelto);
          $('#boton_guardar').attr('disabled', false);
        } else {
          alert('Efectivo insuficiente');
          $(this).val(''); $('#dato_vuelto').val(0);
          $('#boton_guardar').attr('disabled', true);
          $(this).focus();
        }
      } else {
        $('#boton_guardar').attr('disabled', true);
        $('#dato_vuelto').val(0);
      }
    });
  });

</script>
