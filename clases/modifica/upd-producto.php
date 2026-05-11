<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../../index.php"); exit(); }
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) { exit('Error de conexión'); }

$id = (int)($_REQUEST['id'] ?? 0);

// Cargar combos
function fetchCombo2($conexion, $sql) {
    $r = mysqli_query($conexion, $sql);
    $rows = [];
    while ($d = mysqli_fetch_assoc($r)) $rows[] = $d;
    return $rows;
}
$generos = fetchCombo2($conexion, "SELECT id_genero AS id, nombre FROM tb_genero ORDER BY nombre");
$marcas  = fetchCombo2($conexion, "SELECT id_marca  AS id, nombre FROM tb_marca  ORDER BY nombre");
$tipos   = fetchCombo2($conexion, "SELECT id_tipo   AS id, nombre FROM tb_tipo   ORDER BY nombre");
$talles  = fetchCombo2($conexion, "SELECT id_talle  AS id, nombre FROM tb_talle  ORDER BY nombre");
$colores = fetchCombo2($conexion, "SELECT id_color  AS id, nombre FROM tb_color  ORDER BY nombre");
$ivas    = fetchCombo2($conexion, "SELECT id_iva_condicion AS id, nombre FROM tb_iva_condicion ORDER BY nombre");

// Cargar producto
$stmt = mysqli_prepare($conexion,
    "SELECT p.id_productos, p.nombre, p.id_marca, p.id_genero, p.id_tipo,
            p.id_talle, p.id_color, p.id_iva_condicion, p.codigo, p.foto,
            p.precio_costo, p.margen_ganancia
     FROM tb_productos p
     WHERE p.id_productos = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,
    $p_id, $p_nombre, $p_marca, $p_genero, $p_tipo,
    $p_talle, $p_color, $p_iva, $p_codigo, $p_foto,
    $p_costo, $p_margen
);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

function opcionesHtml2($rows, $selected) {
    $html = '<option value="0">-- Seleccionar --</option>';
    foreach ($rows as $r) {
        $sel = ((int)$r['id'] === (int)$selected) ? ' selected' : '';
        $html .= '<option value="' . (int)$r['id'] . '"' . $sel . '>'
               . htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8')
               . '</option>';
    }
    return $html;
}

$p_nombre = htmlspecialchars($p_nombre ?? '', ENT_QUOTES, 'UTF-8');
$p_codigo = htmlspecialchars($p_codigo ?? '', ENT_QUOTES, 'UTF-8');
$p_foto   = htmlspecialchars($p_foto   ?? '', ENT_QUOTES, 'UTF-8');
$p_costo  = $p_costo  !== null ? number_format((float)$p_costo,  2, '.', '') : '';
$p_margen = $p_margen !== null ? number_format((float)$p_margen, 2, '.', '') : '';
$pv_calc  = ($p_costo !== '' && $p_margen !== '')
              ? number_format((float)$p_costo * (1 + (float)$p_margen / 100), 2, '.', '')
              : '';
?>

<form class="form-horizontal" role="form" id="formulario_nuevo"
      onsubmit="event.preventDefault(); modifica('modificaproducto')">

<div class="modal-header">
  <h4 class="modal-title">Modificar Producto</h4>
</div>
<br>

<div class="well bs-component">
 <div class="row">
  <div class="col-lg-6">
   <fieldset>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Nombre</label>
      <div class="col-lg-8">
        <input type="text" class="form-control" autocomplete="off"
               id="dato_nombre" value="<?php echo $p_nombre; ?>" required autofocus>
        <input type="hidden" id="dato_id" value="<?php echo (int)$p_id; ?>">
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Marca</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_marca" required>
          <?php echo opcionesHtml2($marcas, $p_marca); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Género</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_genero" required>
          <?php echo opcionesHtml2($generos, $p_genero); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Tipo</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_tipo" required>
          <?php echo opcionesHtml2($tipos, $p_tipo); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Talle</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_talle" required>
          <?php echo opcionesHtml2($talles, $p_talle); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">Color</label>
      <div class="col-lg-8">
        <select class="form-control combo-codigo" id="dato_color" required>
          <?php echo opcionesHtml2($colores, $p_color); ?>
        </select>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-4 control-label">IVA</label>
      <div class="col-lg-8">
        <select class="form-control" id="dato_iva" required>
          <?php echo opcionesHtml2($ivas, $p_iva); ?>
        </select>
      </div>
    </div>

   </fieldset>
  </div>

  <div class="col-lg-6">
   <fieldset>

    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Código</label>
      <div class="col-lg-8">
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" autocomplete="off"
                 id="dato_codigo" value="<?php echo $p_codigo; ?>" required>
          <span class="input-group-btn">
            <button type="button" class="btn btn-default" id="btn-regenerar"
                    title="Regenerar código">
              <span class="glyphicon glyphicon-refresh"></span>
            </button>
          </span>
        </div>
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
                     id="dato_costo" step="0.01" min="0"
                     value="<?php echo $p_costo; ?>" placeholder="0.00">
            </div>
          </div>
          <div class="col-xs-6">
            <div class="input-group input-group-sm">
              <input type="number" class="form-control" autocomplete="off"
                     id="dato_margen" step="0.01" min="0" max="999"
                     value="<?php echo $p_margen; ?>" placeholder="0.00">
              <span class="input-group-addon">%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Precio venta</label>
      <div class="col-lg-9">
        <div class="input-group input-group-sm">
          <span class="input-group-addon">$</span>
          <input type="text" class="form-control" id="precio_venta_calc"
                 readonly value="<?php echo $pv_calc ? '$ '.$pv_calc : ''; ?>">
        </div>
      </div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Foto (URL)</label>
      <div class="col-lg-9">
        <input type="text" class="form-control" autocomplete="off"
               id="dato_foto" value="<?php echo $p_foto; ?>">
      </div>
    </div>

   </fieldset>
  </div>
 </div>
</div>

<div class="modal-footer">
  <div class="form-group form-group-sm">
    <div class="col-lg-7"><div align="center" id="div_mensaje_general"></div></div>
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
    var pad = function(n,w){ return String(n).padStart(w,'0'); };
    var base12 = '20'+pad(marca,2)+pad(genero,2)+pad(tipo,2)+pad(talle,2)+pad(color,2);
    $('#dato_codigo').val(base12 + ean13check(base12));
  }
  function calcPV() {
    var costo  = parseFloat($('#dato_costo').val())  || 0;
    var margen = parseFloat($('#dato_margen').val()) || 0;
    if (costo > 0) {
      $('#precio_venta_calc').val('$ ' + (costo * (1 + margen / 100)).toFixed(2));
    } else {
      $('#precio_venta_calc').val('');
    }
  }
  $('.combo-codigo').on('change', generarCodigo);
  $('#btn-regenerar').on('click', generarCodigo);
  $('#dato_costo, #dato_margen').on('input', calcPV);
})();
</script>
