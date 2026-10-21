<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set("Africa/Nairobi"); 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$Appnameversion=" v.1.0.0 ™ <a href='http://innovee.co.ke'> Innovee</a>";
$Appurl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$timestamp=date('Y-m-d H:i:s');

$_SESSION['conn'] = $conn = mysqli_connect('127.0.0.1', 'root', '', 'afya', 3306);
if (!$conn) {
    die('CONNECTION FAILED: ' . mysqli_connect_error());
} else {
    echo '';
}
 //on session creation
$_SESSION['timestamp']=time();

if (isset($_GET['page'])) {
    $page = $_GET['page'];
} else {
    $parts = explode('/', $Appurl);
    $last_part = $parts[count($parts) - 1];
    $parts = explode("&", $last_part, 2);
    $page = $parts[0];
}

// FIX: Safe array verification for "logout" key check
if(isset($_GET['logout']))
{
    session_destroy();
    session_unset();
    header("Location: index.php");
    exit();
}

// FIX: Avoid passing null values to string mutation operations
function osteens($input) {
    return strtr(base64_encode($input ?? ''), '+/=', '-_,');
}
function base64_url_encode($input) {
    return strtr(base64_encode($input ?? ''), '+/=', '-_,');
}
function base64_url_decode($input) {
    return base64_decode(strtr($input ?? '', '-_,', '+/='));
}

// FIX: Protected array key boundary extractions for dynamic parameters
function workout($D){
    global $part, $rec;
    $decoded = base64_url_decode($D ?? '');
    $teile = explode("&", $decoded);
    
    $part_arr = isset($teile[0]) ? explode("=", $teile[0]) : [];
    $part = isset($part_arr[1]) ? $part_arr[1] : '';
    
    $rec_arr = isset($teile[1]) ? explode("=", $teile[1]) : [];
    $rec = isset($rec_arr[1]) ? $rec_arr[1] : '';
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
 
    if($imageFileType == "php" || $imageFileType == "exe" || $imageFileType == "bat" ) {
        $uploadOk = 0;
    }
 
    if(basename($_FILES[$txtfieldname]["name"])=='')
    {
        $uploadOk=0;	
    } else {
        if (isset($_POST['hiddenfile'])) {
            @unlink("uploads/".$_POST['hiddenfile']);	
        }
    }
    
    if ($uploadOk == 0) {
        $errorupload=1;
    } else {
        if (move_uploaded_file($_FILES[$txtfieldname]["tmp_name"], $target_file)) {
             // File upload success
        } else {
            $nofilesattahced=1;
        }
    }
}
 
function _title($title){
    if($title=='') { 
        $title = isset($_SESSION['companyname']) ? $_SESSION['companyname'] : 'System Dashboard'; 
    }
    echo "<title>".htmlspecialchars($title)."</title>";	
}

function audittrail($conn,$table,$id,$action,$timestamp,$username){
    $sql = "INSERT INTO audittrail (tablename,id,action,timestamp,username)
    VALUES ('$table','$id','$action','$timestamp','$username')";
    if ($conn->query($sql) === TRUE) { }	
} 

function get_access($conn,$roleid)
{
    $_SESSION['allaccess']='';	
    $qresultv = mysqli_query($conn,"SELECT roles_pages.filename,roles_access.view,roles_access.canadd,roles_access.canedit,roles_access.candelete FROM roles_pages,roles_access WHERE roles_pages.logid=roles_access.pages AND roles_access.role='$roleid'");
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
    $access = "";
    $qresultv = mysqli_query($conn,"SELECT * FROM roles_access where role='$roleid' AND pages='$pages'");
    while($drow = mysqli_fetch_assoc($qresultv))
    { 
        $access=$drow['view'].",".$drow['canadd'].",".$drow['canedit'].",".$drow['candelete']; 
    }	
    return $access;
}

// FIX: Defined scope context for access alerts to eliminate undefined variable notice
function caniacess($page)
{
    $caniacess = ''; 
    $session_access = isset($_SESSION['allaccess']) ? $_SESSION['allaccess'] : '';
    
    if (preg_match("/\b".$page."\b/i", $session_access)) {
        $_SESSION['stop']=0;
    } else {
        $page=str_replace('_',' ',$page);	
        // SAFE FALLBACK: Checks if PNotify is available. If not, uses standard browser alert.
        $caniacess= "<script> window.addEventListener('load', function () { 
            if (typeof PNotify !== 'undefined') {
                new PNotify({
                    title: 'Access error',
                    text: 'You do not have access to the $page page!',
                    nonblock: { nonblock: !0 },
                    type: 'error',
                    styling: 'bootstrap3'
                }); 
            } else {
                alert('Access error: You do not have access to the " . htmlspecialchars($page) . " page!');
            }
        });</script>";
        $_SESSION['stop']=1;							  
    }
    echo $caniacess;
}
 
// FIX: Protected application assignment keys fallback setup
$username = isset($_SESSION['userusername']) ? $_SESSION['userusername'] : 'System'; 
$Appname = isset($_SESSION['companyname']) ? $_SESSION['companyname'] : 'Pochi La Afya'; 
 
function nextsequence($conn,$prefix)
{
    $header=$prefix;
    $keyvalue = 0;
    $sqlnm = @mysqli_query($conn,"SELECT keyname,keyvalue FROM idkeys WHERE  keyname='$header'");
    while ($row = mysqli_fetch_array($sqlnm))
    { 
        $keyvalue=$row['keyvalue'];
    }
    $nextkey=$keyvalue+1;
    if($keyvalue == 0 || $keyvalue == '')
    { 
        @mysqli_query($conn,"INSERT INTO idkeys SET  keyname='$header',keyvalue='$nextkey'"); 
    }
    else
    {
        @mysqli_query($conn,"UPDATE idkeys SET keyvalue='$nextkey' where  keyname='$header'"); 
    } 
    return $nextkey;	
}
$mylimit=10;

function return_tb($db,$table,$field,$field2,$field2value)
{
	$record = '';
	if($table != '' && $field != '' && $field2 != '' && $field2value != '')
	{
       $sql=@mysqli_query($db,"Select $field from $table where $field2='$field2value'");
       while($row=@mysqli_fetch_array($sql))
       {
           $record = $row[$field];
       }
	}
	return $record;
}

// FIX: Handled "login" index detection logic safely
if(isset($_POST['login']))
{
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $errors = 0;

    if (empty($username)) {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({
                title: 'error',
                text: 'Username is required',
                nonblock: { nonblock: !0 },
                type: 'error',
                styling: 'bootstrap3'
            }); }) </script>";
        $errors++;
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
        $errors++;
    }
 
    if ($errors == 0) {
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
                    
                    $insertSQLn = "UPDATE 0_users SET lastlogin='$timestamp' WHERE username='$username'"; 
                    if(@mysqli_query($conn,$insertSQLn)){}
                }
                $_SESSION['systemexpiry'] = time();
            }

            echo "<script> window.addEventListener('load', function () { 
                new PNotify({
                    title: 'Success',
                    text: 'Welcome back Admin',
                    nonblock: { nonblock: !0 },
                    type: 'success',
                    styling: 'bootstrap3'
                }); }) </script>";
        } else {
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
    $firstname = "";
    $gname = "";
    $text = "";
    $group_id = "";
 
    $sqlnm = @mysqli_query($db,"SELECT logid,group_id,name from member where phone_no='$phone'");  
    while ($row = @mysqli_fetch_assoc($sqlnm))
	{  
        $firstname=$row['name'];
        $group_id=$row['group_id'];
	}
	
    $sqlnm = @mysqli_query($db,"SELECT $colmn as text FROM smssettings where logid='$smstype'");
    while ($row = @mysqli_fetch_assoc($sqlnm))
	{  
        $text=$row['text'];
	}	
	
    $sqlnm = mysqli_query($db,"SELECT name from provider_group where logid='$group_id'");
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
        $success =@mysqli_query($db,"INSERT INTO sms SET date='".date('Y-m-d')."',smstype='$smstype',phone='$phone',sms='$sms'"); 
        if(!$success) { echo mysqli_error($db); }	
    }
}

function create_db_sms_man($db,$sms,$phone)
{ 
    $success =@mysqli_query($db,"INSERT INTO sms SET date='".date('Y-m-d')."',smstype='ma',phone='$phone',sms='$sms'"); 
    if(!$success) { echo mysqli_error($db); }	
}

$success =@mysqli_query($conn,"UPDATE `mpesa`,`member` SET mpesa.`groupid`=member.`group_id`,mpesa.`memberid`=member.`logid` WHERE mpesa.`phonenumber`=member.`phone_no`"); 
if(!$success) { echo mysqli_error($conn); }
	
$success =@mysqli_query($conn,"UPDATE `mpesa` SET ctype=1"); 
if(!$success) { echo mysqli_error($conn); }