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

class UserMessage extends \Spot\Entity
{
    protected static $table = "users_messages";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "message_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true, 'notnull' => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true, 'notnull' => true],
            "recipient_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true, 'notnull' => true],
            "pan_id" => ["type" => "integer", "unsigned" => true, "value" => 0, 'index' => true, 'notnull' => true],
            "hasread" => ["type" => "boolean", 'value' => false, 'notnull' => true],
            "dateread" => ["type" => "datetime", "value" => new \DateTime()],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'message' => $mapper->belongsTo($entity, 'App\Message', 'message_id'),
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'recipient' => $mapper->belongsTo($entity, 'App\User', 'recipient_id'),
            'form' => $mapper->belongsTo($entity, 'App\Panoram', 'pan_id')
        ];
    }

    public function transform(UserMessage $um)
    {
        return [
            "id" => (integer) $um->id ?: null,
            "message_id" => (integer) $um->message_id ?: "",
            "user_id" => (integer) $um->user_id ?: "",
            "recipient_id" => (integer) $um->recipient_id ?: "",
            "pan_id" => (integer) $um->pan_id ?: "",
            "timespan" => \human_timespan($um->created->format('U')),
            "message" => [
                "content" => (string) $um->message->content ? : null,
                "author" => ((string) $um->message->user->first_name ? : "") . ' ' . ((string) $um->message->user->last_name ? : "")
            ],
            "user" => [
                "id" => (integer) $um->user_id ? : null,
                "title" => ((string) $um->user->first_name ? : "") . ' ' . ((string) $um->user->last_name ? : ""),
                "email" => (string) $um->user->email ? : null,
                "picture" => (string) $um->user->picture ? : null
            ],
            "recipient" => [
                "id" => (integer) $um->recipient_id ? : null,
                "title" => ((string) $um->recipient->first_name ? : "") . ' ' . ((string) $um->recipient->last_name ? : ""),
                "email" => (string) $um->recipient->email ? : null,
                "picture" => (string) $um->recipient->picture ? : null
            ],
            "form" => [
                "id" => (INTEGER) $um->form->id ? : null,
                "code" => (string) $um->form->code ? : null,
                "title" => ((string) $um->form->brand->title ? : "") . ' ' . ((string) $um->form->model->title ? : ""),
                "currency" => (string) $um->form->currency->iso_code ? : null,
                "photo_url" => (string) $um->form->photos[0]->photo_url,
                "user" => ((string) $um->form->user->first_name ? : "") . ' ' . ((string) $um->form->user->last_name ? : ""),
                "mt_year" => (integer) $um->form->mt_year ? : null,
                "kms" => (integer) $um->form->kms ? : null,
                "price" => (string) $um->form->price ? : null
            ],
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "user_id" => null,
            "recipient_id" => null,
            "message_id" => null
        ]);
    }
}
