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

class Prop extends \Spot\Entity
{
    protected static $table = "props";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "group_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true],
            "title" => ["type" => "string", "length" => 100],
            "order" => ["type" => "integer", "length" => 2],
            "enabled" => ["type" => "boolean", "value" => false],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'group' => $mapper->belongsTo($entity, 'App\PropGroup', 'group_id'),
        ];
    }

    public function transform(Prop $prop)
    {
        return [
            "id" => (integer) $prop->id ?: null,
            "title" => (string) $prop->title ?: ""
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
            "enabled" => null
        ]);
    }
}
