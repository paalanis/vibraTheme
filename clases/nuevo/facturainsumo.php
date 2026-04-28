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
?>

<!-- <div class="panel panel-default"> -->

<!-- <div class="panel-body" id="Panel1"> -->
<div class="panel-body" id="Panel1" style="height:200px">
<table class="table table-striped table-hover">
  <thead>
    <tr class="active">
      <th>Código</th>
      <th>Cantidad</th>
      <th>Descripcion</th>
      <th>Precio</th>
      <th>Subtotal</th>
      <th>Eliminar</th>
      </tr>
  </thead>
  <tbody>
   
        <?php

        $factura=$_REQUEST['factura'];
        //$cliente=$_REQUEST['cliente'];
        $cierre=$_REQUEST['cierre']; 

        $sqlingreso = "SELECT
            tb_ventas.id_ventas AS id,
            tb_productos.nombre AS producto,
            tb_productos.codigo as codigo,
            tb_ventas.precio_venta AS precio,
            tb_ventas.cantidad AS cantidad,
            tb_ventas.subtotal AS subtotal
            FROM
            tb_ventas
            INNER JOIN tb_productos ON tb_productos.id_productos = tb_ventas.id_productos
            WHERE
            tb_ventas.numero_factura = '$factura' AND
            tb_ventas.id_cierre = '$cierre' AND
            tb_ventas.estado = '0'";
        $rsingreso = mysqli_query($conexion, $sqlingreso);
        
        $filas =  mysqli_num_rows($rsingreso);

        if ($filas > 0) { // si existen ingreso con de esa ingreso se muestran, de lo contrario queda en blanco  
        
        $total_fc = 0;
        $num=0;  
        
        while ($datos = mysqli_fetch_assoc($rsingreso)){
        $id=mb_convert_encoding($datos['id'], 'UTF-8', 'ISO-8859-1');
        $producto=mb_convert_encoding($datos['producto'], 'UTF-8', 'ISO-8859-1');
        $codigo=mb_convert_encoding($datos['codigo'], 'UTF-8', 'ISO-8859-1');
        $precio=mb_convert_encoding($datos['precio'], 'UTF-8', 'ISO-8859-1');
        $cantidad=mb_convert_encoding($datos['cantidad'], 'UTF-8', 'ISO-8859-1');
        $subtotal=mb_convert_encoding($datos['subtotal'], 'UTF-8', 'ISO-8859-1');
        
        $total_fc = $total_fc + $subtotal;
        $num= $num + 1;

        echo '

        <tr tabindex="'.$num.'"">
          <td>'.$codigo.'</td>
          <td>'.round($cantidad,2).'</td>
          <td>'.$producto.'</td>
          <td>'.round($precio,2).'</td>
          <td>'.round($subtotal,2).'</td>
          <td><button type="button" class="ver_modal ver_modal-danger ver_modal-xs" id="'.$id.'" value="'.$factura.'"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button></td>
         </tr>
        ';
    
        }
        ?>       
        <script type="text/javascript">
        $('#dato_cliente').attr('disabled', true);
        $('#subtotal').val(<?php echo round($total_fc,2);?>);
        $('#dato_condicion').attr('disabled', false);
        </script>
        <?php
        }
        ?>
  </tbody>
</table>
</div>

<?php
 if ($filas == 0){
?>       
<script type="text/javascript">
$('#dato_cliente').attr('disabled', false);
$('#subtotal').val(0);
$("#dato_codigo").focus()
$('#dato_condicion').attr('disabled', true);
</script>
<?php
}
?>
<!-- </div> -->
<!-- </div>   -->

<script type="text/javascript">

$(document).ready(function(){

  $('#dato_condicion').val('');
  $('#total').val(0);
  $('#dato_monto').val('')
  $('#dato_vuelto').val(0)
  $('#total').val(0)
  $('#dato_monto').attr('disabled', true);
  $('#dato_cupon').attr('disabled', true);
  $("#div_producto").html('')
  $('#boton_guardar').attr('disabled', true);

});

  $(function() {
        $('.ver_modal-danger').click(function() {

          var cierre = <?php echo $cierre;?>;
          var factura = $(this).val();
          var id_producto = $(this).attr('id');
          $('#panel_inicio').load('clases/nuevo/autoriza.php', {id_producto: id_producto, cierre: cierre, factura: factura})


          //  var factura = $(this).val()
          //  var id_producto = $(this).attr('id')
          //  var cierre = <?php echo $cierre;?>
                          
          //  var pars = "id=" + id_producto + "&";

          // $('#div_remitos').html('<div class="text-center"><div class="loadingsm"></div></div>');
          // $.ajax({
          //     url : "clases/elimina/factura.php",
          //     data : pars,
          //     dataType : "json",
          //     type : "get",

          //     success: function(data){
                  
          //       if (data.success == 'true') {
          //         $('#div_remitos').html('<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Se eliminó producto!</div>');
          //         $('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: factura, cierre: cierre});
          //         setTimeout("$('#mensaje_general').alert('close')", 2000);
          //         $("#dato_codigo").focus()


          //       } else {
          //         $('#div_remitos').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');        
          //         setTimeout("$('#mensaje_general').alert('close')", 2000);
          //         $("#dato_codigo").focus()
          //       }
              
          //     }

          // });

              
        })
      })


 $(window).ready(function(){
  $("#Panel1").animate({ scrollTop: $(document).height()}, 1000);    
  });



 </script>