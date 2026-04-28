<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';

$id = (int)($_REQUEST['id'] ?? 0);
$stmt = mysqli_prepare($conexion,
    "SELECT id_proveedores, nombre, cuit, direccion, provincia, localidad, telefono, mail
     FROM tb_proveedores WHERE id_proveedores = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$id_proveedor=$nombre=$cuit=$direccion=$provincia=$localidad=$telefono=$mail=null;
mysqli_stmt_bind_result($stmt, $id_proveedor, $nombre, $cuit, $direccion, $provincia, $localidad, $telefono, $mail);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$id_proveedor = $id_proveedor;
$nombre    = htmlspecialchars($nombre    ?? '', ENT_QUOTES, 'UTF-8');
$cuit      = htmlspecialchars($cuit      ?? '', ENT_QUOTES, 'UTF-8');
$direccion = htmlspecialchars($direccion ?? '', ENT_QUOTES, 'UTF-8');
$provincia = htmlspecialchars($provincia ?? '', ENT_QUOTES, 'UTF-8');
$localidad = htmlspecialchars($localidad ?? '', ENT_QUOTES, 'UTF-8');
$telefono  = htmlspecialchars($telefono  ?? '', ENT_QUOTES, 'UTF-8');
$mail      = htmlspecialchars($mail      ?? '', ENT_QUOTES, 'UTF-8');
?>
<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); modifica('proveedor')">

<div class="modal-header">
   <h4 class="modal-title">Modificar proveedor</h4>
</div>
<br>

 <div class="well bs-component">
 <div class="row">
 <div class="col-lg-6">
   <fieldset>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Nombre</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_nombre" value="<?php echo $nombre;?>" aria-describedby="basic-addon1" required>
          <input type="hidden" class="form-control" value="<?php echo $id_proveedor;?>" id="dato_id" aria-describedby="basic-addon1">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">CUIT</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_cuit" value="<?php echo $cuit;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Dirección</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_direccion" value="<?php echo $direccion;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-2 control-label">Provincia</label>
        <div class="col-lg-10">
          <select class="form-control" id="dato_provincia" required>
              <option value="<?php echo $provincia;?>"><?php echo $provincia;?></option>   
              <option value="Buenos Aires">Buenos Aires</option>
              <option value="Cordoba">Cordoba</option>
              <option value="Mendoza">Mendoza</option>
              <option value="San Luis">San Luis</option>
              <option value="San Juan">San Juan</option>
              <option value="Santa Fe">Santa Fe</option>
          </select>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Localidad</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_localidad" value="<?php echo $localidad;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Teléfono</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_telefono" value="<?php echo $telefono;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Mail</label>
        <div class="col-lg-10">
          <input type="mail" class="form-control" autocomplete="off" id="dato_mail" value="<?php echo $mail;?>" aria-describedby="basic-addon1" required>
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
