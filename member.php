<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-22 16:00:23
 
// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBAL
global $title;
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

$page="member";

// Ensure variables are initialized to avoid undefined variable/index warnings
$part = isset($_GET['part']) ? $_GET['part'] : '';
$rec = isset($_GET['rec']) ? $_GET['rec'] : 0;
$username = isset($username) ? $username : (isset($_SESSION['username']) ? $_SESSION['username'] : 'System');
$deleted = 0;
$alert = '';
$error = '';

// SAFE CHECK: Only call workout() if it has been defined by your core system helper files
if (function_exists('workout')) {
    if (isset($_GET['D'])) { workout($_GET['D']); }
} else {
    if (!empty($_GET['D'])) {
        parse_str(base64_decode($_GET['D']), $output);
        foreach ($output as $key => $val) {
            $$key = $val;
        }
    }
}

$timestamp=date('Y-m-d H:i:s');

// SAFE CHECK: Define a fallback or check if caniacess exists to prevent Fatal Errors
if (function_exists('caniacess')) {
    if($part=='') { caniacess($page."_view"); }
    if($part=='1') { caniacess($page."_add"); }
    if($part=='2') { caniacess($page."_edit"); }
    if(isset($_POST['member_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop']==1)
{  
    include('stop.php'); 
}
else 
{
//---------------------------------------------------------------------------------------------------- 
// MEMBER SAVE / CREATE LOGIC
if(isset($_POST['member_save']))
{
    $group_id=ucwords(($_POST['group_id']));
    $name=ucwords(($_POST['name']));
    $phone_no=ucwords(($_POST['phone_no']));
    $nat_id=ucwords(($_POST['nat_id']));
    $dob=dateto_mysql($_POST['dob']);
    $gender=ucwords(($_POST['gender']));
    $medical_condition=ucwords(($_POST['medical_condition']));
    $caregiver_name=ucwords(($_POST['caregiver_name']));
    $caregiver_phone=ucwords(($_POST['caregiver_phone']));
    $registration_date=dateto_mysql($_POST['registration_date']);
    $active=ucwords(($_POST['active']));
    $activation_dt=dateto_mysql($_POST['activation_dt']);
    $deactivation_dt=isset($_POST['deactivation_dt']) ? dateto_mysql($_POST['deactivation_dt']) : '0000-00-00 00:00:00';
    $user_id=isset($_POST['user_id']) ? ucwords(($_POST['user_id'])) : 0;
    $created_by=(($username));
    $created_on=ucwords(($timestamp));
    $pin=isset($_POST['pin']) ? ucwords(($_POST['pin'])) : '';
    $pin2=isset($_POST['pin2']) ? ucwords(($_POST['pin2'])) : '';
    $tnc=isset($_POST['tnc']) ? ucwords(($_POST['tnc'])) : '';
    $pinchecked=isset($_POST['pinchecked']) ? ucwords(($_POST['pinchecked'])) : '';
    $ussdstage=isset($_POST['ussdstage']) ? ucwords(($_POST['ussdstage'])) : '';
    $showmenu=isset($_POST['showmenu']) ? ucwords(($_POST['showmenu'])) : '';
    $regfee=1;
    $isleader=isset($_POST['isleader']) ? $_POST['isleader'] : 0;
    
    // Safety check for sequential number generation
    $mnumber = 'M' . str_pad(function_exists('nextsequence') && $conn ? nextsequence($conn,'Member') : rand(1000,9999), 4, '0', STR_PAD_LEFT);

    if(trim($group_id)!=''&&trim($name)!=''&&trim($phone_no)!=''&&trim($nat_id)!=''&&trim($dob)!=''&&trim($gender)!=''&&trim($medical_condition)!=''&&trim($active)!='') { 
        $phone_no= "+254".trim(substr(str_replace(' ', '',$phone_no), -9));
        $sql = "INSERT INTO member (group_id,name,phone_no,nat_id,dob,gender,medical_condition,caregiver_name,caregiver_phone,registration_date,active,activation_dt,deactivation_dt,user_id,created_by,created_on,pin,pin2,tnc,pinchecked,ussdstage,showmenu,regfee,mnumber,isleader)
                VALUES ('$group_id','$name','$phone_no','$nat_id','$dob','$gender','$medical_condition','$caregiver_name','$caregiver_phone','$registration_date','$active','$activation_dt','$deactivation_dt','$user_id','$created_by','$created_on','$pin','$pin2','$tnc','$pinchecked','$ussdstage','$showmenu','$regfee','$mnumber','$isleader')";
        
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert=1; 
            if(function_exists('audittrail')) {
                audittrail($conn,'member','','inserting',$timestamp,$username); 
            }
        } else { 
            $error=("Error: you are duplicating data or system failure!"); 
            if($conn) { $error .= " " . $conn->error; } 
        }
    } else {  
        $error="Fill all mandatory fields in red.";  
    }  

    if($alert!='')
    {
        if(function_exists('create_db_sms')) { create_db_sms($conn,7,'member',$phone_no,''); }
        
        echo "<script> window.addEventListener('load', function () { 
        new PNotify({
                                      title: 'Success',
                                      text: 'Data saved successfully!',
									  nonblock: { nonblock: !0 },
                                      type: 'success',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
    if($error!='')
    {
        echo "<script> window.addEventListener('load', function () { 
        new PNotify({
                                      title: 'Error',
                                      text: '$error',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// MEMBER UPDATE LOGIC
if(isset($_POST['member_update']))
{
    $group_id=ucwords(($_POST['group_id']));
    $name=ucwords(($_POST['name']));
    $phone_no=ucwords(($_POST['phone_no']));
    $nat_id=ucwords(($_POST['nat_id']));
    $dob=dateto_mysql($_POST['dob']);
    $gender=ucwords(($_POST['gender']));
    $medical_condition=ucwords(($_POST['medical_condition']));
    $caregiver_name=ucwords(($_POST['caregiver_name']));
    $caregiver_phone=ucwords(($_POST['caregiver_phone']));
    $registration_date=dateto_mysql($_POST['registration_date']);
    $active=ucwords(($_POST['active']));
    $activation_dt=dateto_mysql($_POST['activation_dt']);
    $deactivation_dt=dateto_mysql($_POST['deactivation_dt']);
    $user_id=isset($_POST['user_id']) ? ucwords(($_POST['user_id'])) : 0;
    $pin=isset($_POST['pin']) ? ucwords(($_POST['pin'])) : '';
    $pin2=isset($_POST['pin2']) ? ucwords(($_POST['pin2'])) : '';
    $pinchecked=isset($_POST['pinchecked']) ? ucwords(($_POST['pinchecked'])) : '';
    $ussdstage=isset($_POST['ussdstage']) ? ucwords(($_POST['ussdstage'])) : '';
    $showmenu=isset($_POST['showmenu']) ? ucwords(($_POST['showmenu'])) : '';
    $logid=$_POST['logid'];
    $isleader=isset($_POST['isleader']) ? $_POST['isleader'] : 0;

    if(trim($group_id)!=''&&trim($name)!=''&&trim($phone_no)!=''&&trim($nat_id)!=''&&trim($dob)!=''&&trim($gender)!=''&&trim($medical_condition)!=''&&trim($active)!='') { 
        $phone_no= "+254".trim(substr(str_replace(' ', '',$phone_no), -9));

        $sql = "UPDATE  member SET  group_id='$group_id',name='$name',phone_no='$phone_no',nat_id='$nat_id',dob='$dob',gender='$gender',medical_condition='$medical_condition',caregiver_name='$caregiver_name',caregiver_phone='$caregiver_phone',registration_date='$registration_date',active='$active',activation_dt='$activation_dt',deactivation_dt='$deactivation_dt',user_id='$user_id',pin='$pin',pin2='$pin2',pinchecked='$pinchecked',ussdstage='$ussdstage',showmenu='$showmenu',isleader='$isleader' WHERE logid='$logid'";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert=1; 
            if(function_exists('audittrail')) { audittrail($conn,'member',$logid,'updatings',$timestamp,$username); }
        } else { $error=("Error: you are duplicating data!"); }
    } else {  $error="Fill all mandatory fields in red.";  }  

    if($alert!='')
    {
        echo "<script> window.addEventListener('load', function () { 
        new PNotify({
                                      title: 'Success',
                                      text: 'Data updated successfully!',
									  nonblock: { nonblock: !0 },
                                      type: 'success',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
    if($error!='')
    {
        echo "<script> window.addEventListener('load', function () { 
        new PNotify({
                                      title: 'Error',
                                      text: '$error',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// MEMBER DELETE LOGIC
if(isset($_POST['member_delete']))
{ 
    $count = isset($_POST['txtcount']) ? $_POST['txtcount'] : 0;
    for($i=0;$i<$count;$i++){ 
          if(!empty($_POST['del'][$i]))
    	  { 
    	      if(function_exists('audittrail')) {
                  audittrail($conn,'member',$_POST['del'][$i],'deleting',$timestamp,$username);
    	      }
    	  
              $sql = "DELETE FROM member WHERE logid='".$_POST['del'][$i]."'";
              if ($conn && $conn->query($sql) === TRUE) { $deleted=$deleted+1; }
    	  }  
    } 
    if ($deleted>0) { $alert=1; } else { $error="No record selected"; }
    if($alert!='')
    {
    	echo "<script> window.addEventListener('load', function () { 
    	new PNotify({
                                      title: 'Success',
                                      text: 'Deleted successfully',
									  nonblock: { nonblock: !0 },
                                      type: 'success',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
    if($error!='')
    {
    	echo "<script> window.addEventListener('load', function () { 
    	new PNotify({
                                      title: 'Error',
                                      text: '$error',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }	  
}

//---------------------------------------------------------------------------------------------------- 
if (function_exists('_title')) {
    _title($title='Member'); 
} ?>

<style>
  .custom-table-wrapper {
    width: 100% !important;
    overflow-x: auto !important;
    margin-top: 15px;
    background: #fff;
    border: 1px solid #e3e6f0;
    border-radius: 4px;
    padding: 10px;
  }
  .custom-table-wrapper table {
    width: 100% !important;
    max-width: none !important;
    white-space: nowrap !important;
  }
  .custom-table-wrapper th, .custom-table-wrapper td {
    padding: 10px 14px !important;
    vertical-align: middle !important;
    font-size: 13px !important;
  }
  .custom-table-wrapper th {
    background-color: #f8f9fc !important;
    color: #4e73df !important;
    font-weight: 700 !important;
  }
</style>

<form method='POST' action='' class='rounded' name='member' style='min-width:100%' enctype='multipart/form-data'>

<?php 
$role = isset($_SESSION['userrole']) ? $_SESSION['userrole'] : 0;

if($part=='1') { ?>

<div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                    <h2><?=ucwords($title);?><small>new entry</small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content  center-block'>
					
 
<div class="item form-group"><label for="group_id" class="col-sm-3 manda">Group id : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="group_id" name="group_id"  required='required'>
<?php if(isset($_POST['group_id']) && $_POST['group_id'] && $conn) { 
  $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group where logid='".$_POST['group_id']."'");
  while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
    <option value="<?php echo $drow['logid']?>"><?php echo $drow['name']?></option>
<?php } } ?>

<option value="">Select Group id</option>
<?php
if($role==4) {
	$group_id_query =" where logid='".(isset($_SESSION['groupid']) ? $_SESSION['groupid'] : 0)."' ";
} else {
	$group_id_query =" ";
}
if($conn) {
    $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group $group_id_query ORDER BY name");
    while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
        <option value="<?php echo $drow['logid']?>"><?php echo $drow['name']?></option>
<?php } } ?>
</select>
</div>
</div>

 <div class="item form-group">
<label for="name" class="col-sm-3 manda">Name : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="name" id="name" placeholder="Name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''?>" autocomplete="off">
</div>
</div>

 <div class="item form-group">
<label for="phone_no" class="col-sm-3 manda">Phone no : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="phone_no" id="phone_no" placeholder="Phone no" value="<?php echo isset($_POST['phone_no']) ? $_POST['phone_no'] : ''?>" autocomplete="off">
</div>
</div>

 <div class="item form-group">
<label for="nat_id" class="col-sm-3 manda">Nat id : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="nat_id" id="nat_id" placeholder="Nat id" value="<?php echo isset($_POST['nat_id']) ? $_POST['nat_id'] : ''?>" autocomplete="off">
</div>
</div>

<div class="item form-group"><label for="dob" class="col-sm-3 manda">Dob : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<?php echo date('Y-m-d')?>" name="dob" id="dob">
</div>
</div>

<div class="item form-group"><label for="gender" class="col-sm-3 manda">Gender : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="gender" name="gender"  required='required'>
<?php if(isset($_POST['gender']) && $_POST['gender']) { ?>
    <option value="<?php echo $_POST['gender']?>"><?php echo $_POST['gender']?></option>	
<?php } ?>
<option value="">Select Gender</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>
</select>
</div>
</div>

<div class="item form-group"><label for="medical_condition" class="col-sm-3 manda">Medical condition : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="medical_condition" name="medical_condition"  required='required'>
<?php if(isset($_POST['medical_condition']) && $_POST['medical_condition'] && $conn) { 
  $qresult = mysqli_query($conn,"SELECT logid,description FROM conditions where logid='".$_POST['medical_condition']."'");
  while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
    <option value="<?php echo $drow['logid']?>"><?php echo $drow['description']?></option>
<?php } } ?>

<option value="">Select Medical condition</option>
<?php
if($conn) {
    $qresult = mysqli_query($conn,"SELECT logid,description FROM conditions ORDER BY description");
    while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
        <option value="<?php echo $drow['logid']?>"><?php echo $drow['description']?></option>
<?php } } ?>
</select>
</div>
</div>

 <div class="item form-group">
<label for="caregiver_name" class="col-sm-3 ">Caregiver name : </label>
<div class="col-sm-8">
<input type="text"  class="form-control form-control-sm " name="caregiver_name" id="caregiver_name" placeholder="Caregiver name" value="<?php echo isset($_POST['caregiver_name']) ? $_POST['caregiver_name'] : ''?>" autocomplete="off">
</div>
</div>

 <div class="item form-group">
<label for="caregiver_phone" class="col-sm-3 ">Caregiver phone : </label>
<div class="col-sm-8">
<input type="text"  class="form-control form-control-sm " name="caregiver_phone" id="caregiver_phone" placeholder="Caregiver phone" value="<?php echo isset($_POST['caregiver_phone']) ? $_POST['caregiver_phone'] : ''?>" autocomplete="off">
</div>
</div>

<div class="item form-group"><label for="registration_date" class="col-sm-3 ">Registration date : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<?php echo date('Y-m-d')?>" name="registration_date" id="registration_date">
</div>
</div>

<div class="item form-group"><label for="isleader" class="col-sm-3 manda">Leader : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="isleader" name="isleader"  required='required'>
<?php if(isset($_POST['isleader']) && $_POST['isleader']) { ?>
    <option value="<?php echo $_POST['isleader']?>"><?php echo ($_POST['isleader']==1) ? 'Yes' : 'No'; ?></option>	
<?php } ?>
<option value="">Select</option>
<option value="1">Yes</option>
<option value="0">No</option>
</select>
</div>
</div>

<div class="item form-group"><label for="active" class="col-sm-3 manda">Active : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="active" name="active"  required='required'>
<?php if(isset($_POST['active']) && $_POST['active']) { ?>
    <option value="<?php echo $_POST['active']?>"><?php echo $_POST['active']?></option>	
<?php } ?>
<option value="">Select Active</option>
<option value="Yes">Yes</option>
<option value="No">No</option>
</select>
</div>
</div>

<div class="item form-group"><label for="activation_dt" class="col-sm-3 ">Activation dt : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<?php echo date('Y-m-d')?>" name="activation_dt" id="activation_dt">
</div>
</div>

<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">
 <button data-placement="bottom" title="Save data" type="submit" name="member_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 &nbsp; &nbsp;
 <a href="index.php?page=<?= $page ?>&part=1" data-placement="bottom" title="Add New Record">
 <button type="button" name="new" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>
 &nbsp; &nbsp;
 <a href="index.php?page=<?= $page ?>" data-placement="bottom" title="Go to records">
 <button type="button" name="exit" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>View</span>  <i class="fa fa-search custom"></i></button></a>
 &nbsp; &nbsp;
 <a href="index.php?page=<?= $page ?>" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-success btn-sm btn-info"><i class="fa fa-reply" style="float: none;"></i> &nbsp;<span>Back</span> </button></a>
</div>
</div>

</div>
</div>
</div>

<?php } elseif($part=='2') { ?>

<div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                    <h2><?=ucwords($title);?><small>update entry</small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content center-block'>

<?php 
$result = $conn ? mysqli_query($conn,"SELECT * FROM member WHERE logid='$rec'") : null;
while($result && $row = mysqli_fetch_assoc($result)) { ?> 
<div class="item form-group"><label for="group_id" class="col-sm-3 manda">Group id : </label>
<div class="col-sm-8">
<select class="form-control manda" id="group_id" name="group_id"  required='required'>
<?php $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group WHERE logid='".$row['group_id']."'");
while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
    <option value="<?php echo $drow['logid']?>"><?php echo $drow['name']?></option>
<?php } ?>
<option value="">Select Group id</option>
<?php
$qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group ORDER BY name");
while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
    <option value="<?php echo $drow['logid']?>"><?php echo $drow['name']?></option>
<?php } ?>
</select>
</div>
</div>

<div class="item form-group"><label for="name" class="col-sm-3 manda">Name : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="name" id="name" placeholder="Name" value="<?php echo $row['name']?>" autocomplete="off">
</div>
</div>

<div class="item form-group"><label for="phone_no" class="col-sm-3 manda">Phone no : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="phone_no" id="phone_no" placeholder="Phone no" value="<?php echo $row['phone_no']?>" autocomplete="off">
</div>
</div>

<div class="item form-group"><label for="nat_id" class="col-sm-3 manda">Nat id : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="nat_id" id="nat_id" placeholder="Nat id" value="<?php echo $row['nat_id']?>" autocomplete="off">
</div>
</div>

<div class="item form-group"><label for="dob" class="col-sm-3 manda">Dob : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<?php echo ($row['dob'])?>" name="dob" id="dob">
</div>
</div>
 
<div class="item form-group"><label for="gender" class="col-sm-3 manda">Gender : </label>
<div class="col-sm-8">
<select class="form-control manda" id="gender" name="gender"  required='required'>
<option value="<?php echo $row['gender']?>"><?php echo $row['gender']?></option>
<option value="">Select Gender</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>
</select>
</div>
</div>

<div class="item form-group"><label for="medical_condition" class="col-sm-3 manda">Medical condition : </label>
<div class="col-sm-8">
<select class="form-control manda" id="medical_condition" name="medical_condition"  required='required'>
<?php $qresult = mysqli_query($conn,"SELECT logid,description FROM conditions WHERE logid='".$row['medical_condition']."'");
while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
    <option value="<?php echo $drow['logid']?>"><?php echo $drow['description']?></option>
<?php } ?>
<option value="">Select Medical condition</option>
<?php
$qresult = mysqli_query($conn,"SELECT logid,description FROM conditions ORDER BY description");
while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
    <option value="<?php echo $drow['logid']?>"><?php echo $drow['description']?></option>
<?php } ?>
</select>
</div>
</div>

<div class="item form-group"><label for="caregiver_name" class="col-sm-3 ">Caregiver name : </label>
<div class="col-sm-8"> 
<input type="text"  class="form-control form-control-sm " name="caregiver_name" id="caregiver_name" placeholder="Caregiver name" value="<?php echo $row['caregiver_name']?>" autocomplete="off">
</div>
</div>

<div class="item form-group"><label for="caregiver_phone" class="col-sm-3 ">Caregiver phone : </label>
<div class="col-sm-8"> 
<input type="text"  class="form-control form-control-sm " name="caregiver_phone" id="caregiver_phone" placeholder="Caregiver phone" value="<?php echo $row['caregiver_phone']?>" autocomplete="off">
</div>
</div>

<div class="item form-group"><label for="registration_date" class="col-sm-3 ">Registration date : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<?php echo ($row['registration_date'])?>" name="registration_date" id="registration_date">
</div>
</div>
 
 <div class="item form-group"><label for="isleader" class="col-sm-3 manda">Leader : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="isleader" name="isleader"  required='required'>
<option value="<?php echo $row['isleader']?>"><?php echo ($row['isleader']==1) ? 'Yes' : 'No'; ?></option>	
<option value="">Select</option>
<option value="1">Yes</option>
<option value="0">No</option>
</select>
</div>
</div>

<div class="item form-group"><label for="active" class="col-sm-3 manda">Active : </label>
<div class="col-sm-8">
<select class="form-control manda" id="active" name="active"  required='required'>
<option value="<?php echo $row['active']?>"><?php echo $row['active']?></option>
<option value="">Select Active</option>
<option value="Yes">Yes</option>
<option value="No">No</option>
</select>
</div>
</div>

<div class="item form-group"><label for="activation_dt" class="col-sm-3 ">Activation dt : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<?php echo $row['activation_dt'] ?>" name="activation_dt" id="activation_dt">
</div>
</div>
 
<div class="item form-group"><label for="deactivation_dt" class="col-sm-3 ">Deactivation dt : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<?php if($row['deactivation_dt']!='0000-00-00 00:00:00' && !empty($row['deactivation_dt'])) { echo date('Y-m-d', strtotime($row['deactivation_dt'])); }?>" name="deactivation_dt" id="deactivation_dt">
</div>
</div>
 
<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">
 <button data-placement="bottom" title="Update data" type="submit" name="member_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>
 &nbsp; &nbsp; 
 <a href="index.php?page=<?= $page ?>&part=1" data-placement="bottom" title="Add New Record">
 <button type="button" name="new" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>
 &nbsp; &nbsp; 
 <a href="index.php?page=<?= $page ?>" data-placement="bottom" title="Go to records">
 <button type="button" name="exit" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>View</span>  <i class="fa fa-search custom"></i></button></a>
  &nbsp; &nbsp;
 <a href="index.php?page=<?= $page ?>" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-success btn-sm"><i class="fa fa-reply" style="float: none;"></i> &nbsp;<span>Back</span>  </button></a>
</div>
</div>

</div> 
<input name="logid" type="hidden" value="<?=$row['logid']?>" />
 <?php } ?> 

</div>
</div>

<?php } else { ?>

<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
                  <div class="x_title">
                    <h2><?=ucwords($title);?><small> existing records</small></h2>
					<ul class="nav navbar-right panel_toolbox">
                     <li><a href="index.php?page=<?= $page ?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Create New</a></li>
                     </ul>
					<div class="clearfix"></div>
                  </div>
<div class="x_content">

<div class="custom-table-wrapper">
<table id="datatable" role="grid" class="table table-striped table-bordered dataTable table-sm">
<thead>
<tr>
<th width="1px">MemberNo.</th>
<th>Member Ref</th>
<th>Group Id</th>
<th>Name</th>
<th>Phone No</th>
<th>Gender</th>
<th>Medical Condition</th>
<th>Caregiver Name</th>
<th>Caregiver Phone</th>
<th>Registration Date</th>
<th>Active</th>
<th align="center">Rx</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
  <th>MemberNo.</th>
  <th>Member Ref</th>
<th>Group Id</th>
<th>Name</th>
<th>Phone No</th>
<th>Gender</th>
<th>Medical Condition</th>
<th>Caregiver Name</th>
<th>Caregiver Phone</th>
<th>Registration Date</th>
<th>Active</th>
<th  align="center">Rx</th>
<th></th>
<th>
<button name="member_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>
</th> 
</tr>
</tfoot>
<tbody>
<?php $mycount=0;
if($role==4) {
	$where=" and group_id='".(isset($_SESSION['groupid']) ? $_SESSION['groupid'] : 0)."' ";
} else {
	$where=" ";
}

if(isset($_POST['tbn_search']) && $conn) {
    $search=$conn->real_escape_string($_POST['search']);
    $result = @mysqli_query($conn,"SELECT * FROM member where group_id>0 $where and (group_id like '%$search%' OR name like '%$search%' OR phone_no like '%$search%' OR nat_id like '%$search%' OR dob like '%$search%' OR gender like '%$search%' OR medical_condition like '%$search%' OR caregiver_name like '%$search%' OR caregiver_phone like '%$search%' OR registration_date like '%$search%' OR active like '%$search%' OR activation_dt like '%$search%' OR deactivation_dt like '%$search%' OR created_by like '%$search%' OR created_on like '%$search%')");
} else {
    $result = $conn ? @mysqli_query($conn,"SELECT * FROM member $where") : null;
}

if($result && mysqli_num_rows($result) > 0) {
    while($row = @mysqli_fetch_assoc($result)) { 
        $mycount=$mycount+1; ?> 				  
        <tr>
        <td><?=$row['logid']?></td>
        <td><a href="index.php?page=member_profile&id=<?=$row['logid']?>"><?=$row['mnumber']?></a></td>
        <td><?php
        $qresult = $conn ? mysqli_query($conn,"SELECT name FROM provider_group WHERE logid='".$row['group_id']."'") : null;
        while($qresult && $drow = mysqli_fetch_assoc($qresult)) {  
            echo htmlspecialchars($drow['name']);  
        } ?></td>
        <td><?php echo htmlspecialchars($row['name'])?></td>
        <td><?php echo htmlspecialchars($row['phone_no'])?></td>
        <td><?php echo htmlspecialchars($row['gender'])?></td>
        <td><?php
        $qresult = $conn ? mysqli_query($conn,"SELECT description FROM conditions WHERE logid='".$row['medical_condition']."'") : null;
        while($qresult && $drow = mysqli_fetch_assoc($qresult)) {  
            echo htmlspecialchars($drow['description']);  
        } ?></td>
        <td><?php echo htmlspecialchars($row['caregiver_name'])?></td>
        <td><?php echo htmlspecialchars($row['caregiver_phone'])?></td>
        <td><?php echo htmlspecialchars($row['registration_date'])?></td>
        <td><?php echo htmlspecialchars($row['active'])?></td>
        <td align="center"><a href="index.php?page=prescription&part=1&m=<?=$row['logid']?>&g=<?=$row['group_id']?>"><i class="fa fa-user-md text-info"></i></a></td>
        <td align="center"><a href="index.php?page=<?= $page ?>&part=2&rec=<?=$row['logid']?>" data-placement="bottom" title="Go to edit"><i class="fa fa-lg fa-pencil text-primary"></i></a></td>
        <td align="center"><input name="del[]" id="del[]" type="checkbox" value="<?=$row['logid']?>" /></td>
        </tr>
<?php } 
} else { ?>
    <tr>
        <td colspan="14" class="text-center" style="padding: 30px; color: #73879C; font-size: 14px;">
            <i class="fa fa-info-circle fa-lg"></i> No member records found. Click <a href="index.php?page=<?= $page ?>&part=1" class="alert-link"><u>Create New</u></a> to get started.
        </td>
    </tr>
<?php } ?>
</tbody>
</table>
</div> <input type="hidden" name="txtcount" id="txtcount" value="<?php echo $mycount;?>"> 
</div>
 
</div>
</div>
<?php } ?>

</form>
<?php } ?>