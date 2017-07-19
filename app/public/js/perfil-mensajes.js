var messages = undefined
, node = undefined
, message_helper = {
	isInterlocutor : function(index,id,name){
		var token = get_token()
		var data = token && token.id == id ? messages[index]['recipient'][name] : messages[index]['user'][name]
		var size = '80x80'
		if( ! data  && name == 'picture'){
			data = profile_dummy
		}
		return data.replace(bucket_url + '/',bucket_url + '/' + size)
	},
	isPrice : function(currency,price){
		return '<span class="currency">' + currency + '</span> ' + (typeof Intl=='object' ? new Intl.NumberFormat().format(price) : price)
	},	
	isOther : function(id){
		var token = get_token()
		return token && token.id != id
	},
	isRecipient : function(user_id,recipient_id){
		var token = get_token()
		return token && token.id == user_id ? recipient_id : user_id
	}
}
, perfil_mensajes_fetch = function(){
	var code = window.location.hash.split('#').join('')
	$.server({
		url: '/perfil/mensajes', 
		success: function(response){
			if( ! $.isEmptyObject(response.data)){
				messages = response.data
				$('.tabsmenuvertical').html($.templates("#message-tab").render(response.data,message_helper))
				for(var i in response.data){
					$('.tabscontentvertical').append($.templates("#message-content").render(response.data[i],message_helper))
				}
				$('.tabslinkvertical' + node).addClass('w--current')
				$('.w-tab-pane' + node).addClass('w--tab-active')
				webflow_reset();
			} else {
				$('.tabscontentvertical').find('.noinfo').show()
			}
		}
	})
}

$(document).on('submit','#perfil-form-message',function(){
	var that = this
	, message = $(this).find('textarea[name="mensaje"]').val()
	, recipient_id = $(this).find('input[name="recipient_id"]').val()
	, tab = $('.tabslinkvertical.w--current').data('w-tab')
	, codeparts = tab.split("-")
	, code = codeparts[0]

	disableForm(this);
	$.server({ 
		url: '/perfil/mensajes/enviar/' + code
		, data: {
			recipient_id: recipient_id,
			message: message
		}
		, success: function(response){
			if(response.data){
				$('.w-tab-pane.' + tab).children().last().prev().after($.templates("#message").render(response.data, message_helper)).promise().done(function(){
					$(this).next().addClass("flash")
					$(that).find('textarea[name="mensaje"]').val('')
				})
			} else {
				$('.w-form-fail').show()
			}
			
		}
		, then : function(){
			enableForm(that)
		}
	})

	return false;
});

$(function(){
	node = ':first'
	if(location.hash)	node = '.'+location.hash.split('#').join('')
	perfil_mensajes_fetch()
})