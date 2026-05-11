<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

// Productos
$stmt_p = mysqli_prepare($conexion,
    "SELECT id_productos, nombre FROM tb_productos ORDER BY nombre ASC"
);
mysqli_stmt_execute($stmt_p);
mysqli_stmt_bind_result($stmt_p, $p_id, $p_nombre);
$productos = [];
while (mysqli_stmt_fetch($stmt_p)) {
    $productos[] = ['id' => $p_id, 'nombre' => mb_convert_encoding($p_nombre, 'UTF-8', 'ISO-8859-1')];
}
mysqli_stmt_close($stmt_p);

// Géneros (antes rubros)
$stmt_g = mysqli_prepare($conexion,
    "SELECT id_genero, nombre FROM tb_genero ORDER BY nombre ASC"
);
mysqli_stmt_execute($stmt_g);
mysqli_stmt_bind_result($stmt_g, $g_id, $g_nombre);
$generos = [];
while (mysqli_stmt_fetch($stmt_g)) {
    $generos[] = ['id' => $g_id, 'nombre' => mb_convert_encoding($g_nombre, 'UTF-8', 'ISO-8859-1')];
}
mysqli_stmt_close($stmt_g);
?>

<form class="form-horizontal" id="formulario_reporte" role="form">

<div class="modal-header">
  <h4 class="modal-title">Reporte de Stock</h4>
</div>
<br>

<div class="well bs-component">
  <div class="row">
    <div class="col-lg-10">
      <fieldset>

        <div class="form-group form-group-sm">
          <label class="col-lg-2 control-label">Producto</label>
          <div class="col-lg-4">
            <select class="form-control" id="dato_producto">
              <option value="0">Todos</option>
              <?php foreach ($productos as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <label class="col-lg-2 control-label">Género</label>
          <div class="col-lg-4">
            <select class="form-control" id="dato_genero">
              <option value="0">Todos</option>
              <?php foreach ($generos as $g): ?>
                <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group form-group-sm">
          <label class="col-lg-2 control-label">Estado</label>
          <div class="col-lg-4">
            <select class="form-control" id="dato_estado">
              <option value="todos">Todos</option>
              <option value="sin_stock">Sin stock</option>
              <option value="bajo">Stock bajo</option>
              <option value="ok">Stock normal</option>
            </select>
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
        <button type="button" onclick="inicio()" class="btn btn-default">Salir</button>
        <button type="button" id="boton_buscar" class="btn btn-primary"
                onclick="reporte('stock')">Buscar</button>
      </div>
    </div>
  </div>
</div>

<div id="div_reporte"></div>

</form>

<script>
$(document).ready(function() { $('#botonExcel1').hide(); });
</script>
