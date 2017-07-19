var showResponse = function(that,url,status,xhr) {
	$(that).next().removeClass('w-hidden')
	$(that).next().find('.request-url').text(url)
	$(that).next().find('.response-code').text(JSON.stringify(xhr.status, null, "\t"))
	$(that).next().find('.response-body').text(decodeURI(JSON.stringify(xhr.responseJSON, null, "\t")))
	$(that).next().find('.response-headers').text(xhr.getAllResponseHeaders())
}
, disableForm = function (form,value) {
	var submit = $(form).find('button[type="submit"]')
	if(submit){
		submit.prop("disabled",true).text("Enviando...")
	}
}
, enableForm = function (form,value) {
	var submit = $(form).find('button[type="submit"]')
	if(submit){
		submit.prop("disabled",false).text("Enviar")
	}
}

$(document).on('click','.m-toggle', function(){
	$(this).next().slideToggle()
})

$(document).on('submit','.method-form', function(){

	disableForm(this)

	var that = this
	, url = endpoint + $(this).attr('action')
	, authorization = $(this).find('input[name="header--Authorization"]').val()
	, method = $(this).attr('method')
	, dataArr = $(this).serializeArray()
	, beforeSend = null
	, data = {};

	for (var i in dataArr) {
		if( dataArr[i].name.indexOf('header--') == -1){
			data[dataArr[i].name] = dataArr[i].value
		}
	}

	if(data.length == 1 && data.id){
		data = [];
	}

	if(authorization){
		beforeSend = function (xhr) { 
	    	xhr.setRequestHeader('Authorization', authorization)
	    }
	}

	$.ajax({
		method : method
		, url : url
		, contentType : "application/json"
		//, dataType : "json"
		//, beforeSend : beforeSend
		, data : method!='get'? JSON.stringify(data) : data
	})
	.then(function(data, status, xhr) {
		showResponse(that,url,status,xhr)
		enableForm(that)
	})

	return false
})

$(function(){
	var access_data = localStorage.getItem("access_data")
	, ad = $.parseJSON(access_data)

	if(ad){
		$('.non-authorized-client').hide()
		$('.client-auth').text('Authorization: ' + ad.client_id+':'+ad.key)
		$('.header-links a').first().append(' para <strong>' + ad.company + '</strong>')
	}

	$('.wiki').html($.templates("#model").render(model,helper))
})