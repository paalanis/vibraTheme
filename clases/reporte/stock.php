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

if(isset($_REQUEST['dato_producto'])){
 $producto=$_REQUEST['dato_producto'];
}else{
 $producto = '0';
}

$consulta_productos = "";
if ($producto != "0") {
$consulta_productos = "WHERE tb_productos.id_productos = '$producto'";
}
        
$sqlproducto = "SELECT
  tb_productos.nombre AS producto,
  tb_rubro.nombre AS rubro,
  tb_existencias.cantidad AS cantidad,
  tb_productos.descripcion AS descripcion,
  tb_productos.precio_venta AS venta
FROM
tb_existencias
LEFT JOIN tb_productos ON tb_productos.id_productos = tb_existencias.id_productos
LEFT JOIN tb_rubro ON tb_rubro.id_rubro = tb_productos.id_rubro
$consulta_productos
ORDER BY
producto ASC";

$rsproducto = mysqli_query($conexion, $sqlproducto);
$cantidad =  mysqli_num_rows($rsproducto);

if ($cantidad > 0) { // si existen producto con de esa finca se muestran, de lo contrario queda en blanco  
$datos = array();
while ($rows = mysqli_fetch_assoc($rsproducto)){
  $datos[] = $rows;
}

if(isset($_POST["export_data"])) {

    if(!empty($datos)) {

      $filename = "reporte_stock.xls";

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
<div class="panel-body" id="Panel1" style="height:220px">

<table class="table table-striped table-hover">
  <thead>
    <tr class="active">
      <th>Producto</th>
      <th>Rubro</th>
      <th>Cantidad</th>
      </tr>
  </thead>
<tbody> 

  <?php foreach($datos as $dato) { ?>
  <tr>
  <td><?php echo $dato['producto']; ?></td>
  <td><?php echo $dato['rubro']; ?></td>
  <td><?php echo $dato['cantidad']; ?></td>
  </tr>
  <?php }?>

<script type="text/javascript">
document.getElementById("botonExcel1").style.visibility = "visible";
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
