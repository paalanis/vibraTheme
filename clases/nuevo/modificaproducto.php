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


 <div class="row">

<!--  <div class="col-lg-1"></div> -->
 <div class="col-lg-12">
 
   <fieldset>

     <div class="panel panel-default">

      <div class="panel-body" id="Panel1" style="height:400px">
      <table class="table table-striped table-hover">
        <thead>
          <tr class="active">
            <th>Producto</th>
            <th>Código</th>
            <th>Precio</th>
            <th>Modificar</th>
            </tr>
        </thead>
        <tbody>
         
              <?php

              if ($_REQUEST['tipo'] == 'porcodigo' ) {
                # code...
                $codigo=$_REQUEST['codigo'];

                $sqlinsumo = "SELECT
                            tb_productos.id_productos AS id_productos,
                            tb_productos.nombre AS productos,
                            tb_productos.codigo AS codigo,
                            tb_productos.precio_venta AS precio
                            FROM
                            tb_productos
                            WHERE
                            tb_productos.codigo = '$codigo'
                            ORDER BY
                            productos ASC";
              }else{

                $codigo = '%'.$_REQUEST['nombre'].'%';

                $sqlinsumo = "SELECT
                            tb_productos.id_productos AS id_productos,
                            tb_productos.nombre AS productos,
                            tb_productos.codigo AS codigo,
                            tb_productos.precio_venta AS precio
                            FROM
                            tb_productos  
                            WHERE
                            CONCAT(tb_productos.nombre,tb_productos.codigo) LIKE '$codigo'
                            ORDER BY
                            productos ASC";

              }

             
              $rsinsumo = mysqli_query($conexion, $sqlinsumo);
              
              $cantidad =  mysqli_num_rows($rsinsumo);

              if ($cantidad > 0) { // si existen insumo con de esa insumo se muestran, de lo contrario queda en blanco  
             
              while ($datos = mysqli_fetch_assoc($rsinsumo)){
              $productos=utf8_decode($datos['productos']);
              $id_productos=utf8_encode($datos['id_productos']);
              $codigo=utf8_encode($datos['codigo']);
              $precio=utf8_encode($datos['precio']);
                            
              echo '

              <tr>
                <td>'.$productos.'</td>
                <td>'.$codigo.'</td>
                <td>'.$precio.'</td>
                <td><button class="ver_modal ver_modal-info ver_modal-xs" id="'.$id_productos.'" value="'.$id_productos.'" type="button"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>
                </tr>
              ';
          
              }   
              }
              ?>
        </tbody>
      </table> 
      <?php
       if ($cantidad == 0){

                echo "No hay productos cargados.";
              }
      ?>
      </div>
      </div>      
      
         
   </fieldset>
  </div> 

  <!-- <div class="col-lg-1"></div> -->

</div> 



<script type="text/javascript">

  
  $(function() {

    $('.ver_modal').click(function(){

      var id = $(this).val()
    

      $("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
      $('#panel_inicio').load("clases/modifica/upd-producto.php", {id:id});

    })
  })

</script>