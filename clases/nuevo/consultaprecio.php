<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header("Location: ../../index.php");
}
if (!isset($_SESSION['cierre'])) {
header("Location: abrecaja.php");
}
?>

<div class="modal fade bs-example-modal-lg" id="modal_abrecaja">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4>CONSULTA DE PRECIOS - <span class="glyphicon glyphicon-question-sign"></span></h4>
      </div>
      <div class="modal-body" id="div_cargando">
        <div class="well bs-component">
           <div class="row">
          <!--  <div class="col-lg-1"></div>  -->
           <div class="col-lg-12">
             <fieldset>

                <div class="form-group form-group-md">
                  <div class="col-lg-12">

                    <div class="col-lg-6">
                      <input type="text" class="form-control" id="dato_codigo" autocomplete='off' value="" placeholder='Código' aria-describedby="basic-addon1">
                    </div>
                    <div class="col-lg-6">
                      <input type="text" class="form-control" id="nombre" value="" autocomplete='off' placeholder='Buscar por nombre o código' aria-describedby="basic-addon1">
                    </div>
                  </div>  
                </div>    
           
             </fieldset>
           
           </div>
           
           <!-- <div class="col-lg-2"></div> -->
          </div>

          <div class="row">
            <!-- <div class="col-lg-1"></div>  -->
            <div class="col-lg-10">   
              
              <div class="form-group form-group-md">
                <!-- <label class="col-lg-3 control-label">Producto</label> -->
                <div class="col-lg-9" id="div_producto">
                  
                <!-- se carga el pruducto buscado -->

                </div>
              </div>

            </div>

          </div>

          <div class="row">
            
             <fieldset id="div_remitos">
             </fieldset>

           </div>
        

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
     
    $("#dato_codigo").focus()

    });

    $('#modal_abrecaja').on('hidden.bs.modal', function (e) {
    $('#panel_inicio').load("clases/nuevo/factura.php");
    })

    $(function() {
        $('#dato_codigo').change(function() {  
        
          if ($(this).val() != ''){
             
              var codigo = $(this).val()
                  
              //alert(codigo);

              $("#div_remitos").html('<div class="text-center"><div class="loadingsm"></div></div>');
              $('#div_remitos').load('clases/nuevo/buscaprecio.php', {codigo: codigo, tipo: 'porcodigo'});
                  
            }

        })
      })

  $(function() {
        $('#nombre').change(function() {  


           if ($(this).val() != '') {

              var nombre = $(this).val()    

              $("#div_remitos").html('<div class="text-center"><div class="loadingsm"></div></div>');
              $('#div_remitos').load('clases/nuevo/buscaprecio.php', {nombre: nombre, tipo: 'pornombre'});
            

           }
        })
      })


</script>