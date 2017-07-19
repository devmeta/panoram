function perfil_autos_fetch(){
	$('.publicaciones__caritem__options').fadeOut()
	$.server({
		url : '/perfil/transmisiones', 
		success: function(response){

			if( ! $.isEmptyObject(response.listing) && response.listing.data.length){
				$('.content_publicaciones').html($.templates("#publicaciones").render(response.listing.data,helpers.listing))
				if(response.listing.data.length > 5){
					$('.publicaciones__caritem__search').delay(500).fadeIn(1000)
				}
			} else {
				$('.content_publicaciones').find('.noinfo').show()
			}

			if($.isEmptyObject(response.fav)){
				$('.tab-pane__fav').find('.noinfo').show()
			} else {
				$('.tab-pane__fav').html($.templates("#fav").render(response.fav.data,helpers.listing))
			}

			if($.isEmptyObject(response.alert)){
				$('.tab-pane__alert').parent().find('.noinfo').show()
			} else {
				$('.tab-pane__alert').html($.templates("#alert").render(response.alert.data,helpers.listing)).promise().done(function(){
					webflow_reset()
				});
			}

			hideLoader()
		}
	})
}

function parse_reponse(resp){
	$('.publicaciones__caritem__options').fadeOut()
	$('.publicaciones__caritem').removeClass('selected')
    for(var i in resp.data){
    	var current = $('.publicaciones__caritem.'+resp.data[i].data.code)
    	current.before($.templates('#publicaciones').render([resp.data[i].data],helpers.listing))
    	current.remove()
    }
}

$(document).on('keyup','.publicaciones--filtro',function(e){
	var index = $(this).val().toLowerCase()
	$('.publicaciones__caritem').each(function(){
		var m = $(this).find('.caritem__principalinfo--h').text().toLowerCase()
		if(m.indexOf(index) == -1) {
			$(this).fadeOut(100)
		} else {
			$(this).fadeIn(100)	
		}  			
	})
})

$(document).on('click','.publicaciones__caritem',function(e){

	if($(e.target).is('a'))	return true

	e.preventDefault()

	if(	$(e.target).hasClass('caritem--img') || 
		$(e.target).hasClass('vendido') || 
		$(e.target).parents().hasClass('vendido')) 

		return null

	if($(this).hasClass('selected')){
		$(this).removeClass('selected')
	} else {
		$(this).addClass('selected')
	}

	if($('.publicaciones__caritem.selected').length){
	
		var showbuttons = []

		$('.publicaciones__caritem.selected').each(function(){

			if($(this).data('sold')) {
				showbuttons.push('publicaciones__disponible')
			} else {
				showbuttons.push('publicaciones__vendido')	
				if($(this).data('paused')) {
					showbuttons.push('publicaciones__despausar')
				} else {
					if($(this).data('active')){
						showbuttons.push('publicaciones__pausar')	
					}
				} 
				if(! $(this).data('active')) showbuttons.push('publicaciones__renovar')
			} 				

			$('.publicaciones__caritem__options a').hide()
			$('.publicaciones__caritem__options').fadeIn()

			for(var i in showbuttons){
				$('.'+showbuttons[i]).show()
			}
		})

		if($('.publicaciones__caritem__options').is(':hidden')){
			$('.publicaciones__caritem__options').fadeIn()
		}
	} else {
		if($('.publicaciones__caritem__options').is(':visible')){
			$('.publicaciones__caritem__options').fadeOut()
		}
	}
})

$(document).on('click','.publicaciones__pausar',function(e){
	e.preventDefault()
	
	var codes = []

	$('.publicaciones__caritem.selected').each(function(){
		codes.push($(this).attr('id'));
	})

	swal({   
		title: "Pausar publicaciones",   
		text: "Mientras las pubicaciones se encuentren en pausa no serán visibles en el sitio. Estas seguro que querés pausar estas publicaciones?",
		type: "warning",
		showCancelButton: true,   
		closeOnConfirm: false,   
		showLoaderOnConfirm: true,
	}, function(){   
		$.server({ 
			url: '/perfil/transmisiones/pausar',
			data: {'codes':codes},
			success: function(resp){
				swal.close()
			    $('.publish__uploadimages--info').text("Tu publicación ha sido pausada")
				parse_reponse(resp)
			}
		})		
	})	
})

$(document).on('click','.publicaciones__despausar',function(e){
	e.preventDefault()
	var codes = []
	$('.publicaciones__caritem.selected').each(function(){
		codes.push($(this).attr('id'));
	})

	swal({   
		title: "Quitar de Pausa publicaciones",   
		text: "Mientras las pubicaciones se encuentren en pausa no serán visibles en el sitio. Estas seguro que querés restaurar estas publicaciones?",
		type: "warning",
		showCancelButton: true,   
		closeOnConfirm: false,   
		showLoaderOnConfirm: true,
	}, function(){   
		$.server({ 
			url: '/perfil/transmisiones/despausar',
			data: {'codes':codes},
			success: function(resp){
				swal.close()
			    $('.publish__uploadimages--info').text("Tu publicación ha sido eliminada")
			    parse_reponse(resp)
			}
		})		
	})	
})

$(document).on('click','.publicaciones__renovar',function(e){
	e.preventDefault()
	var codes = []
	$('.publicaciones__caritem.selected').each(function(){
		codes.push($(this).attr('id'));
	})

	swal({   
		title: "Renovar publicaciones",   
		text: "Las publicaciones se renovarán y serán vigentes por el plazo de 65 días. Estas seguro que querés renovar estas publicaciones?",
		type: "warning",
		showCancelButton: true,   
		closeOnConfirm: false,   
		showLoaderOnConfirm: true,
	}, function(){   
		$.server({ 
			url: '/perfil/transmisiones/renovar',
			data: {'codes':codes},
			success: function(resp){
				swal.close()
			    $('.publish__uploadimages--info').text("Tu publicación ha sido renovada")
			    parse_reponse(resp)
			}
		})		
	})	
})

$(document).on('click','.publicaciones__vendido',function(e){
	e.preventDefault()
	var codes = []
	$('.publicaciones__caritem.selected').each(function(){
		codes.push($(this).attr('id'));
	})

	swal({   
		title: "Establecer como vendido",   
		text: "Estas seguro que querés establecer como vendidas estas publicaciones?",
		type: "warning",
		showCancelButton: true,   
		closeOnConfirm: false,   
		showLoaderOnConfirm: true,
	}, function(){   
		$.server({ 
			url: '/perfil/transmisiones/vendido',
			data: {'codes':codes},
			success: function(resp){
				swal.close()
			    $('.publish__uploadimages--info').text("Tu publicación ha sido establecida como vendida")
			    parse_reponse(resp)
			}
		})		
	})	
})

$(document).on('click','.publicaciones__disponible',function(e){
	e.preventDefault()
	var codes = []
	$('.publicaciones__caritem.selected').each(function(){
		codes.push($(this).attr('id'));
	})

	swal({   
		title: "Establecer como disponible",   
		text: "Estas seguro que querés establecer como disponibles estas publicaciones?",
		type: "warning",
		showCancelButton: true,   
		closeOnConfirm: false,   
		showLoaderOnConfirm: true,
	}, function(){   
		$.server({ 
			url: '/perfil/transmisiones/disponible',
			data: {'codes':codes},
			success: function(resp){
				swal.close()
			    $('.publish__uploadimages--info').text("Tu publicación ha sido establecida como disponible")
			    parse_reponse(resp)
			}
		})		
	})	
})

$(document).on('click','.caritem--eliminar',function(e){
	e.preventDefault()
	var code = $(this).data('code')
	swal({   
		title: "Borrar publicación",   
		text: "Estas seguro que querés eliminar esta publicación?",   
		type: "warning",
		showCancelButton: true,   
		closeOnConfirm: false,   
		showLoaderOnConfirm: true,
	}, function(){   
		$.server({ 
			url: '/perfil/transmisiones/eliminar/' + code,
			success: function(){
				$('.' + code).fadeOut(1000, function(){

					swal.close()

				    $('.publish__uploadimages--info').text("Tu publicación ha sido eliminada")

			        if($('.publish__uploadimages--info').is(':hidden')) {
			        	$('.publish__uploadimages--info').fadeIn()
			        	setTimeout(function(){
			        		$('.publish__uploadimages--info').fadeOut(1000)
			        	},5000)
			        }
				})
			}
		})		
	})
})

$(function(){
	showLoader();
    perfil_autos_fetch();
})