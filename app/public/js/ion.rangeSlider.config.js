var rangeSliderconfig = {
	range_01 : {
		type: "double",
		keyboard: true,
		min : 1930,
		max : new Date().getFullYear(),
		onFinish: function(data){
			$('.sidebar__form').submit()
			sidebar()
		}
	},
	range_02 : {
		type: "double",
		keyboard: true,
		min : 40000,
		max : 500000,
		onFinish: function(data){
			$('.sidebar__form').submit()
			sidebar()
		}
	},
	range_03 : {
		type: "double",
		keyboard: true,
		min : 0,
		max : 200000,
		onFinish: function(data){
			$('.sidebar__form').submit()
			sidebar()
		}
	}
}