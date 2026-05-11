<?php
session_start();
if (!isset($_SESSION['usuario']) || strtolower($_SESSION['tipo_user'] ?? '') !== 'admin') {
    header('Location: ../../index.php'); exit();
}
include '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("La conexión con el servidor de base de datos falló comuniquese con su administrador: %s\n", mysqli_connect_error());
    exit();
}
?>
<form class="form-horizontal" role="form" id="formulario_nuevo" onsubmit="event.preventDefault(); nuevo('usuario')">

  <div class="modal-header">
    <h4 class="modal-title">Gestión de Usuarios</h4>
  </div>
  <br>

  <div class="well bs-component">
    <div class="row">

      <!-- Columna izquierda: formulario -->
      <div class="col-lg-5">
        <fieldset>

          <div class="form-group form-group-sm">
            <label class="col-lg-3 control-label">Usuario</label>
            <div class="col-lg-9">
              <input type="text" class="form-control" autocomplete="off"
                     id="dato_nombre" placeholder="Nombre de usuario" required autofocus>
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="col-lg-3 control-label">Contraseña</label>
            <div class="col-lg-9">
              <div class="input-group input-group-sm">
                <input type="password" class="form-control" autocomplete="new-password"
                       id="dato_pass" placeholder="Contraseña" required>
                <span class="input-group-btn">
                  <button class="btn btn-default btn-xs" type="button" id="btn_toggle_pass"
                          onclick="togglePass()" tabindex="-1"
                          style="height:100%; padding: 2px 8px; font-size:12px;">
                    <span class="glyphicon glyphicon-eye-open" id="ico_pass"></span>
                  </button>
                </span>
              </div>
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="col-lg-3 control-label">Rol</label>
            <div class="col-lg-9">
              <select class="form-control" id="dato_tipo" required>
                <option value="">-- Seleccionar --</option>
                <option value="admin">Administrador</option>
                <option value="cajero">Cajero</option>
              </select>
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="col-lg-3 control-label">Cód. Autoriza</label>
            <div class="col-lg-9">
              <input type="number" class="form-control" autocomplete="off"
                     id="dato_autoriza" placeholder="Ej: 1234 (opcional)">
              <span class="help-block" style="font-size:11px;">
                Código numérico para autorizar operaciones especiales. Dejar vacío si no aplica.
              </span>
            </div>
          </div>

        </fieldset>
      </div>

      <!-- Columna derecha: tabla de usuarios existentes -->
      <div class="col-lg-7">
        <fieldset>
          <div class="panel panel-default">
            <div class="panel-body" id="Panel1" style="height:280px; overflow-y:auto;">
              <table class="table table-striped table-hover">
                <thead>
                  <tr class="active">
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Autoriza</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                $stmt = mysqli_prepare($conexion,
                    "SELECT id_usuario, nombre, tipo_user, autoriza FROM tb_usuarios ORDER BY id_usuario ASC"
                );
                mysqli_stmt_execute($stmt);
                $id_u = $nom = $tipo = $aut = null;
                mysqli_stmt_bind_result($stmt, $id_u, $nom, $tipo, $aut);
                $count = 0;
                while (mysqli_stmt_fetch($stmt)) {
                    $count++;
                    $nom_esc  = htmlspecialchars($nom,  ENT_QUOTES, 'UTF-8');
                    $tipo_esc = htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8');
                    $aut_esc  = ($aut !== null) ? htmlspecialchars($aut, ENT_QUOTES, 'UTF-8') : '—';
                    $badge = (strtolower($tipo) === 'admin')
                        ? '<span class="label label-danger">Admin</span>'
                        : '<span class="label label-default">Cajero</span>';
                    echo "
                    <tr>
                      <td>{$id_u}</td>
                      <td>{$nom_esc}</td>
                      <td>{$badge}</td>
                      <td>{$aut_esc}</td>
                    </tr>";
                }
                mysqli_stmt_close($stmt);
                if ($count === 0) {
                    echo '<tr><td colspan="4">No hay usuarios cargados.</td></tr>';
                }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </fieldset>
      </div>

    </div>
  </div>

  <div class="modal-footer">
    <div class="form-group form-group-sm">
      <div class="col-lg-7">
        <div align="center" id="div_mensaje_general"></div>
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
function togglePass() {
    var campo = document.getElementById('dato_pass');
    var icono = document.getElementById('ico_pass');
    if (campo.type === 'password') {
        campo.type = 'text';
        icono.className = 'glyphicon glyphicon-eye-close';
    } else {
        campo.type = 'password';
        icono.className = 'glyphicon glyphicon-eye-open';
    }
}
</script>
