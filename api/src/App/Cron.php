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

namespace App;

require __DIR__ . "/../../routes/functions.php";

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;
use Spot\EventEmitter;
use Tuupola\Base62;
use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;
use Spot\Locator;
use Doctrine\DBAL\Query\QueryBuilder;
use Intervention\Image\ImageManager;


class Cron extends \Spot\Entity
{
    protected static $table = "cron";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "report" => ["type" => "text"],
            "success" => ["type" => "boolean", "value" => false],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public function transform(Cron $cron)
    {
        return [
            "id" => (integer) $cron->id ?: null,
            "report" => (string) $cron->report ?: "",
            "success" => !!$cron->success
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "report" => null,
            "success" => null
        ]);
    }

    public function alerts($onlytousers = null){

        global $container;

        $cache = [];
        $data = [];
        $udata = [];
        // relations
        $relations = $container["spot"]->mapper("App\UserPanoram")->query("SELECT users_panorams.*, panorams.model_id, users.first_name, users.last_name, users.email FROM users_panorams 
            LEFT JOIN panorams ON panorams.id = users_panorams.pan_id 
            LEFT JOIN users ON users.id = users_panorams.user_id 
            WHERE panorams.enabled = 1 
            AND type = 'alert' 
            AND panorams.deleted = 0 
            AND panorams.paused = 0 
            AND panorams.sold = 0 
            AND panorams.price > 0 
            AND panorams.schedule <> ''
            AND panorams.tel <> '' 
            AND panorams.mt_year > 1950 
            AND panorams.enabled_until > now()
            ORDER BY created DESC");

        foreach($relations as $row){

            if(empty($cache[$row->pan_id])){

                $related = $container["spot"]->mapper("App\Panoram")->query("SELECT panorams.* FROM panorams WHERE model_id = {$row->model_id} AND id <> {$row->pan_id} AND enabled = 1 AND paused = 0 AND deleted = 0 AND sold = 0 AND enabled_until > now() AND created > '" . date('Y-m-d H:i',strtotime('-1 week')) . "' AND price > 0 GROUP BY panorams.id ORDER BY created DESC");

                $cache[$row->pan_id] = $related;
            } else {
                $related = $cache[$row->pan_id];
            }

            if($related->count()){

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Collection($related, new Panoram);
                $items = $fractal->createData($resource)->toArray();

                if(empty($udata[$row->user_id])){
                    $udata[$row->user_id] = [
                        "user" => [
                            "email" => $row->email,
                            "first_name" => $row->first_name,
                            "last_name" => $row->last_name
                        ]
                    ];
                } 

                $udata[$row->user_id]["items"][] = $items["data"];
            }
        }

        foreach($udata as $user_id => $row){
            $items = [];

            if(!$onlytousers OR (is_array($onlytousers) AND in_array($user_id,$onlytousers))) {
                
                foreach($row["items"] as $items2){
                    foreach($items2 as $item){
                        $items[]= $item;
                    }
                }

                $data = [
                    "user" => $row["user"],
                    "items" => $items
                ];
            }

            \send_email("{$row['user']['first_name']}, tus alertas semanales",(object) $row["user"],'alert.html',$data);
        }
    }

    public function enable(){

        global $container;

        // enable all --- fot testing purposes

        /*
        $connection = $container["spot"]->mapper("App\Panoram")->connection();
        $qb = $connection->createQueryBuilder();
        $sql = $qb->update("panorams")
            ->set("enabled",1)
            ->execute();*/

        $disabled = $container["spot"]->mapper("App\Panoram")->all([
            'enabled' => 0
        ])->order(['id' => "ASC"]);

        $enabled_count = 0;

        foreach($disabled as $form){
            if($form->price > 0 AND count($form->photos)){
                $form->data(['enabled' => 1]);
                $container["spot"]->mapper("App\Panoram")->save($form);
                $enabled_count++;
            }
        }

        $cron = new Cron;

        $body = [
            'success' => 1,
            'report' => "Total enabled : " . $enabled_count
        ];

        $cron->data($body);
        $container["spot"]->mapper("App\Cron")->save($cron);

        print "Exit scheduled tasks.";
    }

    public function check_titles(){

        global $container;

        $rows = $container["spot"]->mapper("App\Panoram")->all()
            ->order(['id' => "ASC"]);

        foreach($rows as $mapper){

            $photo = "";
            $photo_name = "";
            $description = "";
            $titlechunk = [];
            $ctl = 0;

            foreach($mapper->photos as $mphoto){
                if(!$ctl AND $mphoto->photo_url){
                    $photo_name = $mphoto->photo_url;
                    $ctl = 1;
                }
            }

            if(strlen($photo_name)){
                $photo_name = substr($photo_name, strrpos($photo_name, '/') + 1);
                $photo_name = strtok($photo_name, ".");
                $photo = $photo_name;
            }

            if($mapper->brand->title){
                $titlechunk[] = $mapper->brand->title;
            }

            if($mapper->model->title){
                $titlechunk[] = $mapper->model->title;
            }

            if($mapper->version->title){
                $titlechunk[] = $mapper->version->title;
            }

            if($mapper->doors){
                $titlechunk[] = $mapper->doors . ' puertas';
            }

            if($mapper->kms){
                $titlechunk[] = $mapper->kms . ' kms';
            } else {
                $titlechunk[] = '0 km';
            }

            if($mapper->region->title){
                $titlechunk[] = $mapper->region->title;
            }

            if($mapper->extrainfo){
                $description = $mapper->extrainfo;
            }
            $title = urlencode(str_replace(['---','/'],'-',implode('--',[$photo,implode('-',str_replace(' ','-',$titlechunk)),$description])));
            
            //$body['kms'] = empty($body['kms']) ? 0 : $body['kms'];
            $title = substr($title,0,255);


            $body['title'] =  substr($title,0,255);
            $mapper->data($body);
            $container["spot"]->mapper("App\Panoram")->save($mapper);

        }
    }

    public function check_usernames(){

        global $container;

        $users = $container["spot"]->mapper("App\User")->all()
            ->order(['id' => "ASC"]);

        foreach($users as $user){

            $intended = \slugify($user->first_name.$user->last_name);
            $username = $intended;
            $j=0;

            while($user->username != $username AND $container["spot"]->mapper("App\User")->first(["username" => $username])){
                $j++;
                $username = $intended . $j;
            }

            if( $user->username != $username){
                $user->data(['username' => $username]);
                $container["spot"]->mapper("App\User")->save($user);
            }
        }
    }

    public function resize_photos(){

        global $container;

        $updated = 0;
        $photos = $container["spot"]->mapper("App\File")->all()
            ->order(['id' => "ASC"]);

        $resolutions = explode(',',getenv('S3_RESOLUTIONS'));
        $manager = $manager = new ImageManager();
        foreach($photos as $photo){
            $fn = substr($photo->photo_url, strrpos($photo->photo_url, '/') + 1);
            $fp = __DIR__ . '/../../bucket/' . $fn;
            print $fp . PHP_EOL;
            if(file_exists($fp)){
                foreach($resolutions as $res){
                    $parts = explode('x',$res);
                    $resized = $manager->make($fp)
                        ->orientate()
                        ->fit((int) $parts[0],(int) $parts[1])
                        ->save(__DIR__ . '/../../bucket/' . $parts[0] . 'x' . $parts[1] . $fn, (int) getenv('S3_QUALITY'));
                    $updated++;                        
                }
            }
        }

        print "Total photos updated: " . $updated . PHP_EOL;
    }

    public function resize_profile_photos(){

        global $container;

        $updated = 0;
        $sql = "SELECT id, picture FROM users WHERE picture LIKE '%ver-usados.s3.amazonaws.com%'";

        $users = $container["spot"]->mapper("App\User")->query($sql);

        $resolutions = explode(',',getenv('S3_PROFILE_RESOLUTIONS'));
        $manager = $manager = new ImageManager();
        foreach($users as $user){

            $tmp_name = str_replace('80x80','',$user->picture);
            print $tmp_name . PHP_EOL;
            foreach($resolutions as $res){

                $key = substr($tmp_name, strrpos($tmp_name, '/') + 1);

                $orig = $manager->make($tmp_name)
                    ->orientate()
                    ->save(__DIR__ . '/../../bucket/' . $key, (int) getenv('S3_QUALITY'));

                $parts = explode('x',$res);
                $resized = $manager->make($tmp_name)
                    ->orientate()
                    ->fit((int) $parts[0],(int) $parts[1])
                    ->save(__DIR__ . '/../../bucket/' . $parts[0] . 'x' . $parts[1] . $key, (int) getenv('S3_QUALITY'));

                $data = $container["spot"]->mapper("App\User")
                    ->where(['id' => $user->id])
                    ->first();

                $data->data(['picture' =>  getenv('BUCKET_URL') . '/80x80' . $key]);
                $container["spot"]->mapper("App\User")->save($data);

                $updated++;                    
            }
        }

        print "Total resize_profile_photos updated: " . $updated . PHP_EOL;
    }    


    public function fix_s3_broken_photos(){

        global $container;

        $updated = 0;
        $sql = "SELECT id, photo_url FROM photos WHERE id < 78 AND pan_id IS NOT NULL";
        $photos = $container["spot"]->mapper("App\File")->query($sql);

        $resolutions = explode(',',getenv('S3_RESOLUTIONS'));
        $manager = $manager = new ImageManager();
        foreach($photos as $photo){

            $tmp_name = $photo->photo_url;
            print $tmp_name . PHP_EOL;
            foreach($resolutions as $res){

                $key = substr($tmp_name, strrpos($tmp_name, '/') + 1) . getenv('S3_EXTENSION');

                $orig = $manager->make($tmp_name)
                    ->orientate()
                    ->save(__DIR__ . '/../../bucket/' . $key, (int) getenv('S3_QUALITY'));

                $parts = explode('x',$res);
                $resized = $manager->make($tmp_name)
                    ->orientate()
                    ->fit((int) $parts[0],(int) $parts[1])
                    ->save(__DIR__ . '/../../bucket/' . $parts[0] . 'x' . $parts[1] . $key, (int) getenv('S3_QUALITY'));

                $data = $container["spot"]->mapper("App\File")
                    ->where(['id' => $photo->id])
                    ->first();

                $data->data(['photo_url' =>  getenv('BUCKET_URL') . '/' . $key]);
                $container["spot"]->mapper("App\File")->save($data);

                $updated++;                    
            }
        }

        print "Total resize_profile_photos updated: " . $updated . PHP_EOL;
    }       


    public function currency_convert(){

        global $container;

        // Initialize cURL:
        $endpoint = 'live';
        $access_key = getenv('API_CURRENCY_ID');
        $base_currency = 'USD';
        $done_currency = 0; 
        $currencies_ref = [];
        $currencies_rel = [];

        // initialize CURL:
        $ch = curl_init('http://apilayer.net/api/'.$endpoint.'?access_key='.$access_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // get the (still encoded) JSON data:
        $json = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response:
        $conversionResult = json_decode($json, true);

        if( $conversionResult AND ! empty($conversionResult['quotes'])){


            // first important, change pivot to ars since its our primary currency in frontend.

            $base = $conversionResult['quotes']['USDARS'];
            foreach($conversionResult['quotes'] as $iso_codes => $value){
                $currencies_rel[\replace_first('USD','',$iso_codes)] = (float) $base / $value;
            }

            $currencies = $container["spot"]->mapper("App\Currency")
                ->all()
                ->order(['id' => "ASC"]);

            foreach($currencies as $c){
                $rate = 1;
                if($c->iso_code != "ARS"){
                    $rate = $rate / $currencies_rel[$c->iso_code];
                }
                $c->data([
                    'rate' => $rate
                ]);
                $container["spot"]->mapper("App\Currency")->save($c);
                $done_currency++;
            }
        }

        $currencies = $container["spot"]->mapper("App\Currency")
            ->where(['iso_code <>' => $base_currency])
            ->order(['id' => "ASC"]);

        foreach($currencies as $c){
            // direct write skip orm
            $connection = $container["spot"]->mapper("App\Panoram")->connection();
            $qb = $connection->createQueryBuilder();
            $sql = $qb->update("panorams")
                ->where("currency_id = " . $c->id)
                ->where("price > 0")
                ->where("deleted = 0")
                ->set("price_ars","price * " . $currencies_rel[$c->iso_code]);
            $rows[$c->iso_code] = $qb->execute();
        }

        print "Total de monedas convertidas: " . $done_currency . PHP_EOL;
        foreach($rows as $iso_code => $q){
            print "Total de vehículos actualizados: " . $iso_code .  " -> " . $q . PHP_EOL;
        }        
    }
}