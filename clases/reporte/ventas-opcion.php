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
$sqlproducto = "SELECT
tb_productos.id_productos AS id,
tb_productos.nombre AS producto
FROM
tb_productos
ORDER BY
tb_productos.nombre ASC
";
$rsproducto = mysqli_query($conexion, $sqlproducto);

?>

<form class="form-horizontal" id="formulario_reporte" method="post">

<div class="modal-header">
   <h4 class="modal-title">Reporte de Ventas</h4>
</div>
<br>

 <div class="well bs-component">
 <div class="row">
 <div class="col-lg-6">
   <fieldset>
      <div class="form-group form-group-sm">
        <label  class="col-lg-2 control-label">Desde</label>
        <div class="col-lg-4">
          <input type="date" class="form-control" id="dato_desde" name="dato_desde" value="<?php echo $hoy;?>" aria-describedby="basic-addon1" required>
        </div>
        <label  class="col-lg-2 control-label">Hasta</label>
        <div class="col-lg-4">
          <input type="date" class="form-control" id="dato_hasta" name="dato_hasta" value="<?php echo $hoy;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
     </fieldset>
 </div>

 <div class="col-lg-6">
    <fieldset>

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
          <button type="button" id="boton_buscar" class="btn btn-primary" onclick="reporte('ventas')">Buscar</button>   
          </div>
        </div>
      </div>  
  </div>


<div id="div_reporte"></div>


</form>

<script type="text/javascript">

  $(document).ready(function () {
      
   document.getElementById("botonExcel1").style.visibility = "hidden";
   $('mensaje_general').alert('close');

   })

</script>