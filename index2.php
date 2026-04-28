<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
$usuario = htmlspecialchars($_SESSION['usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VIBRA — Panel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/bootstrap-toggle.min.css">
  <link rel="stylesheet" href="css/formato.css">
  <link rel="stylesheet" href="css/tablas.css">
  <link rel="stylesheet" href="css/tabla_fija.css">
  <link rel="stylesheet" href="css/cargando.css">
  <style>
    /* Layout principal */
    html, body { height: 100%; margin: 0; }
    body { display: flex; flex-direction: column; background-color: #F5F5F3; }
    #main-wrap { display: flex; flex: 1; min-height: 0; }

    /* Sidebar */
    #sidebar {
      width: 190px;
      min-width: 190px;
      background-color: #1E1E1E;
      border-right: 1px solid #2A2A2A;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }
    #sidebar::-webkit-scrollbar { width: 4px; }
    #sidebar::-webkit-scrollbar-thumb { background: #3A3A3A; border-radius: 2px; }

    /* Contenido principal */
    #panel_inicio {
      flex: 1;
      padding: 24px 28px;
      overflow-y: auto;
      min-width: 0;
    }

    /* Loading indicator */
    #cargando {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(14,14,14,0.75);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 14px;
    }
    #cargando.show { display: flex; }
    .vibra-spinner {
      width: 32px; height: 32px;
      border: 2px solid rgba(255,255,255,0.1);
      border-top-color: #A855F7;
      border-radius: 50%;
      animation: spin 0.65s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .vibra-spinner-text {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 12px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: #888;
    }

    /* Nav-sidebar overrides */
    .nav-sidebar { padding-top: 4px; }
    .nav-sidebar-header {
      font-size: 9px;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: #444;
      padding: 14px 18px 5px;
      font-family: 'DM Sans', sans-serif;
    }
    .nav-sidebar > li > a {
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      color: #888;
      padding: 8px 18px 8px 16px;
      border-left: 2px solid transparent;
      transition: all 0.12s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .nav-sidebar > li > a:hover {
      background: rgba(255,255,255,0.05);
      color: #FFFFFF;
      border-left-color: #3A3A3A;
    }
    .nav-sidebar > li.active > a {
      background: rgba(168,85,247,0.1);
      color: #A855F7;
      border-left-color: #A855F7;
    }
    .nav-dot {
      width: 4px; height: 4px;
      border-radius: 50%;
      background: currentColor;
      opacity: 0.5;
      flex-shrink: 0;
    }

    /* Sidebar footer */
    .sidebar-footer {
      margin-top: auto;
      padding: 14px 18px;
      border-top: 1px solid #2A2A2A;
      font-family: 'DM Sans', sans-serif;
      font-size: 11px;
      color: #555;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .sidebar-footer strong {
      color: #888;
      font-weight: 500;
    }
  </style>
</head>
<body>

<!-- ── NAVBAR ─────────────────────────────────────── -->
<nav class="navbar navbar-default" style="position:sticky;top:0;z-index:100;">
  <div class="container-fluid" style="padding: 0 16px;">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#" onclick="inicio()">
        <img src="images/logo.png" alt="VIBRA" style="height:28px;vertical-align:middle;filter:brightness(0)invert(1);margin-right:8px;">
        VIBRA
      </a>
    </div>
    <div class="collapse navbar-collapse" id="navbar-collapse">
      <ul class="nav navbar-nav">
        <li><a href="#" onclick="carga('clases/nuevo/factura.php')">
          <span class="nav-dot" style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block;margin-right:5px;opacity:.5;"></span>Nueva venta</a></li>
        <li><a href="#" onclick="carga('clases/nuevo/producto.php')">Productos</a></li>
        <li><a href="#" onclick="carga('clases/nuevo/cliente.php')">Clientes</a></li>
        <li><a href="#" onclick="carga('clases/reporte/ticket-opcion.php')">Reportes</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right" style="margin-right:0;">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:#7C3AED;font-size:11px;font-weight:500;color:#fff;margin-right:6px;vertical-align:middle;">
              <?php echo strtoupper(substr($usuario, 0, 2)); ?>
            </span>
            <?php echo $usuario; ?> <span class="caret"></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="conexion/logout.php">
              <span class="glyphicon glyphicon-log-out" style="margin-right:6px;"></span>Salir
            </a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ── BODY ───────────────────────────────────────── -->
<div id="main-wrap">

  <!-- Sidebar -->
  <div id="sidebar">
    <ul class="nav nav-sidebar">

      <li class="nav-sidebar-header">Ventas</li>
      <li><a href="#" onclick="carga('clases/nuevo/factura.php')"><span class="nav-dot"></span>Nueva venta</a></li>
      <li><a href="#" onclick="carga('clases/control/remito.php')"><span class="nav-dot"></span>Remitos</a></li>
      <li><a href="#" onclick="carga('clases/elimina/factura.php')"><span class="nav-dot"></span>Facturas</a></li>

      <li class="nav-sidebar-header">Caja</li>
      <li><a href="#" onclick="carga('clases/nuevo/abrecaja.php')"><span class="nav-dot"></span>Abrir caja</a></li>
      <li><a href="#" onclick="carga('clases/nuevo/cierracaja.php')"><span class="nav-dot"></span>Cerrar caja</a></li>
      <li><a href="#" onclick="carga('clases/nuevo/retirocaja.php')"><span class="nav-dot"></span>Retiro de caja</a></li>
      <li><a href="#" onclick="carga('clases/nuevo/arqueocaja.php')"><span class="nav-dot"></span>Arqueo</a></li>

      <li class="nav-sidebar-header">Inventario</li>
      <li><a href="#" onclick="carga('clases/nuevo/producto.php')"><span class="nav-dot"></span>Productos</a></li>
      <li><a href="#" onclick="carga('clases/nuevo/rubro.php')"><span class="nav-dot"></span>Rubros</a></li>
      <li><a href="#" onclick="carga('clases/nuevo/talle.php')"><span class="nav-dot"></span>Talles</a></li>
      <li><a href="#" onclick="carga('clases/control/saldo_insumo.php')"><span class="nav-dot"></span>Stock</a></li>

      <li class="nav-sidebar-header">Clientes</li>
      <li><a href="#" onclick="carga('clases/nuevo/cliente.php')"><span class="nav-dot"></span>Clientes</a></li>
      <li><a href="#" onclick="carga('clases/nuevo/club.php')"><span class="nav-dot"></span>Club VIBRA</a></li>
      <li><a href="#" onclick="carga('clases/reporte/cuentascorrientes-opcion.php')"><span class="nav-dot"></span>Cuentas corrientes</a></li>

      <li class="nav-sidebar-header">Proveedores</li>
      <li><a href="#" onclick="carga('clases/nuevo/proveedor.php')"><span class="nav-dot"></span>Proveedores</a></li>

      <li class="nav-sidebar-header">Precios</li>
      <li><a href="#" onclick="carga('clases/control/precio.php')"><span class="nav-dot"></span>Control precios</a></li>
      <li><a href="#" onclick="carga('clases/nuevo/consultaprecio.php')"><span class="nav-dot"></span>Consulta precios</a></li>

      <li class="nav-sidebar-header">Reportes</li>
      <li><a href="#" onclick="carga('clases/reporte/ventas-opcion.php')"><span class="nav-dot"></span>Ventas</a></li>
      <li><a href="#" onclick="carga('clases/reporte/ticket-opcion.php')"><span class="nav-dot"></span>Tickets</a></li>
      <li><a href="#" onclick="carga('clases/reporte/stock-opcion.php')"><span class="nav-dot"></span>Stock</a></li>

    </ul>

    <div class="sidebar-footer">
      <strong><?php echo $usuario; ?></strong>
      <span>VIBRA &copy; <?php echo date('Y'); ?></span>
    </div>
  </div>

  <!-- Contenido dinámico -->
  <div id="panel_inicio">
    <!-- Pantalla de bienvenida -->
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;gap:12px;">
      <div style="font-family:'Barlow Condensed',sans-serif;font-size:48px;font-weight:700;letter-spacing:4px;color:#0E0E0E;">VIBRA</div>
      <div style="font-family:'DM Sans',sans-serif;font-size:13px;color:#999;letter-spacing:2px;">SISTEMA DE GESTIÓN</div>
      <div style="width:40px;height:2px;background:#A855F7;margin-top:8px;"></div>
      <p style="font-family:'DM Sans',sans-serif;font-size:13px;color:#AAA;margin-top:12px;">
        Seleccioná una opción del menú lateral para comenzar.
      </p>
    </div>
  </div>

</div>

<!-- Loading overlay -->
<div id="cargando">
  <div class="vibra-spinner"></div>
  <div class="vibra-spinner-text">Cargando...</div>
</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-toggle.min.js"></script>
<script src="js/jquery.mask.min.js"></script>
<script src="js/custom.js"></script>
<script>
/* ── Helpers globales ── */
function inicio() {
  $('#panel_inicio').html(
    '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;gap:12px;">' +
    '<div style="font-family:\'Barlow Condensed\',sans-serif;font-size:48px;font-weight:700;letter-spacing:4px;color:#0E0E0E;">VIBRA</div>' +
    '<div style="font-family:\'DM Sans\',sans-serif;font-size:13px;color:#999;letter-spacing:2px;">SISTEMA DE GESTIÓN</div>' +
    '<div style="width:40px;height:2px;background:#A855F7;margin-top:8px;"></div>' +
    '</div>'
  );
  $('#sidebar li').removeClass('active');
}

function carga(url, data) {
  $('#cargando').addClass('show');
  // Marcar activo en sidebar
  $('#sidebar li').removeClass('active');
  $('#sidebar a[onclick*="' + url.split('/').pop() + '"]').parent().addClass('active');

  $('#panel_inicio').load(url, data || {}, function() {
    $('#cargando').removeClass('show');
    // Re-inicializar plugins
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="toggle"]').bootstrapToggle();
  });
}

// Compatibilidad con código existente
function modifica(tipo) {
  var datos = {};
  $('#formulario_nuevo input, #formulario_nuevo select, #formulario_nuevo textarea').each(function() {
    if ($(this).attr('id')) {
      datos[$(this).attr('id')] = $(this).val();
    }
  });
  $('#cargando').addClass('show');
  $.post('clases/modifica/' + tipo + '.php', datos, function(r) {
    $('#cargando').removeClass('show');
    var res = typeof r === 'string' ? JSON.parse(r) : r;
    if (res.success === 'true') {
      $('#div_mensaje_general').html(
        '<div class="alert alert-success" style="margin:0;">Guardado correctamente.</div>'
      );
    } else {
      $('#div_mensaje_general').html(
        '<div class="alert alert-danger" style="margin:0;">Ocurrió un error. Intentá nuevamente.</div>'
      );
    }
  });
}

$(document).on('click', '.nav-sidebar a', function(e) {
  e.preventDefault();
});

// Ocultar loading en errores jQuery
$(document).ajaxError(function() {
  $('#cargando').removeClass('show');
});
</script>

</body>
</html>
