<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class StarclickController extends Controller
{
    public static function login_sc($uname, $pass, $chatid)
    {
        $captcha = $otp = 0;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/login.php',
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
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
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
            CURLOPT_POSTFIELDS => 'param=0',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));
        $response = curl_exec($curl);
        $header = curl_getinfo($curl);
        $header_content = substr($response, 0, $header['header_size']);
        trim(str_replace($header_content, '', $response));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", $matches['cookie']);

        if ($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'starclick')->update([
                'username' => $uname,
                'password' => $pass,
                'cookies'  => $cookiesOut
            ]);
        }

        print_r("Cookies Login Page : $cookiesOut\n\n");

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/send-otp',
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
            CURLOPT_POSTFIELDS => 'guid=&code=&data='.urlencode('{"code":"'.$uname.'"}'),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$cookiesOut
            ),
        ));
        curl_exec($curl);

        $fp = fopen('sc.jpg', 'w+');
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/actor/captcha/image',
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

        $caption = 'Kode Captcha Starclick '.date('Y-m-d H:i:s');
        Telegram::sendPhoto($chatid, $caption, 'sc.jpg');

        print_r("Masukan Captcha :\n");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) == 'cancel')
        {
            print_r("ABORTING!\n");
            exit;
        }
        $captcha = trim($line);
        fclose($handle);

        print_r("\nMasukan Kode OTP :\n");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) == 'cancel') {
            print_r("ABORTING!\n");
            exit;
        }
        $otp = trim($line);
        fclose($handle);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/authenticate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'guid=&code=&data='.urlencode('{"code":"'.$uname.'","password":"'.$pass.'","otp":"'.$otp.'","captcha":"'.$captcha.'"}'),
            CURLOPT_COOKIE => $cookiesOut,
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIE => $cookiesOut,
        ));
        $response = curl_exec($curl);
        $header = curl_getinfo($curl);
        $header_content = substr($response, 0, $header['header_size']);
        trim(str_replace($header_content, '', $response));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", $matches['cookie']);

        if ($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'starclick')->update([
                'username' => $uname,
                'password' => $pass,
                'cookies'  => $cookiesOut
            ]);
        }

        print_r("Cookies Session Page : $cookiesOut\n\n");

        curl_close($curl);

        $result = json_decode($response);
        dd($result);
    }

    public static function refresh_sc()
    {
        $starclick = DB::table('cookie_systems')->where('application', 'starclick')->first();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIE => $starclick->cookies,
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response);
        dd($result);
    }

    public static function logout_sc()
    {
        DB::table('cookie_systems')->where('application', 'starclick')->update([
            'username' => null,
            'password' => null,
            'cookies'  => null
        ]);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);

        curl_setopt($ch, CURLOPT_URL, 'https://starclickncx.telkom.co.id/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIE, "");

        $response = curl_exec($ch);
        curl_close($ch);

        dd($response);
    }

    public static function grabstarclick_pi($witel)
    {
        $starclick = DB::table('cookie_systems')->where('application', 'starclick')->first();
        $txtmsg = "";

        for ($i = 0;$i <= 2; $i++) {
            $datex = date('d/m/Y',strtotime("-$i days"));
            $ch = curl_init();

            //get session
            curl_setopt($ch, CURLOPT_URL, 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session');
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":"1202","Fieldtransaksi":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page=1&start=0&limit=10';
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, $starclick->cookies);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $result = curl_exec($ch);

            if (curl_errno($ch))
            {
                echo 'Error:' . curl_error($ch);
                curl_close($ch);
            }
            else
            {
                curl_close($ch);
                $result = json_decode($result);

                if($result == null)
                {
                    print_r("starclick session expired!");
                }

                if ($result)
                {
                    $list = $result->data;
                    $jumlahpage = round(@(int)$list->CNT/10);
                    $txtmsg .= "\n".$datex." total page : ".$jumlahpage;
                    if (isset($list->CNT) && isset($list->LIST))
                    {
                        $list = @$list->LIST;
                        $start = 0;
                        for ($x = 1; $x <= $jumlahpage; $x++)
                        {
                            $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":"1202","Fieldtransaksi":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page='.$x.'&start=0&limit=10';
                            if ($x == 1)
                            {
                                $start = $start + 11;
                            } else {
                                $start = $start + 10;
                            }
                            exec('php /srv/htdocs/tomman_api/artisan grabstarclick_pi_insert ' . $witel . ' ' . $datex . ' ' . $x . ' ' . $start . ' "' . $starclick->cookies . '" > /dev/null &');

                            print_r("php /srv/htdocs/tomman_api/artisan grabstarclick_pi_insert $witel $datex $x $start $starclick->cookies > /dev/null &\n\n");
                        }
                    }
                }
            }
            sleep(1);
        }

        print_r("$txtmsg\n\n");

        DB::connection('db_t1')->table('cookie_systems')->where([
            ['application', 'starclick'],
            ['witel', 'KALSEL']
        ])->update([
            'last_sync' => date('Y-m-d H:i:s')
        ]);
    }

    public static function grabstarclick($witel, $chatid, $datex = null)
    {
        if ($datex == null)
        {
            $datex = date('d/m/Y');
        }

        $datex = date('d/m/Y', strtotime($datex));

        $starclick = DB::table('cookie_systems')->where('application', 'starclick')->first();
        $txtmsg = "";

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $ch = curl_init();

        //get session
        curl_setopt($ch, CURLOPT_URL, 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);

        $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"' . $witel . '","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"StartDate":"' . $datex . '","EndDate":"' . $datex . '","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page=1&start=0&limit=10';

        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $starclick->cookies);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);

        if (curl_errno($ch))
        {
            echo 'Error:' . curl_error($ch);
            curl_close($ch);
        } else {
            curl_close($ch);
            $result = json_decode($result);

            if ($result == null)
            {
                Telegram::sendMessage($chatid, 'Login Starclick is Expired');
            }

            if ($result->data->LIST != null)
            {
                $list = $result->data;
                $jumlahpage = round(@(int)$list->CNT / 10);
                $txtmsg .= "\n" . $datex . " total page : " . $jumlahpage;
                if (isset($list->CNT) && isset($list->LIST))
                {
                    $list = @$list->LIST;
                    $start = 0;
                    for ($x = 1; $x <= $jumlahpage; $x++)
                    {
                        $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"' . $witel . '","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"StartDate":"' . $datex . '","EndDate":"' . $datex . '","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page=' . $x . '&start=' . $start . '&limit=10';
                        if ($x == 1)
                        {
                            $start = $start + 11;
                        } else {
                            $start = $start + 10;
                        }
                        exec('php /srv/htdocs/tomman_api/artisan grabstarclick_insert ' . $witel . ' ' . $datex . ' ' . $x . ' ' . $start . ' "' . $starclick->cookies . '" > /dev/null &');

                        print_r("php /srv/htdocs/tomman_api/artisan grabstarclick_insert $witel $datex $x $start $starclick->cookies > /dev/null &\n\n");
                    }
                }
            } else {
                print_r("data is null!\n");
            }
        }
        print_r("$txtmsg\n\n");

        sleep(1);
    }

    public static function grabstarclickweekly($witel)
    {
        $starclick = DB::table('cookie_systems')->where('application', 'starclick')->first();
        $txtmsg = "";

        for ($i = 0;$i <= 14; $i++) {
            $datex = date('d/m/Y',strtotime("-$i days"));
            $ch = curl_init();

            //get session
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_COOKIE => $starclick->cookies,
            ));
            curl_exec($ch);

            $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page=1&start=0&limit=10';

            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, $starclick->cookies);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $result = curl_exec($ch);

            if (curl_errno($ch))
            {
                echo 'Error:' . curl_error($ch);
                curl_close($ch);
            } else {
                curl_close($ch);
                $result = json_decode($result);

                if($result == null)
                {
                    print_r("starclick session expired!");
                }

                if ($result)
                {
                    $list = $result->data;
                    $jumlahpage = round(@(int)$list->CNT/10);
                    $txtmsg .= "\n".$datex." total page : ".$jumlahpage;
                    if (isset($list->CNT) && isset($list->LIST))
                    {
                        $list = @$list->LIST;
                        $start = 0;
                        for ($x = 1; $x <= $jumlahpage; $x++)
                        {
                            $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page='.$x.'&start='.$start.'&limit=10';

                            if ($x == 1)
                            {
                                $start = $start + 11;
                            } else {
                                $start = $start + 10;
                            }

                            exec('php /srv/htdocs/tomman_api/artisan grabstarclick_insert ' . $witel . ' ' . $datex . ' ' . $x . ' ' . $start . ' "' . $starclick->cookies . '" > /dev/null &');

                            print_r("php /srv/htdocs/tomman_api/artisan grabstarclick_insert $witel $datex $x $start $starclick->cookies > /dev/null &\n\n");
                        }
                    }
                }
            }
            sleep(1);
        }

        print_r("$txtmsg\n\n");

        DB::connection('db_t1')->table('cookie_systems')->where([
            ['application', 'starclick'],
            ['witel', 'KALSEL']
        ])->update([
            'last_sync' => date('Y-m-d H:i:s')
        ]);
    }

    public static function grabstarclick_pi_insert($witel, $datex, $x, $start, $cookies)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        print_r("SYNC TO STARCLICKNCX \n");
        $ch = curl_init();

        //get session
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIE => $cookies,
        ));
        curl_exec($ch);

        $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":"1202","Fieldtransaksi":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page='.$x.'&start='.$start.'&limit=10';
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);

        if (curl_errno($ch))
        {
            echo 'Error:' . curl_error($ch);
            curl_close($ch);
        }
        else
        {
            curl_close($ch);
            $result = json_decode($result);

            $allRecord = array();

            if ($result)
            {
                $list = $result->data;

                if ($list)
                {
                    $list = $list->LIST;
                    foreach ($list as $data)
                    {
                            $orderDate = "";
                            $orderDatePs = "";
                            if (!empty($data->ORDER_DATE_PS)) $orderDatePs = date('Y-m-d H:i:s',strtotime(@$data->ORDER_DATE_PS));
                            if (!empty($data->ORDER_DATE)) $orderDate = date('Y-m-d H:i:s',strtotime(@$data->ORDER_DATE));
                            if ($data->ND_INTERNET <> '')
                            {
                                $int = explode('~', $data->ND_INTERNET);
                                if (count($int) > 1)
                                {
                                    $internet = $int[1];
                                } else {
                                    $internet = $data->ND_INTERNET;
                                }
                            };
                            if ($data->ND_POTS<>'')
                            {
                                $telp = explode('~', $data->ND_POTS);
                                if (count($telp) > 1)
                                {
                                    $noTelp = $telp[1];
                                } else {
                                    $noTelp = $data->ND_POTS;
                                }
                            };
                            $get_myir = explode(';',$data->KCONTACT);
                            if (count($get_myir) > 0)
                            {
                            if ($get_myir[0] == "MYIR" || $get_myir[0] == "MI")
                            {
                                $get_myir_2 = explode('-',$get_myir[1]);
                                $myir = $get_myir_2[1];
                            } else {
                                $myir = "\N";
                            }
                            } else {
                                $myir = "\N";
                            }
                            //get STO
                            $get_sto = explode('-',@$data->LOC_ID);
                            if (count($get_sto) > 0)
                            {
                                $STO = @$get_sto[1];
                            } else {
                                $STO = "N";
                            }
                            // echo $data->LOC_ID;
                            $allRecord[] = array(
                                "orderId"        => @$data->ORDER_ID,
                                "orderIdInteger" => @$data->ORDER_ID,
                                "orderName"      => str_replace(array("'","’"),"",@$data->CUSTOMER_NAME),
                                "orderAddr"      => str_replace(array("'","’"),"",@$data->INS_ADDRESS),
                                "orderNotel"     => @$data->POTS,
                                "orderDate"      => $orderDate,
                                "orderDatePs"    => $orderDatePs,
                                "orderCity"      => str_replace(array("'","’"),"",@$data->CUSTOMER_ADDR),
                                "orderStatus"    => @$data->STATUS_RESUME,
                                "orderStatusId"  => @$data->STATUS_CODE_SC,
                                "orderNcli"      => @$data->NCLI,
                                "ndemSpeedy"     => @$data->ND_INTERNET,
                                "ndemPots"       => @$data->ND_POTS,
                                "orderPaketID"   => @$data->ODP_ID,
                                "kcontact"       => str_replace(array("'","’")," ",@$data->KCONTACT),
                                "username"       => @$data->USERNAME,
                                "alproName"      => @$data->LOC_ID,
                                "tnNumber"       => @$data->POTS,
                                "reservePort"    => @$data->ODP_ID,
                                "jenisPsb"       => @$data->JENISPSB,
                                "sto"            => @$STO,
                                "lat"            => @$data->GPS_LATITUDE,
                                "lon"            => @$data->GPS_LONGITUDE,
                                "internet"       => @$internet,
                                "noTelp"         => @$noTelp,
                                "orderPaket"     => @$data->PACKAGE_NAME,
                                "myir"           => $myir,
                                "myir_no"        => $myir,
                                "witel"          => @$data->WITEL,
                                "agent_id"       => @$data->AGENT_ID
                            );
                            print_r("$data->ORDER_ID\n");
                    }
                }
            }
            print_r("saving\n");

            $srcarr = array_chunk($allRecord,500);
            foreach($srcarr as $item)
            {
                self::insertOrUpdate($item);
            }

        }
    }

    public static function grabstarclick_insert($witel, $datex, $x, $start, $cookies)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        print_r("SYNC TO STARCLICKNCX \n");
        $ch = curl_init();

        //get session
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIE => $cookies,
        ));
        curl_exec($ch);

        $link = 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/tracking-naf?_dc=1593610106271&ScNoss=true&guid=0&code=0&data={"SearchText":"'.$witel.'","Field":"ORG","Fieldstatus":null,"Fieldtransaksi":null,"StartDate":"'.$datex.'","EndDate":"'.$datex.'","start":null,"source":"NOSS","typeMenu":"TRACKING"}&&page='.$x.'&start='.$start.'&limit=10';
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);

        if (curl_errno($ch))
        {
            echo 'Error:' . curl_error($ch);
            curl_close($ch);
        }
        else
        {
            curl_close($ch);
            $result = json_decode($result);

            $allRecord = array();

            if ($result)
            {
                $list = $result->data;

                if ($list)
                {
                    $list = $list->LIST;
                    foreach ($list as $data)
                    {
                        $orderDate = $orderDatePs = "";
                        $internet = $noTelp = $myir = null;

                        if (!empty($data->ORDER_DATE_PS)) $orderDatePs = date('Y-m-d H:i:s',strtotime(@$data->ORDER_DATE_PS));
                        if (!empty($data->ORDER_DATE)) $orderDate = date('Y-m-d H:i:s',strtotime(@$data->ORDER_DATE));

                        if ($data->ND_INTERNET <> '')
                        {
                            $int = explode('~', $data->ND_INTERNET);
                            if (count($int) > 1)
                            {
                                $internet = $int[1];
                            } else {
                                $internet = $data->ND_INTERNET;
                            }
                        };
                        if ($data->ND_POTS<>'')
                        {
                            $telp = explode('~', $data->ND_POTS);
                            if (count($telp) > 1)
                            {
                                $noTelp = $telp[1];
                            } else {
                                $noTelp = $data->ND_POTS;
                            }
                        };
                        $get_myir = explode(';',$data->KCONTACT);
                        if (count($get_myir) > 0)
                        {
                        if ($get_myir[0] == "MYIR" || $get_myir[0] == "MI")
                        {
                            $get_myir_2 = explode('-',$get_myir[1]);
                            $myir = $get_myir_2[1];
                        } else {
                            $myir = "\N";
                        }
                        } else {
                            $myir = "\N";
                        }
                        //get STO
                        $get_sto = explode('-',@$data->LOC_ID);
                        if (count($get_sto) > 0)
                        {
                            $STO = @$get_sto[1];
                        } else {
                            $STO = "N";
                        }
                        // echo $data->LOC_ID;
                        $allRecord[] = [
                            "orderId"          => $data->ORDER_ID,
                            "orderIdInteger"   => $data->ORDER_ID,
                            "orderDate"        => $orderDate,
                            "orderStatus"      => $data->ORDER_STATUS,
                            "orderDatePs"      => $orderDatePs,
                            "orderNo"          => $data->EXTERN_ORDER_ID,
                            "orderNcli"        => $data->NCLI,
                            "orderName"        => str_replace(array("'","’"),"",$data->CUSTOMER_NAME),
                            "witel"            => $data->WITEL,
                            "agent_id"         => $data->AGENT_ID,
                            "jenisPsb"         => $data->JENISPSB,
                            // "SOURCE"        => $data->SOURCE,
                            "sto"              => $data->STO,
                            "ndemSpeedy"       => $data->SPEEDY,
                            "ndemPots"         => $data->POTS,
                            "orderPackageName" => $data->PACKAGE_NAME,
                            // "KODEFIKASI_SC" => $data->KODEFIKASI_SC,
                            "orderStatus"      => $data->STATUS_RESUME,
                            "orderStatusId"    => $data->STATUS_CODE_SC,
                            // "USERNAME"      => $data->USERNAME,
                            "orderIdNcx"       => $data->ORDER_ID_NCX,
                            "orderCity"        => str_replace(array("'","’"),"",$data->CUSTOMER_ADDR),
                            "kcontact"         => str_replace(array("'","’"),"",$data->KCONTACT),
                            "orderAddr"        => str_replace(array("'","’"),"",$data->INS_ADDRESS),
                            // "CITY_NAME"     => $data->CITY_NAME,
                            "internet"         => $internet,
                            "noTelp"           => $noTelp,
                            "lat"              => $data->GPS_LATITUDE,
                            "lon"              => $data->GPS_LONGITUDE,
                            "tnNumber"         => $data->TN_NUMBER,
                            "alproname"        => $data->LOC_ID,
                            // "ODP_ID"        => $data->ODP_ID,
                            "reserveTn"        => $data->RESERVE_TN,
                            "reservePort"      => $data->RESERVE_PORT,
                            "myir"             => $myir
                        ];
                        print_r("$data->ORDER_ID\n");
                    }
                }
            }
            print_r("saving\n");

            $srcarr = array_chunk($allRecord,500);
            foreach($srcarr as $item)
            {
                self::insertOrUpdate($item);
            }

        }
    }

    public static function grabstarclick_log_proccess($orderId, $type)
    {
        $starclick = DB::table('cookie_systems')->where('application', 'starclick')->first();

        $ch = curl_init();
        //get session
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIE => $starclick->cookies,
        ));
        curl_exec($ch);

        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/api/load-list-milestone?_dc=1694404831732&guid=0&code=0&data='.urlencode('{"scid":"'.$orderId.'"}').'&page=1&start=0&limit=20',
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
            CURLOPT_COOKIE => $starclick->cookies
        ));
        $log_proccess = curl_exec($ch);
        curl_close($ch);

        $log_proccess_detail = json_decode($log_proccess);

        $tgl_log = null;

        if ($log_proccess_detail != null)
        {
            if ($type == 'PI')
            {
                foreach ($log_proccess_detail->data->LIST as $list)
                {
                    if ($list->XS6 == '7 | OSS - PROVISIONING ISSUED')
                    {
                        $tgl_log = date('Y-m-d H:i:s', strtotime($list->XD1));

                        break;
                    }
                }

                DB::connection('db_t1')->table('Data_Pelanggan_Starclick')
                ->where('orderId', $orderId)
                ->update([
                    'orderDatePI' => $tgl_log
                ]);

                print_r("$orderId orderDatePI is $tgl_log\n");
            }
            else if ($type == 'ACTCOMP')
            {
                foreach ($log_proccess_detail->data->LIST as $list)
                {
                    if ($list->XS6 == '9 | WFM - ACTIVATION COMPLETE')
                    {
                        $tgl_log = date('Y-m-d H:i:s', strtotime($list->XD1));

                        break;
                    }
                }

                DB::connection('db_t1')->table('Data_Pelanggan_Starclick')
                ->where('orderId', $orderId)
                ->update([
                    'orderDateActComp' => $tgl_log
                ]);

                print_r("$orderId orderDateActComp is $tgl_log\n");
            }
            else if ($type == 'REGIST')
            {
                if (end($log_proccess_detail->data->LIST))
                {
                    $tgl_log = date('Y-m-d H:i:s', strtotime(end($log_proccess_detail->data->LIST)->XD1));

                    DB::connection('db_t1')->table('Data_Pelanggan_Starclick')
                    ->where('orderId', $orderId)
                    ->update([
                        'orderDateRegist' => $tgl_log
                    ]);

                    print_r("$orderId orderDateRegist is $tgl_log\n");
                }
            }

            print_r("log proccess $orderId\n");
        }
        else
        {
            print_r("log proccess $orderId is null\n");
        }
    }

    public static function grabstarclick_detail($witel)
    {
        $today     = date('Y-m-d');
        $days3ago  = date('Y-m-d', strtotime('-2 days'));
        $get_sc    = DB::connection('db_t1')->table('Data_Pelanggan_Starclick')->whereBetween('orderDate', [$days3ago, $today])->where('witel', $witel)->orderBy('orderDate', 'desc')->get();
        $starclick = DB::table('cookie_systems')->where('application', 'starclick')->first();

        $total = count($get_sc);

        print_r("start update total $total \n");

        foreach($get_sc as $gsc)
        {
            $orderId = $gsc->orderId;

            $ch = curl_init();

            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_COOKIE => $starclick->cookies,
            ));
            curl_exec($ch);

            curl_setopt($ch, CURLOPT_URL, 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/ordersc/load-order');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_COOKIE, $starclick->cookies);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "code=0&giud=0&data=".urlencode('{"scid":"'.$orderId.'"}'));
            $load_order = curl_exec($ch);
            curl_close($ch);

            if (json_decode($load_order) != null)
            {
                $load_order_detail   = json_decode($load_order);

                if ($load_order_detail->info == 'data found')
                {
                    $id_ncx           = $load_order_detail->data->entity->xs8;
                    $order_code       = $load_order_detail->data->entity->order_code;
                    $order_notel      = $load_order_detail->data->detail->appointment->xs3;
                    $order_kontak     = $load_order_detail->data->detail->appointment->xs4;
                    $package_name     = $load_order_detail->data->detail->paket_preview->xs2;
                    $provider         = $load_order_detail->data->detail->CA->xs3;
                    $wfm_id           = $load_order_detail->data->detail->appointment->xs9;
                    $appointment_date = date('Y-m-d', strtotime($load_order_detail->data->detail->appointment->xd3));
                    $appointment_time = $load_order_detail->data->detail->appointment->xs40;
                    $appointment_desc = $load_order_detail->data->detail->appointment->xs1;

                    DB::connection('db_t1')->table('Data_Pelanggan_Starclick')->where('orderId', $orderId)->update([
                        'orderIdNcx'       => $id_ncx,
                        'orderNo'          => $order_code,
                        'orderNotel'       => $order_notel,
                        'orderKontak'      => $order_kontak,
                        'email'            => $order_kontak,
                        'orderPackageName' => $package_name,
                        'wfm_id'           => $wfm_id,
                        'provider'         => $provider,
                        'appointment_date' => $appointment_date,
                        'appointment_time' => $appointment_time,
                        'appointment_desc' => $appointment_desc
                    ]);

                    // self::grabstarclick_log_proccess($orderId, 'PI');
                    exec('php /srv/htdocs/tomman_api/artisan grabstarclick_log_proccess '.$orderId.' PI > /dev/null &');
                    print_r("php /srv/htdocs/tomman_api/artisan grabstarclick_log_proccess $orderId PI > /dev/null &\n");

                    // self::grabstarclick_log_proccess($orderId, 'ACTCOMP');
                    exec('php /srv/htdocs/tomman_api/artisan grabstarclick_log_proccess '.$orderId.' ACTCOMP > /dev/null &');
                    print_r("php /srv/htdocs/tomman_api/artisan grabstarclick_log_proccess $orderId ACTCOMP > /dev/null &\n");

                    // self::grabstarclick_log_proccess($orderId, 'REGIST');
                    exec('php /srv/htdocs/tomman_api/artisan grabstarclick_log_proccess '.$orderId.' REGIST > /dev/null &');
                    print_r("php /srv/htdocs/tomman_api/artisan grabstarclick_log_proccess $orderId REGIST > /dev/null &\n");

                    print_r("data found $orderId\n");
                }
                else
                {
                    print_r("data not found $orderId\n");
                }
            }
            else
            {
                print_r("failed $orderId\n");
            }
        }

        print_r("finish update \n");
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

    public static function grabstarclick_backend($witel)
    {
        $starclick = DB::table('cookie_systems')->where('application', 'starclick')->first();
        $txtmsg = "";

        for ($i = 0;$i <= 6; $i++) {
            $datex = date('d/m/Y',strtotime("-$i days"));
            $ch = curl_init();

            //get session
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://starclickncx.telkom.co.id/newsc/api/public/starclick/user/get-session',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_COOKIE => $starclick->cookies,
            ));
            curl_exec($ch);

            $link = 'https://starclickncx.telkom.co.id/newsc/api/public/backend/myindihome/tracking-naf-inbox';

            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_COOKIE, $starclick->cookies);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "draw=1&columns%5B0%5D%5Bdata%5D=EXTERN_ORDER_ID&columns%5B0%5D%5Bname%5D=&columns%5B0%5D%5Bsearchable%5D=true&columns%5B0%5D%5Borderable%5D=false&columns%5B0%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B0%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B1%5D%5Bdata%5D=ORDER_DATE&columns%5B1%5D%5Bname%5D=&columns%5B1%5D%5Bsearchable%5D=true&columns%5B1%5D%5Borderable%5D=false&columns%5B1%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B1%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B2%5D%5Bdata%5D=CUSTOMER_NAME&columns%5B2%5D%5Bname%5D=&columns%5B2%5D%5Bsearchable%5D=true&columns%5B2%5D%5Borderable%5D=false&columns%5B2%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B2%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B3%5D%5Bdata%5D=LOC_ID&columns%5B3%5D%5Bname%5D=&columns%5B3%5D%5Bsearchable%5D=true&columns%5B3%5D%5Borderable%5D=false&columns%5B3%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B3%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B4%5D%5Bdata%5D=ND_POTS&columns%5B4%5D%5Bname%5D=&columns%5B4%5D%5Bsearchable%5D=true&columns%5B4%5D%5Borderable%5D=false&columns%5B4%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B4%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B5%5D%5Bdata%5D=STATUS_RESUME&columns%5B5%5D%5Bname%5D=&columns%5B5%5D%5Bsearchable%5D=true&columns%5B5%5D%5Borderable%5D=false&columns%5B5%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B5%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B6%5D%5Bdata%5D=WITEL&columns%5B6%5D%5Bname%5D=&columns%5B6%5D%5Bsearchable%5D=true&columns%5B6%5D%5Borderable%5D=false&columns%5B6%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B6%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B7%5D%5Bdata%5D=APPOINMENT_DATE&columns%5B7%5D%5Bname%5D=&columns%5B7%5D%5Bsearchable%5D=true&columns%5B7%5D%5Borderable%5D=false&columns%5B7%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B7%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B8%5D%5Bdata%5D=KCONTACT&columns%5B8%5D%5Bname%5D=&columns%5B8%5D%5Bsearchable%5D=true&columns%5B8%5D%5Borderable%5D=false&columns%5B8%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B8%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B9%5D%5Bdata%5D=INS_ADDRESS&columns%5B9%5D%5Bname%5D=&columns%5B9%5D%5Bsearchable%5D=true&columns%5B9%5D%5Borderable%5D=false&columns%5B9%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B9%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B10%5D%5Bdata%5D=PACKAGENAME&columns%5B10%5D%5Bname%5D=&columns%5B10%5D%5Bsearchable%5D=true&columns%5B10%5D%5Borderable%5D=false&columns%5B10%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B10%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B11%5D%5Bdata%5D=SCID&columns%5B11%5D%5Bname%5D=&columns%5B11%5D%5Bsearchable%5D=true&columns%5B11%5D%5Borderable%5D=false&columns%5B11%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B11%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B12%5D%5Bdata%5D=USERNAME&columns%5B12%5D%5Bname%5D=&columns%5B12%5D%5Bsearchable%5D=true&columns%5B12%5D%5Borderable%5D=false&columns%5B12%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B12%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B13%5D%5Bdata%5D=ACTION&columns%5B13%5D%5Bname%5D=&columns%5B13%5D%5Bsearchable%5D=true&columns%5B13%5D%5Borderable%5D=false&columns%5B13%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B13%5D%5Bsearch%5D%5Bregex%5D=false&order%5B0%5D%5Bcolumn%5D=0&order%5B0%5D%5Bdir%5D=asc&start=0&length=1000&search%5Bvalue%5D=&search%5Bregex%5D=false&type=AO&output=dataTable&inbox=BE&source=2%2C3%2C4&menu=inquiry&Field=ORG&SearchText=".$witel."&StartDate=".$datex."&EndDate=".$datex."&Fieldstatus=");
            $result = curl_exec($ch);



            if (curl_errno($ch))
            {
                echo 'Error:' . curl_error($ch);
                curl_close($ch);
            }
            else
            {
                curl_close($ch);
                $result = json_decode($result);

                if($result == null)
                {
                    print_r("starclick session expired!");
                }

                if ($result)
                {
                    $list = $result->data;

                    if (is_array($list) || is_object($list))
                    {
                        foreach($list as $data)
                        {
                            if (!empty($data->ORDER_DATE)) $orderDate = date('Y-m-d H:i:s',strtotime(@$data->ORDER_DATE));
                            if (!empty($data->APOINTMENT_DATE)) $apointment_date = date('Y-m-d H:i:s',strtotime(@$data->APOINTMENT_DATE));
                            $orderCodeINT = (substr(@$data->ORDER_CODE,5,15));

                            $allRecord[] = array(
                                "ORDER_ID"        => @$data->ORDER_ID,
                                "ORDER_DATE"      => @$orderDate,
                                "ORDER_TYPE_ID"   => @$data->ORDER_TYPE_ID,
                                "EXTERN_ORDER_ID" => @$data->EXTERN_ORDER_ID,
                                "NCLI"            => @$data->NCLI,
                                "CUSTOMER_NAME"   => @$data->CUSTOMER_NAME,
                                "WITEL"           => @$data->WITEL,
                                "AGENT_ID"        => @$data->AGENT_ID,
                                "JENISPSB"        => @$data->JENISPSB,
                                "SOURCE"          => @$data->SOURCE,
                                "KODEFIKASI_SC"   => @$data->KODEFIKASI_SC,
                                "STATUS_RESUME"   => @$data->STATUS_RESUME,
                                "STATUS_CODE_SC"  => @$data->STATUS_CODE_SC,
                                "USERNAME"        => @$data->USERNAME,
                                "CREATEUSERID"    => @$data->CREATEUSERID,
                                "ORDER_CODE"      => @$data->ORDER_CODE,
                                "ORDER_CODE_INT"  => $orderCodeINT,
                                "PACKAGE_NAME"    => @$data->PACKAGENAME,
                                "STO"             => @$data->STO,
                                "ODP_ID"          => @$data->ODP_ID,
                                "RESERVE_TN"      => @$data->RESERVE_TN,
                                "RESERVE_PORT"    => @$data->RESERVE_PORT,
                                "ND_INTERNET"     => @$data->ND_INTERNET,
                                "ND_POTS"         => @$data->ND_POTS,
                                "GPS_LATITUDE"    => @$data->GPS_LATITUDE,
                                "GPS_LONGITUDE"   => @$data->GPS_LONGITUDE,
                                "TN_NUMBER"       => @$data->TN_NUMBER,
                                "CUSTOMER_NUMBER" => @$data->CUSTOMER_NUMBER,
                                "CUSTOMER_ADDR"   => @$data->CUSTOMER_ADDR,
                                "KCONTACT"        => @$data->KCONTACT,
                                "APOINTMENT_DATE" => @$apointment_date,
                                "INS_ADDRESS"     => @$data->INS_ADDRESS,
                                "SCID"            => @$data->SCID,
                                "CITY_NAME"       => @$data->CITY_NAME,
                                "ACTION"          => @$data->ACTION,
                                "LOC_ID"          => @$data->LOC_ID
                            );
                            print_r("saving $data->ORDER_ID \n");
                        }

                        self::insertOrUpdate_backend($allRecord);
                    }
                }
            }
            sleep(1);
        }

        print_r("$txtmsg\n\n");
    }

    public static function insertOrUpdate_backend(array $rows)
    {
        $table = 'Data_Pelanggan_Starclick_Backend';
        $first = reset($rows);
        $columns = implode( ',',
            array_map( function( $value ) { return "$value"; } , array_keys($first) )
        );
        $values = implode( ',', array_map( function( $row ) {
                return '('.implode( ',',
                    array_map( function( $value ) { return '"'.str_replace('"', '""', $value).'"'; } , $row )
                ).')';
            } , $rows )
        );
        $updates = implode( ',',
            array_map( function( $value ) { return "$value = VALUES($value)"; } , array_keys($first) )
        );
        $sql = "INSERT INTO {$table}({$columns}) VALUES {$values} ON DUPLICATE KEY UPDATE {$updates}";
        return \DB::connection('db_t1')->statement( $sql );
    }
}
