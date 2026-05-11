<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
}
require_once '../../conexion/conexion.php';
require_once '../../conexion/csrf.php';
if (mysqli_connect_errno()) {
    printf("La conexión con el servidor de base de datos falló: %s\n", mysqli_connect_error());
    exit();
}
date_default_timezone_set("America/Argentina/Mendoza");
$hoy = date("Y-m-d");

// Ultimo margen utilizado en remitos confirmados
$stmt_m = mysqli_prepare($conexion,
    "SELECT margen_ganancia FROM tb_remitos
     WHERE margen_ganancia IS NOT NULL AND estado = '1'
     ORDER BY id_remitos DESC LIMIT 1"
);
mysqli_stmt_execute($stmt_m);
mysqli_stmt_bind_result($stmt_m, $ultimo_margen_val);
$ultimo_margen = mysqli_stmt_fetch($stmt_m) ? (float)$ultimo_margen_val : null;
mysqli_stmt_close($stmt_m);

// REMOVIDO: DELETE masivo de tb_remitos estado=0 sin filtro de usuario/sesión
// (borraba los borradores de TODOS los usuarios al abrir el formulario)

$sqlproducto = "SELECT tb_productos.id_productos as id, tb_productos.nombre as productos
                FROM tb_productos ORDER BY tb_productos.nombre ASC";
$rsproducto = mysqli_query($conexion, $sqlproducto);

$sqlproveedor = "SELECT tb_proveedores.id_proveedores as id, tb_proveedores.nombre as proveedor
                 FROM tb_proveedores ORDER BY proveedor";
$rsproveedor = mysqli_query($conexion, $sqlproveedor);
?>
<form class="form-horizontal" id="formulario_nuevo" role="form" onsubmit="event.preventDefault(); carga('producto')">

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
          <input type="date" class="form-control" id="dato_fecha" value="<?php echo $hoy;?>" required>
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
        <label class="col-lg-3 control-label">Código</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" id="codigo" value="">
        </div>
      </div>
      <div class="form-group form-group-md">
        <label class="col-lg-3 control-label">Nombre</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" id="nombre" value="">
        </div>
      </div>
   </fieldset>
 </div>
 <div class="col-lg-2"></div>
</div>

<div class="row">
  <div class="col-lg-10">
    <div class="form-group form-group-md">
      <label class="col-lg-3 control-label">Producto</label>
      <!-- col-lg-12: control/producto.php ocupa todo el ancho (incluye su propio botón) -->
      <div class="col-lg-9" id="div_producto"></div>
    </div>
  </div>
  <!-- Botón estático REMOVIDO: control/producto.php ya renderiza el suyo en modo remito -->
</div>

<div class="row">
  <div class="col-lg-10" id='div_duplicado'></div>
</div>

</div>

  <div class="modal-footer">
        <div class="form-group form-group-sm">
        <div class="col-lg-7">
          <div align="center" id="div_mensaje_general"></div>
        </div>
        <div class="col-lg-5">
          <div align="right">
          <button type="button" id="boton_salir" onclick="inicio()" class="btn btn-default">Salir</button>
          <button type="button" id="boton_guardar" class="btn btn-primary" onclick="carga('remito')">Guardar Remito</button>
          </div>
        </div>
      </div>
  </div>
</form>

<!-- div_remitos FUERA del form: los botones sin id de remitoinsumo.php
     no interfieren con fx.js carga() que itera :input dentro de #formulario_nuevo -->
<div class="row">
  <fieldset id="div_remitos"></fieldset>
</div>

<script type="text/javascript">
// CSRF token como variable JS — evita PHP anidado dentro de callbacks
var CSRF_TOKEN = '<?php echo csrf_token(); ?>';
var ULTIMO_MARGEN = <?php echo $ultimo_margen !== null ? json_encode($ultimo_margen) : 'null'; ?>;

  $(document).ready(function () {
    // Relleno de ceros al salir del campo
    $('#dato_sucursal').on('blur', function() {
      var v = $(this).val().replace(/\D/g, '');
      if (v !== '') {
        $(this).val(v.padStart(4, '0'));
        // .val() programático no dispara 'change' en todos los browsers → forzamos
        $(this).trigger('change');
      }
    });
    $('#dato_remito').on('blur', function() {
      var v = $(this).val().replace(/\D/g, '');
      if (v !== '') {
        $(this).val(v.padStart(8, '0'));
        $(this).trigger('change');
      }
    });
    // Solo permite dígitos mientras escribe
    $('#dato_sucursal, #dato_remito').on('input', function() {
      $(this).val($(this).val().replace(/\D/g, ''));
    });
    $('#boton_guardar').attr('disabled', true);
  });

  // Búsqueda por código
  $(function() {
    $('#codigo').change(function() {
      var codigo = $(this).val();
      if (codigo !== '') {
        $('#div_producto').html('<div class="text-center"><div class="loadingsm"></div></div>');
        // buscar_modo=remito activa dato_cantidad + precio_costo en control/producto.php
        $("#div_producto").load("clases/control/producto.php",
          {codigo: codigo, buscar: 'codigo', buscar_modo: 'remito'},
          function() {
            if (ULTIMO_MARGEN !== null && $('#dato_margen').val() === '') {
              $('#dato_margen').val(ULTIMO_MARGEN);
            }
          });
        $("#dato_cantidad").focus();
      } else {
        $("#div_producto").html('');
        $("#codigo").focus();
      }
    });
  });

  // Búsqueda por nombre
  $(function() {
    $('#nombre').change(function() {
      var nombre = $(this).val();
      if (nombre !== '') {
        $('#div_producto').html('<div class="text-center"><div class="loadingsm"></div></div>');
        $("#div_producto").load("clases/control/producto.php",
          {codigo: nombre, buscar: 'nombre', buscar_modo: 'remito'},
          function() {
            if (ULTIMO_MARGEN !== null && $('#dato_margen').val() === '') {
              $('#dato_margen').val(ULTIMO_MARGEN);
            }
          });
        $("#dato_cantidad").focus();
      } else {
        $("#div_producto").html('');
        $("#nombre").focus();
      }
    });
  });

  $(function() {
    $('#dato_sucursal').change(function() { $('#dato_remito').val(''); });
  });

  $(function() {
    $('#dato_proveedor').change(function() {
      $('#dato_remito').val('');
      $('#dato_sucursal').val('');
    });
  });

  // Validación de número de remito
  $(function() {
    $('#dato_remito').change(function() {
      var sucursal  = $('#dato_sucursal').val();
      var remito    = $('#dato_remito').val();
      var proveedor = $('#dato_proveedor').val();
      var remitofinal = sucursal + '-' + remito;

      if (sucursal !== '' && proveedor !== '') {
        $('#remito-ok').html('<div class="text-center"><div class="loadingsm"></div></div>');
        $.ajax({
          url      : "clases/control/remito.php",
          data     : {proveedor: proveedor, remito: remitofinal},
          dataType : "json",
          type     : "get",
          success  : function(data) {
            if (data.success === 'true') {
              $('#remito-ok').html('<span class="glyphicon glyphicon-ok" style="line-height:28px"></span>');
              $('#boton_producto').prop('disabled', false);

            } else if (data.success === 'borrador') {
              var remito    = $('#dato_sucursal').val() + '-' + $('#dato_remito').val();
              var proveedor = $('#dato_proveedor').val();
              $('#remito-ok').html('<span class="glyphicon glyphicon-ok" style="line-height:28px"></span>');
              // Carga la lista en borrador inmediatamente
              $('#div_remitos').load('clases/nuevo/remitoinsumo.php',
                {remito: remito, proveedor: proveedor});
              $('#boton_guardar').prop('disabled', false);
              $('#boton_producto').prop('disabled', false);
              // Bloquea cabecera igual que al cargar el primer producto
              $('#dato_proveedor').prop('disabled', true);
              $('#dato_sucursal').prop('disabled', true);
              $('#dato_remito').prop('disabled', true);
              // Aviso con opciones — usa template pre-renderizado (sin HTML en JS)
              var cant = parseInt(data.cant);
              $('#div_duplicado').html(
                '<div class="alert alert-warning" style="margin-top:8px">' +
                '<strong>Este remito tiene ' + cant + ' producto(s) en borrador.</strong>' +
                '<button type="button" class="btn btn-xs btn-default" id="btn_continuar_borrador" style="margin-left:10px">Continuar borrador</button>' +
                '<button type="button" class="btn btn-xs btn-danger" id="btn_descartar_borrador" style="margin-left:4px">Descartar y empezar de cero</button>' +
                '</div>'
              );
              $('#btn_continuar_borrador').on('click', function() {
                $('#div_duplicado').html('');
              });
              $('#btn_descartar_borrador').on('click', function() {
                var remitoVal   = $('#dato_sucursal').val() + '-' + $('#dato_remito').val();
                var proveedorVal = $('#dato_proveedor').val();
                $.ajax({
                  url      : 'clases/elimina/remito-borrador.php',
                  data     : {remito: remitoVal, proveedor: proveedorVal, csrf_token: CSRF_TOKEN},
                  dataType : 'json',
                  type     : 'post',
                  success  : function(r) {
                    if (r.success === 'true') {
                      $('#div_remitos').html('');
                      $('#div_duplicado').html('');
                      $('#boton_guardar').prop('disabled', true);
                      $('#dato_proveedor').prop('disabled', false);
                      $('#dato_sucursal').prop('disabled', false);
                      $('#dato_remito').prop('disabled', false);
                      $('#dato_remito').val('');
                      $('#remito-ok').html('');
                      $('#dato_sucursal').focus();
                    } else {
                      alert('Error al descartar. Reintente.');
                    }
                  }
                });
              });

            } else {
              $('#remito-ok').html('<span class="glyphicon glyphicon-remove" style="line-height:28px"></span>');
              $('#dato_remito').val('');
            }
          }
        });
      } else {
        if (sucursal === '') {
          $("#dato_sucursal").tooltip({title: "Cargar sucursal", placement: "top"}).tooltip('show');
        }
        if (proveedor === '') {
          $("#dato_proveedor").tooltip({title: "Cargar proveedor", placement: "top"}).tooltip('show');
        }
      }
    });
  });
</script>
