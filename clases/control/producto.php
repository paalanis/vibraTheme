<?php 
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../../index.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
printf("La conexión con el servidor de base de datos falló comuniquese con su administrador: %s\n", mysqli_connect_error());
exit();
}

$codigo=$_REQUEST['codigo'];

	
	$codigo = '%'.$codigo.'%';

	$sqlproducto = "SELECT
	tb_productos.id_productos AS id,
	tb_productos.codigo AS codigo2,
	tb_productos.nombre AS producto,
  IF(tb_productos.id_rubro > 1, 'false', 'true')  AS rubro,
	tb_productos.precio_venta as precio
	FROM
	tb_productos
	WHERE
	CONCAT(tb_productos.nombre,tb_productos.codigo) LIKE '$codigo'
  ORDER BY
  tb_productos.nombre ASC";
	$rsproducto = mysqli_query($conexion, $sqlproducto);


$filas = mysqli_num_rows($rsproducto);

?>

<div class="col-lg-7" >
<select class="form-control" id="dato_producto" required>		
	<option value="">Seleccione producto</option>   
  <?php
  if ($filas > 0) {

	  	$lista = array();
      $campoprecio = array();
	  	while ($sql_producto = mysqli_fetch_assoc($rsproducto)){
	    $idproductos= $sql_producto['id'];
	    $productos = $sql_producto['producto'];
	    $codigo2 = $sql_producto['codigo2'];
	    $precio = $sql_producto['precio'];
      $rubro = $sql_producto['rubro'];
	    $lista[$sql_producto['codigo2']] = $sql_producto['precio'];
      $campoprecio[$sql_producto['codigo2']] = $sql_producto['rubro'];

	    echo utf8_encode('<option value='.$codigo2.'>'.$productos.'</option>');
	  	}
	}else{
		echo utf8_encode('<option value="">NO EXISTE EL PRODUCTO</option>');
    $lista = 0;
    $campoprecio = 0;
	}
  ?>
</select>
</div>
<div class="col-lg-2">
  <input class="form-control" autocomplete="off" value="" placeholder="$$" id="dato_precio" type="text" required disabled>
</div>
<div class="col-lg-3">
   <button type="button" id="boton_producto" class="btn btn-success">Cargar Producto</button>
</div>

<script type='text/javascript'>

$(document).ready(function(){

  $("#dato_producto").focus();
  
    $("#dato_precio").keypress(function(e) {
        //no recuerdo la fuente pero lo recomiendan para
        //mayor compatibilidad entre navegadores.
        var code = (e.keyCode ? e.keyCode : e.which);
        if(code==13){
            
               if ($('#dato_producto').val() != '' && $('#dato_cantidad').val() > 0) {
               
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
                            break;

                          }
                                    
                      } 

                    });
              }else {

                alert('Faltan datos')
              }
        }
    });
});

$('#boton_producto').attr('disabled', true);
var tempArray = <?php echo json_encode($lista); ?>;
var tempArray2 = <?php echo json_encode($campoprecio); ?>;

//alert(tempArray[1]);

// $(document).ready(function () {
  
//    var id_producto = $(this).val()
//    $('#dato_precio').val(tempArray[id_producto])
  
//   });

 $(function() {
        
        $('#dato_producto').change(function() {

           var id_producto = $(this).val()
           var campo = tempArray2[id_producto];
          
           // alert(id_producto)
           // alert(campo)

           $('#dato_precio').val(tempArray[id_producto])
           if (id_producto != '') {
            $('#boton_producto').attr('disabled', false);
            if (campo == 'false') {
              $('#dato_precio').attr('disabled', false);
              $('#dato_precio').val('')
              $('#dato_precio').focus()
            }else{
              $('#dato_precio').attr('disabled', true);
            }
      		 }else{
           $('#boton_producto').attr('disabled', true);
           $('#dato_precio').attr('disabled', true);
      		 }
     
        })
      })



     $(function() {
          $('#boton_producto').click(function() {  
          
            if ($('#dato_producto').val() != '' && $('#dato_cantidad').val() > 0) {
               
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
                            break;

                          }
                                    
                      } 

                    });
              }else {

                alert('Faltan datos')
              }
          })
        })

</script>