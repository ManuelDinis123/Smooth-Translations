<?php

use Illuminate\Support\Facades\Route;
use Manueldinis\Smoothtranslations\Translator;

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

Route::get('/', function () {
    $translator = new Translator([
        "host" => "localhost",
        "database" => "smooth_translations_test",
        "username" => "root",
        "password" => "",
    ], "pt");

    // $translator->add_language("Russian");
    // $translator->add_text("new table");
    // $translator->add_translation("New Table", 1, 3);
    // $translator->add_translation("Nova Tabela", 2, 3);


    return view('index')->with("translator", $translator);
});
