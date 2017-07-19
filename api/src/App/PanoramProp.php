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

class PanoramProp extends \Spot\Entity
{
    protected static $table = "panorams_props";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "pan_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true],
            "prop_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'vehicle' => $mapper->belongsTo($entity, 'App\Panoram', 'pan_id'),
            'property' => $mapper->belongsTo($entity, 'App\Prop', 'prop_id'),
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "pan_id" => null,
            "prop_id" => null
        ]);
    }
}
