<?php


header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, Accept, X-Auth-Token, Origin, Authorization, User-Agent');

use Illuminate\Http\Request;
use App\Library\Helper;



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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::post('/records/show',        'ApiController@testConnect'); // test connection
Route::post('/records/{id}',        'ApiController@show'); // 
Route::post('/records/{id}/create', 'ApiController@store'); //pull the record then store 

Route::any('/records/{id}',        'ApiController@show');
Route::any('/records/{id}/create', 'ApiController@store'); //pull the record then store 

Route::post('/records/{id}/store', 'ApiController@store2');