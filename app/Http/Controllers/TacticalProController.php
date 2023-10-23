<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class TacticalProController extends Controller
{
    public static function orderPickupOnline($witel)
    {
        return DB::select('
            SELECT
                dps.order_id as sc_id,
                dps.orderDate as order_date,
                dps.orderDatePs as ps_date,
                dps.orderNo as no_order,
                dps.orderIdNcx as id_ncx,
                dps.ncli,
                dps.customer_name as nama_pelanggan,
                dps.ins_address as alamat_pelanggan,
                dps.internet as no_inet,
                dps.no_telp,
                dps.loc_id as alpro,
                dps.witel,
                dps.kcontact,
                "OSS PROVISIONING ISSUED" as status_sc,
                dps.username as user_id,
                dps.jenis_psb as layanan,
                dps.sto as id_sto
                FROM Data_Pelanggan_Starclick dps
            WHERE
                dps.status_code_sc <> 1500 AND
                dps.jenis_psb LIKE "AO%" AND
                dps.kcontact NOT LIKE "%WMS%" AND
                dps.witel = "' . $witel . '"
            GROUP BY dps.order_id
        ');
    }

    public static function sendPickupOnline($witel)
    {
        $data = self::orderPickupOnline($witel);

        if ($data)
        {
            foreach ($data as $value)
            {
                $postfields['source'] = 'nonbackend';
                $postfields['data'] = array($value);

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://tacticalpro.co.id/api/workorder/creates',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($postfields),
                    CURLOPT_HTTPHEADER => array(
                        'apisecret: bnB3WWhYSmE4ZmQvcnh6dnd6QnV1dz09',
                        'Content-Type: application/json'
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);

                // dd(json_decode($response));

                // DB::transaction(function () use ($value, $response, $witel) {

                //     $result = json_decode($response);

                //     DB::table('log_tacticalpro')->insert([
                //         'order_id'      => $value->sc_id,
                //         'code'          => $result->code,
                //         'message'       => $result->message,
                //         'created_by'    => $witel
                //     ]);
                // });

                print_r("Success Send Order $value->sc_id \n");
            }

            // hapus data pelanggan starclick
            DB::table('Data_Pelanggan_Starclick')->where('witel', $witel)->delete();

            switch ($witel)
            {
                case 'KALTARA':
                    $chatID = '-1001790850331';
                    break;

                case 'BALIKPAPAN':
                    $chatID = '-306306083';
                    break;
            }

            $message = "Success Send Order <b>Total " . count($data) . "</b> [TACTICAL][" . $witel . "][API] \n\n" . date('d/m/Y H:i:s');
            Telegram::sendMessage($chatID, $message);

            return $response;

        }
        else
        {

            switch ($witel)
            {
                case 'KALTARA':
                    $chatID = '-1001790850331';
                    break;

                case 'BALIKPAPAN':
                    $chatID = '-306306083';
                    break;
            }

            $message = "Order not found! [TACTICAL][" . $witel . "][API] \n\n" . date('d/m/Y H:i:s');
            Telegram::sendMessage($chatID, $message);

            // hapus data pelanggan starclick
            DB::table('Data_Pelanggan_Starclick')->truncate();

            print_r("Order not found! [TACTICAL][API] \n");
        }
    }

    public static function login_tacticalpro($uname, $pass, $chatid)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://tacticalpro.co.id/auth/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ));
        $response = curl_exec($curl);

        $err = curl_errno($curl);
        $errmsg = curl_error($curl);
        $header = curl_getinfo($curl);
        $header_content = substr($response, 0, $header['header_size']);
        trim(str_replace($header_content, '', $response));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all ($pattern, $header_content, $matches);
        print_r($matches['cookie']);
        $cookiesOut = "";
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", $matches['cookie']);

        if($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'tacticalpro')->update([
                'username' => $uname,
                'password' => $pass,
                'cookies'  => $cookiesOut
            ]);
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));

        $based64Captcha = $dom->getElementById('CaptchaDiv')->getAttribute("src");
        list($type, $based64Captcha) = explode(';', $based64Captcha);
        list(, $based64Captcha) = explode(',', $based64Captcha);
        $imageData = base64_decode($based64Captcha);
        $filename = "tacticalpro_captcha.jpg";
        file_put_contents($filename, $imageData);

        $caption = 'Kode Captcha TacticalPro '.date('Y-m-d H:i:s');
        Telegram::sendPhoto($chatid, $caption, 'tacticalpro_captcha.jpg');

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

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://tacticalpro.co.id/auth/dologin/v2',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => 'username='.$uname.'&password='.$pass.'&type_login=1&CaptchaInput='.$captcha,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$cookiesOut
            ),
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tacticalpro.co.id/auth/otp',
            CURLOPT_RETURNTRANSFER => true,
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
        curl_exec($curl);

        print_r("Masukan Kode OTP :\n");
        $otp = 0;
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);

        if(trim($line) == 'cancel')
        {
            print_r("ABORTING!\n");
            exit;
        }

        $otp = trim($line);
        fclose($handle);
        print_r("response $otp\n\n");

        if (count(str_split($otp)) == 4)
        {
            $code1 = str_split($otp)[0];
            $code2 = str_split($otp)[1];
            $code3 = str_split($otp)[2];
            $code4 = str_split($otp)[3];

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://tacticalpro.co.id/auth/otp/check',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'code_1='.$code1.'&code_2='.$code2.'&code_3='.$code3.'&code_4='.$code4,
                CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$cookiesOut
                ),
            ));
            curl_exec($curl);

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://tacticalpro.co.id/',
                CURLOPT_RETURNTRANSFER => true,
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
            curl_exec($curl);
            curl_close($curl);

            self::refresh_tacticalpro('KALSEL');
        }
        else
        {
            curl_close($curl);
            dd("failed!\n");
        }
    }

    public static function refresh_tacticalpro()
    {
        $tacticalpro = DB::table('cookie_systems')->where('application', 'tacticalpro')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://tacticalpro.co.id',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'Cookie: '.$tacticalpro->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        print_r("$response");
    }

    public static function workorderTactical()
    {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');

        $tacticalpro = DB::table('cookie_systems')->where('application', 'tacticalpro')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tacticalpro.co.id/workorder/get',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'draw=1&columns%5B0%5D%5Bdata%5D=&columns%5B0%5D%5Bname%5D=&columns%5B0%5D%5Bsearchable%5D=true&columns%5B0%5D%5Borderable%5D=false&columns%5B0%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B0%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B1%5D%5Bdata%5D=durasi&columns%5B1%5D%5Bname%5D=&columns%5B1%5D%5Bsearchable%5D=true&columns%5B1%5D%5Borderable%5D=false&columns%5B1%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B1%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B2%5D%5Bdata%5D=track_id&columns%5B2%5D%5Bname%5D=&columns%5B2%5D%5Bsearchable%5D=true&columns%5B2%5D%5Borderable%5D=false&columns%5B2%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B2%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B3%5D%5Bdata%5D=sc_id&columns%5B3%5D%5Bname%5D=&columns%5B3%5D%5Bsearchable%5D=true&columns%5B3%5D%5Borderable%5D=false&columns%5B3%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B3%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B4%5D%5Bdata%5D=jenis_paket&columns%5B4%5D%5Bname%5D=&columns%5B4%5D%5Bsearchable%5D=true&columns%5B4%5D%5Borderable%5D=false&columns%5B4%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B4%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B5%5D%5Bdata%5D=layanan&columns%5B5%5D%5Bname%5D=&columns%5B5%5D%5Bsearchable%5D=true&columns%5B5%5D%5Borderable%5D=false&columns%5B5%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B5%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B6%5D%5Bdata%5D=no_inet&columns%5B6%5D%5Bname%5D=&columns%5B6%5D%5Bsearchable%5D=true&columns%5B6%5D%5Borderable%5D=false&columns%5B6%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B6%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B7%5D%5Bdata%5D=order_date&columns%5B7%5D%5Bname%5D=&columns%5B7%5D%5Bsearchable%5D=true&columns%5B7%5D%5Borderable%5D=false&columns%5B7%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B7%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B8%5D%5Bdata%5D=alpro&columns%5B8%5D%5Bname%5D=&columns%5B8%5D%5Bsearchable%5D=true&columns%5B8%5D%5Borderable%5D=false&columns%5B8%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B8%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B9%5D%5Bdata%5D=status_wo&columns%5B9%5D%5Bname%5D=&columns%5B9%5D%5Bsearchable%5D=true&columns%5B9%5D%5Borderable%5D=false&columns%5B9%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B9%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B10%5D%5Bdata%5D=teknisi&columns%5B10%5D%5Bname%5D=&columns%5B10%5D%5Bsearchable%5D=true&columns%5B10%5D%5Borderable%5D=false&columns%5B10%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B10%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B11%5D%5Bdata%5D=sto&columns%5B11%5D%5Bname%5D=&columns%5B11%5D%5Bsearchable%5D=true&columns%5B11%5D%5Borderable%5D=false&columns%5B11%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B11%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B12%5D%5Bdata%5D=mitra&columns%5B12%5D%5Bname%5D=&columns%5B12%5D%5Bsearchable%5D=true&columns%5B12%5D%5Borderable%5D=false&columns%5B12%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B12%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B13%5D%5Bdata%5D=witel&columns%5B13%5D%5Bname%5D=&columns%5B13%5D%5Bsearchable%5D=true&columns%5B13%5D%5Borderable%5D=false&columns%5B13%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B13%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B14%5D%5Bdata%5D=jenis_pekerjaan&columns%5B14%5D%5Bname%5D=&columns%5B14%5D%5Bsearchable%5D=true&columns%5B14%5D%5Borderable%5D=false&columns%5B14%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B14%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B15%5D%5Bdata%5D=ps_date&columns%5B15%5D%5Bname%5D=&columns%5B15%5D%5Bsearchable%5D=true&columns%5B15%5D%5Borderable%5D=false&columns%5B15%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B15%5D%5Bsearch%5D%5Bregex%5D=false&start=0&length=10&search%5Bvalue%5D=&search%5Bregex%5D=false&filter_date='.$startDate.'+s%2Fd+'.$endDate.'&id=index&breakdown=SHJncnlhemZjVWM9&source=',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$tacticalpro->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response);

        if ($result != null)
        {
            if ($result->recordsFiltered > 0)
            {
                print_r("start grab from records total $result->recordsFiltered\n\n");

                DB::connection('db_t1')->table('tacticalpro_tr6')->whereBetween('created_at_format', [$startDate, $endDate])->delete();

                $rows = @$result->recordsFiltered;

                $split = 700;

                for ($i = 0; $i <= $rows; $i++)
                {
                    $output[] = $i;
                }

                $output = array_chunk($output, $split);
                // $fd = [];

                foreach($output as $k => $v)
                {
                    if ($k == 0)
                    {
                        $current = 0;
                    }
                    else
                    {
                        $current = current($v);
                    }

                    // $fd[$k][0] = current($v);
                    // $fd[$k][1] = end($v);

                    print_r("pages_workorderTactical $current to $split\n");

                    self::pages_workorderTactical($startDate, $endDate, $current, $split);
                }
            }
        }
    }

    public static function pages_workorderTactical($startDate, $endDate, $x, $y)
    {
        $tacticalpro = DB::table('cookie_systems')->where('application', 'tacticalpro')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tacticalpro.co.id/workorder/get',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'draw=1&columns%5B0%5D%5Bdata%5D=&columns%5B0%5D%5Bname%5D=&columns%5B0%5D%5Bsearchable%5D=true&columns%5B0%5D%5Borderable%5D=false&columns%5B0%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B0%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B1%5D%5Bdata%5D=durasi&columns%5B1%5D%5Bname%5D=&columns%5B1%5D%5Bsearchable%5D=true&columns%5B1%5D%5Borderable%5D=false&columns%5B1%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B1%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B2%5D%5Bdata%5D=track_id&columns%5B2%5D%5Bname%5D=&columns%5B2%5D%5Bsearchable%5D=true&columns%5B2%5D%5Borderable%5D=false&columns%5B2%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B2%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B3%5D%5Bdata%5D=sc_id&columns%5B3%5D%5Bname%5D=&columns%5B3%5D%5Bsearchable%5D=true&columns%5B3%5D%5Borderable%5D=false&columns%5B3%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B3%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B4%5D%5Bdata%5D=jenis_paket&columns%5B4%5D%5Bname%5D=&columns%5B4%5D%5Bsearchable%5D=true&columns%5B4%5D%5Borderable%5D=false&columns%5B4%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B4%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B5%5D%5Bdata%5D=layanan&columns%5B5%5D%5Bname%5D=&columns%5B5%5D%5Bsearchable%5D=true&columns%5B5%5D%5Borderable%5D=false&columns%5B5%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B5%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B6%5D%5Bdata%5D=no_inet&columns%5B6%5D%5Bname%5D=&columns%5B6%5D%5Bsearchable%5D=true&columns%5B6%5D%5Borderable%5D=false&columns%5B6%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B6%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B7%5D%5Bdata%5D=order_date&columns%5B7%5D%5Bname%5D=&columns%5B7%5D%5Bsearchable%5D=true&columns%5B7%5D%5Borderable%5D=false&columns%5B7%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B7%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B8%5D%5Bdata%5D=alpro&columns%5B8%5D%5Bname%5D=&columns%5B8%5D%5Bsearchable%5D=true&columns%5B8%5D%5Borderable%5D=false&columns%5B8%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B8%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B9%5D%5Bdata%5D=status_wo&columns%5B9%5D%5Bname%5D=&columns%5B9%5D%5Bsearchable%5D=true&columns%5B9%5D%5Borderable%5D=false&columns%5B9%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B9%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B10%5D%5Bdata%5D=teknisi&columns%5B10%5D%5Bname%5D=&columns%5B10%5D%5Bsearchable%5D=true&columns%5B10%5D%5Borderable%5D=false&columns%5B10%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B10%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B11%5D%5Bdata%5D=sto&columns%5B11%5D%5Bname%5D=&columns%5B11%5D%5Bsearchable%5D=true&columns%5B11%5D%5Borderable%5D=false&columns%5B11%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B11%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B12%5D%5Bdata%5D=mitra&columns%5B12%5D%5Bname%5D=&columns%5B12%5D%5Bsearchable%5D=true&columns%5B12%5D%5Borderable%5D=false&columns%5B12%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B12%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B13%5D%5Bdata%5D=witel&columns%5B13%5D%5Bname%5D=&columns%5B13%5D%5Bsearchable%5D=true&columns%5B13%5D%5Borderable%5D=false&columns%5B13%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B13%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B14%5D%5Bdata%5D=jenis_pekerjaan&columns%5B14%5D%5Bname%5D=&columns%5B14%5D%5Bsearchable%5D=true&columns%5B14%5D%5Borderable%5D=false&columns%5B14%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B14%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B15%5D%5Bdata%5D=ps_date&columns%5B15%5D%5Bname%5D=&columns%5B15%5D%5Bsearchable%5D=true&columns%5B15%5D%5Borderable%5D=false&columns%5B15%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B15%5D%5Bsearch%5D%5Bregex%5D=false&start='.$x.'&length='.$y.'&search%5Bvalue%5D=&search%5Bregex%5D=false&filter_date='.$startDate.'+s%2Fd+'.$endDate.'&id=index&breakdown=SHJncnlhemZjVWM9&source=',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$tacticalpro->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response);

        $insert[] = [];

        if ($result <> null)
        {
            foreach ($result->data as $data)
            {
                $v1_teknisi = str_replace(array(' - - -', '- - -', ' - -', '- -', '|'), '', $data->teknisi);
                $v2_teknisi = str_replace(' | ', '', $v1_teknisi);
                if (strlen($v2_teknisi) == 2)
                {
                    $v3_teknisi = str_replace('  ', '', $v2_teknisi);
                }
                else
                {
                    $v3_teknisi = $v2_teknisi;
                }
                if ($v3_teknisi == '')
                {
                    $teknisi = null;
                }
                else
                {
                    $teknisi = $v3_teknisi;
                }

                $insert[] = [
                    'durasi'                    => $data->durasi,
                    'tgl_pickup'                => $data->tgl_pickup,
                    'tgl_berangkat_kerja'       => $data->tgl_berangkat_kerja,
                    'tgl_mulai_kerja'           => $data->tgl_mulai_kerja,
                    'tgl_selesai_kerja'         => $data->tgl_selesai_kerja,
                    'keterangan'                => $data->keterangan,
                    'last_status'               => $data->last_status,
                    'enc_id'                    => $data->enc_id,
                    'witel_ms2n'                => $data->witel_ms2n,
                    'id'                        => $data->id,
                    'id_pickup_order_survey'    => $data->id_pickup_order_survey,
                    'id_pickup_order_instalasi' => $data->id_pickup_order_instalasi,
                    'sc_id'                     => $data->sc_id,
                    'track_id'                  => $data->track_id,
                    'order_date'                => $data->order_date,
                    'ps_date'                   => $data->ps_date,
                    'nama_pelanggan'            => $data->nama_pelanggan,
                    'nohp_pelanggan'            => $data->nohp_pelanggan,
                    'titik_koordinat'           => $data->titik_koordinat,
                    'alpro'                     => $data->alpro,
                    'id_sto'                    => $data->id_sto,
                    'no_telp'                   => $data->no_telp,
                    'no_inet'                   => $data->no_inet,
                    'tgl_manja_pelanggan'       => $data->tgl_manja_pelanggan,
                    'kcontact'                  => $data->kcontact,
                    'alamat_pelanggan'          => $data->alamat_pelanggan,
                    'layanan'                   => $data->layanan,
                    'info_tambahan'             => $data->info_tambahan,
                    'qrcode'                    => $data->qrcode,
                    'teknisi_1'                 => $data->teknisi_1,
                    'teknisi_2'                 => $data->teknisi_2,
                    'amcrew'                    => $data->amcrew,
                    'status_wo'                 => $data->status_wo,
                    'id_witel'                  => $data->id_witel,
                    'reg'                       => $data->reg,
                    'source'                    => $data->source,
                    'jenis_paket'               => $data->jenis_paket,
                    'tanggal_fo'                => $data->tanggal_fo,
                    'tanggal_fulfill'           => $data->tanggal_fulfill,
                    'datestamp'                 => $data->datestamp,
                    'revoke'                    => $data->revoke,
                    'created_at_format'         => date('Y-m-d', strtotime($data->created_at)),
                    'created_at'                => $data->created_at,
                    'created_by'                => $data->created_by,
                    'updated_at'                => $data->updated_at,
                    'updated_at_format'         => date('Y-m-d', strtotime($data->updated_at)),
                    'updated_by'                => $data->updated_by,
                    'deleted_at'                => $data->deleted_at,
                    'onwork_1'                  => $data->onwork_1,
                    'onwork_2'                  => $data->onwork_2,
                    'teknisi'                   => $teknisi,
                    'sto'                       => $data->sto,
                    'mitra'                     => $data->mitra,
                    'uc_by'                     => $data->uc_by,
                    'uc_at'                     => $data->uc_at,
                    'disable_assign'            => $data->disable_assign,
                    'witel'                     => $data->witel,
                    'detail_revoke'             => $data->detail_revoke,
                    'recycle'                   => $data->recycle
                ];
            }

            if (array_filter($insert))
            {
                $chunk = array_chunk(array_filter($insert), 350);

                foreach ($chunk as $numb => $dump)
                {
                    DB::connection('db_t1')->table('tacticalpro_tr6')->insert($dump);

                    print_r("saved page $numb success from $x to $y!\n");
                }

                DB::connection('db_t1')->statement('DELETE a1 FROM tacticalpro_tr6 a1, tacticalpro_tr6 a2 WHERE a1.id_tacticalpro_tr6 > a2.id_tacticalpro_tr6 AND a1.sc_id = a2.sc_id');

                print_r("Finish Syncron TacticalProvisioning Page $x to $y\n");
            }
        }
        else
        {
            print_r("Miss Syncron TacticalProvisioning Page $x to $y Total null :(\n");
        }
    }
}
