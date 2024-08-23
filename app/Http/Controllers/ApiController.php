<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller {

    //


    public function getIbanConvert(Request $request) {
        $acc = $request->_account;
        $bankcode = $request->_bankcode;
        $type = $request->_type;
        $response_array = array();

        if ($type == 'acc2iban') {
            $m = $this->toIBAN($acc, $bankcode);
            $response_array['iban'] = $m;
            $response_array['status'] = 1;
        } else {
            $b = $this->fromIBAN($acc);

            if ($b["status"] == 0) {
                $response_array['status'] = 0;
                $response_array["result"] = $b["result"];
            } else {
                $response_array = $b;
            }
        }
        //header('Content-type: application/json');
        return response()->json($response_array);
    }

    public function toIBAN($acc, $bankcode) {
        $dbSchema = config('app.db_schema');

        $result = DB::select("select ".$dbSchema.".PK_COMMON.get_iban('MN','" . str_pad($bankcode, 4, "0", STR_PAD_LEFT) . "','$acc') as iban from dual");
        return $result[0]->iban;

        /* $val = "MN00" . str_pad($bankcode, 4, "0", STR_PAD_LEFT) . str_pad($acc, 12, "0", STR_PAD_LEFT);
          $val2 = intval(str_pad($bankcode, 4, "0", STR_PAD_LEFT)) . str_pad($acc, 12, "0", STR_PAD_LEFT) . "222300";
          $mod = $this->myMod($val2, 97);
          $m = "MN" . (98 - $mod) . str_pad($bankcode, 4, "0", STR_PAD_LEFT) . str_pad($acc, 12, "0", STR_PAD_LEFT);
          return $m; */
    }

    public function fromIBAN($val) {
        $result_array = array();
        $dbSchema = config('app.db_schema');
        $result = DB::select("select ".$dbSchema.".PK_COMMON.calculate_mod('" . $val . "') as isban from dual");
        if ($result[0]->isban == 1) {
            $bankname = $this->getBankName(substr($val, 6, 2));
            $result_array["bankname"] = $bankname;
            $result_array["bankcode"] = substr($val, 6, 2);
            $result_array["account"] = intval(substr($val, 8));
            $result_array["status"] = 1;
            return $result_array;
        } else {
            $result_array["status"] = 0;
            $result_array["result"] = "Алдаатай данс";
            return $result_array;
        }
    }

    public function getBankName($bankcode) {
        $dbSchema = config('app.db_schema');
        $result = DB::select("select * from ".$dbSchema.".CIF_BANK where substr (bank_no_of_bom,1,2) = '$bankcode'");

        if (sizeof($result) > 0) {
            return $result[0]->name;
        } else {
            return "Банк олдсонгүй";
        }
    }


    public function gitpull(){
        $projectName = 'test_itd/apps';
        $projectUrl = 'c:\projects\vbgate';
        exec("cd $projectUrl");
        exec("git pull",$out); //projectURL - үндсэн замаа заах, projectName - repo нэрийг нь бичих
        return $out;
    }

}
