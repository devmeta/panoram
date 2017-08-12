var pano = undefined
, map
, marker 
, markers = []
, auto = function (){
	$.server({
		url: location.pathname,
		success: function(response){

			if(response.status == 'error'){
				$("body").addClass('momargin').load("/message", function(){
					parseMessage(response)
				})

				return false
			}

			pano = response.pano.data
			, token = get_token()
			//hideLoader()

			if(!$.isEmptyObject(pano)){

				$('.car-info-info').html($.templates("#carinfo").render(pano))
				$('.container-profile__detail--financiamiento').html((pano.financing?"Sí":"No"))
				$('.datosvendedir').html($.templates("#datosvendedor").render(pano,helpers.users))
				$('.bestinfo__profile').html($.templates("#profile").render(pano,helpers.listing)).promise().done(function(){
					if(pano.lat && pano.lng){
						L.mapbox.accessToken = geo.mapbox.accessToken
						map = L.mapbox.map('map', 'mapbox.streets');
						marker = L.marker([pano.lat,pano.lng], {icon:geo.icon({displayName:"",className:'me',colorId:1})}).addTo(map);
			        	map.setView([pano.lat,pano.lng], 8)

			        } else {
						L.mapbox.accessToken = geo.mapbox.accessToken
						map = L.mapbox.map('map', 'mapbox.streets');
						//marker = L.marker([0,0]).addTo(map);
			        	map.setView([0,0], 1)

			        	$('#map').css({opacity:0.5})
			        }
				})

				if($.isEmptyObject(token)){
					$('.profile--button').attr('data-ix','register')
				}

				if($.inArray(pano.id,token.owned) > -1){
					$('#form-message').remove()
				}

				if(!$.isEmptyObject(pano.props.sonido)){
					$('.container-profile__detail--sonido').html($.templates("#props").render(pano.props.sonido))
				}

				if(!$.isEmptyObject(pano.props.exterior)){
					$('.container-profile__detail--exterior').html($.templates("#props").render(pano.props.exterior))
				}

				if(!$.isEmptyObject(pano.props.confort)){
					$('.container-profile__detail--confort').html($.templates("#props").render(pano.props.confort))
				}

				if(!$.isEmptyObject(pano.props.seguridad)){
					$('.container-profile__detail--seguridad').html($.templates("#props").render(pano.props.seguridad))
				}

				if(!$.isEmptyObject(pano.props.estado)){
					$('.container-profile__detail--estado').html($.templates("#props").render(pano.props.estado))
				}

				$('.w-slider-mask').html($.templates("#slide").render(pano.files,helpers.listing)).promise().done(function(){
					$('.bestinfo__slider--nav').html($.templates("#slide-nav").render(pano.files,helpers.listing)).promise().done(function(){
						webflow_reset()
					});
				});

				// related
				if(!$.isEmptyObject(response.related.data)){
					$('.relacionados--h').after($.templates("#related").render(response.related.data,helpers.listing))
				} else {
					$('.relacionados').remove()
				}


			}

			$('.spinner').delay(500).fadeOut(500,function(){
				$('#map, .auto-loader').hide()
				$('.auto-container').fadeIn(500, function(){
					$('#map').show()
					map.invalidateSize()		
					webflow_reset()			
				})
			})

		}
	})
}

$(document).on('submit','#form-message', function(){

	disableForm(this)

	var that = this
	, token = get_token()

	if($.isEmptyObject(token)){

		$('.datosvendedir').fadeOut(200,function(){
			$('.button--register').click()
		});			

		return false
	}

	var message = $(this).find('textarea[name="message"]').val()
	, recipient_id = $(this).find('input[name="recipient_id"]').val()
	
	$.server({ 
		url: '/perfil/mensajes/enviar/' + $('.profile--name').data('code')
		, data: {
			recipient_id: recipient_id
			, message: message				
		}
		, success: function(response){
			if(response.data){
				$(that).parent().find('#form-message').fadeOut(200, function(){
					$(that).parent().find('.w-form-done').show()		
				})
			} else {
				$(that).parent().find('.w-form-fail').show()
			}
		}
		, then : function(){
			$(that).find('textarea[name="message"]').val('').focus()
			enableForm(that)
		}
	})

	return false
}) 

$(function(){
	showLoader()
	auto()
})

$(document).on("click",".pano-download", function(e){
	e.preventDefault()
	$.post( endpoint + '/descargar/' + $(this).attr('id'), function(response){
		if(response.status=='success'){
			location.href = response.message
			//$.get(response.message)
		}
	})
	return false;
})