$(function(){
	$('#wf-form-un-auto-para-vos').submit(function(){
		location.href = '/#_=sb&' + $(this).serialize();
		return false;
	});
})
