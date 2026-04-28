<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
if (isset($_SESSION['cierre'])) {
header("Location: factura.php");
}
date_default_timezone_set("America/Argentina/Mendoza");
$fecha = date("d-m-Y H:i");  
?>
<input type="hidden" class="form-control" id="id_usuario" value="<?php echo $_SESSION['id_usuario'];?>" aria-describedby="basic-addon1">
<input type="hidden" class="form-control" id="id_cierre" value="0" aria-describedby="basic-addon1">
<div class="modal" id="modal_abrecaja" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4><?php echo $fecha;?> - ABRIR CAJA - <span class="glyphicon glyphicon-shopping-cart"></span></h4>
      </div>
      <div class="modal-body" id="div_cargando">
        <div class="input-group">
          <span class="input-group-addon"><span class="glyphicon glyphicon-usd"></span></span>
          <input type="number" class="form-control is-valid" id="efectivo" min="0"  placeholder="Efectivo Inicial" aria-label="Amount (to the nearest dollar)">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" onclick="abrir('abrecaja')">Abrir Caja</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Salir</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  
  $(document).ready(function () {
    
    $('#modal_abrecaja').modal('show')
    $('#modal_abrecaja').modal({
      keyboard: false
    })
    
    });
</script>