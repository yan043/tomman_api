<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\IboosterController;
use App\Http\Controllers\KproController;
use App\Http\Controllers\StarclickController;
use App\Http\Controllers\StarclickOneController;
use App\Http\Controllers\TacticalProController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\DnsWarriorsController;
use App\Http\Controllers\NossaController;
use App\Http\Controllers\InseraController;
use App\Http\Controllers\UtOnlineController;
use App\Http\Controllers\PrabacController;
use App\Http\Controllers\RismaController;
use App\Http\Controllers\XproController;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ibooster
Artisan::command('login_ibooster {is_username} {is_password}', function ($is_username, $is_password) {
    IboosterController::login_ibooster($is_username, $is_password);
});
Artisan::command('ukur_massal_inet {list_inet}', function ($list_inet) {
    IboosterController::ukur_massal_inet($list_inet);
});
Artisan::command('monet_bpp', function () {
    IboosterController::monet_bpp();
});

// xpro
Artisan::command('login_xpro {uname} {pass} {chatid}', function ($uname, $pass, $chatid) {
    XproController::login_xpro($uname, $pass, $chatid);
});

// kpro
Artisan::command('login_kpro {uname} {pass} {chatid}', function ($uname, $pass, $chatid) {
    KproController::login_kpro($uname, $pass, $chatid);
});
Artisan::command('refresh_kpro', function () {
    KproController::refresh_kpro();
});
Artisan::command('all_sync_kpro', function () {
    KproController::all_sync_kpro();
});
Artisan::command('selfi_kpro {regional} {witel} {periode}', function ($regional, $witel, $periode) {
    KproController::selfi_kpro($regional, $witel, $periode);
});
Artisan::command('provi_kpro {regional} {witel}', function ($regional, $witel) {
    KproController::provi_kpro($regional, $witel);
});
Artisan::command('total_pi {regional} {witel}', function ($regional, $witel) {
    KproController::total_pi($regional, $witel);
});
Artisan::command('pda_kpro {regional} {witel}', function ($regional, $witel) {
    KproController::pda_kpro($regional, $witel);
});
Artisan::command('ps_kpro {regional} {witel} {tahun} {bulan}', function ($regional, $witel, $tahun, $bulan) {
    KproController::ps_kpro($regional, $witel, $tahun, $bulan);
});
Artisan::command('pt2simple_kpro {regional} {witel}', function ($regional, $witel) {
    KproController::pt2simple_kpro($regional, $witel);
});
Artisan::command('byod_survey_kpro_tr6 {regional} {witel}', function ($regional, $witel) {
    KproController::byod_survey_kpro_tr6($regional, $witel);
});
Artisan::command('kpro_eval_unsc {regional} {witel}', function ($regional, $witel) {
    KproController::kpro_eval_unsc($regional, $witel);
});

// ut online
Artisan::command('login_utonline', function () {
    UtOnlineController::login_utonline();
});
Artisan::command('refresh_utonline', function () {
    UtOnlineController::refresh_utonline();
});
Artisan::command('full_utonline', function () {
    UtOnlineController::full_utonline();
});
Artisan::command('full_utonline_date {cookie} {date}', function ($cookie, $date) {
    UtOnlineController::full_utonline_date($cookie, $date);
});
Artisan::command('full_utonline_pages {cookie} {date} {x}', function ($cookie, $date, $x) {
    UtOnlineController::full_utonline_pages($cookie, $date, $x);
});
Artisan::command('material_utonline {startDate} {endDate}', function ($startDate, $endDate) {
    UtOnlineController::material_utonline($startDate, $endDate);
});

// starclick one
Artisan::command('login_sc_one {uname} {pass} {chatid}', function ($uname, $pass, $chatid) {
    StarclickOneController::login_sc_one($uname, $pass, $chatid);
});
Artisan::command('logout_sc_one', function () {
    StarclickOneController::logout_sc_one();
});
Artisan::command('refresh_sc_one', function () {
    StarclickOneController::refresh_sc_one();
});
Artisan::command('grabstarclickoneweekly {witel}', function ($witel) {
    StarclickOneController::grabstarclickoneweekly($witel);
});
Artisan::command('grabstarclickone_insert {witel} {datex} {x} {start} {cookies}', function ($witel, $datex, $x, $start, $cookies) {
    StarclickOneController::grabstarclickone_insert($witel, $datex, $x, $start, $cookies);
});

// starclick ncx
Artisan::command('login_sc {uname} {pass} {chatid}', function ($uname, $pass, $chatid) {
    StarclickController::login_sc($uname, $pass, $chatid);
});
Artisan::command('refresh_sc', function () {
    StarclickController::refresh_sc();
});
Artisan::command('logout_sc', function () {
    StarclickController::logout_sc();
});
Artisan::command('grabstarclick_pi {witel}', function ($witel) {
    StarclickController::grabstarclick_pi($witel);
});
Artisan::command('grabstarclick_pi_insert {witel} {datex} {x} {start} {cookies}', function ($witel, $datex, $x, $start, $cookies) {
    StarclickController::grabstarclick_pi_insert($witel, $datex, $x, $start, $cookies);
});
Artisan::command('grabstarclick {witel} {chatid} {datex}', function ($witel, $chatid, $datex) {
    StarclickController::grabstarclick($witel, $chatid, $datex);
});
Artisan::command('grabstarclickweekly {witel}', function ($witel) {
    StarclickController::grabstarclickweekly($witel);
});
Artisan::command('grabstarclick_insert {witel} {datex} {x} {start} {cookies}', function ($witel, $datex, $x, $start, $cookies) {
    StarclickController::grabstarclick_insert($witel, $datex, $x, $start, $cookies);
});
Artisan::command('grabstarclick_detail {witel}', function ($witel) {
    StarclickController::grabstarclick_detail($witel);
});
Artisan::command('grabstarclick_log_proccess {orderId} {type}', function ($orderId, $type) {
    StarclickController::grabstarclick_log_proccess($orderId, $type);
});
Artisan::command('grabstarclick_backend {witel}', function ($witel) {
    StarclickController::grabstarclick_backend($witel);
});

// prabac
Artisan::command('login_prabac {username} {password} {chatid}', function ($username, $password, $chatid) {
    PrabacController::login_prabac($username, $password, $chatid);
});
Artisan::command('refresh_prabac', function () {
    PrabacController::refresh_prabac();
});
Artisan::command('kpi_prov2023 {kode} {header} {witel} {periode}', function ($kode, $header, $witel, $periode) {
    PrabacController::kpi_prov2023($kode, $header, $witel, $periode);
});
Artisan::command('all_sync_prabac {witel}', function ($witel) {
    PrabacController::all_sync_prabac($witel);
});
Artisan::command('kpi_wsa_tsel {kode} {header} {regional} {witel} {startdate} {enddate}', function ($kode, $header, $regional, $witel, $startdate, $enddate) {
    PrabacController::kpi_wsa_tsel($kode, $header, $regional, $witel, $startdate, $enddate);
});
Artisan::command('cbd_psharian_indihome', function () {
    PrabacController::cbd_psharian_indihome();
});

// tactical pro
Artisan::command('login_tacticalpro {uname} {pass} {chatid}', function ($uname, $pass, $chatid) {
    TacticalProController::login_tacticalpro($uname, $pass, $chatid);
});
Artisan::command('refresh_tacticalpro', function () {
    TacticalProController::refresh_tacticalpro();
});
Artisan::command('workorderTactical', function () {
    TacticalProController::workorderTactical();
});
Artisan::command('pages_workorderTactical {startDate} {endDate} {x} {y}', function ($startDate, $endDate, $x, $y) {
    TacticalProController::pages_workorderTactical($startDate, $endDate, $x, $y);
});
Artisan::command('sendPickupOnline {witel}', function ($witel) {
    TacticalProController::sendPickupOnline($witel);
});

// dns warriors
Artisan::command('order_premium_dns {order} {kode}', function ($order, $kode) {
    DnsWarriorsController::order_premium_dns($order, $kode);
});

// nossa
Artisan::command('update_mxIdNossa {witel}', function ($witel) {
    NossaController::update_mxIdNossa($witel);
});
Artisan::command('nossa_order_today {witel}', function ($witel) {
    NossaController::nossa_order_today($witel);
});
Artisan::command('nossa_order {witel} {date}', function ($witel, $date) {
    NossaController::nossa_order($witel, $date);
});

// insera
Artisan::command('login_insera {is_username} {is_password}', function ($is_username, $is_password) {
    InseraController::login_insera($is_username, $is_password);
});
Artisan::command('refresh_insera', function () {
    InseraController::refresh_insera();
});
Artisan::command('ticket_list {witel}', function ($witel) {
    InseraController::ticket_list($witel);
});
Artisan::command('ticket_list_date {type} {witel} {date}', function ($type, $witel, $date) {
    InseraController::ticket_list_date($type, $witel, $date);
});
Artisan::command('ticket_list_repo_date {type} {witel} {date}', function ($type, $witel, $date) {
    InseraController::ticket_list_repo_date($type, $witel, $date);
});

// risma
Artisan::command('all_sync_risma', function () {
    RismaController::all_sync_risma();
});

// api
Artisan::command('cleansing_trash_order_kawan {witel}', function ($witel) {
    ApiController::cleansing_trash_order_kawan($witel);
});
