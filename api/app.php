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

date_default_timezone_set("America/Argentina/Buenos_Aires");

require __DIR__ . "/vendor/autoload.php";

if(file_exists(__DIR__ . '/.env')){
	$dotenv = new Dotenv\Dotenv(__DIR__);
	$dotenv->load();
}

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true
    ]
]);

require __DIR__ . "/config/dependencies.php";
require __DIR__ . "/config/handlers.php";
require __DIR__ . "/config/middleware.php";

$app->get("/", function ($request, $response, $arguments) {
    print "-Panoram API-";
});

require __DIR__ . "/routes/functions.php";
require __DIR__ . "/routes/auth.php";
require __DIR__ . "/routes/panos.php";
require __DIR__ . "/routes/perfil.php";
require __DIR__ . "/routes/transmitir.php";
require __DIR__ . "/routes/email.php";

$app->run();