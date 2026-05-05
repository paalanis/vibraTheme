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

// Búsqueda AJAX: devuelve JSON con productos
if (isset($_POST['accion']) && $_POST['accion'] === 'buscar') {
    $buscar = '%' . mysqli_real_escape_string($conexion, $_POST['q']) . '%';
    $sql = "SELECT p.id_productos, p.nombre, p.codigo, p.precio_venta, p.color,
                   t.nombre AS talle
            FROM tb_productos p
            LEFT JOIN tb_talle t ON p.id_talle = t.id_talle
            WHERE p.nombre LIKE '$buscar' OR p.codigo LIKE '$buscar'
            ORDER BY p.nombre ASC
            LIMIT 60";
    $rs = mysqli_query($conexion, $sql);
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

<div class="well bs-component">
  <div class="row">
    <div class="col-lg-12">
      <fieldset>

        <!-- Buscador -->
        <div class="form-group form-group-sm">
          <label class="col-lg-2 control-label">Buscar producto</label>
          <div class="col-lg-6">
            <input type="text" class="form-control" id="etq_buscar"
                   autocomplete="off" placeholder="Nombre o código...">
          </div>
          <div class="col-lg-4">
            <button type="button" class="btn btn-default btn-sm" id="etq_btn_todos">
              Ver todos
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="etq_btn_imprimir" disabled>
              <span class="glyphicon glyphicon-print"></span> Imprimir seleccionados
            </button>
          </div>
        </div>

        <!-- Contador seleccionados -->
        <div class="form-group form-group-sm">
          <div class="col-lg-offset-2 col-lg-10">
            <span id="etq_contador" class="label label-info">0 productos seleccionados</span>
          </div>
        </div>

      </fieldset>
    </div>
  </div>

  <!-- Tabla de resultados -->
  <div class="row">
    <div class="col-lg-12">
      <div id="etq_loading" class="text-center" style="display:none;">
        <div class="loadingsm"></div>
      </div>

      <table class="table table-hover table-condensed table-bordered" id="etq_tabla" style="display:none;">
        <thead>
          <tr>
            <th style="width:40px; text-align:center;">
              <input type="checkbox" id="etq_sel_todos" title="Seleccionar todos">
            </th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Talle</th>
            <th>Color</th>
            <th>Precio</th>
            <th style="width:80px;">Cantidad</th>
          </tr>
        </thead>
        <tbody id="etq_tbody"></tbody>
      </table>

      <div id="etq_vacio" class="text-center text-muted" style="display:none; padding:20px;">
        No se encontraron productos.
      </div>
    </div>
  </div>
</div>

<!-- =====================================================================
     VENTANA DE IMPRESIÓN  (se abre en nueva pestaña)
     ===================================================================== -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
(function () {

  /* ── helpers ── */
  function mostrarContador() {
    var n = $('#etq_tbody input[type=checkbox]:checked').length;
    $('#etq_contador').text(n + ' producto' + (n !== 1 ? 's' : '') + ' seleccionado' + (n !== 1 ? 's' : ''));
    $('#etq_btn_imprimir').prop('disabled', n === 0);
  }

  function renderTabla(productos) {
    var $tbody = $('#etq_tbody').empty();
    if (!productos || productos.length === 0) {
      $('#etq_tabla').hide();
      $('#etq_vacio').show();
      return;
    }
    $('#etq_vacio').hide();
    $('#etq_tabla').show();
    $.each(productos, function (i, p) {
      var fila = '<tr>' +
        '<td style="text-align:center;vertical-align:middle;">' +
          '<input type="checkbox" class="etq_chk" data-id="' + p.id + '"' +
          ' data-nombre="' + $('<span>').text(p.nombre).html() + '"' +
          ' data-codigo="' + $('<span>').text(p.codigo).html() + '"' +
          ' data-precio="' + p.precio + '"' +
          ' data-color="' + $('<span>').text(p.color).html() + '"' +
          ' data-talle="' + $('<span>').text(p.talle).html() + '">' +
        '</td>' +
        '<td style="vertical-align:middle;font-family:monospace;">' + $('<span>').text(p.codigo).html() + '</td>' +
        '<td style="vertical-align:middle;">' + $('<span>').text(p.nombre).html() + '</td>' +
        '<td style="vertical-align:middle;">' + $('<span>').text(p.talle).html() + '</td>' +
        '<td style="vertical-align:middle;">' + $('<span>').text(p.color).html() + '</td>' +
        '<td style="vertical-align:middle;">$' + p.precio + '</td>' +
        '<td style="vertical-align:middle;">' +
          '<input type="number" class="form-control input-sm etq_qty" value="1" min="1" max="99" style="width:64px;">' +
        '</td>' +
      '</tr>';
      $tbody.append(fila);
    });
    mostrarContador();
  }

  function buscar(q) {
    $('#etq_loading').show();
    $('#etq_tabla').hide();
    $('#etq_vacio').hide();
    $.post(window.location.pathname.replace(/index2\.php.*/, '') + 'clases/nuevo/etiqueta.php',
      { accion: 'buscar', q: q },
      function (data) {
        $('#etq_loading').hide();
        renderTabla(data);
      }, 'json'
    ).fail(function () {
      $('#etq_loading').hide();
      $('#etq_vacio').text('Error al cargar productos.').show();
    });
  }

  /* ── eventos ── */
  var timerBuscar;
  $('#etq_buscar').on('input', function () {
    clearTimeout(timerBuscar);
    var q = $(this).val().trim();
    if (q.length < 2) return;
    timerBuscar = setTimeout(function () { buscar(q); }, 350);
  });

  $('#etq_btn_todos').on('click', function () {
    $('#etq_buscar').val('');
    buscar('');
  });

  $(document).on('change', '.etq_chk', mostrarContador);
  $(document).on('input', '.etq_qty', function () {
    if ($(this).val() < 1) $(this).val(1);
  });

  $('#etq_sel_todos').on('change', function () {
    $('.etq_chk').prop('checked', $(this).is(':checked'));
    mostrarContador();
  });

  /* ── imprimir ── */
  $('#etq_btn_imprimir').on('click', function () {
    var items = [];
    $('#etq_tbody tr').each(function () {
      var chk = $(this).find('.etq_chk');
      if (chk.is(':checked')) {
        items.push({
          nombre: chk.data('nombre'),
          codigo: chk.data('codigo'),
          precio: chk.data('precio'),
          color:  chk.data('color'),
          talle:  chk.data('talle'),
          qty:    parseInt($(this).find('.etq_qty').val()) || 1
        });
      }
    });
    if (items.length === 0) return;
    abrirVentanaImpresion(items);
  });

  /* ── ventana de impresión ── */
  function abrirVentanaImpresion(items) {

    /* Construir etiquetas HTML (una por unidad) */
    var etiquetas = '';
    items.forEach(function (p) {
      for (var i = 0; i < p.qty; i++) {
        var svgId = 'bc_' + Math.random().toString(36).slice(2);
        etiquetas +=
          '<div class="etiqueta">' +
            '<svg class="bc-svg" id="' + svgId + '" data-codigo="' + htmlEsc(p.codigo) + '"></svg>' +
            '<div class="sku">' + htmlEsc(p.codigo) + '</div>' +
            '<div class="nombre">' + htmlEsc(p.nombre) + '</div>' +
            '<div class="variante">' + htmlEsc(p.talle) + (p.color ? '  ' + htmlEsc(p.color) : '') + '</div>' +
            '<div class="precio">$' + p.precio + '</div>' +
          '</div>';
      }
    });

    var html = '<!DOCTYPE html><html lang="es"><head>' +
      '<meta charset="utf-8">' +
      '<title>Etiquetas VIBRA</title>' +
      '<style>' +
        '@page { margin: 4mm; }' +
        'body { margin:0; font-family: Arial, sans-serif; }' +
        '.grilla { display:flex; flex-wrap:wrap; gap:4mm; padding:2mm; }' +
        '.etiqueta {' +
          'width:57mm; min-height:32mm;' +
          'border:0.5px solid #ccc;' +
          'border-radius:2px;' +
          'padding:3mm 3mm 2mm;' +
          'box-sizing:border-box;' +
          'display:flex; flex-direction:column; align-items:flex-start;' +
          'background:#fff;' +
          'page-break-inside:avoid;' +
        '}' +
        '.etiqueta .bc-svg { width:100%; max-height:30px; }' +
        '.etiqueta .sku  { font-size:7pt; color:#555; font-family:monospace; margin-top:1mm; }' +
        '.etiqueta .nombre { font-size:9pt; font-weight:bold; color:#111; line-height:1.2; margin:1.5mm 0 1mm; }' +
        '.etiqueta .variante { font-size:7pt; color:#555; }' +
        '.etiqueta .precio { font-size:10pt; font-weight:bold; color:#111; margin-top:1.5mm; }' +
        '@media print {' +
          'body { -webkit-print-color-adjust:exact; }' +
        '}' +
      '</style>' +
      '</head><body>' +
      '<div class="grilla">' + etiquetas + '</div>' +
      '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>' +
      '<script>' +
        'window.onload = function() {' +
          'document.querySelectorAll(".bc-svg").forEach(function(el) {' +
            'try {' +
              'JsBarcode(el, el.dataset.codigo, {' +
                'format:"CODE128", width:1.4, height:28,' +
                'displayValue:false, margin:1' +
              '});' +
            '} catch(e) {}' +
          '});' +
          'setTimeout(function(){ window.print(); }, 600);' +
        '};' +
      '<\/script>' +
      '</body></html>';

    var win = window.open('', '_blank', 'width=900,height=600');
    win.document.write(html);
    win.document.close();
  }

  function htmlEsc(s) {
    return String(s || '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

})();
</script>
