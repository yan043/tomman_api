<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class PrabacController extends Controller
{
    public static function login_prabac($uname, $passw, $chatid)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dashboard.telkom.co.id/fulfillment',
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
        preg_match_all ($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", $matches['cookie']);

        if ($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'prabac')->update([
                'username' => $uname,
                'password' => $passw,
                'cookies'  => $cookiesOut
            ]);
        }

        print_r("Cookies Prabac $cookiesOut\n");

        $csrf = $dom->getElementById('csrf')->getAttribute("value");

        print_r("CSRF Token $csrf\n");

        $captcha_img = $dom->getElementsByTagName('img')->item(0)->getAttribute("src");
        $captcha_id = $dom->getElementById('captcha-id')->getAttribute("value");
        $captcha_photo = 'https://dashboard.telkom.co.id'.$captcha_img;

        print_r("Captcha Photo $captcha_photo\n");

        $caption = 'Kode Captcha Prabac '.date('Y-m-d H:i:s');
        Telegram::sendPhoto($chatid, $caption, $captcha_photo);

        print_r("Masukan Captcha :\n");
        $captcha = 0;
        $handle = fopen("php://stdin","w+");
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
            CURLOPT_URL => 'https://dashboard.telkom.co.id/fulfillment',
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
            CURLOPT_POSTFIELDS => 'ID=&uname='.$uname.'&passw='.$passw.'&csrf='.$csrf.'&captcha%5Bid%5D='.$captcha_id.'&captcha%5Binput%5D='.$captcha.'&submit=Login&agree=0&agree=1',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              'Cookie: '.$cookiesOut
            ),
        ));
        curl_exec($curl);

        print_r("Masukan Kode OTP :\n");
        $otp = 0;
        $handle = fopen("php://stdin","w+");
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
            CURLOPT_URL => 'https://dashboard.telkom.co.id/public/otpaction',
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
            CURLOPT_POSTFIELDS => 'uxname='.$uname.'&module=fulfillment&verifikasi='.$otp,
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              'Cookie: '.$cookiesOut
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        dd($response);
    }

    public static function refresh_prabac()
    {
        $prabac = DB::table('cookie_systems')->where('application', 'prabac')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dashboard.telkom.co.id/fulfillment',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$prabac->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        dd($response);
    }

    public static function kpi_prov2023($kode, $header = null, $witel, $periode)
    {
        // TTI -> Jml PS : Kode (TTI) , Header (PS)
        // PS/PI -> Jml PI : Kode (PSPI) , Header (PI)
        // FFG -> Jml GGN : Kode (FFG)
        // TTR FFG -> Not Comply / Comply : Kode (TTR)
        // QC RETURN -> Not Comply : Kode (QCREWORK) , Header (NOT COMPLY)
        // QC RETURN -> Comply : Kode (QCREWORK) , Header (COMPLY)

        if ($kode == 'TTI')
        {
            $kode_data = 'TTI_PS';
        }
        else if ($kode == 'PSPI')
        {
            $kode_data = 'PSPI_PI';
        }
        else if ($kode == 'FFG')
        {
            $kode_data = 'FFG';
        }
        else if ($kode == 'TTR')
        {
            $kode_data = 'TTR';
        }
        else if ($kode == 'QCREWORK' & $header == 'NOT_COMPLY')
        {
            $kode_data = 'QCREWORK_NOTCOMPLY';
        }
        else if ($kode == 'QCREWORK' & $header == 'COMPLY')
        {
            $kode_data = 'QCREWORK_COMPLY';
        }

        $prabac = DB::table('cookie_systems')->where('application', 'prabac')->first();

        if ($header != null)
        {
            $link = 'https://dashboard.telkom.co.id/fulfillment/supportreadiness/detailkpitaprovisioning?kode='.$kode.'&header='.urlencode(str_replace('_', ' ', $header)).'&paramregional=DIVRE%206&paramwitel='.$witel.'&paramdatel=ALL&paramsto=ALL&periode='.$periode.'&CSEG[]=1&dl=true';
        }
        else
        {
            $link = 'https://dashboard.telkom.co.id/fulfillment/supportreadiness/detailkpitaprovisioning?kode='.$kode.'&paramregional=DIVRE%206&paramwitel='.$witel.'&paramdatel=ALL&paramsto=ALL&periode='.$periode.'&CSEG[]=1&dl=true';
        }

        $curl = curl_init();

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
                'Cookie: '.$prabac->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');

        if ($kode == 'TTI')
        {
            $table = 'kpi_prov2023_tti';

            $columns = [1 => 'regional', 'witel', 'kandatel', 'sto', 'ncli', 'ndos', 'nd', 'nd_speedy', 'tgl_ps', 'tgl_start_tti', 'prov_comp_tti', 'durasi_tti', 'kpi_status', 'fo_asap', 'fo_osm', 'fo_uim', 'fo_wfm', 'wo_ta_amcrew', 'tgl_manja', 'tgl_pi_last', 'ndem', 'sc_id', 'cseg'];
        }
        else if ($kode == 'PSPI')
        {
            $table = 'kpi_prov2023_pspi';

            $columns = [1 => 'regional', 'witel', 'kandatel', 'sto', 'nd_speedy', 'tgl_pi', 'tgl_pi_last', 'tgl_ps', 'kpi_status', 'ndem', 'sc_id', 'status_order', 'cseg'];
        }
        else if (in_array($kode, ['FFG', 'TTR']))
        {
            $table = 'kpi_prov2023_ttrffg';

            $columns = [1 => 'regional', 'witel', 'kandatel', 'sto', 'umur_psb_ggn', 'tgl_ps', 'trouble_no', 'trouble_headline', 'trouble_opentime', 'jenis_ggn_baru', 'desc_solution', 'actual_solution_code', 'actual_solution', 'flag_teknis_baru', 'is_gamas', 'flag_exclude', 'ttr_open_tclose', 'ttr_first_assign_freeze', 'f_comply_ttr', 'jenis', 'ndem', 'cseg'];
        }
        else if ($kode == 'QCREWORK')
        {
            $table = 'kpi_prov2023_qcreturn';

            $columns = [1 => 'regional', 'witel', 'kandatel', 'sto', 'nd_speedy', 'nd_telp', 'order_id', 'tgl_create_order', 'last_rework', 'last_rework_user', 'last_status', 'last_updatetime', 'kpi_status', 'wonum', 'external_id', 'sc_id', 'cseg'];
        }

        $result = [];
        for ($i = 1, $count = $rows->length; $i < $count; $i++)
        {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = [];
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++)
            {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }

            if (isset($data['sc_id']))
            {
                $data['sc_id'] = str_replace('SC', '', $data['sc_id']);
            }

            if (isset($data['trouble_no']))
            {
                $data['trouble_no_id'] = str_replace(array('INC', 'IN'), '', $data['trouble_no']);
            }

            if (isset($data['actual_solution']))
            {
                $data['exp1_actual_solution'] = $data['exp2_actual_solution'] = $data['exp3_actual_solution'] = null;

                $explode_actual_solution = explode(' - ', $data['actual_solution']);

                if (isset($explode_actual_solution[1]))
                {
                    $data['exp1_actual_solution'] = @$explode_actual_solution[1];
                }
                if (isset($explode_actual_solution[2]))
                {
                    $data['exp2_actual_solution'] = @$explode_actual_solution[2];
                }
                if (isset($explode_actual_solution[3]))
                {
                    $data['exp3_actual_solution'] = @$explode_actual_solution[3];
                }
            }

            $data['periode'] = $periode;
            $data['kode_data'] = $kode_data;
            $result[] = $data;
        }

        $total = count($result);

        if ($total > 0)
        {
            DB::connection('db_t1')->table($table)
            ->where([
                ['witel', $witel],
                ['periode', $periode],
                ['kode_data', $kode_data]
            ])
            ->delete();

            $srcarr = array_chunk($result, 500);
            foreach ($srcarr as $numb => $item)
            {
                DB::connection('db_t1')->table($table)->insert($item);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            print_r("Success Grab Prabac KPI Provisioning 2023 Kode $kode Header $header Witel $witel Periode $periode Total $total\n");
        }
    }

    public static function all_sync_prabac($witel)
    {
        $periode = date('Ym');

        self::kpi_prov2023('TTI', 'PS', $witel, $periode);
        print_r("\n\n");

        self::kpi_prov2023('PSPI', 'PI', $witel, $periode);
        print_r("\n\n");

        self::kpi_prov2023('FFG', null, $witel, $periode);
        print_r("\n\n");

        self::kpi_prov2023('TTR', null, $witel, $periode);
        print_r("\n\n");

        self::kpi_prov2023('QCREWORK', 'NOT_COMPLY', $witel, $periode);
        print_r("\n\n");

        self::kpi_prov2023('QCREWORK', 'COMPLY', $witel, $periode);
        print_r("\n\n");

        self::cbd_psharian_indihome();
        print_r("\n\n");
    }

    public static function kpi_wsa_tsel($kode, $header, $regional, $witel, $startdate, $enddate)
    {
        // TTI 3x24 Jam : Kode (TTI_72_JAM) Header (PS)
        // FFG : Kode (FFG) Header (NOT COMPLY)
        // TTR FFG : Kode (TTR) Header (ALL)

        $prabac = DB::table('cookie_systems')->where('application', 'prabac')->first();

        if (in_array($kode, ['FFG', 'TTR']))
        {
            $columns = [1 => 'tsel_regional', 'tsel_branch', 'tsel_cluster', 'sto', 'umur_psb_ggn', 'tgl_ps', 'trouble_no', 'trouble_headline', 'trouble_opentime', 'jenis_ggn_baru', 'desc_solution', 'actual_solution_code', 'actual_solution', 'flag_teknis_baru', 'is_gamas', 'ttr_open_tclose', 'ttr_first_assign_freeze', 'f_comply_ttr', 'jenis', 'ndem', 'cseg'];
        }
        else
        {
            $columns = [];
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dashboard.telkom.co.id/fulfillment/wsatsel/detailkpiwsa?level=WITEL&kode='.$kode.'&header='.$header.'&paramregional='.$regional.'&paramwitel='.$witel.'&paramdatel=ALL&paramsto=ALL&startdate='.$startdate.'&enddate='.$enddate.'&UBIS=1&dl=true',
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
                'Cookie: '.$prabac->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');

        $result = [];
        for ($i = 1, $count = $rows->length; $i < $count; $i++)
        {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = [];
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++)
            {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }

            if (isset($data['sc_id']))
            {
                $data['sc_id'] = str_replace('SC', '', $data['sc_id']);
            }

            if (isset($data['trouble_no']))
            {
                $data['trouble_no_id'] = str_replace(array('INC', 'IN'), '', $data['trouble_no']);
            }

            $data['periode'] = $periode;
            $result[] = $data;
        }

        $total = count($result);

        if ($total > 0)
        {

        }
    }

    public static function cbd_psharian_indihome()
    {
        $start    = date('01/m/Y');
        $end      = date('d/m/Y');
        $start_dt = date('Y-m-01');
        $end_dt   = date('Y-m-d');

        $prabac   = DB::table('cookie_systems')->where('application', 'prabac')->first();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dashboard.telkom.co.id/cbd/summaryeksekutif/detilnetaddlokasi?header=PSBLN&level=WITEL&kolom=KALSEL&startdate='.$start.'&enddate='.$end.'&TEMATIK=ALL&JENIS=SALES&ACTIVATION=ALL&DIVRE=DIVRE%206&WITEL=ALL&CHANEL=ALL&jenisalpro=ALL&segment=ALL&CCAT=ALL&netizen=ALL&dl=true',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$prabac->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');

        $columns = [1 => 'witel','datel','sto','ncli','ndos','ndem','nd_internet','nd','chanel','citem_speedy','kecepatan','deskripsi','tgl_reg','tgl_etat','status','nama','kcontact','status_order','alpro','ccat','jalan','nojalan','distrik','kota','cpack','cseg','order_id'];

        $result = [];
        for ($i = 1, $count = $rows->length; $i < $count; $i++)
        {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = [];
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++)
            {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }
            $data['order_id_int'] = str_replace('SC', '', $data['order_id']);
            $data['tgl_etat_dtm'] = date('Y-m-d H:i:s', strtotime($data['tgl_etat']));
            $data['tgl_etat_dt'] = date('Y-m-d', strtotime($data['tgl_etat']));
            $result[] = $data;
        }

        $total = count($result);

        if ($total > 0)
        {
            DB::connection('db_t1')->table('prabac_psharian_indihome')->whereBetween('tgl_etat_dt', [$start_dt, $end_dt])->delete();

            $srcarr = array_chunk($result, 500);
            foreach ($srcarr as $numb => $item)
            {
                DB::connection('db_t1')->table('prabac_psharian_indihome')->insert($item);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }

            print_r("Success Grab Prabac PS Harian IndiHome $start $end Total $total\n");
        }
    }
}
