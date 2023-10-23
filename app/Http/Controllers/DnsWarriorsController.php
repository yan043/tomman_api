<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class DnsWarriorsController extends Controller
{
    public static function order_premium_dns($order, $kode)
    {
        switch ($order) {
            case 'stb':
                switch ($kode) {
                    case 'valid':
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://dns.warriors.id/menu/dashboard_stb_premium_exp.php?kode=' . $kode,
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
                        libxml_use_internal_errors(true);
                        $dom = new \DOMDocument();
                        $dom->loadHTML(trim($response));
                        $table = $dom->getElementsByTagName('table')->item(0);
                        $rows = $table->getElementsByTagName('tr');
                        $columns = array(
                            1 => 'reg', 'witel', 'sto', 'no_internet', 'nd_pots', 'mac_address', 'nama_pelanggan', 'alamat_pelanggan', 'no_hp_pelanggan'
                        );
                        $result = array();
                        for ($i = 1, $count = $rows->length; $i < $count; $i++)
                        {
                            $cells = $rows->item($i)->getElementsByTagName('td');
                            $data = array();
                            for ($j = 1, $jcount = count($columns); $j <= $jcount; $j++) {
                                $td = $cells->item($j);
                                $data[$columns[$j]] =  $td->nodeValue;
                            }
                            $result[] = $data;
                        }

                        if (count($result) > 0)
                        {
                            $total = count($result);

                            DB::table('migrasi_stb_premium_log')->where('kode', $kode)->delete();

                            foreach ($result as $value)
                            {
                                $insert[] = [
                                    'reg' => $value['reg'],
                                    'witel' => $value['witel'],
                                    'sto' => $value['sto'],
                                    'no_internet' => $value['no_internet'],
                                    'nd_pots' => $value['nd_pots'],
                                    'mac_address' => $value['mac_address'],
                                    'nama_pelanggan' => $value['nama_pelanggan'],
                                    'alamat_pelanggan' => $value['alamat_pelanggan'],
                                    'no_hp_pelanggan' => $value['no_hp_pelanggan'],
                                    'kode' => $kode
                                ];
                            }

                            $chunk = array_chunk($insert, 500);
                            foreach ($chunk as $numb => $data)
                            {
                                DB::table('migrasi_stb_premium_log')->insert($data);

                                print_r("saved page $numb and sleep (1)\n");

                                sleep(1);
                            }

                            print_r("Finish Grab Order Migrasi STB Premium Kode $kode Total $total\n");
                        }
                        break;

                    case 'ps':
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://dns.warriors.id/menu/dashboard_stb_premium_exp.php?kode='.$kode,
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
                        libxml_use_internal_errors(true);
                        $dom = new \DOMDocument();
                        $dom->loadHTML(trim($response));
                        $table = $dom->getElementsByTagName('table')->item(0);
                        $rows = $table->getElementsByTagName('tr');
                        $columns = array(
                            1 => 'wo_id' , 'reg', 'witel', 'sto', 'no_internet', 'nd_pots', 'mac_address', 'nama_pelanggan', 'alamat_pelanggan', 'no_hp_pelanggan', 'helpdesk_assign', 'amcrew', 'nik_teknisi_1', 'nik_teknisi_2', 'merk', 'stb_id', 'mac_address_baru', 'tanggal_assign', 'tanggal_visited', 'tanggal_usage'
                        );
                        $result = array();
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

                        if (count($result) > 0)
                        {
                            $total = count($result);

                            DB::table('migrasi_stb_premium_log')->where('kode', $kode)->delete();

                            foreach ($result as $value)
                            {
                                $insert[] = [
                                    'wo_id' => $value['wo_id'],
                                    'reg' => $value['reg'],
                                    'witel' => $value['witel'],
                                    'sto' => $value['sto'],
                                    'no_internet' => $value['no_internet'],
                                    'nd_pots' => $value['nd_pots'],
                                    'mac_address' => $value['mac_address'],
                                    'nama_pelanggan' => $value['nama_pelanggan'],
                                    'alamat_pelanggan' => $value['alamat_pelanggan'],
                                    'no_hp_pelanggan' => $value['no_hp_pelanggan'],
                                    'helpdesk_assign' => $value['helpdesk_assign'],
                                    'amcrew' => $value['amcrew'],
                                    'nik_teknisi1' => $value['nik_teknisi1'],
                                    'nik_teknisi2' => $value['nik_teknisi2'],
                                    'merk' => $value['merk'],
                                    'stb_id' => $value['stb_id'],
                                    'mac_address_baru' => $value['mac_address_baru'],
                                    'tanggal_assign' => $value['tanggal_assign'],
                                    'tanggal_visited' => $value['tanggal_visited'],
                                    'tanggal_usage' => $value['tanggal_usage'],
                                    'kode' => $kode
                                ];
                            }

                            $chunk = array_chunk($insert, 500);
                            foreach ($chunk as $numb => $data)
                            {
                                DB::table('migrasi_stb_premium_log')->insert($data);

                                print_r("saved page $numb and sleep (1)\n");

                                sleep(1);
                            }

                            print_r("Finish Grab Order Migrasi STB Premium Kode $kode Total $total\n");
                        }
                        break;
                    }
                    // insert to data master
                    DB::table('migrasi_stb_premium')->where('kode', $kode)->delete();
                    DB::statement('INSERT INTO `migrasi_stb_premium` SELECT * FROM `migrasi_stb_premium_log` WHERE `kode` = "' . $kode .'"');
                break;

            case 'ont':
                switch ($kode) {
                    case 'valid':
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://dns.warriors.id/menu/dashboard_ont_premium_exp.php?kode='.$kode,
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
                        libxml_use_internal_errors(true);
                        $dom = new \DOMDocument();
                        $dom->loadHTML(trim($response));
                        $table = $dom->getElementsByTagName('table')->item(0);
                        $rows = $table->getElementsByTagName('tr');
                        $columns = array(
                            1 => 'reg', 'witel', 'nama_pelanggan', 'alamat_pelanggan', 'no_tlp', 'no_internet', 'kw', 'type_ont', 'average_arpu', 'no_hp', 'jenis_dapros'
                        );
                        $result = array();
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

                        if (count($result) > 0)
                        {
                            $total = count($result);

                            DB::table('migrasi_ont_premium_log')->where('kode', $kode)->delete();

                            foreach ($result as $value)
                            {
                                $insert[] = [
                                    'reg' => $value['reg'],
                                    'witel' => $value['witel'],
                                    'nama_pelanggan' => $value['nama_pelanggan'],
                                    'alamat_pelanggan' => $value['alamat_pelanggan'],
                                    'no_tlp' => $value['no_tlp'],
                                    'no_internet' => $value['no_internet'],
                                    'kw' => $value['kw'],
                                    'type_ont' => $value['type_ont'],
                                    'average_arpu' => $value['average_arpu'],
                                    'no_hp' => $value['no_hp'],
                                    'jenis_dapros' => $value['jenis_dapros'],
                                    'kode' => $kode
                                ];
                            }

                            $chunk = array_chunk($insert, 500);
                            foreach ($chunk as $numb => $data)
                            {
                                DB::table('migrasi_ont_premium_log')->insert($data);

                                print_r("saved page $numb and sleep (1)\n");

                                sleep(1);
                            }

                            print_r("Finish Grab Order Migrasi ONT Premium Kode $kode Total $total\n");
                        }
                        break;

                    case 'ps':
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://dns.warriors.id/menu/dashboard_ont_premium_exp.php?kode='.$kode,
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
                        libxml_use_internal_errors(true);
                        $dom = new \DOMDocument();
                        $dom->loadHTML(trim($response));
                        $table = $dom->getElementsByTagName('table')->item(0);
                        $rows = $table->getElementsByTagName('tr');
                        $columns = array(
                            1 => 'wo_id', 'reg', 'witel', 'nama_pelanggan', 'alamat_pelanggan', 'no_tlp', 'no_internet', 'ont_type_eksisting', 'sn_ont_eksisting', 'ont_type_baru', 'sn_ont_baru', 'type', 'helpdesk_approve', 'amcrew', 'nik_teknisi1', 'nik_teknisi2', 'no_hp', 'jenis_dapros', 'tanggal_usage'
                        );
                        $result = array();
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

                        if (count($result) > 0)
                        {
                            $total = count($result);

                            DB::table('migrasi_ont_premium_log')->where('kode', $kode)->delete();

                            foreach ($result as $value)
                            {
                                $insert[] = [
                                    'wo_id' => $value['wo_id'],
                                    'reg' => $value['reg'],
                                    'witel' => $value['witel'],
                                    'nama_pelanggan' => $value['nama_pelanggan'],
                                    'alamat_pelanggan' => $value['alamat_pelanggan'],
                                    'no_tlp' => $value['no_tlp'],
                                    'no_internet' => $value['no_internet'],
                                    'ont_type_eksisting' => $value['ont_type_eksisting'],
                                    'sn_ont_eksisting' => $value['sn_ont_eksisting'],
                                    'ont_type_baru' => $value['ont_type_baru'],
                                    'sn_ont_baru' => $value['sn_ont_baru'],
                                    'type' => $value['type'],
                                    'helpdesk_approve' => $value['helpdesk_approve'],
                                    'amcrew' => $value['amcrew'],
                                    'nik_teknisi1' => $value['nik_teknisi1'],
                                    'nik_teknisi2' => $value['nik_teknisi2'],
                                    'no_hp' => $value['no_hp'],
                                    'jenis_dapros' => $value['jenis_dapros'],
                                    'tanggal_usage' => $value['tanggal_usage'],
                                    'kode' => $kode
                                ];
                            }

                            $chunk = array_chunk($insert, 500);
                            foreach ($chunk as $numb => $data)
                            {
                                DB::table('migrasi_ont_premium_log')->insert($data);

                                print_r("saved page $numb and sleep (1)\n");

                                sleep(1);
                            }

                            print_r("Finish Grab Order Migrasi ONT Premium Kode $kode Total $total\n");
                        }
                        break;
                    }
                    // insert to data master
                    DB::table('migrasi_ont_premium')->where('kode', $kode)->delete();
                    DB::statement('INSERT INTO `migrasi_ont_premium` SELECT * FROM `migrasi_ont_premium_log` WHERE `kode` = "' . $kode .'"');
                break;
        }
    }

}
