<?php

use App\Http\Controllers\BotManController;
use App\Mail\FeedbackMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

$botman = resolve('botman');

$botman->hears('/start', function ($bot) {

    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();

    $message = sprintf("*Добро пожаловать в магазин плоттеров!*\nВоспользуйтесь нашим специальным предложением!");

    $keyboard = [
        [
            ["text" => "\xF0\x9F\x94\xA5\xF0\x9F\x94\xA5\xF0\x9F\x94\xA5ПОЛУЧИТЬ СПЕЦИАЛЬНОЕ ЦЕНОВОЕ ПРЕДЛОЖЕНИЕ\xF0\x9F\x94\xA5\xF0\x9F\x94\xA5\xF0\x9F\x94\xA5",
                "request_contact" => true]
        ],
        ["\xE2\x8C\x9AПолучить расчет окупаемости"],
        ["\xF0\x9F\x92\xB8Получить стоимость плоттера и пленок"],
        ["\xF0\x9F\x92\xB3Порядок заказа и оплаты"],
        ["\xF0\x9F\x92\xACЗадать свой вопрос"],
    ];

    $bot->sendRequest("sendMessage",
        [
            "chat_id" => "$id",
            "text" => $message,
            "parse_mode" => "Markdown",
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'one_time_keyboard' => false,
                'resize_keyboard' => true
            ])
        ]);
})->stopsConversation();


$botman->hears('.*Получить расчет окупаемости', function ($bot) {
    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();

    $message = "Уникальное предложение!\xE2\x9C\x85.\nВыберите цену поклейки плёнки:";


    $keyboard = [
        [
            ['text' => "\xF0\x9F\x94\xB8 500 ₽", 'callback_data' => "/calc 500"],
            ['text' => "\xF0\x9F\x94\xB8 600 ₽", 'callback_data' => "/calc 600"],
            ['text' => "\xF0\x9F\x94\xB8 700 ₽", 'callback_data' => "/calc 700"],
        ],
        [
            ['text' => "\xF0\x9F\x94\xB8 800 ₽", 'callback_data' => "/calc 800"],
            ['text' => "\xF0\x9F\x94\xB8 1000 ₽", 'callback_data' => "/calc 1000"],
        ]
    ];
    /*  for ($i = 100; $i < 1200; $i += 100) {
          $price = $i + 200;
          array_push($tmp, ['text' => "\xF0\x9F\x94\xB8 $price ₽", 'callback_data' => "/calc $price"]);
          if ($i % 300 === 0) {
              array_push($keyboard, $tmp);
              $tmp = [];
          }

      }

      if ($tmp != [])
          array_push($keyboard, $tmp);*/

    $bot->sendRequest("sendMessage",
        [
            "chat_id" => $id,
            "parse_mode" => "markdown",
            "text" => $message,
            'reply_markup' => json_encode([
                'inline_keyboard' =>
                    $keyboard
            ])
        ]);
});

/*$botman->hears('.*Получить стоимость плоттера и пленок', function ($bot) {
    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();

    $message = sprintf("*Цена плоттера:*\nПервый - *39 000 ₽*\xE2\x9C\x85, цена на второй и последующие плоттеры – *30 000 ₽* \xE2\x9C\x85\n 
*Цена пленки:*\n50-200 шт – *100 ₽/шт*, свыше 300 шт – *80 ₽/шт*

");

    $keyboard = [
        [
            ["text" => "\xF0\x9F\x93\xA6Свяжись с нами", "callback_data" => "/request"]
        ],

    ];

    $bot->sendRequest("sendMessage",
        [
            "chat_id" => "$id",
            "text" => $message,
            "parse_mode" => "Markdown",
            'reply_markup' => json_encode([
                'inline_keyboard' =>
                    $keyboard
            ])
        ]);
});*/

$botman->hears('/calc ([0-9]+)', function ($bot, $price) {
    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();

    $result = round(39000 / ($price - 100));
    $message = sprintf("Вы выбрали цену поклейки *%s* ₽ плоттер окупится через *%s*\xE2\x9C\x85 поклеек!",
        $price,
        $result
    );

    $keyboard = [
        [
            ["text" => "\xF0\x9F\x93\xA6Свяжись с нами", "callback_data" => "/request"]
        ]
    ];

    $bot->sendRequest("sendMessage",
        [
            "chat_id" => "$id",
            "text" => $message,
            "parse_mode" => "Markdown",
            'reply_markup' => json_encode([
                'inline_keyboard' =>
                    $keyboard
            ])
        ]);
});

$botman->hears('.*Порядок заказа и оплаты|/order', function ($bot) {
    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();

    $message = sprintf("*Заказ товара:*\n\xF0\x9F\x94\xB8 позвони по телефону *+7 938 528-76-99*, *+79994826970* Whats App нашему менеджеру;\n
\xF0\x9F\x94\xB8 оставить заявку на нашем сайте: https://plottersale.ru/\n
\xF0\x9F\x94\xB8 оформить заявку на Авито\n\n*Оплата товара:*\n\xF0\x9F\x94\xB8 наличными курьеру при получении (Москва)\n
\xF0\x9F\x94\xB8 через Авито доставку и оплату (https://www.avito.ru/moskva/orgtehnika_i_rashodniki/plotter_dlya_narezki_gidrogelevoy_plenki_na_telefon_2009828423)\n
\xF0\x9F\x94\xB8 на карту Сбербанк
");

    $keyboard = [
        [
            ["text" => "\xF0\x9F\x93\xA6Свяжись с нами", "callback_data" => "/request"]
        ],

    ];

    $bot->sendRequest("sendMessage",
        [
            "chat_id" => "$id",
            "text" => $message,
            "parse_mode" => "Markdown",
            'reply_markup' => json_encode([
                'inline_keyboard' =>
                    $keyboard
            ])
        ]);

});

$botman->hears('.*Получить стоимость плоттера и пленок|/request', BotManController::class . '@startRequest');
$botman->hears('.*Задать свой вопрос|.*заявка.*', BotManController::class . '@startRequestWithMessage');

$botman->fallback(function (\BotMan\BotMan\BotMan $bot) {
    /* Log::info(print_r($bot->getMessage()->getPayload(),true));*/

    $json = json_decode($bot->getMessage()->getPayload());

    if (isset($json->contact)) {
        $tmp_phone = $json->contact->phone_number;

        $user = $bot->getUser();

        $bot->reply("Заявка успешно принята! Мы свяжемся с вами в течение 10 минут!");

        $toEmail = env('MAIL_ADMIN');
        Mail::to($toEmail)->send(new FeedbackMail([
            "name" => ($user->getLastName() . " " . $user->getFirstName() ?? $user->getUsername() ?? $user->getId()),
            "phone" => $tmp_phone,
            "date" => (Carbon::now("+3"))
        ]));
    } else
        $bot->reply("Попробуй что-то другое ввести!");
});
