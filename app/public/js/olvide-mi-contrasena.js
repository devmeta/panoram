$(function(){

	$('#wf-form-email-pass').submit(function(){

		disableForm(this) 
		var that = this

		$.ajax({
			method: "post"
			, url : endpoint + "/password-change-confirmation"
			, data : $(this).serialize()
			, success : function(res){
				if(res.status=='ok'){
					$(that).parent().find('.form__succes').fadeIn()
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