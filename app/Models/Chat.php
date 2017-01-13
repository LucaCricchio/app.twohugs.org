<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Chat
 *
 * @property int $id
 * @property int $sender_id
 * @property int $receiver_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Chat whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Chat whereSenderId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Chat whereReceiverId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Chat whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Chat extends Model {
    public static function getFromUser(User $user) {
        return Chat::where('sender_id', '=', $user->id)
                    ->orWhere('receiver_id', '=', $user->id)
                    ->get();
    }

    /**
     * @param $from Carbon|null
     * @return ChatMessage
     */
    public function getMessages($from) {
        return $from === null ?
            ChatMessage::whereChatId($this->id)->orderBy('created_at', 'DESC') :
            ChatMessage::whereChatId($this->id)->where('created_at', '>', $from)->orderBy('created_at', 'DESC');
    }
}
