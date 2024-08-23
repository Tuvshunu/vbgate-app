<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Monolog\Logger;
use Illuminate\Support\Facades\Validator;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use DB;

class SmsGateController extends Controller {

    //

    public function checkUserAuhth(Request $request) {
        $token = request()->bearerToken();
        if ($token == "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJpbmZpbml0ZSBzb2x1dGlvbnMiLCJpYXQiOjE2MzQ4OTU2NzMsImV4cCI6MTY5Nzk2NzY3MywiYXVkIjoidHJhbnMiLCJzdWIiOiJ0cmFuc2JhbmsubW4ifQ.WE_fassjwHshbEqQS404TIDIZaL_mWFiGmpgRR99QXY") {

            $this->fileLog("request", $request->mobile, $request->text);

            $result_json = $this->SmsGate($request->mobile, $request->text, "VB_SMS_TEST");
           
            
            return $result_json;
        }
		
		if ($token == "eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJUUkFOU0JBTksiLCJuYW1lIjoiRVJYRVMiLCJpYXQiOjE1MTYyMzkwMjJ9.zNKZNzfhmbTaPqC7MCkNGU_aZnqbvp1FIudx5Xl2rqbed2hD0JYXysKRNji3c0p9S-t8cuCzpOxkqfjurW6Www") {

            $this->fileLog("request", $request->mobile, $request->text);

            $result_json = $this->SmsGate($request->mobile, $request->text, "ERXES");
           
            
            return $result_json;
        }
    }
	
	

    public function SmsGate($mobile, $text, $sourcename) {

        $client = new \SoapClient(
                "http://172.16.200.7:81/SmsService.svc?WSDL",
                [
            'soapVersion' => SOAP_1_2,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'allow_self_signed' => true
                ],
                'http' => [
                    'header' => "Content-Type: application/xml;charset=\"utf-8\""
                ]
            ])
                ]
        );
        $soapParam = [
            "Name" => $sourcename,
            "PhoneNo" => $mobile,
            "TextBody" => $text
        ];

        $result = $client->SmsSent($soapParam);
        
        $this->fileLog("response", $mobile, $result->SmsSentResult->ErrDesc);

        $json_data = json_encode((array) $result->SmsSentResult);

        return $json_data;
    }
	
	public function checkUserEmailAuhth(Request $request) {
        $token = request()->bearerToken();
        if ($token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJpbmZpbml0ZSBzb2x1dGlvbnMiLCJpYXQiOjE2MzQ4OTU2NzMsImV4cCI6MTY5Nzk2NzY3MywiYXVkIjoidHJhbnMiLCJzdWIiOiJ0cmFuc2JhbmsubW4ifQ.WE_fassjwHshbEqQS404TIDIZaL_mWFiGmpgRR99QXY") {

            $this->fileLog("request", $request->email, $request->subject, $request->body);

            $result_json = $this->EmailGate($request->email, $request->subject, $request->body);


            return $result_json;
        }
    }

    public function EmailGate($email, $subject, $emailbody) {

        $client = new \SoapClient(
                "http://172.16.200.7:85/SendMail.svc?wsdl",
                [
            'soapVersion' => SOAP_1_2,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'allow_self_signed' => true
                ],
                'http' => [
                    'header' => "Content-Type: application/xml;charset=\"utf-8\""
                ]
            ])
                ]
        );
        $soapParam = [
            "email" => $email,
            "bccMail" => "",
            "appid" => "VB_TEST_EMAIL",
            "subject" => $subject,
            "bodytext" => $emailbody,
        ];

        $result = $client->SendMailBody($soapParam);

        if ($result->SendMailBodyResult) {
            return response()->json([
                        'email' => $email,
                        'status' => 'true',
            ]);
        } else {
            return response()->json([
                        'email' => $email,
                        'status' => 'false',
            ]);
        }

        //$this->fileLog("response", $mobile, $result->SendMailBodyResult);
        //$json_data = json_encode((array) $result->SmsSentResult);
    }

    public function fileLog($type, $mobile, $text) {
        $logstr = ['mobile' => $mobile,
            'msg' => $text];
        $log = new Logger($type);
        $log->pushHandler(new StreamHandler('logs/vbsms/' . date('Ymd') . '.log', Logger::INFO));
        $log->info($type, $logstr);
    }
	
	public function smsList(Request $request) {

        $validate = Validator::make($request->all(), [
                    'custcode' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'Алдаа гарлаа',
                        'msg2' => 'Error'
            ]);
        }

        //$this->fileLog("request", $request->acntno);

        $result = \App\Models\DatabaseHelper::instance()->smsList($request->custcode);

        if (sizeof($result) > 0) {
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'list' => $result
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'Та одоогоор мессеж мэдээ үйлчилгээнд бүртгэлгүй байна',
					'msg2' => 'Not registered'
        ]);
    }

    public function smsDetail(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'Алдаа гарлаа',
						'msg2' => 'Error'
            ]);
        }

        //$this->fileLog("request", $request->acntno);

        $result = \App\Models\DatabaseHelper::instance()->smsDetail($request->acntno);

        if (sizeof($result) > 0) {
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'list' => $result
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'Та одоогоор мессеж мэдээ үйлчилгээнд бүртгэлгүй байна'
        ]);
    }

    public function createSms(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required',
                    'phoneno' => 'required',
                    'txn_limit' => 'required',
                    'txn_type' => 'required',
                    'cust_lang' => 'required',
                    'fee_acntno' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'Мэдээлэл дутуу байна',
						'msg2' => 'Information is missing'
            ]);
        }

        $is_credit = "";
        $is_debit = "";
        $is_credit_limit = "";
        $is_debit_limit = "";

        switch ($request->txn_type) {
            case "C":
                $is_credit = "1";
                $is_credit_limit = $request->txn_limit;
                break;
            case "D":
                $is_debit = "1";
                $is_debit_limit = $request->txn_limit;
                break;
            case "CD":
                $is_debit = "1";
                $is_debit_limit = $request->txn_limit;
                $is_credit = "1";
                $is_credit_limit = $request->txn_limit;
                break;
        }

        //$this->fileLog("request", $request->acntno);

        $insertData = array(
            "acntno" => $request->acntno,
            "is_sms" => "1",
            "phoneno" => $request->phoneno,
            "status" => "A",
            "insert_date" => date('Y-m-d H:i:s'),
            "insert_empid" => "60",
            "is_credit" => $is_debit,
            "is_debit" => $is_credit,
            "credit_limit" => $is_credit_limit,
            "debit_limit" => $is_debit_limit,
            "cust_lang" => $request->cust_lang,
            "fee_account" => $request->fee_acntno
        );
        try {
            $result = DB::table("eoffice.sms_info_account")->insert($insertData);
            // all good
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // something went wrong
            return response()->json([
                        'code' => 1,
                        'msg' => "Мэдээлэл бүртгэгдсэн байна!",
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'Алдаа гарлаа',
					'msg2' => 'Error'
        ]);
    }

    public function updateSms(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required',
                    'phoneno' => 'required',
                    'txn_limit' => 'required',
                    'txn_type' => 'required',
                    'cust_lang' => 'required',
                    'fee_acntno' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'Мэдээлэл дутуу байна',
                        'msg2' => 'Information is missing'
            ]);
        }

        $is_credit = "";
        $is_debit = "";
        $is_credit_limit = "";
        $is_debit_limit = "";

        switch ($request->txn_type) {
            case "C":
                $is_credit = "1";
                $is_credit_limit = $request->txn_limit;
                break;
            case "D":
                $is_debit = "1";
                $is_debit_limit = $request->txn_limit;
                break;
            case "CD":
                $is_debit = "1";
                $is_debit_limit = $request->txn_limit;
                $is_credit = "1";
                $is_credit_limit = $request->txn_limit;
                break;
        }

        //$this->fileLog("request", $request->acntno);

        $updateData = array(
            "phoneno" => $request->phoneno,
            "is_credit" => $is_debit,
            "is_debit" => $is_credit,
            "credit_limit" => $is_credit_limit,
            "debit_limit" => $is_debit_limit,
            "cust_lang" => $request->cust_lang,
            "fee_account" => $request->fee_acntno
        );
        try {
            $result = DB::table("eoffice.sms_info_account")->where("acntno", "=" , $request->acntno)->update($updateData);
            // all good
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // something went wrong
            return response()->json([
                        'code' => 1,
                        'msg' => $e,
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'Алдаа гарлаа'
        ]);
    }

    public function updateStatusSms(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required',
                    'status' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'Мэдээлэл дутуу байна',
						'msg2' => 'Information is missing'
            ]);
        }

        //$this->fileLog("request", $request->acntno);

        $updateData = array(
            "status" => $request->status,
        );
        try {
            $result = DB::table("eoffice.sms_info_account")->where("acntno", "=",$request->acntno)->update($updateData);
            // all good
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // something went wrong
            return response()->json([
                        'code' => 1,
                        'msg' => $e,
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'Алдаа гарлаа'
        ]);
    }

}
