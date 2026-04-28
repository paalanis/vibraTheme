<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
printf("La conexión con el servidor de base de datos falló comuniquese con su administrador: %s\n", mysqli_connect_error());
exit();
}
$id=$_REQUEST['id'];
$sqlrubro = "SELECT
tb_rubro.id_rubro as id_rubro,
tb_rubro.nombre as nombre
FROM
tb_rubro
ORDER BY
nombre ASC";
$rsrubro = mysqli_query($conexion, $sqlrubro); 
// $sqltalle = "SELECT
// tb_talle.id_talle as id_talle,
// tb_talle.nombre as nombre
// FROM
// tb_talle
// ORDER BY
// nombre ASC";
// $rstalle = mysqli_query($conexion, $sqltalle);
$sqliva = "SELECT
tb_iva_condicion.id_iva_condicion as id_iva,
tb_iva_condicion.nombre as nombre
FROM
tb_iva_condicion
ORDER BY
nombre ASC";
$rsiva = mysqli_query($conexion, $sqliva);
// $sqlclub = "SELECT
// tb_club.id_club as id_club,
// tb_club.nombre as nombre
// FROM
// tb_club
// ORDER BY
// nombre ASC";
// $rsclub = mysqli_query($conexion, $sqlclub);

$sqlproducto = "SELECT
tb_productos.id_productos AS id_producto,
tb_productos.id_iva_condicion AS id_iva,
tb_productos.id_rubro AS id_rubro,
tb_productos.nombre AS nombre,
tb_productos.descripcion AS descripcion,
tb_productos.precio_costo AS costo,
tb_productos.precio_venta AS venta,
tb_productos.codigo AS codigo,
tb_productos.foto AS foto,
tb_iva_condicion.nombre AS iva,
tb_rubro.nombre AS rubro
FROM
tb_productos
LEFT JOIN tb_iva_condicion ON tb_iva_condicion.id_iva_condicion = tb_productos.id_iva_condicion
LEFT JOIN tb_rubro ON tb_rubro.id_rubro = tb_productos.id_rubro
WHERE
tb_productos.id_productos = '$id'";
$rsproducto = mysqli_query($conexion, $sqlproducto);
while ($datos = mysqli_fetch_assoc($rsproducto)){
$id_producto=mb_convert_encoding($datos['id_producto'], 'UTF-8', 'ISO-8859-1');
$id_iva=mb_convert_encoding($datos['id_iva'], 'UTF-8', 'ISO-8859-1');
$id_rubro=mb_convert_encoding($datos['id_rubro'], 'UTF-8', 'ISO-8859-1');
$nombre=utf8_decode($datos['nombre']);
$descripcion=mb_convert_encoding($datos['descripcion'], 'UTF-8', 'ISO-8859-1');
$costo=mb_convert_encoding($datos['costo'], 'UTF-8', 'ISO-8859-1');
$venta=mb_convert_encoding($datos['venta'], 'UTF-8', 'ISO-8859-1');
$codigo=mb_convert_encoding($datos['codigo'], 'UTF-8', 'ISO-8859-1');
$foto=mb_convert_encoding($datos['foto'], 'UTF-8', 'ISO-8859-1');
$iva_1=mb_convert_encoding($datos['iva'], 'UTF-8', 'ISO-8859-1');
$rubro_1=mb_convert_encoding($datos['rubro'], 'UTF-8', 'ISO-8859-1');
}   
?>
<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); modifica('modificaproducto')">
 
<div class="modal-header">
   <h4 class="modal-title">Modificar Producto</h4>
</div>
<br>

 <div class="well bs-component">
 <div class="row">
 <div class="col-lg-5">
   <fieldset>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Nombre</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_nombre" value="<?php echo $nombre;?>" aria-describedby="basic-addon1" required autofocus="">
          <input type="hidden" class="form-control" autocomplete="off" id="dato_id" value="<?php echo $id_producto;?>" aria-describedby="basic-addon1">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Descripción</label>
        <div class="col-lg-9">
          <textarea class="form-control" autocomplete="off" rows="1" id="dato_descripcion"><?php echo $descripcion;?></textarea>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Precio costo con IVA</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_costo" value="<?php echo $costo;?>" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Precio venta con IVA</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_venta" value="<?php echo $venta;?>" aria-describedby="basic-addon1" required autofocus="">
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Foto</label>
        <div class="col-lg-9">
         <!--  <input type="text" class="form-control" autocomplete="off" id="dato_nombre" aria-describedby="basic-addon1" required autofocus=""> -->
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">rubro</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_rubro" required>   
              <option value="<?php echo $id_rubro;?>"><?php echo $rubro_1;?></option>
              <?php
              while ($sql_rubro = mysqli_fetch_assoc($rsrubro)){
                $idrubro= $sql_rubro['id_rubro'];
                $rubro = $sql_rubro['nombre'];

                echo mb_convert_encoding('<option value='.$idrubro.'>'.$rubro.'</option>', 'UTF-8', 'ISO-8859-1');
                
              }
              ?>
            </select>
        </div>
      </div>
            <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">IVA</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_iva" required>   
              <option value="<?php echo $id_iva;?>"><?php echo $iva_1;?></option>
              <?php
              while ($sql_iva = mysqli_fetch_assoc($rsiva)){
                $idiva= $sql_iva['id_iva'];
                $iva = $sql_iva['nombre'];

                echo mb_convert_encoding('<option value='.$idiva.'>'.$iva.'</option>', 'UTF-8', 'ISO-8859-1');
                
              }
              ?>
            </select>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label for="inputPassword" class="col-lg-3 control-label">Codigo</label>
        <div class="col-lg-9">
          <input type="text" class="form-control" autocomplete="off" id="dato_codigo" value="<?php echo $codigo;?>" aria-describedby="basic-addon1" required>
        </div>
      </div>
      <div align="right">
      <h3><span class="label label-default">Uso exclusivo para productos que pasen por balanza</span></h3>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Pesable en balanza</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_pesable">   
             <option value="1"></option>
             <option value="1">SI</option>
             <option value="0">NO</option>
            </select>
        </div>
      </div>
      <!-- <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Club</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_club" required>   
              <option value="<?php echo $id_club;?>"><?php echo $club_1;?></option>
              <?php
              while ($sql_club = mysqli_fetch_assoc($rsclub)){
                $idclub= $sql_club['id_club'];
                $club = $sql_club['nombre'];

                echo mb_convert_encoding('<option value='.$idclub.'>'.$club.'</option>', 'UTF-8', 'ISO-8859-1');
                
              }
              ?>
            </select>
        </div>
      </div>
      <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Color</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_color">   
              <option value=""></option>
              <option value="Amarillo">Amarillo</option>
              <option value="Azul">Azul</option>
              <option value="Blanco">Blanco</option>
              <option value="Celeste">Celeste</option>
              <option value="Negro">Negro</option>
              <option value="Rojo">Rojo</option>
              <option value="Otros">Otros</option>
            </select>
        </div>
      </div> -->

      <!-- <div class="form-group form-group-sm">
        <label  class="col-lg-3 control-label">Talle</label>
        <div class="col-lg-9">
          <select class="form-control" id="dato_talle" required>   
              <option value="<?php echo $id_talle;?>"><?php echo $talle_1;?></option>
              <?php
              while ($sql_talle = mysqli_fetch_assoc($rstalle)){
                $idtalle= $sql_talle['id_talle'];
                $talle = $sql_talle['nombre'];

                echo mb_convert_encoding('<option value='.$idtalle.'>'.$talle.'</option>', 'UTF-8', 'ISO-8859-1');
                
              }
              ?>
            </select>
        </div>
      </div> -->
   </fieldset>
 
 </div>
 <div class="col-lg-7">
 

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

<script type="text/javascript">

 $(document).ready(function () {
  // $('#dato_venta').mask("##.00", {reverse: true});
  // $('#dato_costo').mask("##.00", {reverse: true});
  });

</script>