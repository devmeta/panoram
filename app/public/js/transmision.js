var vehicle = undefined
, map
, marker 
, markers = []
, auto = function (){
	$.server({
		url: location.pathname,
		success: function(response){

			$('.auto-loader').delay(500).fadeOut(500,function(){
				$('#map').hide()
				$('.auto-container').fadeIn(500, function(){
					$('#map').show()
					map.invalidateSize()		
					webflow_reset()			
				})
			})

			if(response.status == 'error'){
				$("body").addClass('momargin').load("/message", function(){
					parseMessage(response)
				})

				return false
			}

			vehicle = response.vehicle.data
			, token = get_token()
			//hideLoader()

			if(!$.isEmptyObject(vehicle)){

				$('.car-info-info').html($.templates("#carinfo").render(vehicle))
				$('.container-profile__detail--financiamiento').html((vehicle.financing?"Sí":"No"))
				$('.datosvendedir').html($.templates("#datosvendedor").render(vehicle,helpers.users))
				$('.bestinfo__profile').html($.templates("#profile").render(vehicle,helpers.listing)).promise().done(function(){
					if(vehicle.lat && vehicle.lng){
						L.mapbox.accessToken = geo.mapbox.accessToken
						map = L.mapbox.map('map', 'mapbox.streets');
						marker = L.marker([vehicle.lat,vehicle.lng], {icon:geo.icon({displayName:"",className:'me',colorId:1})}).addTo(map);
			        	map.setView([vehicle.lat,vehicle.lng], 8)

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

				if($.inArray(vehicle.id,token.owned) > -1){
					$('#form-message').remove()
				}

				if(!$.isEmptyObject(vehicle.props.sonido)){
					$('.container-profile__detail--sonido').html($.templates("#props").render(vehicle.props.sonido))
				}

				if(!$.isEmptyObject(vehicle.props.exterior)){
					$('.container-profile__detail--exterior').html($.templates("#props").render(vehicle.props.exterior))
				}

				if(!$.isEmptyObject(vehicle.props.confort)){
					$('.container-profile__detail--confort').html($.templates("#props").render(vehicle.props.confort))
				}

				if(!$.isEmptyObject(vehicle.props.seguridad)){
					$('.container-profile__detail--seguridad').html($.templates("#props").render(vehicle.props.seguridad))
				}

				if(!$.isEmptyObject(vehicle.props.estado)){
					$('.container-profile__detail--estado').html($.templates("#props").render(vehicle.props.estado))
				}

				$('.w-slider-mask').html($.templates("#slide").render(vehicle.files,helpers.listing)).promise().done(function(){
					$('.bestinfo__slider--nav').html($.templates("#slide-nav").render(vehicle.files,helpers.listing)).promise().done(function(){
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

$(auto)