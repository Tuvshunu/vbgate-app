<?php

namespace App\Models;
use DB;

class DatabaseHelper {

    protected static $_instance;

    public static function instance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getAccountLocal($acntno) {
     return DB::select("SELECT A.ACNT_CODE, A.RESERVED, name, cur_code, a.prod_type FROM tb_test.BCOM_ACNT a
 WHERE  ( A.ACNT_CODE =UPPER('$acntno') or a.reserved = UPPER('$acntno') )
 and A.PROD_TYPE IN ('CA',  'SA',  'TD', 'LOAN','CCA')");
    }

    public function getAddress($custcode) {
        return DB::select("select * from (SELECT
a.CUST_CODE,
tb_test.CYRI2LATIN(  replace ( replace (  replace ( B.NAME, 'хот', 'Mongolia' ),  'дүүрэг', 'district' ), 'аймаг', 'province') ) ||' '|| tb_test.CYRI2LATIN( A.ADDR_DETAIL ) address_en,
 B.NAME ||' '||  A.ADDR_DETAIL as address_mn
 , nvl ( p.mobile, p.phone ) as phone
 , dense_rank() over ( partition by A.CUST_CODE order by  A.IS_MAIN desc, A.CREATED_DATETIME desc ) rnk
 , A.IS_MAIN
  FROM tb_test.CIF_CUST_ADDRESS a
       LEFT JOIN tb_test.CIF_ADDRESS b ON A.ADDR_ID = B.ADDR_ID
       LEFT JOIN (
       select cust_code, P.MOBILE, P.PHONE  from tb_test.CIF_PERSON p
        union all
        select cust_code, P.MOBILE, P.PHONE  from tb_test.CIF_ORG p
         ) p on P.CUST_CODE = A.CUST_CODE
WHERE A.CUST_CODE = '$custcode'
and A.STATUS = 1 ) where rnk = 1");
    }

	 public function getTicketDeal($custcode) {
        return DB::connection("test_db76")->select("SELECT C.DEAL_CODE,
         C.BUY_SELL,
         C.BUY_ACNT_CODE,
         A.NAME as BUY_ACNTNAME,
         A.NAME2 as BUY_ACNTNAME2,
         C.BUY_CUR_CODE,
         C.BUY_AMOUNT,
         C.SELL_ACNT_CODE,
         A2.NAME as SELL_ACNTNAME,
         A2.NAME2 as SELL_ACNTNAME2,
         C.SELL_CUR_CODE,
         C.SELL_AMOUNT,
         C.CREATED_DATETIME,
         C.STATUS
    FROM tb_test.TKT_DEAL c
    left join tb_test.BCOM_ACNT a on A.ACNT_CODE = C.BUY_ACNT_CODE
    left join tb_test.BCOM_ACNT a2 on A2.ACNT_CODE = C.SELL_ACNT_CODE
   WHERE     C.CUST_CODE = '$custcode'
      AND ( (C.CREATED_DATETIME BETWEEN TRUNC (SYSDATE - 7) AND SYSDATE and C.STATUS in ('OPEN', 'COMPLETED', 'APPROVED', 'REQUESTED' )  )
   OR (  C.CREATED_DATETIME BETWEEN TRUNC (SYSDATE -3) AND SYSDATE and C.STATUS in ('CANCELLED' ) )   )
ORDER BY C.CREATED_DATETIME DESC");
    }

	public function getCardInfo($card_type) {

        $cond = "";

        $cartype_list = array("VISA" => "4", "UP" => "6", "T" => "9");


        if ($card_type != "*") {

            $cond = " and S.CARDPREFIX like '" . $cartype_list[$card_type] . "%' ";
        }

        return DB::connection("test_db76")->select("select
card_type  as card_type,
max ( card_fee_text ) as card_fee_text,
max ( card_fee_text2 ) as card_fee_text2,
max ( card_fee_dun ) as card_fee_dun,
max (CARDPREFIX) as CARDPREFIX,
max ( PREFIXNAME ) as PREFIXNAME,
currency,
LISTAGG( cbs_prod_code , ', ') WITHIN GROUP (ORDER BY cbs_prod_code ) \"cbs_prod_code\",
max ( card_prod_name ) as card_prod_name,
max ( card_prod_name2 ) as card_prod_name2,
max ( card_info ) as card_info,
max ( card_info2 ) as card_info2,
max ( delivery_info ) as delivery_info,
max ( delivery_info2 ) as delivery_info2,
max ( branch_info ) as branch_info,
max ( branch_info2 ) as branch_info2,
max ( expperiod ) as expperiod,
max ( finprof ) as finprof,
max ( groupcmd ) as groupcmd,
 tw_acnt_prod
 From (SELECT CASE
            WHEN S.CARDPREFIX LIKE '4%' THEN 'VISA'
            WHEN S.CARDPREFIX LIKE '6%' THEN 'UP'
            WHEN S.CARDPREFIX LIKE '9%' THEN 'T'
         END
            card_type,
         CASE
            WHEN S.CARDPREFIX LIKE '46%' THEN '3 жил /20,000 MNT'
            WHEN S.CARDPREFIX LIKE '6%' THEN '3 жил /30,000 MNT'
            WHEN S.CARDPREFIX LIKE '9%' THEN '3 жил /5,000 MNT'
            WHEN S.CARDPREFIX = '40583500' THEN '3 жил /50,000 MNT'
			WHEN S.CARDPREFIX = '42073401' THEN '3 жил /30,000 MNT'
         END
            card_fee_text,
            CASE
            WHEN S.CARDPREFIX LIKE '46%' THEN '3 year /20,000 MNT'
            WHEN S.CARDPREFIX LIKE '6%' THEN '3 year /30,000 MNT'
            WHEN S.CARDPREFIX LIKE '9%' THEN '3 year /5,000 MNT'
            WHEN S.CARDPREFIX = '40583500' THEN '3 year /50,000 MNT'
			WHEN S.CARDPREFIX = '42073401' THEN '3 year /30,000 MNT'
         END
            card_fee_text2,
            CASE
			WHEN S.CARDPREFIX LIKE '46%' THEN 20000
            WHEN S.CARDPREFIX LIKE '6%' THEN 30000
            WHEN S.CARDPREFIX LIKE '9%' THEN 5000
            WHEN S.CARDPREFIX = '40583500' THEN 50000
			WHEN S.CARDPREFIX = '42073401' THEN 30000
         END
            card_fee_dun,
         S.CARDPREFIX,
         S.PREFIXNAME,T.CURRENCY,
          a.cbs_prod_code,
          t.card_prod_name,
          t.card_prod_name2,
          to_char (t.card_info ) as card_info ,
          to_char (t.CARD_INFO2 ) as CARD_INFO2 ,
          to_char ( t.delivery_info) as delivery_info,
          to_char ( t.delivery_info2) as delivery_info2,
           to_char (t.branch_info ) as  branch_info,
           to_char (t.branch_info2 ) as  branch_info2,
         S.EXPPERIOD,
         S.FINPROF,
         S.GROUPCMD,
         T.TW_ACNT_PROD
    FROM tb_test.UVW_PRODUCTS s
         LEFT JOIN IBTEST.UVW_CARDPROD_CCY t
            ON T.CARD_PROD_CODE = S.CARDPREFIX
         left join (select card_prod_code,cbs_prod_code from tb_test.TSD_CARDPROD_LINK group by card_prod_code,cbs_prod_code) a on a.card_prod_code = S.CARDPREFIX
   WHERE T.CURRENCY is not null $cond
   )
   group by card_type, currency, tw_acnt_prod
   order by 1, decode ( CURRENCY, 'MNT',1, 'USD',2 ) ");
    }

	 public function getCardList($custcode, $pan) {
        return DB::connection("test_db76")->select("  SELECT
         A.CUST_CODE,
         C.ACCNO as acnt_code,
         A.CUR_CODE,
         A.STATUS,
          SUBSTR (C.PAN, 1, 6) || '******' || SUBSTR (C.PAN, 13, 4) as pan,
         A.NAME,
         C.ACCTYPE,
         C.A2CSTAT,
         C.A2CSTAT_NAME,
         C.ACCSTBO,
         C.ACCSTBO_NAME,
         C.ACCSTFO,
         C.ACCSTFO_NAME
    FROM tb_test.UVW_ACCOUNTSBYCARD c
         LEFT JOIN tb_test.BCOM_ACNT a ON A.ACNT_CODE = C.ACCNO
   WHERE     A.CUST_CODE = '$custcode'
         AND SUBSTR (C.PAN, 1, 6) || '******' || SUBSTR (C.PAN, 13, 4) = '$pan'
         AND A.STATUS IN ('O', 'N')
ORDER BY 1 DESC, pan");
    }

	 public function getCardDebitStat($acntcode, $startdate, $enddate) {
       return DB::connection("test_db76")->select("with txn_t as (select case when s.user_id in (68) or S.OPER_CODE ='15131024' then 'КАРТ'
            when (s.user_id =60 and i.c_type in ('LN_PAY','CC_PAY')) or (S.OPER_CODE in ('13640100','13640801','13642002','13610250','13600107','13600103','13600253') ) then 'ЗЭЭЛ ТӨЛӨЛТ'
            when (s.user_id =60 and i.c_type ='QPAY') or (S.USER_ID=116 ) then 'ТӨЛБӨР'
            when (s.user_id =60 and i.c_type not in ('QPAY','LN_PAY','CC_PAY')) or
            (S.OPER_CODE in ('13641501','13610050','13110301','13110303','13610051','13600500','15131071','13610054','13610055',
            '13610053','13601101','13600005','13600507','13600004','13600015','13600700','13600001','13600647','13600600','13600634','15171022','13600635','13600633'
            ,'13600101') ) then 'ШИЛЖҮҮЛЭГ'
            else 'БУСАД' end t_Type,
            case when s.user_id in (68) or S.OPER_CODE ='15131024' then 'CARD'
            when (s.user_id =60 and i.c_type in ('LN_PAY','CC_PAY')) or (S.OPER_CODE in ('13640100','13640801','13642002','13610250','13600107','13600103','13600253') ) then 'LOAN_PAY'
            when (s.user_id =60 and i.c_type ='QPAY') or (S.USER_ID=116 ) then 'PAYMENT'
            when (s.user_id =60 and i.c_type not in ('QPAY','LN_PAY','CC_PAY')) or
            (S.OPER_CODE in ('13641501','13610050','13110301','13110303','13610051','13600500','15131071','13610054','13610055',
            '13610053','13601101','13600005','13600507','13600004','13600015','13600700','13600001','13600647','13600600','13600634','15171022','13600635','13600633'
            ,'13600101') ) then 'TRANSFER'
            else 'OTHER' end t_Type2,
            s.jrno,
    s.jritem_no,
    TO_CHAR (s.post_date, 'YYYY.MM.DD HH24.MI') TRTIME,
     S.CRAMOUNT AS cramount,
     S.DRAMOUNT AS dramount,
    --TRIM (TO_CHAR (S.CONTRATE ,'999G999G999G990D00','NLS_NUMERIC_CHARACTERS = ''.,''')) AS contrate,
    --TRIM (TO_CHAR (S.BALANCE ,'B999G999G999G990D00','NLS_NUMERIC_CHARACTERS = ''.,''')) AS balance,
    --S.CONTACNTCODE,
    S.TXNDESC,
    S.TELLER,S.USER_ID ,I.C_TYPE,S.OPER_CODE
from tb_test.TRANS_1305_STATEMENT s
left join IBTEST.T_TRANSACTIONS i on I.C_TXN_JRN_ID=S.JRNO
where s.acnt_code= '".$acntcode."' and
s.txn_date between to_date ('".$startdate."','yyyy-mm-dd') and to_date ('".$enddate."','yyyy-mm-dd') )
select
t_typeid,
t_type,
t_type2,
dramount,
qnt,
trunc ( t.dramount*100/sum (t.dramount) over () , 4)  as percent
from (select max (MCC.TYPE_ID ) as t_typeid, t.t_type,t.t_type2,sum(t.dramount) as dramount ,count(*) as qnt
from txn_t t
left join IBTEST.MCC_CODE mcc on mcc.type  = t.t_Type2
where t.cramount =0
group by t.t_type,T.T_TYPE2  ) t
order by T.T_TYPE2");
    }

	public function getPfmDebitStatDetail($acntcode, $startdate, $enddate) {

	return DB::connection("test_db76")->select("select t_typeid, t_type, t_type2, fin_catid, fin_cat, fin_cat2, sum ( dramount ) as dramount from (
select
		MC.TYPE_ID as t_typeid,
PRIV.T_TYPE,
PRIV.T_TYPE2,
mc.catid as fin_catid,
priv.fin_cat ,
priv.fin_cat2 ,
priv.jrno ,
priv.trtime ,
priv.dramount ,
priv.txndesc ,
priv.teller ,
priv.user_id ,
priv.oper_code ,
priv.acnt
		from (with txn_t as (select s.txn_date, I.C_TYPE ,case when s.user_id in (68) or S.OPER_CODE ='15131024' then 'КАРТ'
            when (s.user_id =60 and i.c_type in ('LN_PAY','CC_PAY')) or (S.OPER_CODE in ('13640100','13640801','13642002','13610250','13600107','13600103','13600253') ) then 'ЗЭЭЛ ТӨЛӨЛТ'
            when (s.user_id =60 and i.c_type ='QPAY') or (S.USER_ID=116 ) then 'ТӨЛБӨР'
            when (s.user_id =60 and i.c_type not in ('QPAY','LN_PAY','CC_PAY')) or
            (S.OPER_CODE in ('13641501','13610050','13110301','13110303','13610051','13600500','15131071','13610054','13610055',
            '13610053','13601101','13600005','13600507','13600004','13600015','13600700','13600001','13600647','13600600','13600634','15171022','13600635','13600633'
            ,'13600101') ) then 'ШИЛЖҮҮЛЭГ'
            else 'БУСАД' end t_Type,
            case when s.user_id in (68) or (s.user_id not in (60,146) and S.OPER_CODE ='15131024' ) then 'CARD'
            when (s.user_id =60 and i.c_type in ('LN_PAY','CC_PAY')) or (S.OPER_CODE in ('13640100','13640801','13642002','13610250','13600107','13600103','13600253') ) then 'LOAN_PAY'
            when (s.user_id =60 and i.c_type ='QPAY') or (S.USER_ID=116 ) then 'PAYMENT'
            when (s.user_id =60 and i.c_type not in ('QPAY','LN_PAY','CC_PAY')) or
            (S.OPER_CODE in ('13641501','13610050','13110301','13110303','13610051','13600500','15131071','13610054','13610055',
            '13610053','13601101','13600005','13600507','13600004','13600015','13600700','13600001','13600647','13600600','13600634','15171022','13600635','13600633'
            ,'13600101') ) then 'TRANSFER'
            else 'OTHER' end t_Type2,
            s.jrno,
    s.jritem_no,
    TO_CHAR (s.post_date, 'YYYY.MM.DD HH24.MI') TRTIME,
     S.CRAMOUNT AS cramount,
     S.DRAMOUNT AS dramount,
    --TRIM (TO_CHAR (S.CONTRATE ,'999G999G999G990D00','NLS_NUMERIC_CHARACTERS = ''.,''')) AS contrate,
    --TRIM (TO_CHAR (S.BALANCE ,'B999G999G999G990D00','NLS_NUMERIC_CHARACTERS = ''.,''')) AS balance,
    --S.CONTACNTCODE,
    S.TXNDESC,
    S.TELLER,S.USER_ID ,S.OPER_CODE
from tb_test.TRANS_1305_STATEMENT s
left join IBTEST.T_TRANSACTIONS i on I.C_TXN_JRN_ID=S.JRNO  and i.c_type not in ( 'BATCH_TX')
where s.acnt_code= '".$acntcode."' and
s.txn_date between to_date ('".$startdate."','yyyy-mm-dd') and to_date ('".$enddate."','yyyy-mm-dd') )
select case when t.t_type2= 'CARD' then nvl(M.CATNAME,'ОУ гүйлгээ')
when t.t_type2 ='TRANSFER' and T.C_TYPE='SAME_BANK' then 'БАНКНЫ ДАНС ХООРОНД'
when t.t_type2 ='TRANSFER' and T.C_TYPE='OWN' then 'ӨӨРИЙН ДАНС ХООРОНД'
when t.t_type2 ='TRANSFER' and T.C_TYPE='OTHER_BANK' then 'БАНК ХООРОНД'
when t.t_type2 ='LOAN_PAY' and T.C_TYPE='CC_PAY' then 'КРЕДИТ КАРТ'
when t.t_type2 ='LOAN_PAY' and T.C_TYPE='LN_PAY' then 'ЗЭЭЛ'
else 'БУСАД'
end  FIN_CAT, nvl(t.c_type,case when t.t_type2= 'CARD' then nvl( M.CATNAME2 ,'International txn')
when t.t_type2 ='TRANSFER' and T.C_TYPE='SAME_BANK' then 'БАНКНЫ ДАНС ХООРОНД'
when t.t_type2 ='TRANSFER' and T.C_TYPE='OWN' then 'ӨӨРИЙН ДАНС ХООРОНД'
when t.t_type2 ='TRANSFER' and T.C_TYPE='OTHER_BANK' then 'БАНК ХООРОНД'
when t.t_type2 ='LOAN_PAY' and T.C_TYPE='CC_PAY' then 'КРЕДИТ КАРТ'
when t.t_type2 ='LOAN_PAY' and T.C_TYPE='LN_PAY' then 'ЗЭЭЛ'
else 'OTHER'
end) fin_cat2 ,t.t_type,t.t_type2,t.jrno,t.jritem_no,t.trtime,t.dramount,t.txndesc,t.teller,t.user_id,t.oper_code,
'".$acntcode."' acnt
from txn_t t
left join tb_test.TOT_TXN_REQUIST tt on tt.core_txn_date = t.txn_date and (TT.CORE_TXN_JRNO = t.jrno or TT.CORE_ORG_JRNO=t.jrno )
left join IBTEST.MCC_CODE m on TT.MCC=M.MCC and m.type = 'CARD'
where t.cramount =0) priv
left join ( select MC.CATID, MC.CATNAME,  MC.CATNAME2, mc.type_id, MC.TYPE  from ibtest.mcc_code mc group by MC.CATID, MC.CATNAME,  MC.CATNAME2, mc.type_id, MC.TYPE ) mc on mc.type=priv.t_type2 and mc.catname2 = priv.fin_cat2
)
group by t_typeid, t_type, t_type2, fin_catid, fin_cat, fin_cat2
order by t_typeid");

    }

	public function smsList($custcode) {
        return DB::select("SELECT S.ACNTNO, A.CUR_CODE,
case
 when S.IS_CREDIT = 1 then S.CREDIT_LIMIT
 when S.IS_DEBIT = 1 then S.DEBIT_LIMIT
 else 0
 end txn_limit,
 case
 when S.IS_CREDIT = 1 and S.IS_DEBIT = 1 then  'CD'
 when S.IS_CREDIT = 1 then 'D'
 when S.IS_DEBIT = 1 then  'C'
 end txn_type,
 S.STATUS as status
  FROM EOFFICE.SMS_INFO_ACCOUNT s
  INNER JOIN tb_test.BCOM_ACNT a on A.ACNT_CODE = S.ACNTNO
  WHERE A.CUST_CODE = '$custcode' and S.IS_SMS = 1 ");
    }

    public function smsDetail($acntno) {
        return DB::select("SELECT S.PHONENO, S.ACNTNO, P.NAME as prod_name, P.NAME2 as prod_name2, A.CUR_CODE, S.FEE_ACCOUNT,  P2.NAME as prod_name_fee, P2.NAME2 as prod_name_fee2,
case
 when S.IS_CREDIT = 1 then S.CREDIT_LIMIT
 when S.IS_DEBIT = 1 then S.DEBIT_LIMIT
 else 0
 end txn_limit,
 case
 when S.IS_CREDIT = 1 and S.IS_DEBIT = 1 then  'CD'
 when S.IS_CREDIT = 1 then 'D'
 when S.IS_DEBIT = 1 then  'C'
 end txn_type,
 S.CUST_LANG,
 S.STATUS as status
  FROM EOFFICE.SMS_INFO_ACCOUNT s
  INNER JOIN tb_test.BCOM_ACNT a on A.ACNT_CODE = S.ACNTNO
  LEFT JOIN tb_test.BCOM_PROD p on P.PROD_CODE = A.PROD_CODE
  LEFT JOIN tb_test.BCOM_PROD p2 on P2.PROD_CODE = A.PROD_CODE
  WHERE A.ACNT_CODE = '$acntno' and S.IS_SMS = 1");
    }

		public function getLnAvailableBal($acntno) {
			return DB::select("select
									a.acnt_code,
									round((nvl (max(vd.balance) ,0)- nvl (max(P.MIN_BAL),0 ) - nvl ( max(bl.balance) , 0 )) / ( (max(a.MATURITY_DATE) - tb_test.TXNDATE ('19')) * (max(decode(i.INT_LVL,'P', pi.INT_RATE, i.INT_RATE)) + 6)/100/365 +1 ),2) as ln_available_amount
								from tb_test.TD_ACNT a
									left join tb_test.BCOM_VBAL vd on vd.acnt_code = a.acnt_code and vd.bal_type_code = 'CRNT' and vd.is_active = 1
									left join tb_test.BCOM_VBAL bl on bl.acnt_code = a.acnt_code and bl.bal_type_code = 'BLOCKED' and bl.is_active = 1
									left join tb_test.BCOM_PROD_LIMIT p on p.prod_code = a.prod_code and p.cur_code = a.cur_code
									left join tb_test.BCOM_ACNT_INT i ON i.ACNT_CODE = a.ACNT_CODE and i.INT_TYPE_CODE = 'SIMPLE_INT'
									left join tb_test.BCOM_PROD_INT pi on pi.PROD_CODE = a.prod_code and pi.INT_TYPE_CODE = 'SIMPLE_INT'

								where A.ACNT_CODE = '$acntno'
								group by a.acnt_code");
		     /*return DB::select("select a.acnt_code,
									round (((nvl (max (vd.balance ),0)- nvl (max (P.MIN_BAL),0 ))*0.85 )-nvl (sum ( v.balance ),0)-nvl (max ( bl.block_amount ), 0 ),2) as ln_available_amount
									from tb_test.TD_ACNT a
									left join tb_test.COLL_ACNT coll on coll.key2 = a.acnt_code and coll.status_add = 'EN'
									left join tb_test.BCOM_UTIL u on coll.acnt_code = u.acnt_code AND u.link_type = 'COLL'
									left join tb_test.LOAN_ACNT l on l.acnt_code = u.use_acnt_code and l.status='O'
									left join tb_test.BCOM_VBAL v on v.acnt_code = l.acnt_code and v.bal_type_code = 'PRINCIPAL' and v.is_active = 1
									left join tb_test.BCOM_VBAL vd on vd.acnt_code = a.acnt_code and vd.bal_type_code = 'CRNT' and vd.is_active = 1
									left join tb_test.BCOM_PROD_LIMIT p on p.prod_code = a.prod_code and p.cur_code = a.cur_code
									left join ( SELECT bl.acnt_code, sum( BL.BLOCK_AMOUNT ) as BLOCK_AMOUNT
									  FROM tb_test.BCOM_BLOCK bl
									 WHERE   bl.status = 'ACTIVE' AND (   bl.blk_type_code NOT IN ('COLL_BLK_TYPE')
												OR bl.created_by NOT IN ('146'))
										   AND bl.IS_MANUAL = 1
										   group by BL.ACNT_CODE) bl on bl.acnt_code = a.acnt_code
									where A.ACNT_CODE = '$acntno'
									group by a.acnt_code");*/
	}

	public function getIntPayCalc($td_acntno, $approve_amount) {
		       return DB::select("SELECT
T.ACNT_CODE as acntno,
       T.CUR_CODE,
       round ((t.maturity_date - tb_test.TXNDATE ('19')) * ($approve_amount * ( (R.INT_RATE + 6) / 100 / 365)),2) as int_amount,
       round ((t.maturity_date - tb_test.TXNDATE ('19')) * ($approve_amount * ( (R.INT_RATE + 6) / 100 / 365)),2)+$approve_amount as princ_amount
  FROM tb_test.TD_ACNT t
       LEFT JOIN tb_test.BCOM_ACNT_INT r
          ON r.acnt_code = t.acnt_code AND r.int_type_code = 'SIMPLE_INT'
 WHERE T.ACNT_CODE = '$td_acntno'");
	}

    public function checkCardEligibility($pan, $exp_year, $exp_month)
    {
        return DB::select("select t.pan, T.PVV , T.EMBOSSNAME,  to_char(K.EXPTIME,'YYYYMM') expdate, K.STATUS
                            from TX.CARD@DBLINK_TEST194 t
                            left join tx.token@DBLINK_TEST194 k on k.ID = t.id
                            where t.pan='$pan' and to_char(k.EXPTIME,'yyyy') = '$exp_year' and to_char(k.EXPTIME,'mm') = '$exp_month' and k.STATUS = 'Active'");
    }
    public function approveProvisioning($pan, $exp_year, $exp_month)
    {
        return DB::select("select t.pan,
                            p.FIRSTNAME as familyName, p.FIRSTNAME as secondFamilyName,
                                p.FIRSTNAME as firstName, p.FIRSTNAME as secondFirstName,  p.FIRSTNAME as maidenName, substr( to_char(  p.MOBILEPHONES),instr(to_char(  p.MOBILEPHONES),']')+1) as phone,
                                substr( to_char(p.EMAILS),instr(to_char(p.EMAILS),']')+1) as email
                            from TX.CARD@DBLINK_TEST194 t
                                left join tx.token@DBLINK_TEST194 k on k.ID = t.id
                                left join TX.CONTRACT@DBLINK_TEST194 co ON  k.contractid = co.id
                                left join TX.PERSON@DBLINK_TEST194 p ON co.clientid=p.id
                                where t.pan='$pan' and to_char(k.EXPTIME,'yyyy') = '$exp_year' and to_char(k.EXPTIME,'mm') = '$exp_month' and k.STATUS = 'Active'");
    }

}
