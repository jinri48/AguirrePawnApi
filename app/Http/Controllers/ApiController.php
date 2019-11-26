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

    public function testConnect()
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

        $search_name = "roque";
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


    public function show($id)
    {

        // get the id and the keyword of the request 

        $record = RecordRequest::where('REQUESTID', '=', $id)->first();


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

        $keyword = trim($record->ACCOUNTNAME);
        // $keyword  = "Harry Roque";
        if (!isset($keyword)) {
            return "Please provide a valid name";
        }

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


    public function store($id)
    {
        $request_results = $this->show($id);

        if ($request_results->getStatusCode() != 200) {
            
            if ($request_results->getStatusCode() == 404) {
                $msg = json_decode((string) $request_results->getBody())->message; // error message in aguirre api client
                if ($msg == 'record could not be found') {
                    $record = RecordRequest::where('REQUESTID', $id)
                        ->first()
                        ->update([
                            'STATUS' => 1, // record not found
                        ]);

                    return response()->json([
                        'success'   => true,
                        'status'    => 200,
                        'message'   => 'No record found'
                    ]);
                }

                // dd($record);
                return response()->json([
                    'success'   => false,
                    'status'    => 404,
                    'message'   => $msg
                ]);
            } else if ($request_results->getStatusCode() == 500) {

                $record = RecordRequest::where('REQUESTID', $id)
                    ->first()
                    ->update([
                        'STATUS' => 4, // Server error in the aguirre api client 
                    ]);

                return response()->json([
                    'success'   => false,
                    'status'    => 500,
                    'message'   => $msg
                ]);
            } else {
                return json_decode((string) $request_results);
            }
        }


        try {

            DB::beginTransaction();

            $resData = $request_results->getBody();

            // transform the json to object
            $data = json_decode($resData);

            //get the record of the request to get the name and status 
            $record = RecordRequest::where('REQUESTID', '=', $id)->first();

            /*
            * Manipulate the data to save to DB 
            */
            $full_name = '';
            $ctr = 0;
            $seq = 0;
            $temp = []; //checker 

            $last_resp = RecordResponse::orderby('RESPONSEID', 'desc')->first();
            $last_resp_id = 0;

            if (!is_null($last_resp)) {
                $last_resp_id = $last_resp->RESPONSEID;
            }

            // save each detail
            foreach ($data as $key => $value) {

                $last_resp_id = $last_resp_id + 1;
                $_res = new RecordResponse();

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
            }

            // update the status to with hit 
            // meaning it has criminal case or administrative case

            $record->STATUS = 2;
            $record->save();

            DB::commit();
            return response()->json([
                'success'   => true,
                'status'    => 200,
                'message'   => 'Record/s found',
                'data'      => $data
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }




    public function store2($conn, $id)
    {
        // db 2
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
            $uri = 'https://' . $u . ':' . $p . '@kaisercheck.com/listing/pub/index.php/api/kaiser/check/';
            $headers = ['KAISER-API-KEY' => "'memLdpB[B8r4!#*"];

            // Create a client with a base URI
            $client = new Client([
                'headers' => $headers,
                'base_uri' => $uri,
            ]);


            $keyword = trim($record->ACCOUNTNAME);
            if (!isset($keyword)) {
                return "Please provide a valid name";
            }

            $response = $client->request('GET', $keyword);

            if ($response->getStatusCode() != 200) {
                $msg = json_decode((string) $response->getBody())->message;

                if ($response->getStatusCode() == 404) {
                    if ($msg == 'record could not be found') {
                        $record->STATUS = 1; // record not found
                        $record->save();
                        return response()->json([
                            'success'   => true,
                            'status'    => 200,
                            'message'   => 'No record found'
                        ]);
                    }

                    return response()->json([
                        'success'   => false,
                        'status'    => 404,
                        'message'   => $msg
                    ]);
                } else if ($response->getStatusCode() == 500) {
                    $record->STATUS = 4; // Server error in the aguirre api client
                    $record->save();

                    return response()->json([
                        'success'   => false,
                        'status'    => 500,
                        'message'   => $msg
                    ]);
                } else {
                    return json_decode((string) $response);
                }
            }

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


            $last_resp = RecordResponse::orderby('RESPONSEID', 'desc')->first();
            $last_resp_id = 0;

            if (!is_null($last_resp)) {
                $last_resp_id = $last_resp->RESPONSEID;
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
            }

            // update the status to with hit 
            // meaning it has criminal case or administrative case

            $record->STATUS = 2;
            $record->save();

            DB::commit();
            return response()->json([
                'success'   => true,
                'status'    => 200,
                'message'   => 'Record/s found',
                'data'      => $data
            ]);
        } catch (ConnectException $e) {
            DB::rollback();
            $response = $e->getMessage();
            return $response;
        } catch (ClientException $e) {
            DB::rollback();
            $response  = $e->getResponse();
            return $response;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            // abort(500);
            return $e->getMessage();
        }
    }

    


}
