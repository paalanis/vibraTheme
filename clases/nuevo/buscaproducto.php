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

?>
<form class="form-horizontal" id="formulario_nuevo" role="form">

<div class="modal-header">
<h4 class="modal-title">Buscar - Modificar Producto</h4>
</div>
<br>
 
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

<div class="well bs-component" style="background-color:#cad0d273">
 <div class="row">
  
   <fieldset id="div_remitos">
   </fieldset>

 </div>
</div>


</div> 

 </form>

<script type="text/javascript">

  $(document).ready(function () {
    
      $("#dato_codigo").focus()
 
    
    });

  $(function() {
        $('#dato_codigo').change(function() {  
        
          if ($(this).val() != ''){
             
              var codigo = $(this).val()
                  
              //alert(codigo);

              $("#div_remitos").html('<div class="text-center"><div class="loadingsm"></div></div>');
              $('#div_remitos').load('clases/nuevo/modificaproducto.php', {codigo: codigo, tipo: 'porcodigo'});
                  
            }

        })
      })

  $(function() {
        $('#nombre').change(function() {  


           if ($(this).val() != '') {

              var nombre = $(this).val()    

              $("#div_remitos").html('<div class="text-center"><div class="loadingsm"></div></div>');
              $('#div_remitos').load('clases/nuevo/modificaproducto.php', {nombre: nombre, tipo: 'pornombre'});
            

           }
        })
      })

 

 </script>