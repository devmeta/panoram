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
            "lat" => ["type" => "decimal", "precision" => 21, "scale" => 2, "value" => 0, "default" => 0, "notnull" => true],
            "lng" => ["type" => "decimal", "precision" => 21, "scale" => 2, "value" => 0, "default" => 0, "notnull" => true],
            "tel" => ["type" => "string", "length" => 255, "value" => "", "notnull" => true],
            "schedule" => ["type" => "string", "length" => 255, "value" => "", "notnull" => true],
            "currency_id" => ["type" => "integer", "unsigned" => true, "value" => 1, "default" => 1, 'index' => true],
            "mt_year" => ["type" => "integer", "unsigned" => true, "default" => 0],
            "condition" => ["type" => "integer", "unsigned" => true, "value" => 1,"default" => 1, "notnull" => true],
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
            'files' => $mapper->hasMany($entity, 'App\File','pan_id')->order(['position' => 'ASC']),
            'props' => $mapper->hasManyThrough($entity, 'App\Prop', 'App\PanoramProp','prop_id','pan_id')->order(['order' => 'ASC'])
        ];
    }

    public function transform(Panoram $form)
    {
        $files = $props = [];

        foreach($form->files as $photo){
            $files[] = [
                'id' => $photo->id,
                'photo_url' => $photo->file_url,
                'position' => $photo->position,
                'filesize'  => $photo->filesize
            ];
        }

        foreach($form->props as $prop){
            $props[$prop->group->slug][] = $prop->title;
        }

        $until = $form->enabled_until;
        if(is_object($until)) $until_date = $until->format('U');

        $created = $form->created;
        if(is_object($created)) $created_date = $created->format('U');

        return [
            "id" => (integer) $form->id ?: null,
            "public_id" => sprintf("%'.09d\n", $form->id),
            "encoded" => Base62::encode(sprintf("%'.09d\n", $form->id)),
            "code" => (string) $form->code ?: null,
            "title" => (string) $form->title ?: null,
            "extrainfo" => (string) $form->extrainfo ?: null,
            "hits" => (integer) $form->hits ?: 0,
            "enabled" => !!$form->enabled,
            "requests" => $form->requests->count(),
            "enabled_until" => \human_timespan($until_date),
            "created" => \human_timespan($created_date),
            "active" => ($until_date > time()),
            "file" => ! empty($files)?$files[0]:null,
            "files" => $files,
            "props" => $props,
            "user" => [
                "id" => (integer) $form->user_id ? : null,
                "title" => ((string) $form->user->first_name ? : "") . ' ' . ((string) $form->user->last_name ? : ""),
                "email" => (string) $form->user->email ? : null,
                "picture" => (string) $form->user->picture ? : null
            ],
            "brand" => [
                "id" => (integer) $vehicle->brand_id ? : null,
                "title" => (string) $vehicle->brand->title ? : null
            ],
            "model" => [
                "id" => (integer) $vehicle->model_id ?: null,
                "title" => (string) $vehicle->model->title ?: null
            ],
            "version" => [
                "id" => (integer) $vehicle->version_id ?: null,
                "title" => (string) $vehicle->version->title ?: null
            ],
            "region" => [
                "id" => (integer) $vehicle->region_id ?: null,
                "title" => (string) $vehicle->region->title ?: null
            ],
            "city" => [
                "id" => (integer) $vehicle->city_id ?: null,
                "title" => (string) $vehicle->city->title ?: null
            ],
            "gear" => [
                "id" => (integer) $vehicle->gear_id ?: null,
                "title" => (string) $vehicle->gear->title ?: null
            ],
            "fuel" => [
                "id" => (integer) $vehicle->fuel_id ?: null,
                "title" => (string) $vehicle->fuel->title ?: null
            ],
            "color" => [
                "id" => (integer) $vehicle->color_id ?: null,
                "title" => (string) $vehicle->color->title ?: null
            ],
            "links"        => [
                "self" => "/" . $form->code . '---' . $form->title
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
