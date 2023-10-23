<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class RismaController extends Controller
{
    public static function all_sync_risma()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.194.5.20/Risma/load-data-consume-list-outlet-witel.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'status-data=0&login=730578',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item(0);
        $rows = $table->getElementsByTagName('tr');

        $columns = [1 => 'apply_date', 'trackID', 'nama_pelanggan', 'handphone', 'email', 'survey', 'odp', 'kordinat', 'witel', 'status_data', 'keterangan', 'status_inputer', 'tools'];

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
            $data['trackID_int'] = str_replace(array('MYID-', 'MYIR-', 'YID-'), '', $data['trackID']);
            $data['sto'] = substr(str_replace(array('\r\n', '\n', ' '), '', $data['odp']), 4, 3);
            $data['last_updated'] = date('Y-m-d H:i:s');
            $result[] = $data;
        }

        $total = count($result);
        if ($total > 0)
        {
            DB::connection('db_t1')->table('risma_prov_witel')->truncate();

            $srcarr = array_chunk($result, 500);
            foreach ($srcarr as $numb => $item)
            {
                DB::connection('db_t1')->table('risma_prov_witel')->insert($item);

                print_r("saved pages $numb and sleep (1)\n");
                sleep(1);
            }

            print_r("Success Grab Risma (ALL) Total $total\n");
        }
    }
}
