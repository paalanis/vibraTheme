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

<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); nuevo('club')">

<div class="modal-header">
   <h4 class="modal-title">Agregar Club</h4>
</div>
<br>

 <div class="well bs-component">
 <div class="row">
 <div class="col-lg-6">
   <fieldset>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Nombre</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_nombre" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
  
   </fieldset>
 </div>
 <div class="col-lg-6">
   <fieldset>

      <div class="panel panel-default">

      <div class="panel-body" id="Panel1" style="height:225px">
      <table class="table table-striped table-hover">
        <thead>
          <tr class="active">
            <th>Nombre</th>
            <th>Modificar</th>
            </tr>
        </thead>
        <tbody>
              <?php
              
               $sqlclub = "SELECT
                            tb_club.nombre as club,
                            tb_club.id_club as id_club
                            FROM
                            tb_club
                            ORDER BY
                            tb_club.nombre ASC
                            ";
              $rsclub = mysqli_query($conexion, $sqlclub);
              
              $cantidad =  mysqli_num_rows($rsclub);

              if ($cantidad > 0) { // si existen club con de esa club se muestran, de lo contrario queda en blanco  
             
              while ($datos = mysqli_fetch_assoc($rsclub)){
              $club=utf8_decode($datos['club']);
              $id_club=mb_convert_encoding($datos['id_club'], 'UTF-8', 'ISO-8859-1');
              echo '

              <tr>
                <td>'.$club.'</td>
                <td><button class="ver_modal ver_modal-info ver_modal-xs" id="'.$id_club.'" value="'.$id_club.'" type="button"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>
              </tr>
              ';
              
          
              }   
              }
              
              ?>
        </tbody>
      </table> 
      <?php
      if ($cantidad == 0){
        echo "No hay clubes cargados.";
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
  
  $(function() {

    $('.ver_modal').click(function(){

      var id = $(this).val()
    

      $("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
      $('#panel_inicio').load("clases/modifica/upd-club.php", {id:id});

    })
  })

</script>