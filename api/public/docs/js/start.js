$(function(){

	var access_data = localStorage.getItem("access_data")

	if(access_data){
		var ad = $.parseJSON(access_data)
		$(".access-form").hide()
		$(".company").text(ad.company)
		$(".client-auth").removeClass("w-hidden")
	}

	$('.btn-reset').click(function(e){
		e.preventDefault()
		localStorage.clear()
		$(".access-form").find('input[name="client_id"]').val("")
		$(".access-form").find('input[name="key"]').val("")
		$(".access-form").removeClass("hide").show()
		$(".client-auth").addClass("w-hidden")
		window.scrollTo(0,0)
		return false
	})

	$('.access-form').submit(function(){
		var that = this
		, client_id = $(this).find('input[name="client_id"]').val()
		, key = $(this).find('input[name="key"]').val()
		, ad = {client_id:client_id,key:key}

		$(this).find('input[type="submit"]').prop("disabled",true).val("Aguarda...")

		$.ajax({
			method:'post',
			url: endpoint + '/client',
			beforeSend: function (xhr) { 
	    		xhr.setRequestHeader('Authorization', ad.client_id+':'+ad.key)
	    	},
			success: function(response){
				if(response.message=='Autentificación requerida'){
					$(that).parent().find('.w-form-fail').show()
				} else if(response.status=='deshabilitado'){
					$(that).parent().find('.w-form-fail').show()
				} else {
					ad.company = response.nombre
					localStorage.setItem("access_data",JSON.stringify(ad))
					$(that).find('input[type="submit"]').prop("disabled",false).val("Ingresar")
					$(that).parent().find('.w-form-done').show()					
					setTimeout(function(){
						location.href = '/api-docs.html'	
					},3000)					
				}
			},
			error : function(){
				$(that).parent().find('.w-form-fail').show()
				$(that).find('input[type="submit"]').prop("disabled",false).val("Ingresar")
			}
		})
		return false;
	})
})