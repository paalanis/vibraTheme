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

if(isset($_REQUEST['dato_cliente'])){
 $cliente=$_REQUEST['dato_cliente'];
}else{
 $cliente = '0';
}

$consulta_clientes = "";
if ($cliente != "0") {
$consulta_clientes = "and tb_clientes.id_clientes = '$cliente'";
}
        
$sqlventas = "SELECT
tb_ventas_acumulado.id_clientes as id_cliente,
round(sum(`subtotal`),2) AS total,
Concat(tb_clientes.apellido,' ', tb_clientes.nombre) as cliente
FROM
tb_ventas_acumulado
INNER JOIN tb_clientes ON tb_clientes.id_clientes = tb_ventas_acumulado.id_clientes
WHERE
tb_ventas_acumulado.fecha BETWEEN '$desde 00:00:00' AND '$hasta 23:59:59' $consulta_clientes
GROUP BY
tb_ventas_acumulado.id_clientes
ORDER BY
total DESC
";

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
      <th>Cliente</th>
      <th>Total acumulado</th>
      <th>Reporte PDF</th>
      </tr>
  </thead>
<tbody> 

  <?php 

  $total_vta = 0;
  foreach($datos as $dato) { ?>

  <tr>
  <td><?php echo $dato['cliente']; ?></td>
  <td><?php echo $dato['total']; ?></td>
  <td id='div_pdf'><button class="ver_modal ver_modal-info ver_modal-xs" style='width: 80px; height: 25px;' id="<?php echo $dato['id_cliente']; ?>" value="<?php echo $dato['id_cliente'].'_'.$dato['cliente'].'_'.$dato['total']; ?>" type="button"><span class="glyphicon glyphicon-save" aria-hidden="true"></span></button></td>
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

  
  $(function() {

    $('.ver_modal').click(function(){

      var id = $(this).val()
      
      var pars = ''
      var campos = Array()
      var campospasan = Array()

      $("#formulario_reporte").find(':input').each(function(){
                
              $(this).attr('id')
              var dato = $(this).attr('id').split('_',2) 
            
              if (dato[0] == 'dato') {
                 campos.push("dato_"+dato[1])
                campospasan.push("dato_"+dato[1])
              };
                
            });
      
       for (i = 0; i < campos.length; i++) {
        campo = document.getElementById(campos[i]);

        pars =pars + campospasan[i] + "=" + campo.value + "&";
       }  
        
        var dato_cliente = id.split('_',3);
        var id = dato_cliente[0];
        var cliente = dato_cliente[1];
        var total = dato_cliente[2];

        pars = pars + "id_cliente"+"="+id+ "&" + "cliente"+"="+cliente+ "&" + "total"+"="+total+ "&";
      //alert(pars)

      $("#div_pdf").html('<div class="text-center"><div class="loadingsm"></div></div>');
      location.href = 'clases/pdf/liquidacion.php/?'+pars+'';
      $("#div_reporte").load("clases/reporte/cuentascorrientes.php", pars);
     
    })
  })

</script>