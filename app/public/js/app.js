// Safari, in Private Browsing Mode, looks like it supports localStorage but all calls to setItem
// throw QuotaExceededError. We're going to detect this and just silently drop any calls to setItem
// to avoid the entire page breaking, without having to do a check at each usage of Storage.
if (typeof localStorage === 'object') {
    try {
        localStorage.setItem('localStorage', 1)
        localStorage.removeItem('localStorage')
    } catch (e) {
        Storage.prototype._setItem = Storage.prototype.setItem
        Storage.prototype.setItem = function () {}
        swal("Error", 'Tu navegador no soporta alojamiento local. En Safari, la causa más común de este error es usar "Modo Navegación Privada". Algunas preferencias no podrán ser guardadas y la aplicación no funcionará correctamente.','error')
    }
}

var scroll_count = 0
, endpoints = undefined
, files = []
, ads = []
, ads_inserted = 0
, settings = {
	currency: '<span class="currency">ARS</span>'
}
, fix_date_zeros = function (date){
	return date < 10 ? "0" + date : date 
}
, get_timestamp = function (){
	var currentdate = new Date()
	, timestamp = currentdate.getFullYear() + "/"
        + fix_date_zeros(currentdate.getMonth()+1)  + "/" 
        + fix_date_zeros(currentdate.getDate()) + " "  
        + fix_date_zeros(currentdate.getHours()) + ":"  
        + fix_date_zeros(currentdate.getMinutes()) + ":" 
        + fix_date_zeros(currentdate.getSeconds())
	return timestamp
}
, refresh_token = function(){
	var token = get_token()
	$.server({ 
		url: '/refresh-token',
		success: function (resp){
			if(resp){
				localStorage.setItem("token_data",JSON.stringify(resp.data))
				setTimeout(function (){
					$('body').trigger('token_updated')	
				},200)
			}
		},
		error : function (xhr, status, error){
			switch(xhr.status){
				case 401:
				var resp = $.parseJSON(xhr.responseText)
				, _endpoints = $.parseJSON(resp.message)
				if(_endpoints){
					endpoints = _endpoints
				}
				logout()
			}
		}
	})
}
, getRandomInt = function (min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
, getBucketSize = function (size,url){
	if(url == undefined || url.indexOf('/')==-1) return
	var parts = url.split('/')
	if(url.indexOf(bucket_url) > -1){
		parts[parts.length-1] = size + parts[parts.length-1]
	}
	return parts.join('/')
}		
, get_browser = function () {
	var ua=navigator.userAgent,tem,M=ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || []; 
	if(/trident/i.test(M[1])){
	    tem=/\brv[ :]+(\d+)/g.exec(ua) || []; 
	    return {name:'IE',version:(tem[1]||'')};
	    }   
	if(M[1]==='Chrome'){
	    tem=ua.match(/\bOPR|Edge\/(\d+)/)
	    if(tem!=null)   {return {name:'Opera', version:tem[1]};}
	    }   
	M=M[2]? [M[1], M[2]]: [navigator.appName, navigator.appVersion, '-?'];
	if((tem=ua.match(/version\/(\d+)/i))!=null) {M.splice(1,1,tem[1]);}
	return {
	  name: M[0],
	  version: M[1]
	};
}
, helpers = {
	translations : {
		brand_id : "Marca"
		, model_id : "Modelo"
		, fuel_id : "Combustible"
		, gear_id : "Transmisión"
		, color_id : "Color"
		, region_id : "Ubicación"
	}
	, forms : {
		rules : {
			precio : {
				when : ";500000"
				, then : ";500000+"
			}
			, km : {
				when : ";200000"
				, then : ";200000+"
			}			
		}
		, check_rules : function (that){

			var viewArr = $(that).serializeArray()
			, view = ""

			for (var i in viewArr){
				if(viewArr[i]){
					var rule = helpers.forms.rules[viewArr[i].name]
					if(rule){
						if(viewArr[i].value.indexOf(rule.when)){
							view+= viewArr[i].name+'='+viewArr[i].value.replace(rule.when,rule.then)
						}
					}
					else{
						view+= viewArr[i].name+'='+viewArr[i].value
					}
					view+= "&"
				}
			}

			return view
		}
	}
	, selects : {
		isLabel : function (i){
			return helpers.translations[i]
		}
		, isLimited : function (index,limit){
			return limit && limit <= index
		}
		, isChecked : function (name, value){
			return helpers.getParameterByName(name)==value
		}
	}
	, users : {
		isValidated : function (){
			var token = get_token()
			return token && token.validated
		}
		, isLogged : function (){
			var token = get_token()
			return token.id?1:0
		}
		, getEncEmail : function (){
			var token = get_token()
			return token.email_encoded
		}
		, getEP : function (){
			return endpoint
		}
		, getPath : function (){
			return location.pathname
		}
	}
	, token : {
		is_token : function (token){
			var token = get_token()
			return !$.isEmptyObject(token)
		}
		, is_dummy : function (){
			return profile_dummy
		}
		, getBucketSize : getBucketSize 
	}
	, listing : {
		format : function (number){
			return typeof Intl=='object' ? new Intl.NumberFormat().format(number) : number
		}
		, isPrice : function (currency,price){
			return '<span class="currency">' + price + '</span> '
		}
		, getBucketSize : getBucketSize
		, isFav : function (id){
			var token = get_token()
			var preferences = token.preferences || {}
			return preferences.fav ? $.inArray(id,preferences.fav) > -1 : 0
		}
		, isAlert : function (id){
			var token = get_token()
			var preferences = token.preferences || {}
			return preferences.alert ? $.inArray(id,preferences.alert) > -1 : 0
		}
		, isOwned : function (id){
			var token = get_token()
			return token.owned ? $.inArray(id,token.owned) > -1 : 0
		}
		, isDummy : function (){
			return profile_dummy
		}
		, isDraft : function (price,kms,tel,mt_year,region_id,city_id){
			return (!price||!kms||!tel||tel==''||!mt_year||mt_year<=1950||!region_id||!city_id)
		}
		, isLogged : function () {
			return get_token()
		}
		, isGuest : function () {
			return !get_token()
		}
	}
	, pagination : {
		currentPage : function (){
			return helpers.getParameterByName('p')||1
		}
	}
	, setParameterByName : function (name,value,url){
        if(!url) url = window.location.hash.split('#').join('')
        if(value == null) value = ''
        var pattern = new RegExp('\\b('+name+'=).*?(&|$)')
        if(url.search(pattern)>=0){
            return url.replace(pattern,'$1' + value + '$2')
        }
        return url + '&' + name + '=' + value 
    }
    , getParameterByName : function (name,url) {
        if(!url) url = window.location.hash
        var name = name.replace(/[\[\]]/g, "\\$&")
        , regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)")
        , results = regex.exec(url)
        if(!results) return ''
        if(!results[2]) return ''
        return decodeURI(results[2].replace(/\+/g, " "))
    }  	
	, getParameterByNameGroup : function (a) {
		var b = helpers.getParameterByName(a)
		, c = b ? b.split('-') : []
		return c
	}    
}
, get_token = function () {
	return $.parseJSON(localStorage.getItem("token_data")) || {}
}
, get_jwt = function () {
	var token = get_token()
	return token.token||""
}
, check_token = function (token) {
	if(!$.isEmptyObject(token)){
		var decoded = parseJwt(token.token)
		if(decoded.iat > decoded.exp){
			localStorage.clear()
		}
	}
}
, add_preference = function (type,id){
	var token = get_token()
	if($.isEmptyObject(token)) return
	var preferences = token.preferences || {}
	if(preferences[type] && ! preferences[type][id]){
		preferences[type].push(id)
	}
	localStorage.setItem("token_data",JSON.stringify(token))
	return preferences
}
, remove_preference = function (type,id){
	var token = get_token()
	if($.isEmptyObject(token)) return
	var preferences = token.preferences[type] || {}
	, save = []

	if(preferences.length){
		for(var i in preferences){
			if(preferences[i]!=id) save.push(preferences[i])
		}
	}

	token.preferences[type] = save
	localStorage.setItem("token_data",JSON.stringify(token))
	return save
}
, parseJwt = function (token) {
	if(!token) return null
    var base64Url = token.split('.')[1];
    var base64 = base64Url.replace('-', '+').replace('_', '/');
    return JSON.parse(window.atob(base64));
}
, logout = function (){
	localStorage.clear()
	$('.button--profile').hide()
	$('.button--login').removeClass('w-hidden-important').show()
}
, readURL = function (input, selector) {
    if (input.files && input.files[0]) {
        var reader = new FileReader()
        reader.onload = function (e) {
        	$(selector).css({
        		'background-image' : 'url(' + e.target.result + ')',
        		'background-size' : 'cover'
        	})
		   	var exif = EXIF.readFromBinaryFile(new base64ToArrayBuffer(e.target.result))
		   	fixExifOrientation(exif.Orientation,selector)        	
        }

        reader.readAsDataURL(input.files[0])
    }
}
, fixExifOrientation = function (int,obj) {
    switch(parseInt(int)) {
        case 2:
        	$(obj).parent().addClass('loaded'); 
            $(obj).addClass('flip'); 
            break;
        case 3:
        	$(obj).parent().addClass('loaded'); 
            $(obj).addClass('rotate-180'); break;
        case 4:
            $(obj).addClass('flip-and-rotate-180'); break;
        case 5:
            $(obj).addClass('flip-and-rotate-270'); break;
        case 6:
        	$(obj).parent().addClass('loaded'); 
            $(obj).addClass('rotate-90'); break;
        case 7:
            $(obj).addClass('flip-and-rotate-90'); break;
        case 8:
            $(obj).addClass('rotate-270'); break;
    }
}
, capitalizeFirstLetter = function (string) {
    return string.charAt(0).toUpperCase() + string.slice(1)
}
, disableForm = function (form,value) {
	var submit = $(form).find('input[type="submit"]')
	if(submit){
		submit.removeClass('active').addClass('inactive').attr('data-value',submit.val())
		submit.prop("disabled",true).val((value?value:"Enviando..."))
	}
}
, enableForm = function (form,value) {
	var submit = $(form).find('input[type="submit"]')
	if(submit){
		submit.removeClass('inactive').addClass('active').prop("disabled",false).val(submit.data('value'))
	}
}
, showLoader = function () {
	$('#loader').fadeIn(250)
}
, hideLoader = function () {
	$('#loader').fadeOut(500, function (){
		$('.anim-asset').hide()
	})	
}
, showTick = function () {
	$(".tick-asset").css({'display':'block'})
	$(".trigger").addClass("drawn")
	setTimeout(function (){
		$(".trigger").removeClass("drawn")
	},1000)
}
, parseMessage = function (res){
	$("body").addClass('momargin').load("/message", function (){
		$('.utility-page-content h2').html(res.status+': '+res.title)
		$('.utility-page-content div').html(res.message)
	})
}
, filter_reset = function () {
	$('#condition-1, #condition-2').prop('checked', false)
	for(var i in rangeSliderconfig){
		var range = $("#"+i).data("ionRangeSlider")
		range.update({from:rangeSliderconfig[i].min,to:rangeSliderconfig[i].max})
	}
}
, webflow_reset = function () {
	if(typeof Webflow == 'undefined') return
	Webflow.require("lightbox").ready()
	Webflow.require('ix').init([
	  {"slug":"toggle","name":"Toggle","value":{"style":{},"triggers":[{"type":"click","selector":".buttontoggle:not(.disabled)","descend":true,"preserve3d":true,"stepsA":[{"transition":"transform 500ms ease-in-out 0","x":"51px","y":"0px","z":"0px"}],"stepsB":[{"transition":"transform 500ms ease-in-out 0","x":"0px","y":"0px","z":"0px"}]},{"type":"click","selector":".buttontext-off:not(.disabled)","descend":true,"stepsA":[{"wait":"125ms"},{"opacity":0,"transition":"opacity 175ms ease 0"}],"stepsB":[{"wait":"100ms"},{"opacity":1,"transition":"opacity 175ms ease 0"}]},{"type":"click","selector":".togglebuttongreen:not(.disabled)","descend":true,"stepsA":[{"opacity":1,"transition":"opacity 500ms ease 0"}],"stepsB":[{"opacity":0,"transition":"opacity 500ms ease 0"}]}]}},
	  {"slug":"favorite","name":"favorite","value":{"style":{},"triggers":[{"type":"click","selector":".favorite-active","descend":true,"stepsA":[{"display":"block"}],"stepsB":[{"display":"none"}]},{"type":"click","selector":".favorite-default","descend":true,"stepsA":[{"display":"none"}],"stepsB":[{"display":"block"}]}]}},
	  {"slug":"register","name":"register","value":{"style":{},"triggers":[{"type":"click","selector":".register","stepsA":[{"display":"block"},{"wait":"200ms"},{"opacity":1,"transition":"opacity 500ms ease 0"}],"stepsB":[]}]}},
	  {"slug":"close","name":"close","value":{"style":{},"triggers":[{"type":"click","selector":".modalwrapper","stepsA":[{"opacity":0,"transition":"opacity 100ms ease 0"},{"wait":"100ms"},{"display":"none"}],"stepsB":[]}]}},
	  {"slug":"gotoregister","name":"GoToRegister","value":{"style":{},"triggers":[{"type":"click","selector":".login","stepsA":[{"display":"none"},{"opacity":0}],"stepsB":[]},{"type":"click","selector":".register","stepsA":[{"display":"block"},{"opacity":1,"transition":"opacity 200 ease 0"}],"stepsB":[]}]}},
	  {"slug":"infotopublish","name":"InfoToPublish","value":{"style":{},"triggers":[{"type":"click","selector":".infoparapublicar","stepsA":[{"display":"block"},{"opacity":1,"transition":"opacity 200 ease 0"}],"stepsB":[]}]}},
	  {"slug":"gotologin","name":"GoToLogin","value":{"style":{},"triggers":[{"type":"click","selector":".register","stepsA":[{"display":"none"},{"opacity":0}],"stepsB":[]},{"type":"click","selector":".login","stepsA":[{"display":"block"},{"opacity":1}],"stepsB":[]}]}},
	  {"slug":"gotodatos","name":"GoToDatos","value":{"style":{},"triggers":[{"type":"click","selector":".datosvendedir","stepsA":[{"display":"block"},{"opacity":1}],"stepsB":[]}]}},
	  {"slug":"gotocontact","name":"GoToContact","value":{"style":{},"triggers":[{"type":"click","selector":".contact","stepsA":[{"display":"block"},{"wait":"200ms"},{"opacity":1,"transition":"opacity 500ms ease 0"}],"stepsB":[]}]}},
	  {"slug":"used","name":"used","value":{"style":{},"triggers":[{"type":"click","stepsA":[{"display":"none"}],"stepsB":[]},{"type":"click","selector":".togglenew","stepsA":[{"display":"flex"}],"stepsB":[]}]}},
	  {"slug":"new","name":"new","value":{"style":{},"triggers":[{"type":"click","stepsA":[{"display":"none"}],"stepsB":[]},{"type":"click","selector":".toggleused","stepsA":[{"display":"flex"}],"stepsB":[]}]}},
	  //{"slug":"garantia","name":"garantia","value":{"style":{},"triggers":[{"type":"click","selector":".aplicoagarantia","stepsA":[{"display":"flex"}],"stepsB":[]},{"type":"click","selector":".aplicagarantia","stepsA":[{"display":"none"}],"stepsB":[]}]}}
	])					
}
, check_controls = function () {
	var parts = []
	, reduced = []
	, pairs = location.hash.split('&')
	, range_01 = $("#range_01").data("ionRangeSlider")
	, range_02 = $("#range_02").data("ionRangeSlider")
	, range_03 = $("#range_03").data("ionRangeSlider")
	
	if(param = decodeURIComponent(helpers.getParameterByName('ano'))){
		var pair = param.split(';')
		parts.push(pair[0] + ' - ' + pair[1])
		if(range_01){
			range_01.update({
			    from: pair[0],
			    to: pair[1]
			})
		}
	}

	if(search = decodeURIComponent(helpers.getParameterByName('search'))){
		$('#search').val(search)
		parts.push("“"+search+"”")
	}

	if(desde = decodeURIComponent(helpers.getParameterByName('Desde'))){
		$('#Desde').val(desde)
		parts.push(settings.currency+desde)
		if(range_02){
			range_02.update({
			    from: desde
			})		
		}
	}

	if(hasta = decodeURIComponent(helpers.getParameterByName('Hasta'))){
		var rep = hasta.replace(' ','+')
		, rep2 = hasta.replace(' ','')
		$('#Hasta').val(rep)
		parts.push(settings.currency+rep)
		if(range_02){
			range_02.update({
			    to: rep2
			})
		}
	}

	if(param = decodeURIComponent(helpers.getParameterByName('precio'))){
		if(param.indexOf(';')>-1){
			var pair = param.split(';')
			, rep = pair[1].replace(' ','+') 
			, rep2 = pair[1].replace(' ','') 
			parts.push(pair[0]+settings.currency+ ' - ' +rep+settings.currency)
			if(range_02){
				range_02.update({
				    from: pair[0],
				    to:  rep2
				})
			}
		} else {
			parts.push(param+settings.currency)
		}
	}

	if(param = decodeURIComponent(helpers.getParameterByName('km'))){
		var pair = param.split(';')
		if(pair[1]){
			parts.push(pair[0]+'km' + (pair[1] ? ' - ' + pair[1].replace(' ','+') +'km' : ''))
			if(range_03){
				range_03.update({
				    from: pair[0],
				    to: pair[1]
				})
			}
		} else {
			parts.push(pair[0]+'km')
		}
	}

	if(param = decodeURIComponent(helpers.getParameterByName('filtro'))){
		parts.push(capitalizeFirstLetter(param).replace('-',' '))
	}	

	for(var i in pairs){
		if(pairs[i]){

			var parts1 = pairs[i].split('=')
			, parts2 = parts1[0].split('-')

			if(parts1[1]!=''){
				switch(parts2[0]){

					case 'doors':
					$('#doors-'+parts2[1]).prop('checked',true)
					parts.push(parts2[1] + ' puertas')
					break

					case 'brand_id':
					$('#brand_id-'+parts2[1]).prop('checked',true)
					parts.push($('*[for="brand_id-' + parts2[1] + '"]').text())
					break

					case 'model_id':
					$('#model_id-'+parts2[1]).prop('checked',true)
					parts.push($('*[for="model_id-' + parts2[1] + '"]').text())
					break

					case 'fuel_id':
					$('#fuel_id-'+parts2[1]).prop('checked',true)
					parts.push($('*[for="fuel_id-' + parts2[1] + '"]').text())
					break

					case 'gear_id':
					$('#gear_id-'+parts2[1]).prop('checked',true)
					parts.push($('*[for="gear_id-' + parts2[1] + '"]').text())
					break

					case 'color_id':
					$('#color_id').val(parts1[1])
					parts.push($('#color_id option:selected').text())
					break

					case 'region_id':
					$('#region_id').val(parts1[1])
					parts.push($('#region_id option:selected').text())
					break

					case 'condition':
					$('#'+parts2.join('-')).prop('checked',true)
					break					
				}
			}
		}
	}

	if(sort = decodeURIComponent(helpers.getParameterByName('sort'))){
		var that = $('* [id="' + sort + '"]').first()

		$('.w-dropdown-list, .w-dropdown-toggle').removeClass('w--open')
		that.addClass('w--open')

		if(that.hasClass('sorting__dropdown--p')) {} else 
			$('.sorting__dropdown--p').text(that.text())
	
		parts.push(that.text())
	} else {
		parts.push($('.sorting__dropdown--p').text())
	}

	for(var i in parts){
		if(parts[i].length){
			reduced.push(parts[i])
		}
	}

	return reduced.join(' > ')
}
, get_rowsize = function(){
	var size = 4
	, width = $(window).width()
	if(width < 991 && width > 424) size = 2
	if(width < 424) size = 1
	return size
}
// take = rowsize * 4
, fetch = function (pos) {
	
	var rowsize = get_rowsize()
	, take = rowsize * 4
	, i = helpers.getParameterByNameGroup('i')

	if(pos==undefined) pos = 0
	showLoader()
	
	if(pos==0) {
		scroll_count = 0
		ads_inserted = 0
		sidebar()
	} 

	$('.noinfo').hide()
	$('.carlisting-indicator__filter--n').html(check_controls())

	if(location.hash && ! i.length){
		$('.w-slider').hide()
	}

	$.server({
		url: '/transmisiones/buscar',
		data: location.hash + '&pos=' + pos + '&take=' + take,
		success: function (response){
			hideLoader()
			scroll_count = response.pagination.count
			if(pos==0){
				if(!$.isEmptyObject(response.listing.data)){
					$('.carlisting__cont__cars').hide().html($.templates("#listing").render(response.listing.data,helpers.listing)).fadeIn(600, function (){
						window.scrollTo(0,0)
						if(ads.length){
							//insert_next_ad(1)
						}
					})
				}
			} else {
				$('.carlisting__cont__cars').append($.templates("#listing").render(response.listing.data,helpers.listing)).promise().done(function (){
					//insert_next_ad(getRandomInt(0,take / rowsize))
				})
			}

			$('.carlisting-indicator__amount--n').text(response.pagination.position + ' de ' + response.pagination.count + ' resultado de:')

			if($.isEmptyObject(response.listing.data)){
				$('.carlisting__cont__cars').html('<div class="noinfo">No hay resultados</div>').promise().done(function (){
					$(this).find('.noinfo').fadeIn()
				})
			}
		}
	})
}
, insert_next_ad = function(pos){
	if(pos==undefined) pos = 'before'
	var ad = ads[ads_inserted]

	if(!ad) return null

	var template = $.templates('#embedded_ad').render(ad)
	, rowsize = get_rowsize()
	, take = rowsize * 4
	, done = function(){
		ads_inserted++
	}

	if(pos=='before'){
		$('.carlisting__caritem').first().before(template).promise().done(done)
	} else if(pos=='after') {
		$('.carlisting__caritem').last().after(template).promise().done(done)
	} else {
		var index = $('.carlisting__caritem').length - take + rowsize - 1 + ((pos-1) * rowsize)
		$($('.carlisting__caritem').get(index)).after(template).promise().done(done)
	}
}
, sidebar = function () {
	$.ajax({
		type: "post",
		url: endpoint + '/transmisiones/sidebar',
		data: location.hash,
		success: function (response){
			
			$('.filter--puertas').html($.templates("#doors").render(response.doors,helpers.selects))
			
			$('.filter__item--checks').html($.templates("#checks").render(response.checks,helpers.selects)).promise().done(function (){
				$('.filter__item--selects').html($.templates("#selects").render(response.selects,helpers.selects)).promise().done(function (){
					if($('.irs.js-irs-1 .irs-to').text()=='500 000'){
						setTimeout(function (){
							$('.irs.js-irs-1 .irs-to').text('500 000+')	
						},250)						
					}
					if($('.irs.js-irs-2 .irs-to').text()=='200 000'){
						setTimeout(function (){
							$('.irs.js-irs-2 .irs-to').text('200 000+')	
						},250)						
					}
					if(location.hash&&location.hash.length > 20){
						if($('.filter__items.brand_id').find('input:checkbox:checked').length){
							$('.filter__items.brand_id').find('input:checkbox').each(function (){
								$(this).parent().removeClass('w-hidden-important')
							})
							$('.filter__items.brand_id').find('input:checkbox:not(:checked)').each(function (){
								$(this).parent().addClass('w-hidden-important')
							})
						}
						if($('.filter__items.model_id').find('input:checkbox:checked').length){
							$('.filter__items.model_id').find('input:checkbox').each(function (){
								$(this).parent().removeClass('w-hidden-important')
							})
							$('.filter__items.model_id').find('input:checkbox:not(:checked)').each(function (){
								$(this).parent().addClass('w-hidden-important')
							})
						}
					}
				})
			})

			$('.destaque--h').after($.templates("#featured").render(response.featured.data,helpers.listing))

			if( ! $.isEmptyObject(response.slides.data)){
				$('.w-slider-mask').html($.templates("#slide").render(response.slides.data)).promise().done(function (){
					webflow_reset()
				})
			} else {
				$('.w-slider').remove()
			}
			if( ! $.isEmptyObject(response.banners.data)){
				$('.filter__banners--top').html($.templates("#banner").render(response.banners.data.slice(0,1)))
				$('.filter__banners--bottom').html($.templates("#banner").render(response.banners.data.slice(1,response.banners.data.length)))
			}
		}
	})
}
, infinite_scroll = function () {
	var position = $('.carlisting__cont__cars').children().length
	if(position < scroll_count) fetch(position)
}

$(function (){

	var token = get_token()
	, i = helpers.getParameterByNameGroup('i')

	check_token(token)
	webflow_reset()

	// alerts
	if(i.length){
		setTimeout(function (){
			for(var n in i){
				$('#'+i[n]).removeClass('w-hidden')
			}
			location.hash=''
		},200)
	}

	$('.container--profile').html($.templates("#profileactions").render(token,helpers.token))
	$('.session--links').html($.templates("#sessionlinks").render(token,helpers.token))

	// dom actions

	$(document).on('submit','#wf-form-login',function (e){
		e.preventDefault() 
		disableForm(this) 
		$.post( endpoint + '/ingresar',$(this).serialize(),function (response){
			$('#wf-form-login').parent().find('.w-form-fail, .w-form-done').hide()
			if(response.status=='ok'){
				$('#wf-form-login').parent().find('.w-form-done').show()
				localStorage.setItem("token_data", JSON.stringify(response.data))
				setTimeout(function (){
					location.href=location.href+location.hash
					location.reload()
				},1000)
			} else {
				$('#wf-form-login').parent().find('.w-form-fail').show()
			}
			enableForm('#wf-form-login')
		})
		return false
	})

	$(document).on('submit','#wf-form-register',function (e){
		e.preventDefault() 
		if($(this).find('#password').val()!==$(this).find('#confirm-password').val()){
			swal("Advertencia!", "Las contraseñas no cinciden","warning")
			return false
		}
		disableForm(this) 
		$.post( endpoint + '/registro',$(this).serialize(),function (response){
			if(response.status=='ok'){
				$('#wf-form-register').hide()
				$('#wf-form-register').parent().parent().find('.modal--p,.modalwrapper__container--btns').hide()
				$('#wf-form-register').parent().find('.w-form-fail').hide()
				$('#wf-form-register').parent().find('.w-form-done').show()
				localStorage.setItem("token_data", JSON.stringify(response.data))
			} else {
				$('#wf-form-register').parent().find('.w-form-fail div').html(response.message)
				$('#wf-form-register').parent().find('.w-form-fail').show()
			}
			enableForm('#wf-form-register')
		})
		return false
	})

	$(document).on('click','.filter--moreoptions',function (){
		$(this).hide().parent().find('.filter__items div').removeClass("w-hidden-important").show()
	})

	$(document).on('click','.pagination__list--item',function (){
		location.hash = helpers.setParameterByName('p',$(this).data('p'))
	})

	$(document).on('click','.google.social-btn',function (e){
		e.preventDefault()
		var url = endpoints.google
		location.href = url
		localStorage.setItem("redirect",location.pathname+location.hash)
		return false
	})

	$(document).on('click','.facebook.social-btn',function (e){
		e.preventDefault()
		var url = endpoints.facebook
		location.href = url
		localStorage.setItem("redirect",location.pathname+location.hash)
		return false
	})

	$(document).on('click','.button--logout',function (){
		logout()
		location.href = '/#&i=session_ended'
	})

	$(document).on('click','.button--publish',function (){
		var token = get_token()

		if(!$.isEmptyObject(token)){
			if(token.first_name){
				$.server({
					url : '/transmitir/inicio',
					success: function (response){
						window.location.href = '/transmitir/' + response.code
					}
				})
			} else {
				if(token.picture){
					$('#wf-form-publish').find('.modal__profilepicture img').attr('src',token.picture)
					$('#wf-form-publish').find('.modal__uploadimage').hide()
				}

				$('#wf-form-publish').find('#name').val((token.first_name?token.first_name:'') + (token.last_name?' ' +token.last_name:'')) 
				$('.button--infotopublish').click()
			}
		} else {
			// mobile
			setTimeout(function (){ $('.button--register').click()}, 100);
			$('.button--register').trigger("mousedown")
		}
	})

	$(document).on('click','.registro--email--reenviar',function (){
		$.server({
			url: '/perfil/reenviar-bienvenida',
			data : {readable: $('#wf-form-register').find('#password').val()},
			success: function (response){
				hideLoader()
				$('.registro--email--reenviar').after("<div><p>" + response.message + "</p></div>")
			}
		})
	})

	$(document).on('click','.caritem__data--descr__fav, .caritem__data--descr__alert',function (e){

		var token = get_token()

		if($.isEmptyObject(token)){
			if($('.button--register').length){
				setTimeout(function (){ $('.button--register').click()}, 100)
				$('.button--register').trigger("mousedown")
			}

			return false
		}

		var type = $(this).data('type')
		, id = $(this).data('id')

		if(type=='fav'){
			var state = $(this).attr('src')=='/images/fav-active.svg'?0:1
			$(this).attr('src','/images/fav' + (state?'-active':'orite') + '.svg')
		} else if(type=='alert'){
			var state = $(this).find('.togglebuttongreen').css('opacity')==1?0:1
		}

		$.server({
			url: '/perfil/' + type + '/' + id,
			data : {state: state}, 
			success: function (resp){
				if(resp.status=="success"){
					if(state) add_preference(type,id)
					else remove_preference(type,id)
					showTick()
				}
			}
		})	
	
		e.preventDefault()
	})

	$(document).on('click','.sidebar__form input',function (){
		$('.sidebar__form').submit()
	})

	$(document).on('change','.sidebar__form select',function (){
		$('.sidebar__form').submit()
	})

    $(document).on('click', '.data-url', function (){
    	var a=''
    	if(!$(this).data('url')) return 
    	a+='<a id="link" href="' + $(this).data('url') + '"'
    	if($(this).hasClass('data-url-blank')) a+=' target="blank"'
    	a+='></a>'
    	$('body').append(a)
    	$('#link')[0].click()
    	return false
    })

	// static actions
	$('.close__alert--link').click(function (){
		$('.w-alert').each(function (){
			$(this).fadeOut(200)
		})
	})

	$('.sorting__dropdown--link').click(function (){
		var prev = $('.sorting__dropdown--p').text()
		, text  = $(this).text()
		, sort = $(this).attr('id')
		, sort2 = $('.sorting__dropdown--p').attr('id')

		$('.w-dropdown-list, .w-dropdown-toggle').removeClass('w--open')
		$('.sorting__dropdown--p')
			.attr('id',sort)
			.text(text)
			
		$(this)
			.attr('id',sort2)
			.text(prev)

		location.hash = helpers.setParameterByName('sort',sort)
	})

	$('.buscador__form').submit(function (){
		location.href = '/#_=nb&'+helpers.forms.check_rules(this) + 'sort=' + helpers.getParameterByName('sort')
		return false
	})	

	$('.sidebar__form').submit(function (){
		location.href = '/#_=sb&'+helpers.forms.check_rules(this) + 'sort=' + helpers.getParameterByName('sort')
		return false
	})

	$("#publishfileupload").change(function (){
	    readURL(this,'.modal__profilepicture')
	})

	$('.modal__uploadimage, .modal__profilepicture').click(function (e){
	    $("#publishfileupload").click() 
	    e.preventDefault()
	})

	$('#wf-form-publish').submit(function (e){
		disableForm(this) 
		var token = get_token() 
		, formData = new FormData(this) 

	    $.ajax({
	        type:'post',
	        url: endpoint + '/perfil/datos/completar',
	        data:formData,
			beforeSend: function (xhr) { 
	    		xhr.setRequestHeader('Authorization', 'Bearer ' + get_jwt()) 
	    	},	 
	    	cache:false,
	        contentType: false,
	        processData: false,
	        success:function (response){
	        	if(response.first_name){
	        		var token = get_token() 
	        		token.first_name = response.first_name
	        		if(response.picture)
	        			token.picture = response.picture
	        		if(response.last_name)
	        			token.last_name = response.last_name
					localStorage.setItem("token_data",JSON.stringify(token))
					$.server({
						url : '/transmitir/inicio',
						success: function (response){
							window.location.href = '/transmitir/' + response.code
						}
					})
	        	}
	        	enableForm('#wf-form-publish')
	        },
	        error: function (data){
	        	enableForm('#wf-form-publish')
	            console.log("Hubo un error al subir el archivo")
	        }
	    })
		return false
	})

	// close modal on negative space not covered in wf
	
	$('.modalwrapper').click(function (e){
		if($(e.target).parents('.modalwrapper__container').length) return
		$('.modalwrapper').fadeOut(300)
	})

	// footer actions

	$('.footer__link--a').click(function (){
		$('#wf-form-contact').find('#area').val($(this).data('option'))
		$('#wf-form-contact').parent().find('.form__succes').hide()
		setTimeout(function (){
			$('#wf-form-contact').find('#Email-2').focus() 
		},1000)		
	})

	$('#wf-form-contact').submit(function (){
		disableForm(this) 
	    $.ajax({
	        type:'post',
	        url: endpoint + '/contacto',
	        data:$(this).serialize(),
	        success:function (response){
	        	if(response.status=='ok'){
	        		$('#wf-form-contact').parent().find('.form__succes').fadeIn()
	        	}
	        	enableForm('#wf-form-contact')
	        },
	        error: function (data){
	        	enableForm('#wf-form-publish')
	            console.log("Hubo un error al subir el archivo")
	        }
	    })
		return false
	})


    $(window).on('hashchange', function (){
    	if(location.pathname=='/'){
			fetch()
		}
    })

    // check and keep session alive

    refresh_token()

    setInterval(function (){
    	refresh_token()
    },1000*60*60)    
})

window.onerror = function (msg, url, line, col, error) {
   var extra = !col ? '' : '\ncolumn: ' + col
   , browser = get_browser()
   extra += !error ? '' : '\nerror: ' + error
   var message = "Error: " + msg + "\nurl: " + url + "\nline: " + line + extra + "\nBrowser: " + browser.name + " " + browser.version
   //alert(msg);
   $.post(endpoint + '/ecmalog',{line:message});
   return true;
}

$.views.settings.delimiters("[[", "]]")
$.extend({ 
	server: function (options) { 

		options.method = "post"
		options.url = endpoint + options.url
		options.cache = false
		options.async = true
		options.then = options.then

		var jwt = get_jwt()

		if(jwt.length){
			options.beforeSend = function (xhr) { 
		    	xhr.setRequestHeader('Authorization', 'Bearer ' + jwt)
		    }
		}

		var jqXHR = $.ajax(options).then(options.then)

  		jqXHR.done(function () { 

  		})
	}
})

$.ajaxSetup({
    cache:false,
    error: function (xhr, status, error){
    	hideLoader()
    	switch(xhr.status){
    		case 401:
			localStorage.clear()
    		$("body").addClass('momargin').load("/401", function (){
    			webflow_reset()
    			setTimeout(function (){
    				$('.button--register').click()	
    			},1000)	  			
    		})
    		break
    		default:
    		$("body").addClass('momargin').load("/404")
    	}
    }
})

