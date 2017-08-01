<?php 

use App\User;
use App\Message;
use App\Email;
use App\UserMessage;
use App\Panoram;
use App\File;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;
use Slim\Views\Twig;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Intervention\Image\ImageManager;

$manager = NULL;

function auth_endpoints(){
    global $container; 

    if(!session_id()) {
        session_start();
    }

    $fb = new Facebook\Facebook([
      'app_id' => getenv("FB_APP_ID"),
      'app_secret' => getenv("FB_APP_SECRET"),
      'default_graph_version' => 'v2.2',
    ]);

    $helper = $fb->getRedirectLoginHelper();
    $permissions = ['email']; // Optional permissions
    $loginUrl = $helper->getLoginUrl($container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost() . '/facebook/signup', $permissions);

    $data['facebook'] = $loginUrl;

    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/../config/client_id.json');
    $client->setScopes(["profile","email"]);
    $loginUrl = $client->createAuthUrl();

    $data['google'] = $loginUrl;

    return json_encode($data);
    return $container->response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($data));
}

function copy_profile_photo($url){
    $mime = ".jpg";
    $key = strtolower(Base62::encode(random_bytes(6))) . $mime;
    $path = getenv('BUCKET_PATH') . '/users/';
    $dest = $path . $key;

    while(file_exists($dest)){
        $key = strtolower(Base62::encode(random_bytes(6))) . $mime;
    }

    copy($url, $dest);
    $resolutions = explode(',',getenv('S3_PROFILE_RESOLUTIONS'));
    $manager = new ImageManager();

    foreach($resolutions as $res){
        $parts = explode('x',$res);
        $resized = $manager->make($dest)
            ->orientate()
            ->fit((int) $parts[0],(int) $parts[1])
            ->save($path . $parts[0] . 'x' . $parts[1] . $key, (int) getenv('S3_QUALITY'));
    }

    return getenv('BUCKET_URL') . '/users/' . $key;
}

function get_bucket_size($size,$url){
    return str_replace( BUCKET_URL . '/', BUCKET_URL . '/' . $size, $url);
}

function replace_first($find, $replace, $subject) {
    // stolen from the comments at PHP.net/str_replace
    // Splits $subject into an array of 2 items by $find,
    // and then joins the array with $replace
    return implode($replace, explode($find, $subject, 2));
}

function sidebar_items($name,$filter){
    global $container;

    $parent = str_replace("_id","s",$name);
    $ws = [];

    foreach($filter as $w){
        if(strpos($w,$name.' IN') === false){
            $ws[] = $w;
        }
    }

    $where = implode(' AND ', $ws);

    $mapper = $container["spot"]->mapper("App\Panoram")
        ->query("SELECT panorams.{$name} FROM panorams WHERE {$where} AND panorams.{$name} IS NOT NULL AND panorams.{$name} > 0 GROUP BY panorams.{$name} ORDER BY panorams.{$name} ASC");
    $items = [];
    foreach($mapper as $pano){
        $items[] = $pano->doors;
    }    

    return $items;
}


function sidebar_component($name,$filter,$limit=null){

    global $container;
    
    $parent = str_replace("_id","s",$name);
    $ws = [];

    foreach($filter as $w){
        if(strpos($w,$name.' IN') === false){
            $ws[] = $w;
        }
    }

    $where = implode(' AND ', $ws);

    $mapper = $container["spot"]->mapper("App\Panoram")
        ->query("SELECT panorams.{$name}, {$parent}.title as title FROM panorams LEFT JOIN {$parent} ON {$parent}.id = panorams.{$name} WHERE {$where} AND panorams.{$name} IS NOT NULL GROUP BY panorams.{$name} ORDER BY title ASC LIMIT 1000");

    $items = [];

    foreach($mapper as $pano){
        $items[] = [
            'id' => $pano->{$name},
            'title' => strlen($pano->title) > 3 ? ucwords(strtolower($pano->title)) : $pano->title
        ];
    }

    $arr = [
        'slug' => $name,
        'items' => $items
    ];

    if(!empty($limit)) $arr['limit'] = $limit;
    return $arr;
}

function login_redirect($data){
    \log2file( __DIR__ . "/../logs/ecma-" . date('Y-m-d') . ".log",json_encode($data)); 
    return "<script>location.href = '" . \login_redirect_url($data) . "';</script>";
}

function login_redirect_url($data){
    return getenv('APP_URL') . "/opener?token=" . json_encode($data) . "&url=" . getenv('APP_REDIRECT_AFTER_LOGIN');
}

function log2file($path, $data, $mode="a"){
   $fh = fopen($path, $mode) or die($path);
   fwrite($fh,$data . "\n");
   fclose($fh);
   chmod($path, 0777);
}

function upload_database($files, $index, $url, $started, $pan) {

    global $container;

    $created = new DateTime();
    $created->setTimestamp($started);

    $body = [
        'pan_id' => $pan->id,
        'file_url' => getenv('BUCKET_URL') . '/' . $url,
        'filesize' => $files['size'][$index]
    ];

    $photo = new File($body);
    $id = $container["spot"]->mapper("App\File")->save($photo);
    $data = $photo->data(['created' => $created]);
    $container["spot"]->mapper("App\File")->save($data);

    return (int) $id;
}

function bucket_store($tmp_name,$res,$tag = ''){

    global $container, $manager;

    if(!$manager){
        $manager = new ImageManager();
    }

    $started = time();

    $jti = Base62::encode(random_bytes(8));

    while(is_file(getenv('BUCKET_PATH') . '/users/' . $jti . '.' . getenv('S3_EXTENSION')) ){
        $jti = Base62::encode(random_bytes(8));
    }

    $key = $jti . '.' . getenv('S3_EXTENSION');
    $resolutions = explode(',',$res);
    $path = getenv('BUCKET_PATH') . '/' . $tag . '/';
    $orig = $manager->make($tmp_name)
        ->orientate()
        ->save($path . $key, (int) getenv('S3_QUALITY'));

    foreach($resolutions as $res){
        $parts = explode('x',$res);
        $resized = $manager->make($tmp_name)
            ->orientate()
            ->fit((int) $parts[0],(int) $parts[1])
            ->save($path . $parts[0] . 'x' . $parts[1] . $key, (int) getenv('S3_QUALITY'));
    }

    $data['key'] = $key;
    $data['started'] = $started;

    return $data;
}

function get_preferences($uid){

    global $container;

    $items = ['fav' => [],'alert' => []];
    $mapper = $container["spot"]->mapper("App\UserPanoram")
        ->where(['user_id' => $uid])
        ->limit(1000);

    foreach($mapper as $item){
        $items[$item->type][] = $item->pan_id;
    }

    return $items;
}

function get_owned($uid){

    global $container;

    $items = [];
    $mapper = $container["spot"]->mapper("App\Panoram")
        ->where(['user_id' => $uid])
        ->limit(1000);

    foreach($mapper as $item){
        $items[] = $item->id;
    }

    return $items;
}

function set_token($uid){

    $now = new DateTime();
    $future = new DateTime("now +" . getenv('APP_JWT_EXPIRATION'));
    $jti = Base62::encode(random_bytes(16));

    $payload = [
        "uid" => $uid,
        "iat" => $now->getTimeStamp(),
        "exp" => $future->getTimeStamp(),
        "jti" => $jti
    ];

    $secret = getenv("APP_JWT_SECRET");
    $token = JWT::encode($payload, $secret, "HS256");   

    return $token;
}

// sender, recipient, item_id, parent_id, message
function send_message($sender_id,$recipient_id,$item_id,$content,$send_email = true){

    global $container;

    try {

        $status = 'ok';

        $sender = $container["spot"]->mapper("App\User")->first([
            'id' => $sender_id
        ]);

        $recipient = $container["spot"]->mapper("App\User")->first([
            'id' => $recipient_id
        ]);

        $item = $container["spot"]->mapper("App\Panoram")->first([
            'id' => $item_id
        ]);

        $message = new Message([
            'user_id' => $sender_id,
            'content' => $content
        ]);

        $id = $container["spot"]->mapper("App\Message")->save($message);

        $relation = new UserMessage([
            'user_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'message_id' => (int) $id,
            'pan_id' => $item_id
        ]);

        $container["spot"]->mapper("App\UserMessage")->save($relation);

        // send email
        if($send_email){
            \send_email("Recibiste un mensaje en " . getenv('APP_TITLE'), $recipient,'message.html',[
                'sender' => $sender,
                'item' => $item,
                'content' => $content
            ]);
        }
        
    } catch(\Exception $e){
        $message = $e->getMessage();
    }

    return $message;
}

function send_email($subject,$recipient,$template,$data,$debug = 0){

    global $container; 

    $view = new \Slim\Views\Twig( __DIR__ . '/../public/templates', [
        'cache' => false
    ]);

    $code = strtolower(Base62::encode(random_bytes(16)));

    while($container["spot"]->mapper("App\Email")->first(["code" => $code])){
        $code = strtolower(Base62::encode(random_bytes(16)));
    }

    $data['code'] = $code;
    $data['app_url'] = getenv('APP_URL');
    $data['recipient'] = $recipient;
    $data['api_url'] = $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost();

    $html = $view->fetch('emails/' . $template,$data);
    $full_name = $recipient->first_name + ' ' + $recipient->last_name;

    //Create a new PHPMailer instance
    $mail = new \PHPMailer;
    $mail->IsSMTP(); 
    $mail->SMTPDebug = $debug?:getenv('MAIL_SMTP_DEBUG');
    $mail->SMTPAuth = getenv('MAIL_SMTP_AUTH');
    $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE');
    $mail->Host = getenv('MAIL_SMTP_HOST');
    $mail->Port = getenv('MAIL_SMTP_PORT');
    $mail->CharSet = "utf8mb4";
    $mail->IsHTML(true);
    $mail->Username = getenv('MAIL_SMTP_ACCOUNT');
    $mail->Password = getenv('MAIL_SMTP_PASSWORD');
    $mail->setFrom(getenv('MAIL_FROM'), getenv('MAIL_FROM_NAME'));
    $mail->addReplyTo(getenv('MAIL_FROM'), getenv('MAIL_FROM_NAME'));
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = \html2text($html);
    $mail->addAddress($recipient->email, $full_name);

    $body = [
        'code' => $code,
        'subject' => $subject,
        'user_id' => $recipient->id,
        'email' => $recipient->email,
        'full_name' => $full_name,
        'content' => $html
    ];

    $email = new Email($body);
    $container["spot"]->mapper("App\Email")->save($email);

    //$mail->addAttachment('images/phpmailer_mini.png');
    $data = [];
    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['status'] =  "error";
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['status'] = "success";
    }

    return $data;
}

function html2text($Document) {
    $Rules = array ('@<style[^>]*?>.*?</style>@si',
                    '@<script[^>]*?>.*?</script>@si',
                    '@<[\/\!]*?[^<>]*?>@si',
                    '@([\r\n])[\s]+@',
                    '@&(quot|#34);@i',
                    '@&(amp|#38);@i',
                    '@&(lt|#60);@i',
                    '@&(gt|#62);@i',
                    '@&(nbsp|#160);@i',
                    '@&(iexcl|#161);@i',
                    '@&(cent|#162);@i',
                    '@&(pound|#163);@i',
                    '@&(copy|#169);@i',
                    '@&(reg|#174);@i',
                    '@&#(d+);@e'
             );
    $Replace = array ('',
                      '',
                      '',
                      '',
                      '',
                      '&',
                      '<',
                      '>',
                      ' ',
                      chr(161),
                      chr(162),
                      chr(163),
                      chr(169),
                      chr(174),
                      'chr()'
                );
  return preg_replace($Rules, $Replace, $Document);
}


function human_timespan_short($time){

    $str = "";
    $diff = time() - $time; // to get the time since that moment
    $diff = ($diff<1)? $diff*-1 : $diff;

    $Y = date('Y', $time);
    $n = date('n', $time);
    $w = date('w', $time);
    $wdays = ['dom','lun','mar','mié','jue','vie','sáb'];

    //if($diff < 86400){
    if($diff < (86400 / 4)){
        $str = date('H:i',$time); 
    } elseif($diff < 604800){
        $str = $wdays[$w];
    } elseif($Y != date('Y')){
        $str = date('j/n/y',$time);  
    } elseif($n != date('n')){
        $str = date('j/n',$time); 
    } else {
        $str = date('j',$time);  
    }

    return $str;
}

function human_timespan($time, $from = 0){

    $time = ($from?:time()) - $time; // to get the time since that moment
    $time = ($time<1)? $time*-1 : $time;
    $tokens = array (
        31536000 => 'año',
        2592000 => 'mes',
        604800 => 'semana',
        86400 => 'día',
        3600 => 'hora',
        60 => 'minuto',
        1 => 'segundo'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?($text=='mes'?'es':'s'):'');
    }
}

function slugify($text){

    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return strtolower(Base62::encode(random_bytes(8)));
    }

    return $text;
}

function set_username($intended){

    global $container; 

    if($intended == ""){
        $intended = strtolower(Base62::encode(random_bytes(8)));
    }

    $j=0;
    $username = $intended;

    while($container["spot"]->mapper("App\User")->first(["username" => \slugify($username)])){
        $j++;
        $username = $intended . $j;
    }

    return \slugify($username);
}