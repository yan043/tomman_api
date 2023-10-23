<?php

namespace App\Models;

date_default_timezone_set('Asia/Makassar');

class Telegram
{
    public static function sendMessage($chatID, $message)
    {
        $text = urlencode($message);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://api.telegram.org/bot207659566:AAFY7LKIrJ2vYyaGohyYDzuIOS3tOOwV3fE/sendmessage?chat_id=$chatID&text=$text&parse_mode=HTML",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageReply($chatID, $message, $messageID)
    {
        $text = urlencode($message);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://api.telegram.org/bot207659566:AAFY7LKIrJ2vYyaGohyYDzuIOS3tOOwV3fE/sendmessage?chat_id=$chatID&text=$text&parse_mode=HTML&reply_to_message_id=$messageID",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendPhoto($chatID, $caption, $photo)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://api.telegram.org/bot207659566:AAFY7LKIrJ2vYyaGohyYDzuIOS3tOOwV3fE/sendPhoto",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => [
                "chat_id"    => $chatID,
                "parse_mode" => "HTML",
                "caption"    => $caption,
                "photo"      => new \CURLFILE($photo)
            ],
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}
