<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class ApiController extends Controller
{
    public static function cleansing_trash_order_kawan($witel)
    {
        DB::connection('db_data_center')->table('assurance_nossa_order')->whereRaw('DATE(date_reported) < "2023" AND witel = "'.$witel.'"')->delete();

        DB::connection('db_data_center')->table('dispatch_order AS do')
        ->leftJoin('assurance_nossa_order AS ano', 'do.order_id', '=', 'ano.id_incident')
        ->whereRaw('ano.incident IS NULL AND ano.witel = "'.$witel.'"')
        ->delete();

        DB::connection('db_data_center')->table('log_dispatch_order AS ldo')
        ->leftJoin('dispatch_order AS do', 'ldo.dispatch_id', '=', 'do.id_dispatch')
        ->whereNull('do.order_type')
        ->delete();

        DB::connection('db_data_center')->table('master_order AS mo')
        ->leftJoin('dispatch_order AS do', 'mo.id_dispatch_order', '=', 'do.id_dispatch')
        ->whereNull('do.order_type')
        ->delete();

        DB::connection('db_data_center')->table('log_master_order AS lmo')
        ->leftJoin('master_order AS mo', 'lmo.id_master_order', '=', 'mo.id_master_order')
        ->whereNull('mo.id_master_order')
        ->delete();

        DB::connection('db_data_center')->statement('DELETE a1 FROM assurance_nossa_order a1, assurance_nossa_order a2 WHERE a1.id > a2.id AND a1.id_incident = a2.id_incident');

        DB::connection('db_data_center')->statement('DELETE a1 FROM master_order a1, master_order a2 WHERE a1.id_master_order > a2.id_master_order AND a1.id_dispatch_order = a2.id_dispatch_order');

        DB::connection('db_data_center')->statement('DELETE a1 FROM log_master_order a1, log_master_order a2 WHERE a1.id_master_order > a2.id_master_order AND a1.id_dispatch_order = a2.id_dispatch_order');

        print_r("finish cleansing trash order!\n");
    }
}
