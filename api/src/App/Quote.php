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

class Quote extends \Spot\Entity
{

    protected static $table = "quotes";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "pan_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true],
            "file_url" => ["type" => "string", "length" => 255],
            "content" => ["type" => "text"],
            "author" => ["type" => "string", "length" => 255],
            "position" => ["type" => "integer", "unsigned" => true, "value" => 0],
            "filesize" => ["type" => "integer", "unsigned" => true, "value" => 0],
            "enabled" => ["type" => "boolean", "value" => false],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'form' => $mapper->belongsTo($entity, 'App\Panoram', 'pan_id'),
        ];
    }

    public function transform(Quote $file)
    {
        return [
            "id" => (integer) $file->id ?: null,
            "file_url" => (string) $file->file_url ?: ""
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
