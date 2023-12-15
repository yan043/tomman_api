<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class KanossController extends Controller
{
    public static function login_kanoss($uname, $passw, $chatid)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dashboard0.telkom.co.id/prabagen/index/page/238',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
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
            DB::table('cookie_systems')->where('application', 'kanoss')->update([
                'username' => $uname,
                'password' => $passw,
                'cookies'  => $cookiesOut
            ]);
        }

        $captcha_img = $dom->getElementsByTagName('img')->item(0)->getAttribute("src");
        $captcha_id = $dom->getElementById('captcha-id')->getAttribute("value");
        $captcha_photo = 'https://dashboard0.telkom.co.id'.$captcha_img;

        $caption = 'Kode Captcha Prabac Kanoss '.date('Y-m-d H:i:s');
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
            CURLOPT_URL => 'https://dashboard0.telkom.co.id/public/login?redirect=/prabagen/index/page/238',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "uname=$uname&passw=$passw&captcha%5Bid%5D=$captcha_id&captcha%5Binput%5D=$captcha&term=on",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$cookiesOut
            ),
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dashboard0.telkom.co.id/prabagen/index/page/238',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$cookiesOut
            ),
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://smartanalytics0.telkom.co.id/javascripts/api/viz_v1.js',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$cookiesOut
            ),
        ));
        curl_exec($curl);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://smartanalytics0.telkom.co.id/t/OperationalAnalytics/views/DashboardNTENewSCMT/DashboardNTE?%3Aembed=y&%3AshowVizHome=no&%3Ahost_url=https%3A%2F%2Fsmartanalytics0.telkom.co.id%2F&%3Aembed_code_version=3&%3Atabs=no&%3Atoolbar=yes&%3AshowAppBanner=false&%3Adisplay_spinner=no&%3AloadOrderID=0',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Cookie: tableau_locale=en'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        dd($response);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);

        $headerArray = explode("\r\n", $headers);
        dd($headerArray);

        $xSessionId = '';
        foreach ($headerArray as $header) {
            if (strpos($header, 'X-Session-Id:') !== false) {
                $xSessionId = trim(str_replace('X-Session-Id:', '', $header));
                break;
            }
        }
        dd($xSessionId);

        dd($response);
    }

    public static function kanoss_intech()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $id_session = 'AF9630EEF5BE45AA8B7F29A2CF69EA65-2:1';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://smartanalytics0.telkom.co.id/vizql/t/OperationalAnalytics/w/DashboardNTENewSCMT/v/DashboardNTE/sessions/'.$id_session.'/commands/tabdoc/get-show-data-pres-model',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('dataProviderType' => 'selection','showDataTableFormat' => 'serialized-formatted-tables','topN' => '700000','showDataTables' => 'underlying-data','showDataTableId' => '_495E2AD28EEF4A3C97B4C483D7B88C45','visualIdPresModel' => '{"worksheet":"wb_Stock_Gudang","dashboard":"Dashboard NTE","flipboardZoneId":0,"storyPointId":0}'),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response);

        if ($result)
        {
            if ($result->vqlCmdResponse->cmdResultList[0]->commandReturn->showDataPresModel->dataTablePresModels[0]->showDataFormattedTable)
            {
                $data = json_decode($result->vqlCmdResponse->cmdResultList[0]->commandReturn->showDataPresModel->dataTablePresModels[0]->showDataFormattedTable);

                $total = count($data->table->tuples);

                if ($total > 0)
                {
                    print_r("start saved total $total\n");

                    DB::connection('db_data_center')->table('kanoss_intech_newscmt')->truncate();

                    foreach ($data->table->tuples as $k => $v)
                    {
                        $insert[] = [
                            'device_type'                => $v[7],
                            'device_id'                  => $v[15],
                            'dumped'                     => $v[16],
                            'installed'                  => $v[17],
                            'installed_via'              => $v[18],
                            'intech'                     => $v[19],
                            'is_asset'                   => $v[20],
                            'item_attr_inventory_status' => $v[21],
                            'item_code'                  => $v[22],
                            'item_description'           => $v[23],
                            'item_id'                    => str_replace(',', '', $v[24]),
                            'location_id'                => str_replace(',', '', $v[25]),
                            'loc_code'                   => $v[26],
                            'loc_description'            => $v[27],
                            'loc_id'                     => str_replace(',', '', $v[28]),
                            'mac_address'                => $v[29],
                            'merk_copy'                  => $v[30],
                            'number_of_records'          => $v[31],
                            'order_date'                 => date('Y-m-d H:i:s', strtotime($v[32])),
                            'order_supplier'             => $v[33],
                            'order_supplier_id'          => $v[34],
                            'owner_id'                   => $v[35],
                            'periode_intech_copy'        => $v[36],
                            'period_inst'                => date('Y-m-d H:i:s', strtotime($v[37])),
                            'product_id'                 => $v[38],
                            'purchase_mode'              => $v[39],
                            'purchase_order'             => $v[40],
                            'reference'                  => $v[41],
                            'reference_a'                => $v[42],
                            'reference_b'                => $v[43],
                            'refresh_date'               => date('Y-m-d H:i:s', strtotime($v[44])),
                            'region_id'                  => $v[45],
                            'remarks'                    => $v[46],
                            'serial_key_id'              => $v[47],
                            'sn'                         => $v[48],
                            'stb_id'                     => $v[49],
                            'supplier_name'              => $v[50],
                            'supplier_number'            => $v[51],
                            'technician_code'            => $v[52],
                            'technician_code_name'       => $v[53],
                            'technician_code_ref'        => $v[54],
                            'tech_date'                  => date('Y-m-d H:i:s', strtotime($v[55])),
                            'tech_inst_code'             => $v[56],
                            'tech_inst_name'             => $v[57],
                            'telkom_serial_number'       => $v[58],
                            'terms_of_payment'           => $v[59],
                            'terms_of_payment_id'        => $v[60],
                            'type_location_id'           => $v[61],
                            'wh_date'                    => date('Y-m-d H:i:s', strtotime($v[62])),
                            'wh_desc'                    => $v[63],
                            'wh_owner_type'              => $v[64],
                            'witel_copy'                 => $v[65],
                            'witel'                      => $v[66],
                        ];
                    }

                    foreach(array_chunk($insert, 500) as $numb => $data)
                    {
                        DB::connection('db_data_center')->table('kanoss_intech_newscmt')->insert($data);

                        print_r("saved page $numb and sleep (1)\n");

                        sleep(1);
                    }
                }
            }
        }
    }
}
