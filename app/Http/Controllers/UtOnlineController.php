<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;

date_default_timezone_set('Asia/Makassar');

class UtOnlineController extends Controller
{
    public static function login_utonline()
    {
        $username = '18900106';
        $password = 'sukses$05';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://utonline.telkom.co.id/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
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
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all ($pattern, $header_content, $matches);
        $cookiesOut = "";
        $header['headers'] = $header_content;
        $header['cookies'] = $cookiesOut;
        $cookiesOut = implode("; ", $matches['cookie']);

        DB::table('cookie_systems')->where('application', 'utonline')->update([
            'cookies' => $cookiesOut
        ]);

        $token = $dom->getElementsByTagName('input')->item(0)->getAttribute("value");

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://utonline.telkom.co.id/actor/otp/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'guid=0&code=0&data='.urlencode('{"token":"'.$token.'","otpCode":"'.$username.'"}'),
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              'Cookie: '.$cookiesOut
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
            CURLOPT_URL => 'https://utonline.telkom.co.id/composite/user/authenticate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'guid=0&code=0&data='.urlencode('{"token":"'.$token.'","otpCode":"'.$username.'","otpPassword":"'.$password.'","otp":"'.$otp.'"}'),
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              'Cookie: '.$cookiesOut
            ),
        ));
        curl_exec($curl);
        curl_close($curl);

        self::refresh_utonline();
    }

    public static function refresh_utonline()
    {
        $utonline = DB::table('cookie_systems')->where('application', 'utonline')->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://utonline.telkom.co.id/ut-online/api/order/get-count-tl',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'start_date='.date('Y-m-d', strtotime("-6 days")).'&end_date='.date('Y-m-d'),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: utonlineprod='.$utonline->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response);
        print_r($result);
    }

    public static function full_utonline()
    {
        $utonline = DB::table('cookie_systems')->where('application', 'utonline')->first();

        for ($i = 0; $i <= 31; $i++)
        {
            $date = date('Y-m-d', strtotime("-$i days"));

            exec("php /srv/htdocs/tomman_api/artisan full_utonline_date $utonline->cookies $date > /dev/null &");

            print_r("php /srv/htdocs/tomman_api/artisan full_utonline_date $utonline->cookies $date > /dev/null &\n");
        }
    }

    public static function full_utonline_date($cookie, $date)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://utonline.telkom.co.id/ut-online/api/order/listOrder',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'data[type]'                   => 'inbox_search',
                'data[sdate]'                  => $date,
                'data[edate]'                  => $date,
                'data[range]'                  => 'xd2',
                'data[where][order_code]'      => '',
                'data[where][xs1]'             => '',
                'data[where][xs4]'             => '',
                'data[where][xs5]'             => '',
                'data[where][order_status_id]' => '',
                'data[where][xs9]'             => 'KALSEL (BANJARMASIN)',
                'data[where][xs10]'            => 'REGIONAL_6',
                'data[isHistory]'              => true,
                'page'                         => '1',
                'size'                         => '100'
            ],
            CURLOPT_HTTPHEADER => [
                'Cookie: '.$cookie
            ],
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response);
        if ($result)
        {
            if ($result->total_rows)
            {
                DB::connection('db_t1')->table('utonline_tr6')->where('tglTrx_date', $date)->delete();

                print_r("\ndate $date total pages $result->total_pages total rows $result->total_rows \n");

                $pages = $result->total_pages;

                for ($x = 1; $x <= $pages; $x++)
                {
                    exec("php /srv/htdocs/tomman_api/artisan full_utonline_pages $cookie $date $x > /dev/null &");

                    print_r("php /srv/htdocs/tomman_api/artisan full_utonline_pages $cookie $date $x > /dev/null &\n");
                }
            }
        }
    }

    public static function full_utonline_pages($cookie, $date, $x)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://utonline.telkom.co.id/ut-online/api/order/listOrder',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'data[type]'                   => 'inbox_search',
                'data[sdate]'                  => $date,
                'data[edate]'                  => $date,
                'data[range]'                  => 'xd2',
                'data[where][order_code]'      => '',
                'data[where][xs1]'             => '',
                'data[where][xs4]'             => '',
                'data[where][xs5]'             => '',
                'data[where][order_status_id]' => '',
                'data[where][xs9]'             => 'KALSEL (BANJARMASIN)',
                'data[where][xs10]'            => 'REGIONAL_6',
                'data[isHistory]'              => true,
                'page'                         => $x,
                'size'                         => '100'
            ],
            CURLOPT_HTTPHEADER => [
                'Cookie: '.$cookie
            ],
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response);

        if ($result)
        {
            $data = $result->data;
            $total = count($data);

            foreach ($data as $d)
            {
                if (strpos(@$d->scId, 'SC') !== false)
                {
                    $jenis_order = 'SC';
                }
                elseif (strpos(@$d->scId, 'MYIR') !== false)
                {
                    $jenis_order = 'MYIR';
                }
                elseif (strpos(@$d->scId, 'MYID') !== false)
                {
                    $jenis_order = 'MYID';
                }
                elseif (strpos(@$d->scId, 'MYDB') !== false)
                {
                    $jenis_order = 'MYDB';
                }
                elseif (strpos(@$d->scId, 'MYIRX') !== false)
                {
                    $jenis_order = 'MYIRX';
                }
                elseif (strpos(@$d->scId, '3-') !== false)
                {
                    $jenis_order = 'CORPORATE';
                }
                else
                {
                    $jenis_order = null;
                }

                $insert[] = [
                    'tipePerusahaanDesc' => @$d->tipePerusahaanDesc,
                    'order_id'           => @$d->order_id,
                    'order_code'         => @$d->order_code,
                    'order_type_id'      => @$d->order_type_id,
                    'order_subtype_id'   => @$d->order_subtype_id,
                    'order_status_id'    => @$d->order_status_id,
                    'order_desc'         => @$d->order_desc,
                    'customer_desc'      => @$d->customer_desc,
                    'product_desc'       => @$d->product_desc,
                    'create_user_id'     => @$d->create_user_id,
                    'create_dtm'         => @$d->create_dtm,
                    'close_dtm'          => @$d->close_dtm,
                    'tipePerusahaan'     => @$d->tipePerusahaan,
                    'scId'               => @$d->scId,
                    'scId_int'           => str_replace(array('SC', 'MYIR-', 'MYID-', 'MYDB-', 'MYIRX-', '3-'), '', @$d->scId),
                    'laborCode'          => @$d->laborCode,
                    'namaPerusahaan'     => @$d->namaPerusahaan,
                    'noInternet'         => @$d->noInternet,
                    'noVoice'            => @$d->noVoice,
                    'rating'             => @$d->rating,
                    'sto'                => @$d->sto,
                    'leader'             => @$d->leader,
                    'witel'              => @$d->witel,
                    'regional'           => @$d->regional,
                    'laborName'          => @$d->laborName,
                    'segment'            => @$d->segment,
                    'orderSource'        => @$d->orderSource,
                    'wonumChild'         => @$d->wonumChild,
                    'pickedBy'           => @$d->pickedBy,
                    'pickedAt'           => @$d->pickedAt,
                    'assignBy'           => @$d->assignBy,
                    'qcApproveBy'        => @$d->qcApproveBy,
                    'qcStatus'           => @$d->qcStatus,
                    'qcStatusName'       => @$d->qcStatusName,
                    'qcNotes'            => @$d->qcNotes,
                    'tglWo'              => @$d->tglWo,
                    'tglWo_date'         => date('Y-m-d', strtotime(@$d->tglWo)),
                    'tglTrx'             => @$d->tglTrx,
                    'tglTrx_date'        => date('Y-m-d', strtotime(@$d->tglTrx)),
                    'statusName'         => @$d->statusName,
                    'createUserName'     => @$d->createUserName,
                    'details'            => json_encode(@$d->details),
                    'approver'           => json_encode(@$d->approver),
                    'getFlowLatest'      => json_encode(@$d->getFlowLatest),
                    'jenis_order'        => $jenis_order
                ];
            }

            $chunk = array_chunk($insert, 500);

            foreach ($chunk as $data)
            {
                DB::connection('db_t1')->table('utonline_tr6')->insert($data);
            }

            print_r("finish saved pages $x total $total\n");

            DB::connection('db_t1')->statement('DELETE FROM `utonline_tr6` WHERE `order_code` LIKE "-%"');

            sleep(1);
        }
    }

    public static function material_utonline($startDate, $endDate)
    {
        $data = DB::connection('db_t1')->table('utonline_tr6')->whereBetween('tglTrx_date', [$startDate, $endDate])->groupBy('order_id')->orderBy('tglTrx', 'DESC')->get();

        foreach ($data as $value)
        {
            $result = self::get_material_utonline($value->order_id, 'alista');
            if (array_filter($result))
            {
                DB::connection('db_t1')->table('utonline_material')->where([
                    ['id_order', $value->order_id],
                    ['type', 'alista']
                ])->delete();

                foreach ($result as $v)
                {
                    DB::connection('db_t1')->table('utonline_material')->insert([
                        'id_order'  => $value->order_id,
                        'id_barang' => $v['id_barang'],
                        'type'      => 'alista',
                        'stok'      => $v['stok'],
                        'satuan'    => $v['satuan'],
                        'volume'    => $v['volume']
                    ]);
                }

                print_r("saved order id $value->order_id \n");
            }
        }
    }

    public static function get_material_utonline($id, $type)
    {
        $utonline = DB::table('cookie_systems')->where('application', 'utonline')->first();

        switch ($type) {
            case 'alista':
                $id_type = '0';
                break;

            case 'tambahan':
                $id_type = '1';
                break;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://utonline.telkom.co.id/evidence/order/detail?id='.$id.'&flag=search',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$utonline->cookies
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementsByTagName('table')->item($id_type);
        $rows = $table->getElementsByTagName('tr');

        $columns = [
            1 => 'id_barang', 'stok', 'satuan', 'volume'
        ];

        $result = [];

        for ($i = 1, $count = $rows->length; $i < $count; $i++)
        {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = [];

            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++)
            {
                $td = $cells->item($j);
                $data[$columns[$j]] =  $td->nodeValue;
                $data['id_order'] = $id;
                $data['type'] = $type;
            }

            $result[] = $data;
        }

        return $result;
    }
}
