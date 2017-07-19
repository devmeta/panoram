$(function(){

	$('#wf-form-email-pass').submit(function(){

		disableForm(this) 
		var that = this

		$.ajax({
			method: "post"
			, url : endpoint + "/password-change"
			, data : $(this).serialize()
			, success : function(res){
				if(res.redirect_url && res.status=='ok'){
					$(that).parent().find('.form__succes').fadeIn()
					setTimeout(function(){
						location.href = res.redirect_url
					},3000)
				} else {
					$(that).parent().find('.form__error').fadeIn()
				}
			}
		})
		.then(function(){
			enableForm(that) 
		})
		
		return false
	})
})