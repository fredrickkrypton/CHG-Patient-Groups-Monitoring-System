<?php date_default_timezone_set("Africa/Nairobi"); 
include('mobiconfig.php'); 
 $recipients=$message='';
$_SESSION['conn']=$conn =$db;
//$_SESSION['conn']=$conn =@mysqli_connect('localhost', 'kklketlb_afyacredit', '$%fgfu9.mb65\5t;"', 'kklketlb_afyacredit');

$qry = $conn->query("delete from sms where sms=''");
function encodeURI($uri)
{
    return preg_replace_callback("{[^0-9a-z_.!~*'();,/?:@&=+$#-]}i", function ($m) {
        return sprintf('%%%02X', ord($m[0]));
    }, $uri);
}


$qresultv = mysqli_query($conn,"SELECT MAX(logid) AS logid   
     FROM (SELECT * FROM sms) AS tmp     GROUP BY DATE,smstype,phone,sms,TIMESTAMP  HAVING COUNT(*) > 1");
  while($row = mysqli_fetch_assoc($qresultv))
    { 

$list = $conn->query($fr="delete from sms   where logid='".$row['logid']."'");	
  $fr;
	}
	
//Sending Messages using sender id/short code
$qresultv = mysqli_query($conn,"SELECT * from sms where sent='0'");
  while($row = mysqli_fetch_assoc($qresultv))
    { 
$list = $conn->query("update sms set sent='1' where logid='".$row['logid']."'");

  $response=''; $message='';
				 
$recipients = $row['phone'];
$message    =  $row['sms'];

$username   = "pafccm";
$apikey     = "1937c647aa0d01505851c5ce253267c37116a0a02573ec0567223a10b541c345";
$from = "";
if($from!='')
{
	$sender='."&from=".$from';
}
if($recipients) {
$ch=curl_init();
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json","Content-Type: application/x-www-form-urlencoded","apiKey: $apikey"));
curl_setopt($ch,CURLOPT_URL,'https://api.africastalking.com/version1/messaging');
curl_setopt($ch, CURLOPT_POST, 1);
$message=encodeURI($message);
$message=str_replace('#', '%23',$message);
curl_setopt($ch, CURLOPT_POSTFIELDS,"username=".$username."&to=".$recipients."&message=".$message."&from=22184");
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

if(mb_strlen($message, "UTF-8")>4)
{
echo $response = curl_exec($ch);
} 

}	
 
	 
 }	
 
 //alerts 
 function checkdups($dbc,$phone,$date,$smstype)
 {
$check=mysqli_query($dbc,$hj="SELECT logid FROM sms WHERE phone='$phone' AND smstype='$smstype' AND date='$date'");
$checkrows=mysqli_num_rows($check);
 
   if($checkrows>0) {
	   return 1;
   } else {
	   return 0;
   }
	 
 }
 
 
  //create alerts
  
	
 ?> Done