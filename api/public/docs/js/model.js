var model = [
	{
		"method" : "get"
		, "url" : "/google/signup"
		, "description" : "Ingresa a usuario desde Google"
		, "headers" : []
		, "body" : [			
			{
				"name" : "code"
				, "placeholder" : ""
				, "description" : "Token data"
				, "datatype" : "String"
				, "required" : "required"
			}
		]
	}
	, {
		"method" : "post"
		, "url" : "/oauthendpoints"
		, "description" : "Obtiene los endpoints de redes sociales"
		, "headers" : []
		, "body" : []
	}
	, {
		"method" : "post"
		, "url" : "/contacto"
		, "description" : "Envía correo de contacto"
		, "headers" : []
		, "body" : [
			{
				"name" : "email"
				, "placeholder" : "user@fakemail.com"
				, "description" : "Email del remitente"
				, "datatype" : "String"
				, "required" : "required"
			}
			, {
				"name" : "area"
				, "placeholder" : "Contacto"
				, "description" : "Area de la consulta"
				, "datatype" : "String"
				, "required" : "required"
			}
			, {
				"name" : "consulta"
				, "placeholder" : "Consulta"
				, "description" : "Consulta del remitente"
				, "datatype" : "Text"
				, "required" : "required"
			}

		]
	}
	, {
		"method" : "post"
		, "url" : "/autos/buscar"
		, "description" : "Buscar vehículos"
		, "headers" : []
		, "body" : [
			{
				"name" : "search"
				, "placeholder" : "Audi"
				, "description" : "Palabra clave"
				, "datatype" : "String"
			}
			, {
				"name" : "Desde"
				, "placeholder" : "50000"
				, "description" : "Precio máximo"
				, "datatype" : "String"
			}
			, {
				"name" : "Hasta"
				, "placeholder" : "500000"
				, "description" : "Precio mínimo"
				, "datatype" : "String"
			}
			, {
				"name" : "ano"
				, "placeholder" : "1930;2017"
				, "description" : "Año de matriculación rango"
				, "datatype" : "String"				
			}
			, {
				"name" : "precio"
				, "placeholder" : "50000;500000"
				, "description" : "Precio rango"
				, "datatype" : "String"
				, "required" : "required"				
			}
			, {
				"name" : "color_id"
				, "placeholder" : 1
				, "description" : "Color ID"
				, "datatype" : "Integer"				
			}
			, {
				"name" : "region_id"
				, "placeholder" : 1
				, "description" : "Región ID"
				, "datatype" : "Integer"
			}
			, {
				"name" : "brand_id"
				, "placeholder" : 1
				, "description" : "Fabricante ID"
				, "datatype" : "Integer"				
			}
			, {
				"name" : "model_id"
				, "placeholder" : 1
				, "description" : "Modelo ID"
				, "datatype" : "Integer"
			}
			, {
				"name" : "version_id"
				, "placeholder" : 1
				, "description" : "Versión ID"
				, "datatype" : "Integer"
			}
			, {
				"name" : "gear_id"
				, "placeholder" : 1
				, "description" : "Transmisión ID"
				, "datatype" : "Integer"
			}
			, {
				"name" : "fuel_id"
				, "placeholder" : 1
				, "description" : "Combustible ID"
				, "datatype" : "Integer"
			}
			, {
				"name" : "doors"
				, "placeholder" : 4
				, "description" : "Puertas"
				, "datatype" : "Integer"
			}
		]
	}
	, {
		"method" : "post"
		, "url" : "/autos/sidebar"
		, "description" : "Establece filtros y portada"
		, "body" : []
		, "headers" : []
	}	
	, {
		"method" : "get"
		, "url" : "client-shipments"
		, "description" : "Genera listado de envíos por cliente"
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "{client_id}:{key}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				name : "limit"
				, "placeholder" : "10"
				, "description" : "Nro. de resultados por página"
				, "datatype" : "Integer"
				, "required" : "required"				
			}
			, {
				name : "offset"
				, "placeholder" : "0"
				, "description" : "A partir de que registro comienza la página"
				, "datatype" : "Integer"
				, "required" : "required"				
			}			
		]
	}
	, {
		"method" : "get"
		, "url" : "cancel-shipment/{id}"
		, "description" : "Cancela un envío"
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {token}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "id"
				, "placeholder" : "tcgs-99566"
				, "description" : "ID del envío a cancelar"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]
	}
]
, helper = {
	getHeaders : function(value){
		var access_data = localStorage.getItem("access_data")
		, ad = $.parseJSON(access_data)		
		return ad ? ad.client_id+':'+ad.key : value
	}
	, getUrl : function(url,id) {
		return url.replace('{id}',id)
	}
}