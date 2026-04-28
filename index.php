<?php
session_start();
// Si ya está logueado, ir al panel
if (isset($_SESSION['usuario'])) {
    header("Location: index2.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexion/conexion.php';
    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Ajustar según tu tabla de usuarios
    $stmt = mysqli_prepare($conexion,
        "SELECT id_usuario, password FROM tb_usuarios WHERE usuario = ? AND estado = 1 LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($rs);

    if ($row && password_verify($pass, $row['password'])) {
        session_regenerate_id(true);
        $_SESSION['usuario']    = $user;
        $_SESSION['id_usuario'] = $row['id_usuario'];
        header("Location: index2.php");
        exit();
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VIBRA — Ingresar</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/signin.css">
</head>
<body class="signin-page">

<div class="signin-wrap">

  <!-- Panel izquierdo: marca -->
  <div class="signin-left">

    <div class="signin-logo-wrap">
      <img src="images/logo.png" alt="VIBRA" class="signin-logo-img">
      <div class="signin-logo-name">VIBRA</div>
      <div class="signin-logo-sub">Pre Teens &middot; Teens &middot; Adolescentes</div>
    </div>

    <div>
      <div class="signin-tagline">
        GESTIÓN<br>QUE <span>VIBRA</span>
      </div>
      <p class="signin-tagline-sub">Sistema de gestión integral para tu negocio.</p>
    </div>

    <div class="signin-dots">
      <div class="signin-dot active"></div>
      <div class="signin-dot"></div>
      <div class="signin-dot"></div>
    </div>

  </div>

  <!-- Panel derecho: formulario -->
  <div class="signin-right">

    <div>
      <div class="signin-form-title">INGRESAR</div>
      <p class="signin-form-sub">Accedé con tus credenciales</p>
    </div>

    <?php if ($error): ?>
      <div class="signin-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php" autocomplete="off">

      <div class="signin-field" style="margin-bottom: 16px;">
        <label class="signin-label" for="usuario">Usuario</label>
        <input
          type="text"
          class="signin-input"
          id="usuario"
          name="usuario"
          placeholder="Tu usuario"
          autocomplete="username"
          required
        >
      </div>

      <div class="signin-field" style="margin-bottom: 24px;">
        <label class="signin-label" for="password">Contraseña</label>
        <input
          type="password"
          class="signin-input"
          id="password"
          name="password"
          placeholder="••••••••"
          autocomplete="current-password"
          required
        >
      </div>

      <button type="submit" class="signin-btn">ENTRAR &rarr;</button>

    </form>

    <div class="signin-footer">VIBRA &copy; <?php echo date('Y'); ?></div>

  </div>
</div>

</body>
</html>
