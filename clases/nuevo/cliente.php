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
<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); nuevo('cliente')">
 
 <div class="modal-header">
   <h4 class="modal-title">Agregar Cliente</h4>
</div>
<br>

 <div class="well bs-component">
 <div class="row">
 <div class="col-lg-5">
   <fieldset>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Nombre</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_nombre" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Apellido</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_apellido" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">DNI</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_dni" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Mail</label>
        <div class="col-lg-10">
          <input type="mail" class="form-control" autocomplete="off" id="dato_mail" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Teléfono</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_telefono" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Calle</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_calle" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Número</label>
        <div class="col-lg-10">
          <input type="number" class="form-control" autocomplete="off" id="dato_numero" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Localidad</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_localidad" aria-describedby="basic-addon1" required autofocus="">
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
        <label for="inputPassword" class="col-lg-2 control-label">C.Postal</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_codigopostal" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
   </fieldset>
 
 </div>
 <div class="col-lg-7">
 
   <fieldset>

     <div class="panel panel-default">

      <div class="panel-body" id="Panel1" style="height:430px">
      <table class="table table-striped table-hover">
        <thead>
          <tr class="active">
            <th>Apellido, Nombre</th>
            <th>Mail</th>
            <th>DNI</th>
            <th>Modificar</th>
            </tr>
        </thead>
        <tbody>
         
              <?php
 
              $sqlcliente = "SELECT
                    tb_clientes.id_clientes as id,
                    CONCAT(tb_clientes.apellido,', ',tb_clientes.nombre) AS nombre,
                    tb_clientes.telefono AS telefono,
                    tb_clientes.mail AS mail
                    FROM
                    tb_clientes
                    ORDER BY
                    nombre ASC";

              $rscliente = mysqli_query($conexion, $sqlcliente);
              
              $cantidad =  mysqli_num_rows($rscliente);

              if ($cantidad > 0) { // si existen cliente con de esa cliente se muestran, de lo contrario queda en blanco  
             
              while ($datos = mysqli_fetch_assoc($rscliente)){
              $id=utf8_encode($datos['id']);
              $nombre=utf8_decode($datos['nombre']);
              $telefono=utf8_encode($datos['telefono']);
              $mail=utf8_decode($datos['mail']);
                            
              echo '

              <tr>
                <td>'.$nombre.'</td>
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

                echo "No hay clientes cargados.";
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
  
    $('#dato_dni').mask("NNNNNNNNNNN", {'translation': {N: {pattern: /[0-9]/}}, clearIfNotMatch: true});
    $('#dato_codigopostal').mask("AAAA", {'translation': {A: {pattern: /[0-9]/}}, clearIfNotMatch: true});
  
  });
  
  $(function() {

    $('.ver_modal').click(function(){

      var id = $(this).val()    

      $("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
      $('#panel_inicio').load("clases/modifica/upd-cliente.php", {id:id});

    })
  })

</script>