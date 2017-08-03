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

use App\Quote;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Tuupola\Base62;

$app->post("/quotes/{take}", function ($request, $response, $arguments) {
        
    $take = $request->getAttribute('take');
    $data = $this->spot->mapper("App\Quote")->query("SELECT * FROM quotes ORDER BY RAND() LIMIT {$take}");

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

