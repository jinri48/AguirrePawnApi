<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\RecordRequest;
use App\RecordResponse;

use DB;
use Exception;
use Log;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class ApiController extends Controller
{

    public function testConnect(Request $request)
    {

        $u = 'mbaguirre';
        $p = ')J4jVnt2';
        $uri = 'https://' . $u . ':' . $p . '@kaisercheck.com/listing/pub/index.php/api/kaiser/check/';

        // Create a client with a base URI
        // $client = new Client();
        $client = new Client([
            'headers' => ['KAISER-API-KEY' => "'memLdpB[B8r4!#*"],
            'base_uri' => $uri,

        ]);

        $search_name = $request;
        if (!isset($search_name)) {
            return "Please provide a valid name";
        }

        // Send a request to the Aguire api   
        try {
            $response = $client->request('GET', $search_name);
        } catch (\Exception $e) {
            $response = $e->getResponse();
            // $responseBodyAsString = $response->getBody()->getContents();          
        }

        return $response;
    }

    public function show($conn, Request $request)
    {

         // configure the connection 

         if ($conn == 1) {
            $conn = 'sqlsrv';
        } else if ($conn == 2) {
            $conn = 'sqlsrv2';
        } else {
            return response()->json([
                'success'   => false,
                'status'    => 404,
                'message'   => 'connection does not exist',

            ], 404);
        }

        if(!is_null($request->id)){
              // get the id and the keyword of the request 
            $record = RecordRequest::on($conn)->where('REQUESTID', '=', $request->id)->first();

            if (is_null($record)) {

                $status = 404;
                $headers = [];
                $body = json_encode([
                    'success'   => false,
                    'status'    => 404,
                    'message'      => 'Request ID not found'
                ]);
                $protocol = '1.1';
                $response = new Response($status, $headers, $body, $protocol);
                // $r = response()->json([
                //     'success'   => false,
                //     'status'    => 404,
                //     'message'=> 'Request ID not found'
                // ], 404);
    
                // dd($r->getData());
                return $response;
            }

            $keyword = trim($record->ACCOUNTNAME);

        }else if (!is_null($request->keyword)){
            
            $keyword = trim($request->keyword);

        }else{
            return response()->json([], 404);
        }
      

        /*
        * Connect to aguire external api
        */

        $u = 'mbaguirre';
        $p = ')J4jVnt2';
        $uri = 'https://' . $u . ':' . $p . '@kaisercheck.com/listing/pub/index.php/api/kaiser/check/';
        $headers = ['KAISER-API-KEY' => "'memLdpB[B8r4!#*"];

        // Create a client with a base URI
        $client = new Client([
            'headers' => $headers,
            'base_uri' => $uri,
        ]);

        // Send a request to Aguire api  
        try {
            $response = $client->request('GET', $keyword);
        } catch (ConnectException $e) {
            $response = $e->getMessage();
        } catch (ClientException $e) {
            $response  = $e->getResponse();
        } catch (\Exception $e) {
            $response = $e->getResponse();
        }

        return $response;
    }


    public function store($conn, $id)
    {

        // configure the connection 

        if ($conn == 1) {
            // $rq->setConnection('sqlsrv');
            $conn = 'sqlsrv';
        } else if ($conn == 2) {
            $conn = 'sqlsrv2';
        } else {
            return response()->json([
                'success'   => false,
                'status'    => 404,
                'message'   => 'connection does not exist',

            ], 404);
        }

        try {
            DB::beginTransaction();
            $rq = new RecordRequest();
            $rq->setConnection($conn);
            $record = $rq->find($id);

            if (is_null($record)) {
                return response()->json([
                    'success'   => false,
                    'status'    => 404,
                    'message' => 'Request ID not found'
                ], 404);
            }

            /**
             * Connect to aguire external api
             **/

            $u = 'mbaguirre';
            $p = ')J4jVnt2';
            // $uri = 'https://' . $u . ':' . $p . '@kaisercheck.com/listing/pub/index.php/api/kaiser/check/';
            $uri = 'https://' . $u . ':' . $p . '@kaisercheck.com/listingv2/pub/index.php/api/kaiser/dtag/0/';
            $headers = ['KAISER-API-KEY' => "'memLdpB[B8r4!#*"];

            // Create a client with a base URI
            $client = new Client([
                'headers' => $headers,
                'base_uri' => $uri,
            ]);


            $keyword = trim($record->ACCOUNTNAME);
            if (!isset($keyword) || is_null($keyword)) {
                return "Please provide a valid name";
            }

            $response = $client->request('GET', $keyword);

            if ($response->getStatusCode() == 200) {

                $resData = $response->getBody();

                // transform the json to object
                $data = json_decode($resData);

                /*
                 * Manipulate the data to save to DB 
                */
                $full_name = '';
                $ctr = 0;
                $seq = 0;
                $temp = []; //checker 
                
                $last_resp = RecordResponse::on($conn)->orderby('RESPONSEID', 'desc')->first();
                $last_resp_id = 0;
               
                
                if (!is_null($last_resp)) {
                    $last_resp_id = $last_resp->RESPONSEID;
                    $ctr = $last_resp->LINENO;
                }

                
               
                // save each detail
                foreach ($data as $key => $value) {

                    $last_resp_id = $last_resp_id + 1;
                    
                    $_res = new RecordResponse();
                    $_res = $_res->setConnection($conn);


                    $_res->RESPONSEID = $last_resp_id;
                    $_res->REQUESTID  = $record->REQUESTID;
                    $_res->FIRSTNAME  = $value->firstname;
                    $_res->MIDDLENAME = $value->midname;
                    $_res->LASTNAME   = $value->lastname;
                    $_res->SUFFIX     = $value->suffix;
                    $_res->DATETIME   = now();
                    $_res->POSITION   = $value->details[0]->position;
                    $_res->AREA       = $value->details[0]->jurisdiction;
                    $_res->TERMSTART  = $value->details[0]->hired;
                    $_res->TERMEND    = $value->details[0]->resigned;
                    $_res->WITHHIT    = 1; // has a case
                    $_res->DECEASED   = $value->deceased;

                    if ($full_name != $value->firstname . ' ' . $value->lastname) {
                        $ctr++;
                        $seq = 1;
                    }

                    $_res->SEQUENCEID = $seq;
                    $_res->LINENO = $ctr;
                    $seq++;

                    $_res->save();

                    $full_name =  $value->firstname . ' ' . $value->lastname;
                    array_push($temp, $_res);

                    // update the status to with hit 
                    // meaning it has criminal case or administrative case
                    $record->STATUS = 2;
                    $record->save();

                }
            }

            DB::commit();
            return response()->json([
                'success'   => true,
                'status'    => 200,
                'message'   => 'Record/s found',
                'data'      => $data
            ]);

        } catch (ConnectException $e) {

            DB::rollback();
            $this->setStatus($conn, $id, 3); // timeout
            $response = $e->getMessage();
            return $response;

        } catch (ClientException $e) {
            DB::rollback();
            $response = $e->getResponse();
            if (
                $response->getStatusCode() == 404
                && json_decode((string) $response->getBody())->message == 'record could not be found'
            ) {
                $this->setStatus($conn, $id, 1); // no record found == no hit
                return response()->json([
                    'success'   => false,
                    'status'    => 404,
                    'message' => 'No record found for '.$record->ACCOUNTNAME
                ], 404);
            }

            $this->setStatus($conn, $id, 3); // timeout
            Log::error($e->getMessage());
            return $response;
        } catch (ServerException $e) {
            DB::rollback();
            $this->setStatus($conn, $id, 4); // external api
            Log::error($e->getMessage());
            return $e->getMessage();

        } catch (\Exception $e) {
            DB::rollback();
            $this->setStatus($conn, $id, 5); // api
            Log::error($e->getMessage());
            return $e->getMessage();
        }
    }

    public function setStatus($conn, $rec_id, $status)
    {
        try {

            DB::beginTransaction();
            $record = new RecordRequest();
            $record->setConnection($conn);
            $record = $record->findOrFail($rec_id);
            $record->STATUS = $status;
            $record->save();

            DB::commit();
            // return $record;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }
}
