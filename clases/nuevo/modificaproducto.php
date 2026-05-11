<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

$tipo_busq = $_REQUEST['tipo'] ?? 'pornombre';
$buscar    = '%' . ($_REQUEST['nombre'] ?? '') . '%';
$codigo    = $_REQUEST['codigo'] ?? '';

if ($tipo_busq === 'porcodigo') {
    $stmt = mysqli_prepare($conexion,
        "SELECT p.id_productos, p.nombre, p.codigo,
                p.precio_costo, p.margen_ganancia,
                g.nombre AS genero, m.nombre AS marca
         FROM tb_productos p
         LEFT JOIN tb_genero g ON g.id_genero = p.id_genero
         LEFT JOIN tb_marca  m ON m.id_marca  = p.id_marca
         WHERE p.codigo = ?
         ORDER BY p.nombre ASC"
    );
    mysqli_stmt_bind_param($stmt, 's', $codigo);
} else {
    $stmt = mysqli_prepare($conexion,
        "SELECT p.id_productos, p.nombre, p.codigo,
                p.precio_costo, p.margen_ganancia,
                g.nombre AS genero, m.nombre AS marca
         FROM tb_productos p
         LEFT JOIN tb_genero g ON g.id_genero = p.id_genero
         LEFT JOIN tb_marca  m ON m.id_marca  = p.id_marca
         WHERE CONCAT(p.nombre, p.codigo) LIKE ?
         ORDER BY p.nombre ASC"
    );
    mysqli_stmt_bind_param($stmt, 's', $buscar);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_nombre, $r_codigo, $r_costo, $r_margen, $r_genero, $r_marca);

$rows = [];
while (mysqli_stmt_fetch($stmt)) {
    $pv = ($r_costo !== null && $r_margen !== null)
            ? number_format($r_costo * (1 + $r_margen / 100), 2, '.', '')
            : '-';
    $rows[] = [
        'id'     => $r_id,
        'nombre' => htmlspecialchars(mb_convert_encoding($r_nombre ?? '', 'UTF-8', 'ISO-8859-1'), ENT_QUOTES, 'UTF-8'),
        'codigo' => htmlspecialchars($r_codigo ?? '', ENT_QUOTES, 'UTF-8'),
        'costo'  => $r_costo !== null ? number_format((float)$r_costo, 2, '.', '') : '-',
        'margen' => $r_margen !== null ? number_format((float)$r_margen, 2, '.', '') . '%' : '-',
        'pventa' => $pv !== '-' ? '$ ' . $pv : '-',
        'genero' => htmlspecialchars($r_genero ?? '', ENT_QUOTES, 'UTF-8'),
        'marca'  => htmlspecialchars($r_marca  ?? '', ENT_QUOTES, 'UTF-8'),
    ];
}
mysqli_stmt_close($stmt);
?>

<div class="row">
 <div class="col-lg-12">
  <fieldset>
   <div class="panel panel-default">
    <div class="panel-body" id="Panel1" style="height:420px; overflow-y:auto">
     <table class="table table-striped table-hover table-condensed">
      <thead>
        <tr class="active">
          <th>Nombre</th>
          <th>Marca / Género</th>
          <th>Código</th>
          <th>Costo</th>
          <th>Margen</th>
          <th>Precio venta</th>
          <th>Editar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo $r['nombre']; ?></td>
          <td><?php echo $r['marca']; ?> / <?php echo $r['genero']; ?></td>
          <td><?php echo $r['codigo']; ?></td>
          <td><?php echo $r['costo'] !== '-' ? '$ ' . $r['costo'] : '-'; ?></td>
          <td><?php echo $r['margen']; ?></td>
          <td><?php echo $r['pventa']; ?></td>
          <td>
            <button class="ver_modal btn btn-xs btn-info" type="button"
                    value="<?php echo $r['id']; ?>">
              <span class="glyphicon glyphicon-pencil"></span>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7">No se encontraron productos.</td></tr>
        <?php endif; ?>
      </tbody>
     </table>
    </div>
   </div>
  </fieldset>
 </div>
</div>

<script>
$(function() {
  $('.ver_modal').click(function() {
    var id = $(this).val();
    $("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
    $('#panel_inicio').load("clases/modifica/upd-producto.php", {id: id});
  });
});
</script>
