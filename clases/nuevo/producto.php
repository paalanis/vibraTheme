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
$sqlrubro = "SELECT
tb_rubro.id_rubro as id_rubro,
tb_rubro.nombre as nombre
FROM
tb_rubro
ORDER BY
nombre ASC";
$rsrubro = mysqli_query($conexion, $sqlrubro); 
// $sqltalle = "SELECT
// tb_talle.id_talle as id_talle,
// tb_talle.nombre as nombre
// FROM
// tb_talle
// ORDER BY
// nombre ASC";
// $rstalle = mysqli_query($conexion, $sqltalle);
$sqliva = "SELECT
tb_iva_condicion.id_iva_condicion as id_iva,
tb_iva_condicion.nombre as nombre
FROM
tb_iva_condicion
ORDER BY
nombre ASC";
$rsiva = mysqli_query($conexion, $sqliva);
// $sqlclub = "SELECT
// tb_club.id_club as id_club,
// tb_club.nombre as nombre
// FROM
// tb_club
// ORDER BY
// nombre ASC";
// $rsclub = mysqli_query($conexion, $sqlclub);   
?>
<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); nuevo('producto')">
 
 <div class="modal-header">
   <h4 class="modal-title">Agregar Producto</h4>
</div>
<br>

 <div class="well bs-component">
 <div class="row">
 <div class="col-lg-2"></div>
 <div class="col-lg-7">
   <fieldset>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Nombre</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_nombre" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Descripción</label>
        <div class="col-lg-9">
          <textarea class="form-control" autocomplete="off" rows="1" id="dato_descripcion"></textarea>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Precio costo con IVA</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_costo" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Precio venta con IVA</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_venta" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Foto</label>
        <div class="col-lg-9">
         <!--  <input type="text" class="form-control" autocomplete="off" id="dato_nombre" aria-describedby="basic-addon1" required> -->
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Rubro</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_rubro" required>   
              <option value=""></option>
              <?php
              while ($sql_rubro = mysqli_fetch_assoc($rsrubro)){
                $idrubro= $sql_rubro['id_rubro'];
                $rubro = $sql_rubro['nombre'];

                echo utf8_encode('<option value='.$idrubro.'>'.$rubro.'</option>');
                
              }
              ?>
            </select>
        </div>
      </div>
      <!-- <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Club</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_club" required>   
              <option value=""></option>
              <?php
              while ($sql_club = mysqli_fetch_assoc($rsclub)){
                $idclub= $sql_club['id_club'];
                $club = $sql_club['nombre'];

                echo utf8_encode('<option value='.$idclub.'>'.$club.'</option>');
                
              }
              ?>
            </select>
        </div>
      </div> -->
      <!-- <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Color</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_color">   
              <option value=""></option>
              <option value="Amarillo">Amarillo</option>
              <option value="Azul">Azul</option>
              <option value="Blanco">Blanco</option>
              <option value="Celeste">Celeste</option>
              <option value="Negro">Negro</option>
              <option value="Rojo">Rojo</option>
              <option value="Otros">Otros</option>
            </select>
        </div>
      </div> -->
      <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">IVA</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_iva" required>   
              <option value=""></option>
              <?php
              while ($sql_iva = mysqli_fetch_assoc($rsiva)){
                $idiva= $sql_iva['id_iva'];
                $iva = $sql_iva['nombre'];

                echo utf8_encode('<option value='.$idiva.'>'.$iva.'</option>');
                
              }
              ?>
            </select>
        </div>
      </div>
     <!--  <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Talle</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_talle" required>   
              <option value=""></option>
              <?php
              while ($sql_talle = mysqli_fetch_assoc($rstalle)){
                $idtalle= $sql_talle['id_talle'];
                $talle = $sql_talle['nombre'];

                echo utf8_encode('<option value='.$idtalle.'>'.$talle.'</option>');
                
              }
              ?>
            </select>
        </div>
      </div> -->
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Codigo</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_codigo" aria-describedby="basic-addon1" required>
        </div>
      </div>

      <div align="right">
      <h3><span class="label label-default">Uso exclusivo para productos que pasen por balanza</span></h3>
      </div>
      
      <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Pesable en balanza</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_pesable">   
             <option value="1"></option>
             <option value="1">SI</option>
             <option value="0">NO</option>
            </select>
        </div>
      </div>

   </fieldset>
 
 </div>
 <div class="col-lg-3"></div>

 

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
  $('#dato_venta').mask("##.00", {reverse: true});
  $('#dato_costo').mask("##.00", {reverse: true});
  });


</script>