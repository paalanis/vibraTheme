<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
if (!isset($_SESSION['cierre'])) {
header("Location: abrecaja.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
printf("La conexión con el servidor de base de datos falló comuniquese con su administrador: %s\n", mysqli_connect_error());
exit();
}
date_default_timezone_set("America/Argentina/Mendoza");
$fecha = date("d-m-Y H:i"); 
$cierre_actual = $_SESSION['cierre'];

$sqlcierre = "SELECT
DATE_FORMAT(tb_cierres.fecha_apertura, '%d-%m-%Y %T') as apertura,
tb_condicion_venta.nombre AS tipo,
ROUND(Sum(tb_ventas.subtotal),2) AS monto
FROM
tb_ventas
INNER JOIN tb_condicion_venta ON tb_condicion_venta.id_condicion_venta = tb_ventas.id_condicion_venta
INNER JOIN tb_cierres ON tb_ventas.id_cierre = tb_cierres.id_cierre
WHERE
tb_ventas.id_cierre = '$cierre_actual'
GROUP BY
tb_cierres.fecha_apertura,
tb_ventas.id_cierre,
tb_condicion_venta.nombre";
$rscierre = mysqli_query($conexion, $sqlcierre);
$filas = mysqli_num_rows($rscierre);

$sqlretiros = "SELECT
IF(tb_retiros.tipo = '0', 'Retiros de efectivo', 'Efectivo Inicial') AS tipo2,
round(SUM(IF(tb_retiros.tipo = '0', tb_retiros.monto*-1, tb_retiros.monto)),2) AS monto,
IF(tb_retiros.tipo = '0', 'danger', 'success') AS color
FROM
tb_retiros
WHERE
tb_retiros.id_cierres = '$cierre_actual'
GROUP BY
tb_retiros.tipo
ORDER BY
tb_retiros.tipo DESC
";
$rsretiros = mysqli_query($conexion, $sqlretiros);
$filas2 = mysqli_num_rows($rsretiros);


?>
<input type="hidden" class="form-control" id="id_usuario" value="<?php echo $_SESSION['id_usuario'];?>" aria-describedby="basic-addon1">
<input type="hidden" class="form-control" id="id_cierre" value="<?php echo $_SESSION['cierre'];?>" aria-describedby="basic-addon1">
<div class="modal" id="modal_abrecaja" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <!-- <h5 class="modal-title"><?php echo $fecha;?> CIERRE DE CAJA <p></p></h5> -->
        <h4><?php echo $fecha;?> - CIERRE CAJA N° <span class="label label-default"><?php echo $cierre_actual;?></span></h4>
<!--         <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button> -->
      </div>

      <div class="modal-body" id="div_cargando">
     
        <ul class="list-group">

          <?php
          $retiro = 0;
          if ($filas2 > 0) {

              while ($sql_retiros = mysqli_fetch_assoc($rsretiros)){
              
              echo '<li class="list-group-item list-group-item-'.$sql_retiros['color'].'"><strong>'.$sql_retiros['tipo2'].'</strong>
              <span class="badge badge-primary badge-pill">$ '.$sql_retiros['monto'].'</span></li>';

              $retiro = $retiro + $sql_retiros['monto'];

              }
            }else{echo '';}

          $total = 0;
          if ($filas > 0) {

              while ($sql_cierre = mysqli_fetch_assoc($rscierre)){
               $apertura=$sql_cierre['apertura'];

              echo '
              <li class="list-group-item list-group-item-info"><strong>'.$sql_cierre['tipo'].'</strong>
              <span class="badge badge-primary badge-pill">$ '.$sql_cierre['monto'].'</span></li>';

              $total = $total + $sql_cierre['monto'];

              }
            }else{echo '';}

            $totalcaja = $retiro + $total;
            $totalcaja = round($totalcaja, 2);
         ?>
              
          <li class="list-group-item list-group-item-info"><strong>SubTotal ventas:</strong>
              <span class="badge badge-primary badge-pill">$ <?php echo $total;?></span></li>
          <li class="list-group-item list-group-item-default"><strong>Total cierre de caja:</strong>
              <span class="badge badge-primary badge-pill">$ <?php echo $totalcaja;?></span></li>
        </ul>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" onclick="abrir('cierracaja')">Cerrar CAJA</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Salir</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  
  $(document).ready(function () {
    
    $('#modal_abrecaja').modal('show')
    $('#modal_abrecaja').modal({
      keyboard: false
    })
    
    });
</script>