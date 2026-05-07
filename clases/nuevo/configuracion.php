<?php
session_start();
if (!isset($_SESSION['usuario']) || strtolower($_SESSION['tipo_user']) !== 'admin') {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';

// Parámetros disponibles con sus etiquetas y defaults
$parametros = [
    'permite_venta_sin_stock' => [
        'etiqueta'  => 'Permitir venta con stock insuficiente',
        'detalle'   => 'Si está activo, el sistema permite confirmar ventas aunque no haya stock disponible.',
        'tipo'      => 'bool',
        'default'   => '0',
    ],
    'stock_minimo' => [
        'etiqueta'  => 'Stock mínimo (alerta amarilla)',
        'detalle'   => 'Cantidad por debajo de la cual un producto se marca como stock bajo.',
        'tipo'      => 'number',
        'default'   => '5',
    ],
];

// Carga valores actuales para todas las sucursales
$stmt = mysqli_prepare($conexion,
    "SELECT s.id_sucursal, s.nombre, c.clave, c.valor
     FROM tb_sucursal s
     LEFT JOIN tb_configuracion c ON c.id_sucursal = s.id_sucursal
     ORDER BY s.id_sucursal"
);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_nombre, $r_clave, $r_valor);

$sucursales = [];
while (mysqli_stmt_fetch($stmt)) {
    if (!isset($sucursales[$r_id])) {
        $sucursales[$r_id] = ['nombre' => utf8_encode($r_nombre), 'config' => []];
    }
    if ($r_clave !== null) {
        $sucursales[$r_id]['config'][$r_clave] = $r_valor;
    }
}
mysqli_stmt_close($stmt);

$csrf = csrf_token();
?>

<div class="modal-header">
  <h4 class="modal-title">Configuración por sucursal</h4>
</div>
<br>

<div class="well bs-component">
  <div class="row">
    <div class="col-lg-10">

      <?php if (empty($sucursales)): ?>
        <p class="text-muted">No hay sucursales registradas.</p>
      <?php else: ?>

        <?php foreach ($sucursales as $id_suc => $suc): ?>
        <div class="panel panel-default" style="margin-bottom:20px">
          <div class="panel-heading">
            <h4 class="panel-title"><?php echo htmlspecialchars($suc['nombre']); ?></h4>
          </div>
          <div class="panel-body">
            <table class="table table-condensed" style="margin-bottom:0">
              <tbody>
                <?php foreach ($parametros as $clave => $param):
                  $valor_actual = $suc['config'][$clave] ?? $param['default'];
                  $tipo = $param['tipo'];
                ?>
                <tr>
                  <td style="vertical-align:middle; width:60%">
                    <strong><?php echo htmlspecialchars($param['etiqueta']); ?></strong>
                    <br><small class="text-muted"><?php echo htmlspecialchars($param['detalle']); ?></small>
                  </td>
                  <td style="vertical-align:middle; text-align:center">
                    <?php if ($tipo === 'bool'): $checked = ($valor_actual === '1') ? 'checked' : ''; ?>
                    <label class="switch-label">
                      <input type="checkbox"
                             class="config-toggle"
                             data-tipo="bool"
                             data-sucursal="<?php echo $id_suc; ?>"
                             data-clave="<?php echo htmlspecialchars($clave); ?>"
                             data-csrf="<?php echo $csrf; ?>"
                             <?php echo $checked; ?>>
                      <span class="toggle-estado <?php echo $checked ? 'text-success' : 'text-muted'; ?>">
                        <?php echo $checked ? 'Activo' : 'Inactivo'; ?>
                      </span>
                    </label>
                    <?php elseif ($tipo === 'number'): ?>
                    <div class="input-group" style="width:120px; display:inline-table">
                      <input type="number"
                             class="form-control config-number"
                             min="0" step="1"
                             value="<?php echo (int)$valor_actual; ?>"
                             data-tipo="number"
                             data-sucursal="<?php echo $id_suc; ?>"
                             data-clave="<?php echo htmlspecialchars($clave); ?>"
                             data-csrf="<?php echo $csrf; ?>">
                    </div>
                    <?php endif; ?>
                  </td>
                  <td style="vertical-align:middle; width:120px" class="toggle-feedback-<?php echo $id_suc; ?>-<?php echo $clave; ?>">
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endforeach; ?>

      <?php endif; ?>

    </div>
  </div>
</div>

<script type="text/javascript">
$(function() {
  // Toggle bool
  $('.config-toggle').on('change', function() {
    var $cb       = $(this);
    var sucursal  = $cb.data('sucursal');
    var clave     = $cb.data('clave');
    var valor     = $cb.is(':checked') ? '1' : '0';
    var csrf      = $cb.data('csrf');
    var $estado   = $cb.siblings('.toggle-estado');
    var $feedback = $('.toggle-feedback-' + sucursal + '-' + clave);

    $feedback.html('<span class="text-muted"><small>Guardando...</small></span>');

    $.ajax({
      url      : 'clases/guardar/configuracion.php',
      type     : 'post',
      dataType : 'json',
      data     : {id_sucursal: sucursal, clave: clave, valor: valor, csrf_token: csrf},
      success  : function(data) {
        if (data.success === 'true') {
          $estado.text(valor === '1' ? 'Activo' : 'Inactivo')
                 .removeClass('text-success text-muted text-danger')
                 .addClass(valor === '1' ? 'text-success' : 'text-muted');
          $feedback.html('<span class="text-success"><small>✓ Guardado</small></span>');
          setTimeout(function() { $feedback.html(''); }, 2000);
        } else {
          $feedback.html('<span class="text-danger"><small>Error</small></span>');
          $cb.prop('checked', !$cb.is(':checked'));
        }
      },
      error: function() {
        $feedback.html('<span class="text-danger"><small>Error de red</small></span>');
        $cb.prop('checked', !$cb.is(':checked'));
      }
    });
  });

  // Input numérico — guarda al salir del campo (blur)
  var numberTimer = {};
  $('.config-number').on('blur', function() {
    var $inp     = $(this);
    var sucursal = $inp.data('sucursal');
    var clave    = $inp.data('clave');
    var valor    = parseInt($inp.val()) || 0;
    var csrf     = $inp.data('csrf');
    var $feedback = $('.toggle-feedback-' + sucursal + '-' + clave);

    if (valor < 0) { $inp.val(0); valor = 0; }
    $feedback.html('<span class="text-muted"><small>Guardando...</small></span>');

    $.ajax({
      url      : 'clases/guardar/configuracion.php',
      type     : 'post',
      dataType : 'json',
      data     : {id_sucursal: sucursal, clave: clave, valor: valor, csrf_token: csrf},
      success  : function(data) {
        if (data.success === 'true') {
          $feedback.html('<span class="text-success"><small>✓ Guardado</small></span>');
          setTimeout(function() { $feedback.html(''); }, 2000);
        } else {
          $feedback.html('<span class="text-danger"><small>Error</small></span>');
        }
      },
      error: function() {
        $feedback.html('<span class="text-danger"><small>Error de red</small></span>');
      }
    });
  });
});
</script>
