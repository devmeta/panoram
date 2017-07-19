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

class Message extends \Spot\Entity
{
    protected static $table = "messages";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "content" => ["type" => "text", "charset" => "utf8mb4_general_ci", 'notnull' => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
        ];
    }

    public function transform(Message $message)
    {
        $created = $message->created;

        return [
            "id" => (integer) $message->id ?: null,
            "content" => (string) $message->content ?: "",
            "timespan" => \human_timespan($created->format('U')),
            "user" => [
                "id" => (integer) $message->user_id ? : null,
                "title" => ((string) $message->user->first_name ? : "") . ' ' . ((string) $message->user->last_name ? : ""),
                "email" => (string) $message->user->email ? : null,
                "picture" => (string) $message->user->picture ? : null
            ]            
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "content" => null,
        ]);
    }
}
