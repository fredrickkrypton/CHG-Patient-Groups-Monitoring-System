<?php 
error_reporting(2); 
date_default_timezone_set("Africa/Nairobi"); 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$Appnameversion=" v.1.0.0 ™ <a href='http://innovee.co.ke'> Innovee</a>";
$Appurl = $_SERVER['REQUEST_URI'];
$timestamp=date('Y-m-d H:i:s');
//$_SESSION['conn']=$conn =@mysqli_connect('localhost', 'root', '', 'ccm');
 $_SESSION['conn']=$conn =@mysqli_connect('localhost', 'pharmacc_root', '$%fgfu9.mb65\5t;"', 'pharmacc_ccm');
 //on session creation
$_SESSION['timestamp']=time();

$parts = explode('/',$Appurl);
$parts = $parts[count($parts) - 1];
$parts = explode("&", $parts, 2);
$page = $parts[0];

if($_GET['logout'])
{
session_destroy();
session_unset();
header("Location: index.php");

}

function osteens($input) {
 return strtr(base64_encode($input), '+/=', '-_,');
}
function base64_url_encode($input) {
 return strtr(base64_encode($input), '+/=', '-_,');
}
function base64_url_decode($input) {
 return base64_decode(strtr($input, '-_,', '+/='));
}
function workout($D){
global $part, $rec;
$teile = explode("&",base64_url_decode($D));
$part= explode("=",$teile[0]);
$part=$part[1];
$rec=explode("=",$teile[1]); 
$rec=$rec[1];
}
function dateto_mysql($date){
$date=str_replace("/","-",$date);
   $date=date('Y-m-d',strtotime($date)); 
   return $date;
}
function dateto_normal($date){
		 $date=date('d/m/Y',strtotime($date)); 
	    return $date;
}
function dateto_display($date){
		 $date=date('d/m/Y',strtotime($date)); 
	    return $date;
}
function uploadthisfile($txtfieldname)
{
 global $errorupload;  global $nofilesattahced; 
  $target_dir = "uploads/";
$target_file = $target_dir . date('ymdHis')."_".basename($_FILES[$txtfieldname]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
 
if($imageFileType == "php" ||$imageFileType == "exe" || $imageFileType == "bat" ) {
     ("Sorry,php,exe and bat files are not allowed.");
    $uploadOk = 0;
}
 
if(basename($_FILES[$txtfieldname]["name"])=='')
{
$uploadOk=0;	
} else 
{
 
unlink("uploads/".$_POST['hiddenfile']);	
}
// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    $errorupload=1;
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES[$txtfieldname]["tmp_name"], $target_file)) {
         ("The file ". basename( $_FILES[$txtfieldname]["name"]). " has been uploaded.");
    } else {
         ("No upload file attached!");
		$nofilesattahced=1;
    }
}
}
 
function _title($title){
	if($title=='') { $title=$_SESSION['companyname']; }
echo "<title>".$title."</title>";	
}
function  audittrail($conn,$table,$id,$action,$timestamp,$username){
$sql = "INSERT INTO audittrail (tablename,id,action,timestamp,username)
VALUES ('$table','$id','$action','$timestamp','$username')";
if ($conn->query($sql) === TRUE) { }	
} 

function get_access($conn,$roleid)
{
$_SESSION['allaccess']='';	
$qresultv = mysqli_query($conn,$fg="SELECT roles_pages.filename,roles_access.view,roles_access.canadd,roles_access.canedit,roles_access.candelete FROM 
roles_pages,roles_access
WHERE roles_pages.logid=roles_access.pages AND roles_access.role='$roleid'");
  while($drow = mysqli_fetch_assoc($qresultv))
    { 
if($drow['view']=="Yes"){ $_SESSION['allaccess'].=$drow['filename']."_view,"; }
if($drow['canadd']=="Yes"){ $_SESSION['allaccess'].=$drow['filename']."_add,"; }
if($drow['canedit']=="Yes"){ $_SESSION['allaccess'].=$drow['filename']."_edit,"; }
if($drow['candelete']=="Yes"){ $_SESSION['allaccess'].=$drow['filename']."_delete,"; }
	 }	
	 return $_SESSION['allaccess'];
} 

function generateRandomString($length = 10) {
    $characters = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}




function checked($conn,$roleid,$pages)
{
$qresultv = mysqli_query($conn,$fg="SELECT * FROM roles_access where role='$roleid' AND pages='$pages'");
  while($drow = mysqli_fetch_assoc($qresultv))
    { $access=$drow['view'].",".$drow['canadd'].",".$drow['canedit'].",".$drow['candelete']; }	
	return $access;
}
 function caniacess($page)
{
if (preg_match("/\b".$page."\b/i",$_SESSION['allaccess'])) {
 $_SESSION['stop']=0;
} else {
$page=str_replace('_',' ',$page);	
$caniacess= "<script> window.addEventListener('load', function () { 
	new PNotify({
                                  title: 'Access error',
                                  text: 'You do not have access to  $page page!',
								  nonblock: { nonblock: !0 },
                                  type: 'error',
                                  styling: 'bootstrap3'
                              }); });</script>";
 
 $_SESSION['stop']=1;							  
}
echo $caniacess;

}
 
  $username=$_SESSION['userusername']; $Appname=$_SESSION['companyname']; 
 
 function nextsequence($conn,$prefix)
{

 $header=$prefix;
$sqlnm = @mysqli_query($conn,"SELECT keyname,keyvalue FROM idkeys WHERE  keyname='$header'");
 while ($row = mysqli_fetch_array($sqlnm))
	  { 
	  $keyvalue=$row['keyvalue'];
	  }
	 $nextkey=$keyvalue+1;
	  $fullref=$header.$nextkey;
 if($keyvalue=='')
	   { 
	  $success =@mysqli_query($conn,"INSERT INTO idkeys SET  keyname='$header',keyvalue='$nextkey'"); 
if($success)  { }
	   }
	  else
	  {
		 $success =@mysqli_query($conn,"UPDATE idkeys SET keyvalue='$nextkey' where  keyname='$header'"); 
if($success)  { }  
		  
	  } 
 return $nextkey;	
}
$mylimit=10;

function return_tb($db,$table,$field,$field2,$field2value)
{
	$record = '';
	if($table != '' && $field != '' && $field2!= '' && $field2value!= '')
	{
       $sql=@mysqli_query($db,"Select $field from $table where $field2='$field2value'");
			while($row=@mysqli_fetch_array($sql))
			{
			  $record = $row[$field];
			}
	}
			return $record;
}

if($_POST['login'])
{
$username = mysqli_real_escape_string($conn, $_POST['username']);

$password = mysqli_real_escape_string($conn, $_POST['password']);

//$_POST['secc']='admin';

if (empty($username)) {
echo "<script> window.addEventListener('load', function () { 
	new PNotify({
                                  title: 'error',
                                  text: 'Username is required',
								  nonblock: { nonblock: !0 },
                                  type: 'error',
                                  styling: 'bootstrap3'
                              }); }) </script>";
$errors=1;
}

if (empty($password)) {

echo "<script> window.addEventListener('load', function () { 
	new PNotify({
                                  title: 'error',
                                  text: 'Password is required',
								  nonblock: { nonblock: !0 },
                                  type: 'error',
                                  styling: 'bootstrap3'
                              }); }) </script>";
$errors=1;
}
 

if (count($errors) == 0) {

$password = md5($password);

 $query = "SELECT * FROM 0_users WHERE username='$username' AND password='$password' AND active='Yes'";

$results = @mysqli_query($conn, $query);

if (@mysqli_num_rows($results) == 1) {

$_SESSION['userusername'] = $username;

while($row = @mysqli_fetch_assoc($results))
    { 
	$_SESSION['userfullnames']=$row['fullnames'];
	$_SESSION['useremail']=$row['email'];
	$_SESSION['userphone']=$row['phone'];
	$_SESSION['userrole']=$row['role'];
	$_SESSION['groupid']=$row['groupid'];
	
	$_SESSION['userprofpic']=$row['profpic'];
	get_access($conn,$row['role']);
	
	$resultsA = mysqli_query($conn,"SELECT * FROM settings");
	while($st = mysqli_fetch_assoc($resultsA))
    {
	$_SESSION['companyname']=$st['companyname'];
	$_SESSION['companyphone']=$st['phone'];
	$_SESSION['companymobile']=$st['mobile'];	
	$_SESSION['companyaddress']=$st['address'];
	$_SESSION['companyemail']=$st['email'];
	$_SESSION['companycurrency']=$st['currency'];
	$_SESSION['logouttime']=$st['logouttime'];
	$_SESSION['rounding']=$st['rounding'];
	$_SESSION['userprofpic']=$st['profpic'];
	$_SESSION['fixedlogin']='';
	 $insertSQLn = "UPDATE  0_users SET lastlogin='$timestamp' WHERE  username='$username'"; 
   if(@mysqli_query($conn,$insertSQLn)){}
	}
	$_SESSION['systemexpiry'] = time();
	 }

echo "<script> window.addEventListener('load', function () { 
	new PNotify({
                                  title: 'Success',
                                  text: 'Welcome back ".$_SESSION['fullnames']."',
								  nonblock: { nonblock: !0 },
                                  type: 'success',
                                  styling: 'bootstrap3'
                              }); }) </script>";
//header("Location: index.php");
}else {

 
echo "<script> window.addEventListener('load', function () { 
	new PNotify({
                                  title: 'error',
                                  text: 'Wrong username/password combination',
								  nonblock: { nonblock: !0 },
                                  type: 'error',
                                  styling: 'bootstrap3'
                              }); }) </script>";
}

}

}

 

function create_db_sms($db,$smstype,$colmn,$phone,$smsdata)
{ 
 
$sqlnm = @mysqli_query($db,$jk="SELECT logid,group_id,name from member where phone_no='$phone'");  
while ($row = @mysqli_fetch_assoc($sqlnm))
	{  
	$firstname=$row['name'];
	$userid=$row['logid'];
	$group_id=$row['group_id'];
	}
	
	
$sqlnm = @mysqli_query($db,"SELECT $colmn as text FROM smssettings where logid='$smstype'");
while ($row = @mysqli_fetch_assoc($sqlnm))
	{  
	$text=$row['text'];
	}	
	
$sqlnm = mysqli_query($db,$gh="SELECT name from provider_group where logid='$group_id'");     $gh;
while ($row = mysqli_fetch_assoc($sqlnm))
	{  
	$gname=$row['name']; 
	}
		
$sms=str_replace("[1STNAME]",$firstname,$text);
$sms=str_replace("{NAME}",$firstname,$sms);
$sms=str_replace("{GROUPNAME}",$gname,$sms);
$sms=str_replace("[PAYMENT PLAN]",$smsdata,$sms);	
$sms=str_replace("[AMNT]",$smsdata,$sms);
$sms=str_replace("{AMOUNT}",$smsdata,$sms);
$sms=str_replace("{GROUPNAME}",$gname,$sms);
$sms=str_replace("1/2/3",$smsdata,$sms);

if($sms!='')
{
$success =@mysqli_query($db,"INSERT INTO sms SET  date='".date('Y-m-d')."',smstype='$smstype',phone='$phone',sms='$sms'"); 
if($success)  { } else { echo mysqli_error($db); }	
}
}

function create_db_sms_man($db,$sms,$phone)
{ 
 
$sqlnm = mysqli_query($db,"SELECT logid,firstname from users where phone='$phone'");
while ($row = mysqli_fetch_assoc($sqlnm))
	{  
	$firstname=$row['firstname'];
	$userid=$row['logid'];
	}
	
 
$success =@mysqli_query($db,"INSERT INTO sms SET  date='".date('Y-m-d')."',smstype='ma',phone='$phone',sms='$sms'"); 
if($success)  { } else { echo mysqli_error(); }	
 
}

 $success =@mysqli_query($conn,"UPDATE `mpesa`,`member` SET mpesa.`groupid`=member.`group_id`,mpesa.`memberid`=member.`logid`
WHERE mpesa.`phonenumber`=member.`phone_no`"); 
if($success)  { } else { echo mysqli_error($conn); }
	
$success =@mysqli_query($conn,"UPDATE `mpesa` SET ctype=1"); 
if($success)  { } else { echo mysqli_error($conn); }
 
 