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

$desde  = $_REQUEST['dato_desde']  ?? $hoy;
$hasta  = $_REQUEST['dato_hasta']  ?? $hoy;
$origen = $_REQUEST['dato_origen'] ?? 'dia'; // 'dia' | 'acumulado'

// Sanitizar fechas
$desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : $hoy;
$hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : $hoy;

if ($origen === 'dia') {
    // Ventas de la caja actual (abierta o cerrada en el período)
    $sqlventas = "SELECT
        COALESCE(tc.id_cierre, tv.id_cierre) AS cierre,
        ROUND(SUM(tv.subtotal), 2) AS total
        FROM tb_ventas tv
        LEFT JOIN tb_cierres tc ON tc.id_cierre = tv.id_cierre
        WHERE tv.fecha BETWEEN '$desde 00:00:00' AND '$hasta 23:59:59'
        AND tv.estado = '1'
        GROUP BY tv.id_cierre
        ORDER BY tv.id_cierre DESC";
    $titulo_origen = 'Ventas del día';
} else {
    // Ventas acumuladas (cajas cerradas)
    $sqlventas = "SELECT
        id_cierre AS cierre,
        ROUND(SUM(subtotal), 2) AS total
        FROM tb_ventas_acumulado
        WHERE fecha BETWEEN '$desde 00:00:00' AND '$hasta 23:59:59'
        GROUP BY id_cierre
        ORDER BY id_cierre DESC";
    $titulo_origen = 'Ventas acumuladas';
}

$rsventas = mysqli_query($conexion, $sqlventas);
$cantidad = mysqli_num_rows($rsventas);

$datos = [];
if ($cantidad > 0) {
    while ($rows = mysqli_fetch_assoc($rsventas)) {
        $datos[] = $rows;
    }
}

if (isset($_POST["export_data"])) {
    if (!empty($datos)) {
        $filename = "reporte_ventas.xls";
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=" . $filename);
        $mostrar_columnas = false;
        foreach ($datos as $dato) {
            if (!$mostrar_columnas) {
                echo implode("\t", array_keys($dato)) . "\n";
                $mostrar_columnas = true;
            }
            echo implode("\t", array_values($dato)) . "\n";
        }
    } else {
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
      <th>Total — <?php echo htmlspecialchars($titulo_origen); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php
    $total_vta = 0;
    foreach ($datos as $dato) { ?>
    <tr>
      <td><?php echo (int)$dato['cierre']; ?></td>
      <td><?php echo $dato['total']; ?></td>
    </tr>
    <?php $total_vta += $dato['total']; } ?>
  </tbody>
</table>

<?php if ($cantidad == 0): ?>
  <p>No hay registros</p>
<?php endif; ?>

</div>
</div>

<?php if ($cantidad > 0): ?>
  <h3>Total de ventas del período: $<?php echo round($total_vta, 2); ?></h3>
<?php endif; ?>

<form class="form-horizontal" id="Exportar_excel" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
<div class="modal-footer">
  <div class="row">
    <div class="form-group form-group-sm">
      <div class="col-lg-12">
        <div align="right">
          <button type="submit" class="btn btn-info" id="botonExcel1"
                  name="export_data" value="Export to excel">
            <span class="glyphicon glyphicon-save"></span> Descargar
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
</form>

<script type="text/javascript">
$(function() {
  $('.form-control').change(function() {
    var _btn = document.getElementById("botonExcel1");
    if (_btn) _btn.style.visibility = "hidden";
  });
});
</script>
