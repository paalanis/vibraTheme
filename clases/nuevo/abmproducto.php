<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

// Cargar combos
function fetchCombo($conexion, $sql) {
    $r = mysqli_query($conexion, $sql);
    $rows = [];
    while ($d = mysqli_fetch_assoc($r)) $rows[] = $d;
    return $rows;
}

$generos = fetchCombo($conexion, "SELECT id_genero AS id, nombre FROM tb_genero ORDER BY nombre");
$marcas  = fetchCombo($conexion, "SELECT id_marca  AS id, nombre FROM tb_marca  ORDER BY nombre");
$tipos   = fetchCombo($conexion, "SELECT id_tipo   AS id, nombre FROM tb_tipo   ORDER BY nombre");
$talles  = fetchCombo($conexion, "SELECT id_talle  AS id, nombre FROM tb_talle  ORDER BY nombre");
$colores = fetchCombo($conexion, "SELECT id_color  AS id, nombre FROM tb_color  ORDER BY nombre");
$ivas    = fetchCombo($conexion, "SELECT id_iva_condicion AS id, nombre FROM tb_iva_condicion ORDER BY nombre");

function opcionesHtml($rows, $selected = 0) {
    $html = '<option value="0">-- Seleccionar --</option>';
    foreach ($rows as $r) {
        $sel = ($r['id'] == $selected) ? ' selected' : '';
        $html .= '<option value="' . (int)$r['id'] . '"' . $sel . '>'
               . htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8')
               . '</option>';
    }
    return $html;
}
?>

<form class="form-horizontal" role="form" id="formulario_nuevo"
      onsubmit="event.preventDefault(); guardarProducto()">

<div class="modal-header">
  <h4 class="modal-title">Alta de Producto</h4>
</div>
<br>

<div class="well bs-component">
 <div class="row">
  <!-- Columna izquierda: atributos -->
  <div class="col-lg-6">
   <fieldset>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Nombre</label>
      <div class="col-lg-8">
        <input type="text" class="form-control" autocomplete="off"
               id="dato_nombre" required autofocus>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Marca</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_marca" required>
          <?php echo opcionesHtml($marcas); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Género</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_genero" required>
          <?php echo opcionesHtml($generos); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Tipo</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_tipo" required>
          <?php echo opcionesHtml($tipos); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Talle</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_talle" required>
          <?php echo opcionesHtml($talles); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Color</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_color" required>
          <?php echo opcionesHtml($colores); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">IVA</label>
      <div class="col-lg-8">
        <select class="form-control" id="dato_iva" required>
          <?php echo opcionesHtml($ivas); ?>
        </select>
      </div>
    </div>

   </fieldset>
  </div>

  <!-- Columna derecha: precios y código -->
  <div class="col-lg-6">
   <fieldset>

    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Código EAN-13</label>
      <div class="col-lg-9">
        <input type="text" class="form-control input-sm" id="dato_codigo"
               readonly placeholder="Se genera automáticamente al guardar"
               style="background:#f5f5f5;color:#888">
        <div id="dup-estado" style="margin-top:4px;min-height:18px"></div>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Costo / Margen</label>
      <div class="col-lg-9">
        <div class="row">
          <div class="col-xs-6">
            <div class="input-group input-group-sm">
              <span class="input-group-addon">$</span>
              <input type="number" class="form-control" autocomplete="off"
                     id="dato_costo" step="0.01" min="0" placeholder="0.00">
            </div>
          </div>
          <div class="col-xs-6">
            <div class="input-group input-group-sm">
              <input type="number" class="form-control" autocomplete="off"
                     id="dato_margen" step="0.01" min="0" max="999" placeholder="0.00">
              <span class="input-group-addon">%</span>
            </div>
          </div>
        </div>
        <small class="text-muted">Se actualizan desde el remito. Opcionales al cargar.</small>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Venta</label>
      <div class="col-lg-9">
        <div class="input-group input-group-sm">
          <span class="input-group-addon">$</span>
          <input type="text" class="form-control" id="precio_venta_calc"
                 readonly placeholder="Costo × (1 + Margen%)">
        </div>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Foto</label>
      <div class="col-lg-9">
        <input type="text" class="form-control" autocomplete="off"
               id="dato_foto" placeholder="URL opcional">
      </div>
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
        <button type="button" onclick="inicio()" class="btn btn-default">Salir</button>
        <button type="submit" id="boton_guardar" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

</form>

<script>
// Manejo custom para mostrar errores descriptivos del servidor
function guardarProducto() {
  var pars = '';
  $("#formulario_nuevo").find(':input').each(function() {

  // ── Control de producto duplicado por atributos ─────────────────────
  function verificarDuplicado() {
    var marca  = parseInt($('#dato_marca').val())  || 0;
    var genero = parseInt($('#dato_genero').val()) || 0;
    var tipo   = parseInt($('#dato_tipo').val())   || 0;
    var talle  = parseInt($('#dato_talle').val())  || 0;
    var color  = parseInt($('#dato_color').val())  || 0;

    if (!marca || !genero || !tipo || !talle || !color) {
      $('#dup-estado').html('');
      $('#boton_guardar').prop('disabled', false);
      return;
    }
    $('#dup-estado').html('<span class="text-muted"><small>Verificando...</small></span>');
    var nombre = $('#dato_nombre').val().trim();
    $.ajax({
      url      : 'clases/control/producto-dup.php',
      data     : {nombre:nombre, marca:marca, genero:genero, tipo:tipo, talle:talle, color:color},
      dataType : 'json', type: 'get',
      success  : function(data) {
        if (data.existe) {
          $('#dup-estado').html(
            '<span class="text-danger"><span class="glyphicon glyphicon-ban-circle"></span> ' +
            '<strong>Producto duplicado</strong> — ya existe: <em>' + data.nombre + '</em></span>'
          );
          $('#boton_guardar').prop('disabled', true);
        } else {
          $('#dup-estado').html(
            '<span class="text-success"><span class="glyphicon glyphicon-ok"></span> ' +
            'Combinación disponible</span>'
          );
          $('#boton_guardar').prop('disabled', false);
        }
      }
    });
  }

  // ── Precio venta calculado ──────────────────────────────────────────
  function calcularPrecioVenta() {
    var costo  = parseFloat($('#dato_costo').val())  || 0;
    var margen = parseFloat($('#dato_margen').val()) || 0;
    if (costo > 0) {
      $('#precio_venta_calc').val('$ ' + (costo * (1 + margen / 100)).toFixed(2));
    } else {
      $('#precio_venta_calc').val('');
    }
  }

  $('#dato_nombre').on('blur', verificarDuplicado);
  $('#dato_marca, #dato_genero, #dato_tipo, #dato_talle, #dato_color').on('change', verificarDuplicado);
  $('#dato_costo, #dato_margen').on('input', calcularPrecioVenta);

})();
</script>
