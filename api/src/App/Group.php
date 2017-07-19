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

class Group extends \Spot\Entity
{
    protected static $table = "groups";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "city_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'isnull' => false, 'index' => true],            
            "title" => ["type" => "string", "length" => 50],
            "address" => ["type" => "string", "length" => 50],
            "tel" => ["type" => "string", "length" => 50],
            "whatsapp" => ["type" => "string", "length" => 50],
            "facebook" => ["type" => "string", "length" => 50],
            "twitter" => ["type" => "string", "length" => 50],
            "code" => ["type" => "string", "length" => 50],
            "color" => ["type" => "string", "length" => 20],
            "rate" => ["type" => "decimal", "precision" => 22, "scale" => 11, "value" => 0, "default" => 0, "notnull" => true],
            "order" => ["type" => "integer", "length" => 2, "notnull" => true, "value" => 99],
            "enabled" => ["type" => "boolean", "value" => false],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'city' => $mapper->belongsTo($entity, 'App\City', 'city_id')
        ];
    }


    public function transform(Group $currency)
    {
        return [
            "id" => (integer) $currency->id ?: null,
            "title" => (string) $currency->title ?: "",
            "iso_code" => (string) $currency->iso_code ?: "",
            "rate" => (string) $currency->rate ?: ""
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "title" => null,
            "iso_code" => null,
            "enabled" => null
        ]);
    }
}
