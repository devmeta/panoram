var datos_helper = {
	isOther : function(id){
		var token = get_token();
		return token && token.id!=id;
	},
	format : function(number){
		return typeof Intl=='object' ? new Intl.NumberFormat().format(number) : number
	},
	isPrice : function(currency,price){
		return '<span class="currency">' + currency.iso_code + '</span> ' + (typeof Intl=='object' ? new Intl.NumberFormat().format(price) : price)
	},
	getBucketSize : function(size,url){
		return url.replace(bucket_url + '/',bucket_url + '/' + size);
	}
}

function perfil_datos_fetch(){
	$.server({
		url: '/perfil/datos', 
		success: function(response){
			if( ! $.isEmptyObject(response.data)){
				var perc = 20;

				$('#form-datos').find('#name').val((response.data.first_name?response.data.first_name:'') + (response.data.last_name?' ' +response.data.last_name:''));
				$('#form-datos').find('#email').val(response.data.email);

				if(response.data.picture){
					perc+= 20;
					$('.profilepicture--img img').attr('src',datos_helper.getBucketSize('80x80',response.data.picture));
					$('.dashboard__list-item:eq(0)').hide();
				}

				if(response.message_sent){
					perc+= 20;
					$('.dashboard__list-item:eq(1)').hide();
				}

				if(response.published){
					perc+= 20;
					$('.dashboard__list-item:eq(2)').hide();
				}

				if(response.warranty){
					perc+= 20;
					$('.dashboard__list-item:eq(3)').hide();
				}

				if( ! $.isEmptyObject(response.messages.data)){
					$('.notifications__container').html($.templates("#message").render(response.messages.data,datos_helper));
				} else {
					$('.profile_content__notifications').hide();
				}

				var bar_perc = perc-18;
				if(perc == 0) bar_perc = -17;
				if(perc == 100) bar_perc = 32;

				$('.dashboard__text').css({'margin-left':bar_perc+'%'});
				$('.graphic--complete').css({width:perc+'%'});
				$('.dashboard__text--triangle').next().text(perc+'%');
				$('.dashboard--amount').text(perc+'%');

			} else {
				$('.tabscontentvertical').find('.noinfo').show();
			}
		}
	});
}

$(document).on('submit','.form-message',function(){
	var message = $(this).find('#message').val();
	var code = window.location.hash.split('#').join('');	
	$.server({
		url: '/perfil/mensajes/enviar/' + code,
		data: {message: message},
		success: function(response){
			if(response.status == 'ok'){
				$('.' + code).last().before($.templates("#message-single").render(response.data));
				showTick();
			} else {
				$('.w-form-fail').show();
			}
		}
	});

	return false;
});


$(function(){

	$(".profilepicture").click(function(e) {
	    $("#fileupload").click();
	    e.preventDefault();
	});

	$('#fileupload').change(function (e) {
	    $("#imageuploadform").submit();
	    e.preventDefault();
	});

	$('#imageuploadform').submit(function(e) {

	    var formData = new FormData(this);

	    $('.profilepicture--link').text("Subiendo...");

	    $.ajax({
	        type:'post',
	        url: endpoint + '/perfil/datos/upload',
	        data:formData,
			beforeSend: function (xhr) { 
	    		xhr.setRequestHeader('Authorization', 'Bearer ' + get_jwt()); 
	    	},	        
	        xhr: function() {
                var myXhr = $.ajaxSettings.xhr();
                if(myXhr.upload){
                    myXhr.upload.addEventListener('progress',function(e){
					    if(e.lengthComputable){
					        var max = e.total;
					        var current = e.loaded;
					        var percentage = (current * 100)/max;

					        console.log("subiendo : " + parseInt(percentage));

					        if(percentage >= 100)
					        {
					        	//datos_showTick();
					        }
					    }
                    }, false);
                }
                return myXhr;
	        },
	        cache:false,
	        contentType: false,
	        processData: false,
	        success:function(response){
	        	if(response.error){
	        		console.log(response.error);
	        	}

	        	if(response.url){
	        		var token = get_token();
	        		token.picture = response.url;
					localStorage.setItem("token_data",JSON.stringify(token));

	        		$('.profilepicture--img img').attr('src',datos_helper.getBucketSize('80x80',response.url));
	        		$('.profilepicture--link').text("Cambiar foto");
	            	showTick();
	        	}
	        },
	        error: function(data){
	            console.log("Hubo un error al subir el archivo");
	        }
	    });

	    e.preventDefault();
	});

	$(document).on('click','.gotomessage',function(){
		location.href = '/perfil-usuario/mensajes#'  + $(this).data('code')
	})

	$('#form-datos').submit(function(){

		disableForm(this);

		$.server({
			url: '/perfil/datos/actualizar',
			data: $(this).serialize(),
			success: function(response){
	        	if(response.error){
	        		swal("Error",response.error,"error");
	        	}
				enableForm('#form-datos');
				showTick();
			}
		});

		return false;
	});

    perfil_datos_fetch();
})