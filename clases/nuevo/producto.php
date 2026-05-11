<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("Error de conexión: %s\n", mysqli_connect_error()); exit();
}

$codigo = $_REQUEST['codigo'] ?? '';
$modo   = $_REQUEST['buscar_modo'] ?? 'venta'; // 'remito' | 'venta'
$buscar = '%' . $codigo . '%';

// precio_venta = precio_costo * (1 + margen_ganancia/100)
$stmt = mysqli_prepare($conexion,
    "SELECT
        p.id_productos                                        AS id,
        p.codigo                                              AS codigo2,
        p.nombre                                              AS producto,
        p.precio_costo                                        AS precio_costo,
        p.margen_ganancia                                     AS margen,
        ROUND(p.precio_costo * (1 + p.margen_ganancia/100), 2) AS precio_venta
     FROM tb_productos p
     WHERE CONCAT(p.nombre, p.codigo) LIKE ?
     ORDER BY p.nombre ASC"
);
mysqli_stmt_bind_param($stmt, 's', $buscar);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_codigo, $r_nombre, $r_costo, $r_margen, $r_pventa);

$filas      = 0;
$lista      = [];   // codigo => precio_venta
$listacosto = [];   // codigo => precio_costo
$listamargen= [];   // codigo => margen
$rows       = [];

while (mysqli_stmt_fetch($stmt)) {
    $filas++;
    $lista[$r_codigo]       = (float)$r_pventa;
    $listacosto[$r_codigo]  = (float)$r_costo;
    $listamargen[$r_codigo] = (float)$r_margen;
    $rows[] = [
        'id'     => $r_id,
        'codigo' => $r_codigo,
        'nombre' => $r_nombre,
        'pventa' => $r_pventa,
        'costo'  => $r_costo,
        'margen' => $r_margen,
    ];
}
mysqli_stmt_close($stmt);
?>

<?php
// Auto-selección: búsqueda por código y resultado único
$buscar_tipo  = $_REQUEST['buscar'] ?? 'nombre';
$auto_select  = ($buscar_tipo === 'codigo' && $filas === 1);
$auto_codigo  = $auto_select ? $rows[0]['codigo'] : '';
?>
<?php if ($modo === 'remito'): ?>
<!-- ── MODO REMITO ── -->
<div class="col-lg-12">

  <!-- Fila 1: Producto + Cantidad -->
  <div class="row" style="margin-bottom:6px">
    <div class="col-lg-8">
      <select class="form-control input-sm" id="dato_producto" required>
        <option value="">-- Seleccione producto --</option>
        <?php foreach ($rows as $row):
          $cod  = htmlspecialchars($row['codigo'], ENT_QUOTES, 'UTF-8');
          $nom  = htmlspecialchars(mb_convert_encoding($row['nombre'],'UTF-8','ISO-8859-1'), ENT_QUOTES, 'UTF-8');
          $sel  = ($auto_select && $row['codigo'] === $auto_codigo) ? ' selected' : '';
        ?>
          <option value="<?php echo $cod; ?>"<?php echo $sel; ?>><?php echo $nom; ?></option>
        <?php endforeach; ?>
        <?php if ($filas === 0): ?>
          <option value="">— Producto no encontrado —</option>
        <?php endif; ?>
      </select>
    </div>
    <div class="col-lg-4">
      <div class="input-group input-group-sm">
        <span class="input-group-addon">Cant.</span>
        <input class="form-control" autocomplete="off" value="1"
               id="dato_cantidad" type="number" min="1" step="1" required>
      </div>
    </div>
  </div>

  <!-- Fila 2: Costo + Margen + Precio venta + Botón -->
  <div class="row">
    <div class="col-lg-3">
      <div class="input-group input-group-sm">
        <span class="input-group-addon">$ costo</span>
        <input class="form-control" autocomplete="off" id="dato_precio"
               type="number" min="0" step="0.01" placeholder="0.00">
      </div>
    </div>
    <div class="col-lg-3">
      <div class="input-group input-group-sm">
        <input class="form-control" autocomplete="off" id="dato_margen"
               type="number" min="0" step="0.01" placeholder="0.00">
        <span class="input-group-addon">%</span>
      </div>
    </div>
    <div class="col-lg-3">
      <div class="input-group input-group-sm">
        <span class="input-group-addon">$ venta</span>
        <input class="form-control" id="precio_venta_muestra"
               type="text" readonly style="background:#f5f5f5">
      </div>
    </div>
    <div class="col-lg-3">
      <button type="submit" id="boton_producto" class="btn btn-success btn-sm btn-block" disabled>
        Cargar
      </button>
    </div>
  </div>

</div>

<script type="text/javascript">
(function() {
    var costos   = <?php echo json_encode($listacosto);  ?>;
    var margenes = <?php echo json_encode($listamargen); ?>;
    var autoSel  = <?php echo $auto_select ? 'true' : 'false'; ?>;

    function calcPV() {
        var costo  = parseFloat($('#dato_precio').val())  || 0;
        var margen = parseFloat($('#dato_margen').val())  || 0;
        if (costo > 0) {
            $('#precio_venta_muestra').val('$ ' + (costo * (1 + margen / 100)).toFixed(2));
        } else {
            $('#precio_venta_muestra').val('');
        }
    }

    function seleccionar(cod) {
        if (cod !== '') {
            $('#dato_precio').val(costos[cod]   || '');
            $('#dato_margen').val(margenes[cod] || '');
            calcPV();
            $('#boton_producto').prop('disabled', false);
            $('#dato_cantidad').focus().select();
        } else {
            $('#dato_precio').val('');
            $('#dato_margen').val('');
            $('#precio_venta_muestra').val('');
            $('#boton_producto').prop('disabled', true);
        }
    }

    $('#dato_producto').on('change', function() {
        seleccionar($(this).val());
    });

    // Auto-selección si búsqueda por código único
    if (autoSel) {
        seleccionar($('#dato_producto').val());
    }

    $('#dato_precio, #dato_margen').on('input', calcPV);
})();
</script>

<?php else: ?>
<!-- ── MODO VENTA: comportamiento original con precio calculado ── -->
<div class="col-lg-7">
    <select class="form-control" id="dato_producto" required>
        <option value="">Seleccione producto</option>
        <?php foreach ($rows as $row): ?>
            <option value="<?php echo htmlspecialchars($row['codigo'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
        <?php if ($filas === 0): ?>
            <option value="">NO EXISTE EL PRODUCTO</option>
        <?php endif; ?>
    </select>
</div>
<div class="col-lg-2">
    <input class="form-control" autocomplete="off" value=""
           placeholder="$$" id="dato_precio"
           type="text" required disabled>
</div>
<div class="col-lg-3">
    <button type="button" id="boton_producto" class="btn btn-success">
        Cargar Producto
    </button>
</div>

<script type="text/javascript">
(function() {
    $('#boton_producto').prop('disabled', true);
    var precios = <?php echo json_encode($lista); ?>;

    $('#dato_producto').change(function() {
        var cod = $(this).val();
        if (cod !== '') {
            $('#dato_precio').val(precios[cod] || '0.00');
            $('#boton_producto').prop('disabled', false);
            $('#dato_precio').prop('disabled', true);
        } else {
            $('#dato_precio').val('');
            $('#boton_producto').prop('disabled', true);
        }
    });

    $('#boton_producto').click(function() {
        if ($('#dato_producto').val() !== '' && $('#dato_cantidad').val() > 0) {
            var pars = '';
            var campos = [], campospasan = [];
            $("#formulario_nuevo").find(':input').each(function() {
                var _id = $(this).attr('id'); if (!_id) return;
                var dato = _id.split('_', 2);
                if (dato[0] === 'dato') {
                    campos.push('dato_' + dato[1]);
                    campospasan.push('dato_' + dato[1]);
                }
            });
            for (var i = 0; i < campos.length; i++) {
                pars += campospasan[i] + '=' + document.getElementById(campos[i]).value + '&';
            }
            $("#div_remitos").html('<div class="text-center"><div class="loadingsm"></div></div>');
            $.ajax({
                url     : "clases/guardar/producto-caja.php",
                data    : pars,
                dataType: "json",
                type    : "get",
                success : function(data) {
                    switch (data.success) {
                        case 'true':
                            $('#div_remitos').load('clases/nuevo/facturainsumo.php',
                                {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                            $('#dato_cantidad').val(1);
                            $('#dato_codigo').val('');
                            $('#nombre').val('');
                            $('#dato_codigo').focus();
                            break;
                        case 'no_existe':
                            $('#div_duplicado').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                                '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                                'Producto inexistente!</div>');
                            setTimeout("$('#div_duplicado').find('.alert').alert('close')", 2000);
                            $('#div_remitos').load('clases/nuevo/facturainsumo.php',
                                {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                            $('#dato_cantidad').val('1');
                            $('#dato_codigo').val('');
                            $('#nombre').val('');
                            $('#dato_codigo').focus();
                            break;
                        case 'false':
                            $('#div_remitos').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                                '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                                'Error, reintente!</div>');
                            setTimeout("$('#div_remitos').find('.alert').alert('close')", 2000);
                            break;
                    }
                }
            });
        } else {
            alert('Faltan datos');
        }
    });
})();
</script>

<?php endif; ?>
