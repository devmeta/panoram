<?php 

$app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html');
})->setName('inicio');

$app->get('/transmitir/{code}', function ($request, $response, $args) {
    return $this->view->render($response, 'transmitir.html',[
        'code' => $args['code']
    ]);
})->setName('transmitir');

$app->get('/opener', function ($request, $response, $args) {
    $params = $request->getQueryParams();
    return $this->view->render($response, 'opener.html',[
        'token' => str_replace('__amp__','&',urldecode($params['token'])),
        'url' => $params['url']
    ]);
})->setName('opener');

$app->group('/perfil-usuario', function () use ($app) {
    $app->get('/transmisiones', function ($request, $response, $args) {
        return $this->view->render($response, 'perfil-usuario/transmisiones.html');
    })->setName('perfil-usuario-autos');

    $app->get('/datos', function ($request, $response, $args) {
        return $this->view->render($response, 'perfil-usuario/datos.html');
    })->setName('perfil-usuario-datos');  

    $app->get('/mensajes', function ($request, $response, $args) {
        return $this->view->render($response, 'perfil-usuario/mensajes.html');
    })->setName('perfil-usuario-mensajes');       
});

$app->get('/401', function ($request, $response, $args) {
    return $this->view->render($response, '401.html');
})->setName('401');

$app->get('/404', function ($request, $response, $args) {
    return $this->view->render($response, '404.html');
})->setName('404');

$app->get('/message', function ($request, $response, $args) {
    return $this->view->render($response, 'message.html');
})->setName('message');

// this will work for vehicles, users and pages as well
// Important: everything you put under ./templates/pages will be public

$app->get('/{slug}', function ($request, $response, $args) {

    $pageslug = str_replace('.','/',$args['slug']);

    if(file_exists(__DIR__ . '/../public/templates/pages/' .$pageslug . '.html')){
        return $this->view->render($response, 'pages/' . $pageslug . '.html',[
            'params' => $request->getQueryParams(),
            'currentyear' => date('Y')
        ]);
    }

    // vehicle
    $og = substr($args['slug'], strrpos($args['slug'], '---') + 1);
    $chunk = array_values(array_filter(explode("--",$og)));
    $id = strtok($args['slug'],"--");
    $photo = "";
    $title = "";
    $description = "";
    $host = $request->getUri()->getScheme().'://'.$request->getUri()->getHost();

    if(count($chunk) > 1){
        $photo = getenv('BUCKET_URL') . '/cams/' . $id . '/' .$chunk[0] . ".jpg";
        $title = str_replace("-"," ",$chunk[1]);
        //$description = str_replace("-"," ",$chunk[2]);
    }

    return $this->view->render($response, 'transmision.html',[
        'shorturl' => $host.'/'.strtok($args['slug'],"---"),
        'url' => $host.'/'.$args['slug'],
        'photo' => $photo,
        'title' => $title,
        //'description' => $description
    ]);

    return $this->view->render($response, '404.html');

})->setName('page');