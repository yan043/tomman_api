<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class IboosterController extends Controller
{
    public static function login_ibooster($is_username, $is_password)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.62.165.58/ibooster/login/',
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

        print_r("Cookies Login Page\n$cookiesOut\n\n");

        $input_sha256 = $dom->getElementsByTagName('input')->item(0)->getAttribute("value");
        $input_generate = $dom->getElementsByTagName('input')->item(4)->getAttribute("value");

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.62.165.58/ibooster/login/login_code.php',
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
            CURLOPT_POSTFIELDS => 'sha256='.$input_sha256.'&username='.$is_username.'&password='.urlencode($is_password).'&dropdown_login=sso&captcha='.$input_generate.'&generate='.$input_generate,
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
        preg_match_all ($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", $matches['cookie']);

        print_r("Cookies Request Login\n$cookiesOut\n\n");

        $scripts = $dom->getElementsByTagName('script');

        foreach ($scripts as $script)
        {
            if ($script->getAttribute('type') === 'text/javascript')
            {
                $javascriptCode = $script->nodeValue;

                $pattern = "/window\.location=['\"]([^'\"]+)['\"]/";

                if (preg_match($pattern, $javascriptCode, $matches))
                {
                    $destroy_url = $matches[1];
                    break;
                }
            }
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.62.165.58/ibooster/login/'.$destroy_url,
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

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.62.165.58/ibooster/home.php',
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
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $xpath = new \DOMXPath($dom);
        curl_close($curl);

        $query = "//li[contains(., 'UKUR MASSAL')]";

        $elements = $xpath->query($query);

        if ($elements->length > 0)
        {
            $ukurMassalElement = $elements[0];

            $query2 = "./ul/li/a[contains(@href, '?page=')]";
            $subElements = $xpath->query($query2, $ukurMassalElement);

            foreach ($subElements as $subElement)
            {
                $page_id = $subElement->getAttribute('href');
                break;
            }
        }

        if($cookiesOut)
        {
            DB::table('cookie_systems')->where('application', 'ibooster')->update([
                'username' => $is_username,
                'password' => $is_password,
                'page'     => $page_id,
                'cookies'  => $cookiesOut
            ]);
        }

        dd($response);
    }

    public static function ukur_massal_inet($list_inet)
    {
        $list_inet = str_replace('_', ';', $list_inet);

        $ibooster = DB::table('cookie_systems')->where('application', 'ibooster')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.62.165.58/ibooster/home.php'.$ibooster->page,
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
            CURLOPT_POSTFIELDS => 'nospeedy='.urlencode($list_inet).'&analis=ANALISA',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$ibooster->cookies
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $columns = [ 1 => 'ND', 'IP_Embassy', 'Type', 'Calling_Station_Id', 'IP_NE', 'ADSL_Link_Status', 'Upstream_Line_Rate', 'Upstream_SNR', 'Upstream_Attenuation', 'Upstream_Attainable_Rate', 'Downstream_Line_Rate', 'Downstream_SNR', 'Downstream_Attenuation', 'Downstream_Attainable_Rate', 'ONU_Link_Status', 'ONU_Serial_Number', 'Fiber_Length', 'OLT_Tx', 'OLT_Rx', 'ONU_Tx', 'ONU_Rx', 'Gpon_Onu_Type', 'Gpon_Onu_VersionID', 'Gpon_Traffic_Profile_UP', 'Gpon_Traffic_Profile_Down', 'Framed_IP_Address', 'MAC_Address', 'Last_Seen', 'AcctStartTime', 'AccStopTime', 'AccSesionTime', 'Up', 'Down', 'Status_Koneksi', 'Nas_IP_Address'
        ];

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(1);

        if ($table != null)
        {
            $rows = $table->getElementsByTagName('tr');
            $result = [];
            for ($i = 3, $count = $rows->length; $i < $count; $i++)
            {
                $cells = $rows->item($i)->getElementsByTagName('td');
                $data = [];
                for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++)
                {
                    $td = $cells->item($j);
                    if (is_object($td))
                    {
                        $node = $td->nodeValue;
                    }
                    else
                    {
                        $node = "empty";
                    }

                    $data[$columns[$j]] = trim($node);
                }
                $result[] = $data;
            }
        }

        dd($result);
    }

    public static function monet_bpp()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.62.165.58/monet/hvc-kritis-bpp',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $result_hvc = json_decode($response);

        if (isset($result_hvc->data))
        {
            $total_hvc = count($result_hvc->data);
            print_r("\nmonet hvc dengan total $total_hvc\n");

            DB::connection('db_data_center')->table('monet_hasil_ukur')->where('category', 'hvc')->delete();

            foreach ($result_hvc->data as $k_hvc => $v_hvc)
            {
                $insert_hvc[] = [
                    'category'   => 'hvc',
                    'regional'   => $v_hvc->regional,
                    'nd_inet'    => $v_hvc->nd_inet,
                    'odp_name'   => $v_hvc->odp_name,
                    'witel'      => $v_hvc->witel,
                    'sto'        => $v_hvc->sto,
                    'onu_status' => $v_hvc->onu_status,
                    'onu_sn'     => $v_hvc->onu_sn,
                    'node_id'    => $v_hvc->node_id,
                    'slot'       => $v_hvc->slot,
                    'port'       => $v_hvc->port,
                    'onu_id'     => $v_hvc->onu_id,
                    'onu_rx_pwr' => $v_hvc->onu_rx_pwr,
                    'updated_at' => $v_hvc->updated_at,
                    'offline_at' => $v_hvc->offline_at,
                ];
            }

            foreach (array_chunk($insert_hvc, 500) as $kk_hvc => $vv_hvc)
            {
                DB::connection('db_data_center')->table('monet_hasil_ukur')->insert($vv_hvc);

                print_r("save page number $kk_hvc category hvc\n");
            }
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.62.165.58/monet/hasil-ukur-bpp',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $result_all = json_decode($response);

        if (isset($result_all->data))
        {
            $total_all = count($result_all->data);
            print_r("\nmonet all dengan total $total_all\n");

            DB::connection('db_data_center')->table('monet_hasil_ukur')->where('category', 'all')->delete();

            foreach ($result_all->data as $k_all => $v_all)
            {
                $insert_all[] = [
                    'category'   => 'all',
                    'regional'   => $v_all->regional,
                    'nd_inet'    => $v_all->nd_inet,
                    'odp_name'   => $v_all->odp_name,
                    'witel'      => $v_all->witel,
                    'sto'        => $v_all->sto,
                    'onu_status' => $v_all->onu_status,
                    'onu_sn'     => $v_all->onu_sn,
                    'node_id'    => $v_all->node_id,
                    'slot'       => $v_all->slot,
                    'port'       => $v_all->port,
                    'onu_id'     => $v_all->onu_id,
                    'onu_rx_pwr' => $v_all->onu_rx_pwr,
                    'updated_at' => $v_all->updated_at,
                    'offline_at' => $v_all->offline_at,
                ];
            }

            foreach (array_chunk($insert_all, 500) as $kk_all => $vv_all)
            {
                DB::connection('db_data_center')->table('monet_hasil_ukur')->insert($vv_all);

                print_r("save page number $kk_all category all\n");
            }
        }
    }
}
