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
$sqliva = "SELECT
tb_iva_condicion.id_iva_condicion as id_iva_condicion,
tb_iva_condicion.nombre as nombre
FROM
tb_iva_condicion
ORDER BY
nombre ASC";
$rsiva = mysqli_query($conexion, $sqliva);
?>
<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); nuevo('proveedor')">
 
 <div class="modal-header">
   <h4 class="modal-title">Agregar Proveedor</h4>
</div>
<br>

 <div class="well bs-component">
 <div class="row">
 <div class="col-lg-5">
   <fieldset>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Nombre</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_nombre" aria-describedby="basic-addon1" required autofocus>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">CUIT</label>
        <div class="col-lg-10">
          <input type="number" class="form-control" autocomplete="off" id="dato_cuit" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Dirección</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_direccion" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-2 control-label">Provincia</label>
        <div class="col-lg-10">
          <select class="form-control" id="dato_provincia" required>
              <option value=""></option>   
              <option value="Buenos Aires">Buenos Aires</option>
              <option value="Cordoba">Cordoba</option>
              <option value="Mendoza">Mendoza</option>
              <option value="San Luis">San Luis</option>
              <option value="San Juan">San Juan</option>
              <option value="Santa Fe">Santa Fe</option>
          </select>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Localidad</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_localidad" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Teléfono</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_telefono" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Mail</label>
        <div class="col-lg-10">
          <input type="mail" class="form-control" autocomplete="off" id="dato_mail" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>     
   </fieldset>
 
 </div>
 <div class="col-lg-7">
 
   <fieldset>

     <div class="panel panel-default">

      <div class="panel-body" id="Panel1" style="height:230px">
      <table class="table table-striped table-hover">
        <thead>
          <tr class="active">
            <th>Proveedor</th>
            <th>CUIT</th>
            <th>Teléfono</th>
            <th>Mail</th>
            <th>Modificar</th>
            </tr>
        </thead>
        <tbody>
         
              <?php
 
              $sqlinsumo = "SELECT
                    tb_proveedores.id_proveedores as id,
                    tb_proveedores.nombre AS nombre,
                    tb_proveedores.cuit AS cuit,
                    tb_proveedores.telefono AS telefono,
                    tb_proveedores.mail AS mail
                    FROM
                    tb_proveedores
                    ORDER BY
                    nombre ASC";

              $rsinsumo = mysqli_query($conexion, $sqlinsumo);
              
              $cantidad =  mysqli_num_rows($rsinsumo);

              if ($cantidad > 0) { // si existen insumo con de esa insumo se muestran, de lo contrario queda en blanco  
             
              while ($datos = mysqli_fetch_assoc($rsinsumo)){
              $id=utf8_encode($datos['id']);
              $nombre=utf8_decode($datos['nombre']);
              $cuit=utf8_encode($datos['cuit']);
              $telefono=utf8_decode($datos['telefono']);
              $mail=utf8_decode($datos['mail']);
                            
              echo '

              <tr>
                <td>'.$nombre.'</td>
                <td>'.$cuit.'</td>
                <td>'.$telefono.'</td>
                <td>'.$mail.'</td>
                <td><button class="ver_modal ver_modal-info ver_modal-xs" id="'.$id.'" value="'.$id.'" type="button"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>
                </tr>
              ';
          
              }   
              }
              ?>
        </tbody>
      </table> 
      <?php
       if ($cantidad == 0){

                echo "No hay proveedores cargados.";
              }
      ?>
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
          <button type="submit" id="boton_guardar" class="btn btn-primary">Guardar</button>  
          </div>
        </div>
      </div>  
  </div>

</form>

<script type="text/javascript">

  $(document).ready(function () {
  
    $('#dato_cuit').mask("NNNNNNNNNNN", {'translation': {N: {pattern: /[0-9]/}}, clearIfNotMatch: true});
    $('#dato_codigopostal').mask("AAAA", {'translation': {A: {pattern: /[0-9]/}}, clearIfNotMatch: true});
  
  });
  
  $(function() {

    $('.ver_modal').click(function(){

      var id = $(this).val()
    

      $("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
      $('#panel_inicio').load("clases/modifica/upd-proveedor.php", {id:id});

    })
  })

</script>