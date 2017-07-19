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

class Model extends \Spot\Entity
{
    protected static $table = "models";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "brand_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true],
            "title" => ["type" => "string", "length" => 50],
            "enabled" => ["type" => "boolean", "value" => false],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public function transform(Model $model)
    {
        return [
            "id" => (integer)$model->id ?: null,
            "title" => (string)$model->title ?: ""
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'brand' => $mapper->belongsTo($entity, 'App\Brand', 'brand_id'),
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
