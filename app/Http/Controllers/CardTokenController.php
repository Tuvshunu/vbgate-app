<?php

namespace App\Http\Controllers;

use App\Models\ActivationCode;
use App\Models\CardEligibility;
use App\Models\CardProvisioning;
use App\Models\MetaData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class CardTokenController extends Controller {

//
    public function checkCardEligibility(Request $request) {

        $this->file2Log("request_checkCardEligibility", $request->all());

        $response_array = [];

        try {
            $validate = $request->validate([
                'walletId' => 'required|string',
                'correlationId' => 'required|string',
                'card.pan' => 'required|string',
                'card.cardholderName' => 'required|string',
                'card.cvv2' => 'required|string',
                'card.expiryDate.month' => 'required|string',
                'card.expiryDate.year' => 'required|string',
            ]);

        } catch (ValidationException $e) {
            // Handle validation errors
            $response_array = ["result" => "FALIED", "msg" => $e->getMessage()];
            $this->fileLog2Response("response_checkCardEligibility", $response_array);
            return response()->json([$response_array], 422);
        }

        $eligibility = new CardEligibility();
        $eligibility->create_date = Carbon::now();
        $eligibility->correlationId = $validate["correlationId"];
        $eligibility->walletId = $validate["walletId"];
        $eligibility->pan = $validate["card"]["pan"];
        $eligibility->cardholdername = $validate["card"]["cardholderName"];
        $eligibility->cvv2 = $validate["card"]["cvv2"];
        $eligibility->expiry_month = $validate["card"]["expiryDate"]["month"];
        $eligibility->expiry_year = $validate["card"]["expiryDate"]["year"];
        $eligibility->save();


        $result = \App\Models\DatabaseHelper::instance()->checkCardEligibility($validate["card"]["pan"], $validate["card"]["expiryDate"]["year"], $validate["card"]["expiryDate"]["month"]);

        if (sizeof($result) > 0) {

            $response_array  = [
                "cardMetadataAndArtInfo" => [
                      "profileMetaTag" => "gree8-eaf5-4060-81a1-ff78d15e",
                      "cardMetaDataInfo" => [
                         "cardName" => $result[0]->embossname,
                         "foregroundRGB" => "rgb(123,72,135)",
                         "backgroundRGB" => "rgb(123,111,176)",
                         "description" => "199999 bank card"
                      ],
                      "cardArtInfo" => [
                            "artReferenceId" => [
                               "Art111",
                               "Art112"
                            ],
                            "tnCReferenceId" => "TnC003",
                            "tnCURL" => "https://transbank.mn/citizens/payment-card"
                         ],
                      "issuerContactInformation" => [
                                  "issuerBankName" => "TRANSBANK",
                                  "phoneNumber" => "18009999",
                                  "email" => "info@transbank.mn",
                                  "webSiteURL" => "https://www.transbank.mn/"
                               ]
                   ]
             ];

            $this->fileLog2Response("response_checkCardEligibility", $response_array);
            $eligibility->response_status = 0;
            $eligibility->save();

            return response()->json( $response_array, 200, [], JSON_NUMERIC_CHECK );
        }
        else{
            $response_array = ['code' => 1,'msg' => 'empty'];
            $eligibility->response_status = 1;
            $eligibility->save();
            $this->fileLog2Response("response_checkCardEligibility", $response_array);
        }

        return response()->json($response_array);
    }
    public function approveProvisioning(Request $request) {

        $this->file2Log("request_approveProvisioning", $request->all());

        $response_array = [];

        try {
            $validate = $request->validate([
                'correlationId' => 'required|string',
                'walletId' => 'required|string',
                'card.pan' => 'required|string',
                'card.cardholderName' => 'required|string',
                'card.cvv2' => 'required|string',
                'card.expiryDate.month' => 'required|string',
                'card.expiryDate.year' => 'required|string',
            ]);

        } catch (ValidationException $e) {
            // Handle validation errors
            $response_array = ["result" => "FALIED", "msg" => $e->getMessage()];
            $this->fileLog2Response("response_approveProvisioning", $response_array);
            return response()->json([$response_array], 422);
        }

        $provisioning = new CardProvisioning();
        $provisioning->create_date = Carbon::now();
        $provisioning->correlationId = $validate["correlationId"];
        $provisioning->walletId = $validate["walletId"];
        $provisioning->pan = $validate["card"]["pan"];
        $provisioning->cardholdername = $validate["card"]["cardholderName"];
        $provisioning->cvv2 = $validate["card"]["cvv2"];
        $provisioning->expiry_month = $validate["card"]["expiryDate"]["month"];
        $provisioning->expiry_year = $validate["card"]["expiryDate"]["year"];
        $provisioning->save();

        $eligibility = CardEligibility::where('correlationId', $validate["correlationId"])->orderBy('id', 'DESC')->first();

        if(!isset($eligibility)){
            $response_array = ['code' => 2,'msg' => 'correlationId not exists'];
            $this->fileLog2Response("response_approveProvisioning", $response_array);
            $provisioning->response_status = 2;
            $provisioning->save();
            return response()->json($response_array);
        }


        $result = \App\Models\DatabaseHelper::instance()->approveProvisioning($validate["card"]["pan"], $validate["card"]["expiryDate"]["year"], $validate["card"]["expiryDate"]["month"]);

        if (sizeof($result) > 0) {

            $response_array  = [
                "decision" => "STEPUP",
                "stepUpMethodTypes" =>["SMS"],
                "customerDetails" => [
                    "titleCode" => "01",
                    "familyName"=> $result[0]->familyname,
                    "secondFamilyName"=> $result[0]->secondfamilyname,
                    "firstName"=> $result[0]->firstname,
                    "secondFirstName"=> $result[0]->secondfirstname,
                    "maidenName"=> $result[0]->maidenname,
                    "phoneType"=> "1",
                    "phoneNumber"=> $result[0]->phone,
                    "email1"=> $result[0]->email,
                    "email2"=> ""
                   ]
             ];
             $provisioning->response_status = 0;
             $provisioning->save();

            $this->fileLog2Response("response_approveProvisioning", $response_array);

            return response()->json( $response_array, 200, [], JSON_NUMERIC_CHECK );
        }
        else{
            $response_array = ['code' => 1,'msg' => 'empty'];
            $this->fileLog2Response("response_approveProvisioning", $response_array);
        }

        return response()->json($response_array);
    }

    public function deliverActivationCode(Request $request) {

        $this->file2Log("request_deliverActivationCode", $request->all());

        $response_array = [];

        try {
            $validate = $request->validate([
                'correlationId' => 'required|string',
                'walletId' => 'required|string',
                'tokenReferenceId' => 'required|string',
                'panReferenceId' => 'required|string',
                'deviceId' => 'required|string',
                'methodType' => 'required|string'
            ]);

        } catch (ValidationException $e) {
            // Handle validation errors
            $response_array = ["result" => "FALIED", "msg" => $e->getMessage()];
            $this->fileLog2Response("response_deliverActivationCode", $response_array);
            return response()->json([$response_array], 422);
        }



        $provisioning = CardProvisioning::where('correlationId', $validate["correlationId"])->where('response_status',0)->orderBy('id', 'DESC')->first();

        if(!isset($provisioning)){
            $response_array = ['result' => 'FAILED','msg' => 'correlationId not approved'];
            $this->fileLog2Response("response_deliverActivationCode", $response_array);
            return response()->json($response_array);
        }
        else{
            $activation_code = new ActivationCode();
            $activation_code->create_date = Carbon::now();
            $activation_code->correlationId = $validate["correlationId"];
            $activation_code->walletId = $validate["walletId"];
            $activation_code->tokenReferenceId = $validate["tokenReferenceId"];
            $activation_code->panReferenceId = $validate["panReferenceId"];
            $activation_code->deviceId = $validate["deviceId"];
            $activation_code->methodType = $validate["methodType"];
            $activation_code->code_value = random_int(100000, 999999);
            $activation_code->status = "CREATED";
            $activation_code->save();
            $response_array = ['result' => 'SUCCESS'];
            $this->fileLog2Response("response_deliverActivationCode", $response_array);

        }

        return response()->json($response_array);
    }
    public function verifyActivationCode(Request $request) {

        $this->file2Log("request_verifyActivationCode", $request->all());

        $response_array = [];

        try {
            $validate = $request->validate([
                'correlationId' => 'required|string',
                'walletId' => 'required|string',
                'tokenReferenceId' => 'required|string',
                'panReferenceId' => 'required|string',
                'deviceId' => 'required|string',
                'activationCodeValue' => 'required|string'
            ]);

        } catch (ValidationException $e) {
            // Handle validation errors
            $response_array = ["result" => "FALIED", "msg" => $e->getMessage()];
            $this->fileLog2Response("response_verifyActivationCode", $response_array);
            return response()->json([$response_array], 422);
        }



        $activation_code = ActivationCode::where('correlationId', $validate["correlationId"])->where('status','CREATED')->orderBy('id', 'DESC')->first();

        if(!isset($activation_code)){
            $response_array = ['verificationResult' => false,'msg' => 'Activation code not exists '];
            $this->fileLog2Response("response_verifyActivationCode", $response_array);
            return response()->json($response_array);
        }
        else{
            if($activation_code->code_value == $validate["activationCodeValue"]){
                $activation_code->verify_status = "true";
                $activation_code->status = "VERIFIED";
                $response_array = ['verificationResult' => true];
            }
            else{
                $activation_code->verify_status = "false";
                $response_array = ['verificationResult' => false,'msg' => 'Activation code is invalid'];
            }
            $activation_code->verify_date = Carbon::now();
            $activation_code->request_code_value = $validate["activationCodeValue"];
            $activation_code->save();

            $this->fileLog2Response("response_verifyActivationCode", $response_array);

        }

        return response()->json($response_array);
    }

    public function cardMetadataNotificationService(Request $request) {

        $this->file2Log("request_cardMetadataNotificationService", $request->all());

        $response_array = [];

        try {
            $validate = $request->validate([
                'tokenRequestorId' => 'required|string',
                'walletId' => 'required|string',
                'tokenReferenceId' => 'required|string',
                'panReferenceId' => 'required|string',
                'actionCode' => 'required|string'
            ]);

        } catch (ValidationException $e) {
            // Handle validation errors
            $response_array = ["result" => "FALIED", "msg" => $e->getMessage()];
            $this->fileLog2Response("response_cardMetadataNotificationService", $response_array);
            return response()->json([$response_array], 422);
        }

        try{
            $meta_data = new MetaData();
            $meta_data->create_date = Carbon::now();
            $meta_data->tokenRequestorId = $validate["tokenRequestorId"];
            $meta_data->tokenReferenceId = $validate["tokenReferenceId"];
            $meta_data->panReferenceId = $validate["panReferenceId"];
            $meta_data->walletId = $validate["walletId"];
            $meta_data->actionCode = $validate["actionCode"];
            $meta_data->response_status = "SUCCESS";
            $meta_data->save();
            $response_array = ['result' => 'SUCCESS'];
            $this->fileLog2Response("response_cardMetadataNotificationService", $response_array);
        } catch (ValidationException $e) {
            // Handle validation errors
            $response_array = ["result" => "FALIED", "msg" => $e->getMessage()];
            $this->fileLog2Response("response_cardMetadataNotificationService", $response_array);
            return response()->json([$response_array], 422);
        }

        return response()->json($response_array);
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
        $log->pushHandler(new StreamHandler('logs/cardToken/' . date('Ymd') . '.log', Logger::INFO));
        $log->info($type, $msg);
    }

	public function fileLog2Response($type,$msg) {
        $log = new Logger("Response");
        $log->pushHandler(new StreamHandler('logs/cardToken/' . date('Ymd') . '.log', Logger::INFO));
        $log->info($type, $msg);
    }

}
