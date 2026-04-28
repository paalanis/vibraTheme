<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../../index.php'); exit(); }
require_once '../../conexion/conexion.php';

$id = (int)($_REQUEST['id'] ?? 0);
$stmt = mysqli_prepare($conexion,
    "SELECT id_clientes, nombre, apellido, dni, telefono,
            mail, calle, numero, localidad, provincia, codigo_postal
     FROM tb_clientes WHERE id_clientes = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$id_cliente=$nombre=$apellido=$dni=$tel=$mail=$calle=$numero=$loc=$prov=$cpostal=null;
mysqli_stmt_bind_result($stmt, $id_cliente, $nombre, $apellido, $dni, $tel,
    $mail, $calle, $numero, $loc, $prov, $cpostal);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$id_cliente = $id_cliente;
$nombre  = htmlspecialchars($nombre   ?? '', ENT_QUOTES, 'UTF-8');
$apellido= htmlspecialchars($apellido ?? '', ENT_QUOTES, 'UTF-8');
$dni     = htmlspecialchars($dni      ?? '', ENT_QUOTES, 'UTF-8');
$tel     = htmlspecialchars($tel      ?? '', ENT_QUOTES, 'UTF-8');
$mail    = htmlspecialchars($mail     ?? '', ENT_QUOTES, 'UTF-8');
$calle   = htmlspecialchars($calle    ?? '', ENT_QUOTES, 'UTF-8');
$numero  = htmlspecialchars($numero   ?? '', ENT_QUOTES, 'UTF-8');
$loc     = htmlspecialchars($loc      ?? '', ENT_QUOTES, 'UTF-8');
$prov    = htmlspecialchars($prov     ?? '', ENT_QUOTES, 'UTF-8');
$cpostal = htmlspecialchars($cpostal  ?? '', ENT_QUOTES, 'UTF-8');
?>
<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); modifica('cliente')">

<div class="modal-header">
   <h4 class="modal-title">Modificar Cliente</h4>
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
          <input type="hidden" class="form-control" value="<?php echo $id_cliente;?>" id="dato_id" aria-describedby="basic-addon1">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Apellido</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_apellido" value="<?php echo $apellido;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">DNI</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_dni" value="<?php echo $dni;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Mail</label>
        <div class="col-lg-10">
          <input type="mail" class="form-control" autocomplete="off" id="dato_mail" value="<?php echo $mail;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Teléfono</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_telefono" value="<?php echo $tel;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Calle</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_calle" value="<?php echo $calle;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Número</label>
        <div class="col-lg-10">
          <input type="number" class="form-control" autocomplete="off" id="dato_numero" value="<?php echo $numero;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-2 control-label">Localidad</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_localidad" value="<?php echo $loc;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-2 control-label">Provincia</label>
        <div class="col-lg-10">
          <select class="form-control" id="dato_provincia" required>
              <option value="<?php echo $prov;?>"><?php echo $prov;?></option>   
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
        <label for="inputPassword" class="col-lg-2 control-label">C.Postal</label>
        <div class="col-lg-10">
          <input type="text" class="form-control" autocomplete="off" id="dato_codigopostal" value="<?php echo $cpostal;?>" aria-describedby="basic-addon1" required>
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
