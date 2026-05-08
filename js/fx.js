// MENU --------------------------------------------------------------------------------
	$(function() {

		$('.menu').click(function(){

			var titulo = $(this).attr('title')
			var datos = titulo.split('_',2)
			
			var carpeta = datos[0]
			var opcion = datos[1]

			$("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
			$('#panel_inicio').load("clases/"+carpeta+"/"+opcion+".php");

		})
	})
	//FIN MENU	------------------------------------------------------------------------

// NUEVOS --------------------------------------------------------------------------------

	function nuevo(formulario){

		var pars = ''
		var campos = Array()
		var campospasan = Array()

		$("#formulario_nuevo").find(':input').each(function(){
              
            var id = $(this).attr('id');
            if (!id) return;
            var dato = id.split('_',2) 
          
            if (dato[0] == 'dato') {
               campos.push("dato_"+dato[1])
              campospasan.push("dato_"+dato[1])
            };
              
          });
		
		 for (i = 0; i < campos.length; i++) {
			campo = document.getElementById(campos[i]);

			pars =pars + campospasan[i] + "=" + campo.value + "&";
		 }	
		//alert

		// CSRF token (seguridad)
		var csrfToken = $('meta[name="csrf-token"]').attr('content');
		pars = pars + "csrf_token=" + csrfToken + "&";(pars);
				
				$("#div_mensaje_general").html('<div class="text-center"><div class="loadingsm"></div></div>');
				$('#boton_guardar').attr('disabled', true);

				$.ajax({
						url : "clases/guardar/"+formulario+".php",
						data : pars,
						dataType : "json",
						type : "post",

						success: function(data){
								
							if (data.success == 'true') {

								if (data.tipo == 'ticket') {

									$.ajax({
										url : "ticket/ticket.php",
										data : pars,
										dataType : "json",
										type : "post",

										success: function(data){

											$('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Ingreso exitoso!</div>');				
											setTimeout("$('#mensaje_general').alert('close')", 1000);
											setTimeout("$('#panel_inicio').load('clases/nuevo/"+formulario+".php')", 1050);

										}
									});


								};

								$('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Ingreso exitoso!</div>');				
								setTimeout("$('#mensaje_general').alert('close')", 2000);
								setTimeout("$('#panel_inicio').load('clases/nuevo/"+formulario+".php')", 1050);
							} else {
								$('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');				
								setTimeout("$('#mensaje_general').alert('close')", 2000);
							}
						
						}	

				});

	}


 	//FIN NUEVOS	------------------------------------------------------------------------

// REPORTES --------------------------------------------------------------------------------

	function reporte(formulario){

		var pars = ''
		var campos = Array()
		var campospasan = Array()

		$("#formulario_reporte").find(':input').each(function(){
              
            //alert($(this).attr('id'))
            var dato = id.split('_',2) 

            if (dato[0] == 'dato') {
               campos.push("dato_"+dato[1])
              campospasan.push("dato_"+dato[1])
            };
              
          });
		
		 for (i = 0; i < campos.length; i++) {
			campo = document.getElementById(campos[i]);

			pars =pars + campospasan[i] + "=" + campo.value + "&";
		 }	
		  //alert

		// CSRF token (seguridad)
		var csrfToken = $('meta[name="csrf-token"]').attr('content');
		pars = pars + "csrf_token=" + csrfToken + "&";(pars);
				
				$("#div_reporte").html('<div class="text-center"><div class="loadingsm"></div></div>');
				$("#div_reporte").load("clases/reporte/"+formulario+".php", pars);

	}

	//FIN REPORTES	------------------------------------------------------------------------

// CONTROLES --------------------------------------------------------------------------------

	function saldo(){
		// if (typeof $('#dato_insumo').val() == "undefined"){
  		//	var insumo = $("#insumo_e").val()
		// }else{

			var insumo = $("#dato_insumo").val()
		// }

		
		if (insumo != "") {
		$("#div_saldo").html('<div class="text-center"><div class="loadingsm"></div></div>');
		$("#div_saldo").load("clases/control/saldo_insumo.php", {insumo: insumo});			
		}else{
			$("#div_saldo").html('');
		}
	}

	//FIN CONTROLES	------------------------------------------------------------------------
	
// MODIFICA --------------------------------------------------------------------------------

	function modifica(formulario){

		var pars = ''
		var campos = Array()
		var campospasan = Array()

		$("#formulario_nuevo").find(':input').each(function(){
              
            var id = $(this).attr('id');
            if (!id) return;
            var dato = id.split('_',2) 

            if (dato[0] == 'dato') {
               campos.push("dato_"+dato[1])
              campospasan.push("dato_"+dato[1])
            };
              
          });
		
		 for (i = 0; i < campos.length; i++) {
			campo = document.getElementById(campos[i]);

			pars =pars + campospasan[i] + "=" + campo.value + "&";
		 }	
		//alert

		// CSRF token (seguridad)
		var csrfToken = $('meta[name="csrf-token"]').attr('content');
		pars = pars + "csrf_token=" + csrfToken + "&";(formulario);
				
				$("#div_mensaje_general").html('<div class="text-center"><div class="loadingsm"></div></div>');
				$('#boton_guardar').attr('disabled', true);

				$.ajax({
						url : "clases/modifica/"+formulario+".php",
						data : pars,
						dataType : "json",
						type : "post",

						success: function(data){
								
							if (data.success == 'true') {	

								if (data.tipo == 'ticket') {

									$.ajax({
										url : "ticket/frente_gondola.php",
										data : pars,
										dataType : "json",
										type : "post",
										
									});	

								}


								$('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Ingreso exitoso!</div>');				
								setTimeout("$('#mensaje_general').alert('close')", 1000);
								if (formulario == 'modificaproducto'){
								setTimeout("$('#panel_inicio').load('clases/nuevo/buscaproducto.php')", 1050);	
								}else{
									if (formulario == 'viaje'){
									setTimeout("$('#panel_inicio').load('clases/reporte/"+formulario+"-opcion.php')", 1050);	
									}else{
									setTimeout("$('#panel_inicio').load('clases/nuevo/"+formulario+".php')", 1050);
									}
								}
							} else {
								$('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');				
								setTimeout("$('#mensaje_general').alert('close')", 2000);
							}
						
						}	

				});	
	}

 	//FIN MODIFICA	------------------------------------------------------------------------

// PUNTUALES --------------------------------------------------------------------------------

	function carga(formulario){

		var pars = ''
		var campos = Array()
		var campospasan = Array()

		$("#formulario_nuevo").find(':input').each(function(){
              
            var id = $(this).attr('id');
            if (!id) return;
            var dato = id.split('_',2) 
          
            if (dato[0] == 'dato') {
               campos.push("dato_"+dato[1])
              campospasan.push("dato_"+dato[1])
            };
              
          });
		
		 for (i = 0; i < campos.length; i++) {
			campo = document.getElementById(campos[i]);

			pars =pars + campospasan[i] + "=" + campo.value + "&";
		 }	
		//alert

		// CSRF token (seguridad)
		var csrfToken = $('meta[name="csrf-token"]').attr('content');
		pars = pars + "csrf_token=" + csrfToken + "&";(pars);
				
				$("#div_remitos").html('<div class="text-center"><div class="loadingsm"></div></div>');
				$('#boton_producto').attr('disabled', true);

				$.ajax({
						url : "clases/guardar/carga-"+formulario+".php",
						data : pars,
						dataType : "json",
						type : "post",

						success: function(data){

							switch(formulario){

							  case 'producto':

							  	if (data.success == 'true') {

									$('#div_remitos').load('clases/nuevo/remitoinsumo.php', {remito: data.remito, proveedor: data.proveedor});
									$('#boton_producto').attr('disabled', false);
									$("#dato_producto").val('')
									$("#dato_precio").val('')
									$("#dato_cantidad").val('')
									$("#codigo").val('')
									$("#codigo").focus()
								} else {
									
									if (data.success == 'false') {
										$('#div_remitos').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');				
										setTimeout("$('#mensaje_general').alert('close')", 2000);
									    $('#boton_producto').attr('disabled', false);

									}
									if (data.success == 'duplicado') {
										$('#div_duplicado').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Producto duplicado!</div>');				
										$('#div_remitos').load('clases/nuevo/remitoinsumo.php', {remito: data.remito, proveedor: data.proveedor});
										setTimeout("$('#mensaje_general').alert('close')", 2000);
										$('#boton_producto').attr('disabled', false);
										$("#dato_producto").val('')
										$("#dato_precio").val('')
										$("#dato_cantidad").val('')
										$("#codigo").val('')
										$("#codigo").focus()
									}
								}
							    break;
							  
							  case 'factura':

							  	if (data.success == 'true') {

									$('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: data.factura, cliente: data.cliente});
									$('#boton_producto').attr('disabled', false);
									$("#dato_producto").val('')
									$("#dato_precio").val('')
									$("#dato_cantidad").val('')
									$("#codigo").val('')
									$("#codigo").focus()
								} else {
									
									if (data.success == 'false') {
										$('#div_remitos').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');				
										setTimeout("$('#mensaje_general').alert('close')", 2000);
									    $('#boton_producto').attr('disabled', false);

									}
									if (data.success == 'duplicado') {
										$('#div_duplicado').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Producto duplicado!</div>');				
										$('#div_remitos').load('clases/nuevo/facturainsumo.php', {factura: data.factura, cliente: data.cliente});
										setTimeout("$('#mensaje_general').alert('close')", 2000);
										$('#boton_producto').attr('disabled', false);
										$("#dato_producto").val('')
										$("#dato_precio").val('')
										$("#dato_cantidad").val('')
										$("#codigo").val('')
										$("#codigo").focus()
									}
								}
							  	break;

							  default:

							  	if (data.success == 'true') {

									$('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-info alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Ingreso exitoso!</div>');				
									setTimeout("$('#mensaje_general').alert('close')", 1000);
									setTimeout("$('#panel_inicio').load('clases/nuevo/"+formulario+".php')", 1050);
								} else {
									$('#div_mensaje_general').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');				
									setTimeout("$('#mensaje_general').alert('close')", 2000);

								}



							 }
						
						}	

				});
 	}




	function inicio(){

		 window.location.href = "index2.php";
	}


	function imprimepdf(pdf,dato){

		var pdf = pdf.toString();
		var dato = dato.toString();
		//alert(pdf)
		//alert(dato)

		switch(pdf){

			case '1':
			var pdf = 'liquidacion'
			break

		}


		$("#div_boton_imprimir").html('<div class="text-center"><div class="loadingsm"></div></div>');
		location.href = 'http://localhost/holaexpress/clases/pdf/'+pdf+'.php/?dato='+dato+''
		setTimeout("$('#panel_inicio').load('clases/nuevo/"+pdf+".php')", 2000);
	

	}

	function abrir(formulario){

		var usuario =  $('#id_usuario').val();
		var cierre =  $('#id_cierre').val();

		if ($('#retiro').val() === undefined) {

		var efectivo = $('#efectivo').val();
		var pars = "id=" + usuario + "&" + "cierre=" + cierre + "&" + "efectivo=" + efectivo + "&";

		}else{

		var retiro = $('#retiro').val();
		var obs = $('#obs').val();
		var pars = "retiro=" + retiro + "&" + "obs=" + obs + "&";
			
		}

		$("#div_cargando").html('<div class="text-center"><div class="loadingsm"></div></div>');
		
		//alert(pars);
				$.ajax({
						url : "clases/guardar/"+formulario+".php",
						data : pars,
						dataType : "json",
						type : "post",

						success: function(data){
								
							if (formulario == 'abrecaja' || formulario == 'retirocaja') {

								if (data.success == 'true') {

									$.ajax({
										url : "ticket/abrecaja.php",
										data : pars,
										dataType : "json",
										type : "post",

										success: function(data){

											if (formulario == 'abrecaja') {
												location.reload();
											}else{
											$('#modal_abrecaja').modal('hide')
											$("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
											setTimeout("$('#panel_inicio').load('clases/nuevo/factura.php')", 1000);
											}

										}
									});
							
								} else {
									$('#div_cargando').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');				
									setTimeout("$('#mensaje_general').alert('close')", 2000);
								}

							}else{

								if (data.success == 'true') {

									$.ajax({
										url : "ticket/cierracaja.php",
										data : pars,
										dataType : "json",
										type : "post",

										success: function(data){


											location.reload();
											// $('#modal_abrecaja').modal('hide')
											// $("#panel_inicio").html('<div class="text-center"><div class="loadingsm"></div></div>');
											// $("#panel_inicio").html('');

											}
										});

																
								} else {
									$('#div_cargando').html('<div id="mensaje_general" class="alert alert-danger alert-dismissible" style="height:47px" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error reintente!</div>');				
									setTimeout("$('#mensaje_general').alert('close')", 2000);
								}
							}
						
						}	

				});

	}

	 