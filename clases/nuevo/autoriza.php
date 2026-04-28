<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
if (!isset($_SESSION['cierre'])) {
header("Location: abrecaja.php");
}
date_default_timezone_set("America/Argentina/Mendoza");
$fecha = date("d-m-Y H:i");  
$id = $_REQUEST['id_producto'];
$cierre2 = $_REQUEST['cierre'];
$factura2 = $_REQUEST['factura'];
?>

<div class="modal" id="modal_abrecaja" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4><?php echo $fecha;?> - REQUIERE AUTORIZACIÓN - <span class="glyphicon glyphicon-piggy-bank"></span></h4>
      </div>
      <div class="modal-body" id="div_cargando">
        <div class="input-group">
          <span class="input-group-addon"><span class="glyphicon glyphicon-barcode"></span></span>
          <input type="hidden" class="form-control" id="articulo" value="<?php echo $id;?>" aria-describedby="basic-addon1">
          <input type="hidden" class="form-control" id="cierre_" value="<?php echo $cierre2;?>" aria-describedby="basic-addon1">
          <input type="hidden" class="form-control" id="factura_" value="<?php echo $factura2;?>" aria-describedby="basic-addon1">
          <input type="password" class="form-control is-valid" id="codigo" placeholder="Ingrese código de autorización">
        </div>
      </div>
      <div class="modal-footer">
        <div id='div_mensaje'></div>
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

    $('#modal_abrecaja').on('hidden.bs.modal', function (e) {
    $('#panel_inicio').load("clases/nuevo/factura.php");
    })

     $(function() {
        $('#codigo').change(function() {  

          var factura = $('#factura_').val();
          var cierre = $('#cierre_').val();
          var autoriza = <?php echo $_SESSION['autoriza'];?>;
          var id_producto = $('#articulo').val();
          var pars = "id=" + id_producto + "&";

          if ($(this).val() == autoriza) {

              $('#div_remitos').html('<div class="text-center"><div class="loadingsm"></div></div>');
              $.ajax({
                  url : "clases/elimina/factura.php",
                  data : pars,
                  dataType : "json",
                  type : "get",

                  success: function(data){
                      
                    if (data.success == 'true') {
                      $('#div_mensaje').html('<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Se eliminó producto!</div>');
                      setTimeout("$('#mensaje_general').alert('close')", 1000);
                      setTimeout("$('#modal_abrecaja').modal('hide')", 2000);
                      //$('#modal_abrecaja').modal('hide')
                      //$('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: factura, cierre: cierre});
                      $("#dato_codigo").focus()


                    } else {
                      $('#div_mensaje').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');        
                      setTimeout("$('#mensaje_general').alert('close')", 2000);
                      $("#dato_codigo").focus()
                    }
                  
                  }

              });
           
            }else{

              alert('Código erroneo')
            }


        })
      })

</script>