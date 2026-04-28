<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
if (!isset($_SESSION['cierre'])) {
header("Location: abrecaja.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
printf("La conexión con el servidor de base de datos falló comuniquese con su administrador: %s\n", mysqli_connect_error());
exit();
}
date_default_timezone_set("America/Argentina/Mendoza");
$hoy = date("Y-m-d H:i:s");

$cierre = $_SESSION['cierre'];
//Elimina pruductos pendientes
// mysqli_select_db($conexion,'$basedatos');
// $sql = "DELETE FROM tb_ventas WHERE tb_ventas.estado = '0' AND tb_ventas.id_cierre = '$cierre'";
// mysqli_query($conexion,$sql);

$sqlcliente = "SELECT
tb_clientes.id_clientes AS id,
CONCAT(tb_clientes.apellido,' ',tb_clientes.nombre) AS cliente
FROM
tb_clientes
ORDER BY
prioridad ASC,
cliente ASC";
$rscliente = mysqli_query($conexion, $sqlcliente);

$sqlcondicion = "SELECT
tb_condicion_venta.id_condicion_venta AS id,
tb_condicion_venta.nombre AS condicion,
tb_condicion_venta.descuento AS descuento,
tb_condicion_venta.cupon AS cupon
FROM
tb_condicion_venta
WHERE
tb_condicion_venta.dias LIKE CONCAT('%',DAYOFWEEK(CURDATE()),'%')
ORDER BY
condicion ASC
";
$rscondicion = mysqli_query($conexion, $sqlcondicion);


//buscamos si el ultimo tkt del cierre tiene articulos en pendientes, en ese caso recargamos es numero de tkt

$sqlfactura = "SELECT
ifnull(Max(tb_ventas.numero_factura),1) as factura,
tb_ventas.id_clientes as cliente
FROM
tb_ventas
WHERE
tb_ventas.id_sucursal = '1' AND tb_ventas.id_cierre = '$cierre' AND tb_ventas.estado = '0'
";
$rsfactura = mysqli_query($conexion, $sqlfactura);
$sql_factura = mysqli_fetch_assoc($rsfactura);
$factura = $sql_factura['factura'];
$cliente = $sql_factura['cliente'];

if ($factura != 1) {
  
  $factura = $sql_factura['factura'];
  $pendiente = 'si';
  
}else{

        $pendiente = 'no';
        // Buscamos el ultimo numero de factura
        $sqlfactura = "SELECT
        ifnull(Max(tb_ventas.numero_factura) + 1,1) as factura
        FROM
        tb_ventas
        WHERE
        tb_ventas.id_sucursal = '1' AND tb_ventas.id_cierre = '$cierre'
        ";
        $rsfactura = mysqli_query($conexion, $sqlfactura);
        $sql_factura = mysqli_fetch_assoc($rsfactura);
        $factura = $sql_factura['factura'];

        if ($factura != 1) {
          
          $factura = $sql_factura['factura'];
          
        }else{

              $sqlfactura = "SELECT
              ifnull(Max(tb_ventas_acumulado.numero_factura) + 1,1) as factura
              FROM
              tb_ventas_acumulado
              WHERE
              tb_ventas_acumulado.id_sucursal = '1'
              ";
              $rsfactura = mysqli_query($conexion, $sqlfactura);
              $sql_factura = mysqli_fetch_assoc($rsfactura);
              $factura = $sql_factura['factura'];
        }
}





?>
<form class="form-horizontal" id="formulario_nuevo" role="form">
 
   <input type="hidden" class="form-control" id="factura" value="<?php echo $factura;?>" aria-describedby="basic-addon1" required>
   <input type="hidden" class="form-control" id="cliente" value="<?php echo $cliente;?>" aria-describedby="basic-addon1" required>
   <input type="hidden" class="form-control" id="dato_sucursal" value="1" aria-describedby="basic-addon1" required>
   <input type="hidden" class="form-control" id="pendiente" value="<?php echo $pendiente;?>" aria-describedby="basic-addon1" required>
   <input type="hidden" class="form-control" id="cierre" value="<?php echo $cierre;?>" aria-describedby="basic-addon1" required>

 <div class="well bs-component">
 <div class="row">
<!--  <div class="col-lg-1"></div>  -->
 <div class="col-lg-12">
   <fieldset>
      <div class="form-group form-group-sm">
        <div class="col-lg-12">
          <div class="col-lg-2">
            <input type="datetime" class="form-control" id="dato_fecha" value="<?php echo $hoy;?>" aria-describedby="basic-addon1" required disabled>
          </div>
            <label for="inputPassword" class="col-lg-2 control-label">Numero de Ticket</label>
          <div class="col-lg-3">
            <input type="text" class="form-control" id="dato_factura" value="<?php echo $factura;?>" aria-describedby="basic-addon1" disabled required>
          </div>
          <div class="col-lg-5">  
            <select class="form-control" id="dato_cliente" required>   
              <!-- <option value="">Seleccione Cliente</option> -->
              <?php
              while ($sql_cliente = mysqli_fetch_assoc($rscliente)){
                $idcliente= $sql_cliente['id'];
                $cliente = $sql_cliente['cliente'];
                echo utf8_encode('<option value='.$idcliente.'>'.$cliente.'</option>');
              }
              ?>
            </select>
          </div>
        </div>
      </div>
      <div class="form-group form-group-md">
        <div class="col-lg-12">
          <label for="inputPassword" class="col-lg-2 control-label">Cantidad de producto</label>
          <div class="col-lg-2">
            <input type="number" class="form-control" id="dato_cantidad" min='0' value="1" aria-describedby="basic-addon1">
          </div>
          <div class="col-lg-4">
            <input type="text" class="form-control" id="dato_codigo" autocomplete='off' value="" placeholder='Código' aria-describedby="basic-addon1">
          </div>
          <div class="col-lg-4">
            <input type="text" class="form-control" id="nombre" value="" autocomplete='off' placeholder='Buscar por nombre o código' aria-describedby="basic-addon1">
          </div>
        </div>
      </div>    
 
   </fieldset>
 
 </div>
 
 <!-- <div class="col-lg-2"></div> -->
</div>

<div class="row">
  <!-- <div class="col-lg-1"></div>  -->
  <div class="col-lg-10">   
    
    <div class="form-group form-group-md">
      <!-- <label class="col-lg-3 control-label">Producto</label> -->
      <div class="col-lg-9" id="div_producto">
        
      <!-- se carga el pruducto buscado -->

      </div>
    </div>

  </div>
  
  <div class="col-lg-2">
    <!-- <button type="submit" id="boton_producto" class="btn btn-success">Cargar Producto</button> -->
  </div>
</div>

<div class="row">
  <div class="col-lg-10" id='div_duplicado'>
   <!-- mensaje duplicado -->
  </div>  
</div>

</div>

<div class="well bs-component" style="background-color:#cad0d273">
 <div class="row">
  
   <fieldset id="div_remitos">
   </fieldset>

 </div>
</div>

 <div class="well bs-component" style="background-color:#4f636b4d">
   <div class="row">
      
     <div class="col-lg-2"></div>
     <div class="col-lg-5">
       
         <label class="col-lg-3 control-label">Venta</label>
          <div class="col-lg-9">
            <select class="form-control" id="dato_condicion" disabled>   
                <option value="0"></option>
                <?php
                while ($sql_condicion = mysqli_fetch_assoc($rscondicion)){
                  $idcondicion= $sql_condicion['id'];
                  $condicion = $sql_condicion['condicion'];
          
                  $cupon[$sql_condicion['id']] = $sql_condicion['cupon'];
                  $descuento[$sql_condicion['id']] = $sql_condicion['descuento'];

                  echo utf8_encode('<option value='.$idcondicion.'>'.$condicion.'</option>');
                }
                ?>
              </select>
          </div>
        
          <label class="col-lg-3 control-label">Cupón</label>
          <div class="col-lg-9">
            <input type="text" class="form-control" id="dato_cupon" value="" aria-describedby="basic-addon1" disabled>
          </div>
          <label class="col-lg-3 control-label">Monto $</label>
          <div class="col-lg-9">
            <input type="text" class="form-control" autocomplete='off' id="dato_monto" value="" aria-describedby="basic-addon1" disabled>
          </div>

     </div>

     <div class="col-lg-5">
        <fieldset>      
        
          <label class="col-lg-3 control-label">Subtotal $</label>
          <div class="col-lg-9">
            <input type="text" class="form-control" id="subtotal" value="0" aria-describedby="basic-addon1" required readonly>
          </div>      
          <label class="col-lg-3 control-label">Total $</label>
          <div class="col-lg-9">
            <input type="text" class="form-control" id="total" value="0" aria-describedby="basic-addon1" readonly>
          </div>
          <label class="col-lg-3 control-label">Vuelto $</label>
          <div class="col-lg-9">
            <input type="text" class="form-control" id="dato_vuelto" value="0" aria-describedby="basic-addon1" disabled>
          </div>
        
     </div>
     </fieldset>
   </div> 
 </div> 
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
          <button type="button" id="boton_guardar" class="btn btn-primary" onclick="nuevo('factura')">Guardar Factura</button>  
          </div>
        </div>
      </div>  
  </div>
 </form>

<script type="text/javascript">

  $(document).ready(function () {
    
      $('#boton_guardar').attr('disabled', true);
      $("#dato_codigo").focus()
      //$('#dato_codigo').attr('disabled', true);
      //$('#nombre').attr('disabled', true);
      
      //cargamos los pendientes en caso de haber
      //
    
      
      
      if ($('#pendiente').val() == 'si') {

         var factura = $('#dato_factura').val()
         var cierre = $('#cierre').val()
         var cliente = $('#cliente').val()
         $("#dato_cliente").val(cliente); 

         $('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: factura, cierre: cierre});
         $("#dato_cantidad").val(1)
         $("#dato_codigo").val('')
         $("#nombre").val('')
         $("#dato_codigo").focus()
         $('#pendiente').val('no')

      };

        $("#formulario_nuevo").keypress(function(e) {
          //no recuerdo la fuente pero lo recomiendan para
          //mayor compatibilidad entre navegadores.
          var code = (e.keyCode ? e.keyCode : e.which);
          if(code==13){

            var estado = $('#boton_guardar').prop('disabled');
            
            if (estado == false) {

              var funcion = nuevo('factura')
              //alert(estado)
            };
           
          }

        });
    
    });

  $(function() {
        $('#dato_codigo').change(function() {  
        
          if ($(this).val() != '' && $('#dato_cantidad').val() > 0) {
             
              var pars = ''
              var campos = Array()
              var campospasan = Array()

              $("#formulario_nuevo").find(':input').each(function(){
                        
                      $(this).attr('id')
                      var dato = $(this).attr('id').split('_',2) 
                    
                      if (dato[0] == 'dato') {
                         campos.push("dato_"+dato[1])
                        campospasan.push("dato_"+dato[1])
                      };
                        
                    });
              
               for (i = 0; i < campos.length; i++) {
                campo = document.getElementById(campos[i]);

                pars =pars + campospasan[i] + "=" + campo.value + "&";
               }
            
              //alert(pars);
                  
                  $("#div_remitos").html('<div class="text-center"><div class="loadingsm"></div></div>');

                  $.ajax({
                      url : "clases/guardar/producto-caja.php",
                      data : pars,
                      dataType : "json",
                      type : "get",

                      success: function(data){


                        switch(data.success){

                          case 'true':
                           $('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                           $("#dato_cantidad").val(1)
                           $("#dato_codigo").val('')
                           $("#nombre").val('')
                           $("#dato_codigo").focus()
                          break;

                          case 'no_existe':
                           $('#div_duplicado').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Producto inexistente!</div>');        
                           setTimeout("$('#mensaje_general').alert('close')", 2000);
                           $('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                           $("#dato_cantidad").val('1')
                           $("#dato_codigo").val('')
                           $("#nombre").val('')
                           $("#dato_codigo").focus()
                          break;

                          case 'false':
                           $('#div_remitos').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');       
                           setTimeout("$('#mensaje_general').alert('close')", 2000);
                           $("#dato_codigo").focus()
                          break;

                        }
                                  
                    } 

                  });
            }else {

              alert('Faltan datos')
            }
        })
      })

  $(function() {
        $('#nombre').change(function() {  

           var codigo = $(this).val()
           var buscar = 'nombre';
           var pars = "codigo=" + codigo + "&" + "buscar=" + buscar + "&";

           if (codigo != '') {

            $('#div_producto').html('<div class="text-center"><div class="loadingsm"></div></div>');
            $("#div_producto").load("clases/control/producto.php", pars);
            

           }else {
              
                 $("#div_producto").html('')
                 $("#dato_codigo").focus();
                 //$('#boton_producto').attr('disabled', true);
                 
           }
        })
      })


  // $(function() {
  //       $('#dato_cliente').change(function() {  

  //          var cliente = $(this).val()

  //          if (cliente != '') {

  //           $('#dato_codigo').attr('disabled', false);
  //           $('#nombre').attr('disabled', false);

  //          }else {
              
  //           $('#dato_codigo').attr('disabled', true);
  //           $('#nombre').attr('disabled', true);
                 
  //          }
  //       })
  //     })

  var condicion_cupon = <?php echo json_encode($cupon); ?>;
  var condicion_descuento = <?php echo json_encode($descuento); ?>;

  $(function() {
        $('#dato_condicion').change(function() {  

           var id = $(this).val()

           var dato_cupon = condicion_cupon[id]
           var dato_descuento = condicion_descuento[id]
           var subtotal = $('#subtotal').val()
           
           $('#total').val(parseFloat($('#subtotal').val())+parseFloat($('#subtotal').val()*dato_descuento/100))

           if (id == '0') {

           $('#boton_guardar').attr('disabled', true);
           $('#total').val(0)
           $('#dato_vuelto').val(0)
           $('#dato_monto').val('')
           $('#dato_monto').attr('disabled', true);
           
           }else{

           //$('#boton_guardar').attr('disabled', false);

             if (dato_cupon == '1') {

              $('#dato_cupon').attr('disabled', false);
              $('#boton_guardar').attr('disabled', false);
              $('#dato_monto').attr('disabled', true);
              $('#dato_monto').val('')
              $('#dato_vuelto').val(0)
              
             }else {

              $('#dato_cupon').attr('disabled', true);
              $('#dato_cupon').val('')
              $('#dato_monto').attr('disabled', false);
              $('#boton_guardar').attr('disabled', true);
              $('#dato_monto').focus()
              
             }

             
          }

        })
      })

  $(function() {
        $('#dato_monto').change(function() {  

           var efectivo = parseFloat($(this).val())

           if (efectivo != '') {

             var apagar = parseFloat($('#total').val());
             var vuelto = efectivo - apagar;
             var vuelto = Number(vuelto.toFixed(2))
             if (efectivo >= apagar) {

               $('#dato_vuelto').val(vuelto)
               $('#boton_guardar').attr('disabled', false);
              
             }else{

               alert('Efectivo insufiente')
               $('#dato_monto').val('')
               $('#dato_vuelto').val(0)
               $('#boton_guardar').attr('disabled', true);
               $('#dato_monto').focus()

             };


           }else {
              
            $('#boton_guardar').attr('disabled', true);
            $('#dato_vuelto').val(0)
                 
           }
        })
      })

 

 </script>