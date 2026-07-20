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
    // FIX DEPRECATION WARNINGS: Added null fallbacks ('') to prevent ucwords(null) exceptions
    $group_id=ucwords(($_POST['group_id'] ?? ''));
    $name=ucwords(($_POST['name'] ?? ''));
    $phone_no=ucwords(($_POST['phone_no'] ?? ''));
    $nat_id=ucwords(($_POST['nat_id'] ?? ''));
    $dob=dateto_mysql($_POST['dob'] ?? '');
    $gender=ucwords(($_POST['gender'] ?? ''));
    $medical_condition=ucwords(($_POST['medical_condition'] ?? ''));
    $caregiver_name=ucwords(($_POST['caregiver_name'] ?? ''));
    $caregiver_phone=ucwords(($_POST['caregiver_phone'] ?? ''));
    $registration_date=dateto_mysql($_POST['registration_date'] ?? '');
    $active=ucwords(($_POST['active'] ?? ''));
    $activation_dt=dateto_mysql($_POST['activation_dt'] ?? '');
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
            $part = '';
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
    // FIX DEPRECATION WARNINGS: Added null fallbacks ('') to prevent ucwords(null) exceptions
    $group_id=ucwords(($_POST['group_id'] ?? ''));
    $name=ucwords(($_POST['name'] ?? ''));
    $phone_no=ucwords(($_POST['phone_no'] ?? ''));
    $nat_id=ucwords(($_POST['nat_id'] ?? ''));
    $dob=dateto_mysql($_POST['dob'] ?? '');
    $gender=ucwords(($_POST['gender'] ?? ''));
    $medical_condition=ucwords(($_POST['medical_condition'] ?? ''));
    $caregiver_name=ucwords(($_POST['caregiver_name'] ?? ''));
    $caregiver_phone=ucwords(($_POST['caregiver_phone'] ?? ''));
    $registration_date=dateto_mysql($_POST['registration_date'] ?? '');
    $active=ucwords(($_POST['active'] ?? ''));
    $activation_dt=dateto_mysql($_POST['activation_dt'] ?? '');
    $deactivation_dt=dateto_mysql($_POST['deactivation_dt'] ?? '');
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
            $part = '';
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

<!-- Native Styling Uniformity Integration Layer -->
<style>
    .chg-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: #ffffff;
        border-radius: 6px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-top: 10px;
    }
    .chg-view-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #eef2f5;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }
    .chg-view-title {
        font-size: 18px;
        color: #2c3e50;
        margin: 0;
        font-weight: 600;
    }
    .chg-blue-btn {
        background-color: #1ea7e1 !important;
        color: #ffffff !important;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: bold;
        border-radius: 4px;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        cursor: pointer;
    }
    .chg-blue-btn:hover {
        background-color: #1586b7 !important;
        color: #ffffff !important;
    }
    .chg-data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        border: 1px solid #dddddd;
    }
    .chg-data-table th {
        background-color: #f2f5f8;
        color: #1ea7e1 !important; 
        font-weight: bold;
        padding: 12px 10px;
        border: 1px solid #dddddd;
        font-size: 13px;
    }
    .chg-data-table td {
        padding: 10px;
        border: 1px solid #dddddd;
        font-size: 13px;
        color: #444444;
    }
    .custom-table-wrapper {
        width: 100% !important;
        overflow-x: auto !important;
        margin-top: 15px;
    }
    /* Style form field label names to match standard system blue */
    .manda, .chg-form-label {
        font-weight: bold;
        font-size: 13px;
        color: #1ea7e1 !important;
        display: block;
        margin-bottom: 5px;
    }
    /* Centering Layer for Form View Content */
    .chg-form-center-wrapper {
        max-width: 550px;
        margin: 20px auto;
        padding: 25px;
        background: #fbfcfd;
        border: 1px solid #eef2f5;
        border-radius: 8px;
    }
    /* Custom Modern Inputs and Select Dropdowns Styling Layer */
    .chg-form-center-wrapper .form-control,
    .chg-form-center-wrapper input[type="text"],
    .chg-form-center-wrapper input[type="date"],
    .chg-form-center-wrapper select {
        width: 100% !important;
        height: 38px !important;
        padding: 6px 12px !important;
        font-size: 13px !important;
        color: #333333 !important;
        background-color: #ffffff !important;
        border: 1px solid #cccccc !important;
        border-radius: 4px !important;
        box-shadow: inset 0 1px 1px rgba(0,0,0,0.075) !important;
        transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s !important;
        box-sizing: border-box !important;
    }
    .chg-form-center-wrapper .form-control:focus,
    .chg-form-center-wrapper input:focus,
    .chg-form-center-wrapper select:focus {
        border-color: #1ea7e1 !important;
        outline: 0 !important;
        box-shadow: inset 0 1px 1px rgba(0,0,0,0.075), 0 0 6px rgba(30,167,225,0.4) !important;
    }
</style>

<div class="chg-container">
    <div class="chg-view-header">
        <h2 class="chg-view-title">
            <?= htmlspecialchars(ucwords($title ?? '')); ?>
            <span style="font-size: 13px; color: #7f8c8d; font-weight: normal; margin-left: 5px;">
                <?php echo ($part=='1') ? '» New Entry' : (($part=='2') ? '» Update Entry' : '» Existing Records Matrix'); ?>
            </span>
        </h2>
        <?php if ($part != '1' && $part != '2') { ?>
            <div>
                <a href="index.php?page=<?=$page?>&part=1" class="chg-blue-btn">
                    <i class="fa fa-plus"></i> Create New
                </a>
            </div>
        <?php } ?>
    </div>

    <form method='POST' action='' name='member' enctype='multipart/form-data' id="memberForm">
    <?php 
    $role = isset($_SESSION['userrole']) ? $_SESSION['userrole'] : 0;

    if($part=='1') { ?>
        <!-- ==================== CREATE LAYOUT (CENTERED) ==================== -->
        <div class="chg-form-center-wrapper">
            <div style="margin-bottom: 15px;">
                <label class="manda">Group id : <span style="color:#e74c3c;">*</span></label>
                <select class="form-control" id="group_id" name="group_id" required='required'>
                    <?php if(isset($_POST['group_id']) && $_POST['group_id'] && $conn) { 
                      $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group where logid='".$_POST['group_id']."'");
                      while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                        <option value="<?php echo $drow['logid']?>"><?php echo $drow['name']?></option>
                    <?php } } ?>
                    <option value="">Select Group id</option>
                    <?php
                    $group_id_query = ($role==4) ? " where logid='".(isset($_SESSION['groupid']) ? $_SESSION['groupid'] : 0)."' " : " ";
                    if($conn) {
                        $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group $group_id_query ORDER BY name");
                        while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']?>"><?php echo $drow['name']?></option>
                    <?php } } ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Name : <span style="color:#e74c3c;">*</span></label>
                <input type="text" required='required' class="form-control" name="name" id="name" placeholder="Name" value="<?php echo htmlspecialchars($_POST['name'] ?? '')?>" autocomplete="off">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Phone no : <span style="color:#e74c3c;">*</span></label>
                <input type="text" required='required' class="form-control" name="phone_no" id="phone_no" placeholder="Phone no" value="<?php echo htmlspecialchars($_POST['phone_no'] ?? '')?>" autocomplete="off">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Nat id : <span style="color:#e74c3c;">*</span></label>
                <input type="text" required='required' class="form-control" name="nat_id" id="nat_id" placeholder="Nat id" value="<?php echo htmlspecialchars($_POST['nat_id'] ?? '')?>" autocomplete="off">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Dob : <span style="color:#e74c3c;">*</span></label>
                <input type="date" class="form-control" value="<?php echo date('Y-m-d')?>" name="dob" id="dob">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Gender : <span style="color:#e74c3c;">*</span></label>
                <select class="form-control" id="gender" name="gender" required='required'>
                    <?php if(isset($_POST['gender']) && $_POST['gender']) { ?>
                        <option value="<?php echo htmlspecialchars($_POST['gender'])?>"><?php echo htmlspecialchars($_POST['gender'])?></option>	
                    <?php } ?>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Medical condition : <span style="color:#e74c3c;">*</span></label>
                <select class="form-control" id="medical_condition" name="medical_condition" required='required'>
                    <?php if(isset($_POST['medical_condition']) && $_POST['medical_condition'] && $conn) { 
                      $qresult = mysqli_query($conn,"SELECT logid,description FROM conditions where logid='".$_POST['medical_condition']."'");
                      while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                        <option value="<?php echo $drow['logid']?>"><?php echo $drow['description']?></option>
                    <?php } } ?>
                    <option value="">Select Medical condition</option>
                    <?php if($conn) {
                        $qresult = mysqli_query($conn,"SELECT logid,description FROM conditions ORDER BY description");
                        while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']?>"><?php echo $drow['description']?></option>
                    <?php } } ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label class="chg-form-label">Caregiver name :</label>
                <input type="text" class="form-control" name="caregiver_name" id="caregiver_name" placeholder="Caregiver name" value="<?php echo htmlspecialchars($_POST['caregiver_name'] ?? '')?>" autocomplete="off">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="chg-form-label">Caregiver phone :</label>
                <input type="text" class="form-control" name="caregiver_phone" id="caregiver_phone" placeholder="Caregiver phone" value="<?php echo htmlspecialchars($_POST['caregiver_phone'] ?? '')?>" autocomplete="off">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="chg-form-label">Registration date :</label>
                <input type="date" class="form-control" value="<?php echo date('Y-m-d')?>" name="registration_date" id="registration_date">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Leader : <span style="color:#e74c3c;">*</span></label>
                <select class="form-control" id="isleader" name="isleader" required='required'>
                    <?php if(isset($_POST['isleader']) && $_POST['isleader']) { ?>
                        <option value="<?php echo htmlspecialchars($_POST['isleader'])?>"><?php echo ($_POST['isleader']==1) ? 'Yes' : 'No'; ?></option>	
                    <?php } ?>
                    <option value="">Select</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Active : <span style="color:#e74c3c;">*</span></label>
                <select class="form-control" id="active" name="active" required='required'>
                    <?php if(isset($_POST['active']) && $_POST['active']) { ?>
                        <option value="<?php echo htmlspecialchars($_POST['active'])?>"><?php echo htmlspecialchars($_POST['active'])?></option>	
                    <?php } ?>
                    <option value="">Select Active</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label class="chg-form-label">Activation dt :</label>
                <input type="date" class="form-control" value="<?php echo date('Y-m-d')?>" name="activation_dt" id="activation_dt">
            </div>

            <div style="margin-top: 25px; padding-top: 15px; border-top:1px solid #eef2f5; display:flex; gap:10px; justify-content: flex-start;">
                <button type="submit" name="member_save" class="chg-blue-btn"><i class="fa fa-save"></i> Save</button>
                <a href="index.php?page=<?=$page?>" class="btn btn-secondary btn-sm" style="padding:8px 16px; font-size:13px; line-height: 1.42857143;">Back</a>
            </div>
        </div>

    <?php } elseif($part=='2') { ?>
        <!-- ==================== EDIT LAYOUT (CENTERED) ==================== -->
        <div class="chg-form-center-wrapper">
            <?php 
            $result = $conn ? mysqli_query($conn,"SELECT * FROM member WHERE logid='".mysqli_real_escape_string($conn, $rec)."'") : null;
            while($result && $row = mysqli_fetch_assoc($result)) { ?> 
                <div style="margin-bottom: 15px;">
                    <label class="manda">Group id : <span style="color:#e74c3c;">*</span></label>
                    <select class="form-control" id="group_id" name="group_id" required='required'>
                        <?php $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group WHERE logid='".$row['group_id']."'");
                        while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']?>"><?php echo $drow['name']?></option>
                        <?php } ?>
                        <option value="">Select Group id</option>
                        <?php $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group ORDER BY name");
                        while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']?>"><?php echo $drow['name']?></option>
                        <?php } ?>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Name : <span style="color:#e74c3c;">*</span></label>
                    <input type="text" required='required' class="form-control" name="name" id="name" placeholder="Name" value="<?php echo htmlspecialchars($row['name'])?>" autocomplete="off">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Phone no : <span style="color:#e74c3c;">*</span></label>
                    <input type="text" required='required' class="form-control" name="phone_no" id="phone_no" placeholder="Phone no" value="<?php echo htmlspecialchars($row['phone_no'])?>" autocomplete="off">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Nat id : <span style="color:#e74c3c;">*</span></label>
                    <input type="text" required='required' class="form-control" name="nat_id" id="nat_id" placeholder="Nat id" value="<?php echo htmlspecialchars($row['nat_id'])?>" autocomplete="off">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Dob : <span style="color:#e74c3c;">*</span></label>
                    <input type="date" class="form-control" value="<?php echo htmlspecialchars($row['dob'])?>" name="dob" id="dob">
                </div>
                 
                <div style="margin-bottom: 15px;">
                    <label class="manda">Gender : <span style="color:#e74c3c;">*</span></label>
                    <select class="form-control" id="gender" name="gender" required='required'>
                        <option value="<?php echo htmlspecialchars($row['gender'])?>"><?php echo htmlspecialchars($row['gender'])?></option>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Medical condition : <span style="color:#e74c3c;">*</span></label>
                    <select class="form-control" id="medical_condition" name="medical_condition" required='required'>
                        <?php $qresult = mysqli_query($conn,"SELECT logid,description FROM conditions WHERE logid='".$row['medical_condition']."'");
                        while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']?>"><?php echo $drow['description']?></option>
                        <?php } ?>
                        <option value="">Select Medical condition</option>
                        <?php $qresult = mysqli_query($conn,"SELECT logid,description FROM conditions ORDER BY description");
                        while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']?>"><?php echo $drow['description']?></option>
                        <?php } ?>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="chg-form-label">Caregiver name :</label>
                    <input type="text" class="form-control" name="caregiver_name" id="caregiver_name" placeholder="Caregiver name" value="<?php echo htmlspecialchars($row['caregiver_name'])?>" autocomplete="off">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="chg-form-label">Caregiver phone :</label>
                    <input type="text" class="form-control" name="caregiver_phone" id="caregiver_phone" placeholder="Caregiver phone" value="<?php echo htmlspecialchars($row['caregiver_phone'])?>" autocomplete="off">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="chg-form-label">Registration date :</label>
                    <input type="date" class="form-control" value="<?php echo htmlspecialchars($row['registration_date'])?>" name="registration_date" id="registration_date">
                </div>
                 
                <div style="margin-bottom: 15px;">
                    <label class="manda">Leader : <span style="color:#e74c3c;">*</span></label>
                    <select class="form-control" id="isleader" name="isleader" required='required'>
                        <option value="<?php echo htmlspecialchars($row['isleader'])?>"><?php echo ($row['isleader']==1) ? 'Yes' : 'No'; ?></option>	
                        <option value="">Select</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Active : <span style="color:#e74c3c;">*</span></label>
                    <select class="form-control" id="active" name="active" required='required'>
                        <option value="<?php echo htmlspecialchars($row['active'])?>"><?php echo htmlspecialchars($row['active'])?></option>
                        <option value="">Select Active</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="chg-form-label">Activation dt :</label>
                    <input type="date" class="form-control" value="<?php echo htmlspecialchars($row['activation_dt']) ?>" name="activation_dt" id="activation_dt">
                </div>
                 
                <div style="margin-bottom: 15px;">
                    <label class="chg-form-label">Deactivation dt :</label>
                    <input type="date" class="form-control" value="<?php if($row['deactivation_dt']!='0000-00-00 00:00:00' && !empty($row['deactivation_dt'])) { echo date('Y-m-d', strtotime($row['deactivation_dt'])); }?>" name="deactivation_dt" id="deactivation_dt">
                </div>

                <div style="margin-top: 25px; padding-top: 15px; border-top:1px solid #eef2f5; display:flex; gap:10px; justify-content: flex-start;">
                    <button type="submit" name="member_update" class="chg-blue-btn"><i class="fa fa-save"></i> Update</button>
                    <a href="index.php?page=<?=$page?>" class="btn btn-secondary btn-sm" style="padding:8px 16px; font-size:13px; line-height: 1.42857143;">Cancel</a>
                </div>
                <input name="logid" type="hidden" value="<?=$row['logid']?>" />
            <?php } ?> 
        </div>

    <?php } else { ?>
        <!-- ==================== DATA GRID TABLE LAYOUT ==================== -->
        <div class="custom-table-wrapper">
            <table id="datatable" class="chg-data-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">MemberNo.</th>
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
                        <th style="text-align: center;">Rx</th>
                        <th style="width: 60px; text-align: center;">Edit</th> 
                        <th style="width: 60px; text-align: center;">Del</th> 
                    </tr>
                </thead>
                <tbody>
                <?php $mycount=0;
                $where = ($role==4) ? " and group_id='".(isset($_SESSION['groupid']) ? $_SESSION['groupid'] : 0)."' " : " ";

                if(isset($_POST['tbn_search']) && $conn) {
                    $search=$conn->real_escape_string($_POST['search']);
                    $result = @mysqli_query($conn,"SELECT * FROM member where group_id>0 $where and (group_id like '%$search%' OR name like '%$search%' OR phone_no like '%$search%' OR nat_id like '%$search%' OR dob like '%$search%' OR gender like '%$search%' OR medical_condition like '%$search%' OR caregiver_name like '%$search%' OR caregiver_phone like '%$search%' OR registration_date like '%$search%' OR active like '%$search%' OR activation_dt like '%$search%' OR deactivation_dt like '%$search%' OR created_by like '%$search%' OR created_on like '%$search%')");
                } else {
                    $result = $conn ? @mysqli_query($conn,"SELECT * FROM member $where ORDER BY logid DESC") : null;
                }

                if($result && mysqli_num_rows($result) > 0) {
                    while($row = @mysqli_fetch_assoc($result)) { 
                        $mycount=$mycount+1; ?> 				  
                        <tr>
                            <td style="text-align: center;"><?=$row['logid']?></td>
                            <td><a href="index.php?page=member_profile&id=<?=$row['logid']?>" style="color: #1ea7e1; font-weight: bold;"><?=htmlspecialchars($row['mnumber'])?></a></td>
                            <td><?php
                            $qresult = $conn ? mysqli_query($conn,"SELECT name FROM provider_group WHERE logid='".$row['group_id']."'") : null;
                            while($qresult && $drow = mysqli_fetch_assoc($qresult)) {  
                                echo htmlspecialchars($drow['name']);  
                            } ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name'])?></strong></td>
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
                            <td align="center">
                                <a href="index.php?page=<?= $page ?>&part=2&rec=<?=$row['logid']?>" style="color: #1ea7e1;"><i class="fa fa-pencil"></i> Edit</a>
                            </td>
                            <td align="center">
                                <input name="del[]" type="checkbox" value="<?=$row['logid']?>" style="cursor:pointer;" />
                            </td>
                        </tr>
                    <?php } 
                } else { ?>
                    <tr>
                        <td colspan="14" style="text-align: center; color: #7f8c8d; padding: 25px; font-style: italic;">
                            No member records found. Click <a href="index.php?page=<?= $page ?>&part=1" style="color:#1ea7e1;"><u>Create New</u></a> to get started.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
                <?php if ($mycount > 0) { ?>
                <tfoot>
                    <tr style="background-color: #f5f5f5; font-weight: bold;">
                        <td colspan="13" style="text-align: right; vertical-align: middle; padding-right:15px;">With Selected Items:</td>
                        <td style="text-align: center;">
                            <button name="member_delete" type="submit" class="btn btn-danger btn-sm" onClick="return confirm('Please confirm deletion of checked records');">
                                <i class="fa fa-trash"></i> Del
                            </button>
                        </td>
                    </tr>
                </tfoot>
                <?php } ?>
            </table>
        </div> 
        <input type="hidden" name="txtcount" id="txtcount" value="<?php echo $mycount;?>"> 
    <?php } ?>
    </form>
</div>
<?php } ?>