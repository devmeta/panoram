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

class User extends \Spot\Entity
{
    protected static $table = "users";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "region_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'isnull' => false, 'index' => true],
            "email" => ["type" => "string", "length" => 50, "unique" => true],
            "username" => ["type" => "string", "length" => 100, "unique" => true],
            "first_name" => ["type" => "string", "length" => 32],
            "last_name" => ["type" => "string", "length" => 32],
            "password" => ["type" => "string", "length" => 255],
            "phone" => ["type" => "string", "length" => 255],
            "address" => ["type" => "string", "length" => 255],
            "password_token" => ["type" => "string", "length" => 255],
            "token" => ["type" => "text"],
            "facebook_id" => ["type" => "decimal", "precision" => "21", "unique" => true],
            "google_id" => ["type" => "decimal", "precision" => "21", "unique" => true],
            "picture" => ["type" => "string", "length" => "255"],
            "newsletter" => ["type" => "boolean", "value" => false],
            "terms" => ["type" => "boolean", "value" => false],
            "validated" => ["type" => "boolean", "value" => false],
            "enabled" => ["type" => "boolean", "value" => false],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'region' => $mapper->belongsTo($entity, 'App\Region', 'region_id')
        ];
    }

    public function transform(User $user)
    {
        $member_since = $user->created;
        $member_since_date = $member_since->format('U');

        return [
            "id" => (integer) $user->id ?: null,
            "email" => (string) $user->email ?: null,
            "email_encoded" => (string) $user->email ? Base62::encode($user->email): null,
            "first_name" => (string) $user->first_name ?: "",
            "last_name" => (string) $user->last_name ?: "",
            "picture" => (string) $user->picture ?: "",
            "validated" => (integer) $user->validated ?: 0,
            "member_since" => \human_timespan($member_since_date),
            "token" => \set_token($user->id),
            "owned" => \get_owned($user->id),
            "preferences" => \get_preferences($user->id)
        ];
    }

    public function timestamp()
    {
        return $this->updated_at->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "username" => null,
            "password" => null,
            "enabled" => null
        ]);
    }
}
