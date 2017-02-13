<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller {
    public function getChatForUser() {
        $currentUser = $this->getAuthenticatedUser();
        $chats = Chat::getFromUser($currentUser);
        return parent::response([
            'success' => true,
            'chats' => $chats
        ]);
    }

    public function getChatMessagesWithLastMessage() {
        $currentUser = $this->getAuthenticatedUser();
        $chats = Chat::getFromUserWithLastMessages($currentUser);
        return parent::response([
            'success' => true,
            'chats' => $chats
        ]);
    }

    public function getChatMessages(Request $request, Chat $chat) {
        $from = null;
        if ($request->has('from'))
            // If not valid datetime $from = null
            $from = Carbon::parse($request->get('from'));

        return parent::response([
            'success' => true,
            'chat' => $chat,
            'messages' => $chat->getMessages($from)
        ]);
    }

    public function sendMessage(Request $request, Chat $chat) {
        $data = $request->only('message');
        $validator = Validator::make($data, [
            'message' => 'required|string|min:1|max:255'
        ]);
        if($validator->passes()) {
            $current = $this->getAuthenticatedUser();
            $message = new ChatMessage();
            $message->message = $data['message'];
            $message->chat_id = $chat->id;
            $message->user_id = $current->id;
            $message->save();
            return parent::response([
                'success' => true,
                'chat_id' => $chat->id,
                'message' => $message
            ]);
        } else
            return parent::response([
                'success' => false,
                'errors' => [
                    'message' => $validator->errors()->get('message')
                ]
            ], Response::HTTP_NOT_ACCEPTABLE);
    }
}
