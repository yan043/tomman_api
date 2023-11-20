<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class XproController extends Controller
{
    public static function login_xpro($uname, $pass, $chatid)
    {
        print_r("$uname $pass $chatid\n\n");

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://newxpro.telkom.co.id/auth/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            // CURLOPT_ENCODING       => '',
            // CURLOPT_MAXREDIRS      => 10,
            // CURLOPT_TIMEOUT        => 0,
            // CURLOPT_FOLLOWLOCATION => true,
            // CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ));
        $response          = curl_exec($curl);
        $header            = curl_getinfo($curl);
        $header_content    = substr($response, 0, $header['header_size']);

        trim(str_replace($header_content, '', $response));

        $pattern           = "#set-cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";

        preg_match_all($pattern, $header_content, $matches);

        $cookiesOut        = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut        = implode("; ", $matches['cookie']);

        if($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'xpro')->update([
                'username' => $uname,
                'password' => $pass,
                'cookies'  => $cookiesOut
            ]);
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $captcha_id = $dom->getElementById('captcha_id')->getAttribute('value');
        $csrf_token = $dom->getElementById('csrf')->getAttribute('value');

        $caption = 'Kode Captcha XPRO '.date('Y-m-d H:i:s');
        $file = "https://newxpro.telkom.co.id/store/img/captcha/$captcha_id.png";
        Telegram::sendPhoto($chatid, $caption, $file);

        print_r("Masukan Captcha :\n");
        $captcha = 0;
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);

        if(trim($line) == 'cancel')
        {
            print_r("ABORTING!\n");
            exit;
        }

        $captcha = trim($line);
        fclose($handle);
        print_r("response $captcha\n\n");

        // dd("username=$uname&password=$pass&terms=on&captcha[input]=$captcha&captcha[id]=$captcha_id&redirect_url=&csrf=$csrf_token&meta_csrf_name=&meta_csrf_value=");

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://newxpro.telkom.co.id/auth/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            // CURLOPT_ENCODING       => "",
            // CURLOPT_MAXREDIRS      => 10,
            // CURLOPT_TIMEOUT        => 0,
            // CURLOPT_FOLLOWLOCATION => true,
            // CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => urlencode("username=$uname&password=$pass&terms=on&captcha[input]=$captcha&captcha[id]=$captcha_id&redirect_url=&csrf=$csrf_token&meta_csrf_name=&meta_csrf_value="),
            CURLOPT_HTTPHEADER     => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Cookie: $cookiesOut"
            )
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        dd($response);
    }
}
