<?php

use App\Http\Controllers\BotManController;
use App\Mail\FeedbackMail;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

$botman = resolve('botman');

function createUser($bot)
{
    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();
    $username = $telegramUser->getUsername();
    $lastName = $telegramUser->getLastName();
    $firstName = $telegramUser->getFirstName();

    $user = User::where("telegram_chat_id", $id)->first();
    if ($user == null)
        $user = \App\User::create([
            'name' => $username ?? "$id",
            'email' => "$id@t.me",
            'password' => bcrypt($id),
            'fio_from_telegram' => "$firstName $lastName",
            'telegram_chat_id' => $id,
            'is_admin' => false,
        ]);
    return $user;
}


function isAdmin($bot)
{
    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();
    $user = User::where("telegram_chat_id", $id)->first();
    return $user->is_admin ? true : false;
}

$botman->hears('/start', function ($bot) {

    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();

    createUser($bot);

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

    if (isAdmin($bot)) {
        array_push($keyboard, ["\xF0\x9F\x93\x8AРаздел администратора"]);
    }
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


$botman->hears('.*Раздел администратора|/admin', function ($bot) {

    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();

    if (!isAdmin($bot)) {
        $bot->reply("Раздел недоступен");
        return;
    }

    $users_in_bd = User::all()->count();
    $with_phone_number = User::whereNotNull("phone")->get()->count();
    $without_phone_number = User::whereNull("phone")->get()->count();

    $users_in_bd_day = User::whereDate('created_at', Carbon::today())
        ->orderBy("id", "DESC")
        ->get()
        ->count();

    $message = sprintf("Всего пользователей в бд: %s\nВсего оставили номер телефона:%s\nКол-во не оставивших телефон:%s \nПользователей за день:%s",
        $users_in_bd,
        $with_phone_number,
        $without_phone_number,
        $users_in_bd_day
    );

    $keyboard = [

        [
            ["text" => "Рассылка всем", "callback_data" => "/send_to_all"]
        ],
        [
            ["text" => "Список пользователей (с телефонами)", "callback_data" => "/users_list_1"]
        ],
        [
            ["text" => "Список пользователей (без телефонов)", "callback_data" => "/users_list_2"]
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

$botman->hears('/send_to_all',BotManController::class . '@startMessageToAll');
$botman->hears('/send_message ([0-9]+)',BotManController::class . '@startMessageToUser');

$botman->hears('/send_message ([0-9]+)', function ($bot, $id) {
    if (!isAdmin($bot))
        return;


});

$botman->hears('/users_list_1', function ($bot) {
    if (!isAdmin($bot))
        return;

    $users = User::whereNotNull("phone")
        ->orderBy("id", "desc")
        ->take(30)
        ->skip(0)
        ->get();

    $bot->reply("Список последних 30 пользователей с телефонами");
    usersList($bot, $users);

});

$botman->hears('/users_list_2', function ($bot) {
    if (!isAdmin($bot))
        return;


    $users = User::whereNull("phone")
        ->orderBy("id", "desc")
        ->take(30)
        ->skip(0)
        ->get();

    $bot->reply("Список последних 30 пользователей без телефонов");
    usersList($bot, $users);

});

function usersList($bot, $users)
{

    foreach ($users as $user) {
        $phones = json_decode($user->phone) ?? [];

        $tmp_phones = '';

        if (count($phones) > 0)
            foreach ($phones as $phone)
                $tmp_phones .= "$phone\n";


        $message = sprintf("Пользователь:%s\nТелефон:%s",
            ($user->name ?? $user->fio_from_telegram ?? $user->telegram_chat_id ?? 'Ошибка'),
            $tmp_phones
        );

        $keyboard = [

            [
                ["text" => "\xF0\x9F\x92\xACНаписать", "callback_data" => "/send_message $user->id"]
            ],
        ];

        $bot->sendRequest("sendMessage",
            [
                "chat_id" => $user->telegram_chat_id,
                "text" => $message,
                "parse_mode" => "Markdown",
                'reply_markup' => json_encode([
                    'inline_keyboard' =>
                        $keyboard
                ])
            ]);

    }
}


$botman->hears('.*Порядок заказа и оплаты|/order', function ($bot) {
    $telegramUser = $bot->getUser();
    $id = $telegramUser->getId();

    $message = sprintf("*Заказ товара:*\n\xF0\x9F\x94\xB8 позвони по телефону *+7 938 528-76-99*, *+79994826970* Whats App нашему менеджеру;\n
\xF0\x9F\x94\xB8 оставить заявку на нашем сайте: https://plottersale.ru/\n
\xF0\x9F\x94\xB8 оформить заявку на Авито\n\n*Оплата товара:*\n\xF0\x9F\x94\xB8 наличными курьеру при получении (Москва)\n
\xF0\x9F\x94\xB8 через Авито доставку и оплату\n
\xF0\x9F\x94\xB8 на карту Сбербанк
");

    $keyboard = [
        [
            ["text" => "\xF0\x9F\x93\xA6Свяжись с нами", "callback_data" => "/request"]
        ],
        [
            ["text" => "\xF0\x9F\x93\x8DГлянуть на Авито", "url" => "https://www.avito.ru/moskva/orgtehnika_i_rashodniki/plotter_dlya_narezki_gidrogelevoy_plenki_na_telefon_2009828423"]
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

        $telegramUser = $bot->getUser();
        $id = $telegramUser->getId();

        $bot->reply("Заявка успешно принята! Мы свяжемся с вами в течение 10 минут!");

        $user = User::where("telegram_chat_id", $id)->first();
        $phones = json_decode($user->phone) ?? [];
        array_push($phones, $tmp_phone);
        $user->phone = json_encode($phones);
        $user->save();

        $toEmail = env('MAIL_ADMIN');
        Mail::to($toEmail)->send(new FeedbackMail([
            "name" => ($user->getLastName() . " " . $user->getFirstName() ?? $user->getUsername() ?? $user->getId()),
            "phone" => $tmp_phone,
            "date" => (Carbon::now("+3"))
        ]));
    } else
        $bot->reply("Попробуй что-то другое ввести!");
});
