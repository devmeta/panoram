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

use App\User;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Tuupola\Base62;

$app->post("/ecmalog", function($request, $response, $arguments){
    $body = $request->getParsedBody();
    $line = date('H:i:s') . ' - ' . trim($body['line']);
    \log2file( __DIR__ . "/../logs/ecma-" . date('Y-m-d') . ".log",$line); 

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode([]));      
});

$app->post("/refresh-token", function ($request, $response, $arguments) {

    $user = $this->spot->mapper("App\User")->first([
        "id" => $this->token->decoded->uid
    ]);

    $data = [];

    if ($user) {
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($user, new User);
        $data = $fractal->createData($resource)->toArray();
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->get("/test-email/{id}", function ($request, $response, $arguments) {

    $user = $this->spot->mapper("App\User")->first([
        "id" => $request->getAttribute('id')
    ]);

    $data["status"] = "error";
    $data["message"] = "Correo de prueba no enviado, no se encontro el usuario con id " . $request->getAttribute('id');

    if( $user ){
        $body['readable_password'] = "whatever";
        $body['email_encoded'] = "whatever";
        $sent = \send_email("Bienvenido a " . getenv('APP_TITLE'),$user,'welcome.html',$body,2);

        if($sent['status']=='success'){
            $data["status"] = "ok";
            $data["message"] = "Correo de prueba enviado";
        } else {
            $data["status"] = "error";
            $data["message"] = "Correo de prueba no enviado";
        }
    } 
    
    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));  

});

$app->post("/password-change", function ($request, $response, $arguments) {

    $body = $request->getParsedBody();
    $new_password = $body['new_password'];
    $token = $body['token'];

    $user = $this->spot->mapper("App\User")->first([
        "password_token" => $token
    ]);

    $data["status"] = "error";
    $data["message"] = "Token de actualización de contraseña incorrecta";

    if( $user ){
        //$password = strtolower(Base62::encode(random_bytes(16)));
        $body['password'] = $new_password;
        $body['email'] = $user->email;
        $body['first_name'] = $user->first_name;
        $user->data([
            'password' => sha1($new_password.getenv('APP_HASH_SALT')),
            'password_token' => ""
        ]);

        $this->spot->mapper("App\User")->save($user);

        \send_email("Actualizaste tu contraseña",$user,'password-change.html',$body);

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($user, new User);
        $data = $fractal->createData($resource)->toArray();
        $data["status"] = "ok";
        $data["redirect_url"] = \login_redirect_url($data['data']);
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));    
});

$app->post("/password-change-confirmation", function ($request, $response, $arguments) {
    
    $body = $request->getParsedBody();
    $email = trim($body['email']);

    $user = $this->spot->mapper("App\User")->first([
        "email" => $email
    ]);

    if( $user ){

        $password_token = strtolower(Base62::encode(random_bytes(16)));
        $body['password_token'] = $password_token;
        $user->data(['password_token' => $password_token]);
        $this->spot->mapper("App\User")->save($user);

        $sent = \send_email("Solicitaste ayuda con tu contraseña",$user,'password-change-confirmation.html',$body);

        if($sent['status']=='success'){
            $data["status"] = "ok";
            $data["message"] = "Se envió correo de confirmación";
        }
    } else {
        $data["status"] = "error";
        $data["message"] = "No se encontró el usuario " . $email;        
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});


$app->post("/contacto", function ($request, $response, $arguments) {

    $body = $request->getParsedBody();
    
    $user = (object) [
        'first_name' => "Administrador",
        'last_name' => "",
        'email' => getenv('MAIL_CONTACT')
    ];

    \send_email("Nueva Consulta desde la Web",$user,'contact.html',$body);

    $data["status"] = "ok";
    
    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));  

});

$app->get("/google/signup", function ($request, $response, $arguments) {

    if(!session_id()) {
        session_start();
    }

    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/../config/client_id.json');
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    //      \Firebase\JWT\JWT::$leeway = 60;

    if ($client->getAccessToken()) {
        $decoded = $client->verifyIdToken();

        $body['google_id'] = $decoded['sub'];
        $body['email'] = $decoded['email'];
        $body['first_name'] = $decoded['given_name'];
        $body['last_name'] = $decoded['family_name'];
        $body['picture'] = $decoded['picture'];

        $user = $this->spot->mapper("App\User")->first([
            "email" => $decoded['email']
        ]);

        if( ! $user ){
            $password = strtolower(Base62::encode(random_bytes(16)));
            $body['password'] = sha1($password.getenv('APP_HASH_SALT'));
            $body['username'] = \set_username($body['first_name'].$body['last_name']);
            $user = new User($body);
            $emaildata = $body;            
            $emaildata['readable_password'] = $password;
            $emaildata['email_encoded'] = Base62::encode($decoded['email']);
            \send_email("Bienvenido a " . getenv('APP_TITLE'),$user,'welcome.html',$emaildata);
        } else {

            $existing_ids = $this->spot->mapper("App\User")->all([
                "id <>" => $user->id,
                "google_id" => $body['google_id']
            ]);

            if($existing_ids){
                foreach($existing_ids as $existing_id){
                    $existing_body = $existing_id->data(['google_id' => NULL]);
                    $this->spot->mapper("App\User")->save($existing_body);
                }
            }
        }


        // copy to local 
        $body['picture'] = copy_profile_photo($body['picture']);
        $user->data($body);
        $this->spot->mapper("App\User")->save($user);

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($user, new User);
        $data = $fractal->createData($resource)->toArray();

        //$data['data']['picture'] = str_replace('&','__amp__',$data['data']['picture']);

        echo \login_redirect($data['data']);
        exit;
    }
});

$app->get("/facebook/signup", function ($request, $response, $arguments) {

    if(!session_id()) {
        session_start();
    }

    $fb = new Facebook\Facebook([
      'app_id' => getenv("FB_APP_ID"),
      'app_secret' => getenv("FB_APP_SECRET"),
      'default_graph_version' => 'v2.2',
    ]);

    $helper = $fb->getRedirectLoginHelper();
    $_SESSION['FBRLH_state'] = $_GET['state'];

    try {
      $accessToken = $helper->getAccessToken();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
      // When Graph returns an error
      echo 'Graph returned an error: ' . $e->getMessage();
      exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      exit;
    }

    if (! isset($accessToken)) {
      if ($helper->getError()) {
        header('HTTP/1.0 401 Unauthorized');
        echo "Error: " . $helper->getError() . "\n";
        echo "Error Code: " . $helper->getErrorCode() . "\n";
        echo "Error Reason: " . $helper->getErrorReason() . "\n";
        echo "Error Description: " . $helper->getErrorDescription() . "\n";
      } else {
        header('HTTP/1.0 400 Bad Request');
        echo 'Bad request';
      }
      exit;
    }

    if ($accessToken !== null) {
        $oResponse = $fb->get('/me?fields=id,first_name,last_name,email,picture.type(large)', $accessToken);
        $decoded = $oResponse->getDecodedBody();

        $body['facebook_id'] = $decoded['id'];
        $body['email'] = $decoded['email'];
        $body['first_name'] = $decoded['first_name'];
        $body['last_name'] = $decoded['last_name'];
        $body['picture'] = $decoded['picture']['data']['url'];

        $user = $this->spot->mapper("App\User")->first([
            "email" => $decoded['email']
        ]);

        if( ! $user ){
            $password = strtolower(Base62::encode(random_bytes(16)));
            $body['password'] = sha1($password.getenv('APP_HASH_SALT'));
            $body['username'] = \set_username($body['first_name'].$body['last_name']);
            $user = new User($body);
            $emaildata = $body;
            $emaildata['readable_password'] = $password;
            $emaildata['email_encoded'] = Base62::encode($decoded['email']);
            \send_email("Bienvenido a " . getenv('APP_TITLE'),$user,'welcome.html',$emaildata);
        } else {

            $existing_ids = $this->spot->mapper("App\User")->all([
                "id <>" => $user->id,
                "facebook_id" => $body['facebook_id']
            ]);

            if($existing_ids){
                foreach($existing_ids as $existing_id){
                    $existing_body = $existing_id->data(['facebook_id' => NULL]);
                    $this->spot->mapper("App\User")->save($existing_body);
                }
            }
        }

        // copy to local 
        $body['picture'] = copy_profile_photo($body['picture']);
        $user->data($body);
        $this->spot->mapper("App\User")->save($user);

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($user, new User);
        $data = $fractal->createData($resource)->toArray();

        $data['data']['picture'] = str_replace('&','__amp__',$data['data']['picture']);
    }

    echo \login_redirect($data['data']);
    exit;
});

$app->post("/ingresar", function ($request, $response, $arguments) {

    $body = $request->getParsedBody();

    $user = $this->spot->mapper("App\User")->first([
        "email" => $body['email'],
        'password' => sha1($body['password'].getenv('APP_HASH_SALT'))
    ]);

    if($user){

        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($user, new User);
        $data = $fractal->createData($resource)->toArray();

        $data["status"] = "ok";
        $data["message"] = "Usuario válido";

    } else {
        $data["status"] = "error";
        $data["message"] = "Acceso inválido";
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));  
});

$app->post("/registro", function ($request, $response, $arguments) {
    
    $body = $request->getParsedBody();
    $data["status"] = "";

    if( empty($body['email']) OR empty($body['password'])){
        $data["status"] = "error";
        $data["message"] = "No hay suficientes datos";
    }

    if( $data["status"] != "error" ){

        $user = $this->spot->mapper("App\User")->first([
            "email" => $body['email']
        ]);

        if($user) {

            $data["status"] = "error";
            $data["message"] = "Parece que ya existe una cuenta con este email. <a href='/olvide-mi-contrasena'>Recuperar contraseña</a>";

        } else {

            $hash = sha1($body['password'].getenv('APP_HASH_SALT'));
            $user = new User([
                "email" => $body["email"], 
                "password" => $hash,
                "username" => \set_username("")
            ]);

            $this->spot->mapper("App\User")->save($user);
            
            $body['first_name'] = "";
            $body['last_name'] = "";
            $body['readable_password'] = $body["password"];
            $body['email_encoded'] = Base62::encode($body['email']);

            \send_email("Bienvenido a " . getenv('APP_TITLE'),$user,'welcome.html',$body);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($user, new User);
            $data = $fractal->createData($resource)->toArray();

            $data["status"] = "ok";
            $data["message"] = "Usuario creado";
        }
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
});

$app->get("/validar/{encoded}", function ($request, $response, $arguments) {

    $user = $this->spot->mapper("App\User")->first([
        "email" => Base62::decode($request->getAttribute('encoded'))
    ]);

    if( ! $user){
        $data["status"] = "error";
        $data["message"] = "No se encontró el usuario";
    } else {

        $body = $user->data(['validated' => 1]);
        $this->spot->mapper("App\User")->save($body);

        $data["status"] = "ok";
        $data["message"] = "Tu cuenta ha sido validada";
    }

    $view = new \Slim\Views\Twig('templates', [
        'cache' => false
    ]);

    $params = $request->getQueryParams();
    $data["redirect"] = getenv('APP_URL');

    if( ! empty($params['redirect'])){
        $data["redirect"] = getenv('APP_URL') . $params['redirect'];
    }

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($user, new User);
    $data = $fractal->createData($resource)->toArray();

    echo \login_redirect($data['data']);

    exit;
});