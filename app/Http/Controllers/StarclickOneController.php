<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class StarclickOneController extends Controller
{
    public static function login_sc_one($uname, $pass, $chatid)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/sc',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
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

        $token = $dom->getElementById('token')->getAttribute("value");

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/sc/user/preauth',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'guid=0&code=0&data='.urlencode('{"token":"'.$token.'","code":"'.$uname.'","password":"'.$pass.'"}'),
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              'Cookie: '.$cookiesOut
            ),
        ));
        $response = curl_exec($curl);

        $otp = 0;
        print_r("\nMasukan Kode OTP :\n");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) == 'cancel')
        {
            print_r("ABORTING!\n");
            exit;
        }
        $otp = trim($line);
        fclose($handle);

        $result = json_decode($response);

        $based64Captcha = "data:image/png;base64,".$result->data->captcha;

        list($type, $based64Captcha) = explode(';', $based64Captcha);
        list(, $based64Captcha) = explode(',', $based64Captcha);

        $imageData = base64_decode($based64Captcha);

        $filename = "sc1.jpg";

        file_put_contents($filename, $imageData);

        $caption = 'Kode Captcha Starclick One '.date('Y-m-d H:i:s');
        Telegram::sendPhoto($chatid, $caption, 'sc1.jpg');

        print_r("\nMasukan Captcha :\n");
        $captcha = 0;
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
            CURLOPT_URL => 'https://starclick.telkom.co.id/sc/index/n',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
              'Cookie: '.$cookiesOut
            ),
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/sc/user/auth',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'guid=0&code=0&data='.urlencode('{"token":"'.$token.'","code":"'.$uname.'","password":"'.$pass.'","otp":"'.$otp.'","captcha":"'.$captcha.'"}'),
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

        $cookiesOut = implode("; ", $matches['cookie']);
        print_r("Cookies Home Page : $cookiesOut\n\n");

        if ($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'starclick1')->update([
                'username' => $uname,
                'password' => $pass,
                'cookies'  => $cookiesOut
            ]);
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/retail/public/retail/user/get-session',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$cookiesOut
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response);
        dd($result);
    }

    public static function logout_sc_one()
    {
        DB::table('cookie_systems')->where('application', 'starclick1')->update([
            'username' => null,
            'password' => null,
            'cookies'  => null
        ]);

        $sc1 = DB::table('cookie_systems')->where('application', 'starclick1')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/logout',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$sc1->cookies
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        dd($response);
    }

    public static function refresh_sc_one()
    {
        $sc1 = DB::table('cookie_systems')->where('application', 'starclick1')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/retail/public/retail/user/get-session',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$sc1->cookies
            ),
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/sc',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$sc1->cookies
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        dd($response);
    }

    public static function grabstarclickoneweekly($witel)
    {
        $sc1 = DB::table('cookie_systems')->where('application', 'starclick1')->first();

        for ($i = 0;$i <= 14; $i++)
        {
            $datex = date('d/m/Y',strtotime("-$i days"));

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://starclick.telkom.co.id/retail/public/retail/user/get-session',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: '.$sc1->cookies
                ),
            ));
            curl_exec($curl);

            $link = 'https://starclick.telkom.co.id/retail/public/retail/api/tracking-naf?_dc=1694442833467&ScNoss=true&guid=0&code=0&data='.urlencode('{"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"Fieldchannel":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}').'&page=1&start=0&limit=10';

            curl_setopt_array($curl, array(
                CURLOPT_URL => $link,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: '.$sc1->cookies
                ),
            ));
            $response = curl_exec($curl);

            if (curl_errno($curl))
            {
                echo 'Error:' . curl_error($curl);
                curl_close($curl);
            }
            else
            {
                curl_close($curl);
                $response = json_decode($response);

                if ($response == null)
                {
                    print_r("starclick one session expired!");
                }

                $data = $response->data;
                $jumlahpage = round(@(int)$data->CNT/10);

                if (isset($data->LIST) && isset($data->CNT))
                {
                    $start = 0;

                    if ($data->CNT > 0 && $data->CNT < 10)
                    {
                        $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page=1&start='.$start.'&limit=10';

                        self::grabstarclickone_insert($witel, $datex, 1, 0, $sc1->cookies);

                        // exec('php /srv/htdocs/tomman_api/artisan grabstarclickone_insert ' . $witel . ' ' . $datex . ' 1 0 "' . $sc1->cookies . '" > /dev/null &');

                        print_r("php /srv/htdocs/tomman_api/artisan grabstarclickone_insert $witel $datex 1 0 $sc1->cookies > /dev/null &\n\n");
                    }
                    else
                    {
                        for ($x = 1; $x <= $jumlahpage; $x++)
                        {
                            $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page='.$x.'&start='.$start.'&limit=10';

                            if ($x == 1)
                            {
                                $start = $start + 11;
                            }
                            else
                            {
                                $start = $start + 10;
                            }

                            self::grabstarclickone_insert($witel, $datex, $x, $start, $sc1->cookies);

                            // exec('php /srv/htdocs/tomman_api/artisan grabstarclickone_insert ' . $witel . ' ' . $datex . ' ' . $x . ' ' . $start . ' "' . $sc1->cookies . '" > /dev/null &');

                            print_r("php /srv/htdocs/tomman_api/artisan grabstarclickone_insert $witel $datex $x $start $sc1->cookies > /dev/null &\n\n");
                        }
                    }
                }

            }
        }
    }

    public static function grabstarclickone_insert($witel, $datex, $x, $start, $cookies)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/retail/public/retail/user/get-session',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$cookies
            ),
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclick.telkom.co.id/retail/public/retail/api/tracking-naf?_dc=1694442833467&ScNoss=true&guid=0&code=0&data='.urlencode('{"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"Fieldchannel":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}').'&page='.$x.'&start='.$start.'&limit=10',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$cookies
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response);

        if (isset($response->data->LIST))
        {
            $list = $response->data->LIST;
            $total = count($list);

            foreach ($list as $k => $v)
            {
                $insert[] = [
                    "orderId"          => $v->ORDER_ID,
                    "orderIdInteger"   => $v->ORDER_ID,
                    "orderDate"        => date('Y-m-d H:i:s', strtotime($v->ORDER_DATE)),
                    "orderStatus"      => $v->ORDER_STATUS,
                    "orderDatePs"      => date('Y-m-d H:i:s', strtotime($v->ORDER_DATE_PS)),
                    "orderNo"          => $v->EXTERN_ORDER_ID,
                    "orderNcli"        => $v->NCLI,
                    "orderName"        => str_replace(array("'","’"),"",$v->CUSTOMER_NAME),
                    "witel"            => $v->WITEL,
                    "agent_id"         => $v->AGENT_ID,
                    "jenisPsb"         => $v->JENISPSB,
                    // "SOURCE"        => $v->SOURCE,
                    "sto"              => $v->STO,
                    "ndemSpeedy"       => $v->SPEEDY,
                    "ndemPots"         => $v->POTS,
                    "orderPackageName" => $v->PACKAGE_NAME,
                    // "KODEFIKASI_SC" => $v->KODEFIKASI_SC,
                    "orderStatus"      => $v->STATUS_RESUME,
                    "orderStatusId"    => $v->STATUS_CODE_SC,
                    // "USERNAME"      => $v->USERNAME,
                    "orderIdNcx"       => $v->ORDER_ID_NCX,
                    "orderCity"        => str_replace(array("'","’"),"",$v->CUSTOMER_ADDR),
                    "kcontact"         => str_replace(array("'","’"),"",$v->KCONTACT),
                    "orderAddr"        => str_replace(array("'","’"),"",$v->INS_ADDRESS),
                    // "CITY_NAME"     => $v->CITY_NAME,
                    "internet"         => $v->ND_INTERNET,
                    "noTelp"           => $v->ND_POTS,
                    "lat"              => $v->GPS_LATITUDE,
                    "lon"              => $v->GPS_LONGITUDE,
                    "tnNumber"         => $v->TN_NUMBER,
                    "alproname"        => $v->LOC_ID,
                    // "ODP_ID"        => $v->ODP_ID,
                    "reserveTn"        => $v->RESERVE_TN,
                    "reservePort"      => $v->RESERVE_PORT
                ];

                print_r("saved order id $v->ORDER_ID\n");
            }

            self::insertOrUpdate($insert);
            sleep(1);

            print_r("\nFinish Grab Backend SC ONE Total $total\n");
        }
    }

    public static function insertOrUpdate(array $rows)
    {
        $table = 'Data_Pelanggan_Starclick';
        $first = reset($rows);
        $columns = implode(
            ',',
            array_map(function ($value) {
                return "$value";
            }, array_keys($first))
        );
        $values = implode(
            ',',
            array_map(function ($row) {
                return '(' . implode(
                    ',',
                    array_map(function ($value) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }, $row)
                ) . ')';
            }, $rows)
        );
        $updates = implode(
            ',',
            array_map(function ($value) {
                return "$value = VALUES($value)";
            }, array_keys($first))
        );
        $sql = "INSERT INTO {$table}({$columns}) VALUES {$values} ON DUPLICATE KEY UPDATE {$updates}";
        return \DB::connection('db_t1')->statement($sql);
    }
}
