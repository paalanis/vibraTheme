<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php"); exit();
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

function fetchFiltro($conexion, $sql) {
    $r = mysqli_query($conexion, $sql);
    $rows = [];
    while ($d = mysqli_fetch_assoc($r)) {
        $rows[] = ['id' => $d['id'], 'nombre' => mb_convert_encoding($d['nombre'], 'UTF-8', 'ISO-8859-1')];
    }
    return $rows;
}

$generos = fetchFiltro($conexion, "SELECT id_genero AS id, nombre FROM tb_genero ORDER BY nombre");
$marcas  = fetchFiltro($conexion, "SELECT id_marca  AS id, nombre FROM tb_marca  ORDER BY nombre");
$tipos   = fetchFiltro($conexion, "SELECT id_tipo   AS id, nombre FROM tb_tipo   ORDER BY nombre");
$talles  = fetchFiltro($conexion, "SELECT id_talle  AS id, nombre FROM tb_talle  ORDER BY nombre");
$colores = fetchFiltro($conexion, "SELECT id_color  AS id, nombre FROM tb_color  ORDER BY nombre");

function selectHtml($id, $rows) {
    $html = "<select class=\"form-control\" id=\"$id\"><option value=\"0\">Todos</option>";
    foreach ($rows as $r) {
        $html .= '<option value="'.(int)$r['id'].'">'.htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8').'</option>';
    }
    return $html . '</select>';
}
?>
<form class="form-horizontal" id="formulario_reporte" role="form">
<div class="modal-header"><h4 class="modal-title">Reporte de Stock</h4></div><br>
<div class="well bs-component">
  <div class="row"><div class="col-lg-12"><fieldset>

    <div class="form-group form-group-sm">
      <label class="col-lg-1 control-label">Género</label>
      <div class="col-lg-3"><?php echo selectHtml('dato_genero', $generos); ?></div>
      <label class="col-lg-1 control-label">Marca</label>
      <div class="col-lg-3"><?php echo selectHtml('dato_marca', $marcas); ?></div>
      <label class="col-lg-1 control-label">Tipo</label>
      <div class="col-lg-3"><?php echo selectHtml('dato_tipo', $tipos); ?></div>
    </div>

    <div class="form-group form-group-sm">
      <label class="col-lg-1 control-label">Talle</label>
      <div class="col-lg-3"><?php echo selectHtml('dato_talle', $talles); ?></div>
      <label class="col-lg-1 control-label">Color</label>
      <div class="col-lg-3"><?php echo selectHtml('dato_color', $colores); ?></div>
      <label class="col-lg-1 control-label">Estado</label>
      <div class="col-lg-3">
        <select class="form-control" id="dato_estado">
          <option value="todos">Todos</option>
          <option value="sin_stock">Sin stock</option>
          <option value="bajo">Stock bajo</option>
          <option value="ok">Stock normal</option>
        </select>
      </div>
    </div>

  </fieldset></div></div>
</div>
<div class="modal-footer">
  <div class="form-group form-group-sm">
    <div class="col-lg-7"><div align="center" id="div_mensaje_general"></div></div>
    <div class="col-lg-5"><div align="right">
      <button type="button" onclick="inicio()" class="btn btn-default">Salir</button>
      <button type="button" id="boton_buscar" class="btn btn-primary" onclick="reporte('stock')">Buscar</button>
    </div></div>
  </div>
</div>
</form>
<div id="div_reporte" style="margin-top:10px"></div>
<script>$(document).ready(function() { $('#botonExcel1').hide(); });</script>
