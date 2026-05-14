<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';

$id = (int)($_REQUEST['id'] ?? 0);
$stmt = mysqli_prepare($conexion,
    "SELECT id_condicion_venta, nombre, descuento, cupon, dias
     FROM tb_condicion_venta
     WHERE id_condicion_venta = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$r_id = $r_nom = $r_dto = $r_cup = $r_dias = null;
mysqli_stmt_bind_result($stmt, $r_id, $r_nom, $r_dto, $r_cup, $r_dias);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$nombre    = htmlspecialchars($r_nom  ?? '', ENT_QUOTES, 'UTF-8');
$descuento = number_format((float)($r_dto ?? 0), 2, '.', '');
$cupon     = ($r_cup === '1') ? '1' : '0';
$dias      = $r_dias ?? '';

// Soporta ambos formatos: '1234567' (legacy) y '1,2,3,4,5,6,7' (nuevo)
$dias_activos = (strpos($dias, ',') !== false)
    ? array_map('intval', array_filter(explode(',', $dias), 'strlen'))
    : array_map('intval', array_filter(str_split($dias), 'strlen'));
$dias_activos = array_values(array_filter($dias_activos, fn($n) => $n >= 1 && $n <= 7));

// Normalizar siempre a formato con comas para el hidden input
// Así el PHP de modifica siempre recibe '1,2,3,4,5,6,7' y nunca '1234567'
$dias_normalizado = implode(',', $dias_activos);
$dia_labels   = [1=>'Dom', 2=>'Lun', 3=>'Mar', 4=>'Mié', 5=>'Jue', 6=>'Vie', 7=>'Sáb'];
?>
<form class="form-horizontal" role="form" id="formulario_nuevo"
      onsubmit="event.preventDefault(); modifica('condicion')">

<div class="modal-header">
  <h4 class="modal-title">Modificar Condición de Venta</h4>
</div>
<br>

<div class="well bs-component">
  <div class="row">
    <div class="col-lg-7">
      <fieldset>

        <div class="form-group form-group-sm">
          <label class="col-lg-4 control-label">Nombre</label>
          <div class="col-lg-8">
            <input type="text" class="form-control" autocomplete="off"
                   id="dato_nombre" value="<?php echo $nombre; ?>" required autofocus maxlength="255">
            <input type="hidden" id="dato_id" value="<?php echo (int)$r_id; ?>">
          </div>
        </div>

        <div class="form-group form-group-sm">
          <label class="col-lg-4 control-label">
            Ajuste (%)
            <span class="glyphicon glyphicon-info-sign text-muted"
                  title="Negativo = descuento, positivo = recargo. Ej: -5 aplica 5% de descuento sobre el total."
                  style="cursor:help;"></span>
          </label>
          <div class="col-lg-5">
            <input type="number" class="form-control" id="dato_descuento"
                   step="0.01" min="-100" max="100"
                   value="<?php echo $descuento; ?>">
          </div>
        </div>

        <div class="form-group form-group-sm">
          <label class="col-lg-4 control-label">Requiere cupón</label>
          <div class="col-lg-8">
            <div class="checkbox" style="margin-top:5px;">
              <label>
                <input type="checkbox" id="chk_cupon"
                       <?php echo $cupon === '1' ? 'checked' : ''; ?>>
                Sí, requiere código de cupón
              </label>
            </div>
            <input type="hidden" id="dato_cupon" value="<?php echo $cupon; ?>">
          </div>
        </div>

        <div class="form-group form-group-sm">
          <label class="col-lg-4 control-label">Días activos</label>
          <div class="col-lg-8">
            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:6px;">
              <?php foreach ($dia_labels as $num => $lbl): ?>
              <label class="label label-<?php echo in_array($num, $dias_activos) ? 'primary' : 'default'; ?>"
                     style="font-size:12px; padding:5px 8px; cursor:pointer; font-weight:normal;">
                <input type="checkbox" class="dia-check" value="<?php echo $num; ?>"
                       <?php echo in_array($num, $dias_activos) ? 'checked' : ''; ?>
                       style="margin-right:3px;">
                <?php echo $lbl; ?>
              </label>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-xs btn-info" id="btn_todos_dias">
              <span class="glyphicon glyphicon-check"></span> Todos
            </button>
            <button type="button" class="btn btn-xs btn-default" id="btn_limpiar_dias">
              <span class="glyphicon glyphicon-unchecked"></span> Ninguno
            </button>
            <input type="hidden" id="dato_dias"
                   value="<?php echo htmlspecialchars($dias_normalizado, ENT_QUOTES, 'UTF-8'); ?>">
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
$(function() {

  $('#chk_cupon').on('change', function() {
    $('#dato_cupon').val($(this).is(':checked') ? '1' : '0');
  });

  function actualizarDias() {
    var sel = [];
    $('.dia-check:checked').each(function() { sel.push(parseInt($(this).val())); });
    sel.sort(function(a, b) { return a - b; });
    $('#dato_dias').val(sel.join(','));
    $('.dia-check').each(function() {
      var $lbl = $(this).closest('label');
      $lbl.toggleClass('label-default', !$(this).is(':checked'))
          .toggleClass('label-primary',  $(this).is(':checked'));
    });
  }

  $(document).on('change', '.dia-check', actualizarDias);

  $('#btn_todos_dias').on('click', function() {
    $('.dia-check').prop('checked', true);
    actualizarDias();
  });

  $('#btn_limpiar_dias').on('click', function() {
    $('.dia-check').prop('checked', false);
    actualizarDias();
  });

});
</script>
