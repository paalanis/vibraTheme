<?php
session_start();
require_once 'conexion/csrf.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php'); exit();
}

include 'conexion/conexion.php';

// ── Query compatible sin mysqlnd (bind_result en lugar de get_result) ─────────
$id_usuario = (int)$_SESSION['id_usuario'];
$stmt_cierre = mysqli_prepare($conexion,
    "SELECT id_cierre, DATE_FORMAT(fecha_apertura, '%d-%m-%Y %T') AS apertura
     FROM tb_cierres
     WHERE id_usuario = ? AND estado = '0' LIMIT 1"
);
mysqli_stmt_bind_param($stmt_cierre, 'i', $id_usuario);
mysqli_stmt_execute($stmt_cierre);
$id_cierre_val = $apertura_val = null;
mysqli_stmt_bind_result($stmt_cierre, $id_cierre_val, $apertura_val);
$found_cierre = mysqli_stmt_fetch($stmt_cierre);
mysqli_stmt_close($stmt_cierre);

if ($found_cierre && $id_cierre_val) {
    $_SESSION['cierre'] = $id_cierre_val;
    $cierre   = $id_cierre_val;
    $apertura = $apertura_val;
} else {
    $apertura = '';
    $cierre   = 'CERRADA';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="mobile-web-app-capable" content="yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">

    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="Pablo Alanis" content="">
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon">


    <title>Vibra</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-toggle.min.css" rel="stylesheet">
    <link href="css/bootswatch.scss" type="text/css" rel="stylesheet">
    <link href="css/bootswatch.less" type="text/css" rel="stylesheet">
    <link href="css/cargando.css" rel="stylesheet">
    <link href="css/formato.css" rel="stylesheet">
    <link href="css/tablas.css" rel="stylesheet">
    <link href="css/green.css" rel="stylesheet">

  </head>

  <body>


    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#"><img style="height:30px; width:auto; vertical-align:middle;"
             src="images/logo2.png"></a>
         <!--  <a class="navbar-brand" href="#">Desarrollos</a> -->

        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="">Inicio</a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-e xpanded="false">Ventas <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#" class="menu" title="nuevo_factura" onmouseup="cerrar()">Nueva Venta</a></li>
                <li><a href="#" class="menu" title="reporte_ticket-opcion" onmouseup="cerrar()">Imprimir Ticket</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="#" class="menu" title="nuevo_abrecaja" onmouseup="cerrar()">Abrir Caja</a></li>
                <li><a href="#" class="menu" title="nuevo_cierracaja" onmouseup="cerrar()">Cerrar Caja</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="#" class="menu" title="nuevo_arqueocaja" onmouseup="cerrar()">Arqueo de Caja</a></li>
                <li><a href="#" class="menu" title="nuevo_retirocaja" onmouseup="cerrar()">Retiro de Caja</a></li>
                <?php  if (strtolower($_SESSION['tipo_user']) == 'admin') {
            echo ' 
                <li role="separator" class="divider"></li>
                <li><a href="#" class="menu" title="reporte_ventas-opcion" onmouseup="cerrar()">Reporte de Ventas</a></li>
                <li><a href="#" class="menu" title="reporte_cuentascorrientes-opcion" onmouseup="cerrar()">Reporte de Cuentas corrientes</a></li>
                </ul>
              </li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Compras <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#" class="menu" title="construccion_construccion" onmouseup="cerrar()">Nueva factura</a></li>
                <li><a href="#" class="menu" title="nuevo_remito" onmouseup="cerrar()">Nuevo Remito</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="#" class="menu" title="construccion_construccion" onmouseup="cerrar()" >Reporte de compras</a></li>
                <li><a href="#" class="menu" title="reporte_stock-opcion" onmouseup="cerrar()">Reporte de stock</a></li>
              </ul>
            </li>   
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Altas <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#" class="menu" title="nuevo_cliente" onmouseup="cerrar()">Clientes</a></li>
                <li><a href="#" class="menu" title="nuevo_proveedor" onmouseup="cerrar()">Proveedores</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="#" class="menu" title="nuevo_producto" onmouseup="cerrar()">Productos</a></li>
                <li><a href="#" class="menu" title="nuevo_buscaproducto" onmouseup="cerrar()">Modificar Productos</a></li>
                <li><a href="#" class="menu" title="nuevo_etiqueta" onmouseup="cerrar()">Imprimir Etiquetas</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="#" class="menu" title="nuevo_rubro" onmouseup="cerrar()">Rubros</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Configuración <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#" class="menu" title="nuevo_configuracion" onmouseup="cerrar()">Parámetros por sucursal</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="#" class="menu" title="nuevo_usuario" onmouseup="cerrar()">Usuarios</a></li>
              </ul>
            </li>
          ';}?>                
          </ul>

          <ul class="nav navbar-nav navbar-right">
            <li class="navbar-brand" ></li>     
            <li class="navbar-brand" ><?php echo htmlspecialchars($_SESSION['usuario'].' - CAJA # '.$cierre, ENT_QUOTES, 'UTF-8')?></li>
            <li class="active"><a href="conexion/logout.php">Salir <span class="sr-only"></span></a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <div class="page-header"></div>

    <div class="container" id="panel_inicio">
      
      <!-- Aqui se cargan los paneles de trabajo -->
      <div class="text-center">
      <img style="display:block; margin:20px auto 0; width:55%; max-width:500px; height:auto;" src="images/logo_central.png">
      </div>

    </div>

    <!-- <footer class="footer">
      <div class="container">
        <p class="text-muted">Desarrollado por apss.com.ar</p>
      </div>
    </footer> -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/jquery.min.js"></script>
    <script src="js/fx.js"></script>
    <script src="js/bootstrap-toggle.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.mask.min.js"></script>
    <script src="js/custom.js"></script>
    <script src="js/icheck.min.js"></script>
    <script type="text/javascript">
    var desde = <?php echo json_encode($apertura);?>

    $(document).ready(function () {
  
      if (desde == '') {

      }else{
        alert('Existe caja abierta desde '+desde);
      }
  
    });

    function PulsarTecla(e){   
     
      var e = e || event;
      var tecla =  e.keyCode ;
     
      if(tecla==113)
      {
          $('#panel_inicio').load('clases/nuevo/consultaprecio.php')
      }
        
    }
 
    document.onkeydown = PulsarTecla

    function cerrar(){
      $('.navbar-collapse').collapse('hide');
    }
    </script>
  </body>
</html>
