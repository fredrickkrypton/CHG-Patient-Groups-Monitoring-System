<?
date_default_timezone_set("Africa/Nairobi"); 
include('mobiconfig.php');


$data = json_decode($respo=file_get_contents('php://input'), true);
$Order_ID=$_GET['$Order_ID'];

$number=$date='';

$array = $data['Body']['stkCallback'];
 $result_code = $array['ResultCode'];
$amount = $array['CallbackMetadata']['Item'][0]['Value'];
$id= $arr['Body']['stkCallback']['CheckoutRequestID']; 
$mpesareceiptnumber=$array['CallbackMetadata']['Item'][1]['Value'];

if($array['CallbackMetadata']['Item'][2]['Name']=="Balance") { $date=3; $number=4; } else { $date=2; $number=3; }

$transactiondate=date('Y-m-d H:i:s',strtotime($array['CallbackMetadata']['Item'][$date]['Value']));
$phonenumber=$array['CallbackMetadata']['Item'][$number]['Value'];
 

if($result_code=='0') {
$data="transactiondate='$transactiondate',phonenumber='+$phonenumber',amount='$amount',mpesareceiptnumber='$mpesareceiptnumber'";
$success =mysqli_query($db,"INSERT INTO mpesa SET $data"); 
if($success)  { 

$phone="+".$phonenumber;
$sqlnmx = mysqli_query($db,$ch="SELECT regfee,group_id FROM member where  phone_no='$phone'");
$ch;
while ($rc = mysqli_fetch_assoc($sqlnmx))
	{
$regfee=$rc['regfee'];
$group_id=$rc['group_id'];

	}

$sqlnmx = mysqli_query($db,$ch="SELECT phone_no FROM member where  group_id='$group_id'  AND isleader='1'");
$ch;
while ($rc = mysqli_fetch_assoc($sqlnmx))
	{
$gphone_no=$rc['phone_no'];
	}

if($regfee==0) {
 create_db_sms($db,2,'member',$phone,$amount);
  create_db_sms($db,2,'gleader',$gphone_no,$amount);
} else {
 create_db_sms($db,3,'member',$phone,$amount);
 create_db_sms($db,3,'gleader',$phone,$amount);
}

$successx =mysqli_query($db,"UPDATE `mpesa`,`member` SET ctype=regfee,mpesa.`groupid`=member.`group_id` , mpesa.`memberid`=member.`logid`
WHERE mpesa.`phonenumber`=member.`phone_no` and mpesa.`groupid`=0"); 
if($successx)  { } else { echo mysqli_error(); }

 
 


//allocate=================================================================================

//end of allocation========================================================================
} else { echo mysqli_error(); }
   $data;
 
 }
?>