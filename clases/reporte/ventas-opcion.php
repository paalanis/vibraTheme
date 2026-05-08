<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
printf("La conexión con el servidor de base de datos falló: %s\n", mysqli_connect_error());
exit();
}
date_default_timezone_set("America/Argentina/Mendoza");
$hoy = date("Y-m-d");
?>

<form class="form-horizontal" id="formulario_reporte" method="post">

<div class="modal-header">
   <h4 class="modal-title">Reporte de Ventas</h4>
</div>
<br>

<div class="well bs-component">
<div class="row">
<div class="col-lg-8">
  <fieldset>
    <div class="form-group form-group-sm">
      <label class="col-lg-2 control-label">Desde</label>
      <div class="col-lg-4">
        <input type="date" class="form-control" id="dato_desde" name="dato_desde" value="<?php echo $hoy;?>" required>
      </div>
      <label class="col-lg-2 control-label">Hasta</label>
      <div class="col-lg-4">
        <input type="date" class="form-control" id="dato_hasta" name="dato_hasta" value="<?php echo $hoy;?>" required>
      </div>
    </div>
    <div class="form-group form-group-sm">
      <label class="col-lg-2 control-label">Ver</label>
      <div class="col-lg-10">
        <div class="btn-group" data-toggle="buttons">
          <label class="btn btn-default active">
            <input type="radio" name="dato_origen" value="dia" checked> Ventas del día
          </label>
          <label class="btn btn-default">
            <input type="radio" name="dato_origen" value="acumulado"> Acumuladas
          </label>
        </div>
      </div>
    </div>
  </fieldset>
</div>
</div>
</div>

<div class="modal-footer">
  <div class="form-group form-group-sm">
    <div class="col-lg-7">
      <div align="center" id="div_mensaje_general"></div>
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
  var _btn = document.getElementById("botonExcel1");
  if (_btn) _btn.style.visibility = "hidden";
});
</script>
