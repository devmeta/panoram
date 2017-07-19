var vehicle = undefined
, auto = function (){
	showLoader()
	, $.server({
		url: location.pathname,
		success: function(response){

			if(response.status == 'error'){
				$("body").addClass('momargin').load("/message", function(){
					parseMessage(response)
				})

				return false
			}

			vehicle = response.vehicle.data
			, token = get_token()
			hideLoader()

			if(!$.isEmptyObject(vehicle)){

				$('.car-info-info').html($.templates("#carinfo").render(vehicle))
				$('.container-profile__detail--financiamiento').html((vehicle.financing?"Sí":"No"))
				$('.bestinfo__profile').html($.templates("#profile").render(vehicle,helpers.listing))
				$('.datosvendedir').html($.templates("#datosvendedor").render(vehicle,helpers.users))

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

				$('.w-slider-mask').html($.templates("#slide").render(vehicle.photos,helpers.listing)).promise().done(function(){
					$('.bestinfo__slider--nav').html($.templates("#slide-nav").render(vehicle.photos,helpers.listing)).promise().done(function(){
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