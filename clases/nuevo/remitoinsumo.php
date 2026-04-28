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


<div class="panel panel-default">

<div class="panel-body" id="Panel1" style="height:310px">
<table class="table table-striped table-hover">
  <thead>
    <tr class="active">
      <th>Producto-club-color-talle</th>
      <th>Cantidad</th>
      <th>Eliminar</th>
      </tr>
  </thead>
  <tbody>
   
        <?php

        $remito=$_REQUEST['remito'];
        $proveedor=$_REQUEST['proveedor']; 

        $sqlingreso = "SELECT
          tb_remitos.id_remitos AS id,
          tb_remitos.numero AS remito,
          tb_productos.nombre AS producto,
          tb_remitos.cantidad AS cantidad
          FROM
          tb_remitos
          INNER JOIN tb_productos ON tb_productos.id_productos = tb_remitos.id_productos
          WHERE
          tb_remitos.estado = '0' AND tb_remitos.numero = '$remito' AND tb_remitos.id_proveedores = '$proveedor'";
        $rsingreso = mysqli_query($conexion, $sqlingreso);
        
        $cantidad =  mysqli_num_rows($rsingreso);

        if ($cantidad > 0) { // si existen ingreso con de esa ingreso se muestran, de lo contrario queda en blanco  
          
        while ($datos = mysqli_fetch_assoc($rsingreso)){
        $id=mb_convert_encoding($datos['id'], 'UTF-8', 'ISO-8859-1');
        $remito=mb_convert_encoding($datos['remito'], 'UTF-8', 'ISO-8859-1');
        $producto=mb_convert_encoding($datos['producto'], 'UTF-8', 'ISO-8859-1');
        $cantidad=mb_convert_encoding($datos['cantidad'], 'UTF-8', 'ISO-8859-1');
        
        echo '

        <tr>
          <td>'.$producto.'</td>
          <td>'.$cantidad.'</td>
          <td><button type="button" class="ver_modal ver_modal-danger ver_modal-xs" id="'.$id.'" name="'.$proveedor.'" value="'.$remito.'"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button></td>
         </tr>
        ';
    
        }
        ?>       
        <script type="text/javascript">
        $('#dato_proveedor').attr('disabled', true);
        $('#dato_sucursal').attr('disabled', true);
        $('#dato_remito').attr('disabled', true);
        $('#boton_guardar').attr('disabled', false);
        $('#boton_producto').attr('disabled', true);
        </script>
        <?php         
        }
        ?>
  </tbody>
</table> 
<?php
 if ($cantidad == 0){

          echo "No hay productos cargados.";
        
?>       
<script type="text/javascript">
$('#dato_proveedor').attr('disabled', false);
$('#dato_sucursal').attr('disabled', false);
$('#dato_remito').attr('disabled', false);
$('#boton_guardar').attr('disabled', true);
$('#boton_producto').attr('disabled', true);
</script>
<?php  
}
?>
</div>
</div>  

<script type="text/javascript">

  $(function() {
        $('.ver_modal-danger').click(function() {

           var remito = $(this).val()
           var proveedor = $(this).attr('name')
           var id_producto = $(this).attr('id')
                          
           var pars = "id=" + id_producto + "&";

          $('#div_remitos').html('<div class="text-center"><div class="loadingsm"></div></div>');
          $.ajax({
              url : "clases/elimina/remito.php",
              data : pars,
              dataType : "json",
              type : "get",

              success: function(data){
                  
                if (data.success == 'true') {
                  $('#div_remitos').html('<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Se eliminó remito!</div>');
                  $('#div_remitos').load('clases/nuevo/remitoinsumo.php', {remito: remito, proveedor: proveedor});
                  setTimeout("$('#mensaje_general').alert('close')", 2000);


                } else {
                  $('#div_remitos').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');        
                  setTimeout("$('#mensaje_general').alert('close')", 2000);
                }
              
              }

          });

              
        })
      })

 </script>