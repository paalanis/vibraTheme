<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';
$id = (int)($_REQUEST['id'] ?? 0);
$stmt = mysqli_prepare($conexion, "SELECT id_color, nombre FROM tb_color WHERE id_color = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$id_val = $nombre_val = null;
mysqli_stmt_bind_result($stmt, $id_val, $nombre_val);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$nombre = htmlspecialchars($nombre_val ?? '', ENT_QUOTES, 'UTF-8');
?>
<form class="form-horizontal" role="form" id="formulario_nuevo"
      onsubmit="event.preventDefault(); modifica('color')">
<div class="modal-header"><h4 class="modal-title">Modificar Color</h4></div><br>
<div class="well bs-component">
 <div class="row"><div class="col-lg-6">
  <fieldset>
   <div class="form-group form-group-sm">
     <label class="col-lg-3 control-label">Nombre</label>
     <div class="col-lg-9">
       <input type="text" class="form-control" id="dato_nombre"
              value="<?php echo $nombre; ?>" required autofocus>
       <input type="hidden" id="dato_id" value="<?php echo $id_val; ?>">
     </div>
   </div>
  </fieldset>
 </div></div>
</div>
<div class="modal-footer">
  <div class="form-group form-group-sm">
    <div class="col-lg-7"><div align="center" id="div_mensaje_general"></div></div>
    <div class="col-lg-5"><div align="right">
      <button type="button" onclick="inicio()" class="btn btn-default">Salir</button>
      <button type="submit" class="btn btn-primary">Guardar</button>
    </div></div>
  </div>
</div>
</form>
