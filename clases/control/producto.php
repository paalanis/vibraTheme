<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
}
require_once '../../conexion/conexion.php';
if (mysqli_connect_errno()) {
    printf("La conexión con el servidor de base de datos falló: %s\n", mysqli_connect_error());
    exit();
}

$codigo = $_REQUEST['codigo'] ?? '';
$modo   = $_REQUEST['buscar_modo'] ?? 'venta'; // 'remito' | 'venta'
$buscar = trim('%' . $codigo . '%');

// En modo remito también traemos precio_costo.
$stmt = mysqli_prepare($conexion,
    "SELECT
        tb_productos.id_productos              AS id,
        tb_productos.codigo                    AS codigo2,
        tb_productos.nombre                    AS producto,
        IF(tb_productos.id_rubro > 1,'false','true') AS rubro,
        tb_productos.precio_venta              AS precio_venta,
        tb_productos.precio_costo              AS precio_costo
     FROM tb_productos
     WHERE CONCAT(tb_productos.nombre, tb_productos.codigo) LIKE ?
     ORDER BY tb_productos.nombre ASC"
);
mysqli_stmt_bind_param($stmt, 's', $buscar);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $r_id, $r_codigo, $r_nombre, $r_rubro, $r_precio_venta, $r_precio_costo);

$filas       = 0;
$lista       = [];   // codigo => precio_venta  (modo venta)
$listacosto  = [];   // codigo => precio_costo  (modo remito)
$campoprecio = [];   // codigo => rubro flag
$rows        = [];

while (mysqli_stmt_fetch($stmt)) {
    $filas++;
    $lista[$r_codigo]       = $r_precio_venta;
    $listacosto[$r_codigo]  = $r_precio_costo;
    $campoprecio[$r_codigo] = $r_rubro;
    $rows[] = [
        'id'      => $r_id,
        'codigo'  => $r_codigo,
        'nombre'  => $r_nombre,
        'rubro'   => $r_rubro,
        'pventa'  => $r_precio_venta,
        'pcosto'  => $r_precio_costo,
    ];
}
mysqli_stmt_close($stmt);
?>

<?php if ($modo === 'remito'): ?>
<!-- ── MODO REMITO: cantidad + precio_costo editable ──────────── -->
<div class="col-lg-4">
    <select class="form-control" id="dato_producto" required>
        <option value="">Seleccione producto</option>
        <?php foreach ($rows as $row): ?>
            <option value="<?php echo $row['codigo']; ?>">
                <?php echo mb_convert_encoding($row['nombre'], 'UTF-8', 'ISO-8859-1'); ?>
            </option>
        <?php endforeach; ?>
        <?php if ($filas === 0): ?>
            <option value="">NO EXISTE EL PRODUCTO</option>
        <?php endif; ?>
    </select>
</div>
<div class="col-lg-3">
    <div class="input-group">
        <span class="input-group-addon">Cant.</span>
        <input class="form-control" autocomplete="off" value="1"
               id="dato_cantidad" type="number" min="1" step="1" required>
    </div>
</div>
<div class="col-lg-4">
    <div class="input-group">
        <span class="input-group-addon">$ costo</span>
        <input class="form-control" autocomplete="off" value=""
               id="dato_precio" type="number" min="0" step="0.01">
    </div>
</div>
<div class="col-lg-1">
    <!-- type=submit dispara onsubmit del formulario → carga('producto') -->
    <button type="submit" id="boton_producto" class="btn btn-success" disabled>
        Cargar
    </button>
</div>

<script type="text/javascript">
(function() {
    var costos = <?php echo json_encode($listacosto); ?>;

    $('#dato_producto').change(function() {
        var cod = $(this).val();
        if (cod !== '') {
            $('#dato_precio').val(costos[cod] || '');
            $('#boton_producto').prop('disabled', false);
            $('#dato_cantidad').focus();
        } else {
            $('#dato_precio').val('');
            $('#boton_producto').prop('disabled', true);
        }
    });
})();
</script>

<?php else: ?>
<!-- ── MODO VENTA: comportamiento original ────────────────────── -->
<div class="col-lg-7">
    <select class="form-control" id="dato_producto" required>
        <option value="">Seleccione producto</option>
        <?php foreach ($rows as $row): ?>
            <option value="<?php echo $row['codigo']; ?>">
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
    var tempArray  = <?php echo json_encode($lista); ?>;
    var tempArray2 = <?php echo json_encode($campoprecio); ?>;

    $('#dato_producto').change(function() {
        var cod   = $(this).val();
        var campo = tempArray2[cod];
        $('#dato_precio').val(tempArray[cod]);
        if (cod !== '') {
            $('#boton_producto').prop('disabled', false);
            if (campo === 'false') {
                $('#dato_precio').prop('disabled', false).val('').focus();
            } else {
                $('#dato_precio').prop('disabled', true);
            }
        } else {
            $('#boton_producto').prop('disabled', true);
            $('#dato_precio').prop('disabled', true);
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
                url      : "clases/guardar/producto-caja.php",
                data     : pars,
                dataType : "json",
                type     : "get",
                success  : function(data) {
                    switch (data.success) {
                        case 'true':
                            $('#div_remitos').load('clases/nuevo/facturainsumo.php',
                                {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                            if (data.sin_stock) {
                                $('#div_duplicado').html('<div id="msg_sinstock" class="alert alert-warning alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>⚠ Producto agregado con stock insuficiente</div>');
                                setTimeout("$('#msg_sinstock').alert('close')", 4000);
                            }
                            $('#dato_cantidad').val(1);
                            $('#dato_codigo').val('');
                            $('#nombre').val('');
                            $('#dato_codigo').focus();
                            break;
                        case 'sin_stock':
                            // Producto rechazado por falta de stock (config permite_sin_stock=0)
                            $('#div_remitos').load('clases/nuevo/facturainsumo.php',
                                {factura: data.factura, cliente: data.cliente, cierre: data.cierre});
                            $('#div_duplicado').html('<div id="msg_sinstock" class="alert alert-warning alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>Sin stock suficiente (disponible: ' + data.stock + ', necesita: ' + data.necesita + ')</div>');
                            setTimeout("$('#msg_sinstock').alert('close')", 3000);
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
                                'Error reintente!</div>');
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
