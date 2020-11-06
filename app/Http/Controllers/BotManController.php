<?php

namespace App\Http\Controllers;

use App\Conversations\CalcConversation;
use App\Conversations\MessagesConversation;
use App\Conversations\RequestConversation;
use BotMan\BotMan\BotMan;
use http\Message;
use Illuminate\Http\Request;
use App\Conversations\ExampleConversation;

class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');

        $botman->listen();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }

    /**
     * Loaded through routes/botman.php
     * @param BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new ExampleConversation());
    }

    public function startCalc(BotMan $bot)
    {
        $bot->startConversation(new CalcConversation($bot));
    }

    public function startRequest(BotMan $bot)
    {
        $bot->startConversation(new RequestConversation($bot, true));
    }

    public function startRequestWithMessage(BotMan $bot)
    {
        $bot->startConversation(new RequestConversation($bot));
    }

    public function startMessageToAll(BotMan $bot)
    {
        $bot->startConversation(new MessagesConversation($bot));
    }

    public function startMessageToUser(BotMan $bot, $id)
    {
        $bot->startConversation(new MessagesConversation($bot, $id));
    }

}
