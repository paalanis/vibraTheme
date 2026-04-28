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
$insumo=$_REQUEST['insumo'];
$sqlsaldo = "SELECT
tb_stock.id_stock AS id,
tb_stock.saldo AS saldo,
tb_unidad.nombre as unidad
FROM
tb_stock
INNER JOIN tb_insumo ON tb_stock.id_insumo = tb_insumo.id_insumo
INNER JOIN tb_unidad ON tb_insumo.id_unidad = tb_unidad.id_unidad
WHERE
tb_stock.id_insumo = '$insumo'
ORDER BY
tb_stock.id_stock DESC
LIMIT 1";
$rssaldo = mysqli_query($conexion, $sqlsaldo); 
$cantidad =  mysqli_num_rows($rssaldo);
if ($cantidad > 0) { 
$datos = mysqli_fetch_assoc($rssaldo);
$saldo=mb_convert_encoding($datos['saldo'], 'UTF-8', 'ISO-8859-1');
$unidad=mb_convert_encoding($datos['unidad'], 'UTF-8', 'ISO-8859-1');
}else{
$saldo = "0";
$unidad ="";
}
?>
<span class="help-block">Saldo actual: <?php echo $saldo." ".$unidad;?></span>