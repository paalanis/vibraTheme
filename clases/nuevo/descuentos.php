<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php'); exit();
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) { die('Error de conexión'); }
require_once '../../conexion/descuentos.php';

// ── AJAX: lista de marcas ─────────────────────────────────────────────────
if (isset($_POST['accion']) && $_POST['accion'] === 'marcas') {
    $rs = mysqli_query($conexion, "SELECT id_marca AS id, nombre FROM tb_marca ORDER BY nombre ASC");
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    header('Content-Type: application/json');
    echo json_encode($out); exit();
}

// ── AJAX: lista de tipos ──────────────────────────────────────────────────
if (isset($_POST['accion']) && $_POST['accion'] === 'tipos') {
    $rs = mysqli_query($conexion, "SELECT id_tipo AS id, nombre FROM tb_tipo ORDER BY nombre ASC");
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    header('Content-Type: application/json');
    echo json_encode($out); exit();
}

// ── AJAX: buscar producto ─────────────────────────────────────────────────
if (isset($_POST['accion']) && $_POST['accion'] === 'producto_buscar') {
    $q = trim($_POST['q'] ?? '');
    if (strlen($q) < 2) { header('Content-Type: application/json'); echo '[]'; exit(); }
    $buscar = '%' . mysqli_real_escape_string($conexion, $q) . '%';
    $sql = "SELECT p.id_productos AS id,
                   CONCAT(p.nombre, ' — ', IFNULL(m.nombre,''), ' ', IFNULL(t.nombre,'')) AS label
            FROM tb_productos p
            LEFT JOIN tb_marca m ON m.id_marca = p.id_marca
            LEFT JOIN tb_tipo  t ON t.id_tipo  = p.id_tipo
            WHERE p.nombre LIKE '$buscar' OR p.codigo LIKE '$buscar'
            ORDER BY p.nombre ASC LIMIT 30";
    $rs = mysqli_query($conexion, $sql);
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) {
        $out[] = ['id' => $r['id'], 'label' => mb_convert_encoding($r['label'], 'UTF-8', 'UTF-8')];
    }
    header('Content-Type: application/json');
    echo json_encode($out); exit();
}

// ── AJAX: cargar datos de una regla para editar ───────────────────────────
if (isset($_POST['accion']) && $_POST['accion'] === 'cargar') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo '{}'; exit(); }
    $stmt = mysqli_prepare($conexion,
        "SELECT id_descuento, nombre, tipo_alcance, id_alcance, porcentaje,
                fecha_desde, fecha_hasta, activo, id_sucursal
         FROM tb_descuentos WHERE id_descuento = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $r_id = $r_nom = $r_tipo = $r_alc = $r_pct = $r_fdes = $r_fhas = $r_act = $r_suc = null;
    mysqli_stmt_bind_result($stmt, $r_id, $r_nom, $r_tipo, $r_alc, $r_pct, $r_fdes, $r_fhas, $r_act, $r_suc);
    $found = (bool)mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    if (!$found) { echo '{}'; exit(); }
    header('Content-Type: application/json');
    echo json_encode([
        'id_descuento' => $r_id,
        'nombre'       => $r_nom,
        'tipo_alcance' => $r_tipo,
        'id_alcance'   => $r_alc,
        'porcentaje'   => $r_pct,
        'fecha_desde'  => $r_fdes ?? '',
        'fecha_hasta'  => $r_fhas ?? '',
        'activo'       => $r_act,
        'id_sucursal'  => $r_suc,
    ]);
    exit();
}

// ── HTML: datos para la vista ─────────────────────────────────────────────
$hoy       = date('Y-m-d');
$conflictos = descuento_conflictos($conexion);

// Lista de reglas con JOIN para obtener nombre del alcance
$sql_lista = "
    SELECT d.id_descuento, d.nombre, d.tipo_alcance, d.id_alcance, d.porcentaje,
           d.fecha_desde, d.fecha_hasta, d.activo, d.creado_en,
           CASE d.tipo_alcance
               WHEN 'global'   THEN 'Todos los productos'
               WHEN 'marca'    THEN IFNULL(m.nombre, CONCAT('Marca #', d.id_alcance))
               WHEN 'tipo'     THEN IFNULL(t.nombre, CONCAT('Tipo #',  d.id_alcance))
               WHEN 'producto' THEN IFNULL(p.nombre, CONCAT('Prod. #', d.id_alcance))
               ELSE '—'
           END AS alcance_nombre
    FROM tb_descuentos d
    LEFT JOIN tb_marca     m ON d.tipo_alcance = 'marca'    AND m.id_marca     = d.id_alcance
    LEFT JOIN tb_tipo      t ON d.tipo_alcance = 'tipo'     AND t.id_tipo      = d.id_alcance
    LEFT JOIN tb_productos p ON d.tipo_alcance = 'producto' AND p.id_productos = d.id_alcance
    ORDER BY d.activo DESC, d.creado_en DESC
";
$rs_lista = mysqli_query($conexion, $sql_lista);
$reglas   = [];
while ($r = mysqli_fetch_assoc($rs_lista)) $reglas[] = $r;

function estado_regla(array $r, string $hoy): array {
    if (!$r['activo'])                                     return ['lbl' => 'Pausada',    'cls' => 'default'];
    if ($r['fecha_hasta'] && $r['fecha_hasta'] < $hoy)    return ['lbl' => 'Vencida',    'cls' => 'danger'];
    if ($r['fecha_desde'] && $r['fecha_desde'] > $hoy)    return ['lbl' => 'Programada', 'cls' => 'info'];
    return ['lbl' => 'Activa', 'cls' => 'success'];
}

$tipo_label = ['global' => 'Global', 'marca' => 'Marca', 'tipo' => 'Tipo/Rubro', 'producto' => 'Producto'];

// Contar activas ahora
$activas_ahora = 0;
foreach ($reglas as $r) {
    $e = estado_regla($r, $hoy);
    if ($e['lbl'] === 'Activa') $activas_ahora++;
}
?>

<div class="modal-header">
    <h4 class="modal-title">Descuentos y Ofertas</h4>
</div>
<br>

<?php if (!empty($conflictos)): ?>
<div class="alert alert-warning alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <strong><span class="glyphicon glyphicon-warning-sign"></span> Conflicto detectado:</strong>
    <?php foreach ($conflictos as $c): ?>
        <br>Hay <strong><?php echo $c['cant']; ?> reglas activas simultáneas</strong>
        para el mismo alcance
        (<em><?php echo htmlspecialchars($c['detalle'], ENT_QUOTES, 'UTF-8'); ?></em>).
        Solo aplicará la de <strong>mayor porcentaje</strong>.
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Panel superior: resumen + botón nueva regla -->
<div class="well bs-component" style="padding:10px 15px;">
    <div class="row">
        <div class="col-sm-8">
            <span class="label label-success" style="font-size:13px; padding:5px 10px;">
                <?php echo $activas_ahora; ?> regla<?php echo $activas_ahora !== 1 ? 's' : ''; ?> activa<?php echo $activas_ahora !== 1 ? 's' : ''; ?> ahora
            </span>
            &nbsp;
            <span class="label label-default" style="font-size:13px; padding:5px 10px;">
                <?php echo count($reglas); ?> total
            </span>
        </div>
        <div class="col-sm-4 text-right">
            <button type="button" class="btn btn-primary btn-sm" id="desc_btn_nueva">
                <span class="glyphicon glyphicon-plus"></span> Nueva regla
            </button>
        </div>
    </div>
</div>

<!-- Tabla de reglas -->
<div class="well bs-component">
    <?php if (empty($reglas)): ?>
        <p class="text-muted text-center" style="padding:20px;">
            No hay reglas configuradas. Creá la primera usando el botón <em>Nueva regla</em>.
        </p>
    <?php else: ?>
    <table class="table table-hover table-condensed table-bordered" id="desc_tabla">
        <thead>
            <tr class="active">
                <th>Nombre</th>
                <th>Alcance</th>
                <th>Sobre</th>
                <th style="text-align:center;">%</th>
                <th>Desde</th>
                <th>Hasta</th>
                <th style="text-align:center;">Estado</th>
                <th style="text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reglas as $r):
            $est = estado_regla($r, $hoy);
            $tlbl = $tipo_label[$r['tipo_alcance']] ?? $r['tipo_alcance'];
        ?>
            <tr data-id="<?php echo (int)$r['id_descuento']; ?>">
                <td><?php echo htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($tlbl, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><em><?php echo htmlspecialchars($r['alcance_nombre'], ENT_QUOTES, 'UTF-8'); ?></em></td>
                <td style="text-align:center; font-weight:bold;">
                    <?php echo number_format((float)$r['porcentaje'], 1); ?>%
                </td>
                <td><?php echo $r['fecha_desde'] ? date('d/m/Y', strtotime($r['fecha_desde'])) : '<span class="text-muted">—</span>'; ?></td>
                <td><?php echo $r['fecha_hasta'] ? date('d/m/Y', strtotime($r['fecha_hasta'])) : '<span class="text-muted">—</span>'; ?></td>
                <td style="text-align:center;">
                    <span class="label label-<?php echo $est['cls']; ?>">
                        <?php echo $est['lbl']; ?>
                    </span>
                </td>
                <td style="text-align:center; white-space:nowrap;">
                    <button type="button"
                            class="btn btn-xs btn-default desc_btn_editar"
                            title="Editar"
                            data-id="<?php echo (int)$r['id_descuento']; ?>">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                    <button type="button"
                            class="btn btn-xs <?php echo $r['activo'] ? 'btn-warning' : 'btn-success'; ?> desc_btn_toggle"
                            title="<?php echo $r['activo'] ? 'Pausar' : 'Activar'; ?>"
                            data-id="<?php echo (int)$r['id_descuento']; ?>"
                            data-activo="<?php echo (int)$r['activo']; ?>">
                        <span class="glyphicon glyphicon-<?php echo $r['activo'] ? 'pause' : 'play'; ?>"></span>
                    </button>
                    <button type="button"
                            class="btn btn-xs btn-danger desc_btn_eliminar"
                            title="Eliminar"
                            data-id="<?php echo (int)$r['id_descuento']; ?>"
                            data-nombre="<?php echo htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="glyphicon glyphicon-trash"></span>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Formulario de creación / edición (oculto por defecto) -->
<div id="desc_frm_panel" style="display:none;">
<div class="well bs-component">
    <h5 id="desc_frm_titulo" style="margin-top:0; margin-bottom:15px;">
        <strong>Nueva regla de descuento</strong>
    </h5>

    <input type="hidden" id="desc_id_descuento" value="0">

    <div class="form-horizontal">

      <div class="form-group form-group-sm">
        <label class="col-sm-2 control-label">Nombre <span class="text-danger">*</span></label>
        <div class="col-sm-6">
          <input type="text" class="form-control" id="desc_nombre"
                 placeholder="Ej: Jueves de Jean, Black Friday, Promo Nike..." maxlength="100">
        </div>
      </div>

      <div class="form-group form-group-sm">
        <label class="col-sm-2 control-label">Descuento <span class="text-danger">*</span></label>
        <div class="col-sm-3">
          <div class="input-group input-group-sm">
            <input type="number" class="form-control" id="desc_porcentaje"
                   min="0.01" max="100" step="0.01" placeholder="Ej: 10, 20, 30">
            <span class="input-group-addon">%</span>
          </div>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <label class="col-sm-2 control-label">Alcance <span class="text-danger">*</span></label>
        <div class="col-sm-4">
          <select class="form-control" id="desc_tipo_alcance">
            <option value="global">Global — todos los productos</option>
            <option value="marca">Por Marca</option>
            <option value="tipo">Por Tipo / Rubro</option>
            <option value="producto">Producto específico</option>
          </select>
        </div>
      </div>

      <!-- Selector dinámico de alcance -->
      <div class="form-group form-group-sm" id="desc_alcance_bloque" style="display:none;">
        <label class="col-sm-2 control-label" id="desc_alcance_label">Marca</label>
        <div class="col-sm-4">
          <select class="form-control" id="desc_alcance_select" style="display:none;">
            <option value="">— Seleccionar —</option>
          </select>
          <div id="desc_alcance_producto" style="display:none;">
            <input type="text" class="form-control" id="desc_producto_buscar"
                   placeholder="Buscar por nombre o código..." autocomplete="off">
            <input type="hidden" id="desc_id_alcance" value="">
            <div id="desc_producto_resultados" style="margin-top:4px;"></div>
          </div>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <label class="col-sm-2 control-label">Vigencia</label>
        <div class="col-sm-8">
          <div class="row">
            <div class="col-sm-4">
              <div class="input-group input-group-sm">
                <span class="input-group-addon">Desde</span>
                <input type="date" class="form-control" id="desc_fecha_desde">
              </div>
            </div>
            <div class="col-sm-4">
              <div class="input-group input-group-sm">
                <span class="input-group-addon">Hasta</span>
                <input type="date" class="form-control" id="desc_fecha_hasta">
              </div>
            </div>
            <div class="col-sm-4">
              <button type="button" class="btn btn-info btn-sm btn-block" id="desc_btn_hoy">
                <span class="glyphicon glyphicon-flash"></span> Solo por hoy
              </button>
            </div>
          </div>
          <p class="help-block" style="margin-top:5px; margin-bottom:0;">
            Sin fechas = vigencia permanente. Con fechas = se activa y desactiva sola.
          </p>
        </div>
      </div>

      <div class="form-group form-group-sm">
        <div class="col-sm-offset-2 col-sm-10">
          <div class="checkbox">
            <label>
              <input type="checkbox" id="desc_activo" checked>
              Activa inmediatamente
            </label>
          </div>
        </div>
      </div>

    </div><!-- /form-horizontal -->

    <div id="desc_frm_alerta" style="display:none; margin-top:10px;"></div>

    <div style="margin-top:15px;">
        <button type="button" class="btn btn-primary btn-sm" id="desc_btn_guardar">
            <span class="glyphicon glyphicon-floppy-disk"></span> Guardar
        </button>
        <button type="button" class="btn btn-default btn-sm" id="desc_btn_cancelar">
            Cancelar
        </button>
        <span id="desc_guardando" class="text-muted" style="display:none; margin-left:10px;">
            <div class="loadingsm" style="display:inline-block;"></div> Guardando...
        </span>
    </div>
</div>
</div><!-- /desc_frm_panel -->

<div id="desc_msg" class="alert" style="display:none; margin-top:10px;"></div>

<script>
(function () {
'use strict';
var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

// ── Helpers ────────────────────────────────────────────────────────────────
function esc(s) { return $('<span>').text(s || '').html(); }
function hoy()  { return new Date().toISOString().slice(0,10); }

function msg(txt, tipo) {
    $('#desc_msg')
        .removeClass('alert-success alert-danger alert-warning alert-info')
        .addClass('alert-' + (tipo||'info'))
        .html(txt).show();
    setTimeout(function(){ $('#desc_msg').fadeOut(); }, 4000);
}

// ── Cargar marcas y tipos en cache ─────────────────────────────────────────
var cacheMarcas = null, cacheTipos = null;

function cargarMarcas(cb) {
    if (cacheMarcas) { cb(cacheMarcas); return; }
    $.post('clases/nuevo/descuentos.php', {accion:'marcas'}, function(d){
        cacheMarcas = d; cb(d);
    }, 'json');
}
function cargarTipos(cb) {
    if (cacheTipos) { cb(cacheTipos); return; }
    $.post('clases/nuevo/descuentos.php', {accion:'tipos'}, function(d){
        cacheTipos = d; cb(d);
    }, 'json');
}

// ── Cambio de tipo_alcance — actualiza selector dinámico ──────────────────
$('#desc_tipo_alcance').on('change', function() {
    var tipo = $(this).val();
    $('#desc_id_alcance').val('');
    $('#desc_alcance_select').val('').find('option:gt(0)').remove();
    $('#desc_alcance_producto').hide();
    $('#desc_alcance_select').hide();

    if (tipo === 'global') {
        $('#desc_alcance_bloque').hide();
        return;
    }
    $('#desc_alcance_bloque').show();

    if (tipo === 'marca') {
        $('#desc_alcance_label').text('Marca');
        cargarMarcas(function(lista) {
            var $sel = $('#desc_alcance_select').show();
            $.each(lista, function(i, m) {
                $sel.append('<option value="'+m.id+'">'+esc(m.nombre)+'</option>');
            });
        });
    } else if (tipo === 'tipo') {
        $('#desc_alcance_label').text('Tipo / Rubro');
        cargarTipos(function(lista) {
            var $sel = $('#desc_alcance_select').show();
            $.each(lista, function(i, t) {
                $sel.append('<option value="'+t.id+'">'+esc(t.nombre)+'</option>');
            });
        });
    } else if (tipo === 'producto') {
        $('#desc_alcance_label').text('Producto');
        $('#desc_alcance_producto').show();
    }
});

// El select de marca/tipo actualiza el hidden id_alcance
$('#desc_alcance_select').on('change', function() {
    $('#desc_id_alcance').val($(this).val());
});

// Búsqueda de producto específico
var timerProd = null;
$('#desc_producto_buscar').on('input', function() {
    clearTimeout(timerProd);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#desc_producto_resultados').empty(); return; }
    timerProd = setTimeout(function() {
        $.post('clases/nuevo/descuentos.php', {accion:'producto_buscar', q:q},
        function(lista) {
            var $div = $('#desc_producto_resultados').empty();
            if (!lista.length) { $div.html('<p class="text-muted" style="font-size:12px;">Sin resultados</p>'); return; }
            var html = '<div class="list-group" style="max-height:150px;overflow-y:auto;">';
            $.each(lista, function(i, p) {
                html += '<a href="#" class="list-group-item list-group-item-condensed desc_prod_item" ' +
                        'data-id="'+p.id+'" data-label="'+esc(p.label)+'" style="padding:5px 10px;font-size:12px;">' +
                        esc(p.label) + '</a>';
            });
            html += '</div>';
            $div.html(html);
        }, 'json');
    }, 300);
});

$(document).on('click', '.desc_prod_item', function(e) {
    e.preventDefault();
    $('#desc_id_alcance').val($(this).data('id'));
    $('#desc_producto_buscar').val($(this).data('label'));
    $('#desc_producto_resultados').empty();
});

// ── Botón "Solo por hoy" ───────────────────────────────────────────────────
$('#desc_btn_hoy').on('click', function() {
    var h = hoy();
    $('#desc_fecha_desde').val(h);
    $('#desc_fecha_hasta').val(h);
});

// ── Mostrar / ocultar formulario ───────────────────────────────────────────
$('#desc_btn_nueva').on('click', function() {
    limpiarFormulario();
    $('#desc_frm_titulo').html('<strong>Nueva regla de descuento</strong>');
    $('#desc_frm_panel').slideDown(200);
    $('#desc_nombre').focus();
});
$('#desc_btn_cancelar').on('click', function() {
    $('#desc_frm_panel').slideUp(200);
});

function limpiarFormulario() {
    $('#desc_id_descuento').val('0');
    $('#desc_nombre').val('');
    $('#desc_porcentaje').val('');
    $('#desc_tipo_alcance').val('global').trigger('change');
    $('#desc_fecha_desde').val('');
    $('#desc_fecha_hasta').val('');
    $('#desc_activo').prop('checked', true);
    $('#desc_frm_alerta').hide();
}

// ── Editar ─────────────────────────────────────────────────────────────────
$(document).on('click', '.desc_btn_editar', function() {
    var id = $(this).data('id');
    $.post('clases/nuevo/descuentos.php', {accion:'cargar', id:id}, function(d) {
        if (!d || !d.id_descuento) { msg('No se pudo cargar la regla.','danger'); return; }
        limpiarFormulario();
        $('#desc_id_descuento').val(d.id_descuento);
        $('#desc_nombre').val(d.nombre);
        $('#desc_porcentaje').val(d.porcentaje);
        $('#desc_fecha_desde').val(d.fecha_desde || '');
        $('#desc_fecha_hasta').val(d.fecha_hasta || '');
        $('#desc_activo').prop('checked', d.activo == 1);
        // Tipo alcance y selector dinámico
        // Manejar selects async con callback explícito (evita race condition de setTimeout)
        if (d.tipo_alcance === 'global' || !d.id_alcance) {
            $('#desc_tipo_alcance').val(d.tipo_alcance).trigger('change');
        } else if (d.tipo_alcance === 'producto') {
            $('#desc_tipo_alcance').val('producto').trigger('change');
            $('#desc_id_alcance').val(d.id_alcance);
            $('#desc_producto_buscar').val('Producto #' + d.id_alcance);
        } else if (d.tipo_alcance === 'marca') {
            // cargar opciones y luego seleccionar
            $('#desc_tipo_alcance').val('marca');
            $('#desc_alcance_bloque').show();
            $('#desc_alcance_label').text('Marca');
            cargarMarcas(function(lista) {
                var $sel = $('#desc_alcance_select').show().find('option:gt(0)').remove().end();
                $.each(lista, function(i, m) {
                    $sel.append('<option value="'+m.id+'">'+esc(m.nombre)+'</option>');
                });
                $sel.val(d.id_alcance);
                $('#desc_id_alcance').val(d.id_alcance);
            });
        } else if (d.tipo_alcance === 'tipo') {
            $('#desc_tipo_alcance').val('tipo');
            $('#desc_alcance_bloque').show();
            $('#desc_alcance_label').text('Tipo / Rubro');
            cargarTipos(function(lista) {
                var $sel = $('#desc_alcance_select').show().find('option:gt(0)').remove().end();
                $.each(lista, function(i, t) {
                    $sel.append('<option value="'+t.id+'">'+esc(t.nombre)+'</option>');
                });
                $sel.val(d.id_alcance);
                $('#desc_id_alcance').val(d.id_alcance);
            });
        }
        $('#desc_frm_titulo').html('<strong>Editar regla</strong>');
        $('#desc_frm_panel').slideDown(200);
        $('#desc_nombre').focus();
    }, 'json');
});

// ── Toggle activo/pausado ─────────────────────────────────────────────────
$(document).on('click', '.desc_btn_toggle', function() {
    var $btn = $(this);
    var id   = $btn.data('id');
    $.post('clases/guardar/descuento.php',
        {accion:'toggle', id_descuento:id, csrf_token: csrfToken},
        function(d) {
            if (d.success) {
                // Recargar el panel completo para reflejar nuevo estado
                setTimeout(function(){
                    $('#panel_inicio').load('clases/nuevo/descuentos.php');
                }, 300);
            } else {
                msg('Error al cambiar estado.','danger');
            }
        }, 'json');
});

// ── Eliminar ───────────────────────────────────────────────────────────────
$(document).on('click', '.desc_btn_eliminar', function() {
    var id     = $(this).data('id');
    var nombre = $(this).data('nombre');
    if (!confirm('¿Eliminar la regla "' + nombre + '"?\nLos descuentos ya aplicados en ventas no se modifican.')) return;
    $.post('clases/guardar/descuento.php',
        {accion:'eliminar', id_descuento:id, csrf_token: csrfToken},
        function(d) {
            if (d.success) {
                $('tr[data-id="' + id + '"]').fadeOut(300, function(){ $(this).remove(); });
                msg('Regla eliminada.','success');
            } else {
                msg('Error al eliminar.','danger');
            }
        }, 'json');
});

// ── Guardar (crear / actualizar) ───────────────────────────────────────────
$('#desc_btn_guardar').on('click', function() {
    var id      = parseInt($('#desc_id_descuento').val()) || 0;
    var nombre  = $.trim($('#desc_nombre').val());
    var pct     = parseFloat($('#desc_porcentaje').val());
    var tipo    = $('#desc_tipo_alcance').val();
    var alcance = tipo === 'global' ? '' : (
        tipo === 'producto'
            ? $('#desc_id_alcance').val()
            : $('#desc_alcance_select').val()
    );
    var desde   = $('#desc_fecha_desde').val();
    var hasta   = $('#desc_fecha_hasta').val();
    var activo  = $('#desc_activo').is(':checked') ? 1 : 0;

    // Validaciones básicas
    $('#desc_frm_alerta').hide();
    if (!nombre) {
        showFrmAlert('Ingresá un nombre para la regla.', 'warning'); return;
    }
    if (isNaN(pct) || pct <= 0 || pct > 100) {
        showFrmAlert('El porcentaje debe estar entre 0.01 y 100.', 'warning'); return;
    }
    if (tipo !== 'global' && !alcance) {
        showFrmAlert('Seleccioná el alcance de la regla.', 'warning'); return;
    }
    if (desde && hasta && desde > hasta) {
        showFrmAlert('La fecha "Desde" no puede ser posterior a "Hasta".', 'warning'); return;
    }

    $('#desc_btn_guardar').prop('disabled', true);
    $('#desc_guardando').show();

    var accion = id > 0 ? 'actualizar' : 'crear';
    var data = {
        accion       : accion,
        id_descuento : id,
        nombre       : nombre,
        tipo_alcance : tipo,
        id_alcance   : alcance,
        porcentaje   : pct,
        fecha_desde  : desde,
        fecha_hasta  : hasta,
        activo       : activo,
        csrf_token   : csrfToken
    };

    $.post('clases/guardar/descuento.php', data, function(d) {
        $('#desc_btn_guardar').prop('disabled', false);
        $('#desc_guardando').hide();
        if (d && d.success) {
            $('#desc_frm_panel').slideUp(200);
            msg('Regla guardada correctamente.', 'success');
            setTimeout(function(){
                $('#panel_inicio').load('clases/nuevo/descuentos.php');
            }, 800);
        } else {
            showFrmAlert('Error: ' + (d && d.error ? d.error : 'No se pudo guardar.'), 'danger');
        }
    }, 'json').fail(function() {
        $('#desc_btn_guardar').prop('disabled', false);
        $('#desc_guardando').hide();
        showFrmAlert('Error de comunicación con el servidor.', 'danger');
    });
});

function showFrmAlert(txt, tipo) {
    $('#desc_frm_alerta')
        .removeClass('alert-danger alert-warning alert-info alert-success')
        .addClass('alert alert-' + tipo)
        .text(txt).show();
}

})(); // IIFE
</script>
