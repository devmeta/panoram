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

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Tuupola\Base62;
use App\Panoram;
use App\PropGroup;
use App\Prop;
use App\Region;
use App\City;
use App\PanoramProp;
use App\Status;
use App\Tag;
use App\File;

function update_title($mapper,$initial_title=""){

    $photo = "";
    $photo_name = "";
    $description = "";
    $titlechunk = [];
    $ctl = 0;

    foreach($mapper->files as $mphoto){
        if(!$ctl AND $mphoto->file_url){
            $photo_name = $mphoto->file_url;
            $ctl = 1;
        }
    }

    if(strlen($photo_name)){
        $photo_name = substr($photo_name, strrpos($photo_name, '/') + 1);
        $photo_name = strtok($photo_name, ".");
        $photo = $photo_name;
    }

    if($mapper->extrainfo){
        $description = $mapper->extrainfo;
    }

    if(!empty($initial_title) AND trim($initial_title)!=""){
        $title = $initial_title;
    } else {
        $photo_id = strtok($args['slug'],"--");
        $parts = explode('--',$mapper->title);
        unset($parts[0]);
        $title = implode('--',$parts);
    }

    $title = str_replace(['---','/'],'-',implode('--',[$photo,$title]));
    $title = substr($title,0,255);

    return $title;
}

$app->post("/upload/remove/{id}", function ($request, $response, $arguments) {
    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }

    $photo = $this->spot->mapper("App\File")->first([
        "id" => $request->getAttribute('id')
    ]);

    if( ! $photo){
        throw new NotFoundException("File not found.", 404);
    }

    $fn = substr($photo->photo_url, strrpos($photo->photo_url, '/') + 1);
    $path = getenv('BUCKET_PATH') . '/cams/' . $this->token->decoded->uid . '/' . $pan->code . '/';

    unlink(__DIR__ . '/../bucket/' . $fn);

    $resolutions = explode(',',getenv('S3_RESOLUTIONS'));

    foreach($resolutions as $res){
        $parts = explode('x',$res);
        unlink(__DIR__ . '/../bucket/' . $parts[0] . 'x' . $parts[1] . $fn);
    }

    $this->spot->mapper("App\File")->delete($photo);

    $data["status"] = "ok";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));

});

$app->post("/upload/sort/{code}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }
    
    $body = $request->getParsedBody();

    foreach ($body['sorted'] as $pos => $id) {
        if(!empty($id)){
            
            $photo = $this->spot->mapper("App\File")->first([
                "id" => $id
            ]);

            if( ! $photo){
                throw new NotFoundException("File not found.", 404);
            }

            $photo->data(['position' => $pos]);
            $this->spot->mapper("App\File")->save($photo);

            $data[$id] = ['position' => $pos,'url'=>$photo->photo_url];
        }
    }

    $mapper = $this->spot->mapper("App\Panoram")->first([
        "code" => $request->getAttribute('code'),
        "user_id" => $this->token->decoded->uid
    ]);

    $body = [];
    $body['title'] = \update_title($mapper);
    $mapper->data($body);
    $this->spot->mapper("App\Panoram")->save($mapper);

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));

});

$app->post("/upload/{code}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }

    $request_body = file_get_contents('php://input');
    $path = 'cams/' . $request->getAttribute('code');
    $mapper = $this->spot->mapper("App\Panoram")->first([
        "code" => $request->getAttribute('code'),
        "user_id" => $this->token->decoded->uid
    ]);

    if( ! $mapper){
        throw new NotFoundException("Panoram not found.", 404);        
    }

    $valid_exts = explode(',',getenv('S3_EXTENSIONS')); // valid extensions
    $max_size = getenv('APP_IMAGE_UPLOAD_MAX') * 1024; // max file size in bytes
    $keys = [];
    $data = [];

    // generic upload method per file
    $store = bucket_store($request_body,getenv('S3_RESOLUTIONS'),$path);

    if(empty($store['error'])) {
        $data[$i] = upload_database($_FILES['uploads'],$i, $path . '/' . $store['key'],$store['started'],$mapper);
    } else {
        $data[$i]['error'] = $store['error'];
    }

    $body['title'] = \update_title($mapper);
    $mapper->data($body);
    $this->spot->mapper("App\Panoram")->save($mapper);

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/update-check/{code}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }

    $mapper =  $this->spot->mapper("App\Panoram")->first([
        "code" => $request->getAttribute('code'),
        "user_id" => $this->token->decoded->uid        
    ]);

    if( ! $mapper){
        throw new NotFoundException("Panoram not found.", 404);        
    }

    $id = $request->getParam('id');
    $value = $request->getParam('value');
    $class = "App\PanoramProp";

    $data = [
        "pan_id" => $mapper->id,
        "prop_id" => $id
    ];

    $check = $this->spot->mapper($class)->first($data);

    if( ! $value AND $check){
        $this->spot->mapper($class)->delete($check);
    } else {
        $check = new $class($data);
        $this->spot->mapper($class)->save($check);
    }

    $data["status"] = "ok";
    $data["message"] = "Prop updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/update/{code}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Para actualizar una publicación se requiere autorización", 403);
    }

    $body = $request->getParsedBody();
    $privatekeys = ['id','code','price_ars','enabled','paused','sold','enabled_until','created','updated'];
    $fkeys = ['brand_id','model_id','version_id','fuel_id','color_id','gear_id','city_id','region_id'];

    if(in_array(key($body),$privatekeys)){

        $data["status"] = "error";
        $data["message"] = "Lamentablemente no podemos procesar esta solicitud en este momento.";

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));        
    }

    $mapper = $this->spot->mapper("App\Panoram")->first([
        "code" => $request->getAttribute('code'),
        "user_id" => $this->token->decoded->uid
    ]);

    if( ! $mapper){
        throw new NotFoundException("No se encontró vehículo al actualizar", 404);        
    }

    // standard update -- build title

    if(key($body)=='title'){
        $body['title'] = \update_title($mapper, $body['title']);
    }

    if( ! empty($body['price'])) {
        // humanize price
        if($body['price'] < 1000 AND $mapper->currency AND $mapper->currency->iso_code == "ARS") {
            $body['price'] *= 1000;
        }
        if($mapper->currency AND $mapper->currency->iso_code == "ARS"){
            $body['price_ars'] = $body['price'];    
        } else {
            $body['price_ars'] = $body['price']/ $mapper->currency->rate;            
        }
    }

    if( ! empty($body['currency_id'])){
        $currency =  $this->spot->mapper("App\Currency")->first([
            "id" => $body['currency_id'],
        ]);
        if($currency AND $currency->iso_code == "ARS"){
            $body['price_ars'] = $mapper->price;    
        } else {
            $body['price_ars'] = $mapper->price / $currency->rate;
        }
    }

    // remove empty fks
    foreach($fkeys as $key){
        if( empty($body[$key])) unset($body[$key]);
    }

    // trim body
    foreach($body as $i => $value){
        $body[$i] = trim($value);
    }

    if(!empty($body['kms']) AND $body['kms'] > 10){
        $body['condition'] = 2;
    }

    $mapper->data($body);
    $this->spot->mapper("App\Panoram")->save($mapper);

    $data["status"] = "ok";
    $data["message"] = "Vehículo actualizado";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/transmitir/inicio", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Se necesita acceso para publicar", 403);
    }

    $code = strtolower(Base62::encode(random_bytes(6)));

    while($this->spot->mapper("App\Panoram")->first(["code" => $code])){
        $code = strtolower(Base62::encode(random_bytes(6)));
    }

    $oldmask = umask(0);
    mkdir(getenv('BUCKET_PATH') . '/cams/' . $code, 0777);
    umask($oldmask);

    $body = [
        'code' => $code,
        // 'title' => $code,
        'user_id' => $this->token->decoded->uid,
        'enabled_until' => new \DateTime("now +" . getenv('APP_AD_DUE'))
    ];

    $panoram = new Panoram($body);
    $id = $this->spot->mapper("App\Panoram")->save($panoram);
    $data['id'] = $id;
    $data['code'] = $code;

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/transmitir/localidades/{region_id}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }

    $mapper = $this->spot->mapper("App\City")
        ->where(['region_id' => $request->getAttribute('region_id')])
        ->order(['title' => 'ASC'])
        ->limit(1000);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new City);
    $data['cities'] = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));    
});


$app->post("/transmitir/modelos/{brand_id}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }

    $mapper = $this->spot->mapper("App\Model")
        ->where(['brand_id' => $request->getAttribute('brand_id')])
        ->order(['title' => 'ASC'])
        ->limit(1000);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new Model);
    $data['models'] = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/transmitir/versiones/{model_id}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }

    $mapper = $this->spot->mapper("App\Version")
        ->where(['model_id' => $request->getAttribute('model_id')])
        ->order(['title' => 'ASC'])
        ->limit(1000);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new Version);
    $data['versions'] = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/transmitir/{code}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }

    $mapper = $this->spot->mapper("App\Panoram")->first([
        "code" => $request->getAttribute('code'),
        "user_id" => $this->token->decoded->uid
    ]);

    if( ! $mapper) {
        throw new NotFoundException("Panoram not found.", 404);        
    }

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($mapper, new Panoram);
    $data['vehicle'] = $fractal->createData($resource)->toArray();
    
    if( ! empty($data['vehicle']['data']['region']['id'])){

        // citites
        $mapper = $this->spot->mapper("App\City")
            ->where(['region_id' => $data['vehicle']['data']['region']['id']])
            ->order(["title" => "ASC"]);

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($mapper, new City);
        $data['cities'] = $fractal->createData($resource)->toArray();        
    }


    // regions
    $mapper = $this->spot->mapper("App\Region")
        ->all()
        ->order(["title" => "ASC"]);

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new Region);
    $data['regions'] = $fractal->createData($resource)->toArray();


    // props
    $propgroups = $this->spot->mapper("App\PropGroup")
        ->all(['enabled' => 1])
        ->order(["order" => "ASC"]);

    foreach($propgroups as $propgroup){
        $props = [];

        foreach($propgroup->props as $prop){
            $props[] = [
                'id' => $prop->id,
                'title' => $prop->title
            ];
        }

        $data['props'][$propgroup->slug]['data'] = $props;
    } 

    $data['years'] = [date('Y'),'1950'];
    $data['doors'] = [5,2];

    $basic = [];

    // retrieve basic data (if exists)
    $mapper = $this->spot->mapper("App\Panoram")
        ->where([
            'user_id' => $this->token->decoded->uid,
            'code <>' => $request->getAttribute('code')
        ])
        ->order(['created' => 'DESC'])
        ->limit(1);

    if($mapper[0]){
        $basic = [
            'region_id' => $mapper[0]->region_id,
            'city_id' => $mapper[0]->city_id,
            'tel' => $mapper[0]->tel,
            'schedule' => $mapper[0]->schedule
        ];
    }

    $data['basic'] = $basic;

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});
