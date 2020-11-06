<?php

namespace App\Conversations;

use App\User;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;

class MessagesConversation extends Conversation
{

    protected $bot;
    protected $current_user_id;
    protected $recipient_user_id;

    public function __construct(BotMan $bot, $recipient_user_id = null)
    {
        $this->bot = $bot;
        $this->recipient_user_id = $recipient_user_id;

        $telegramUser = $bot->getUser();
        $this->current_user_id = $telegramUser->getId();
    }

    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        if (is_null($this->recipient_user_id))
            $this->askMessages();
        else
            $this->askMessage();
    }

    public function askMessage()
    {
        $question = Question::create('Текст сообщения для пользователя (' . $this->recipient_user_id . '):')
            ->fallback('Спасибо что пообщались со мной:)!');

        $this->ask($question, function (Answer $answer) {


            $user = User::where("id", $this->recipient_user_id)->get();

            if (is_null($user))
            {
                $this->bot->reply("Хм, что-то пошло не так...");
                return;
            }

            $keyboard = [
                [
                    [
                        "text" => "Хочу узнать подробнее!",
                        "callback_data" => "/request"
                    ]
                ]
            ];

            try {
                $this->bot->sendRequest("sendMessage",
                    [
                        "chat_id" => $user->telegram_chat_id,
                        "text" => $answer->getText(),
                        "parse_mode" => "Markdown",
                        'reply_markup' => json_encode([
                            'inline_keyboard' =>
                                $keyboard
                        ])
                    ]);
                $this->bot->reply("Сообщение доставлен к #$user->telegram_chat_id");
            } catch (\Exception $e) {
                $this->bot->reply("Сообщение НЕ доставелно к #$user->telegram_chat_id ! Пользователь отписался от бота.");
            }

        });
    }

    public function askMessages()
    {
        $question = Question::create('Текст сообщения для пользователей:')
            ->fallback('Спасибо что пообщались со мной:)!');

        $this->ask($question, function (Answer $answer) {


            $users = User::where("telegram_chat_id", $this->current_user_id)->get();


            $this->bot->reply("Ожидайте! Это займет какое-то время! Всего в базе " . (count($users)) . " пользователей!");


            foreach ($users as $user) {
                $keyboard = [
                    [
                        [
                            "text" => "Хочу узнать подробнее!",
                            "callback_data" => "/request"
                        ]
                    ]
                ];

                try {
                    $this->bot->sendRequest("sendMessage",
                        [
                            "chat_id" => "$user->telegram_chat_id",
                            "text" => $answer->getText(),
                            "parse_mode" => "Markdown",
                            'reply_markup' => json_encode([
                                'inline_keyboard' =>
                                    $keyboard
                            ])
                        ]);
                    $this->bot->reply("Сообщение доставлен к #$user->telegram_chat_id");
                } catch (\Exception $e) {
                    $this->bot->reply("Сообщение НЕ доставелно к #$user->telegram_chat_id ! Пользователь отписался от бота.");
                }
            }
        });
    }
}
