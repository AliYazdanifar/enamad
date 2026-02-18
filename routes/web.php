<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/domain',[\App\Http\Controllers\DomainEmailController::class,'index']);
Route::get('/domain_detail',[\App\Http\Controllers\DomainEmailWithDetailsController::class,'index']);
Route::get('/openssl',[\App\Http\Controllers\SslInfoController::class,'index']);

Route::get('/',function(){
    echo "hi";
});

Route::get('/whois',[\App\Http\Controllers\WhoisDomainController::class,'index']);

Route::get('/ir/whois',[\App\Http\Controllers\WhoisDomainController::class,'whoisIR']);

Route::get('/enamad-expires',[\App\Http\Controllers\EnamadExpireController::class,'index']);
