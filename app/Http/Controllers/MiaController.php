<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class MiaController extends Controller
{
    public static function login_mia($uname, $pass, $chatid)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://mia.telkom.co.id/mie',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $header = curl_getinfo($curl);
        $header_content = substr($response, 0, $header['header_size']);
        trim(str_replace($header_content, '', $response));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;

        $cookiesOut = implode("; ", $matches['cookie']);

        print_r("Cookies Login Page : $cookiesOut\n\n");

        $inputs = $dom->getElementsByTagName('input');
        $token = '';
        foreach ($inputs as $input)
        {
            if ($input->getAttribute('name') === 'token')
            {
                $token = $input->getAttribute('value');

                break;
            }
        }

        $fp = fopen('mia.jpg', 'w+');
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://mia.telkom.co.id/actor/captcha/image',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIE => $cookiesOut,
            CURLOPT_FILE => $fp,
        ));
        if (curl_exec($curl) === false)
        {
            print_r("Curl error: ".curl_error($curl)."\n\n");
        }
        else
        {
            print_r("Captcha berhasil diunduh dan disimpan.\n\n");
        }
        fclose($fp);

        $caption = 'Kode Captcha MIA '.date('Y-m-d H:i:s');
        Telegram::sendPhoto($chatid, $caption, 'mia.jpg');

        $captcha = 0;
        print_r("\nMasukan Kode Captcha :\n");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) == 'cancel')
        {
            print_r("ABORTING!\n");
            exit;
        }
        $captcha = trim($line);
        fclose($handle);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://mia.telkom.co.id/composite/user/authenticate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'guid=0&code=0&data='.urlencode('{"token":"'.$token.'","code":"'.$uname.'","password":"'.$pass.'","captcha":"'.$captcha.'"}'),
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              'Cookie: '.$cookiesOut
            ),
        ));
        $response = curl_exec($curl);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $header = curl_getinfo($curl);
        $header_content = substr($response, 0, $header['header_size']);
        trim(str_replace($header_content, '', $response));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;

        print_r("Cookies Auth Login : $cookiesOut\n\n");

        if($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'mia')->update([
                'username' => $uname,
                'password' => $pass,
                'cookies'  => $cookiesOut
            ]);
        }

        curl_close($curl);

        dd($response);
    }

    public static function refresh_mia()
    {
        $mia = DB::table('cookie_systems')->where('application', 'mia')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://mia.telkom.co.id/mie',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: ',$mia->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        dd($response);
    }
}
