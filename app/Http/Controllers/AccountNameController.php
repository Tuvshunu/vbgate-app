<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Response;

class AccountNameController extends Controller {

//
    public function getAccountLocal(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

        $this->fileLog("request", $request->acntno);

        $result = \App\Models\DatabaseHelper::instance()->getAccountLocal($request->acntno);

        foreach ($result as $item) {
            $this->fileLogResponse("response", $request->acntno, $item->name);
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'acntno' => $item->acnt_code,
                        'name' => $item->name,
                        'curcode' => $item->cur_code,
                        'prodtype' => $item->prod_type
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'empty'
        ]);
    }

    public function getCustAddress(Request $request) {
        $validate = Validator::make($request->all(), [
                    'custcode' => 'required|numeric|digits:10'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

        $this->fileLog("request", $request->custcode);

        $result = \App\Models\DatabaseHelper::instance()->getAddress($request->custcode);

        foreach ($result as $item) {
            $this->fileLogResponse("response", $request->custcode, "address_en: " . $item->address_en . ", address_mn: " . $item->address_en . ", phone: " . $item->phone);
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'custcode' => $item->cust_code,
                        'address_en' => $item->address_en,
                        'address_mn' => $item->address_en,
                        'phone' => $item->phone
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'empty'
        ]);
    }

    public function insertStatementLegal(Request $request) {
        $validate = Validator::make($request->all(), [
                    'acntno' => 'required',
                    'email' => 'required',
                    'language' => 'required',
					'type' => 'required',
                    'purpose' => 'required'
        ]);

		$purpose = $new = str_replace(' ', '%20', $request->purpose);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'input parameter error'
            ]);
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://172.16.200.7:91/RECOM/sendRecomVB_new.php?accNumber=' . $request->acntno . '&emails=' . $request->email . '&language=' . $request->language .'&type=' . $request->type . '&purpose=' . $purpose . '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode == 200) {

            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'path' => $response,
            ]);
        }

        return response()->json([
                    'code' => 2,
                    'msg' => 'error',
        ]);
    }

    public function getTicketDeal(Request $request) {

        $validate = Validator::make($request->all(), [
                    'custcode' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

        //$this->fileLog("request", $request->acntno);

        $result = \App\Models\DatabaseHelper::instance()->getTicketDeal($request->custcode);

        if (sizeof($result) > 0) {
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'list' => $result
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'empty'
        ]);
    }

	public function getCardOrderInfo(Request $request) {

        $validate = Validator::make($request->all(), [
                    'card_type' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

        $this->fileLog("request", $request->card_type);

        $result = \App\Models\DatabaseHelper::instance()->getCardInfo($request->card_type);

        if (sizeof($result) > 0) {
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
						'cardprefix' => $result[0]->cardprefix,
                        'list' => $result,
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'Мэдээлэл олдсонгүй'
        ]);
    }

	public function getCardList(Request $request) {

        $validate = Validator::make($request->all(), [
                    'custcode' => 'required',
                    'pan' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

        //$this->fileLog("request", $request->acntno);

        $result = \App\Models\DatabaseHelper::instance()->getCardList($request->custcode, $request->pan);

        if (sizeof($result) > 0) {
            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'list' => $result
            ]);
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'Мэдээлэл олдсонгүй'
        ]);
    }

	public function getCardDebitStat(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required',
                    'startdate' => 'required',
                    'enddate' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

        //$this->fileLog("request", $request->acntno);

        $result = \App\Models\DatabaseHelper::instance()->getCardDebitStat($request->acntno, $request->startdate, $request->enddate);

        if (sizeof($result) > 0) {
            /*return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'list' => $result
            ], JSON_NUMERIC_CHECK); */

			return Response::json( [  'code' => 0,
                        'msg' => 'success',
                        'list' => $result ], 200, [], JSON_NUMERIC_CHECK );
        }



        return response()->json([
                    'code' => 1,
                    'msg' => 'empty'
        ]);
    }

	public function getCardDebitStatDetail(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required',
                    'startdate' => 'required',
                    'enddate' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

        //$this->fileLog("request", $request->acntno);

        $result = \App\Models\DatabaseHelper::instance()->getPfmDebitStatDetail($request->acntno, $request->startdate, $request->enddate);

        if (sizeof($result) > 0) {
			return Response::json( [  'code' => 0,
                        'msg' => 'success',
                        'list' => $result ], 200, [], JSON_NUMERIC_CHECK );
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'empty'
        ]);
    }

	public function getLnAvailableBal(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

       $this->file2Log("request_getLnAvailableBal", $request->all());

        $result = \App\Models\DatabaseHelper::instance()->getLnAvailableBal($request->acntno);

		/*foreach ($result as $item ) {

			$arr = array();

			$arr["acnt_code"] = $item->acnt_code;
			$arr["ln_available_amount"] = str_replace(",", "", number_format ( $item->ln_available_amount,2));

			return Response::json( [  'code' => 0,
                        'msg' => 'success',
                        'list' => $arr ], 200, []);
		} */

        if (sizeof($result) > 0) {

			$this->fileLog2Response("response_getLnAvailableBal", $result);

			return Response::json( [  'code' => 0,
                        'msg' => 'success',
                        'list' => $result ], 200, [], JSON_NUMERIC_CHECK );
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'empty'
        ]);
    }

	public function getIntPayCalc(Request $request) {

        $validate = Validator::make($request->all(), [
                    'acntno' => 'required',
                    'approve_amount' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'error'
            ]);
        }

        $this->file2Log("request_getIntPayCalc", $request->all());

        $result = \App\Models\DatabaseHelper::instance()->getIntPayCalc($request->acntno, $request->approve_amount);


        if (sizeof($result) > 0) {

			$this->fileLog2Response("response_getIntPayCalc", $result);

			if($result[0]->int_amount > 0) {

			return Response::json( [  'code' => 0,
                        'msg' => 'success',
                        'list' => $result ], 200, [], JSON_NUMERIC_CHECK );
			} else {
				 return response()->json([
                    'code' => 1,
                    'msg' => 'Төлөлтийг хасах дүнгээр үүсгэх боломжгүй!'
        ]);
			}
        }

        return response()->json([
                    'code' => 1,
                    'msg' => 'empty'
        ]);
    }

    public function fileLog($type, $msg) {
        $logstr = ['acntno' => $msg];
        $log = new Logger($type);
        $log->pushHandler(new StreamHandler('logs/vbname/' . date('Ymd') . '.log', Logger::INFO));
        $log->info($type, $logstr);
    }

    public function fileLogResponse($type, $acntno, $msg) {
        $logstr = ['acntno' => $acntno, 'result' => $msg];
        $log = new Logger($type);
        $log->pushHandler(new StreamHandler('logs/vbname/' . date('Ymd') . '.log', Logger::INFO));
        $log->info($type, $logstr);
    }

	 public function file2Log($type, $msg) {
        $log = new Logger("Request");
        $log->pushHandler(new StreamHandler('logs/vbgate/' . date('Ymd') . '.log', Logger::INFO));
        $log->info($type, $msg);
    }

	public function fileLog2Response($type,$msg) {
        $log = new Logger("Response");
        $log->pushHandler(new StreamHandler('logs/vbgate/' . date('Ymd') . '.log', Logger::INFO));
        $log->info($type, $msg);
    }

}
