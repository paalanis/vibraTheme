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
      onsubmit="event.preventDefault(); nuevo('producto')">

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
      <label class="col-lg-4 control-label">Código (EAN-13)</label>
      <div class="col-lg-8">
        <div class="input-group">
          <input type="text" class="form-control" autocomplete="off"
                 id="dato_codigo" placeholder="Auto-generado" required>
          <span class="input-group-btn">
            <button type="button" class="btn btn-default" id="btn-regenerar"
                    title="Regenerar código">
              <span class="glyphicon glyphicon-refresh"></span>
            </button>
          </span>
        </div>
        <small class="text-muted">Se genera automáticamente al elegir los atributos.
          Puede editarlo manualmente si lo necesita.</small>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Precio costo</label>
      <div class="col-lg-8">
        <div class="input-group">
          <span class="input-group-addon">$</span>
          <input type="number" class="form-control" autocomplete="off"
                 id="dato_costo" step="0.01" min="0" placeholder="0.00">
        </div>
        <small class="text-muted">Se actualiza desde el remito. Opcional al cargar.</small>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Margen %</label>
      <div class="col-lg-8">
        <div class="input-group">
          <input type="number" class="form-control" autocomplete="off"
                 id="dato_margen" step="0.01" min="0" max="999" placeholder="0.00">
          <span class="input-group-addon">%</span>
        </div>
        <small class="text-muted">Se actualiza desde el remito. Opcional al cargar.</small>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Precio venta</label>
      <div class="col-lg-8">
        <div class="input-group">
          <span class="input-group-addon">$</span>
          <input type="text" class="form-control" id="precio_venta_calc"
                 readonly placeholder="Calculado automáticamente">
        </div>
        <small class="text-muted">= Costo × (1 + Margen%)</small>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Foto (URL)</label>
      <div class="col-lg-8">
        <input type="text" class="form-control" autocomplete="off"
               id="dato_foto" placeholder="Opcional">
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
(function() {

  // ── EAN-13 client-side ──────────────────────────────────────────────
  function ean13check(base12) {
    var sum = 0;
    for (var i = 0; i < 12; i++) {
      sum += parseInt(base12[i]) * (i % 2 === 0 ? 1 : 3);
    }
    return (10 - (sum % 10)) % 10;
  }

  function generarCodigo() {
    var marca  = parseInt($('#dato_marca').val())  || 0;
    var genero = parseInt($('#dato_genero').val()) || 0;
    var tipo   = parseInt($('#dato_tipo').val())   || 0;
    var talle  = parseInt($('#dato_talle').val())  || 0;
    var color  = parseInt($('#dato_color').val())  || 0;

    if (!marca || !genero || !tipo || !talle || !color) return;

    var pad = function(n, w) { return String(n).padStart(w, '0'); };
    var base12 = '20' + pad(marca,2) + pad(genero,2) + pad(tipo,2) + pad(talle,2) + pad(color,2);
    var codigo  = base12 + ean13check(base12);
    $('#dato_codigo').val(codigo);
  }

  // ── Precio venta calculado ──────────────────────────────────────────
  function calcularPrecioVenta() {
    var costo  = parseFloat($('#dato_costo').val())  || 0;
    var margen = parseFloat($('#dato_margen').val()) || 0;
    if (costo > 0) {
      var pv = costo * (1 + margen / 100);
      $('#precio_venta_calc').val('$ ' + pv.toFixed(2));
    } else {
      $('#precio_venta_calc').val('');
    }
  }

  // Eventos
  $('.combo-codigo').on('change', generarCodigo);
  $('#btn-regenerar').on('click', generarCodigo);
  $('#dato_costo, #dato_margen').on('input', calcularPrecioVenta);

})();
</script>
