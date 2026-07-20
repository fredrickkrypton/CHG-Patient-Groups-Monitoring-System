<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-21 20:00:53
 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once("db.php");
// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBAL
global $title, $conn, $part, $rec, $username;

$page="provider";
$part = isset($_GET['part']) ? trim($_GET['part']) : '';
$timestamp=date('Y-m-d H:i:s');

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

// FIX: Check if $_GET['D'] is set before processing to avoid the Undefined Key warning
if (function_exists('workout') && isset($_GET['D'])) { 
    workout($_GET['D']); 
}
// Force-sync $part if D is present and the function didn't set it
if (isset($_GET['D']) && $part == '') {
    // Manually parse base64 for 'part=1' if workout() failed to pass it through
    if (strpos(base64_decode($_GET['D']), 'part=1') !== false) {
        $part = '1';
    }
}


if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['provider_delete'])) { caniacess($page."_delete"); }

if($_SESSION['stop']==1)
{  
    include('stop.php'); 
}
else 
{
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['provider_save']))
{
    $name=ucwords(($_POST['name']));
    $subcounty_id=ucwords(($_POST['subcounty_id']));
    $contact_name=ucwords(($_POST['contact_name']));
    $contact_phone=ucwords(($_POST['contact_phone']));
    $registration_date=dateto_mysql($_POST['registration_date']);
    $created_by=ucwords(($username));
    $created_on=ucwords(($timestamp));

    if(trim($name)!=''&&trim($subcounty_id)!=''&&trim($contact_name)!=''&&trim($contact_phone)!=''&&trim($registration_date)!='') { 
        $sql = "INSERT INTO provider (name,subcounty_id,contact_name,contact_phone,registration_date,created_by,created_on)
                VALUES ('$name','$subcounty_id','$contact_name','$contact_phone','$registration_date','$created_by','$created_on')";
        if ($conn->query($sql) === TRUE) {  
            $alert=1; 
            audittrail($conn,'provider','','inserting',$timestamp,$username); 
        } else { 
            $error=("Error: you are duplicating data!"); 
        }
    } else {  
        $error="Fill all mandatory fields in red.";  
    }  

    if($alert!='')
    {
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
if(isset($_POST['provider_update']))
{
    $name=ucwords(($_POST['name']));
    $subcounty_id=ucwords(($_POST['subcounty_id']));
    $contact_name=ucwords(($_POST['contact_name']));
    $contact_phone=ucwords(($_POST['contact_phone']));
    $registration_date=dateto_mysql($_POST['registration_date']);
    $created_by=ucwords(($username));
    $created_on=ucwords(($timestamp));
    $logid=$_POST['logid'];

    if(trim($name)!=''&&trim($subcounty_id)!=''&&trim($contact_name)!=''&&trim($contact_phone)!=''&&trim($registration_date)!='') { 
        $sql = "UPDATE provider SET name='$name',subcounty_id='$subcounty_id',contact_name='$contact_name',contact_phone='$contact_phone',registration_date='$registration_date',created_by='$created_by',created_on='$created_on' WHERE logid='$logid'";
        if ($conn->query($sql) === TRUE) {  
            $alert=1; 
            audittrail($conn,'provider',$logid,'updatings',$timestamp,$username); 
        } else { 
            $error=("Error: you are duplicating data!"); 
        }
    } else {  
        $error="Fill all mandatory fields in red.";  
    }  

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
if(isset($_POST['provider_delete']))
{ 
    $count = $_POST['txtcount'];
    for($i=0;$i<$count;$i++){ 
        if($_POST['del'][$i]!='')
        { 
            audittrail($conn,'provider',$_POST['del'][$i],'deleting',$timestamp,$username);
            $sql = "DELETE FROM provider WHERE logid='".$_POST['del'][$i]."'";
            if ($conn->query($sql) === TRUE) { $deleted=$deleted+1; }
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
                                      text: '',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }	  
}

//---------------------------------------------------------------------------------------------------- 
_title($title='Provider'); ?>

<!-- Native Styling Uniformity Integration Layer -->
<style>
    .chg-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: #ffffff;
        border-radius: 6px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-top: 10px;
        display: block;
        overflow: visible !important;
        clear: both;
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
    .manda, .chg-form-label {
        font-weight: bold;
        font-size: 13px;
        color: #1ea7e1 !important;
        display: block;
        margin-bottom: 5px;
    }
    .chg-form-center-wrapper {
        max-width: 550px;
        margin: 20px auto;
        padding: 25px;
        background: #fbfcfd;
        border: 1px solid #eef2f5;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: block;
        overflow: visible !important; 
    }
    .chg-form-center-wrapper .form-control,
    .chg-form-center-wrapper input[type="text"],
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
        box-sizing: border-box !important;
        margin-bottom: 5px;
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
            <?= ucwords($title); ?>
            <span style="font-size: 13px; color: #7f8c8d; font-weight: normal; margin-left: 5px;">
                <?php echo ($part=='1') ? '» New Entry' : (($part=='2') ? '» Update Entry' : '» Existing Records Matrix'); ?>
            </span>
        </h2>
        <?php if ($part != '1' && $part != '2') { ?>
            <div>
                <a href="index.php?page=<?=$page?>&part=1" class="chg-blue-btn">
                    <i class="fa fa-plus-circle"></i> Create New
                </a>
            </div>
        <?php } ?>
    </div>

    <form method='POST' action='' name='provider' enctype='multipart/form-data'>
    <?php if($part=='1') { ?>
        <!-- ==================== CREATE LAYOUT (CENTERED) ==================== -->
        <div class="chg-form-center-wrapper">
           <div style="margin-bottom: 15px;">
              <label class="manda">Name :</label>
              <?php
                  // Safely fetch the value
                  $name_val = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
              ?>
              <input type="text" required='required' class="form-control" name="name" id="name" placeholder="Name" value="<?php echo $name_val; ?>" autocomplete="off" onblur="js_validate(this,'general')">
          </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Subcounty id :</label>
                <select class="form-control" id="subcounty_id" name="subcounty_id" required='required'>
                    <? if($_POST['subcounty_id']) { 
                        $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty where logid='".$_POST['subcounty_id']."'");
                        while($drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
                        <? }
                    } ?>
                    <option value="">Select Subcounty id</option>
                    <? $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty ORDER BY name");
                    while($drow = mysqli_fetch_assoc($qresult)) { ?>
                        <option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
                    <? } ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Contact name :</label>
                <input type="text" required='required' class="form-control" name="contact_name" id="contact_name" placeholder="Contact name" value="<? echo $_POST['contact_name']?>" autocomplete="off" onblur="js_validate(this,'general')">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Contact phone :</label>
                <input type="text" required='required' class="form-control" name="contact_phone" id="contact_phone" placeholder="Contact phone" value="<? echo $_POST['contact_phone']?>" autocomplete="off" onblur="js_validate(this,'general')">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Registration date :</label>
                <input type="text" class="form-control" value="<? echo date('d/m/Y')?>" name="registration_date" id="registration_date" onClick="return xCal('registration_date','/',4)">
            </div>

            <div style="margin-top: 25px; padding-top: 15px; border-top:1px solid #eef2f5; display:flex; gap:10px; justify-content: flex-start;">
                <button type="submit" name="provider_save" class="chg-blue-btn"><i class="fa fa-save"></i> Save</button>
                <a href="?/<?=$page?>" class="btn btn-secondary btn-sm" style="padding:8px 16px; font-size:13px; line-height: 1.42857143;">Back</a>
            </div>
        </div>

    <?php } elseif($part=='2') { ?>
        <!-- ==================== EDIT LAYOUT (CENTERED) ==================== -->
        <div class="chg-form-center-wrapper">
            <? $result = mysqli_query($conn,"SELECT * FROM provider WHERE logid='$rec'");
            while($row = mysqli_fetch_assoc($result)) { ?> 
                <div style="margin-bottom: 15px;">
                    <label class="manda">Name :</label>
                    <input type="text" required='required' class="form-control" name="name" id="name" placeholder="Name" value="<? echo $row['name']?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Subcounty id :</label>
                    <select class="form-control" id="subcounty_id" name="subcounty_id" required='required'>
                        <? $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty WHERE logid='".$row['subcounty_id']."'");
                        while($drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
                        <? } ?>
                        <option value="">Select Subcounty id</option>
                        <? $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty ORDER BY name");
                        while($drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
                        <? } ?>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Contact name :</label>
                    <input type="text" required='required' class="form-control" name="contact_name" id="contact_name" placeholder="Contact name" value="<? echo $row['contact_name']?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Contact phone :</label>
                    <input type="text" required='required' class="form-control" name="contact_phone" id="contact_phone" placeholder="Contact phone" value="<? echo $row['contact_phone']?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Registration date :</label>
                    <input type="text" class="form-control" value="<? echo dateto_normal($row['registration_date'])?>" name="registration_date" id="registration_date" onClick="return xCal('registration_date','/',4)">
                </div>

                <div style="margin-top: 25px; padding-top: 15px; border-top:1px solid #eef2f5; display:flex; gap:10px; justify-content: flex-start;">
                    <button type="submit" name="provider_update" class="chg-blue-btn"><i class="fa fa-save"></i> Update</button>
                    <a href="?/<?=$page?>" class="btn btn-secondary btn-sm" style="padding:8px 16px; font-size:13px; line-height: 1.42857143;">Cancel</a>
                </div>
                <input name="logid" type="hidden" value="<?=$row['logid']?>" />
            <? } ?> 
        </div>

    <?php } else { ?>
        <!-- ==================== DATA GRID TABLE LAYOUT ==================== -->
        <div class="custom-table-wrapper">
            <table id="datatable" class="chg-data-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th>Name</th>
                        <th>Subcounty Id</th>
                        <th>Contact Name</th>
                        <th>Contact Phone</th>
                        <th>Registration Date</th>
                        <th>Created By</th>
                        <th>Created On</th>
                        <th style="text-align: center;">Group</th>
                        <th style="width: 60px; text-align: center;">Edit</th> 
                        <th style="width: 60px; text-align: center;">Del</th> 
                    </tr>
                </thead>
                <tbody>
                <? $mycount=0;
                if(isset($_POST['tbn_search'])) {
                    $search=$_POST['search'];
                    $result = mysqli_query($conn,"SELECT * FROM provider where name like '%$search%' OR subcounty_id like '%$search%' OR contact_name like '%$search%' OR contact_phone like '%$search%' OR registration_date like '%$search%' OR created_by like '%$search%' OR created_on like '%$search%'");
                } else {
                    $result = mysqli_query($conn,"SELECT * FROM provider");
                }

                while($row = mysqli_fetch_assoc($result)) { 
                    $mycount=$mycount+1; ?> 				  
                    <tr>
                        <td style="text-align: center;"><?=$mycount?></td>
                        <td><strong><? echo $row['name']?></strong></td>
                        <td><? $qresult = mysqli_query($conn,"SELECT name FROM subcounty WHERE logid='".$row['subcounty_id']."'");
                        while($drow = mysqli_fetch_assoc($qresult)) {  
                            echo $drow['name'];  
                        } ?></td>
                        <td><? echo $row['contact_name']?></td>
                        <td><? echo $row['contact_phone']?></td>
                        <td><? echo dateto_display($row['registration_date'])?></td>
                        <td><? echo $row['created_by']?></td>
                        <td><? echo $row['created_on']?></td>
                        <td align="center"><a href="?/provider_group&D=cGFydD0x&p=<?=$row['logid']?>"><i class="fa fa-lg fa-cog text-info"></i></a></td>
                        <td align="center">
                            <? $edit="part=2&rec=".$row['logid']; ?>
                            <a href="?/<?=$page?>&D=<? echo osteens($edit)?>" style="color: #1ea7e1;"><i class="fa fa-pencil"></i> Edit</a>
                        </td>
                        <td align="center">
                            <input name="del[]" type="checkbox" value="<?=$row['logid']?>" style="cursor:pointer;" />
                        </td>
                    </tr>
                <? } ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: #f5f5f5; font-weight: bold;">
                        <td colspan="10" style="text-align: right; vertical-align: middle; padding-right:15px;">With Selected Items:</td>
                        <td style="text-align: center;">
                            <button name="provider_delete" type="submit" class="btn btn-danger btn-sm" onClick="javascript: return confirm('Please confirm deletion');">
                                <i class="fa fa-trash"></i> Del
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div> 
        <input type="hidden" name="txtcount" id="txtcount" value="<? echo $mycount;?>"> 
    <?php } ?>
    </form>
</div>
<?php } ?>