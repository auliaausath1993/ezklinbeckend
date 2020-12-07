<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'auth', 'namespace' => 'Api'], function() {
    Route::post('login', 'Auth\AuthController@login');
    Route::post('logout', 'Auth\AuthController@logout');
    Route::post('register', 'Auth\AuthController@register');
    Route::post('otp-confirmation', 'Auth\AuthController@otpConfirmation');
});

Route::group(['middleware' => ['jwt.verify'], 'namespace' => 'Api'], function() {
    Route::get('profile', 'UserController@index');
    Route::post('edit_profile', 'UserController@edit_profile');
    Route::post('save_bookservice', 'OrderController@save_bookservice');
    Route::post('layanan_laundry', 'OrderController@layanan_laundry');
    Route::post('layanan_service', 'OrderController@layanan_service');
    Route::post('order_pending', 'OrderController@orders');    
	Route::post('order_reserved', 'OrderController@orders');	
	Route::post('order_going', 'OrderController@orders');	
	Route::post('order_completed', 'OrderController@orders');
    Route::post('order_detail', 'OrderController@order_detail');
	Route::post('syaratketentuan', 'UserController@syaratketentuan');
    Route::post('privasi', 'UserController@privasi');
    Route::post('aboutus', 'UserController@aboutus');
	Route::post('getbooking', 'UserController@getbooking');
});


