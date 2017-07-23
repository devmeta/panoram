<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

$container = $app->getContainer();

$container["errorHandler"] = function ($container) {
	die("asdasd");

	exit();
        return <<<END
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Not Foundss</title>
  <meta content="Not Found" property="og:title">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <meta content="Webflow" name="generator">
  <link href="/css/normalize.css" rel="stylesheet" type="text/css">
  <link href="/css/webflow.css" rel="stylesheet" type="text/css">
  <link href="/css/gibson.css" rel="stylesheet" type="text/css">
  <link href="/css/panoram.webflow.css?_={$now}" rel="stylesheet" type="text/css">
  <link href="/css/animations.css?_={$now}" rel="stylesheet" type="text/css">  
  <link href="/css/sweetalert.css" rel="stylesheet" type="text/css"> 
  <link href="/images/iso-panoram.png" rel="shortcut icon" type="image/x-icon">
  <link href="/images/iso-panoram-big.png" rel="apple-touch-icon">
  <style>
    .irs-bar-edge {top: 25px !important;}
  .irs-from, .irs-to, .irs-single {background: #EF4039 !important}
  </style>
</head>
<body class="momargin">
  <div class="utility-page-wrap">
    <div class="utility-page-content">
      <a href="/">
        <img src="/images/logo-panoram.png">
      </a>
      <h2>No se encontró la página</h2>
      <div>Estass página ya no existe más. Cualquier duda escribinos a <a href="mailto:contacto@panoram.com">contacto@panoram.com</a></div>
    </div>
  </div>
</body>
</html>
END;	
    //return new Slim\Handlers\ApiError($container["logger"]);
};
