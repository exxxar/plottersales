<?php

namespace App\Conversations;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Users\User;

class CalcConversation extends Conversation
{

    protected $bot;
    protected $current_user_id;

    public function __construct(BotMan $bot)
    {
        $this->bot = $bot;

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

    }
}
