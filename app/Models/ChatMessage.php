<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ChatMessage
 *
 * @property int $id
 * @property int $user_id
 * @property int $chat_id
 * @property string $message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChatMessage whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChatMessage whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChatMessage whereChatId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChatMessage whereMessage($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ChatMessage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ChatMessage extends Model {
    protected $table = "chat_messages";
}
