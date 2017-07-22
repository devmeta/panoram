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
use App\PanoramProp;
use App\Status;
use App\Tag;
use App\File;
use App\Banner;

$app->post("/transmisiones/buscar", function ($request, $response, $arguments) {

    $body = $request->getParsedBody();
    $maxprice = "500000";

    $filter = [
        'panorams.enabled = 1',
        'panorams.deleted = 0',
        'panorams.paused = 0',
        'panorams.condition = 1',
        'panorams.enabled_until > now()'
    ];

    $orderarr = [
        'panorams.hits DESC'
    ];

    $cases = [];
    $ors = [];
    $groupby = 'panorams.id';

    if( ! empty($body['sort'])){

        $what = $body['sort'];
        $orderarr = [];

        switch($what){

            case 'menor-precio':
            $orderarr[]= 'panorams.price_ars ASC';
            break;

            case 'mayor-precio':
            $orderarr[]= 'panorams.price_ars DESC';
            break;

            case 'mas-vistos':
            $orderarr[]= 'panorams.hits DESC';
            break;

            case 'con-garantia':
            $orderarr[]= 'panorams.warranty DESC';
            break;

            default:
            $orderarr[]= 'panorams.hits DESC';
            break;

        }
    }

    if( ! empty($body['Desde'])){
        $filter[] = 'panorams.price_ars > ' . $body['Desde'];
    }

    if( ! empty($body['Hasta']) AND $body['Hasta'] < $maxprice){
        $filter[] = 'panorams.price_ars < ' . $body['Hasta'];
    }

    if( ! empty($body['search'])){
        $new_arr = array_map('trim', explode(' ', $body['search']));
        $new_arr = array_filter(array_values($new_arr));
        $j = sizeof($ors);
        $chain = implode('-',$new_arr);
        $ors[$j+1][]= "panorams.title COLLATE UTF8_GENERAL_CI LIKE '%" . $chain . "%'";
    }

    if( ! empty($body['ano'])){
        $parts = explode(';',$body['ano']);

        if(count($parts)<2)
            $parts[1] = $parts[0] + 1;

        $filter[] = 'panorams.mt_year >= ' . $parts[0];
        $filter[] = 'panorams.mt_year <= ' . $parts[1];
    }

    if( ! empty($body['precio'])){
        $parts = explode(';',$body['precio']);

        if(count($parts)<2)
            $parts[1] = $parts[0] + 1000;

        $filter[] = 'panorams.price_ars >= ' . $parts[0];

        if(substr($parts[1],-1) !== ' ')
            $filter[] = 'panorams.price_ars <= ' . $parts[1];
    }

    if( ! empty($body['km']) OR (isset($body['km']) AND $body['km'] == "0")){

        $parts = explode(';',$body['km']);

        if(count($parts)<2)
            $parts[1] = $parts[0] + 10;

        $filter[] = 'panorams.kms >= ' . $parts[0];
        if(substr($parts[1],-1) !== ' ')
            $filter[] = 'panorams.kms <= ' . $parts[1];
    }

    foreach($body as $param => $value){
        if(strlen($value)){
            if(substr($param,-3) == '_id'){
                $filter[] = 'panorams.' . $param . ' = ' . $value;
            }
            else if(substr($param,0,5) == 'prop-'){
                $parts = explode('-',$param);
                // un auto para vos 
                if(!in_array($parts[1],[340,350,360])){
                    if($parts[1]==90){
                        $filter[] = 'panorams.fuel_id = 1';
                    }
                    $ors[$parts[0]][] = 'panorams_props.prop_id = ' . $parts[1];
                } else {
                    if($parts[1]==340){
                        $filter[] = 'panorams.fuel_id = 3';
                    }
                    if($parts[1]==350){
                        $filter[] = 'panorams.fuel_id = 2';
                    }
                    if($parts[1]==360){
                        $filter[] = 'panorams.fuel_id = 1';
                    }
                }
            }
            else if( $value == 'on'){

                $parts = explode('-',$param);

                if($parts[0]=='financing') {
                    $parts[1] = 1;
                }

                if($parts[0]=='condition' AND $parts[1] == 1) {
                    $filter[] = 'panorams.kms < 11';
                }

                if( empty($ors[$parts[0]])) $ors[$parts[0]] = [];
                $ors[$parts[0]][] = 'panorams.' . $parts[0] . ' = ' . $parts[1];
            }
        }
    }

    // filtros custom

    if( !empty($body['filtro'])){
        switch($body['filtro']){

            case 'nuevas-oportunidades':

            $filter[] = 'panorams.mt_year > ' . date('Y') - 5;
            $filter[] = 'panorams.kms < 120000';
            break;
        }
    }

    if(count($ors)){
        foreach($ors as $or){
            $filter[] = '(' . implode(' OR ',$or) . ')';
        }
    }

    if(count($cases)){
        $str= ' CASE ';
        foreach($cases as $case){
            $str.= $case . ' ';
        }
        $orderarr[]= $str . ' END';
    }

    $where = implode(' AND ',$filter);
    $orderby = isset($orderarr) ? implode(',',$orderarr) : '';
    $from = (int) $body['pos']?:0;
    $to = (int) (!empty($body['take']) ? $body['take'] : getenv('APP_LISTING_PER_PAGE'));
    $right = $from+$to;

    $sql = "SELECT panorams.* 
        FROM panorams
        LEFT JOIN brands ON brands.id = panorams.brand_id 
        LEFT JOIN models ON models.id = panorams.model_id 
        LEFT JOIN gears ON gears.id = panorams.gear_id 
        LEFT JOIN fuels ON fuels.id = panorams.fuel_id 
        LEFT JOIN colors ON colors.id = panorams.color_id 
        LEFT JOIN regions ON regions.id = panorams.region_id 
        LEFT JOIN panorams_props ON panorams_props.pan_id = panorams.id 
        WHERE {$where} 
        GROUP BY {$groupby} 
        ORDER BY {$orderby} 
        LIMIT {$from},{$to}";

    $sql2 = "SELECT panorams.id 
        FROM panorams
        LEFT JOIN brands ON brands.id = panorams.brand_id 
        LEFT JOIN models ON models.id = panorams.model_id 
        LEFT JOIN gears ON gears.id = panorams.gear_id 
        LEFT JOIN fuels ON fuels.id = panorams.fuel_id 
        LEFT JOIN colors ON colors.id = panorams.color_id 
        LEFT JOIN regions ON regions.id = panorams.region_id 
        LEFT JOIN panorams_props ON panorams_props.pan_id = panorams.id 
        WHERE {$where}
        GROUP BY panorams.id";

    $mapper = $this->spot->mapper("App\Panoram")->query($sql);
    $mapper2 = $this->spot->mapper("App\Model")->query($sql2);

    if($right > $mapper2->count()) $right = $mapper2->count();

    /* Serialize the response data. */
    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new Panoram);
    $data['listing'] = $fractal->createData($resource)->toArray();

    $data['sql'] = $sql;
    $data['orderby'] = $orderby;
    $data['filter'] = $filter;

    $data['pagination']['count'] = $mapper2->count();
    $data['pagination']['position'] = ($right?1:0) . "-{$right}";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));    
});


$app->post("/transmisiones/sidebar", function ($request, $response, $arguments) {

    $body = $request->getParsedBody();
    $ins = [];

    $filter = [
        "panorams.brand_id > 0",
        "panorams.deleted = 0",
        "panorams.paused = 0",
        "panorams.condition = 1",
        "panorams.price_ars >= 40000",
        "panorams.tel <> ''",
        "panorams.mt_year > 1950",
        "panorams.enabled_until > now()"
    ];

    if( ! empty($body['precio'])){
        $parts = explode(";",$body['precio']);
        if($parts[0] != '0')
            $filter[]='panorams.price_ars > ' . (int) $parts[0];
        if(strpos($parts[1],' ') === false)
            $filter[]='panorams.price_ars < ' . (int) $parts[1];
    }

    if( ! empty($body['km'])){
        $parts = explode(";",$body['km']);
        $filter[]='panorams.kms >= ' . (int) $parts[0];
        if(strpos($parts[1],' ') === false)
            $filter[]='panorams.kms < ' . (int) $parts[1];
    }

    if( ! empty($body['ano'])){
        $parts = explode(";",$body['ano']);
        $filter[]='panorams.mt_year > ' . (int) $parts[0];
        $filter[]='panorams.mt_year <= ' . (int) $parts[1];
    }

    if(count($body)){
        foreach($body as $param => $value){
            if(strlen($value)){
                if( $value == 'on'){
                    $parts = explode("-",$param);
                    if($parts[0]=='financing') $parts[1] = 1;
                    if(empty($ins[$parts[0]])) $ins[$parts[0]] = [];
                    $ins[$parts[0]][] = $parts[1];
                }
            }
        }
    }

    if(count($ins)){
        foreach($ins as $param => $values){
            if(substr($param,0,5) != 'prop'){
                $filter[]='panorams.' . $param . ' IN(' . implode(',',$values) . ')';
            }
        }
    }

    $data['checks'][] = \sidebar_component("brand_id",$filter,4);
    $data['checks'][] = \sidebar_component("model_id",$filter,4);
    $data['checks'][] = \sidebar_component("gear_id",$filter);
    $data['checks'][] = \sidebar_component("fuel_id",$filter);
    $data['selects'][] = \sidebar_component("color_id",$filter);
    $data['selects'][] = \sidebar_component("region_id",$filter);
    $data['doors'] = \sidebar_items("doors",$filter);

    // featured
    $mapper = $this->spot->mapper("App\Panoram")
        ->where([
            'enabled' => 1,
            'deleted' => 0,
            'paused' => 0,
            'condition' => 1,
            'enabled_until <' => "now()",
            'warranty' => 1,
            'price >' => 0,
            'tel <>' => "",
            'mt_year >' => "1950",
        ])
        ->order(["hits" => "DESC"])
        ->limit(20);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new Panoram);
    $data['featured'] = $fractal->createData($resource)->toArray();

    if( ! count($data['featured']['data'])){
        $mapper = $this->spot->mapper("App\Panoram")
            ->where([
                'enabled' => 1,
                'deleted' => 0,
                'paused' => 0,
                'condition' => 1,
                'enabled_until <' => "now()",
                'price >' => 0,
                'tel <>' => "",
                'kms <' => 120000,
                'mt_year >' => date('Y') - 5,
            ])
            ->order(["hits" => "DESC"])
            ->limit(10);

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($mapper, new Panoram);
        $data['featured'] = $fractal->createData($resource)->toArray();
    }

    // banners
    $mapper = $this->spot->mapper("App\Banner")
        ->where([
            'enabled' => 1,
            'type' => "banner",
            'enabled_until <' => "now()",
        ])
        ->order(["position" => "ASC"]);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new Banner);
    $data['banners'] = $fractal->createData($resource)->toArray();
    $data['wheres'] = $filter;

    // slides
    $mapper = $this->spot->mapper("App\Banner")
        ->where([
            'enabled' => 1,
            'type' => "slide",
            'enabled_until <' => "now()"
        ])
        ->order(["position" => "ASC"]);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new Banner);
    $data['slides'] = $fractal->createData($resource)->toArray();

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));      
});


$app->post("/{slug}", function ($request, $response, $arguments) {

    $code = $request->getAttribute('slug');
    $code = strtok($code,'--');
    $data["status"] = "runtime";

    $vehicle = $this->spot->mapper("App\Panoram")->first([
        "code" => $code,
        "enabled" => 1,
        "deleted" => 0,
        "paused" => 0,
        "condition" => 1,
        'enabled_until <' => "now()"
    ]);

    if($vehicle){

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($vehicle, new Panoram);
        $item = $fractal->createData($resource)->toArray();
        
        if( ! $item->active) {
            $data["status"] = "error";
            $data["title"] = "Tu publicación está caducada!";
            $data["message"] = "Esta publicación ha caducado. Si es tu publicación y deseas renovarla <a href='/perfil-usuario/transmisiones'>podés ir a tu panel</a>";
        }

        if($item->sold) {
            $data["status"] = "error";
            $data["title"] = "Tu publicación figura como vendida!";
            $data["message"] = "Este vehículo figura como vendido. <a href='/perfil-usuario/transmisiones'>Volver a mis publicaciónes</a>";
        }

        $vehicle->data(['hits' => $vehicle->hits + 1]);
        $this->spot->mapper("App\Panoram")->save($vehicle);

        if(!empty($vehicle->city_id)){
            $related = $this->spot->mapper("App\Panoram")->query("SELECT panorams.* FROM panorams WHERE (user_id = {$vehicle->user_id} OR city_id = '{$vehicle->city_id})' AND id <> {$vehicle->id} AND enabled = 1 AND deleted = 0 AND paused = 0 AND condition = 1 AND enabled_until > now() ORDER BY CASE city_id WHEN {$vehicle->city_id} THEN 0 ELSE 2 END, CASE region_id WHEN {$vehicle->region_id} THEN 1 ELSE 2 END ASC, hits desc");
        } else {
            $related = $this->spot->mapper("App\Panoram")->query("SELECT panorams.* FROM panorams WHERE user_id = {$vehicle->user_id} AND id <> {$vehicle->id} AND enabled = 1 AND deleted = 0 AND paused = 0 AND condition = 1 AND enabled_until > now() ORDER BY hits desc");
        }

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($related, new Panoram);

        $data['vehicle'] = $item;
        $data['related'] = $fractal->createData($resource)->toArray();
        $data["status"] = "success";

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));

    } else {
        throw new NotFoundException("Panoram not found.", 404);        
    }

    if( $data['status'] == "error"){
        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));        
    }
});