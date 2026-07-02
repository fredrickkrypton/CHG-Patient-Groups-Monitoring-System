<? include("mobiconfig.php");

function substrAfter($str, $last) {
    $startPos = strrpos($str, $last);
    if ($startPos !== false) {
        $startPos++;
        return ($startPos < strlen($str)) ? substr($str, $startPos) : '';
    }
    return $str;
}
$USSDCON="CON ";

$newsession = $_POST["sessionId"];$serviceCode = $_POST["serviceCode"];$phone = $_POST["phoneNumber"];$response = $_POST["text"];
 
//$data = json_decode(file_get_contents('php://input'), true); $response=$data["input"];$phone=$data["mobile"]; $activate=1; $newsession=111; 

$response=substrAfter($response,'*');
$response=trim($response);

$response=str_replace(' ','', $response);


$active="";
if($response<1)
{
	  'b1';
//update_sessions($db,$phone,"ussdstage",'0');
//update_sessions($db,$phone,"showmenu",'0');
update_sessions($db,$phone,"xamount",'0');
}
$sqlnmx = mysqli_query($db,$ch="SELECT * FROM member where  phone_no='$phone'");
$ch;
while ($rc = mysqli_fetch_assoc($sqlnmx))
	{
	$active=$rc['active'];
	$ussdstage=$rc['ussdstage'];
	$showmenu=$rc['showmenu'];
	$pin=$rc['pin'];
	$pin2=$rc['pin2'];
	$tnc=$rc['tnc'];
	$pinchecked=$rc['pinchecked'];
	$regfee=$rc['regfee'];
	
	$xname=$rc['xname'];
	$xdob=$rc['xdob'];
	$xnat_id=$rc['xnat_id'];
	$xgender=$rc['xgender'];
	$xphone=$rc['xphone'];
	$xcondition=$rc['xcondition'];
	$myname=$rc['name'];
	$created_by=$rc['created_by'];
	$group_id=$rc['group_id'];
	$xamount=$rc['xamount'];
	
	if($rc['isleader']==1) { $isleader="\n4: Register member"; $maxmenu=4; } else { $isleader=''; $maxmenu=3; } 
	
	}

if($ussdstage==0&&$response<1&&$showmenu==0)
{
	    'b1';
update_sessions($db,$phone,"ussdstage",'0');
update_sessions($db,$phone,"showmenu",'0');
update_sessions($db,$phone,"xamount",'0');
update_sessions($db,$phone,"xname",'');
update_sessions($db,$phone,"xdob",'');
update_sessions($db,$phone,"xnat_id",'');
update_sessions($db,$phone,"xgender",'');
update_sessions($db,$phone,"xphone",'');
update_sessions($db,$phone,"xcondition",'');

} else
if($ussdstage>=1&&$response<1&&$showmenu==0)
{
	   'c1';
update_sessions($db,$phone,"ussdstage",'0');
update_sessions($db,$phone,"showmenu",'0');
update_sessions($db,$phone,"xamount",'0');
 
$ussdstage=0;$response=0;
}	
else if($ussdstage>=1&&$response<1&&$showmenu<4&&$xamount==0)
{	     'c2';
update_sessions($db,$phone,"ussdstage",'1');
update_sessions($db,$phone,"showmenu",'0');
update_sessions($db,$phone,"xamount",'0');
$ussdstage=0;$response=0;$showmenu=0;
}
else if($ussdstage==1&&$response>3&&$showmenu==1&&$xamount==0)
{
		    'c3';
update_sessions($db,$phone,"ussdstage",'1');
update_sessions($db,$phone,"showmenu",'0');
update_sessions($db,$phone,"xamount",'0');
$ussdstage=0;$response=0;$showmenu=0;
}
else if($ussdstage==4&&$response=='0'&&$showmenu==4)
{
		  'c4';
update_sessions($db,$phone,"ussdstage",'1');
update_sessions($db,$phone,"showmenu",'0');
update_sessions($db,$phone,"xamount",'0');
update_sessions($db,$phone,"xname",'');
update_sessions($db,$phone,"xdob",'');
update_sessions($db,$phone,"xnat_id",'');
update_sessions($db,$phone,"xgender",'');
update_sessions($db,$phone,"xphone",'');
update_sessions($db,$phone,"xcondition",'');
$ussdstage=0;$response=0;$showmenu=0;
} 
   
 

 if($active==''||$active=='No')
 {
$menu="You are not registered to use to this service. \n";	
  } else {

if($ussdstage==0&&$tnc==0&&$response<=2)
{
if($tnc==0&&$response<1)
{
$menu=$terms."\n1: Accept \n2: Don't accept";
update_sessions($db,$phone,"ussdstage",'0');

}
 else if($tnc==0&&$response==1)
{
$menu="Welcome to Pochi La Afya.";	
update_sessions($db,$phone,"tnc",'1');
$USSDCON="END ";
$hidebackhome=1;
if($isleader!='') {
 create_db_sms($db,1,'gleader',$phone,'');
} else {
 create_db_sms($db,1,'member',$phone,'');
}
 
update_sessions($db,$phone,"activation_dt",$timestamp);
update_sessions($db,$phone,"showmenu",0);
update_sessions($db,$phone,"ussdstage",'1');
	
}
else if($tnc==0&&$response==2)
{
$menu="Thank you for participating";	
update_sessions($db,$phone,"ussdstage",'0');
$USSDCON="END ";	
}
 	
} else { 

if($ussdstage==0&&$response<1&&$showmenu==0&&$xamount==0)
{
$menu="1: Make payments \n2: Get help \n3: Terms & Conditions".$isleader;	
update_sessions($db,$phone,"showmenu",0);
update_sessions($db,$phone,"ussdstage",1);	
}
elseif($ussdstage==1&&$response==2&&$showmenu==0&&$xamount==0)
{
$menu="call $helpline";	
update_sessions($db,$phone,"showmenu",0); 	
}
elseif($ussdstage==1&&$response==3&&$showmenu==0&&$xamount==0)
{
$menu=$terms;	
update_sessions($db,$phone,"showmenu",0); 	
}
else
if($ussdstage==1&&$response==1&&$showmenu==0&&$xamount==0)
{
if($regfee==1)
{
$menu="1: Monthly contribution fee \n2: Get help \n3: Terms & Conditions";
$nicontri=1;
} else {
$menu="1: Monthly contributio \n2: Get help \n3: Terms & Conditions";
$nicontri=0;
update_sessions($db,$phone,"xamount",1);
}
update_sessions($db,$phone,"showmenu",1);

 
}

else
if($ussdstage==1&&$response==1&&$showmenu==1&&$xamount==0)
{
$menu="Enter Amount fee";
update_sessions($db,$phone,"showmenu",1);
update_sessions($db,$phone,"xamount",1);

}


else if($ussdstage==1&&$showmenu==1&&$response>0&&$xamount==1)
{
	if($regfee==0)
{ $response=500; $txre=' for Registration'; }

$menu="You are about to pay $response/- $txre\n1: Confirm \n2: Cancel";	
update_sessions($db,$phone,"showmenu",2);

update_sessions($db,$phone,"collect",$response);
}
else if($ussdstage==1&&$showmenu==2&&$response==1&&$xamount==1)
{
$USSDCON="END ";	
$menu="Wait for Mpesa transaction";	
update_sessions($db,$phone,"showmenu",0);
update_sessions($db,$phone,"ussdstage",0);
update_sessions($db,$phone,"xamount",0); 

$accountref="PHARMACCESS FOUNDATION";
if($nicontri==0)
{ $descr="Pochi La Afya Monthly contribution fee"; } else { $descr="Pochi La Afya Monthly contribution"; }
update_sessions($db,$phone,"collecttxt",$descr);
}
else if($ussdstage==1&&$showmenu==2&&$response==2&&$xcondition!='')
{
$USSDCON="END ";	
$menu="Cancelled";	
update_sessions($db,$phone,"showmenu",0);
update_sessions($db,$phone,"ussdstage",0); 
update_sessions($db,$phone,"xamount",0);
update_sessions($db,$phone,"collecttxt",'');
update_sessions($db,$phone,"collect",0);
}
elseif($ussdstage==1&&$showmenu==1&&$response==2)
{
$menu="call $helpline";	
update_sessions($db,$phone,"showmenu",0);
 }
elseif($ussdstage==1&&$showmenu==1&&$response==3)
{
$menu=$terms;
update_sessions($db,$phone,"showmenu",0);
	
 }

elseif($ussdstage==1&&$response==4&&$maxmenu==4)
{
$hidebackhome=0;	
$menu="Enter Member Name";
update_sessions($db,$phone,"showmenu",4);
update_sessions($db,$phone,"ussdstage",4);
}
elseif($ussdstage==4&&$xname==''&&$maxmenu==4)
{
$hidebackhome=0;	
$menu="Enter date of birth (d.m.y)";
update_sessions($db,$phone,"xname",$response);
}
elseif($ussdstage==4&&$xdob==''&&$maxmenu==4)
{
$hidebackhome=0;	
$menu="Enter Gender (M/F/O)";

$date=date_to_normal($response);

if(dateto_mysql($date)=='1970-01-01')
{
	$menu="Enter date of birth (d.m.y)";
}else {
update_sessions($db,$phone,"xdob",dateto_normal($response));
}
}
elseif($ussdstage==4&&$xgender==''&&$maxmenu==4)
{
$hidebackhome=0;	
$menu="Enter National ID Number";
update_sessions($db,$phone,"xgender",$response);
}
elseif($ussdstage==4&&$xnat_id==''&&$maxmenu==4)
{
$hidebackhome=0;	
$menu="Enter phone to be used";
update_sessions($db,$phone,"xnat_id",$response);
}
elseif($ussdstage==4&&$xphone==''&&$maxmenu==4)
{
$hidebackhome=0;	
if(strlen(str_replace(' ', '',$response))>8)
{
$response= "+254".trim(substr(str_replace(' ', '',$response), -9));
$menu="Medical Condition\n1. HTN \n2. DM \n3. None \n4. Both";
update_sessions($db,$phone,"xphone",$response);
} else {
	
$menu="Enter phone to be used";	
}
}
elseif($ussdstage==4&&$xcondition==''&&$maxmenu==4)
{
$hidebackhome=0;	

/*if($response==1)
	{ 	$response='HTN';
	 } else if($response==2) {
		$response='DM';
	 } else if($response==3) {
		$response='None';
	}  else {
		$response='Both';
	}
	*/
$menu="Member details \nName:$xname,\nDOB:$xdob,Gender:$xgender, \nIDNo:$xnat_id, \nPhoneNo:$xphone \nCondition:$response \n1: Confirm \n2: Cancel";
update_sessions($db,$phone,"xcondition",$response);

}
elseif($ussdstage==4&&$response==1&&$maxmenu==4)
{
$hidebackhome=0;
$menu="Successful \n";
update_sessions($db,$phone,"ussdstage",'1');
update_sessions($db,$phone,"showmenu",'0');
update_sessions($db,$phone,"xname",'');
update_sessions($db,$phone,"xdob",'');
update_sessions($db,$phone,"xnat_id",'');
update_sessions($db,$phone,"xgender",'');
update_sessions($db,$phone,"xphone",'');
update_sessions($db,$phone,"xcondition",'');

$mnumber='M'.str_pad(nextsequence($db,'Member'),4, '0', STR_PAD_LEFT);

if(strtolower($xgender)=='m') { $xgender='Male'; }  else if(strtolower($xgender)=='f') { $xgender='Female'; }  else { $xgender='other'; }
$xdob=dateto_mysql($xdob);
$xname=ucwords(strtolower($xname));
$success =mysqli_query($db,"INSERT INTO member SET mnumber='$mnumber',name='$xname',phone_no='$xphone',medical_condition='$xcondition',
nat_id='$xnat_id',dob='$xdob',gender='$xgender',regfee='1',
caregiver_name='',caregiver_phone='',registration_date='".date('Y-m-d')."',
active='Yes',group_id='$group_id',user_id='$user_id',created_by='$created_by',created_on='$timestamp'"); 
if($success)  { 
  
 create_db_sms($db,7,'member',$xphone,'');  
    
} else { $menu='Member phone exist, record not saved '.mysqli_error($db); 
}

}
elseif($ussdstage==4&&$response>1&&$maxmenu==4)
{
$hidebackhome=0;	
$menu="Cancelled \n";
update_sessions($db,$phone,"ussdstage",'1');
update_sessions($db,$phone,"showmenu",'0');
update_sessions($db,$phone,"xname",'');
update_sessions($db,$phone,"xdob",'');
update_sessions($db,$phone,"xnat_id",'');
update_sessions($db,$phone,"xgender",'');
update_sessions($db,$phone,"xphone",'');
update_sessions($db,$phone,"xcondition",'');
}
}
 }
  
if($response=='0'||$response=='')
{ 
$hidebackhome=1; 
}
 
 
header('Content-type: text/plain');	
if($hidebackhome==1){
	$fmenu= $USSDCON.$menu;
	 } else {		
$fmenu= $USSDCON.$menu."\n0. Back to Main Menu"; 
}
echo $fmenu;

?>