<!DOCTYPE html>
<!--  This site was created in Webflow. http://www.webflow.com -->
<!--  Last Published: Wed Feb 08 2017 17:01:40 GMT+0000 (UTC)  -->
<html data-wf-page="589b3c946dc290a6649eaf3a" data-wf-site="5898bd85176071a2715d75b8" lang="es">
<head>
  <meta charset="utf-8">
  <title>Developers Verusados</title>
  <meta content="Documentación para implementar Verusados en tu ecommerce." name="description">
  <meta content="Developers Verusados" property="og:title">
  <meta content="Documentación para implementar Verusados en tu ecommerce." property="og:description">
  <meta content="summary" name="twitter:card">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <meta content="Webflow" name="generator">
  <link href="css/normalize.css" rel="stylesheet" type="text/css">
  <link href="css/webflow.css" rel="stylesheet" type="text/css">
  <link href="css/verusados-developers.webflow.css" rel="stylesheet" type="text/css">
  <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.4.7/webfont.js"></script>
  <script type="text/javascript">
    WebFont.load({
      google: {
        families: ["Open Sans:300,300italic,400,400italic,600,600italic,700,700italic,800,800italic","Inconsolata:400,400italic,700,700italic"]
      }
    });
  </script>
  <script src="js/modernizr.js" type="text/javascript"></script>
  <link href="images/iso-verusados.png" rel="shortcut icon" type="image/x-icon">
  <link href="images/iso-verusados-big.png" rel="apple-touch-icon">
</head>
<body>
  <div class="header">
    <div class="header__contaniner">
      <a class="logo-developers w-inline-block" href="/"><img src="images/iso-verusados.png">
        <div class="logo-developers--txt">Developers</div>
      </a>
      <div class="header-links"><a class="header-links--link" href="api-docs.html">API docs</a>
      </div>
    </div>
  </div>
  <div class="container w-container">
    <h1>Documentación para usar <a href="http://www.verusados.com/" class="link">Verusados</a> para Empresas</h1>
    <div class="non-authorized-client">
    <h3>Primero</h3>
    <p class="developers--p">Solicitar acceso al API para empresas a <a class="link" href="mailto:contacto@verusados.com?subject=Quiero solicitar acceso a la API para empresas">contacto@verusados.com</a>.
      <br>Vas a recibir un email con un&nbsp;<strong>CLIENT_ID</strong>&nbsp;y una&nbsp;<strong>KEY</strong>
    </p>
    </div>
    <h3>Headers</h3>
    <p class="developers--p">Para autentificarte como cliente en nuestra API es necesario que envíes tu&nbsp;<strong>client_id</strong>&nbsp;y&nbsp;<strong>key</strong>&nbsp;en el encabezado de cada solicitud acompañados de la llave&nbsp;<strong>Authorization</strong>.</p>
    <div class="code">
      <p class="code--p client-auth">Authorization: client_id:key</p>
    </div>
    <h3>Métodos</h3>
    <p class="developers--p">Estos son los métodos disponibles para cada uno de nuestros clientes</p>
    <div class="wiki"></div>
    <h3>Endpoints</h3>
    <p class="developers--p">Usá Sandbox para las pruebas</p>
    <div class="code">
      <p class="code--p">https://sandbox.verusados.com</p>
    </div>
    <p></p>
    <p class="developers--p">Usá el entorno real para producción.</p>
    <div class="code">
      <p class="code--p">https://api.verusados.com</p>
    </div>
    <h3>Importante</h3>
    <p class="developers--p">Los envíos se pasarán a retirar solo si son creados en el entorno real.</p>
  </div>
  <div class="footer">
    <div class="w-container">
      <div>Volver a <a href="http://www.verusados.com/" class="footer--link">verusados.com</a>
      </div>
    </div>
  </div>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js" type="text/javascript"></script>
  <script src="js/webflow.js" type="text/javascript"></script>
  <!-- [if lte IE 9]><script src="https://cdnjs.cloudflare.com/ajax/libs/placeholders/3.0.2/placeholders.min.js"></script><![endif] -->
  <script src="js/jsrender.min.js" type="text/javascript"></script>
  <script src="js/config.js" type="text/javascript"></script>
  <script src="js/model.js" type="text/javascript"></script>
  <script src="js/docs.js" type="text/javascript"></script>

  <script id="model" type="text/x-jsrender">
    <h4 class="m-toggle"><span class="w-badge {{:method}}">{{:method}}</span>&nbsp;&nbsp;{{:url}}<span class="w-icon-arrow-down" style="margin-left: 6px;"></span></h4>
    <div class="method {{:method}}{{:url}} w-hidden">
      <p>{{:description}}</p>
      <form class="method-form" method="{{:method}}" action="{{:~getUrl(url,body[0]?body[0].placeholder:null)}}">
      {{if headers.length}}
        <table class="table table-striped">
          <tr>
            <th>Header</th>
            <th>Valor</th>
            <th>Descripción</th>
          </tr>
        {{for headers}}
          <tr>
            <td>{{:name}}</td>
            <td><input class="w-input" type="text" name="header--{{:name}}" value="{{:~getHeaders(value)}}" {{if required}}{{:required}}{{/if}}></td>
            <td>{{:description}}</td>
          </tr>
        {{/for}}
        </table>
      {{/if}}
      {{if body.length}}
        <table class="table table-striped">
          <tr>
            <th>Parámetro</th>
            <th>Valor</th>
            <th>Descripción</th>
            <th>Data Type</th>
          </tr>
        {{for body}}
          <tr>
            <td>{{:name}}</td>
            <td><input class="w-input" type="text" name="{{:name}}" placeholder="{{:placeholder}}" value="{{:placeholder}}" {{if required}}{{:required}}{{/if}}></td>
            <td><p>{{:description}}</p></td>
            <td><em>{{:datatype}}</em></td>
          </tr>
        {{/for}}
        </table>
      {{/if}}
        <button class="w-button" type="submit">Enviar</button>
      </form>
      <div class="response w-hidden">
        <h5>Request URL</h5>
        <pre class="request-url"></pre>
        <h5>Response Body</h5>
        <pre class="response-body"></pre>
        <h5>Response Code</h5>
        <pre class="response-code"></pre>
        <h5>Response Headers</h5>
        <pre class="response-headers"></pre>
      </div>
      <hr>
    </div>
  </script>
</body>
</html>
