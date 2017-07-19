function perfil_fetch(){
    $.ajax({
        type:'post',
        url: endpoint + '/perfil-publico' + location.pathname.split('%').join(''),
		beforeSend: function (xhr) { 
    		xhr.setRequestHeader('Authorization', 'Bearer ' + get_jwt()); 
    	},	 
    	cache:false,
        contentType: false,
        processData: false,
        success:function(response){
        	if(response.status == 'redirect'){
            	location.href = response.redirect_url;
        	} else {
                $('.perfil').html($.templates("#perfil").render(response,helpers.listing));
        	}
        },
        error: function(data){
        	$('.button--register').click();
        }
    });
}

$(function(){
    perfil_fetch();
})