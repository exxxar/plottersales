<?php

namespace App\Conversations;

use App\Mail\FeedbackMail;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class RequestConversation extends Conversation
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

        $this->askPhone();
    }


    public function askPhone()
    {
        $question = Question::create('Скажите мне свой телефонный номер:')
            ->fallback('Спасибо что пообщались со мной:)!');

        $this->ask($question, function (Answer $answer) {
            $this->askMessage($answer->getText());
        });
    }

    public function askMessage($phone)
    {
        $question = Question::create('Напишите ваш вопрос:')
            ->fallback('Спасибо что пообщались со мной:)!');

        $this->ask($question, function (Answer $answer) use ($phone) {

            $user = $this->bot->getUser();

            $this->bot->reply("Заявка успешно принята! С вами свяжется наш менеджер!");

            $toEmail = env('MAIL_ADMIN');
            Mail::to($toEmail)->send(new FeedbackMail([
                "name" => ($user->getLastName() . " " . $user->getFirstName() ?? $user->getUsername() ?? $user->getId()),
                "phone" => $phone,
                "date" => (Carbon::now("+3")),
                "message" => $answer->getText(),
            ]));

        });
    }

}
