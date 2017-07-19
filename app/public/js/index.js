$(function(){

	$(window).scroll(function() {
		clearTimeout($.data(this, 'scrollTimer'));
    	$.data(this, 'scrollTimer', setTimeout(function() {
      		var scrollpos = $(this).scrollTop()
			, position = $('.carlisting__caritem').last().offset() ? $('.carlisting__caritem').last().offset().top : 0
			, loadHeight = $(document).height( ) - $(window).height()
			, position = position - $(window).height() / 2

			if(position < scrollpos || scrollpos == loadHeight){
				infinite_scroll()
			}      		
    	}, 250))
	})

	$(document).on('click','.filter-button', function(){
		filter_reset()
	})

	for(var i in rangeSliderconfig){
		$("#" + i).ionRangeSlider({
			type: rangeSliderconfig[i].type,
			min: rangeSliderconfig[i].min,
			max: rangeSliderconfig[i].max,
			keyboard: rangeSliderconfig[i].keyboard,
			onFinish: rangeSliderconfig[i].onFinish
		})
	}

	fetch()
})