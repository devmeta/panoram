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

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;
use Spot\EventEmitter;
use Tuupola\Base62;
use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Panoram extends \Spot\Entity
{
    
    protected static $table = "panorams";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "brand_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "model_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "version_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "region_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "city_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "gear_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "fuel_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "color_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "code" => ["type" => "string", "length" => 255],
            "title" => ["type" => "string", "length" => 255],
            "extrainfo" => ["type" => "text", "charset" => "utf8mb4_general_ci"],
            "warranty" => ["type" => "boolean", "value" => false, "notnull" => true],
            "warranty_requested" => ["type" => "boolean", "value" => 0, "notnull" => true],
            "financing" => ["type" => "boolean", "value" => false, "notnull" => true],
            "sold" => ["type" => "boolean", "value" => false, "notnull" => true],
            "sold_date"   => ["type" => "datetime", "value" => new \DateTime()],
            "paused" => ["type" => "boolean", "value" => false, "notnull" => true],
            "paused_date"   => ["type" => "datetime", "value" => new \DateTime()],
            "price" => ["type" => "decimal", "precision" => 10, "scale" => 0, "value" => 0, "default" => 0, "notnull" => true],
            "price_ars" => ["type" => "decimal", "precision" => 21, "scale" => 2, "value" => 0, "default" => 0, "notnull" => true],
            "agent" => ["type" => "string", "length" => 255, "value" => "", "notnull" => true],
            "tel" => ["type" => "string", "length" => 255, "value" => "", "notnull" => true],
            "lat" => ["type" => "string", "length" => 255, "value" => "", "notnull" => true],
            "lng" => ["type" => "string", "length" => 255, "value" => "", "notnull" => true],
            "schedule" => ["type" => "string", "length" => 255, "value" => "", "notnull" => true],
            "currency_id" => ["type" => "integer", "unsigned" => true, "value" => 1, "default" => 1, 'index' => true],
            "mt_year" => ["type" => "integer", "unsigned" => true, "default" => 0],
            "condition" => ["type" => "integer", "unsigned" => true, "value" => 1,"default" => 1, "notnull" => true],
            "geolocation" => ["type" => "integer", "unsigned" => true, "value" => 1,"default" => 1, "notnull" => true],
            "kms" => ["type" => "integer", "unsigned" => true, "value" => 0, "default" => 0, "notnull" => true],
            "doors" => ["type" => "integer", "unsigned" => true, "value" => 0, "default" => 0, "notnull" => true],
            "hits" => ["type" => "integer", "unsigned" => true, "value" => 0, "default" => 0, "notnull" => true],
            "enabled" => ["type" => "boolean", "value" => true, "notnull" => true],
            "deleted" => ["type" => "boolean", "value" => false, "notnull" => true],
            "enabled_until"   => ["type" => "datetime", "value" => new \DateTime()],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'brand' => $mapper->belongsTo($entity, 'App\Brand', 'brand_id'),
            'model' => $mapper->belongsTo($entity, 'App\Model', 'model_id'),
            'version' => $mapper->belongsTo($entity, 'App\Version', 'version_id'),
            'region' => $mapper->belongsTo($entity, 'App\Region', 'region_id'),
            'city' => $mapper->belongsTo($entity, 'App\City', 'city_id'),
            'fuel' => $mapper->belongsTo($entity, 'App\Fuel', 'fuel_id'),
            'gear' => $mapper->belongsTo($entity, 'App\Gear', 'gear_id'),
            'color' => $mapper->belongsTo($entity, 'App\Color', 'color_id'),
            'requests' => $mapper->hasMany($entity, 'App\UserMessage','pan_id')->group(['user_id']),
            'files' => $mapper->hasMany($entity, 'App\File','pan_id')->order(['created' => 'ASC']),
            'props' => $mapper->hasManyThrough($entity, 'App\Prop', 'App\PanoramProp','prop_id','pan_id')->order(['order' => 'ASC'])
        ];
    }

    public function transform(Panoram $pan)
    {
        $files = $props = [];

        foreach($pan->files as $photo){
            $created = $photo->created;
            if(is_object($created)) $created_date = $created->format('U');
            $files[] = [
                'id' => $photo->id,
                'photo_url' => $photo->file_url,
                'position' => $photo->position,
                'created' => $created_date,
                'filesize'  => $photo->filesize
            ];
        }

        foreach($pan->props as $prop){
            $props[$prop->group->slug][] = $prop->title;
        }

        $until = $pan->enabled_until;
        if(is_object($until)) $until_date = $until->format('U');

        $created = $pan->created;
        if(is_object($created)) $created_date = $created->format('U');

        $duration = false;

        if(count($files)){
            $duration = \human_timespan($files[0]['created'],$files[count($files)-1]['created']);
        }

        return [
            "id" => (integer) $pan->id ?: null,
            "public_id" => sprintf("%'.09d\n", $pan->id),
            "encoded" => Base62::encode(sprintf("%'.09d\n", $pan->id)),
            "code" => (string) $pan->code ?: null,
            "lat" => (string) $pan->lat ?: null,
            "lng" => (string) $pan->lng ?: null,
            "title" => (string) $pan->title ? substr($pan->title, strpos($pan->title, "--") + 2) : null,
            "extrainfo" => (string) $pan->extrainfo ?: null,
            "hits" => (integer) $pan->hits ?: 0,
            "enabled" => !!$pan->enabled,
            "paused" => !!$pan->paused,
            "condition" => (integer) $pan->condition ?: 0,
            "geolocation" => (integer) $pan->geolocation ?: 0,
            "requests" => $pan->requests->count(),
            "enabled_until" => \human_timespan($until_date),
            "created" => \human_timespan_short($created_date),
            "duration" => $duration?:"n/a",
            "active" => ($until_date > time()),
            "file" => ! empty($files)?$files[0]:null,
            "files" => $files,
            "props" => $props,
            "user" => [
                "id" => (integer) $pan->user_id ? : null,
                "title" => ((string) $pan->user->first_name ? : "") . ' ' . ((string) $pan->user->last_name ? : ""),
                "email" => (string) $pan->user->email ? : null,
                "picture" => (string) $pan->user->picture ? : null
            ],
            "region" => [
                "id" => (integer) $pan->region_id ?: null,
                "title" => (string) $pan->region->title ?: null
            ],
            "city" => [
                "id" => (integer) $pan->city_id ?: null,
                "title" => (string) $pan->city->title ?: null
            ],
            "links"        => [
                "self" => "/" . $pan->code . '---' . $pan->title
            ]
        ];
    }
    
    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function etag()
    {
        return md5($this->id . $this->timestamp());
    }

    public function clear()
    {
        $this->data([
            "title" => null,
        ]);
    }
}
