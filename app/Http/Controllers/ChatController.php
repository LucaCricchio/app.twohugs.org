<?php

namespace App\Http\Controllers;

use App\Helpers\Notifier;
use App\Models\User;
use Validator;
use App\Models\Hug;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Exceptions\ExceptionWithCustomCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller {
    const INVALID_HUG_ID = -3001;
    const USER_STILL_LOCKED = -3002;
    const INVALID_MESSAGE = -3003;
    const NOT_YOUR_CHAT = -3004;

    /**
     * Get chats for the user.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatForUser() {
        $currentUser = $this->getAuthenticatedUser();
        $chats = Chat::getFromUser($currentUser);
        return parent::response([
            'success' => true,
            'chats' => $chats
        ]);
    }

    /**
     * Get chats for a user with latest messages.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatMessagesWithLastMessage() {
        $currentUser = $this->getAuthenticatedUser();
        $chats = Chat::getFromUserWithLastMessages($currentUser);
        return parent::response([
            'success' => true,
            'chats' => $chats
        ]);
    }

    /**
     * Get chat messages from a chat given.
     * @param Request $request Body of request
     * @param Chat $chat Chat to fetch
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Create a new chat between tho unlocked users.
     * Warning: chat can be created just if users are really unlocked.
     * @param Request $request Request data
     * @return \Illuminate\Http\JsonResponse Chat information
     * @throws ExceptionWithCustomCode If users not unlocked or not valid data given in
     */
    public function newChat(Request $request) {
        $hugId = $request->get("hug", false);
        if (is_integer($hugId) && ($hug = Hug::find($hugId))) {
            $user = parent::getAuthenticatedUser();
            // Check if users unlocked their profile
            if ($hug->user_seeker_id == $user->id || $hug->user_sought_id == $user->id) {
                if ($hug->user_seeker_who_are_you_request != null && $hug->user_sought_who_are_you_request != null) {
                    $chat = new Chat();
                    $chat->receiver_id =
                        $hug->user_seeker_id == $user->id ?
                            $hug->user_sought_id :
                            $hug->user_seeker_id;
                    $chat->sender_id = $user->id;
                    $chat->save();

                    return parent::response(
                        [
                            'chat' => $chat
                        ],
                        Response::HTTP_CREATED
                    );
                }
                // Still null ?
                throw new ExceptionWithCustomCode(
                    "Users still locked",
                    self::USER_STILL_LOCKED,
                    Response::HTTP_EXPECTATION_FAILED,
                    null
                );
            }
        }
        // Hug not found or id from another hug ?
        throw new ExceptionWithCustomCode(
            "Invalid hug id",
            self::INVALID_HUG_ID,
            Response::HTTP_NOT_ACCEPTABLE,
            null
        );
    }

    /**
     * Check if a message is valid, then save it as sent.
     * This also send the message to the user.
     * @param Request $request "Message container"
     * @param Chat $chat Chat where to send message
     * @return \Illuminate\Http\JsonResponse
     * @throws ExceptionWithCustomCode
     */
    public function sendMessage(Request $request, Chat $chat) {
        $data = $request->only('message');
        $validator = Validator::make($data, [
            'message' => 'required|string|min:1|max:255'
        ]);
        if($validator->passes()) {
            $current = $this->getAuthenticatedUser();

            if ($chat->receiver_id != $current->id && $chat->sender_id != $current->id)
                throw new ExceptionWithCustomCode(
                    "Not your chat!",
                    self::NOT_YOUR_CHAT,
                    Response::HTTP_NOT_ACCEPTABLE,
                    null
                );

            // Saves the chat
            $message = new ChatMessage();
            $message->message = $data['message'];
            $message->chat_id = $chat->id;
            $message->user_id = $current->id;
            $message->save();

            Notifier::send(
                $chat->receiver_id == $current->id ?
                    User::find($chat->sender_id) : $current,
                'chat',
                'messageReceived',
                [
                    "chat"    => $chat,
                    "message" => $message,
                ]
            );

            return parent::response([
                'success' => true
            ]);
        } else
            throw new ExceptionWithCustomCode(
                $validator->errors()->get('message'),
                self::INVALID_MESSAGE,
                Response::HTTP_NOT_ACCEPTABLE,
                null
            );
    }
}
