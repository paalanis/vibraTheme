<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}
date_default_timezone_set("America/Argentina/Mendoza");
$hoy = date("Y-m-d");

// Último margen utilizado (de los últimos remitos confirmados)
$stmt_m = mysqli_prepare($conexion,
    "SELECT margen_ganancia FROM tb_remitos
     WHERE margen_ganancia IS NOT NULL AND estado='1'
     ORDER BY id_remitos DESC LIMIT 1"
);
mysqli_stmt_execute($stmt_m);
mysqli_stmt_bind_result($stmt_m, $ultimo_margen);
$tiene_margen = (bool)mysqli_stmt_fetch($stmt_m);
mysqli_stmt_close($stmt_m);
$ultimo_margen = $tiene_margen ? (float)$ultimo_margen : '';

$sqlproveedor = "SELECT tb_proveedores.id_proveedores AS id, tb_proveedores.nombre AS proveedor
                 FROM tb_proveedores ORDER BY proveedor";
$rsproveedor = mysqli_query($conexion, $sqlproveedor);
?>
<form class="form-horizontal" id="formulario_nuevo" role="form"
      onsubmit="event.preventDefault(); carga('producto')">

<div class="modal-header">
   <h4 class="modal-title">Nuevo Remito</h4>
</div>
<br>

<div class="well bs-component">
 <div class="row">
  <div class="col-lg-10">
   <fieldset>
    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Fecha</label>
      <div class="col-lg-9">
        <input type="date" class="form-control" id="dato_fecha"
               value="<?php echo $hoy; ?>" required>
      </div>
    </div>
    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Proveedor</label>
      <div class="col-lg-9">
        <select class="form-control" id="dato_proveedor" required>
          <option value=""></option>
          <?php while ($sql_proveedor = mysqli_fetch_assoc($rsproveedor)): ?>
            <option value="<?php echo $sql_proveedor['id']; ?>">
              <?php echo htmlspecialchars($sql_proveedor['proveedor'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Remito Nro</label>
      <div class="col-lg-3">
        <input class="form-control" autocomplete="off" placeholder="0000"
               id="dato_sucursal" type="text" required>
      </div>
      <div class="col-lg-4">
        <input class="form-control" autocomplete="off" placeholder="00000000"
               id="dato_remito" type="text" required>
      </div>
      <div class="col-lg-2">
        <div id="remito-ok"></div>
      </div>
    </div>
    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Observación</label>
      <div class="col-lg-9">
        <textarea class="form-control" autocomplete="off" rows="1" id="dato_obs"></textarea>
      </div>
    </div>
    <div class="form-group form-group-md">
      <label class="col-lg-3 control-label">Código / Nombre</label>
      <div class="col-lg-4">
        <input type="text" class="form-control" id="codigo" value="" placeholder="Buscar...">
      </div>
      <div class="col-lg-5">
        <input type="text" class="form-control" id="nombre" value="" placeholder="Nombre producto">
      </div>
    </div>
   </fieldset>
  </div>
  <div class="col-lg-2"></div>
 </div>

 <div class="row">
  <div class="col-lg-12">
    <div class="form-group form-group-md">
      <label class="col-lg-2 control-label">Producto</label>
      <div class="col-lg-10" id="div_producto"></div>
    </div>
  </div>
 </div>

 <div class="row">
  <div class="col-lg-12">
    <div id="div_remitos"></div>
  </div>
 </div>
</div>

<div class="modal-footer" id="footer_remito">
  <div class="form-group form-group-sm">
    <div class="col-lg-7">
      <div align="center" id="div_mensaje_general"></div>
    </div>
    <div class="col-lg-5">
      <div align="right">
        <button type="button" onclick="inicio()" class="btn btn-default">Salir</button>
        <button type="button" id="boton_confirmar" class="btn btn-primary">
          Confirmar Remito
        </button>
      </div>
    </div>
  </div>
</div>

</form>

<script type="text/javascript">
var ultimoMargen = <?php echo json_encode($ultimo_margen); ?>;

// Búsqueda de producto → carga widget en modo remito
function carga(modulo) {
    var cod = $('#codigo').val().trim();
    var proveedor  = $('#dato_proveedor').val();
    var sucursal   = $('#dato_sucursal').val();
    var remito_num = $('#dato_remito').val();

    if (!proveedor || !sucursal || !remito_num) {
        alert('Complete proveedor y número de remito antes de cargar productos.');
        return;
    }
    $('#div_producto').html('<div class="text-center"><div class="loadingsm"></div></div>');
    $('#div_producto').load('clases/nuevo/producto.php', {
        codigo: cod,
        buscar_modo: 'remito'
    }, function() {
        // Pre-cargar último margen si el campo está vacío
        if (ultimoMargen !== '' && $('#dato_margen').val() === '') {
            $('#dato_margen').val(ultimoMargen);
        }
    });
}

// Submit del formulario de línea de remito
$(document).on('submit', '#formulario_nuevo', function(e) {
    e.preventDefault();
    carga('producto');
});

// Confirmar remito
$('#boton_confirmar').click(function() {
    var proveedor  = $('#dato_proveedor').val();
    var sucursal   = $('#dato_sucursal').val();
    var remito_num = $('#dato_remito').val();
    if (!proveedor || !sucursal || !remito_num) {
        alert('Datos incompletos.');
        return;
    }

    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url     : 'clases/guardar/carga-remito.php',
        type    : 'post',
        dataType: 'json',
        data    : {
            dato_proveedor: proveedor,
            dato_sucursal : sucursal,
            dato_remito   : remito_num,
            csrf_token    : csrfToken
        },
        success: function(data) {
            if (data.success === 'true') {
                $('#div_mensaje_general').html(
                    '<div class="alert alert-success alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                    'Remito confirmado exitosamente.</div>');
                setTimeout(function() { inicio(); }, 1500);
            } else {
                $('#div_mensaje_general').html(
                    '<div class="alert alert-danger alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                    'Error al confirmar: ' + (data.error || 'reintente') + '</div>');
            }
        }
    });
});

// Búsqueda con Enter en campo código
$('#codigo').on('keyup', function(e) {
    if (e.key === 'Enter') carga('producto');
});
</script>
