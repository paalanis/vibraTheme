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
require_once '../../conexion/descuentos.php'; // resolvedor de descuentos

/* ── AJAX: cargar marcas ─────────────────────────────────────── */
if (isset($_POST['accion']) && $_POST['accion'] === 'marcas') {
    $rs   = mysqli_query($conexion, "SELECT id_marca, nombre FROM tb_marca ORDER BY nombre ASC");
    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
        $rows[] = ['id' => $r['id_marca'], 'nombre' => $r['nombre']];
    }
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit();
}

/* ── AJAX: buscar productos ──────────────────────────────────── */
if (isset($_POST['accion']) && $_POST['accion'] === 'buscar') {
    $id_marca = intval($_POST['id_marca'] ?? 0);
    $q        = trim($_POST['q'] ?? '');

    if ($id_marca === 0 && strlen($q) < 2) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'filtro_requerido']);
        exit();
    }

    $where = [];
    if ($id_marca > 0) {
        $where[] = "p.id_marca = $id_marca";
    }
    if (strlen($q) >= 2) {
        $buscar  = '%' . mysqli_real_escape_string($conexion, $q) . '%';
        $where[] = "(p.nombre LIKE '$buscar' OR p.codigo LIKE '$buscar')";
    }
    $whereSQL = implode(' AND ', $where);

    $sql = "SELECT
                p.id_productos,
                p.id_marca,
                p.id_tipo,
                p.nombre,
                p.codigo,
                ROUND(p.precio_costo * (1 + p.margen_ganancia / 100), 2) AS precio_lista_calc,
                t.nombre AS talle,
                c.nombre AS color,
                m.nombre AS marca
            FROM tb_productos p
            LEFT JOIN tb_talle t ON p.id_talle = t.id_talle
            LEFT JOIN tb_color c ON p.id_color  = c.id_color
            LEFT JOIN tb_marca m ON p.id_marca  = m.id_marca
            WHERE $whereSQL
            ORDER BY p.nombre ASC
            LIMIT 100";

    $rs   = mysqli_query($conexion, $sql);
    $prods_raw = [];
    while ($r = mysqli_fetch_assoc($rs)) $prods_raw[] = $r;

    // Cargar todas las reglas de descuento activas en una sola query
    // id_sucursal no está en sesión — 0 = aplica solo reglas globales (id_sucursal IS NULL)
    $id_sucursal_etq = 0;
    $reglas_activas  = descuento_cargar_activos($conexion, $id_sucursal_etq);

    $rows = [];
    foreach ($prods_raw as $r) {
        $lista = (float)$r['precio_lista_calc'];
        $desc  = descuento_resolver_local(
            $reglas_activas,
            (int)$r['id_productos'],
            (int)$r['id_marca'],
            (int)$r['id_tipo']
        );
        $pct  = $desc['porcentaje'];
        $efvo = round($lista * (1 - $pct / 100), 2);
        $rows[] = [
            'id'           => $r['id_productos'],
            'nombre'       => $r['nombre'],
            'codigo'       => $r['codigo'],
            'lista'        => number_format($lista, 2, ',', '.'),
            'efvo'         => number_format($efvo,  2, ',', '.'),
            'desc_pct'     => $pct,
            'desc_nombre'  => $desc['nombre'],
            'talle'        => $r['talle']  ?? '',
            'color'        => $r['color']  ?? '',
            'marca'        => $r['marca']  ?? '',
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
          <span id="etq_dot" style="font-size:16px;color:#d9534f;vertical-align:middle;">●</span>
          &nbsp;QZ Tray&nbsp;
        </label>
        <button type="button" class="btn btn-xs btn-default" id="etq_btn_conectar">Conectar</button>
        <span id="etq_estado" class="text-muted" style="font-size:11px;margin-left:5px;">desconectado</span>
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
               value="50" min="20" max="120" style="display:inline-block;width:58px;">
        <span style="vertical-align:middle;">&times;</span>
        <input type="number" id="etq_alto" class="form-control input-sm"
               value="30" min="15" max="200" style="display:inline-block;width:58px;">
      </div>
    </div>

  </div>
</div>

<!-- Filtros + tabla -->
<div class="well bs-component">
  <div class="row">
    <div class="col-lg-12">
      <fieldset>

        <div class="form-group form-group-sm">
          <label class="col-lg-2 control-label">Marca</label>
          <div class="col-lg-3">
            <select class="form-control" id="etq_marca">
              <option value="0">— Todas —</option>
            </select>
          </div>
          <label class="col-lg-1 control-label">Buscar</label>
          <div class="col-lg-3">
            <input type="text" class="form-control" id="etq_buscar"
                   autocomplete="off" placeholder="Nombre o código...">
          </div>
          <div class="col-lg-3">
            <button type="button" class="btn btn-default btn-sm" id="etq_btn_buscar">
              <span class="glyphicon glyphicon-search"></span> Buscar
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="etq_btn_imprimir" disabled>
              <span class="glyphicon glyphicon-print"></span> Imprimir
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

      <div id="etq_aviso" class="text-center text-muted" style="padding:14px;">
        Seleccioná una marca o ingresá un texto para buscar.
      </div>

      <table class="table table-hover table-condensed table-bordered"
             id="etq_tabla" style="display:none;">
        <thead>
          <tr>
            <th style="width:36px;text-align:center;">
              <input type="checkbox" id="etq_sel_todos" title="Seleccionar todos">
            </th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Marca</th>
            <th>Talle</th>
            <th>Color</th>
            <th>Lista</th>
            <th id="etq_th_efvo">Efvo (−?%)</th>
            <th style="width:64px;">Cant.</th>
          </tr>
        </thead>
        <tbody id="etq_tbody"></tbody>
      </table>

      <div id="etq_vacio" class="text-center text-muted"
           style="display:none;padding:20px;">No se encontraron productos.</div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script>
<script>
(function () {

  /* ── Certificado QZ Tray ─────────────────── */
  var QZ_CERT = "-----BEGIN CERTIFICATE-----\n" +
"MIIDYjCCAkqgAwIBAgIBADANBgkqhkiG9w0BAQUFADBKMRYwFAYDVQQDDA1WSUJS\n" +
"QSBTaXN0ZW1hMQ4wDAYDVQQKDAVWSUJSQTELMAkGA1UEBhMCQVIxEzARBgNVBAgM\n" +
"ClNvbWUtU3RhdGUwHhcNMjYwNTA2MTkxNTQ2WhcNMzYwNTAzMTkxNTQ2WjBKMRYw\n" +
"FAYDVQQDDA1WSUJSQSBTaXN0ZW1hMQ4wDAYDVQQKDAVWSUJSQTELMAkGA1UEBhMC\n" +
"QVIxEzARBgNVBAgMClNvbWUtU3RhdGUwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAw\n" +
"ggEKAoIBAQDIBtEAGox8mQwpT08XSL/bi0ByaRTnl8IXB0faJe2ataHDdLjqLpzA\n" +
"XNXOaPHu0GwoK5+PBNsqy4D9RFJKf9TlTlvH6lHif5GAPoElw5BYCnlWITEtQWep\n" +
"YXPNOlGnVrtVMgY+Z4c3eq0YO4y4G+p8yCcN+XNtWtk0FllMZTIZsilaRoChpaDd\n" +
"Rvb+Unyf8Fa5Jpm/pxEs4cyhylkmoqDifWjYlghHL4sC0kn7DRZOweBNt4uwHoVl\n" +
"nkh38K8ojHTDfo2t0F5LT3IVLLi4pLZ+Uu2Gop7CZBgrVFpD5CIvjHcdb85wrPE6\n" +
"TYducprkfm+XmKZRoGiaW3Xb1wTRPhb/AgMBAAGjUzBRMB0GA1UdDgQWBBTvRkfs\n" +
"c4dWmbXG/tNnQpUKb6Tm6jAfBgNVHSMEGDAWgBTvRkfsc4dWmbXG/tNnQpUKb6Tm\n" +
"6jAPBgNVHRMBAf8EBTADAQH/MA0GCSqGSIb3DQEBBQUAA4IBAQCqlho/rS8M5oUV\n" +
"w5fbRrcrViK2E6weyB1M1yWawlkC9bs2f+G6ArqFwF4JwnYYWFFuG0NOgtbiDR7Y\n" +
"1eC25oSRSo866wgt8O6po1XWcoSJi0C0vP5r3Z53oAPDmR3IoDXNC21CkOZBERhV\n" +
"ojREH6XPAqTaQ8a8EygbQ3osGn9IbF5ATPHuagZD+8Gl7UFse7s8xORPWMD4nu/y\n" +
"Y5eAgRgVQjjMCe6Fy0EjAsu2045IbHERVSSx8Aajb/zh061mle5nW/P6C5cQ/EES\n" +
"HancoFyDa2NJBBIj8+XcH4XErQmwwl2aZ0Wzc1y+CHae49YUx/slN6HD959P5n7c\n" +
"PjvqDwQH\n" +
"-----END CERTIFICATE-----";

  /* ── QZ: configurar certificado y firma ─── */
  function setupQZSecurity() {
    qz.security.setCertificatePromise(function (resolve) {
      resolve(QZ_CERT);
    });
    qz.security.setSignatureAlgorithm("SHA512");
    qz.security.setSignaturePromise(function (toSign) {
      return function (resolve, reject) {
        $.post('clases/nuevo/etiqueta_sign.php', { data: toSign })
          .done(function (sig) { resolve(sig); })
          .fail(function (err) { reject(err); });
      };
    });
  }

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
    setupQZSecurity();
    setEstado(false, 'conectando...');
    qz.websocket.connect()
      .then(function () { setEstado(true, 'conectado'); })
      .catch(function (e) { setEstado(false, 'sin QZ Tray — ' + e.message); });
  }

  $('#etq_btn_conectar').on('click', function () {
    if (conectado) {
      qz.websocket.disconnect().then(function () { setEstado(false, 'desconectado'); });
    } else { conectar(); }
  });

  setTimeout(function () { if (typeof qz !== 'undefined') conectar(); }, 700);

  /* ── Cargar marcas ──────────────────────── */
  $.post('clases/nuevo/etiqueta.php', { accion: 'marcas' }, function (data) {
    var $sel = $('#etq_marca');
    $.each(data, function (i, m) {
      $sel.append('<option value="' + m.id + '">' + esc(m.nombre) + '</option>');
    });
  }, 'json');

  /* ── Tabla ──────────────────────────────── */
  function contarSeleccionados() { return $('#etq_tbody input[type=checkbox]:checked').length; }

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
    $('#etq_aviso').hide();

    if (!lista || !lista.length) {
      $('#etq_tabla').hide(); $('#etq_vacio').show(); return;
    }
    $('#etq_vacio').hide(); $('#etq_tabla').show();

    // Actualizar encabezado con % del primer producto que tenga descuento
    var pctHeader = 0;
    $.each(lista, function(i, p){ if (p.desc_pct > 0) { pctHeader = p.desc_pct; return false; } });
    $('#etq_th_efvo').text(pctHeader > 0 ? 'Efvo (−' + pctHeader + '%)' : 'Efvo');

    $.each(lista, function (i, p) {
      var descLabel = p.desc_pct > 0 ? ' <small style="color:#d9534f;">−'+p.desc_pct+'%</small>' : '';
      $b.append(
        '<tr>' +
        '<td style="text-align:center;vertical-align:middle;">' +
          '<input type="checkbox" class="etq_chk"' +
          ' data-nombre="' + esc(p.nombre) + '"' +
          ' data-codigo="' + esc(p.codigo) + '"' +
          ' data-lista="'  + esc(p.lista)  + '"' +
          ' data-efvo="'   + esc(p.efvo)   + '"' +
          ' data-talle="'  + esc(p.talle)  + '"' +
          ' data-color="'  + esc(p.color)  + '"' +
          ' data-desc="'   + (p.desc_pct||0) + '">' +
        '</td>' +
        '<td style="vertical-align:middle;font-family:monospace;">' + esc(p.codigo) + '</td>' +
        '<td style="vertical-align:middle;">'  + esc(p.nombre) + '</td>' +
        '<td style="vertical-align:middle;">'  + esc(p.marca)  + '</td>' +
        '<td style="vertical-align:middle;">'  + esc(p.talle)  + '</td>' +
        '<td style="vertical-align:middle;">'  + esc(p.color)  + '</td>' +
        '<td style="vertical-align:middle;">$' + esc(p.lista)  + '</td>' +
        '<td style="vertical-align:middle;">$' + esc(p.efvo)   + '</td>' +
        '<td style="vertical-align:middle;">' +
          '<input type="number" class="form-control input-sm etq_qty"' +
          ' value="1" min="1" max="99" style="width:58px;">' +
        '</td>' +
        '</tr>'
      );
    });
    actualizarContador();
  }

  function buscar() {
    var id_marca = $('#etq_marca').val();
    var q        = $('#etq_buscar').val().trim();

    if (id_marca === '0' && q.length < 2) {
      mostrarMsg('Seleccioná una marca o ingresá al menos 2 caracteres', 'warning');
      return;
    }

    $('#etq_loading').show();
    $('#etq_tabla').hide();
    $('#etq_vacio').hide();
    $('#etq_aviso').hide();

    $.post('clases/nuevo/etiqueta.php', { accion: 'buscar', id_marca: id_marca, q: q },
      function (data) {
        $('#etq_loading').hide();
        if (data && data.error === 'filtro_requerido') {
          mostrarMsg('Ingresá al menos un filtro', 'warning'); return;
        }
        renderTabla(data);
      }, 'json'
    ).fail(function () {
      $('#etq_loading').hide();
      $('#etq_vacio').text('Error al consultar la base de datos.').show();
    });
  }

  $('#etq_btn_buscar').on('click', buscar);

  $('#etq_buscar').on('keypress', function (e) {
    if (e.which === 13) buscar();
  });

  $('#etq_marca').on('change', function () {
    $('#etq_tabla').hide();
    $('#etq_vacio').hide();
    $('#etq_aviso').hide();
    if ($(this).val() !== '0') buscar();
  });

  $(document).on('change', '.etq_chk', actualizarContador);
  $(document).on('input',  '.etq_qty', function () { if (+$(this).val() < 1) $(this).val(1); });

  $('#etq_sel_todos').on('change', function () {
    $('.etq_chk').prop('checked', $(this).is(':checked'));
    actualizarContador();
  });

  /* ── Imprimir ───────────────────────────── */
  $('#etq_btn_imprimir').on('click', function () {
    if (!conectado) { mostrarMsg('Conectá QZ Tray primero', 'warning'); return; }
    var items = [];
    $('#etq_tbody tr').each(function () {
      var c = $(this).find('.etq_chk');
      if (c.is(':checked')) {
        items.push({
          nombre: c.data('nombre'),
          codigo: c.data('codigo'),
          lista:  c.data('lista'),
          efvo:   c.data('efvo'),
          talle:  c.data('talle'),
          color:  c.data('color'),
          qty:    parseInt($(this).find('.etq_qty').val()) || 1
        });
      }
    });
    if (!items.length) return;
    imprimirItems(items);
  });

  function imprimirItems(items) {
    var printer  = $('#etq_printer').val().trim() || 'XP-470B';
    var anchoMM  = parseFloat($('#etq_ancho').val()) || 50;
    var altoMM   = parseFloat($('#etq_alto').val())  || 30;
    var anchoIn  = +(anchoMM / 25.4).toFixed(4);
    var altoIn   = +(altoMM  / 25.4).toFixed(4);

    var total = 0;
    items.forEach(function (p) { total += p.qty; });

    mostrarMsg('Enviando 0 / ' + total + '...', 'info');
    $('#etq_btn_imprimir').prop('disabled', true);

    var config = qz.configs.create(printer, {
      size: { width: anchoIn, height: altoIn },
      units: 'in', scaleContent: true,
      colorType: 'blackwhite', copies: 1
    });

    var cadena   = Promise.resolve();
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
    var svgNS = 'http://www.w3.org/2000/svg';
    var svgEl = document.createElementNS(svgNS, 'svg');
    svgEl.setAttribute('xmlns', svgNS);
    var bcOK = false;
    try {
      JsBarcode(svgEl, p.codigo, {
        format: 'CODE128', width: 1.4, height: 28,
        displayValue: false, margin: 1
      });
      bcOK = true;
    } catch (e) {}

    var bcHtml = bcOK
      ? '<div style="width:100%;text-align:center;">' + svgEl.outerHTML + '</div>'
      : '';

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' +
      '* { box-sizing:border-box; margin:0; padding:0; }' +
      'body {' +
        'width:'  + anchoMM + 'mm;' +
        'height:' + altoMM  + 'mm;' +
        'overflow:hidden; font-family:Arial,sans-serif;' +
        'padding:1.5mm 2mm 4mm 2mm;' +
        'display:flex; flex-direction:column; justify-content:space-between;' +
      '}' +
      '.bc svg { width:100%; height:auto; max-height:10mm; display:block; }' +
      '.bc-num  { text-align:center; font-size:6pt; font-family:monospace; color:#333; margin:0.5mm 0; }' +
      '.nombre  { font-size:7.5pt; font-weight:bold; color:#111; line-height:1.2; }' +
      '.variante{ display:flex; justify-content:space-between; font-size:6.5pt; color:#555; margin-top:0.5mm; }' +
      '.precios { display:flex; justify-content:space-between; align-items:flex-end;' +
                 'border-top:0.4px solid #aaa; padding-top:1mm; margin-top:1mm; }' +
      '.precio-bloque { display:flex; flex-direction:column; }' +
      '.precio-label  { font-size:5.5pt; color:#666; margin-bottom:0.5mm; }' +
      '.precio-valor  { font-size:9pt; font-weight:bold; color:#000; }' +
      '</style></head><body>' +
        '<div class="bc">' + bcHtml + '</div>' +
        '<div class="bc-num">' + he(p.codigo) + '</div>' +
        '<div class="nombre">'  + he(p.nombre) + '</div>' +
        '<div class="variante">' +
          '<span>' + he(p.talle) + '</span>' +
          '<span>' + he(p.color) + '</span>' +
        '</div>' +
        '<div class="precios">' +
          '<div class="precio-bloque">' +
            '<span class="precio-label">Lista</span>' +
            '<span class="precio-valor">$' + he(p.lista) + '</span>' +
          '</div>' +
          '<div class="precio-bloque" style="text-align:right;">' +
            '<span class="precio-label">Efvo</span>' +
            '<span class="precio-valor">$' + he(p.efvo) + '</span>' +
          '</div>' +
        '</div>' +
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
