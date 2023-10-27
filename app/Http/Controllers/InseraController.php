<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class InseraController extends Controller
{
    public static function login_insera($is_username, $is_password)
    {
        $username = urlencode($is_username);
        $password = urlencode($is_password);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://insera.telkom.co.id/',
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
        curl_exec($curl);
        $header = curl_getinfo($curl);
        $url_login = $header['url'];

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url_login,
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
        $header = curl_getinfo($curl);
        $header_content = substr($response, 0, $header['header_size']);
        trim(str_replace($header_content, '', $response));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all ($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", $matches['cookie']);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://oss-incident.telkom.co.id/jw/web/json/plugin/org.joget.plugin.marketplace.OpenIDDirectoryManager/service?login=1',
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
        curl_exec($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $url_post_unamepass = $dom->getElementsByTagName('form')->item(0)->getAttribute("action");

        print_r("$url_post_unamepass\n\n");

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url_post_unamepass,
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
            CURLOPT_POSTFIELDS => 'username='.$username.'&password='.$password.'&credentialId=',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              'Cookie: '.$cookiesOut
            ),
        ));
        $response = curl_exec($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $url_post_otp = $dom->getElementsByTagName('form')->item(0)->getAttribute("action");

        print_r("$url_post_otp\n\n");

        $otp = 0;
        print_r("\nMasukan Kode GoogleAuth :\n");
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
            CURLOPT_URL => $url_post_otp,
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
            CURLOPT_POSTFIELDS => 'otp='.$otp.'&login=Sign+In',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              'Cookie: '.$cookiesOut
            ),
        ));
        $response = curl_exec($curl);
        $header = curl_getinfo($curl);
        $header_content = substr($response, 0, $header['header_size']);
        trim(str_replace($header_content, '', $response));
        $pattern = "#set-cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all ($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", array_slice($matches['cookie'], 0, 2));

        if($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'insera')->update([
                'username' => $username,
                'password' => $password,
                'cookies'  => $cookiesOut
            ]);
        }

        curl_close($curl);

        self::refresh_insera();
    }

    public static function refresh_insera()
    {
        $insera = DB::table('cookie_systems')->where('application', 'insera')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://oss-incident.telkom.co.id/jw/web/userview/ticketIncidentService/ticketIncidentService/_/welcome',
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
                'Cookie: '.$insera->cookies
              ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        dd($response);
    }

    public static function ticket_list($witel)
    {
        $start_date     = date('Y-m-d', strtotime('-3 days'));
        $end_date       = date('Y-m-d');

        $start_datetime = date('Y-m-d 00:00:00', strtotime('-3 days'));
        $end_datetime   = date('Y-m-d H :i:s');

        $page           = 1;
        $page_show      = 10000;

        $insera         = DB::table('cookie_systems')->where('application', 'insera')->first();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://oss-incident.telkom.co.id/jw/web/userview/ticketIncidentService/ticketIncidentService/_/welcome',
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
                'Cookie: '.$insera->cookies
              ),
        ));
        $response = curl_exec($curl);

        $pattern = '/JPopup\.tokenValue\s*=\s*["\']([^"\']+)["\']/';
        if (preg_match($pattern, $response, $matches))
        {
            $tokenValue = $matches[1];
        }
        else
        {
            $tokenValue = null;
        }

        print_r("$insera->cookies\n");
        print_r("$tokenValue\n\n");

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://oss-incident.telkom.co.id/jw/web/userview/ticketIncidentService/ticketIncidentService/_/allTicketList?d-5564009-ps='.$page_show.'&d-5564009-p='.$page.'&d-5564009-fn_reported_date_filter='.urlencode($start_datetime).'&d-5564009-fn_reported_date_filter='.urlencode($end_datetime).'&d-5564009-fn_C_CONTACT_NAME=&d-5564009-fn_status_date_filter=&d-5564009-fn_status_date_filter=&d-5564009-fn_C_CONTACT_PHONE=&d-5564009-fn_C_SUMMARY=&d-5564009-fn_C_CONTACT_EMAIL=&d-5564009-fn_C_OWNER_GROUP=&d-5564009-fn_C_OWNER=&d-5564009-fn_C_REPORTED_PRIORITY=&d-5564009-fn_C_SOURCE_TICKET=CUSTOMER,PROACTIVE,GAMAS&d-5564009-fn_C_SUBSIDIARY=&d-5564009-fn_C_EXTERNAL_TICKETID=&d-5564009-fn_C_CHANNEL=&d-5564009-fn_C_CUSTOMER_SEGMENT=DCS,PL-TSEL&d-5564009-fn_C_CUSTOMER_TYPE=&d-5564009-fn_C_CUSTOMER_ID=&d-5564009-fn_C_DESCRIPTION_CUSTOMERID=&d-5564009-fn_C_SERVICE_NO=&d-5564009-fn_C_SERVICE_TYPE=&d-5564009-fn_C_SERVICE_ID=&d-5564009-fn_C_SLG=&d-5564009-fn_C_TECHNOLOGY=&d-5564009-fn_C_LAPUL=&d-5564009-fn_C_GAUL=&d-5564009-fn_C_PENDING_REASON=&d-5564009-fn_C_KODE_PRODUK=&d-5564009-fn_DATEMODIFIED=&d-5564009-fn_C_CLOSED_BY=&d-5564009-fn_C_WORK_ZONE=&d-5564009-fn_C_WITEL='.$witel.'&d-5564009-fn_C_SYMPTOM=&d-5564009-fn_C_REGION=&d-5564009-fn_C_ID_TICKET=&d-5564009-fn_C_SOLUTION_DESCRIPTION=&d-5564009-fn_C_DESCRIPTION_ACTUALSOLUTION=&d-5564009-fn_C_ACTUAL_SOLUTION=&d-5564009-fn_C_CLASSIFICATION_PATH=&d-5564009-fn_C_INCIDENT_DOMAIN=&d-5564009-fn_C_TICKET_STATUS=&d-5564009-fn_C_REPORTED_BY=&d-5564009-fn_C_PERANGKAT=&d-5564009-fn_C_TECHNICIAN=&d-5564009-fn_C_HIERARCHY_PATH=&d-5564009-fn_C_DESCRIPTION_ASSIGMENT=&d-5564009-fn_C_CLASSIFICATION_CATEGORY=&d-5564009-fn_C_REALM=&d-5564009-fn_C_PIPE_NAME=&d-5564009-fn_C_RELATED_TO_GAMAS=&OWASP_CSRFTOKEN='.$tokenValue,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$insera->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = array( 1 =>
            'incident',
            'ttr_customer',
            'summary',
            'reported_date',
            'owner_group',
            'owner',
            'customer_segment',
            'service_type',
            'witel',
            'workzone',
            'status',
            'status_date',
            'induk_gamas',
            'reported_by',
            'contact_phone',
            'contact_name',
            'contact_email',
            'booking_date',
            'assigned_by',
            'reported_priority',
            'source',
            'subsidiary',
            'external_ticket_id',
            'channel',
            'customer_type',
            'closed_by',
            'customer_id',
            'customer_name',
            'service_id',
            'service_no',
            'slg',
            'technology',
            'lapul',
            'gaul',
            'onu_rx_pwr',
            'pending_reason',
            'last_update_ticket',
            'incident_domain',
            'regional',
            'incidents_symptom',
            'hierarchy_path',
            'solutions_segment',
            'actual_solution',
            'kode_produk',
            'perangkat',
            'technician',
            'device_type',
            'device_name',
            'worklog_summary',
            'classfication_flag',
            'realm',
            'related_to_gamas',
            'tsc_result',
            'scc_result',
            'ttr_agent',
            'ttr_mitra',
            'ttr_nasional',
            'ttr_pending',
            'ttr_region',
            'ttr_witel',
            'ttr_end_to_end',
            'notes_eskalasi',
            'guarante_status'
        );
        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++) {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;

                if ($j == 0)
                {
                    $data['id_incident'] = substr($td->nodeValue, 2);
                    $data['incident'] = $td->nodeValue;
                }
            }
            $data['id_incident'] = substr($data['incident'], 3);

            if ($data['status_date'] == '')
            {
                $data['status_date'] = '0000-00-00 00:00:00';
            }
            else
            {
                $data['status_date'] = date('Y-m-d H:i:s', strtotime($data['status_date']));
            }

            if ($data['booking_date'] == '')
            {
                $data['booking_date'] = '0000-00-00 00:00:00';
            }
            else
            {
                $data['booking_date'] = date('Y-m-d H:i:s', strtotime($data['booking_date']));
            }

            if ($data['reported_date'] == '')
            {
                $data['reported_date'] = '0000-00-00 00:00:00';
                $data['date_reported'] = '0000-00-00';
                $data['time_reported'] = '00:00:00';
            }
            else
            {
                $data['reported_date'] = date('Y-m-d H:i:s', strtotime($data['reported_date']));
                $data['date_reported'] = date('Y-m-d', strtotime($data['reported_date']));
                $data['time_reported'] = date('H:i:s', strtotime($data['reported_date']));
            }

            // if ($data['resolved_date'] == '')
            // {
            //     $data['resolved_date'] = '0000-00-00 00:00:00';
            // }
            // else
            // {
            //     $data['resolved_date'] = date('Y-m-d H:i:s', strtotime($data['resolved_date']));
            // }

            $result[] = $data;
        }

        $total = count($result);

        if ($total > 0)
        {
            DB::connection('db_data_center')->statement("DELETE FROM assurance_nossa_order WHERE incident LIKE 'INC%' AND witel = '$witel' AND (DATE(date_reported) BETWEEN '$start_date' AND '$end_date')");

            foreach(array_chunk($result, 500) as $data)
            {
                DB::connection('db_data_center')->table('assurance_nossa_order')->insert($data);
            }

            print_r("Reported Date $start_date s/d $end_date\nAssurance Order Nossa Witel $witel Total $total\n");

            sleep(1);
        }

        exec('php /srv/htdocs/tomman_api/artisan cleansing_trash_order_kawan '.$witel.' > /dev/null &');

        print_r("php /srv/htdocs/tomman_api/artisan cleansing_trash_order_kawan $witel > /dev/null &\n");
    }
}
