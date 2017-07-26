<?php

namespace App\Models;

use Carbon\Carbon;
use DB;
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
     * Get an array with chats and latest messages.
     * @param User $user
     * @return array
     */
    public static function getFromUserWithLastMessages(User $user) {
        return DB::select("
            SELECT id, sender_id, receiver_id, message_id, chat_id, message, photo, newer 
            FROM chats INNER JOIN (
                SELECT message_id, chat_id, message, photo, MAX(created_at) AS newer 
                FROM (
                    SELECT id AS message_id, chat_id, message, photo, created_at
                    FROM chat_messages
                    ORDER BY created_at DESC
                ) AS temp_messages 
            ) as temp_chat_messages
            WHERE sender_id='{$user->id}' OR receiver_id='{$user->id}'
            GROUP BY chat_id
        ");
    }

    /**
     * @param $from Carbon|null
     * @return ChatMessage[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function getMessages($from) {
        return $from === null ?
            ChatMessage::whereChatId($this->id)->orderBy('created_at', 'DESC')->get() :
            ChatMessage::whereChatId($this->id)->where('created_at', '>', $from)->orderBy('created_at', 'DESC')->get();
    }
}
