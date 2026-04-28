<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
printf("La conexión con el servidor de base de datos falló comuniquese con su administrador: %s\n", mysqli_connect_error());
exit();
}
date_default_timezone_set("America/Argentina/Mendoza");
$hoy = date("Y-m-d");

//Elimina pruductos pendientes
mysqli_select_db($conexion,'$basedatos');
$sql = "DELETE FROM tb_remitos WHERE tb_remitos.estado = '0'";
mysqli_query($conexion,$sql);

$sqlproducto = "SELECT
tb_productos.id_productos as id,
tb_productos.nombre as productos
FROM
tb_productos
ORDER BY
tb_productos.nombre ASC
";
$rsproducto = mysqli_query($conexion, $sqlproducto);

$sqlproveedor = "SELECT
tb_proveedores.id_proveedores as id,
tb_proveedores.nombre as proveedor
FROM
tb_proveedores
ORDER BY
proveedor";
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
          <input type="date" class="form-control" id="dato_fecha" value="<?php echo $hoy;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Proveedor</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_proveedor" required>   
              <option value=""></option>
              <?php
              while ($sql_proveedor = mysqli_fetch_assoc($rsproveedor)){
                $idproveedor= $sql_proveedor['id'];
                $proveedor = $sql_proveedor['proveedor'];
                echo utf8_encode('<option value='.$idproveedor.'>'.$proveedor.'</option>');
              }
              ?>
            </select>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Remito Nro</label>
        <div class="col-lg-3">
          <div class="input-group input-group-sm">
            <input class="form-control" autocomplete="off" placeholder="0000" id="dato_sucursal" type="text" required>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="input-group input-group-sm">
            <input class="form-control" autocomplete="off" placeholder="00000000" id="dato_remito" type="text" required>
          </div>
        </div>
        <div class="col-lg-2">
          <div class="input-group input-group-sm" id="remito-ok">
            <!-- <span class="glyphicon glyphicon-align-left" style="line-height: 28px;" aria-hidden="true"></span> -->
          </div>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="textArea" class="col-lg-3 control-label">Observación</label>
        <div class="col-lg-9">
          <textarea class="form-control" autocomplete="off" rows="1" id="dato_obs"></textarea>
         </div>
      </div>
      <div class="form-group form-group-md">
        <label class="col-lg-3 control-label">Código</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" id="codigo" value="" aria-describedby="basic-addon1">
        </div>
      </div>
      <div class="form-group form-group-md">
        <label class="col-lg-3 control-label">Nombre</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" id="nombre" value="" aria-describedby="basic-addon1">
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
      <div class="col-lg-9" id="div_producto">
        
      <!-- se carga el pruducto buscado -->

      </div>
    </div>

  </div>
  
  <div class="col-lg-2">
    <button type="submit" id="boton_producto" class="btn btn-success">Cargar Producto</button>
  </div>
</div>

<div class="row">
  <div class="col-lg-10" id='div_duplicado'>
   <!-- mensaje duplicado -->
  </div>  
</div>

</div>

 <div class="row">
  
   <fieldset id="div_remitos">
   </fieldset>

 </div>
 
  <div class="modal-footer">
        <div class="form-group form-group-sm">
        <div class="col-lg-7">
          <div align="center" id="div_mensaje_general">
          </div>
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
<script type="text/javascript">
  $(document).ready(function () {
  
    $('#dato_sucursal').mask("NNNN", {'translation': {N: {pattern: /[0-9]/}}, clearIfNotMatch: true});
    $('#dato_remito').mask("AAAAAAAA", {'translation': {A: {pattern: /[0-9]/}}, clearIfNotMatch: true});
    $('#boton_guardar').attr('disabled', true);
    $('#boton_producto').attr('disabled', true);
  
  });

$(function() {
      $('#codigo').change(function() {  

         var codigo = $(this).val()
         var buscar = 'codigo';
         var pars = "codigo=" + codigo + "&" + "buscar=" + buscar + "&";

         if (codigo != '') {

          $('#div_producto').html('<div class="text-center"><div class="loadingsm"></div></div>');
          $("#div_producto").load("clases/control/producto.php", pars);
          $("#dato_cantidad").focus();

         }else {
            
               $("#div_producto").html('')
               $("#codigo").focus();
               $('#boton_producto').attr('disabled', true);
         }
      })
    })

$(function() {
      $('#nombre').change(function() {  

         var nombre = $(this).val()
         var buscar = 'nombre';
         var pars = "codigo=" + nombre + "&" + "buscar=" + buscar + "&";

         if (nombre != '') {

          $('#div_producto').html('<div class="text-center"><div class="loadingsm"></div></div>');
          $("#div_producto").load("clases/control/producto.php", pars);
          $("#dato_cantidad").focus();

         }else {
            
               $("#div_producto").html('')
               $("#nombre").focus();
               $('#boton_producto').attr('disabled', true);
         }
      })
    })

$(function() {
        $('#dato_sucursal').change(function() { 

        $('#dato_remito').val('')

         })
      })

$(function() {
        $('#dato_proveedor').change(function() { 

        $('#dato_remito').val('')
        $('#dato_sucursal').val('')

         })
      })

$(function() {
        $('#dato_remito').change(function() {  

           var sucursal = $('#dato_sucursal').val()
           var remito = $('#dato_remito').val()
           var proveedor = $('#dato_proveedor').val()
           var remitofinal = sucursal+'-'+remito         
          
           if (sucursal != '' && proveedor != '') {

           // alert(remitofinal)
           
            var pars = "proveedor=" + proveedor + "&" + "remito=" + remitofinal + "&";
            
            $('#remito-ok').html('<div class="text-center"><div class="loadingsm"></div></div>');

            $.ajax({
                url : "clases/control/remito.php",
                data : pars,
                dataType : "json",
                type : "get",

                success: function(data){
                    
                  if (data.success == 'true') {
                   
                    $('#remito-ok').html('<span class="glyphicon glyphicon-ok" style="line-height: 28px;" aria-hidden="true"></span>')

                  } else {
                    
                    $('#remito-ok').html('<span class="glyphicon glyphicon-remove" style="line-height: 28px;" aria-hidden="true"></span>')
                    $('#dato_remito').val('')

                  }
                }
            });
           }else{

                if (sucursal == '') {
                  $("#dato_sucursal").tooltip({title: "Cargar sucursal", placement: "top"});
                  $("#dato_sucursal").tooltip('show');
                };
                if (proveedor == '') {
                  $("#dato_proveedor").tooltip({title: "Cargar proveedor", placement: "top"});
                  $("#dato_proveedor").tooltip('show');
                };
           }
        })
      })

 </script>