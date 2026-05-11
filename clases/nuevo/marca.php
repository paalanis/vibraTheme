<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../../index.php"); exit(); }
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) { printf("Error de conexión: %s\n", mysqli_connect_error()); exit(); }
$stmt = mysqli_prepare($conexion, "SELECT id_marca, nombre FROM tb_marca ORDER BY nombre ASC");
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $g_id, $g_nombre);
$rows = [];
while (mysqli_stmt_fetch($stmt)) {
    $rows[] = ['id' => $g_id, 'nombre' => htmlspecialchars($g_nombre, ENT_QUOTES, 'UTF-8')];
}
mysqli_stmt_close($stmt);
?>
<form class="form-horizontal" role="form" id="formulario_nuevo"
      onsubmit="event.preventDefault(); nuevo('marca')">
<div class="modal-header"><h4 class="modal-title">Marcas</h4></div><br>
<div class="well bs-component">
 <div class="row">
  <div class="col-lg-6">
   <fieldset>
    <div class="form-group form-group-sm">
      <label class="col-lg-3 control-label">Nombre</label>
      <div class="col-lg-9">
        <input type="text" class="form-control" autocomplete="off"
               id="dato_nombre" required autofocus>
      </div>
    </div>
   </fieldset>
  </div>
  <div class="col-lg-6">
   <fieldset>
    <div class="panel panel-default">
     <div class="panel-body" style="height:225px; overflow-y:auto">
      <table class="table table-striped table-hover">
       <thead><tr class="active"><th>Nombre</th><th>Editar</th></tr></thead>
       <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo $r['nombre']; ?></td>
          <td>
            <button class="ver_modal btn btn-xs btn-default" type="button"
                    value="<?php echo $r['id']; ?>">
              <span class="glyphicon glyphicon-pencil"></span>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr><td colspan="2">No hay marcas cargados.</td></tr><?php endif; ?>
       </tbody>
      </table>
     </div>
    </div>
   </fieldset>
  </div>
 </div>
</div>
<div class="modal-footer">
  <div class="form-group form-group-sm">
    <div class="col-lg-7"><div align="center" id="div_mensaje_general"></div></div>
    <div class="col-lg-5">
      <div align="right">
        <button type="button" onclick="inicio()" class="btn btn-default">Salir</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>
</form>
<script>
$(function() {
  $('.ver_modal').click(function() {
    var id = $(this).val();
    $("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
    $('#panel_inicio').load("clases/modifica/upd-marca.php", {id: id});
  });
});
</script>
