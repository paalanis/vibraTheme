<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';

$id_rubro = (int)($_REQUEST['id'] ?? 0);
$stmt = mysqli_prepare($conexion,
    "SELECT nombre, id_rubro FROM tb_rubro WHERE id_rubro = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id_rubro);
mysqli_stmt_execute($stmt);
$nombre_val = $id_rubro_val = null;
mysqli_stmt_bind_result($stmt, $nombre_val, $id_rubro_val);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$nombre   = htmlspecialchars($nombre_val ?? '', ENT_QUOTES, 'UTF-8');
$id_rubro = $id_rubro_val;
?>
<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); modifica('rubro')">

<div class="modal-header">
   <h4 class="modal-title">Modificar Rubro</h4>
</div>
<br>

 <div class="well bs-component">
 <div class="row">
 <div class="col-lg-6">
   <fieldset>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Nombre</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_nombre" value="<?php echo $nombre;?>" aria-describedby="basic-addon1" required autofocus="">
          <input type="hidden" class="form-control" autocomplete="off" id="dato_id" value="<?php echo $id_rubro;?>" aria-describedby="basic-addon1">
        </div>
      </div>
  
   </fieldset>
 </div>
 <div class="col-lg-6">
   <fieldset>

    </fieldset>
  </div> 
 </div>  
 </div>

<div class="modal-footer">
        <div class="form-group form-group-sm">
        <div class="col-lg-7">
          <div align="center" id="div_mensaje_general">
          </div>
        </div>
        <div class="col-lg-5">
          <div align="right">
          <button type="button" id="boton_salir" onclick="inicio()" class="btn btn-default">Salir</button>
          <button type="submit" id="boton_guardar" class="btn btn-primary">Guardar</button>  
          </div>
        </div>
      </div>  
  </div>

</form>
