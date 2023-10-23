<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class NossaController extends Controller
{
    public static function update_mxIdNossa($witel)
    {
        $datetime   = date('Y-m-d H:i:s');
        $user_nossa = DB::table('assurance_nossa_account')->where([ ['witel', $witel], ['active', 1] ])->first();

        if ($user_nossa == null)
        {
            dd("Maaf, Tidak Ada User yang Active untuk Digunakan");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://nossa.telkom.co.id/maximo/webclient/login/login.jsp');
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.190 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.jar');  //could be empty, but cause problems on some hosts
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $login = curl_exec($ch);

        $dom = @\DOMDocument::loadHTML(trim($login));
        $input = $dom->getElementsByTagName('input')->item(3)->getAttribute("value");
        curl_setopt($ch, CURLOPT_URL, 'https://nossa.telkom.co.id/maximo/ui/login');
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.190 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "allowinsubframe=null&mobile=false&login=jsp&loginstamp=".$input."&username=".$user_nossa->username."&password=".$user_nossa->password);
        $result = curl_exec($ch);

        curl_setopt($ch, CURLOPT_URL, 'https://nossa.telkom.co.id/maximo/ui/?event=loadapp&value=incident');
        curl_setopt($ch, CURLOPT_POST, false);
        $result = curl_exec($ch);
        curl_close ($ch);
        $dom = @\DOMDocument::loadHTML(trim($result));
        $get_input = $dom->getElementsByTagName('input');
        $get_filterrows = substr($result,strpos($result,'filterrows'),50);
        $get_params = substr($get_filterrows,0,strpos($get_filterrows,')'));

        $title[0] = ['filterrows' => str_replace(array("'","mx"),"",explode(',',$get_params)[1])];
        foreach($get_input as $input)
        {
            if ($input->getAttribute("title") == "Owner Group filter")
            {
                $title[0]['mx_owner_group'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Source filter")
            {
                $title[0]['mx_source'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Customer Segment filter")
            {
                $title[0]['mx_customer_segment'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Status filter")
            {
                $title[0]['mx_status'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Reported Date filter")
            {
                $title[0]['mx_reported_date'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Workzone filter")
            {
                $title[0]['mx_workzone'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Incident's Symptom filter")
            {
                $title[0]['mx_incidents_symptom'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Witel filter")
            {
                $title[0]['mx_witel'] = str_replace("mx","",$input->getAttribute("id"));
                $title[0]['currentfocus'] = str_replace("mx","",$input->getAttribute("id"));
            }

            $title[0]['last_updated'] = date('Y-m-d H:i:s');
        }

        DB::table('assurance_nossa_account')->where('witel', $witel)->update($title[0]);

        print_r("Finish Update mx ID Nossa Witel $witel\n\n$datetime\n");
    }

    public static function nossa_order_today($witel)
    {
        for ($i = 0; $i <= 2; $i++)
        {
            $date = date('d-m-Y', strtotime("-$i days"));

            exec('php /srv/htdocs/tomman_api/artisan nossa_order '.$witel.' '.$date.' > /dev/null &');

            print_r("php /srv/htdocs/tomman_api/artisan nossa_order $witel $date > /dev/null &\n");

            sleep(55);
        }

        exec('php /srv/htdocs/tomman_api/artisan cleansing_trash_order_kawan '.$witel.' > /dev/null &');

        print_r("php /srv/htdocs/tomman_api/artisan cleansing_trash_order_kawan $witel > /dev/null &\n");
    }

    public static function nossa_order($witel, $date)
    {
        $datetime   = date('Y-m-d H:i:s');
        $time       = date('H:i:s');
        $user_nossa = DB::table('assurance_nossa_account')->where([ ['witel', $witel], ['active', 1] ])->first();

        if($user_nossa == null)
        {
            print_r("Maaf, Tidak Ada User yang Active untuk Digunakan");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://nossa.telkom.co.id/maximo/webclient/login/login.jsp');
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.190 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.jar');  //could be empty, but cause problems on some hosts
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $login = curl_exec($ch);

        $dom = @\DOMDocument::loadHTML(trim($login));
        $input = $dom->getElementsByTagName('input')->item(3)->getAttribute("value");
        // print_r($input);
        curl_setopt($ch, CURLOPT_URL, 'https://nossa.telkom.co.id/maximo/ui/login');
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.190 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "allowinsubframe=null&mobile=false&login=jsp&loginstamp=".$input."&username=".$user_nossa->username."&password=".$user_nossa->password);
        $result = curl_exec($ch);

        curl_setopt($ch, CURLOPT_URL, 'https://nossa.telkom.co.id/maximo/ui/?event=loadapp&value=incident');
        curl_setopt($ch, CURLOPT_POST, false);
        $result = curl_exec($ch);
        $dom = @\DOMDocument::loadHTML(trim($result));

        $get_input = $dom->getElementsByTagName('input');
        $get_filterrows = substr($result,strpos($result,'filterrows'),50);
        $get_params = substr($get_filterrows,0,strpos($get_filterrows,')'));

        $title[0] = ['filterrows' => str_replace(array("'","mx"),"",explode(',',$get_params)[1])];
        foreach($get_input as $input)
        {
            if ($input->getAttribute("title") == "Owner Group filter")
            {
                $title[0]['mx_owner_group'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Source filter")
            {
                $title[0]['mx_source'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Customer Segment filter")
            {
                $title[0]['mx_customer_segment'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Status filter")
            {
                $title[0]['mx_status'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Reported Date filter")
            {
                $title[0]['mx_reported_date'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Workzone filter")
            {
                $title[0]['mx_workzone'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Incident's Symptom filter")
            {
                $title[0]['mx_incidents_symptom'] = str_replace("mx","",$input->getAttribute("id"));
            }

            if ($input->getAttribute("title") == "Witel filter")
            {
                $title[0]['mx_witel'] = str_replace("mx","",$input->getAttribute("id"));
                $title[0]['currentfocus'] = str_replace("mx","",$input->getAttribute("id"));
            }

            $title[0]['last_updated'] = date('Y-m-d H:i:s');
        }

        DB::table('assurance_nossa_account')->where('witel', $witel)->update($title[0]);

        print_r("Finish Update mx ID Nossa Witel $witel\n$datetime\n\n");

        $uisesid = $dom->getElementById('uisessionid')->getAttribute("value");
        $csrftokenholder = $dom->getElementById('csrftokenholder')->getAttribute("value");

        $event = json_encode([
            (object)[
                "type"            => "setvalue",
                "targetId"        => "mx".$user_nossa->mx_customer_segment,
                "value"           => "=DCS,=PL-TSEL",
                "requestType"     => "ASYNC",
                "csrftokenholder" => $csrftokenholder
            ], (object)[
                "type"            => "setvalue",
                "targetId"        => "mx".$user_nossa->mx_reported_date,
                "value"           => "".$date."",
                "requestType"     => "ASYNC",
                "csrftokenholder" => $csrftokenholder
            ], (object)[
                "type"            => "setvalue",
                "targetId"        => "mx".$user_nossa->mx_witel,
                "value"           => $witel,
                "requestType"     => "ASYNC",
                "csrftokenholder" => $csrftokenholder
            ], (object)[
                "type"            => "filterrows",
                "targetId"        => "mx".$user_nossa->filterrows,
                "value"           => "",
                "requestType"     => "SYNC",
                "csrftokenholder" => $csrftokenholder
            ], (object)[
                "type"            => "longopquerycheck",
                "targetId"        => "query_longopwait",
                "value"           => "",
                "requestType"     => "SYNC",
                "csrftokenholder" => $csrftokenholder
            ]
        ]);

        $postdata = http_build_query(
            [
                "uisessionid"   => $uisesid,
                "csrftoken"     => $csrftokenholder,
                "currentfocus"  => "mx".$user_nossa->mx_witel,
                "scrollleftpos" => "0",
                "scrolltoppos"  => "0",
                "requesttype"   => "SYNC",
                "responsetype"  => "text/xml",
                "events"        => $event
            ]
        );

        curl_setopt($ch, CURLOPT_URL, 'https://nossa.telkom.co.id/maximo/ui/maximo.jsp');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $result = curl_exec($ch);
        $posisi = strpos($result,'<component vis="true" id="'.$user_nossa->id_comp.'_holder" compid="'.$user_nossa->id_comp.'"><![CDATA[<a ctype="label"  id="'.$user_nossa->id_comp.'"  href="')+100;
        $asd = substr_replace($result, "", 0, $posisi);
        $asd = substr_replace($asd, "", strpos($asd,'" onmouseover="return noStatus();" target="_blank"   noclick="1"   targetid="'.$user_nossa->id_comp.'"    class="text tht  " style="display:block;;;;;" title="Download"'), strlen($asd));
        curl_setopt($ch, CURLOPT_URL, $asd);
        $result = curl_exec($ch);
        curl_close ($ch);
        $result = str_replace('&nbsp;', ' ', $result);

        $columns = ['incident','customer_name','contact_name','contact_email','contact_phone','summary','owner_group','owner','last_updated_work_log','last_work_log_date','count_custinfo','last_custinfo','assigned_to','booking_date','assigned_by','reported_priority','source','subsidiary','external_ticket_id','external_ticket_status','segment','channel','customer_segment','customer_type','closed_by','customer_id','service_id','service_no','service_type','top_priority','slg','technology','datek_induk_gamas','datek','rk_name','ibooster_alert_id','induk_gamas','related_to_global_issue','reported_date','lapul','gaul','ttr_customer','ttr_nasional','ttr_regional','ttr_witel','ttr_mitra','ttr_agent','ttr_pending','pending_reason','status','hasil_ukur','osm_resolved_code','last_update_ticket','status_date','resolved_by','workzone','witel','regional','incidents_symptom','solutions_segment','actual_solution','incident_domain','onu_rx_before_after','scc_fisik_inet','scc_logic','complete_wo_by','kode_produk','notes_eskalasi','resolved_date','jumlah_site_tsel_nossa','kategori_tiket_tsel','impacted_site_tsel','id_incident'];

        $dom = @\DOMDocument::loadHTML(trim($result));

        if ($result == "")
        {
            dd("Reported Date $date\nAssurance Order Nossa Witel $witel Total 0\n\n$time\n");
        }

        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $result = array();
        for ($i = 1, $count = $rows->length; $i < $count; $i++)
        {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 0, $jcount = count($columns)-1; $j < $jcount; $j++)
            {
                $td = $cells->item($j);
                $data[$columns[$j]] = $td->nodeValue;

                if ($j == 0)
                {
                    $data['id_incident'] = substr($td->nodeValue, 2);
                    $data['incident'] = $td->nodeValue;
                }
            }

            if ($data['last_work_log_date'] == '')
            {
                $data['last_work_log_date'] = '0000-00-00 00:00:00';
            }
            else
            {
                $data['last_work_log_date'] = date('Y-m-d H:i:s', strtotime($data['last_work_log_date']));
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

            if ($data['last_update_ticket'] == '')
            {
                $data['last_update_ticket'] = '0000-00-00 00:00:00';
            }
            else
            {
                $data['last_update_ticket'] = date('Y-m-d H:i:s', strtotime($data['last_update_ticket']));
            }

            if ($data['status_date'] == '')
            {
                $data['status_date'] = '0000-00-00 00:00:00';
            }
            else
            {
                $data['status_date'] = date('Y-m-d H:i:s', strtotime($data['status_date']));
            }

            if ($data['resolved_date'] == '')
            {
                $data['resolved_date'] = '0000-00-00 00:00:00';
            }
            else
            {
                $data['resolved_date'] = date('Y-m-d H:i:s', strtotime($data['resolved_date']));
            }

            $result[] = $data;
        }

        $total = count($result);

        if ($total > 0)
        {
            DB::table('assurance_nossa_order')->where('witel', $witel)->whereDate('date_reported', date('Y-m-d', strtotime($date)))->delete();
            DB::connection('db_data_center')->statement('DELETE FROM assurance_nossa_order WHERE (DATE(date_reported) = "'.date('Y-m-d', strtotime($date)).'") AND witel = "'.$witel.'" AND ((`incident` LIKE "IN%" AND `customer_name` IS NULL) OR (`source` NOT IN ("SIMASTUPEN_WA", "INDIHOMECARE") OR `source` IS NULL))');

            $chunk = array_chunk($result,500);
            foreach($chunk as $data)
            {
                DB::table('assurance_nossa_order')->insert($data);
                DB::connection('db_data_center')->table('assurance_nossa_order')->insert($data);
            }

            print_r("Reported Date $date\nAssurance Order Nossa Witel $witel Total $total\n\n$time\n");

            sleep(5);
        }
    }
}
