<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error());
    exit();
}

// Búsqueda AJAX
if (isset($_POST['accion']) && $_POST['accion'] === 'buscar') {
    $buscar = '%' . mysqli_real_escape_string($conexion, $_POST['q']) . '%';
    $sql = "SELECT p.id_productos, p.nombre, p.codigo, p.precio_venta, p.color,
                   t.nombre AS talle
            FROM tb_productos p
            LEFT JOIN tb_talle t ON p.id_talle = t.id_talle
            WHERE p.nombre LIKE '$buscar' OR p.codigo LIKE '$buscar'
            ORDER BY p.nombre ASC
            LIMIT 100";
    $rs  = mysqli_query($conexion, $sql);
    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
        $rows[] = [
            'id'     => $r['id_productos'],
            'nombre' => utf8_encode($r['nombre']),
            'codigo' => $r['codigo'],
            'precio' => number_format((float)$r['precio_venta'], 2, '.', ''),
            'color'  => utf8_encode($r['color']),
            'talle'  => utf8_encode($r['talle']),
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit();
}
?>

<div class="modal-header">
  <h4 class="modal-title">Imprimir Etiquetas de Productos</h4>
</div>
<br>

<!-- Panel QZ Tray -->
<div class="well bs-component" style="padding:10px 15px;">
  <div class="row">

    <div class="col-sm-4">
      <div class="form-group form-group-sm" style="margin-bottom:0;">
        <label class="control-label">
          <span id="etq_dot" style="font-size:16px; color:#d9534f; vertical-align:middle;">●</span>
          &nbsp;QZ Tray&nbsp;
        </label>
        <button type="button" class="btn btn-xs btn-default" id="etq_btn_conectar">Conectar</button>
        <span id="etq_estado" class="text-muted" style="font-size:11px; margin-left:5px;">desconectado</span>
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group form-group-sm" style="margin-bottom:0;">
        <label class="control-label">Impresora&nbsp;</label>
        <input type="text" id="etq_printer" class="form-control input-sm"
               value="XP-470B" style="display:inline-block;width:140px;">
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group form-group-sm" style="margin-bottom:0;">
        <label class="control-label">Rollo (mm)&nbsp;</label>
        <input type="number" id="etq_ancho" class="form-control input-sm"
               value="57" min="20" max="120" style="display:inline-block;width:58px;">
        <span style="vertical-align:middle;">&times;</span>
        <input type="number" id="etq_alto" class="form-control input-sm"
               value="32" min="15" max="200" style="display:inline-block;width:58px;">
      </div>
    </div>

  </div>
</div>

<!-- Buscador + tabla -->
<div class="well bs-component">
  <div class="row">
    <div class="col-lg-12">
      <fieldset>
        <div class="form-group form-group-sm">
          <label class="col-lg-2 control-label">Buscar producto</label>
          <div class="col-lg-5">
            <input type="text" class="form-control" id="etq_buscar"
                   autocomplete="off" placeholder="Nombre o código...">
          </div>
          <div class="col-lg-5">
            <button type="button" class="btn btn-default btn-sm" id="etq_btn_todos">Ver todos</button>
            <button type="button" class="btn btn-primary btn-sm" id="etq_btn_imprimir" disabled>
              <span class="glyphicon glyphicon-print"></span> Imprimir seleccionados
            </button>
          </div>
        </div>
        <div class="form-group form-group-sm">
          <div class="col-lg-offset-2 col-lg-10">
            <span id="etq_contador" class="label label-info">0 productos seleccionados</span>
            &nbsp;<span id="etq_msg" class="label label-default" style="display:none;"></span>
          </div>
        </div>
      </fieldset>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12">

      <div id="etq_loading" class="text-center" style="display:none;">
        <div class="loadingsm"></div>
      </div>

      <table class="table table-hover table-condensed table-bordered"
             id="etq_tabla" style="display:none;">
        <thead>
          <tr>
            <th style="width:36px;text-align:center;">
              <input type="checkbox" id="etq_sel_todos" title="Seleccionar todos">
            </th>
            <th>Código</th><th>Nombre</th><th>Talle</th><th>Color</th><th>Precio</th>
            <th style="width:68px;">Cant.</th>
          </tr>
        </thead>
        <tbody id="etq_tbody"></tbody>
      </table>

      <div id="etq_vacio" class="text-center text-muted"
           style="display:none;padding:20px;">No se encontraron productos.</div>
    </div>
  </div>
</div>

<!-- ===================================================================
     Scripts
=================================================================== -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script>
<script>
(function () {

  /* ── Estado QZ ──────────────────────────── */
  var conectado = false;

  function setEstado(ok, txt) {
    conectado = ok;
    $('#etq_dot').css('color', ok ? '#5cb85c' : '#d9534f');
    $('#etq_estado').text(txt);
    $('#etq_btn_conectar').text(ok ? 'Desconectar' : 'Conectar');
    refrescarBoton();
  }

  function conectar() {
    if (typeof qz === 'undefined') { setEstado(false, 'qz-tray.js no disponible'); return; }
    setEstado(false, 'conectando...');
    qz.websocket.connect()
      .then(function () { setEstado(true, 'conectado'); })
      .catch(function (e) { setEstado(false, 'sin QZ Tray (' + e.message + ')'); });
  }

  $('#etq_btn_conectar').on('click', function () {
    if (conectado) {
      qz.websocket.disconnect().then(function () { setEstado(false, 'desconectado'); });
    } else { conectar(); }
  });

  setTimeout(function () { if (typeof qz !== 'undefined') conectar(); }, 700);

  /* ── Tabla ──────────────────────────────── */
  function contarSeleccionados() {
    return $('#etq_tbody input[type=checkbox]:checked').length;
  }
  function refrescarBoton() {
    $('#etq_btn_imprimir').prop('disabled', !conectado || contarSeleccionados() === 0);
  }
  function actualizarContador() {
    var n = contarSeleccionados();
    $('#etq_contador').text(n + ' producto' + (n !== 1 ? 's' : '') + ' seleccionado' + (n !== 1 ? 's' : ''));
    refrescarBoton();
  }

  function esc(s) { return $('<span>').text(s || '').html(); }

  function renderTabla(lista) {
    var $b = $('#etq_tbody').empty();
    if (!lista || !lista.length) { $('#etq_tabla').hide(); $('#etq_vacio').show(); return; }
    $('#etq_vacio').hide(); $('#etq_tabla').show();
    $.each(lista, function (i, p) {
      $b.append(
        '<tr>' +
        '<td style="text-align:center;vertical-align:middle;">' +
          '<input type="checkbox" class="etq_chk"' +
          ' data-nombre="' + esc(p.nombre) + '" data-codigo="' + esc(p.codigo) + '"' +
          ' data-precio="' + p.precio + '" data-color="' + esc(p.color) + '"' +
          ' data-talle="' + esc(p.talle) + '"></td>' +
        '<td style="vertical-align:middle;font-family:monospace;">' + esc(p.codigo) + '</td>' +
        '<td style="vertical-align:middle;">' + esc(p.nombre) + '</td>' +
        '<td style="vertical-align:middle;">' + esc(p.talle)  + '</td>' +
        '<td style="vertical-align:middle;">' + esc(p.color)  + '</td>' +
        '<td style="vertical-align:middle;">$' + p.precio + '</td>' +
        '<td style="vertical-align:middle;">' +
          '<input type="number" class="form-control input-sm etq_qty"' +
          ' value="1" min="1" max="99" style="width:58px;"></td>' +
        '</tr>'
      );
    });
    actualizarContador();
  }

  function buscar(q) {
    $('#etq_loading').show(); $('#etq_tabla').hide(); $('#etq_vacio').hide();
    $.post('clases/nuevo/etiqueta.php', { accion: 'buscar', q: q }, function (d) {
      $('#etq_loading').hide(); renderTabla(d);
    }, 'json').fail(function () {
      $('#etq_loading').hide();
      $('#etq_vacio').text('Error al consultar la base de datos.').show();
    });
  }

  var timer;
  $('#etq_buscar').on('input', function () {
    clearTimeout(timer);
    var q = $(this).val().trim();
    if (q.length < 2) return;
    timer = setTimeout(function () { buscar(q); }, 350);
  });
  $('#etq_btn_todos').on('click', function () { $('#etq_buscar').val(''); buscar(''); });
  $(document).on('change', '.etq_chk', actualizarContador);
  $(document).on('input',  '.etq_qty', function () { if (+$(this).val() < 1) $(this).val(1); });
  $('#etq_sel_todos').on('change', function () {
    $('.etq_chk').prop('checked', $(this).is(':checked')); actualizarContador();
  });

  /* ── Imprimir con QZ Tray ───────────────── */
  $('#etq_btn_imprimir').on('click', function () {
    if (!conectado) { mostrarMsg('Conectá QZ Tray primero', 'warning'); return; }
    var items = [];
    $('#etq_tbody tr').each(function () {
      var c = $(this).find('.etq_chk');
      if (c.is(':checked')) {
        items.push({
          nombre: c.data('nombre'), codigo: c.data('codigo'),
          precio: c.data('precio'), color:  c.data('color'),
          talle:  c.data('talle'),
          qty: parseInt($(this).find('.etq_qty').val()) || 1
        });
      }
    });
    if (!items.length) return;
    imprimirItems(items);
  });

  function imprimirItems(items) {
    var printer  = $('#etq_printer').val().trim() || 'XP-470B';
    var anchoMM  = parseFloat($('#etq_ancho').val()) || 57;
    var altoMM   = parseFloat($('#etq_alto').val())  || 32;
    var anchoIn  = +(anchoMM / 25.4).toFixed(4);
    var altoIn   = +(altoMM  / 25.4).toFixed(4);

    var total = 0;
    items.forEach(function (p) { total += p.qty; });

    mostrarMsg('Enviando 0 / ' + total + '...', 'info');
    $('#etq_btn_imprimir').prop('disabled', true);

    var config = qz.configs.create(printer, {
      size:         { width: anchoIn, height: altoIn },
      units:        'in',
      scaleContent: true,
      colorType:    'blackwhite',
      copies:       1
    });

    /* Encadenamos un print() por cada unidad */
    var cadena  = Promise.resolve();
    var enviados = 0;

    items.forEach(function (p) {
      for (var i = 0; i < p.qty; i++) {
        (function (prod) {
          cadena = cadena.then(function () {
            var data = [{ type: 'pixel', format: 'html', flavor: 'plain',
                          data: htmlEtiqueta(prod, anchoMM, altoMM) }];
            return qz.print(config, data).then(function () {
              enviados++;
              mostrarMsg('Enviando ' + enviados + ' / ' + total + '...', 'info');
            });
          });
        })(p);
      }
    });

    cadena
      .then(function () {
        mostrarMsg('✓ ' + total + ' etiqueta' + (total !== 1 ? 's' : '') + ' impresa' + (total !== 1 ? 's' : ''), 'success');
        $('#etq_btn_imprimir').prop('disabled', false);
      })
      .catch(function (e) {
        mostrarMsg('Error: ' + e.message, 'danger');
        $('#etq_btn_imprimir').prop('disabled', false);
      });
  }

  /* ── HTML de una etiqueta ───────────────── */
  function htmlEtiqueta(p, anchoMM, altoMM) {
    /* Generar SVG del código de barras */
    var svgNS = 'http://www.w3.org/2000/svg';
    var svgEl = document.createElementNS(svgNS, 'svg');
    svgEl.setAttribute('xmlns', svgNS);
    var bcOK = false;
    try {
      JsBarcode(svgEl, p.codigo, { format:'CODE128', width:1.4,
        height:28, displayValue:false, margin:1 });
      bcOK = true;
    } catch (e) {}

    var bcHtml = bcOK
      ? '<div style="width:100%;text-align:center;">' + svgEl.outerHTML + '</div>'
      : '';

    var variante = [p.talle, p.color].filter(Boolean).join('  ');

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' +
      '* { box-sizing:border-box; margin:0; padding:0; }' +
      'body {' +
        'width:'  + anchoMM + 'mm;' +
        'height:' + altoMM  + 'mm;' +
        'overflow:hidden; font-family:Arial,sans-serif;' +
        'padding:1.5mm 2mm;' +
        'display:flex; flex-direction:column; justify-content:space-between;' +
      '}' +
      '.bc svg { width:100%; height:auto; max-height:10mm; display:block; }' +
      '.sku     { font-size:6pt;   color:#444; font-family:monospace; margin-top:0.5mm; }' +
      '.nombre  { font-size:8pt;   font-weight:bold; color:#111; line-height:1.2; margin:1mm 0 0.5mm; }' +
      '.variante{ font-size:6.5pt; color:#555; }' +
      '.precio  { font-size:9.5pt; font-weight:bold; color:#000; }' +
      '</style></head><body>' +
      '<div class="bc">' + bcHtml + '</div>' +
      '<div class="sku">'     + he(p.codigo)  + '</div>' +
      '<div class="nombre">'  + he(p.nombre)  + '</div>' +
      (variante ? '<div class="variante">' + he(variante) + '</div>' : '') +
      '<div class="precio">$' + p.precio + '</div>' +
      '</body></html>';
  }

  /* ── Helpers ────────────────────────────── */
  function he(s) {
    return String(s || '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function mostrarMsg(txt, tipo) {
    $('#etq_msg')
      .removeClass('label-default label-info label-success label-warning label-danger')
      .addClass('label-' + (tipo || 'default'))
      .text(txt).show();
  }

})();
</script>
