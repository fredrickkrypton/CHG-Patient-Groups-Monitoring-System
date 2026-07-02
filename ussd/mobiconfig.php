<? date_default_timezone_set("Africa/Nairobi"); 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization,X-Authorization');

error_reporting(0);
$timestamp=date('Y-m-d H:i:s'); 
session_start();
session_regenerate_id(false);
//$db = mysqli_connect('localhost', 'root', '', 'ccm'); 
$db = mysqli_connect('localhost', 'pharmacc_root', '$%fgfu9.mb65\5t;"', 'pharmacc_ccm'); 
$appname="Money Market Guru"; 
$appurl="";
$copyright="Copyright &copy; ".$appname." v.0.01";
 
  function utf8ize( $mixed ) {
 if (is_array($mixed)) {
 foreach ($mixed as $key => $value) {
 $mixed[$key] = utf8ize($value);
 }
 } elseif (is_string($mixed)) {
return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
 }
return $mixed;
}


 function dateto_normal($date)
{
$date=str_replace('/','-', $date);
 $date=str_replace('.','-', $date);
$date=date('d/m/Y',strtotime($date)); 
return $date;
}

function date_to_normal($date)
{
$d0 = explode(".", $date);

//echo $d0[0].'/'.$d0[1].'/'.$d0[2];
return $d0[2].'-'.$d0[1].'-'.$d0[0];
	
}

function dateto_mysql($date)
{
 $date=str_replace('/','-', $date);
  $date=str_replace('.','-', $date);
 $date=date('Y-m-d',strtotime($date)); 
return $date;
}



//echo dateto_mysql('12.12.1990');



function nextsequence($db,$prefix)
{
$header=$prefix;
$sqlnm = mysqli_query($db,"SELECT keyname,keyvalue FROM idkeys WHERE  keyname='$header'");
$num_results = mysqli_num_rows($sqlnm);
while ($row = mysqli_fetch_assoc($sqlnm))
{ 
$keyvalue=$row['keyvalue'];
}
$nextkey=$keyvalue+1;

$fullref=$header.$nextkey;
 
 if($keyvalue=='')
{ 
$success =mysqli_query($db,"INSERT INTO idkeys SET  keyname='$header',keyvalue='$nextkey'"); 
if($success)  { } else { echo mysqli_error(); }
 }
 else
{
$success =mysqli_query($db,"UPDATE idkeys SET keyvalue='$nextkey' where  keyname='$header'"); 
 
if($success)  { } else { echo mysqli_error(); }  
 } 
 return $nextkey;	
 }



$sqlnm = mysqli_query($db,"SELECT * from settings");
 $num_results = mysqli_num_rows($sqlnm);
 while ($row = mysqli_fetch_assoc($sqlnm))
	{ 
	$interest=$row['interest'];
	$terms=$row['terms'];
	$helpline=$row['helpline'];
	}
 
function return_tb($db,$table,$field,$field2,$field2value)
{
global $db;	$record = 0;
 

	if($table != '' && $field != '' && $field2!= '' && $field2value!= '')
	{
       $sql=mysqli_query($db,$qr="Select $field from $table where $field2='$field2value' $sql");
			while($row=mysqli_fetch_assoc($sql))
			{
			  $record = $row[$field];
			}
	}
	

			return $record;
} 
 
 
function update_sessions($db,$phone,$col,$value) 
{
$success =mysqli_query($db,$vb="update member SET $col='$value' where  phone_no='$phone'");   $vb;
if($success)  {}
 	
}


function create_db_sms($db,$smstype,$colmn,$phone,$amount)
{

 
 $messx = fopen("sms.txt", "a") or die("Unable to open file!");
fwrite($messx,$smstype."--".$colmn."--".$phone."--".$amount."\n");
fclose($messx);

if($smstype==3&&$colmn=='gleader')
{

$sqlnm = mysqli_query($db,$fg="SELECT logid,name,group_id from member where phone_no='$phone'");  $fg;
while ($row = mysqli_fetch_assoc($sqlnm))
	{  
	$firstname=$row['name'];
	$group_id=$row['group_id'];
	}

$sqlnmx = mysqli_query($db,$ch="SELECT phone_no FROM member where  group_id='$group_id'  AND isleader='1'");
$ch;

$messx = fopen("x.txt", "a") or die("Unable to open file!");
fwrite($messx,$ch."\n");
fclose($messx);

while ($rc = mysqli_fetch_assoc($sqlnmx))
	{
$phone=$rc['phone_no'];
	}	
} 

else {
$sqlnm = mysqli_query($db,$fg="SELECT logid,name,group_id from member where phone_no='$phone'");  $fg;
while ($row = mysqli_fetch_assoc($sqlnm))
	{  
	$firstname=$row['name'];
	$group_id=$row['group_id'];
	}
}
 
		
$sqlnm = mysqli_query($db,$gh="SELECT name from provider_group where logid='$group_id'");   $gh;
while ($row = mysqli_fetch_assoc($sqlnm))
	{  
	$gname=$row['name']; 
	}	
$sqlnm = mysqli_query($db,$df="SELECT $colmn as text FROM smssettings where logid='$smstype'");

while ($row = mysqli_fetch_assoc($sqlnm))
	{  
	$text=$row['text'];
	}
		

$sms=str_replace("{NAME}",$firstname,$text);
$sms=str_replace("{GROUPNAME}",$gname,$sms);
$sms=str_replace("{AMOUNT}",$amount,$sms);
$success =mysqli_query($db,"INSERT INTO sms SET date='".date('Y-m-d')."', smstype='$smstype',phone='$phone',sms='$sms'"); 
if($success)  { } else { echo mysqli_error(); }	
}


//=============================================================================mpesa========================================
function lipaNaMpesaPassword()
    {
        $lipa_time = date("YmdHms");
        $passkey = "d9738c9f37b9c065cff30d3f98854f8066d9d44ac4c3a415e0e52851bb5e61da";
        $BusinessShortCode =788202;
        $timestamp =$lipa_time;
        $lipa_na_mpesa_password = base64_encode($BusinessShortCode.$passkey.$timestamp);
        return $lipa_na_mpesa_password;
    }
    /**
     * Lipa na M-PESA STK Push method
     * */
 function customerMpesaSTKPush($phone,$amount,$accountref,$descr)
    {
        $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL, $url);
        curl_setopt($curl,CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.generateAccessToken()));
		
		 
        $curl_post_data =['BusinessShortCode' => 788202,
            'Password' =>lipaNaMpesaPassword(),
            'Timestamp' =>date("YmdHms"),
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' =>$amount, 
            'PartyA' => $phone, // replace this with your phone number
            'PartyB' => 788202,
            'PhoneNumber' =>$phone, // replace this with your phone number
            'CallBackURL' => 'https://pharmaccesskenya.or.ke/ccm/ussd/stk_get.php',
            'AccountReference' =>$accountref,
            'TransactionDesc' =>$descr];
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
         $curl_response = curl_exec($curl);
        return  $curl_response;
    }
   function generateAccessToken()
    {
        $consumer_key="JJyih1e2YIYSt0M3sJ2LeSuynVMnGjLF";
        $consumer_secret="oGOAqexN5KfScxfE";
        $credentials = base64_encode($consumer_key.":".$consumer_secret);
        $url = "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic ".$credentials));
        curl_setopt($curl, CURLOPT_HEADER,false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
		 
		 $myfile = fopen("pesaRes.txt", "w") or die("Unable to open file!");
$txt = "\n";
fwrite($myfile, $curl_response);
fclose($myfile);
        $access_token=json_decode($curl_response);
        return $access_token->access_token;
    }
	//======================================================================================================================
function isRealDate($date) { 
    $tempDate = explode('/', $date);
  // checkdate(month, day, year)
  return checkdate($tempDate[1], $tempDate[0], $tempDate[2]);
}

$sqlnm = mysqli_query($db,"SELECT logid,phone_no,collect,collecttxt FROM member where collect>0 and xamount=0");
while ($row = mysqli_fetch_assoc($sqlnm))
	{  
 	customerMpesaSTKPush(str_replace("+",'',$row['phone_no']),$row['collect'],$row['collecttxt'],$row['collecttxt']); 
	$success =mysqli_query($db,$vb="update member SET collect='0',collecttxt='' where  logid='".$row['logid']."'"); 
if($success) {}
	}

 $success =mysqli_query($db,$vb="update member SET regfee='1' where  regfee='0'");$vb;
if($success) {}
 ?>