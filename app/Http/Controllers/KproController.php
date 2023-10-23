<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class KproController extends Controller
{
    public static function login_kpro($uname, $pass, $chatid)
    {
        print_r("$uname $pass $chatid\n\n");

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://kpro.telkom.co.id/kpro/public/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
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
        $cookiesOut = "";
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", $matches['cookie']);
        if($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'kpro')->update([
                'username' => $uname,
                'password' => $pass,
                'cookies'  => $cookiesOut
            ]);
        }
        $kpro_cookies = DB::table('cookie_systems')->where('application', 'kpro')->first();

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $img_captcha = $dom->getElementById('captcha-id')->getAttribute("value");

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://kpro.telkom.co.id/tmp/captcha/'.$img_captcha.'.png',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'Cookie : '.$kpro_cookies->cookies
            ),
        ));
        curl_exec($curl);

        $caption = 'Kode Captcha KPRO '.date('Y-m-d H:i:s');
        $file = 'https://kpro.telkom.co.id/tmp/captcha/'.$img_captcha.'.png';
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

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://kpro.telkom.co.id/kpro/public/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => 'uname='.$uname.'&passw='.$pass.'&captcha%5Bid%5D='.$img_captcha.'&captcha%5Binput%5D='.$captcha.'&agree=0&agree=1',
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$kpro_cookies->cookies
            ),
        ));
        curl_exec($curl);

        $otp = 0;
        print_r("\nMasukan Kode OTP :\n");
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

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://kpro.telkom.co.id/kpro/public/otpaction',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => 'uxname='.$uname.'&verifikasi='.$otp,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$kpro_cookies->cookies
            ),
        ));
        curl_exec($curl);
        curl_close($curl);

        self::refresh_kpro();
    }

    public static function all_sync_kpro()
    {
        $year    = date('Y');
        $month   = date('n');
        $periode = date('Ym');

        self::selfi_kpro(6, 'ALL', $periode);
        print_r("\n\n");

        self::provi_kpro(6, 'BANJARMASIN');
        print_r("\n\n");

        self::total_pi(6, 'BANJARMASIN');
        print_r("\n\n");

        self::pda_kpro(6, 'BANJARMASIN');
        print_r("\n\n");

        self::ps_kpro(6, 'BANJARMASIN', $year, $month);
        print_r("\n\n");

        self::ps_kpro(6, 'BALIKPAPAN', $year, $month);
        print_r("\n\n");

        self::pt2simple_kpro(6, 'BANJARMASIN');
        print_r("\n\n");

        self::byod_survey_kpro_tr6(6, 'KALSEL');
        print_r("\n\n");

        // self::kpro_eval_unsc(6, 'BANJARMASIN');
        // print_r("\n\n");
    }

    public static function refresh_kpro()
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://kpro.telkom.co.id/kpro/provitelkom/dataindex',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => 'ID=&reg=6&witel=ALL&mode=STO&unit=ALL&psb=ALL&play=ALL&digi=ALL&package=ALL&payment=ALL&hari=ALL&channel=ALL&product=ALL&tanggal=ALL&start='.date('Ymd').'&end='.date('Ymd').'&submit=',
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$kpro->cookies.''
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        dd($response);
    }

    public static function selfi_kpro($regional, $witel, $periode)
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://kpro.telkom.co.id/kpro/selfitelkom/exceltotalselfi?status=TTL&reg=$regional&witel=$witel&datel=ALL&sto=ALL&unit=ALL&psb=NEW+SALES&play=ALL&package=ALL&channel=ALL&payment=ALL&periode=$periode&tanggal=ALL",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$kpro->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = array(
        1 =>
        'ORDER_ID', 'REGIONAL', 'WITEL', 'DATEL', 'STO', 'JENISPSB', 'TYPE_TRANSAKSI', 'TYPE_LAYANAN', 'CHANNEL', 'GROUP_CHANNEL', 'STATUS_RESUME', 'STATUS_MESSAGE', 'PROVIDER', 'ORDER_DATE', 'LAST_UPDATED_DATE', 'DEVICE_ID', 'PACKAGE_NAME', 'HIDE', 'LOC_ID', 'NCLI', 'POTS', 'SPEEDY', 'CUSTOMER_NAME', 'CONTACT_HP', 'INS_ADDRESS', 'GPS_LONGITUDE', 'GPS_LATITUDE', 'K_CONTACT', 'CATEGORY', 'UMUR', 'TINDAK_LANJUT', 'ISI_COMMENT', 'TGL_COMMENT', 'USER_ID_TL', 'WONUM', 'DESK_TASK', 'STATUS_TASK', 'SCHEDULE_LABOR', 'AMCREW', 'STATUS_REDAMAN', 'STATUS_VOICE', 'STATUS_INET', 'STATUS_ONU', 'OLT_RX', 'ONU_RX', 'SNR_UP', 'SNR_DOWN', 'UPLOAD', 'DOWNLOAD', 'LAST_PROGRAM', 'CLID', 'LAST_START', 'LAST_VIEW', 'UKUR_TIME', 'TEKNISI'
        );
        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++) {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }
            $data['PERIODE'] = $periode;
            $result[] = $data;
        }

        $total = count($result);

        if ($total > 0)
        {
            switch ($witel) {
                case 'ALL':
                    DB::connection('db_t1')->table('selfie_kpro_tr6_log')->where('PERIODE', $periode)->delete();
                break;

                default:
                    DB::connection('db_t1')->table('selfie_kpro_tr6_log')->where('WITEL', $witel)->where('PERIODE', $periode)->delete();
                break;
            }

            print_r("delete data witel $witel periode $periode\n");

            foreach ($result as $d)
            {
                $insert[] = [
                    'ORDER_ID'          => str_replace(array("'", "\r\n", "\n", " "), "", $d['ORDER_ID']),
                    'REGIONAL'          => $d['REGIONAL'],
                    'WITEL'             => $d['WITEL'],
                    'DATEL'             => $d['DATEL'],
                    'STO'               => $d['STO'],
                    'JENISPSB'          => $d['JENISPSB'],
                    'TYPE_TRANSAKSI'    => $d['TYPE_TRANSAKSI'],
                    'TYPE_LAYANAN'      => $d['TYPE_LAYANAN'],
                    'CHANNEL'           => $d['CHANNEL'],
                    'GROUP_CHANNEL'     => $d['GROUP_CHANNEL'],
                    'STATUS_RESUME'     => $d['STATUS_RESUME'],
                    'STATUS_MESSAGE'    => $d['STATUS_MESSAGE'],
                    'PROVIDER'          => $d['PROVIDER'],
                    'ORDER_DATE'        => $d['ORDER_DATE'],
                    'LAST_UPDATED_DATE' => $d['LAST_UPDATED_DATE'],
                    'DEVICE_ID'         => $d['DEVICE_ID'],
                    'PACKAGE_NAME'      => $d['PACKAGE_NAME'],
                    'HIDE'              => $d['HIDE'],
                    'LOC_ID'            => $d['LOC_ID'],
                    'NCLI'              => str_replace(array("'", "\r\n", "\n", " "), "", $d['NCLI']),
                    'POTS'              => str_replace(array("'", "\r\n", "\n", " "), "", $d['POTS']),
                    'SPEEDY'            => str_replace(array("'", "\r\n", "\n", " "), "", $d['SPEEDY']),
                    'CUSTOMER_NAME'     => $d['CUSTOMER_NAME'],
                    'CONTACT_HP'        => str_replace(array("'", "\r\n", "\n", " "), "", $d['CONTACT_HP']),
                    'INS_ADDRESS'       => $d['INS_ADDRESS'],
                    'GPS_LONGITUDE'     => str_replace(array("'", "\r\n", "\n", " "), "", $d['GPS_LONGITUDE']),
                    'GPS_LATITUDE'      => str_replace(array("'", "\r\n", "\n", " "), "", $d['GPS_LATITUDE']),
                    'K_CONTACT'         => $d['K_CONTACT'],
                    'CATEGORY'          => $d['CATEGORY'],
                    'UMUR'              => $d['UMUR'],
                    'TINDAK_LANJUT'     => $d['TINDAK_LANJUT'],
                    'ISI_COMMENT'       => $d['ISI_COMMENT'],
                    'TGL_COMMENT'       => date('Y-m-d H:i:s', strtotime($d['TGL_COMMENT'])),
                    'USER_ID_TL'        => $d['USER_ID_TL'],
                    'WONUM'             => str_replace(array("'", "\r\n", "\n", " "), "", $d['WONUM']),
                    'DESK_TASK'         => $d['DESK_TASK'],
                    'STATUS_TASK'       => $d['STATUS_TASK'],
                    'SCHEDULE_LABOR'    => date('Y-m-d H:i:s', strtotime($d['SCHEDULE_LABOR'])),
                    'AMCREW'            => $d['AMCREW'],
                    'STATUS_REDAMAN'    => $d['STATUS_REDAMAN'],
                    'STATUS_VOICE'      => $d['STATUS_VOICE'],
                    'STATUS_INET'       => $d['STATUS_INET'],
                    'STATUS_ONU'        => $d['STATUS_ONU'],
                    'OLT_RX'            => $d['OLT_RX'],
                    'ONU_RX'            => $d['ONU_RX'],
                    'SNR_UP'            => $d['SNR_UP'],
                    'SNR_DOWN'          => $d['SNR_DOWN'],
                    'UPLOAD'            => $d['UPLOAD'],
                    'DOWNLOAD'          => $d['DOWNLOAD'],
                    'LAST_PROGRAM'      => $d['LAST_PROGRAM'],
                    'CLID'              => $d['CLID'],
                    'LAST_START'        => $d['LAST_START'],
                    'LAST_VIEW'         => $d['LAST_VIEW'],
                    'UKUR_TIME'         => $d['UKUR_TIME'],
                    'TEKNISI'           => $d['TEKNISI'],
                    'PERIODE'           => $d['PERIODE']
                ];
            }

            $chunk = array_chunk($insert, 500);

            foreach ($chunk as $numb => $value) {
                DB::connection('db_t1')->table('selfie_kpro_tr6_log')->insert($value);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            print_r("Success Grab Selfi KPRO Witel $witel Periode $periode Total $total\n");

            switch ($witel) {
                case 'ALL':
                    DB::connection('db_t1')->table('selfie_kpro_tr6')->where('PERIODE', $periode)->delete();
                    DB::connection('db_t1')->statement('INSERT INTO `selfie_kpro_tr6` SELECT * FROM `selfie_kpro_tr6_log` WHERE `PERIODE` = "' . $periode . '"');
                break;

                default:
                    DB::connection('db_t1')->table('selfie_kpro_tr6')->where('WITEL', $witel)->where('PERIODE', $periode)->delete();
                    DB::connection('db_t1')->statement('INSERT INTO `selfie_kpro_tr6` SELECT * FROM `selfie_kpro_tr6_log` WHERE `WITEL` = "' . $witel . '" AND `PERIODE` = "' . $periode . '"');
                break;
            }
        }
    }

    public static function provi_kpro($regional, $witel)
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://kpro.telkom.co.id/kpro/provitelkom/detailexcel?status=TOT_ORDER&download=total&psb=ALL&play=ALL&digi=ALL&channel=ALL&product=ALL&hari=ALL&unit=ALL&payment=ALL&package=ALL&paramstanggal=ALL&paramsstart=ALL&paramsend=ALL&mode=STO&reg=$regional&witel=$witel",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$kpro->cookies
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = array(
        1 => 'order_id', 'regional', 'witel', 'datel', 'sto', 'unit', 'jenispsb', 'type_trans', 'type_layanan', 'type_channel', 'group_channel', 'flag_deposit', 'status_resume', 'status_message', 'provider', 'order_date', 'last_updated_date', 'device_id', 'hide', 'package_name', 'loc_id', 'ncli', 'pots', 'speedy', 'customer_name', 'contact_hp', 'ins_address', 'gps_longitude', 'gps_latitude', 'kcontact', 'umur', 'wonum', 'desc_task', 'status_task', 'schedule_labor', 'act_start', 'amcrew', 'status_redaman', 'status_voice', 'status_inet', 'status_onu', 'olt_rx', 'onu_rx', 'snr_up', 'snr_down', 'upload', 'download', 'last_program', 'clid', 'last_start', 'last_view', 'ukur_time', 'teknisi', 'product', 'cust_addr_new', 'appointment_desc', 'flag_revoke', 'tgl_revoke', 'order_id_old', 'order_id_new', 'tgl_manja', 'error_code', 'sub_error_code', 'engineer_memo', 'url_evidence'
        );

        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++) {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }
            $result[] = str_replace(array("\r\n", "\t\t\t"), "", $data);
        }

        if (!empty($result))
        {
            switch ($witel) {
                case 'ALL':
                    DB::connection('db_t1')->table('provi_kpro_tr6_log')->truncate();
                    break;

                default:
                    DB::connection('db_t1')->table('provi_kpro_tr6_log')->where('witel', $witel)->delete();
                    break;
            }

            $total = count($result);

            foreach ($result as $d)
            {
                $insert[] = [
                    'order_id'          => $d['order_id'],
                    'regional'          => $d['regional'],
                    'witel'             => $d['witel'],
                    'datel'             => $d['datel'],
                    'sto'               => $d['sto'],
                    'unit'              => $d['unit'],
                    'jenispsb'          => $d['jenispsb'],
                    'type_trans'        => $d['type_trans'],
                    'type_layanan'      => $d['type_layanan'],
                    'type_channel'      => $d['type_channel'],
                    'group_channel'     => $d['group_channel'],
                    'flag_deposit'      => $d['flag_deposit'],
                    'status_resume'     => $d['status_resume'],
                    'status_message'    => $d['status_message'],
                    'provider'          => $d['provider'],
                    'order_date'        => $d['order_date'],
                    'last_updated_date' => $d['last_updated_date'],
                    'device_id'         => $d['device_id'],
                    'hide'              => $d['hide'],
                    'package_name'      => $d['package_name'],
                    'loc_id'            => $d['loc_id'],
                    'ncli'              => $d['ncli'],
                    'pots'              => $d['pots'],
                    'speedy'            => $d['speedy'],
                    'customer_name'     => $d['customer_name'],
                    'contact_hp'        => $d['contact_hp'],
                    'ins_address'       => $d['ins_address'],
                    'gps_longitude'     => $d['gps_longitude'],
                    'gps_latitude'      => $d['gps_latitude'],
                    'kcontact'          => $d['kcontact'],
                    'umur'              => $d['umur'],
                    'wonum'             => $d['wonum'],
                    'desc_task'         => $d['desc_task'],
                    'status_task'       => $d['status_task'],
                    'schedule_labor'    => $d['schedule_labor'],
                    'act_start'         => $d['act_start'],
                    'amcrew'            => $d['amcrew'],
                    'status_redaman'    => $d['status_redaman'],
                    'status_voice'      => $d['status_voice'],
                    'status_inet'       => $d['status_inet'],
                    'status_onu'        => $d['status_onu'],
                    'olt_rx'            => $d['olt_rx'],
                    'onu_rx'            => $d['onu_rx'],
                    'snr_up'            => $d['snr_up'],
                    'snr_down'          => $d['snr_down'],
                    'upload'            => $d['upload'],
                    'download'          => $d['download'],
                    'last_program'      => $d['last_program'],
                    'clid'              => $d['clid'],
                    'last_start'        => $d['last_start'],
                    'last_view'         => $d['last_view'],
                    'ukur_time'         => $d['ukur_time'],
                    'teknisi'           => $d['teknisi'],
                    'product'           => $d['product'],
                    'cust_addr_new'     => $d['cust_addr_new'],
                    'appointment_desc'  => $d['appointment_desc'],
                    'flag_revoke'       => $d['flag_revoke'],
                    'tgl_revoke'        => $d['tgl_revoke'],
                    'order_id_old'      => $d['order_id_old'],
                    'order_id_new'      => $d['order_id_new'],
                    'tgl_manja'         => $d['tgl_manja'],
                    'error_code'        => $d['error_code'],
                    'sub_error_code'    => $d['sub_error_code'],
                    'engineer_memo'     => $d['engineer_memo'],
                    'url_evidence'      => $d['url_evidence'],
                    'last_grab'         => date('Y-m-d H:i:s')
                ];
            }

            $srcarr = array_chunk($insert, 500);
            foreach ($srcarr as $numb => $item)
            {
                DB::connection('db_t1')->table('provi_kpro_tr6_log')->insert($item);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            print_r("Success Grab PROVI K-PRO Witel $witel Total $total\n");

            switch ($witel) {
                case 'ALL':
                        DB::connection('db_t1')->table('provi_kpro_tr6')->truncate();
                        DB::connection('db_t1')->statement('INSERT INTO `provi_kpro_tr6` SELECT * FROM `provi_kpro_tr6_log`');
                    break;

                default:
                        DB::connection('db_t1')->table('provi_kpro_tr6')->where('witel', $witel)->delete();
                        DB::connection('db_t1')->statement('INSERT INTO `provi_kpro_tr6` SELECT * FROM `provi_kpro_tr6_log` WHERE `witel` = "' . $witel . '"');
                    break;
            }
        }
    }

    public static function total_pi($regional, $witel)
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://kpro.telkom.co.id/kpro/provitelkom/detailexcel?status=PI_TOT&statuskolom=FCC&psb=NEW+SALES&play=2P%2B3P&digi=ALL&channel=ALL&product=INDIHOME&hari=ALL&unit=ALL&payment=ALL&package=ALL&mode=STO&reg=$regional&witel=$witel",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Cookie: '.$kpro->cookies
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = array(
        1 =>
        'order_id', 'regional', 'witel', 'datel', 'sto', 'unit', 'jenispsb', 'type_trans', 'type_layanan', 'type_channel', 'group_channel', 'flag_deposit', 'status_resume', 'status_message', 'provider', 'order_date', 'last_updated_date', 'device_id', 'hide', 'package_name', 'loc_id', 'ncli', 'pots', 'speedy', 'customer_name', 'contact_hp', 'ins_address', 'gps_longitude', 'gps_latitude', 'kcontact', 'umur', 'tindak_lanjut', 'isi_comment', 'tgl_comment', 'user_id_tl', 'wonum', 'desc_task', 'status_task', 'schedule_labor', 'act_start', 'amcrew', 'status_redaman', 'status_voice', 'status_inet', 'status_onu', 'olt_rx', 'onu_rx', 'snr_up', 'snr_down', 'upload', 'download', 'last_program', 'clid', 'last_start', 'last_view', 'ukur_time', 'teknisi', 'product'
        );
        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++) {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }
            $result[] = $data;
        }

        $total = count($result);

        if ($total > 0)
        {
            DB::connection('db_t1')->table('pi_kpro_tr6_log')->truncate();

            foreach ($result as $value)
            {
                $insert[] = [
                    'order_id'          => str_replace(array('\r\n', '\t\t\t'), '', $value['order_id']),
                    'regional'          => str_replace(array('\r\n', '\t\t\t'), '', $value['regional']),
                    'witel'             => str_replace(array('\r\n', '\t\t\t'), '', $value['witel']),
                    'datel'             => str_replace(array('\r\n', '\t\t\t'), '', $value['datel']),
                    'sto'               => str_replace(array('\r\n', '\t\t\t'), '', $value['sto']),
                    'unit'              => str_replace(array('\r\n', '\t\t\t'), '', $value['unit']),
                    'jenispsb'          => str_replace(array('\r\n', '\t\t\t'), '', $value['jenispsb']),
                    'type_trans'        => str_replace(array('\r\n', '\t\t\t'), '', $value['type_trans']),
                    'type_layanan'      => str_replace(array('\r\n', '\t\t\t'), '', $value['type_layanan']),
                    'type_channel'      => str_replace(array('\r\n', '\t\t\t'), '', $value['type_channel']),
                    'group_channel'     => str_replace(array('\r\n', '\t\t\t'), '', $value['group_channel']),
                    'flag_deposit'      => str_replace(array('\r\n', '\t\t\t'), '', $value['flag_deposit']),
                    'status_resume'     => str_replace(array('\r\n', '\t\t\t'), '', $value['status_resume']),
                    'status_message'    => str_replace(array('\r\n', '\t\t\t'), '', $value['status_message']),
                    'provider'          => str_replace(array('\r\n', '\t\t\t'), '', $value['provider']),
                    'order_date'        => str_replace(array('\r\n', '\t\t\t'), '', $value['order_date']),
                    'last_updated_date' => str_replace(array('\r\n', '\t\t\t'), '', $value['last_updated_date']),
                    'device_id'         => str_replace(array('\r\n', '\t\t\t'), '', $value['device_id']),
                    'hide'              => str_replace(array('\r\n', '\t\t\t'), '', $value['hide']),
                    'package_name'      => str_replace(array('\r\n', '\t\t\t'), '', $value['package_name']),
                    'loc_id'            => str_replace(array('\r\n', '\t\t\t'), '', $value['loc_id']),
                    'ncli'              => str_replace(array('\r\n', '\t\t\t'), '', $value['ncli']),
                    'pots'              => str_replace(array('\r\n', '\t\t\t'), '', $value['pots']),
                    'speedy'            => str_replace(array('\r\n', '\t\t\t'), '', $value['speedy']),
                    'customer_name'     => str_replace(array('\r\n', '\t\t\t'), '', $value['customer_name']),
                    'contact_hp'        => str_replace(array('\r\n', '\t\t\t'), '', $value['contact_hp']),
                    'ins_address'       => str_replace(array('\r\n', '\t\t\t'), '', $value['ins_address']),
                    'gps_longitude'     => str_replace(array('\r\n', '\t\t\t'), '', $value['gps_longitude']),
                    'gps_latitude'      => str_replace(array('\r\n', '\t\t\t'), '', $value['gps_latitude']),
                    'kcontact'          => str_replace(array('\r\n', '\t\t\t'), '', $value['kcontact']),
                    'umur'              => str_replace(array('\r\n', '\t\t\t'), '', $value['umur']),
                    'tindak_lanjut'     => str_replace(array('\r\n', '\t\t\t'), '', $value['tindak_lanjut']),
                    'isi_comment'       => str_replace(array('\r\n', '\t\t\t'), '', $value['isi_comment']),
                    'tgl_comment'       => str_replace(array('\r\n', '\t\t\t'), '', $value['tgl_comment']),
                    'user_id_tl'        => str_replace(array('\r\n', '\t\t\t'), '', $value['user_id_tl']),
                    'wonum'             => str_replace(array('\r\n', '\t\t\t'), '', $value['wonum']),
                    'desc_task'         => str_replace(array('\r\n', '\t\t\t'), '', $value['desc_task']),
                    'status_task'       => str_replace(array('\r\n', '\t\t\t'), '', $value['status_task']),
                    'schedule_labor'    => str_replace(array('\r\n', '\t\t\t'), '', $value['schedule_labor']),
                    'act_start'         => str_replace(array('\r\n', '\t\t\t'), '', $value['act_start']),
                    'amcrew'            => str_replace(array('\r\n', '\t\t\t'), '', $value['amcrew']),
                    'status_redaman'    => str_replace(array('\r\n', '\t\t\t'), '', $value['status_redaman']),
                    'status_voice'      => str_replace(array('\r\n', '\t\t\t'), '', $value['status_voice']),
                    'status_inet'       => str_replace(array('\r\n', '\t\t\t'), '', $value['status_inet']),
                    'status_onu'        => str_replace(array('\r\n', '\t\t\t'), '', $value['status_onu']),
                    'olt_rx'            => str_replace(array('\r\n', '\t\t\t'), '', $value['olt_rx']),
                    'onu_rx'            => str_replace(array('\r\n', '\t\t\t'), '', $value['onu_rx']),
                    'snr_up'            => str_replace(array('\r\n', '\t\t\t'), '', $value['snr_up']),
                    'snr_down'          => str_replace(array('\r\n', '\t\t\t'), '', $value['snr_down']),
                    'upload'            => str_replace(array('\r\n', '\t\t\t'), '', $value['upload']),
                    'download'          => str_replace(array('\r\n', '\t\t\t'), '', $value['download']),
                    'last_program'      => str_replace(array('\r\n', '\t\t\t'), '', $value['last_program']),
                    'clid'              => str_replace(array('\r\n', '\t\t\t'), '', $value['clid']),
                    'last_start'        => str_replace(array('\r\n', '\t\t\t'), '', $value['last_start']),
                    'last_view'         => str_replace(array('\r\n', '\t\t\t'), '', $value['last_view']),
                    'ukur_time'         => str_replace(array('\r\n', '\t\t\t'), '', $value['ukur_time']),
                    'teknisi'           => str_replace(array('\r\n', '\t\t\t'), '', $value['teknisi']),
                    'product'           => str_replace(array('\r\n', '\t\t\t'), '', $value['product'])
                ];
            }

            $chunk = array_chunk($insert, 500);
            foreach ($chunk as $numb => $data)
            {
                DB::connection('db_t1')->table('pi_kpro_tr6_log')->insert($data);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            print_r("Finish Grab Total PI K-Pro Regional $regional Witel $witel Total $total\n");

            DB::connection('db_t1')->table('pi_kpro_tr6')->truncate();
            DB::connection('db_t1')->statement('INSERT INTO `pi_kpro_tr6` SELECT * FROM `pi_kpro_tr6_log`');
        }
    }

    public static function pda_kpro($regional, $witel)
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://kpro.telkom.co.id/kpro/pda/detailpda?kolom=FO_TOTAL&periode=ALL&channel=ALL&psb=ALL&hari=ALL&mode=STO&reg=$regional&witel=$witel&dl=true",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$kpro->cookies
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = array(1 => 'order_id','regional','witel','datel','sto','unit','jenis_psb','type_trans','type_layanan','status_resume','status_message','provider','order_date','last_update_date','device_id','hide','package_name','loc_id','ncli','speedy','customer_name','contact_hp','ins_address','gps_longitude','gps_latitude','kcontact','umur','tindak_lanjut','isi_comment','tgl_comment','user_id_tl','wonum','desc_task','status_task','schedule_labor','act_start','amcrew','status_redaman','status_voice','status_inet','status_onu','olt_rx','onu_rx','snr_up','snr_down','upload','download','last_program','clid','last_start','last_view','ukur_time','teknisi','channel','group_channel','customer_addr_new');
        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++) {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }
            $result[] = $data;
        }

        if (!empty($result))
        {
            $total = count($result);

            foreach ($result as $v)
            {
                $insert[] = [
                    'order_id'          => $v['order_id'],
                    'regional'          => $v['regional'],
                    'witel'             => $v['witel'],
                    'datel'             => $v['datel'],
                    'sto'               => $v['sto'],
                    'unit'              => $v['unit'],
                    'jenis_psb'         => $v['jenis_psb'],
                    'type_trans'        => $v['type_trans'],
                    'type_layanan'      => $v['type_layanan'],
                    'status_resume'     => $v['status_resume'],
                    'status_message'    => $v['status_message'],
                    'provider'          => $v['provider'],
                    'order_date'        => $v['order_date'],
                    'last_update_date'  => $v['last_update_date'],
                    'device_id'         => $v['device_id'],
                    'hide'              => $v['hide'],
                    'package_name'      => $v['package_name'],
                    'loc_id'            => $v['loc_id'],
                    'ncli'              => $v['ncli'],
                    'speedy'            => $v['speedy'],
                    'customer_name'     => $v['customer_name'],
                    'contact_hp'        => $v['contact_hp'],
                    'ins_address'       => $v['ins_address'],
                    'gps_longitude'     => $v['gps_longitude'],
                    'gps_latitude'      => $v['gps_latitude'],
                    'kcontact'          => $v['kcontact'],
                    'umur'              => $v['umur'],
                    'tindak_lanjut'     => $v['tindak_lanjut'],
                    'isi_comment'       => $v['isi_comment'],
                    'tgl_comment'       => $v['tgl_comment'],
                    'user_id_tl'        => $v['user_id_tl'],
                    'wonum'             => $v['wonum'],
                    'desc_task'         => $v['desc_task'],
                    'status_task'       => $v['status_task'],
                    'schedule_labor'    => $v['schedule_labor'],
                    'act_start'         => $v['act_start'],
                    'amcrew'            => $v['amcrew'],
                    'status_redaman'    => $v['status_redaman'],
                    'status_voice'      => $v['status_voice'],
                    'status_inet'       => $v['status_inet'],
                    'status_onu'        => $v['status_onu'],
                    'olt_rx'            => $v['olt_rx'],
                    'onu_rx'            => $v['onu_rx'],
                    'snr_up'            => $v['snr_up'],
                    'snr_down'          => $v['snr_down'],
                    'upload'            => $v['upload'],
                    'download'          => $v['download'],
                    'last_program'      => $v['last_program'],
                    'clid'              => $v['clid'],
                    'last_start'        => $v['last_start'],
                    'last_view'         => $v['last_view'],
                    'ukur_time'         => $v['ukur_time'],
                    'teknisi'           => $v['teknisi'],
                    'channel'           => $v['channel'],
                    'group_channel'     => $v['group_channel'],
                    'customer_addr_new' => $v['customer_addr_new']
                ];
            }

            DB::connection('db_t1')->table('pda_kpro_tr6')->truncate();

            $srcarr = array_chunk($insert, 500);
            foreach ($srcarr as $numb => $item)
            {
                DB::connection('db_t1')->table('pda_kpro_tr6')->insert($item);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            print_r("Success Grab PDA K-Pro Witel $witel Total $total\n");
        }
    }

    public static function ps_kpro($regional, $witel, $tahun, $bulan)
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://kpro.telkom.co.id/kpro/pstelkom/dlrekapbulan?dl=true&mode=WITEL&kolom=$witel&reg=$regional&package=ALL&payment=ALL&witel=ALL&unit=ALL&psb=ALL&play=ALL&channel=ALL&product=ALL&thn=$tahun&bln=$bulan",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$kpro->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = array(
        1 => 'ORDER_ID', 'REGIONAL', 'WITEL', 'DATEL', 'STO', 'EXTERN_ORDER_ID', 'JENIS_PSB', 'TYPE_TRANSAKSI', 'FLAG_DEPOSIT', 'STATUS_RESUME', 'STATUS_MESSAGE', 'KCONTACT', 'ORDER_DATE', 'NCLI', 'NDEM', 'SPEEDY', 'POTS', 'CUSTOMER_NAME', 'NO_HP', 'EMAIL', 'INSTALL_ADDRESS', 'CUSTOMER_ADDRESS', 'CITY_NAME', 'GPS_LATITUDE', 'GPS_LONGITUDE', 'PACKAGE_NAME', 'LOC_ID', 'DEVICE_ID', 'AGENT_ID', 'WFM_ID', 'SCHEDSTART', 'SCHEDFINISH', 'ACTSTART', 'ACTFINISH', 'SCHEDULE_LABOR', 'FINISH_LABOR', 'LAST_UPDATED_DATE', 'TYPE_LAYANAN', 'ISI_COMMENT', 'TINDAK_LANJUT', 'USER_ID_TL', 'TL_DATE', 'TANGGAL_PROSES', 'TANGGAL_MANJA', 'HIDE', 'CATEGORY', 'PROVIDER', 'NPER', 'AMCREW', 'STATUS_WO', 'STATUS_TASK', 'CHANNEL', 'GROUP_CHANNEL', 'PRODUCT'
        );
        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++) {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }

            if (strpos($data['DEVICE_ID'], 'MYIR') !== false)
            {
                $jenis_order = 'MYIR';
            } elseif (strpos($data['DEVICE_ID'], 'MYID') !== false) {
                $jenis_order = 'MYID';
            } elseif (strpos($data['DEVICE_ID'], 'MYDB') !== false) {
                $jenis_order = 'MYDB';
            } elseif (strpos($data['DEVICE_ID'], 'MYIRX') !== false) {
                $jenis_order = 'MYIRX';
            } elseif (strpos($data['DEVICE_ID'], 'WMSL') !== false) {
                $jenis_order = 'WMSL';
            } elseif (strpos($data['DEVICE_ID'], 'WMS') !== false) {
                $jenis_order = 'WMS';
            } else {
                $jenis_order = null;
            }

            $data['JENIS_ORDER'] = $jenis_order;

            if ($jenis_order <> null)
            {
                $data['DEVICE_ID_INT'] = str_replace(array('MYIR-', 'MYID-', 'MYDB-', 'MYIRX-', 'WMSL', 'WMS'), '', $data['DEVICE_ID']);
            } else {
                $data['DEVICE_ID_INT'] = 0;
            }

            $result[] = $data;
        }

        if (!empty($result))
        {
            if ($bulan < 10)
            {
                $nper = $tahun . '0' . $bulan;
            } else {
                $nper = $tahun . '' . $bulan;
            }

            DB::connection('db_t1')->table('kpro_tr6')
            ->where([
                ['REGIONAL', $regional],
                ['WITEL', $witel],
                ['NPER', $nper],
            ])->delete();

            $total = count($result);

            foreach ($result as $d)
            {
                if (count($d) == 56)
                {
                    $insert[] = [
                        'ORDER_ID'          => $d['ORDER_ID'],
                        'REGIONAL'          => $d['REGIONAL'],
                        'WITEL'             => $d['WITEL'],
                        'DATEL'             => $d['DATEL'],
                        'STO'               => $d['STO'],
                        'EXTERN_ORDER_ID'   => $d['EXTERN_ORDER_ID'],
                        'JENIS_PSB'         => $d['JENIS_PSB'],
                        'TYPE_TRANSAKSI'    => $d['TYPE_TRANSAKSI'],
                        'FLAG_DEPOSIT'      => $d['FLAG_DEPOSIT'],
                        'STATUS_RESUME'     => $d['STATUS_RESUME'],
                        'STATUS_MESSAGE'    => $d['STATUS_MESSAGE'],
                        'KCONTACT'          => $d['KCONTACT'],
                        'ORDER_DATE'        => $d['ORDER_DATE'],
                        'NCLI'              => $d['NCLI'],
                        'NDEM'              => $d['NDEM'],
                        'SPEEDY'            => $d['SPEEDY'],
                        'POTS'              => $d['POTS'],
                        'CUSTOMER_NAME'     => $d['CUSTOMER_NAME'],
                        'NO_HP'             => $d['NO_HP'],
                        'EMAIL'             => $d['EMAIL'],
                        'INSTALL_ADDRESS'   => $d['INSTALL_ADDRESS'],
                        'CUSTOMER_ADDRESS'  => $d['CUSTOMER_ADDRESS'],
                        'CITY_NAME'         => $d['CITY_NAME'],
                        'GPS_LATITUDE'      => $d['GPS_LATITUDE'],
                        'GPS_LONGITUDE'     => $d['GPS_LONGITUDE'],
                        'PACKAGE_NAME'      => $d['PACKAGE_NAME'],
                        'LOC_ID'            => $d['LOC_ID'],
                        'DEVICE_ID'         => $d['DEVICE_ID'],
                        'AGENT_ID'          => $d['AGENT_ID'],
                        'WFM_ID'            => $d['WFM_ID'],
                        'SCHEDSTART'        => $d['SCHEDSTART'],
                        'SCHEDFINISH'       => $d['SCHEDFINISH'],
                        'ACTSTART'          => $d['ACTSTART'],
                        'ACTFINISH'         => $d['ACTFINISH'],
                        'SCHEDULE_LABOR'    => $d['SCHEDULE_LABOR'],
                        'FINISH_LABOR'      => $d['FINISH_LABOR'],
                        'LAST_UPDATED_DATE' => $d['LAST_UPDATED_DATE'],
                        'TYPE_LAYANAN'      => $d['TYPE_LAYANAN'],
                        'ISI_COMMENT'       => $d['ISI_COMMENT'],
                        'TINDAK_LANJUT'     => $d['TINDAK_LANJUT'],
                        'USER_ID_TL'        => $d['USER_ID_TL'],
                        'TL_DATE'           => $d['TL_DATE'],
                        'TANGGAL_PROSES'    => $d['TANGGAL_PROSES'],
                        'TANGGAL_MANJA'     => $d['TANGGAL_MANJA'],
                        'HIDE'              => $d['HIDE'],
                        'CATEGORY'          => $d['CATEGORY'],
                        'PROVIDER'          => $d['PROVIDER'],
                        'NPER'              => $d['NPER'],
                        'AMCREW'            => $d['AMCREW'],
                        'STATUS_WO'         => $d['STATUS_WO'],
                        'STATUS_TASK'       => $d['STATUS_TASK'],
                        'CHANNEL'           => $d['CHANNEL'],
                        'GROUP_CHANNEL'     => $d['GROUP_CHANNEL'],
                        'PRODUCT'           => $d['PRODUCT'],
                        'JENIS_ORDER'       => $d['JENIS_ORDER'],
                        'DEVICE_ID_INT'     => $d['DEVICE_ID_INT']
                    ];
                }
            }

            $srcarr = array_chunk($insert, 500);
            foreach ($srcarr as $numb => $item)
            {
                DB::connection('db_t1')->table('kpro_tr6')->insert($item);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            DB::connection('db_t1')->statement('DELETE a1 FROM kpro_tr6 a1, kpro_tr6 a2 WHERE a1.ID > a2.ID AND a1.ORDER_ID = a2.ORDER_ID');

            print_r("Success Grab KPRO PS Bulan $nper Witel $witel Total $total\n");

            DB::connection('db_t1')->table('cookie_systems')
            ->where([
                ['application', 'kpro'],
                ['witel', 'KALSEL']
            ])
            ->update([
                'last_sync' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public static function pt2simple_kpro($regional, $witel)
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();
        $year = date('Y');

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://kpro.telkom.co.id/kpro/pt2/detaillmesurvey?status=TOTAL&periode=$year&mode=STO&reg=$regional&witel=$witel&dl=true",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$kpro->cookies
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = array(
        1 =>
        'odp', 'order_id', 'regional', 'witel', 'datel', 'sto', 'jenispb', 'status_resume', 'status_message', 'provider', 'order_date', 'last_updated_date', 'device_id', 'hide', 'pots', 'loc_id', 'ncli', 'package_name', 'speedy', 'customer_name', 'contact_hp', 'isi_comment', 'gps_longitude', 'gps_latitude', 'ins_address', 'category', 'tindak_lanjut', 'kcontact', 'tgl_comment', 'user_id_tl', 'wonum', 'desc_task', 'status_task', 'schedule_labor', 'amcrew', 'status_redaman', 'status_voice', 'status_inet', 'status_onu', 'olt_rx', 'onu_rx', 'snr_up', 'snr_down', 'upload', 'download', 'last_program', 'clid', 'last_start', 'last_view', 'ukur_time', 'teknisi', 'acstart', 'status_prj'
        );
        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++) {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }
            $result[] = $data;
        }

        $total = count($result);

        if ($total > 0)
        {
            DB::connection('db_t1')->table('pt2simple_kpro_tr6')->truncate();

            $srcarr = array_chunk($result, 500);
            foreach ($srcarr as $numb => $item)
            {
                DB::connection('db_t1')->table('pt2simple_kpro_tr6')->insert($item);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            print_r("Success Grab PT2-Simple K-PRO Regional $regional Witel $witel Total $total\n");
        }
    }

    public static function byod_survey_kpro_tr6($regional, $witel)
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $start = '01-01-2022';
        $end = date('d-m-Y');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://kpro.telkom.co.id/kpro/report/detailbyodsurveynew?kolom=BYODJMLORDER&start=$start&end=$end&reg=$regional&witel=$witel&psb=ALL&play=ALL&dl=true",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$kpro->cookies
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if (isset($response))
        {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML(trim($response));
            $table = $dom->getElementsByTagName('table')->item(0);
            $rows = $table->getElementsByTagName('tr');
            $columns = array(
                1 => 'order_id', 'track_id', 'created_date', 'regional', 'witel', 'sto', 'sto_name', 'customer_id', 'name', 'email', 'hp', 'order_status_id', 'order_status', 'labor_id', 'assign_date_teknisi', 'hasil_ukur', 'odp', 'val_desc'
            );

            $result = array();
            for ($i = 1, $count = $rows->length; $i < $count; $i++) {
                $cells = $rows->item($i)->getElementsByTagName('td');
                $data = array();
                for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                    $td = $cells->item($j);
                    $data[$columns[$j]] =  $td->nodeValue;
                }
                $data['track_id_int'] = str_replace('SC', '', $data['track_id']);
                $result[] = $data;
            }

            $total = count($result);

            if ($total > 0)
            {
                DB::connection('db_t1')->table('byod_survey_kpro_tr6')->truncate();

                foreach ($result as $value)
                {
                    $insert[] = [
                        'order_id'             => $value['order_id'],
                        'track_id'             => $value['track_id'],
                        'track_id_int'         => $value['track_id_int'],
                        'created_date'         => $value['created_date'],
                        'created_datex'        => substr(date('Y'), 0, 2).''.substr($value['created_date'], 7, 2).'-'.date('m', strtotime(substr($value['created_date'], 3, 3))).'-'.substr($value['created_date'], 0, 2),
                        'regional'             => $value['regional'],
                        'witel'                => $value['witel'],
                        'sto'                  => $value['sto'],
                        'sto_name'             => $value['sto_name'],
                        'customer_id'          => $value['customer_id'],
                        'name'                 => $value['name'],
                        'email'                => $value['email'],
                        'hp'                   => $value['hp'],
                        'order_status_id'      => $value['order_status_id'],
                        'order_status'         => $value['order_status'],
                        'labor_id'             => $value['labor_id'],
                        'assign_date_teknisi'  => $value['assign_date_teknisi'],
                        'assign_date_teknisix' => substr(date('Y'), 0, 2).''.substr($value['assign_date_teknisi'], 7, 2).'-'.date('m', strtotime(substr($value['assign_date_teknisi'], 3, 3))).'-'.substr($value['assign_date_teknisi'], 0, 2),
                        'hasil_ukur'           => $value['hasil_ukur'],
                        'odp'                  => $value['odp'],
                        'val_desc'             => $value['val_desc']
                    ];
                }

                $chunk = array_chunk($insert, 500);
                foreach ($chunk as $key => $value) {
                    DB::connection('db_t1')->table('byod_survey_kpro_tr6')->insert($value);

                    print_r("saved page $key and sleep (1)\n");

                    sleep(1);
                }

                print_r("Finish Grab BYOD Survey KPRO Total $total Tanggal $start s/d $end\n");
            }
        }
    }

    public static function kpro_eval_unsc($regional, $witel)
    {
        $kpro = DB::table('cookie_systems')->where('application', 'kpro')->first();

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kpro.telkom.co.id/kpro/unsc/detailexcel?status=unsc&by=umr&w=3&psb=NEW+SALES&play=all&tl=&mode=sto&reg='.$regional.'&witel='.$witel,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$kpro->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = array(
        1 =>
        'regional', 'witel', 'datel', 'sto', 'order_id', 'type_transaksi', 'jenis_layanan', 'alpro', 'ncli', 'pots', 'speedy', 'status_resume', 'status_message', 'order_date', 'last_update_status', 'nama_cust', 'no_hp', 'alamat', 'kcontact', 'long', 'lat', 'wfm_id', 'status_wfm', 'desk_task', 'status_task', 'tgl_install', 'amcrew', 'teknisi', 'hp_teknisi', 'tindak_lanjut', 'keterangan', 'user', 'tgl_tindak_lanjut'
        );

        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++) {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }
            $result[] = str_replace(array("\r\n", "\t\t\t"), "", $data);
        }

        $total = count($result);

        if ($total > 0)
        {
            DB::connection('db_t1')->table('kpro_evaluasi_unsc')
            ->where([
                ['regional', $regional],
                ['witel', $witel]
            ])
            ->delete();

            $srcarr = array_chunk($result, 500);
            foreach ($srcarr as $numb => $item)
            {
                DB::connection('db_t1')->table('kpro_evaluasi_unsc')->insert($item);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            print_r("Success Grab KPRO Evaluasi UNSC Witel $witel Total $total\n");
        }
    }
}
