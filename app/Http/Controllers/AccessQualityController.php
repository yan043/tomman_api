<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class AccessQualityController extends Controller
{
    public static function non_warranty($regional, $witel)
    {
        $date = date('Y-m-d');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://access-quality.telkom.co.id/rekap_unspec_sektor/dashboard_semesta/export_data_detail.php?sektor=&jenis=non_warranty&regional=$regional&witel=$witel&tanggal=$date",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));

        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');
        $columns = [ 1=> 'reg', 'witel', 'sektor', 'node_id', 'shelf_slot_port_onuid', 'fiber_length', 'cmdf', 'rk', 'dp', 'nd', 'tanggal_ps', 'status_inet', 'onu_rx_power', 'tanggal_ukur', 'onu_rx_power_ukur_ulang', 'tanggal_ukur_ulang', 'nomor_tiket', 'status_tiket', 'flag_hvc', 'type_pelanggan', 'prioritas'];
        $result = [];

        for ($i = 1, $count = $rows->length; $i < $count; $i++)
        {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = array();
            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++)
            {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
            }

            $result[] = $data;
        }

        dd($result);
    }
}
