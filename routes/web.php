<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::view('/', 'index');

Route::group(["prefix"=>"/ecommerce-solidaire"], function() {
    Route::get('', 'Pages\EcommercesolidaireController@index')->name('pages.ecommercesolidaire.index');
    Route::get('/mentions-legales.html', 'Pages\EcommercesolidaireController@legals')->name('pages.ecommercesolidaire.legals');
    Route::get('/affiche.html', 'Pages\EcommercesolidaireController@print')->name('pages.ecommercesolidaire.print');
    Route::get('/annuaire.html', 'Pages\EcommercesolidaireController@inscriptions')->name('pages.ecommercesolidaire.inscriptions');
    Route::post('/inscription.html', 'Pages\EcommercesolidaireController@inscription')->name('pages.ecommercesolidaire.mail');
});

Auth::routes();

Route::get('/admin', 'AdminController@index')->name('admin');