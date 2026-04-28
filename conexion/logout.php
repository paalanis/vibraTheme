<?php
session_start();
include 'conexion.php';
$id_usuario=$_SESSION['id_usuario'];
mysqli_select_db($conexion,'$basedatos');
$sql = "UPDATE tb_usuarios 
SET estado = '0'
WHERE tb_usuarios.id_usuario = '$id_usuario'";
mysqli_query($conexion,$sql);
$_SESSION['usuario'] = array();
session_destroy();
header("Location: ../index.php");
?>	