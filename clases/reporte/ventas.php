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

if(isset($_REQUEST['dato_desde']) and isset($_REQUEST['dato_hasta'])){
 $desde=$_REQUEST['dato_desde'];
 $hasta=$_REQUEST['dato_hasta'];
}else{
 $desde = $hoy;
 $hasta = $hoy;
}

        
$sqlventas = "SELECT 
round(sum(`subtotal`),2) as total, 
`id_cierre` as cierre 
FROM 
`tb_ventas_acumulado` 
WHERE `fecha` between '$desde 00:00:00' and '$hasta 23:59:59' 
GROUP BY `id_cierre` 
ORDER BY `id_cierre` DESC";

$rsventas = mysqli_query($conexion, $sqlventas);
$cantidad =  mysqli_num_rows($rsventas);

if ($cantidad > 0) { // si existen ventas con de esa finca se muestran, de lo contrario queda en blanco  
$datos = array();

while ($rows = mysqli_fetch_assoc($rsventas)){
  $datos[] = $rows;
}

if(isset($_POST["export_data"])) {

    if(!empty($datos)) {

      $filename = "reporte_ventas.xls";

      header("Content-Type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=".$filename);

      $mostrar_columnas = false;

      foreach($datos as $dato) {

        if(!$mostrar_columnas) {

          echo implode("\t", array_keys($dato)) . "\n";
          $mostrar_columnas = true;

        }

        echo implode("\t", array_values($dato)) . "\n";

      }
    }else{
      echo 'No hay datos a exportar';
    }
exit;
}
?>

<div class="panel panel-default">
<div class="panel-body" id="Panel1" style="height:300px">

<table class="table table-striped table-hover">
  <thead>
    <tr class="active">
      <th>Número de cierre</th>
      <th>Total</th>
      </tr>
  </thead>
<tbody> 

  <?php 

  $total_vta = 0;
  foreach($datos as $dato) { ?>

  <tr>
  <td><?php echo $dato['cierre']; ?></td>
  <td><?php echo $dato['total']; ?></td>
  </tr>

  <?php
  
  $total_vta = $dato['total'] + $total_vta;


   }

  ?>

<script type="text/javascript">
document.getElementById("botonExcel1").style.visibility = "hidden";
</script>
<?php
}
?>
</tbody>
</table>
<?php
if ($cantidad == 0){
echo "No hay registros";
?>
<script type="text/javascript">
document.getElementById("botonExcel1").style.visibility = "hidden";
</script>
<?php
}
?>
</div>
</div>
<?php if ($cantidad == 0){}else{echo '<h3>Total de ventas del período: $'.$total_vta.'</h3>';} ?>
<form class="form-horizontal" id="Exportar_excel" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">

<div class="modal-footer">
<div class="row">
<div class="form-group form-group-sm">
<div class="col-lg-12">
  <div align="right">
    <button type="submit" class="btn btn-info" id="botonExcel1" name="export_data" value="Export to excel"  aria-label="Left Align">
    <span class="glyphicon glyphicon-save" aria-hidden="true"></span> Descargar</button>
  </div>
</div>
</div>
</div>
</div>

</form>

<script type="text/javascript">
$(function() {
$('.form-control').change(function() {
 document.getElementById("botonExcel1").style.visibility = "hidden";       
  })
})

</script>
