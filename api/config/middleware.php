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
use App\Token;

use Slim\Middleware\JwtAuthentication;
use Slim\Middleware\HttpBasicAuthentication;
use Tuupola\Middleware\Cors;
use Gofabian\Negotiation\NegotiationMiddleware;
use Micheh\Cache\CacheUtil;
use Firebase\JWT\JWT;

$container = $app->getContainer();

$container["HttpBasicAuthentication"] = function ($container) {
    return new HttpBasicAuthentication([
        "path" => "/token",
        "relaxed" => [],
        "users" => [
            "test" => "test"
        ]
    ]);
};

$container["token"] = function ($container) {
    return new Token;
};

$container["jwt"] = function ($container) {
    return new JwtAuthentication([
        "path" => [
            "/vender",
            "/perfil",
            "/upload",
            "/update",
            "/update-check",
            "/refresh-token"
        ],
        "passthrough" => [],
        "secret" => getenv("APP_JWT_SECRET"),
        "logger" => $container["logger"],
        "relaxed" => [$container->request->getUri()->getScheme().'://'.$container->request->getUri()->getHost()],
        "error" => function ($request, $response, $arguments) {
            $data["status"] = "error";
            //$data["message"] = $arguments["message"];
            $data["message"] = \auth_endpoints();
            return $response
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        },
        "callback" => function ($request, $response, $arguments) use ($container) {
            $container["token"]->hydrate($arguments["decoded"]);
        }
    ]);
};

$container["Cors"] = function ($container) {
    return new Cors([
        "logger" => $container["logger"],
        "origin" => [
            "https://panoram.app",
            "https://panoram.api",
            "https://panoram.devmeta.net",
            $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost()
        ],
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
        "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since","Content-Type","X-Requested-With","Origin","Accept"],
        "headers.expose" => ["Authorization", "Etag"],
        "credentials" => true,
        "cache" => 60,
        "error" => function ($request, $response, $arguments) {
            $data["status"] = "error";
            //$data["message"] = $arguments["message"];
            $data["message"] = \auth_endpoints();
            return $response
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    ]);
};

$container["Negotiation"] = function ($container) {
    return new NegotiationMiddleware([
        "accept" => ["application/json"]
    ]);
};

$app->add("HttpBasicAuthentication");
$app->add("jwt");
$app->add("Cors");
$app->add("Negotiation");

$container["cache"] = function ($container) {
    return new CacheUtil;
};
