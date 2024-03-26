<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get("recentList", ApiController::class . "@recentList");

Route::post("login", ApiController::class . "@login");
Route::post("register", ApiController::class . "@register");
Route::post("send-code", ApiController::class . "@sendCode");
Route::post("restore-password", ApiController::class . "@restorePassword");

Route::middleware('auth:sanctum')->group(function () {
    Route::post("logout", ApiController::class . "@logout");
    Route::post("home", ApiController::class . "@home");
    Route::post("search", ApiController::class . "@search");
    Route::post("anime", ApiController::class . "@anime");
    Route::post("animes", ApiController::class . "@animes");
    Route::post("calendar", ApiController::class . "@calendar");
    Route::post("episodes", ApiController::class . "@episodes");
    Route::post("players", ApiController::class . "@players");
    Route::post("lists-animes", ApiController::class . "@listsAnimes");
    Route::post("update", ApiController::class . "@updateProfile");

    Route::post("add-favorite", ApiController::class . "@addFavorite");
    Route::post("delete-favorite", ApiController::class . "@deleteFavorite");
    Route::post("add-view", ApiController::class . "@addView");
    Route::post("delete-view", ApiController::class . "@deleteView");
    Route::post("add-watching", ApiController::class . "@addWatching");
    Route::post("delete-watching", ApiController::class . "@deleteWatching");
});

