<?php 
date_default_timezone_set("Africa/Nairobi"); 
 
 $recipients=$message='';
//$_SESSION['conn']=$conn =@mysqli_connect('localhost', 'root', '', 'afya');
 $_SESSION['conn']=$conn =@mysqli_connect('localhost', 'pharmacc_root', '$%fgfu9.mb65\5t;"', 'pharmacc_ccm');
 
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
$qresultv = mysqli_query($conn,"SELECT * from sms where sent='0' limit 1");
  while($row = mysqli_fetch_assoc($qresultv))
    { 
  
$list = $conn->query("update sms set sent='1' where logid='".$row['logid']."'");
 
 
  $response=''; $message='';
				 
$recipients = $row['phone'];
$message    =  $row['sms'];

$username   = "afyacredit";
$apikey     = "753cb213764185c1ae210f09d0bfd70c2fab8dc475f25e84480bd2ec329bf59e";
$from = "22184";

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

$response = curl_exec($ch);
$myfile = fopen("test.txt", "a") or die("Unable to open file!");
$txt = "\n\n";
fwrite($myfile, $response.$txt);
fclose($myfile);
} 
sleep(5);
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
  
	
 ?>  