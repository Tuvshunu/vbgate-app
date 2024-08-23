<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use DB;

class SmartcarController extends Controller {

//
    public function getPenaltyList(Request $request) {

        $validate = Validator::make($request->all(), [
                    'platenumber' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'parameter error'
            ]);
        }

        $requestid = DB::nextSequenceValue('eoffice.smartcar_requestid');
        $data = $this->curlRequest(mb_strtoupper($request->platenumber));

        $res_acnt = DB::select("SELECT T.RCVACNTNO, A.NAME, T.PENALTYTYPE
                            FROM EOFFICE.SMARTCAR_LOCAL_ACNTNO t
                            INNER JOIN tb_test.BCOM_ACNT a ON a.acnt_code = t.rcvacntno");

        $result_array = array();

        if (@$data["resultcode"] != "0") {
            $this->requestAccessLog($requestid, $request->platenumber, "02", $data);
            return response()->json([
                        'code' => 1,
                        'msg' => 'Торгуулийн мэдээлэл олдсонгүй'
            ]);
        } else {
            $this->requestAccessLog($requestid, $request->platenumber, "01", $data);
            $row = 0;
            foreach ($data["response"]["listdata"] as $item) {
				
                if (@$item["paid"] == "false") {
                    $row++;
                    array_push($result_array, array(
                        "amount" => $item["amount"], "barcode" => $item["barcode"],
                        "localname" => trim($item["localname"]), "paid" => $item["paid"], "passdate" => $item["passdate"],
                        "tb_acnt_code" => ($item["reasontypecode"] != "23" ? "MN860019009100000184" : "MN160019009100000183"),
                        "tb_acnt_name" => ($item["reasontypecode"] != "23" ? "ЗАМЫН ХӨДӨЛГӨӨНИЙ УДИРДЛАГЫН ТӨВ" : "ЦАГДААГИЙН ЕРӨНХИЙ ГАЗАР"),
                        "paymentbankaccount" => $item["paymentbankaccount"],
                        "paymentbankaccountname" => $item["paymentbankaccountname"],
                        "paymentbankname" => $item["paymentbankname"], "platenumber" => trim($item["platenumber"]),
                        "reasontype" => trim($item["reasontype"]), "reasontypecode" => $item["reasontypecode"],
                    ));
                }
								
            }
            if ($row > 0) {
                 return Response::json(['code' => 0,
                            'msg' => 'success',
                            'requestid' => $requestid,
                            'list' => $result_array], 200, [], JSON_NUMERIC_CHECK);
            } else {
                 return Response::json(['code' => 1,
                            'msg' => 'Торгуулийн мэдээлэл олдсонгүй'],
                             200, [], JSON_NUMERIC_CHECK);
            }
        }
    }

    public function setUpdatePenalty(Request $request) {

        $validate = Validator::make($request->all(), [
                    'requestid' => 'required',
                    'list' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                        'code' => 2,
                        'msg' => 'parameter error'
            ]);
        }

        if (sizeof($request->list) > 0) {
            $jrno = 0;
            $penaltyArray = json_decode(json_encode($request->all()), true);

            foreach ($penaltyArray["list"] as $item) {
                $this->insertTxnDb($request->requestid, $item);
            }
			
			foreach ($penaltyArray["list"] as $item) {
                $this->savePayment($request->requestid, $item);
            }

            return response()->json([
                        'code' => 0,
                        'msg' => 'success',
                        'requestid' => $request->requestid
            ]);
        }
    }

    public function insertTxnDb($requestid, $item) {
        $result = DB::table("eoffice.smartcar_txndata")
                ->insert(array(
            "requestid" => $requestid,
            "jrno" => $item["txn_jrno"],
            "tellerno" => "60",
            "brchno" => "900",
            "sourcename" => "VB",
            "barcode" => $item["barcode"],
            "txnamount" => str_replace(",", "", $item['amount']),
            "penaltytype" => $item["reasontypecode"],
            "penaltydate" => $item['passdate'],
            "penaltydesc" => $item["reasontype"],
            "logdate" => date('Y-m-d H:i:s'),
            "iscash" => 0,
            "rcvacntno" => trim($item["tb_acnt_code"]),
            "platenumber" => trim($item["platenumber"]),
            "extacntno" => trim($item["paymentbankaccount"]),
            "extacntname" => trim($item["paymentbankaccountname"]),
            "extbankname" => trim($item["paymentbankname"])
        ));

        return $result;
    }

    public function requestAccessLog($requestid, $carnumber, $resultcode, $responseData) {
        DB::table("eoffice.smartcar_requestlog")
                ->insert(array(
                    "requestid" => $requestid,
                    "platenumber" => $carnumber,
                    "sourcename" => "VB",
                    "userno" => 60,
                    "userbranch" => 900,
                    "resultcode" => $resultcode,
                    "resultdata" => json_encode($responseData, JSON_UNESCAPED_UNICODE),
                    "logdate" => date('Y-m-d H:i:s')
        ));
    }

    public function savePayment($requestid,$item) {
		return;
        //$teller = \App\Modules\Gov\Models\DatabaseHelper::instance()->getTeller(Auth::user()->tellerno);        
        $param = "\r\n\t\t\t<amount>" . intval(str_replace(",", "", $item['amount'])) . "</amount>\r\n\t\t\t"
                . "\r\n\t\t\t<barCode>" . strtoupper($item['barcode']) . "</barCode>\r\n\t\t\t"
                . "\r\n\t\t\t<bankId>213</bankId>\r\n\t\t\t"
                . "\r\n\t\t\t<bankName>Тээвэр хөгжлийн банк</bankName>\r\n\t\t\t"
                . "\r\n\t\t\t<reasonTypeCode>" . $item['reasontypecode'] . "</reasonTypeCode>\r\n\t\t\t"
                . "\r\n\t\t\t<transactionNumber>" . $requestid . "</transactionNumber>\r\n\t\t\t"
                . "\r\n\t\t\t<paymentType>Бэлэн бус</paymentType>\r\n\t\t\t"
                . "\r\n\t\t\t<plateNumber>" . mb_strtoupper($item["platenumber"], "UTF-8") . "</plateNumber>\r\n\t\t\t"
                . "\r\n\t\t\t<reasonType>" . $item['reasontype'] . "</reasonType>\r\n\t\t\t";
        // die(print_r($param));
        //rtrim($curl_post_data, '&');
        $curl_response = $this->curlInsert($param);
        //die(print_r($curl_response));
        $this->savePaymentResultSave($requestid, $item["barcode"], $curl_response["resultcode"], $param, $curl_response);
    }

    public function savePaymentResultSave($requestid, $barcode, $txt, $param, $response) {
        DB::table('eoffice.smartcar_txndata')
                ->where('requestid', '=', $requestid)
                ->where('barcode', '=', $barcode)
                ->update(array("resultcode" => "$txt", "senddata" => $param, "resultdata" => serialize($response)));
    }

    public function curlRequest($platenumber) {
        $curl = curl_init();
        $sign_key = $this->getSignature();
        $regnum = "ЖН88040279";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8090",
            CURLOPT_URL => "http://172.16.200.88:8090/xyp/transport-1.3.0/ws?WSDL",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 200,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:les=\"http://transport.xyp.gov.mn/\"> 
                    \r\n<soapenv:Header /> 
                    \r\n\t<soapenv:Body>
                     \r\n\t\t<les:WS100403_getVehiclePenaltyList> 
                     \r\n\t\t\t<request> 
                            \r\n\t\t\t\t<auth>
                                \r\n\t\t\t\t<citizen>
                                    \r\n\t\t\t\t<regnum></regnum>
                                    \r\n\t\t\t\t<fingerprint></fingerprint>                                    
                                \r\n\t\t\t\t</citizen>
                                \r\n\t\t\t\t<operator>
                                    \r\n\t\t\t\t<regnum>$regnum</regnum>
                                \r\n\t\t\t\t</operator>
                            \r\n\t\t\t\t</auth>
                            \r\n\t\t\t\t<plateNumber>" . $platenumber . "</plateNumber> 
                        \r\n\t\t\t</request> 
                    \r\n\t\t</les:WS100403_getVehiclePenaltyList> 
                    \r\n\t</soapenv:Body> \r\n</soapenv:Envelope>",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/xml;charset=\"utf-8\"",
                "accessToken: 662589e26a5c168996ed9093446322b3",
                "timestamp: " . $sign_key["timestamp"] . "",
                "signature: " . base64_encode($sign_key["signature"])
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = mb_strtolower(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response), "UTF-8");
            $xml = $this->xmlConvert($response);
            $json = json_encode($xml);
            //return $json;
            $responseArray = json_decode($json, true);
            //print_r($responseArray);
            return @$responseArray["soapbody"]["ns2ws100403_getvehiclepenaltylistresponse"]["return"];
        }
    }

    public function curlInsert($body) {
        $sign_key = $this->getSignature();
        $regnum = "ЖН88040279";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8090",
            CURLOPT_URL => "http://172.16.200.88:8090/xyp/transport-1.3.0/ws?WSDL",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:les=\"http://transport.xyp.gov.mn/\"> 
                    \r\n<soapenv:Header /> 
                    \r\n\t<soapenv:Body>
                     \r\n\t\t<les:WS100408_updatePenaltyPayment> 
                     \r\n\t\t\t<request>$body 
                        \r\n\t\t\t</request> 
                    \r\n\t\t</les:WS100408_updatePenaltyPayment> 
                    \r\n\t</soapenv:Body> \r\n</soapenv:Envelope>",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/xml;charset=\"utf-8\"",
                "accessToken: 662589e26a5c168996ed9093446322b3",
                "timestamp: " . $sign_key["timestamp"] . "",
                "signature: " . base64_encode($sign_key["signature"])
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $err_array = array();
			$err_array["resultcode"]="";
			$err_array["errormessage"]=$err;
            return $err_array;
        } else {
            //echo $response;
            $response = mb_strtolower(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response), "UTF-8");
            $xml = $this->xmlConvert($response);
            $json = json_encode($xml);
            $responseArray = json_decode($json, true);
            //print_r($responseArray);
            return $responseArray["soapbody"]["ns2ws100408_updatepenaltypaymentresponse"]["return"];
            //return $responseArray["soapbody"];
        }
    }

    public function xmlConvert($str) {
        return $xmlObject = simplexml_load_string($str);
    }

    public function getSignature() {
        $return = array();

        $private_key = <<<EOD
-----BEGIN PRIVATE KEY-----
MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDuJw+iBYM514+7
DYQY03b06wcAKroDV32JchCE77kCFS5HC1po+prQLuF5eJoMzgBao611HagElMCF
cht0YZM4bjrs8l5L0RFQaX8IE5gFW42aH2Rk0piesFFshTTkHl+vHv21qVIAjmDC
kjYMwRXYT/tDmBvxViTWEKSgLc2iJjhf99B+u5RVnFs7foPfgZYC+HnUsNu/+gs8
TeOdIeV0yAS5J/T2ixa7m8NBUWt4TqGI9VpiDUyzdVh2g44pLWGZE2n+yr7gZGby
09QNWB6Q7yyMiGZRTLHdK0iGwAtUfxBOdhXbkMxzE+oMJrq9h/9oVabOlZlgZDt6
wPCjhAnLAgMBAAECggEBAOwvyQ1CslZXNrCoQu3sKvnTbWn49eoChvodKcztgmpS
X0cON2gNwiPdmrhBp2Yzl55M3eZctxlz/UtbU2ckrkE08TO1W1eNzMIXHmkAJbCK
j/DeWU60nbAj5Vze7wmeJf8jmTLk7fKnQcc2Amy7wdJ54BO1c2Dxsi6q6toBEwNP
QprCYd+y7GQbvOeEeAGe0r/Kokcdgin5k/cnzDbmUXCjVDy8I0xl2kz4ie2nAJW8
PNK/+r8ueySIOPmKA7wWKEAt/7uUy4SexL7DYP8lwoc7rUPaeMiXwbQ7gJnEdVj9
vDKrlc2jiLEY2hziJS6rpFf7bYhMv/0cRBfTdwEj8eECgYEA+2KXiKn6NNJB95NM
OhoG1DsWrAhFr9YOTtgDBEcmn9tLNcpPK3BFEAChk1XocNvlyfjWMkSx3FNwNhWP
3PKFlhmLxz6l15rHxIUERSXc1LtXZVxUTxKgpxepAmnDpWwZs8VY1WGLmvQSnYDt
QZx55GtvOlNHNlZbOCWQiR0TQY8CgYEA8oZIFCWnOEGDYRuQRHw9cBDmcYQCpihL
kJcqa4L3oZSNoYsJ4QtZlUjanm5UNI3lZ8FKESG3aAtvWOo6RM/lJJESY4UwA5v4
gt2zc9voHbeKLgGN9CmbEKny7uvLk8/GV+r34SvCxxHhkmjcu3AcCVwXcmAChrt3
Bi8chsO7HgUCgYEAwQYZOPTbjEeOI53UwCBP8hJU/E91wuhoIB45YsWHYOOvwmPP
mpkgToNNjaY/TrlqnkUVo4+fyn++/6hayNrnvDNtYtY13XGZxsokVzwVbrtTBh95
FSZGeUbvnUy8z35L1f/IkD/QRHD5AjuG8gRGjB/6Thy3538zl88wRjgvGv0CgYEA
gaN19A8Mi+25JLdvLqyaysS96f/+yoLPocKsUjv30s9txeRkq389q6b99aJUMKOI
9SVFSlMTjvJN2uGZtB7NBfbmNXyEZemBtbJ8snniYcAyhNUf5Fw5H3c4/K3ebGys
QWLAjgSuYWsVgQW8uBT9Z6NqhSD9OLgMr6mPPhpyc2ECgYBJj54z3RXdq5jE47Z6
W5RqEt8z7WZSY/gRopRFrhXHR0EjI6MFuhUJqa4uX1sOrELJcK8syRf4xAltBI0G
j8fkEy7p9mfFuofBahuD752TmBMFWjSbMep0RquKMT/9i7Y1OHq263kOYYddgTt1
ME/VjhVXpzAytnAEygnDWQSadQ==
-----END PRIVATE KEY-----


EOD;

        $accessToken = "662589e26a5c168996ed9093446322b3";
        $timestamp = time();
        openssl_sign($accessToken . "." . $timestamp, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $return["signature"] = $signature;
        $return["timestamp"] = $timestamp;

        return $return;
    }

    public function testPenalty() {
        $platenumber = mb_strtoupper("3220УБЛ");

        $res = $this->curlRequest($platenumber);

        print_r($res);
    }
	
	public function setManually($requestid){
		 $result = DB::select("select * from EOFFICE.SMARTCAR_TXNDATA s where S.REQUESTID = $requestid and (resultcode<>'0' or resultcode is null )");
		 foreach ( $result as $item ) {
			 $this->savePayment2($requestid,$item);
		 }
	}
	
	public function savePayment2($requestid,$item) {
        //$teller = \App\Modules\Gov\Models\DatabaseHelper::instance()->getTeller(Auth::user()->tellerno);        
        $param = "\r\n\t\t\t<amount>" . intval(str_replace(",", "", $item->txnamount)) . "</amount>\r\n\t\t\t"
                . "\r\n\t\t\t<barCode>" .strtoupper($item->barcode). "</barCode>\r\n\t\t\t"
                . "\r\n\t\t\t<bankId>213</bankId>\r\n\t\t\t"
                . "\r\n\t\t\t<bankName>Тээвэр хөгжлийн банк</bankName>\r\n\t\t\t"
                . "\r\n\t\t\t<reasonTypeCode>" . $item->penaltytype. "</reasonTypeCode>\r\n\t\t\t"
                . "\r\n\t\t\t<transactionNumber>" . $requestid . "</transactionNumber>\r\n\t\t\t"
                . "\r\n\t\t\t<paymentType>Бэлэн бус</paymentType>\r\n\t\t\t"
                . "\r\n\t\t\t<plateNumber>" . mb_strtoupper($item->platenumber, "UTF-8") . "</plateNumber>\r\n\t\t\t"
                . "\r\n\t\t\t<reasonType>" . $item->penaltydesc . "</reasonType>\r\n\t\t\t";
        // die(print_r($param));
        //rtrim($curl_post_data, '&');
        $curl_response = $this->curlInsert2($param);
        //die(print_r($curl_response));
        $this->savePaymentResultSave($requestid, $item->barcode, $curl_response["resultcode"], $param, $curl_response);
    }
	
	public function curlInsert2($body) {
        $sign_key = $this->getSignature();
        $regnum = "ЖН88040279";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8091",
            CURLOPT_URL => "http://172.16.200.88:8091/xyp/transport-1.3.0/ws?WSDL",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:les=\"http://transport.xyp.gov.mn/\"> 
                    \r\n<soapenv:Header /> 
                    \r\n\t<soapenv:Body>
                     \r\n\t\t<les:WS100408_updatePenaltyPayment> 
                     \r\n\t\t\t<request>$body 
                        \r\n\t\t\t</request> 
                    \r\n\t\t</les:WS100408_updatePenaltyPayment> 
                    \r\n\t</soapenv:Body> \r\n</soapenv:Envelope>",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/xml;charset=\"utf-8\"",
                "accessToken: 662589e26a5c168996ed9093446322b3",
                "timestamp: " . $sign_key["timestamp"] . "",
                "signature: " . base64_encode($sign_key["signature"])
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
       
        if ($err) {
			$err_array = array();
			$err_array["resultcode"]="";
			$err_array["errormessage"]=$err;
            return $err_array;
        } else {
            //echo $response;
            $response = mb_strtolower(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response), "UTF-8");
            $xml = $this->xmlConvert($response);
            $json = json_encode($xml);
            $responseArray = json_decode($json, true);
            //print_r($responseArray);
            return $responseArray["soapbody"]["ns2ws100408_updatepenaltypaymentresponse"]["return"];
            //return $responseArray["soapbody"];
        }
    }

}
