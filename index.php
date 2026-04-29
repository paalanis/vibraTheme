<?php
session_start();
if (isset($_SESSION['usuario'])) {
header("Location: index2.php");
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <link rel="shortcut icon" href="images/Logo_vibra.jpeg" type="image/x-icon">

    <title>VIBRA</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

      :root {
        --black:    #111111;
        --offblack: #1a1a1a;
        --white:    #ffffff;
        --grey:     #888888;
        --border:   rgba(255,255,255,0.15);
        --input-bg: rgba(255,255,255,0.05);
      }

      html, body { height: 100%; }

      body {
        background-color: var(--black);
        color: var(--white);
        font-family: 'Barlow', sans-serif;
        font-weight: 300;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        overflow: hidden;
        position: relative;
      }

      /* Grain texture */
      body::before {
        content: '';
        position: fixed;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
        pointer-events: none;
        z-index: 0;
      }

      /* Rings like the logo */
      .bg-ring {
        position: fixed;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,0.04);
        pointer-events: none;
        z-index: 0;
        top: 50%; left: 50%;
        transform: translate(-50%,-50%);
      }
      .bg-ring-1 { width: 600px;  height: 600px;  }
      .bg-ring-2 { width: 820px;  height: 820px;  border-color: rgba(255,255,255,0.025); }
      .bg-ring-3 { width: 1080px; height: 1080px; border-color: rgba(255,255,255,0.015); }

      /* Sparkles */
      .sparkle {
        position: fixed;
        pointer-events: none;
        z-index: 0;
        opacity: 0.22;
        animation: twinkle 3s ease-in-out infinite;
      }
      .sparkle:nth-of-type(2) { animation-delay: 0.8s; }
      .sparkle:nth-of-type(3) { animation-delay: 1.6s; }
      .sparkle:nth-of-type(4) { animation-delay: 2.4s; }
      .sp-tl { top: 12%;    left: 14%;  width: 28px; }
      .sp-tr { top: 8%;     right: 16%; width: 18px; }
      .sp-br { bottom: 14%; right: 12%; width: 32px; }
      .sp-bl { bottom: 18%; left: 18%;  width: 20px; }

      @keyframes twinkle {
        0%, 100% { opacity: 0.18; transform: scale(1)    rotate(0deg);  }
        50%       { opacity: 0.55; transform: scale(1.15) rotate(15deg); }
      }

      /* Card */
      .login-wrap {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        max-width: 380px;
        padding: 0 28px;
        animation: fadeUp 0.7s cubic-bezier(0.22,1,0.36,1) both;
      }

      @keyframes fadeUp {
        from { opacity: 0; transform: translateY(28px); }
        to   { opacity: 1; transform: translateY(0); }
      }

      /* Logo */
      .logo-container { margin-bottom: 40px; text-align: center; }

      .logo-container img {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        object-fit: cover;
        display: block;
        margin: 0 auto;
        filter: drop-shadow(0 0 24px rgba(255,255,255,0.08));
        transition: filter 0.3s;
      }
      .logo-container img:hover {
        filter: drop-shadow(0 0 36px rgba(255,255,255,0.18));
      }

      .tagline {
        margin-top: 10px;
        font-size: 10px;
        letter-spacing: 4px;
        color: var(--grey);
        text-transform: uppercase;
      }

      /* Inputs */
      .form-signin { width: 100%; }

      .form-signin .form-control {
        display: block;
        width: 100%;
        background: var(--input-bg);
        border: 1px solid var(--border);
        border-radius: 4px;
        color: var(--white);
        font-family: 'Barlow', sans-serif;
        font-size: 14px;
        font-weight: 400;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        margin-bottom: 12px;
        outline: none;
        transition: border-color 0.2s, background 0.2s;
      }
      .form-signin .form-control::placeholder { color: var(--grey); font-weight: 300; letter-spacing: 1px; }
      .form-signin .form-control:focus {
        border-color: rgba(255,255,255,0.5);
        background: rgba(255,255,255,0.08);
      }
      .form-signin .form-control:-webkit-autofill,
      .form-signin .form-control:-webkit-autofill:focus {
        -webkit-text-fill-color: var(--white);
        -webkit-box-shadow: 0 0 0 1000px #1a1a1a inset;
      }

      /* Button */
      .btn-ingresar {
        display: block;
        width: 100%;
        margin-top: 8px;
        padding: 14px;
        background: var(--white);
        border: none;
        border-radius: 4px;
        color: var(--black);
        font-family: 'Bebas Neue', sans-serif;
        font-size: 18px;
        letter-spacing: 4px;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
      }
      .btn-ingresar:hover  { background: #e0e0e0; transform: translateY(-1px); }
      .btn-ingresar:active { transform: translateY(0); }

      .divider {
        width: 100%;
        height: 1px;
        background: var(--border);
        margin: 28px 0 0;
      }

      .login-footer {
        margin-top: 16px;
        font-size: 10px;
        letter-spacing: 2px;
        color: rgba(255,255,255,0.2);
        text-transform: uppercase;
        text-align: center;
      }
    </style>
  </head>

  <body>

    <div class="bg-ring bg-ring-1"></div>
    <div class="bg-ring bg-ring-2"></div>
    <div class="bg-ring bg-ring-3"></div>

    <svg class="sparkle sp-tl" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 0 L13.5 10.5 L24 12 L13.5 13.5 L12 24 L10.5 13.5 L0 12 L10.5 10.5 Z"/>
    </svg>
    <svg class="sparkle sp-tr" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 0 L13.5 10.5 L24 12 L13.5 13.5 L12 24 L10.5 13.5 L0 12 L10.5 10.5 Z"/>
    </svg>
    <svg class="sparkle sp-br" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 0 L13.5 10.5 L24 12 L13.5 13.5 L12 24 L10.5 13.5 L0 12 L10.5 10.5 Z"/>
    </svg>
    <svg class="sparkle sp-bl" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 0 L13.5 10.5 L24 12 L13.5 13.5 L12 24 L10.5 13.5 L0 12 L10.5 10.5 Z"/>
    </svg>

    <div class="login-wrap">

      <div class="logo-container">
        <img src="images/Logo_vibra.jpeg" alt="VIBRA">
        <p class="tagline">Pre Teens &nbsp;&ndash;&nbsp; Teens &nbsp;&ndash;&nbsp; Adolescentes</p>
      </div>

      <form class="form-signin" action="conexion/login.php" method="post">
        <input type="text"     name="usuario" id="user"  class="form-control" autocomplete="off" placeholder="Usuario"     required autofocus>
        <input type="password" name="pass"    id="pword" class="form-control" autocomplete="off" placeholder="Contraseña"  required>
        <button class="btn-ingresar" type="submit">Ingresar</button>
      </form>

      <div class="divider"></div>
      <p class="login-footer">Sistema de gestión &nbsp;&middot;&nbsp; VIBRA</p>

    </div>

    <div id="divlog"></div>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

  </body>
</html>
