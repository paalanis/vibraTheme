<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

$stmt = mysqli_prepare($conexion,
    "SELECT id_condicion_venta, nombre, descuento, cupon, dias
     FROM tb_condicion_venta
     ORDER BY nombre ASC"
);
mysqli_stmt_execute($stmt);
$r_id = $r_nom = $r_dto = $r_cup = $r_dias = null;
mysqli_stmt_bind_result($stmt, $r_id, $r_nom, $r_dto, $r_cup, $r_dias);
$condiciones = [];
while (mysqli_stmt_fetch($stmt)) {
    $condiciones[] = [
        'id'        => $r_id,
        'nombre'    => htmlspecialchars($r_nom ?? '', ENT_QUOTES, 'UTF-8'),
        'descuento' => $r_dto,
        'cupon'     => $r_cup,
        'dias'      => $r_dias ?? '',
    ];
}
mysqli_stmt_close($stmt);

// Etiquetas de días (DAYOFWEEK: 1=Dom … 7=Sáb)
$dia_labels = [1=>'Dom', 2=>'Lun', 3=>'Mar', 4=>'Mié', 5=>'Jue', 6=>'Vie', 7=>'Sáb'];

function dias_texto(string $dias, array $labels): string {
    if ($dias === '') return '<span class="text-muted">—</span>';
    // Soporta ambos formatos: '1234567' (legacy) y '1,2,3,4,5,6,7' (nuevo)
    $nums = (strpos($dias, ',') !== false)
        ? array_map('intval', explode(',', $dias))
        : array_map('intval', str_split($dias));
    $nums = array_filter($nums, function($n) { return $n >= 1 && $n <= 7; });
    $out  = [];
    foreach ($nums as $n) {
        if (isset($labels[$n])) $out[] = $labels[$n];
    }
    return $out ? implode(', ', $out) : '<span class="text-muted">—</span>';
}
?>

<form class="form-horizontal" role="form" id="formulario_nuevo"
      onsubmit="event.preventDefault(); nuevo('condicion')">

<div class="modal-header">
  <h4 class="modal-title">Condiciones de Venta</h4>
</div>
<br>

<div class="well bs-component">
  <div class="row">

    <!-- ── Formulario de alta ── -->
    <div class="col-lg-6">
      <fieldset>

        <div class="form-group form-group-sm">
          <label class="col-lg-4 control-label">Nombre</label>
          <div class="col-lg-8">
            <input type="text" class="form-control" autocomplete="off"
                   id="dato_nombre" required autofocus maxlength="255">
          </div>
        </div>

        <div class="form-group form-group-sm">
          <label class="col-lg-4 control-label">
            Ajuste (%)
            <span class="glyphicon glyphicon-info-sign text-muted"
                  title="Negativo = descuento, positivo = recargo. Ej: -5 aplica 5% de descuento sobre el total."
                  style="cursor:help;"></span>
          </label>
          <div class="col-lg-4">
            <input type="number" class="form-control" id="dato_descuento"
                   step="0.01" min="-100" max="100" value="0">
          </div>
          <div class="col-lg-4">
            <span class="form-control-static text-muted" style="font-size:11px;">
              0 = sin ajuste
            </span>
          </div>
        </div>

        <div class="form-group form-group-sm">
          <label class="col-lg-4 control-label">Requiere cupón</label>
          <div class="col-lg-8">
            <div class="checkbox" style="margin-top:5px;">
              <label>
                <input type="checkbox" id="chk_cupon">
                Sí, requiere código de cupón
              </label>
            </div>
            <input type="hidden" id="dato_cupon" value="0">
          </div>
        </div>

        <div class="form-group form-group-sm">
          <label class="col-lg-4 control-label">Días activos</label>
          <div class="col-lg-8">
            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:6px;">
              <?php foreach ($dia_labels as $num => $lbl): ?>
              <label class="label label-default"
                     style="font-size:12px; padding:5px 8px; cursor:pointer; font-weight:normal;">
                <input type="checkbox" class="dia-check" value="<?php echo $num; ?>"
                       style="margin-right:3px;">
                <?php echo $lbl; ?>
              </label>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-xs btn-info" id="btn_todos_dias">
              <span class="glyphicon glyphicon-check"></span> Todos los días
            </button>
            <button type="button" class="btn btn-xs btn-default" id="btn_limpiar_dias">
              <span class="glyphicon glyphicon-unchecked"></span> Ninguno
            </button>
            <input type="hidden" id="dato_dias" value="">
            <p class="help-block" style="margin-top:4px; margin-bottom:0; font-size:11px;">
              Sin selección = condición nunca disponible en caja.
            </p>
          </div>
        </div>

      </fieldset>
    </div>

    <!-- ── Tabla de registros ── -->
    <div class="col-lg-6">
      <fieldset>
        <div class="panel panel-default">
          <div class="panel-body" style="height:250px; overflow-y:auto">
            <table class="table table-striped table-hover table-condensed">
              <thead>
                <tr class="active">
                  <th>Nombre</th>
                  <th style="text-align:center;">Ajuste</th>
                  <th style="text-align:center;">Cupón</th>
                  <th>Días</th>
                  <th>Editar</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($condiciones as $c): ?>
                <tr>
                  <td><?php echo $c['nombre']; ?></td>
                  <td style="text-align:center; font-weight:bold;
                             color:<?php echo ($c['descuento'] < 0 ? '#d9534f' : ($c['descuento'] > 0 ? '#5cb85c' : '#999')); ?>">
                    <?php
                        $dto = (float)($c['descuento'] ?? 0);
                        echo ($dto > 0 ? '+' : '') . number_format($dto, 2) . '%';
                    ?>
                  </td>
                  <td style="text-align:center;">
                    <?php if ($c['cupon'] === '1'): ?>
                      <span class="label label-warning" title="Requiere cupón">
                        <span class="glyphicon glyphicon-tag"></span>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:11px;">
                    <?php echo dias_texto($c['dias'], $dia_labels); ?>
                  </td>
                  <td>
                    <button class="ver_modal btn btn-xs btn-default" type="button"
                            value="<?php echo (int)$c['id']; ?>">
                      <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($condiciones)): ?>
                <tr><td colspan="5">No hay condiciones de venta cargadas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
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
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

</form>

<script>
$(function() {

  // ── Editar ────────────────────────────────────────────────────────────────
  $('.ver_modal').click(function() {
    var id = $(this).val();
    $('#panel_inicio').html('<div class="text-center"><div class="loadingsm"></div></div>');
    $('#panel_inicio').load('clases/modifica/upd-condicion.php', {id: id});
  });

  // ── Cupón ─────────────────────────────────────────────────────────────────
  $('#chk_cupon').on('change', function() {
    $('#dato_cupon').val($(this).is(':checked') ? '1' : '0');
  });

  // ── Días ──────────────────────────────────────────────────────────────────
  function actualizarDias() {
    var sel = [];
    $('.dia-check:checked').each(function() { sel.push($(this).val()); });
    sel.sort(function(a, b) { return a - b; });
    $('#dato_dias').val(sel.join(','));
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
