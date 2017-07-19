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

class Banner extends \Spot\Entity
{
    protected static $table = "banners";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "title" => ["type" => "string", "length" => 50],
            "content" => ["type" => "text"],
            "button" => ["type" => "string", "length" => 50],
            "type" => ["type" => "string", "length" => 10, "value" => "banner"],
            "location" => ["type" => "string", "length" => 10, "value" => "sidebar"],
            "position" => ["type" => "integer", "length" => 2, "value" => 1],
            "photo_url" => ["type" => "string", "length" => 255],
            "url" => ["type" => "string", "length" => 255],
            "newtab" => ["type" => "boolean", "value" => false, "default" => false],
            "enabled" => ["type" => "boolean", "value" => false],
            "enabled_from"   => ["type" => "datetime", "value" => new \DateTime()],
            "enabled_until"   => ["type" => "datetime", "value" => new \DateTime('+30 days')],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public function transform(Banner $banner)
    {
        return [
            "title" => (string) $banner->title ?: "",
            "content" => (string) $banner->content ?: "",
            "button" => (string) $banner->button ?: "",
            "photo_url" => (string) $banner->photo_url ?: "",
            "url" => (string) $banner->url ?: "",
            "newtab" => (integer) $banner->newtab ? 1 : 0
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
            "image" => null,
            "enabled" => null
        ]);
    }
}
