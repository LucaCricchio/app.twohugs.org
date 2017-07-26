<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ChatSeeder extends Seeder
{
    const MESSAGES_NO = 15;
    const CHAT_NO = 2;

    private $users;
    private $excludedUsers;

    public function __construct()
    {
        $this->users = array();
        $this->excludedUsers = array();
        try {
            $this->users[0] = \App\Models\User::whereUsername('Kalizi')->first();
        } catch (Exception $e) {
            $this->users[0] = \App\Models\User::first();
        }
    }


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        for ($i = 0; $i < self::CHAT_NO; ++$i) {
            $this->genSecondUser();
            $chat = \App\Models\Chat::create([
                'sender_id' => $this->users[0],
                'receiver_id' => $this->users[1]
            ]);
            $chat->save();
            for ($j = 0; $j < self::MESSAGES_NO; ++$j) {
                \App\Models\ChatMessage::create([
                    'user_id' => $this->users[$j % 2 == 0 ? 0 : 1]->id,
                    'chat_id' => $chat->id,
                    'message' => $faker->randomAscii
                ])->save();
            }
        }
    }

    private function genSecondUser() {
        $this->excludedUsers[] = $this->users[1];
        do {
            $this->users[1] = \App\Models\User::inRandomOrder()->first();
        } while ($this->users[1] != $this->users[0] && !in_array($this->users[1], $this->excludedUsers));
    }
}
