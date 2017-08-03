
$(function(){
	$.post(endpoint + '/quotes/1',function(resp){
		console.log(resp)
		var quote = resp[0]
		$('.quote').html('<p>' + quote.content +'</p><footer>' + quote.author + '</footer>')
	})
})

