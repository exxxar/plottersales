<?php

namespace App\Conversations;

use App\Mail\FeedbackMail;
use App\User;
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
    protected $is_only_phone;

    public function __construct(BotMan $bot, $is_only_phone = false)
    {
        $this->bot = $bot;
        $this->is_only_phone = $is_only_phone;

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

            $phone =  $answer->getText();

            $pattern_ua = "/^\+380\d{3}\d{2}\d{2}\d{2}$/";
            $pattern_ru = "/^\+7\d{3}\d{2}\d{2}\d{2}$/";
            $tmp_phone = str_replace(["(", ")", "-", " "], "", $phone);

            if (preg_match($pattern_ua, $tmp_phone) == 0&&preg_match($pattern_ru, $tmp_phone)== 0) {
                $this->bot->reply("Вы неверно ввели телефонный номер! Попробуйте ввести в формате +7XXXXXXXXXX.");
                $this->askPhone();
                return;
            }

            $user = User::where("telegram_chat_id", $this->current_user_id)->first();
            if (is_null($user))
            {
                $this->bot->reply("Хм, что-то пошло не так...");
                return;
            }

            $phones = json_decode($user->phone) ?? [];
            if (!in_array($phone, $phones)) {
                array_push($phones, $phone);
                $user->phone = json_encode($phones);
                $user->save();
            }

            if (!$this->is_only_phone) {
                $this->askMessage($tmp_phone);
            } else {
                $this->send([
                    "phone" => $tmp_phone?? "Нет телефона",
                    "message" => 'Нет сообщения'
                ]);
            }
        });
    }

    public function askMessage($phone)
    {
        $question = Question::create('Напишите ваш вопрос:')
            ->fallback('Спасибо что пообщались со мной:)!');

        $this->ask($question, function (Answer $answer) use ($phone) {
            $this->send([
                "phone" => $phone ?? "Нет телефона",
                "message" => $answer->getText() ?? 'Нет сообщения'
            ]);
        });
    }

    protected function send($data)
    {
        $this->bot->reply("Заявка успешно принята! Мы свяжемся с вами в течение 10 минут!");

        $user = $this->bot->getUser();

        $toEmail = env('MAIL_ADMIN');
        Mail::to($toEmail)->send(new FeedbackMail([
            "name" => ($user->getLastName() . " " . $user->getFirstName() ?? $user->getUsername() ?? $user->getId()),
            "phone" => $data["phone"],
            "date" => (Carbon::now("+3")),
            "message" => $data["message"],
        ]));
    }
}
