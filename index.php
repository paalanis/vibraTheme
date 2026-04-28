<?php
session_start();
if (isset($_SESSION['usuario'])) {
header("Location: index2.php");
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="Pablo Alanis"  content="">
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon">  <!-- logo -->

    <title>HOLA Express</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/signin.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/cargando.css" />

  
  </head>

  <body>
    <!-- <div class="container"> -->
   
      <form class="form-signin" action="conexion/login.php" method="post">
        <h2 class="form-signin-heading"></h2>
        <label for="inputEmail" class="sr-only">Usuario</label>
        <input type="text" name="usuario" id="user" class="form-control" autocomplete="off" placeholder="Usuario" required autofocus>
        <label for="inputPassword" class="sr-only">Password</label>
        <input type="password" id="pword" name="pass" autocomplete="off" class="form-control" placeholder="Password" required>
        <div class="checkbox">
          <!-- <label>
            <input type="checkbox" value="remember-me"> Remember me
          </label> -->
        </div>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Ingresar</button>
      </form>

     <div id="divlog"></div>

    </div> <!-- /container 


    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    
  </body>
</html>

