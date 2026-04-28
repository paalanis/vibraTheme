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

if(isset($_REQUEST['dato_fecha'])){
 $fecha=$_REQUEST['dato_fecha'];
}else{
 $fecha = $hoy;
}

        
$sqlticket = "SELECT 
`fecha` as fecha,
`numero_factura` as ticket,
sum(`subtotal`) as total,
`cupon` as cupon,
`id_ventas` as id 
FROM `tb_ventas` 
WHERE `fecha`between '$fecha 00:00:00' and '$fecha 23:59:59' 
GROUP BY `numero_factura`
ORDER BY
`numero_factura` DESC
";

$rsticket = mysqli_query($conexion, $sqlticket);
$cantidad =  mysqli_num_rows($rsticket);

if ($cantidad > 0) { // si existen ticket con de esa finca se muestran, de lo contrario queda en blanco  
$datos = array();

while ($rows = mysqli_fetch_assoc($rsticket)){
  $datos[] = $rows;
}

if(isset($_POST["export_data"])) {

    if(!empty($datos)) {

      $filename = "reporte_ticket.xls";

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
      <th>Fecha</th>
      <th>Numero ticket</th>
      <th>Total</th>
      <th>N° Cupón</th>
      <th>Imprimir</th>
      </tr>
  </thead>
<tbody> 

  <?php foreach($datos as $dato) { ?>
  <tr>
  <td><?php echo $dato['fecha']; ?></td>
  <td><?php echo $dato['ticket']; ?></td>
  <td><?php echo "$ ".number_format($dato['total'],2); ?></td>
  <td><?php echo $dato['cupon']; ?></td>
  <td><?php echo '<button type="button" class="ver_modal ver_modal-info ver_modal-xs" id="'.$dato['id'].'" value="'.$dato['ticket'].'"><span class="glyphicon glyphicon-print" aria-hidden="true"></span></button>'?></td>
  </tr>
  <?php }?>

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
        $('.ver_modal-info').click(function() {

           var factura = $(this).val()
                          
           var pars = "factura=" + factura + "&";

           //alert(pars)

          // $('#div_mensaje_general').html('<div class="text-center"><div class="loadingsm"></div></div>');
          $.ajax({
              url : "ticket/reticket.php",
              data : pars,
              dataType : "json",
              type : "get",

              // success: function(data){
                  
              //   if (data.success == 'true') {

              //     $('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Se eliminó producto!</div>');
              //     setTimeout("$('#mensaje_general').alert('close')", 2000);
              //     $('#div_mensaje_general').html('');

              //   } else {
              //     $('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');        
              //     setTimeout("$('#mensaje_general').alert('close')", 2000);
              //   }
              
              // }

          });
              
        })
      })
</script>
