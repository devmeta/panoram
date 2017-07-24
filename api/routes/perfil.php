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
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Intervention\Image\ImageManager;
use App\Panoram;
use App\PropGroup;
use App\Prop;
use App\PanoramProp;
use App\Status;
use App\Tag;
use App\File;
use App\UserPanoram;
use App\Message;
use App\User;
use App\UserMessage;

$app->post("/perfil/reenviar-bienvenida", function ($request, $response, $arguments) {

    $body = $request->getParsedBody();

    $user = $this->spot->mapper("App\User")->first([
        "id" => $this->token->decoded->uid
    ]);

    if( $user ){
        $body['readable_password'] = $body["readable"];
        $body['email_encoded'] = Base62::encode($user->email);
        $body['email'] = $user->email;
        $body['first_name'] = $user->first_name;
        $body['last_name'] = $user->last_name;
        \send_email("Bienvenido a " . getenv('APP_TITLE'),$user,'welcome.html',$body);
        $data["status"] = "ok";        
        $data["message"] = "Reenviamos el email de bienvenida";

    } else {
        $data["status"] = "error";        
        $data["message"] = "No se encontró el usuario";
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});


$app->post("/perfil/panos", function ($request, $response, $arguments) {

    // publicaciones

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }
    
    $filter = [
        'user_id' => $this->token->decoded->uid,
        'deleted' => 0
    ];

    $order = [
        'paused' => "ASC",
        'updated' => "DESC"
    ];

    $mapper = $this->spot->mapper("App\Panoram")->all()
        ->where($filter)
        ->order($order);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($mapper, new Panoram);
    $data['listing'] = $fractal->createData($resource)->toArray();

    // favs & alerts
    $filter = [
        'user_id' => $this->token->decoded->uid
    ];

    $order = [
        'created' => "DESC"
    ];

    $mapper = $this->spot->mapper("App\UserPanoram")->all()
        ->where($filter)
        ->order($order);

    $pan_ids = $fav = $alert = [];

    foreach($mapper as $item){
        $pan_ids[$item->type][] = $item->pan_id;
    }

    if( ! empty($pan_ids['fav'])){
        $mapper = $this->spot->mapper("App\Panoram")->all()
            ->where(['id' => $pan_ids['fav']]);

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($mapper, new Panoram);
        $fav = $fractal->createData($resource)->toArray();
    }

    if( ! empty($pan_ids['alert'])){
        $mapper = $this->spot->mapper("App\Panoram")->all()
            ->where(['id' => $pan_ids['alert']]);

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($mapper, new Panoram);
        $alert = $fractal->createData($resource)->toArray();
    }

    $data['fav'] = $fav;
    $data['alert'] = $alert;

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));    
});

$app->post("/perfil/panos/eliminar/{code}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to update panorams.", 403);
    }

    $mapper =  $this->spot->mapper("App\Panoram")->first([
        "code" => $request->getAttribute('code'),
        "user_id" => $this->token->decoded->uid
    ]);

    if( ! $mapper){
        throw new NotFoundException("Panoram not found.", 404);        
    }

    // drop burden

    $photos = $this->spot->mapper("App\File")->where([
        "pan_id" => $mapper->id
    ]);

    $path = getenv('BUCKET_PATH') . '/cams/' . $mapper->code . '/';

    foreach($photos as $photo) {
        $fn = substr($photo->file_url, strrpos($photo->file_url, '/') + 1);

        unlink($path . $fn);

        $resolutions = explode(',',getenv('S3_RESOLUTIONS'));

        foreach($resolutions as $res){
            $parts = explode('x',$res);
            unlink($path . $parts[0] . 'x' . $parts[1] . $fn);
        }

        $this->spot->mapper("App\File")->delete($photo);        
    }

    rmdir($path);

    $vehicle = $mapper->data(['deleted' => 1]);
    $this->spot->mapper("App\Panoram")->save($vehicle);

    $data['status'] = 'ok';
    $data['message'] = 'El vehículo fue eliminado';

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));  
});

$app->post("/perfil/panos/pausar", function ($request, $response, $arguments) {
    $body = $request->getParsedBody();
    $codes = $body['codes'];

    if(count($codes)){
        foreach($codes as $code){
            $auto = $this->spot->mapper("App\Panoram")->first([
                "code" => $code
            ]);            

            if($auto){
                $auto->data([
                    'paused' => 1,
                    'paused_date' => new \DateTime("now")
                ]);
                $this->spot->mapper("App\Panoram")->save($auto);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($auto, new Panoram);
                $data['data'][]= $fractal->createData($resource)->toArray();

            }
        }
    }

    $data['status'] = 'ok';
    $data['message'] = 'El vehículo fue pausado';

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/panos/despausar", function ($request, $response, $arguments) {
    $body = $request->getParsedBody();
    $codes = $body['codes'];

    if(count($codes)){
        foreach($codes as $code){
            $auto = $this->spot->mapper("App\Panoram")->first([
                "code" => $code
            ]);

            $enabled_until = $auto->enabled_until;
            $paused_date = $auto->paused_date;
            $seconds = 0;

            if($enabled_until->format('U') > $paused_date->format('U')){
                $seconds = $enabled_until->format('U') - $paused_date->format('U');
            }

            if($auto){
                $auto->data([
                    'paused' => 0,
                    'enabled_until' => new \DateTime("now + " . $seconds . ' seconds')
                ]);
                $this->spot->mapper("App\Panoram")->save($auto);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($auto, new Panoram);
                $data['data'][]= $fractal->createData($resource)->toArray();
            }
        }
    }

    $data['status'] = 'ok';
    $data['message'] = 'El vehículo fue restaurado';

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/panos/vendido", function ($request, $response, $arguments) {
    $body = $request->getParsedBody();
    $codes = $body['codes'];
    $data = [];

    if(count($codes)){
        foreach($codes as $code){
            $auto = $this->spot->mapper("App\Panoram")->first([
                "code" => $code
            ]);            

            if($auto){
                $auto->data([
                    'sold' => 1,
                    'sold_date' => new \DateTime("now")
                ]);
                $this->spot->mapper("App\Panoram")->save($auto);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($auto, new Panoram);
                $data['data'][]= $fractal->createData($resource)->toArray();
            }
        }
    }

    $data['status'] = 'ok';
    $data['message'] = 'El vehículo fue establecido como vendido';

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/panos/disponible", function ($request, $response, $arguments) {
    $body = $request->getParsedBody();
    $codes = $body['codes'];

    if(count($codes)){
        foreach($codes as $code){
            $auto = $this->spot->mapper("App\Panoram")->first([
                "code" => $code
            ]);            

            if($auto){
                $auto->data(['sold' => 0]);
                $this->spot->mapper("App\Panoram")->save($auto);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($auto, new Panoram);
                $data['data'][]= $fractal->createData($resource)->toArray();

            }
        }
    }

    $data['status'] = 'ok';
    $data['message'] = 'El vehículo fue establecido como disponible';

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/panos/renovar", function ($request, $response, $arguments) {
    $body = $request->getParsedBody();
    $codes = $body['codes'];

    if(count($codes)){
        foreach($codes as $code){
            $auto = $this->spot->mapper("App\Panoram")->first([
                "code" => $code
            ]);            

            if($auto){
                $auto->data([
                    'paused' => 0,
                    'enabled_until' => new \DateTime("now +" . getenv('APP_AD_DUE'))
                ]);
                $this->spot->mapper("App\Panoram")->save($auto);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($auto, new Panoram);
                $data['data'][]= $fractal->createData($resource)->toArray();

            }
        }
    }

    $data['status'] = 'ok';
    $data['message'] = 'El vehículo fue renovado';

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/datos", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("You are not allowed to get profiles.", 403);
    }

    $mapper = $this->spot->mapper("App\User")->first([
        "id" => $this->token->decoded->uid
    ]);

    if( ! $mapper){
        throw new NotFoundException("Usuario no encontrado", 404);        
    }

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($mapper, new User);
    $data = $fractal->createData($resource)->toArray(); 

    $um = $this->spot->mapper("App\UserMessage")->query("SELECT users_messages.* FROM users_messages WHERE recipient_id = {$this->token->decoded->uid} GROUP BY users_messages.pan_id ORDER BY created DESC LIMIT 2");

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($um, new UserMessage);
    $data['messages'] = $fractal->createData($resource)->toArray();

    foreach($data['messages']['data'] as $i => $usermessage){
        $usermessagedata = $this->spot->mapper("App\UserMessage")->query("SELECT COUNT(*) as total FROM users_messages WHERE user_id = {$this->token->decoded->uid} OR recipient_id = {$this->token->decoded->uid} AND users_messages.pan_id = {$usermessage['vehicle']['id']}");
        $data['messages']['data'][$i]['count'] = $usermessagedata[0]->total;
    }

    $message_sent = $this->spot->mapper("App\UserMessage")->query("SELECT users_messages.id  FROM users_messages WHERE user_id = {$this->token->decoded->uid} LIMIT 1")
        ->count();

    $data['message_sent'] = $message_sent;

    $published = $this->spot->mapper("App\Panoram")->query("SELECT panorams.id FROM panorams WHERE user_id = {$this->token->decoded->uid} AND enabled = 1 LIMIT 1")
        ->count();

    $data['published'] = $published;

    $warranty = $this->spot->mapper("App\Panoram")->query("SELECT panorams.id FROM panorams WHERE user_id = {$this->token->decoded->uid} AND warranty = 1 LIMIT 1")
        ->count();

    $data['warranty'] = $warranty;

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/datos/upload", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to list panorams.", 403);
    }

    $user = $this->spot->mapper("App\User")->first([
        "id" => $this->token->decoded->uid
    ]);

    if( ! $user){
        throw new NotFoundException("User not found.", 404);        
    }

    $data = [];
    $valid_exts = explode(',',getenv('S3_EXTENSIONS'));// valid extensions
    $max_size = getenv('APP_IMAGE_UPLOAD_MAX') * 1024; // max file size in bytes
    $ext_error = "Alguna de las imágenes no pudieron ser cargadas. Asegurate que tengan alguna de estas extensiones: " . implode(", ", $valid_exts);
    $size_error = "Alguna de las imágenes no pudieron ser cargadas. Asegurate que no sean mas pesadas que " . (ceil($max_size / 1024) / 1000) . 'M';

    if(is_uploaded_file($_FILES['image']['tmp_name']) ){
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $valid_exts)){
            if($_FILES['image']['size'] < $max_size){
                $store = bucket_store($_FILES['image']['tmp_name'],getenv('S3_PROFILE_RESOLUTIONS'),'users');
                if(empty($store['error'])) {
                    if(!empty($user->picture)){

                        $fn = substr($user->picture, strrpos($user->picture, '/') + 1);
                        $resolutions = explode(',',getenv('S3_PROFILE_RESOLUTIONS'));

                        foreach($resolutions as $res){
                            $parts = explode('x',$res);
                        }
                    }

                    $data['url'] = getenv('BUCKET_URL') . '/users/' . $store['key'];
                    $user->data(['picture' => $data['url']]);
                    $this->spot->mapper("App\User")->save($user);                
                } else {
                    $data[$i]['error'] = $store['error'];
                }
            } else {
                $data[$i]['error'] = $size_error;
            }
        } else {
            $data[$i]['error'] = $ext_error;
        }            
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/datos/completar", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("You are not allowed to get messages.", 403);
    }

    $user = $this->spot->mapper("App\User")->first([
        "id" => $this->token->decoded->uid
    ]);

    if( ! $user){
        throw new NotFoundException("User not found.", 404);        
    }

    $data = [];
    $body = $request->getParsedBody();

    $parts = explode(" ", trim($body["name"]));

    if(count($parts)>1){
        $data["last_name"] = array_pop($parts);
        $data["first_name"] = implode(" ", $parts);
    } else {
        $data["first_name"] = $body["name"];
        $data["last_name"] = "";
    }

    $data['picture'] = $user->picture;

    $valid_exts = explode(',',getenv('S3_EXTENSIONS'));// valid extensions
    $max_size = getenv('APP_IMAGE_UPLOAD_MAX') * 1024; // max file size in bytes
    $ext_error = "Alguna de las imágenes no pudieron ser cargadas. Asegurate que tengan alguna de estas extensiones: " . implode(", ", $valid_exts);
    $size_error = "Alguna de las imágenes no pudieron ser cargadas. Asegurate que no sean mas pesadas que " . (ceil($max_size / 1024) / 1000) . 'M';

    if(is_uploaded_file($_FILES['image']['tmp_name']) ){
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $valid_exts)){
            if($_FILES['image']['size'] < $max_size){

                $store = bucket_store($_FILES['image']['tmp_name'],getenv('S3_PROFILE_RESOLUTIONS'),'users');

                if(empty($store['error'])) {
                    if(!empty($user->picture)){

                        $fn = substr($user->picture, strrpos($user->picture, '/') + 1);
                        $resolutions = explode(',',getenv('S3_PROFILE_RESOLUTIONS'));

                        foreach($resolutions as $res){
                            $parts = explode('x',$res);
                        }
                    }

                    $data['picture'] = $store['key'];
                } else {
                    $data[$i]['error'] = $store['error'];
                }
            } else {
                $data[$i]['error'] = $size_error;
            }
        } else {
            $data[$i]['error'] = $ext_error;
        }  
    }

    $user->data([
        'picture' => $data['picture'],
        'first_name' => $data["first_name"],
        'last_name' => $data["last_name"],
        'username' => \set_username($data["first_name"].$data["last_name"])
    ]);

    $this->spot->mapper("App\User")->save($user);

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));    

});

$app->post("/perfil/datos/actualizar", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("You are not allowed to get messages.", 403);
    }

    $user = $this->spot->mapper("App\User")->first([
        "id" => $this->token->decoded->uid
    ]);

    if( ! $user){
        throw new NotFoundException("User not found.", 404);        
    }

    $body = $request->getParsedBody();
    $parts = explode(" ", trim($body["name"]));

    if(count($parts)>1){
        $data["last_name"] = array_pop($parts);
        $data["first_name"] = implode(" ", $parts);
    } else {
        $data["first_name"] = $body["name"];
        $data["last_name"] = "";
    }

    $data["username"] = \set_username($data["first_name"].$data["last_name"]);

    if( ! empty($body['NewPassword'])){
        if($user->password === sha1($body['password'].getenv('APP_HASH_SALT'))){
            $data["password"] = sha1($body['NewPassword'].getenv('APP_HASH_SALT'));
        } else {
            $data["error"] = "La contraseña es incorrecta.";
        }
    }


    if(empty($data["error"])){
        $user->data($data);
        $this->spot->mapper("App\User")->save($user);
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/mensajes", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("You are not allowed to get messages.", 403);
    }

    $threads_all = $this->spot->mapper("App\UserMessage")->query("SELECT id, user_id, recipient_id, pan_id FROM users_messages WHERE user_id = {$this->token->decoded->uid} OR recipient_id = {$this->token->decoded->uid} ORDER BY created DESC");

    $ids = [];
    $ctl = [];
    
    foreach($threads_all as $t){
        $other_id = $t->user_id === $this->token->decoded->uid ? $t->recipient_id : $t->user_id;
        if(!in_array($other_id."~".$t->pan_id, $ctl)){
            $ctl[] = $other_id."~".$t->pan_id;
            $ids[] = $t->id;
        }
    }

    $threads = $this->spot->mapper("App\UserMessage")->where(["id" => $ids])
        ->order(['created' => 'DESC']);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Collection($threads, new UserMessage);
    $data = $fractal->createData($resource)->toArray();

    foreach($data["data"] as $i => $tab){
        $other_id = $tab["user_id"] === $this->token->decoded->uid ? $tab["recipient_id"] : $tab["user_id"];
        $message = $this->spot->mapper("App\Message")->query("SELECT users_messages.*, messages.content  FROM users_messages LEFT JOIN messages ON messages.id = users_messages.message_id WHERE users_messages.pan_id = {$tab['pan_id']} AND (users_messages.user_id = {$this->token->decoded->uid} OR users_messages.recipient_id = {$this->token->decoded->uid}) AND (users_messages.user_id = {$other_id} OR users_messages.recipient_id = {$other_id}) GROUP BY users_messages.message_id  ORDER BY messages.created ASC");

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Collection($message, new Message);
        $data["data"][$i]["lines"] = $fractal->createData($resource)->toArray();
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/mensajes/enviar/{code}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("You are not allowed to send messages.", 403);
    }

    $mapper = $this->spot->mapper("App\Panoram")->first([
        "code" => $request->getAttribute('code'),
        "enabled" => 1,
        "deleted" => 0
    ]);

    if( ! $mapper){
        throw new NotFoundException("Panoram not found.", 404);        
    }

    $body = $request->getParsedBody();

    $message = \send_message($this->token->decoded->uid,$body['recipient_id'],$mapper->id,$body['message']);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($message, new Message);
    $data = $fractal->createData($resource)->toArray();    

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->post("/perfil/{type}/{id}", function ($request, $response, $arguments) {

    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to update vehicle.", 403);
    }

    $uid = $this->token->decoded->uid;
    $id = $request->getAttribute('id');
    $type = $request->getAttribute('type');
    $state = $request->getParam('state');

    $body = [
        'user_id' => $uid,
        'pan_id' => $id,
        'type' => $type
    ];

    $user_vehicle = $this->spot->mapper("App\UserPanoram")->first($body);

    if($state){
        if( ! $user_vehicle){
            $user_vehicle = new UserPanoram($body);
            $this->spot->mapper("App\UserPanoram")->save($user_vehicle);
        }
    } else {
        if($user_vehicle){
            $this->spot->mapper("App\UserPanoram")->delete($user_vehicle);
        }
    }

    $data['status'] = "success";
    $data['message'] = "Preference updated";

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));  
});

$app->post("/perfil-publico/{slug}", function ($request, $response, $arguments) {
    
    if (false === $this->token->decoded->uid) {
        throw new ForbiddenException("Token not allowed to show users.", 403);
    }

    $data["status"] = "error";

    $user = $this->spot->mapper("App\User")->first([
        "username" => str_replace("@","",$request->getAttribute('slug')),
        "validated" => 1
    ]);

    if($user){
        if($user->id === $this->token->decoded->uid){
            $data["status"] = "redirect";
            $data["redirect_url"] = "/perfil-usuario/datos";
        } else {
            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($user, new User);
            $data = $fractal->createData($resource)->toArray(); 
            $data["status"] = "success";
        }
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});