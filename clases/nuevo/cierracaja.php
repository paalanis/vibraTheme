<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
if (!isset($_SESSION['cierre'])) {
    header("Location: abrecaja.php"); exit();
}

include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    echo "Error de conexión: " . mysqli_connect_error();
    exit();
}

date_default_timezone_set("America/Argentina/Mendoza");
$fecha = date("d-m-Y H:i");
$cierre_actual = (int)$_SESSION['cierre'];

// Ventas por condición — LEFT JOIN: si id_condicion_venta no está seteado
// en tb_ventas, la fila aparece como "Sin condición" en vez de desaparecer.
$stmt1 = mysqli_prepare($conexion,
    "SELECT
        DATE_FORMAT(tc.fecha_apertura, '%d-%m-%Y %T') AS apertura,
        COALESCE(tcv.nombre, 'Sin condición') AS tipo,
        ROUND(SUM(tv.subtotal), 2) AS monto
     FROM tb_ventas tv
     LEFT JOIN tb_condicion_venta tcv ON tcv.id_condicion_venta = tv.id_condicion_venta
     INNER JOIN tb_cierres tc ON tv.id_cierre = tc.id_cierre
     WHERE tv.id_cierre = ? AND tv.estado = '1'
     GROUP BY tc.fecha_apertura, tv.id_cierre, tcv.nombre"
);
mysqli_stmt_bind_param($stmt1, 'i', $cierre_actual);
mysqli_stmt_execute($stmt1);
mysqli_stmt_bind_result($stmt1, $r_apertura, $r_tipo, $r_monto);
$rows_ventas = [];
while (mysqli_stmt_fetch($stmt1)) {
    $rows_ventas[] = ['apertura' => $r_apertura, 'tipo' => $r_tipo, 'monto' => $r_monto];
}
mysqli_stmt_close($stmt1);

// Retiros / efectivo inicial
$stmt2 = mysqli_prepare($conexion,
    "SELECT
        IF(tipo = '0', 'Retiros de efectivo', 'Efectivo Inicial') AS tipo2,
        ROUND(SUM(IF(tipo = '0', monto * -1, monto)), 2) AS monto,
        IF(tipo = '0', 'danger', 'success') AS color
     FROM tb_retiros
     WHERE id_cierres = ?
     GROUP BY tipo
     ORDER BY tipo DESC"
);
mysqli_stmt_bind_param($stmt2, 'i', $cierre_actual);
mysqli_stmt_execute($stmt2);
mysqli_stmt_bind_result($stmt2, $r_tipo2, $r_monto2, $r_color);
$rows_retiros = [];
while (mysqli_stmt_fetch($stmt2)) {
    $rows_retiros[] = ['tipo2' => $r_tipo2, 'monto' => $r_monto2, 'color' => $r_color];
}
mysqli_stmt_close($stmt2);
?>

<input type="hidden" class="form-control" id="id_usuario" value="<?php echo (int)$_SESSION['id_usuario']; ?>">
<input type="hidden" class="form-control" id="id_cierre"  value="<?php echo $cierre_actual; ?>">

<div class="modal" id="modal_abrecaja" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4><?php echo htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'); ?> - CIERRE CAJA N° <span class="label label-default"><?php echo $cierre_actual; ?></span></h4>
      </div>

      <div class="modal-body" id="div_cargando">
        <ul class="list-group">

          <?php
          $retiro = 0;
          foreach ($rows_retiros as $row) {
              echo '<li class="list-group-item list-group-item-' . htmlspecialchars($row['color']) . '">'
                 . '<strong>' . htmlspecialchars($row['tipo2']) . '</strong>'
                 . '<span class="badge badge-primary badge-pill">$ ' . $row['monto'] . '</span>'
                 . '</li>';
              $retiro += $row['monto'];
          }

          $total = 0;
          foreach ($rows_ventas as $row) {
              echo '<li class="list-group-item list-group-item-info">'
                 . '<strong>' . htmlspecialchars($row['tipo']) . '</strong>'
                 . '<span class="badge badge-primary badge-pill">$ ' . $row['monto'] . '</span>'
                 . '</li>';
              $total += $row['monto'];
          }

          $totalcaja = round($retiro + $total, 2);
          ?>

          <li class="list-group-item list-group-item-info">
            <strong>SubTotal ventas:</strong>
            <span class="badge badge-primary badge-pill">$ <?php echo $total; ?></span>
          </li>
          <li class="list-group-item list-group-item-default">
            <strong>Total cierre de caja:</strong>
            <span class="badge badge-primary badge-pill">$ <?php echo $totalcaja; ?></span>
          </li>
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
    $('#modal_abrecaja').modal({ show: true, keyboard: false });
  });
</script>
