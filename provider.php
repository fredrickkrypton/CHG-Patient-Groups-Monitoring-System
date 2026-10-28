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

// Initialise variables so PHP does not raise "Undefined variable/key" warnings
$alert = '';
$error = '';
$deleted = 0;
$saved_ok = false;

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

// Check if $_GET['D'] is set before processing to avoid the Undefined Key warning
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

// $rec may not be set by workout() on the add/list screens
if (!isset($rec)) { $rec = ''; }

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['provider_delete'])) { caniacess($page."_delete"); }

if(isset($_SESSION['stop']) && $_SESSION['stop']==1)
{  
    include('stop.php'); 
}
else 
{
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['provider_save']))
{
    $name=mysqli_real_escape_string($conn, ucwords($_POST['name'] ?? ''));
    $subcounty_id=mysqli_real_escape_string($conn, ucwords($_POST['subcounty_id'] ?? ''));
    $contact_name=mysqli_real_escape_string($conn, ucwords($_POST['contact_name'] ?? ''));
    $contact_phone=mysqli_real_escape_string($conn, ucwords($_POST['contact_phone'] ?? ''));
    $registration_date=dateto_mysql($_POST['registration_date'] ?? '');
    $created_by=mysqli_real_escape_string($conn, ucwords($username));
    $created_on=mysqli_real_escape_string($conn, ucwords($timestamp));

    if(trim($name)!=''&&trim($subcounty_id)!=''&&trim($contact_name)!=''&&trim($contact_phone)!=''&&trim($registration_date)!='') { 
        $sql = "INSERT INTO provider (name,subcounty_id,contact_name,contact_phone,registration_date,created_by,created_on)
                VALUES ('$name','$subcounty_id','$contact_name','$contact_phone','$registration_date','$created_by','$created_on')";
        if ($conn->query($sql) === TRUE) {  
            $alert=1; 
            audittrail($conn,'provider','','inserting',$timestamp,$username); 
            $saved_ok = true;
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
                                      text: '".addslashes($error)."',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['provider_update']))
{
    $name=mysqli_real_escape_string($conn, ucwords($_POST['name'] ?? ''));
    $subcounty_id=mysqli_real_escape_string($conn, ucwords($_POST['subcounty_id'] ?? ''));
    $contact_name=mysqli_real_escape_string($conn, ucwords($_POST['contact_name'] ?? ''));
    $contact_phone=mysqli_real_escape_string($conn, ucwords($_POST['contact_phone'] ?? ''));
    $registration_date=dateto_mysql($_POST['registration_date'] ?? '');
    $created_by=mysqli_real_escape_string($conn, ucwords($username));
    $created_on=mysqli_real_escape_string($conn, ucwords($timestamp));
    $logid=mysqli_real_escape_string($conn, $_POST['logid'] ?? '');

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
                                      text: '".addslashes($error)."',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['provider_delete']))
{ 
    if(!empty($_POST['del']) && is_array($_POST['del'])) {
        foreach($_POST['del'] as $delid) {
            if(trim($delid)==='') { continue; }
            $delid = mysqli_real_escape_string($conn, $delid);
            audittrail($conn,'provider',$delid,'deleting',$timestamp,$username);
            if ($conn->query("DELETE FROM provider WHERE logid='$delid'") === TRUE) { $deleted++; }
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
                                      text: '".addslashes($error)."',
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
        <?php
            // After a successful save, clear the form so the same record isn't saved twice
            $form = $saved_ok ? array() : $_POST;
        ?>
        <?php if($saved_ok) { ?>
        <div style="max-width: 550px; margin: 20px auto 0; padding: 15px 20px; background: #eafaf1; border: 1px solid #b7e4c7; border-radius: 6px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <span style="color:#1e7e34; font-size:13px; font-weight:bold;"><i class="fa fa-check-circle"></i> Provider saved successfully.</span>
            <span style="display:flex; gap:8px;">
                <a href="index.php?page=<?=$page?>" class="chg-blue-btn"><i class="fa fa-eye"></i> View</a>
                <a href="index.php?page=<?=$page?>&part=1" class="btn btn-secondary btn-sm" style="padding:8px 16px; font-size:13px;">Add Another</a>
            </span>
        </div>
        <?php } ?>
        <div class="chg-form-center-wrapper">
           <div style="margin-bottom: 15px;">
              <label class="manda">Name :</label>
              <input type="text" required='required' class="form-control" name="name" id="name" placeholder="Name" value="<?php echo htmlspecialchars($form['name'] ?? ''); ?>" autocomplete="off" onblur="js_validate(this,'general')">
          </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Subcounty id :</label>
                <select class="form-control" id="subcounty_id" name="subcounty_id" required='required'>
                    <?php if(!empty($form['subcounty_id'])) { 
                        $sel_sub = mysqli_real_escape_string($conn, $form['subcounty_id']);
                        $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty where logid='".$sel_sub."'");
                        while($drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']; ?>"><?php echo $drow['name']; ?></option>
                        <?php }
                    } ?>
                    <option value="">Select Subcounty id</option>
                    <?php $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty ORDER BY name");
                    while($drow = mysqli_fetch_assoc($qresult)) { ?>
                        <option value="<?php echo $drow['logid']; ?>"><?php echo $drow['name']; ?></option>
                    <?php } ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Contact name :</label>
                <input type="text" required='required' class="form-control" name="contact_name" id="contact_name" placeholder="Contact name" value="<?php echo htmlspecialchars($form['contact_name'] ?? ''); ?>" autocomplete="off" onblur="js_validate(this,'general')">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Contact phone :</label>
                <input type="text" required='required' class="form-control" name="contact_phone" id="contact_phone" placeholder="Contact phone" value="<?php echo htmlspecialchars($form['contact_phone'] ?? ''); ?>" autocomplete="off" onblur="js_validate(this,'general')">
            </div>

            <div style="margin-bottom: 15px;">
                <label class="manda">Registration date :</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($form['registration_date'] ?? date('d/m/Y')); ?>" name="registration_date" id="registration_date" onClick="return xCal('registration_date','/',4)">
            </div>

            <div style="margin-top: 25px; padding-top: 15px; border-top:1px solid #eef2f5; display:flex; gap:10px; justify-content: flex-start;">
                <button type="submit" name="provider_save" class="chg-blue-btn"><i class="fa fa-save"></i> Save</button>
                <a href="index.php?page=<?=$page?>" class="chg-blue-btn" style="background-color:#6c757d !important;"><i class="fa fa-eye"></i> View</a>
                <a href="index.php?page=<?=$page?>" class="btn btn-secondary btn-sm" style="padding:8px 16px; font-size:13px; line-height: 1.42857143;">Back</a>
            </div>
        </div>

    <?php } elseif($part=='2') { ?>
        <!-- ==================== EDIT LAYOUT (CENTERED) ==================== -->
        <div class="chg-form-center-wrapper">
            <?php $rec_safe = mysqli_real_escape_string($conn, $rec);
            $result = mysqli_query($conn,"SELECT * FROM provider WHERE logid='$rec_safe'");
            while($row = mysqli_fetch_assoc($result)) { ?> 
                <div style="margin-bottom: 15px;">
                    <label class="manda">Name :</label>
                    <input type="text" required='required' class="form-control" name="name" id="name" placeholder="Name" value="<?php echo htmlspecialchars($row['name']); ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Subcounty id :</label>
                    <select class="form-control" id="subcounty_id" name="subcounty_id" required='required'>
                        <?php $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty WHERE logid='".mysqli_real_escape_string($conn,$row['subcounty_id'])."'");
                        while($drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']; ?>"><?php echo $drow['name']; ?></option>
                        <?php } ?>
                        <option value="">Select Subcounty id</option>
                        <?php $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty ORDER BY name");
                        while($drow = mysqli_fetch_assoc($qresult)) { ?>
                            <option value="<?php echo $drow['logid']; ?>"><?php echo $drow['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Contact name :</label>
                    <input type="text" required='required' class="form-control" name="contact_name" id="contact_name" placeholder="Contact name" value="<?php echo htmlspecialchars($row['contact_name']); ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Contact phone :</label>
                    <input type="text" required='required' class="form-control" name="contact_phone" id="contact_phone" placeholder="Contact phone" value="<?php echo htmlspecialchars($row['contact_phone']); ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="manda">Registration date :</label>
                    <input type="text" class="form-control" value="<?php echo dateto_normal($row['registration_date']); ?>" name="registration_date" id="registration_date" onClick="return xCal('registration_date','/',4)">
                </div>

                <div style="margin-top: 25px; padding-top: 15px; border-top:1px solid #eef2f5; display:flex; gap:10px; justify-content: flex-start;">
                    <button type="submit" name="provider_update" class="chg-blue-btn"><i class="fa fa-save"></i> Update</button>
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
                <?php $mycount=0;
                if(isset($_POST['tbn_search'])) {
                    $search=mysqli_real_escape_string($conn, $_POST['search'] ?? '');
                    $result = mysqli_query($conn,"SELECT * FROM provider where name like '%$search%' OR subcounty_id like '%$search%' OR contact_name like '%$search%' OR contact_phone like '%$search%' OR registration_date like '%$search%' OR created_by like '%$search%' OR created_on like '%$search%'");
                } else {
                    $result = mysqli_query($conn,"SELECT * FROM provider");
                }

                while($row = mysqli_fetch_assoc($result)) { 
                    $mycount=$mycount+1; ?> 				  
                    <tr>
                        <td style="text-align: center;"><?=$mycount?></td>
                        <td><strong><?php echo $row['name']; ?></strong></td>
                        <td><?php $qresult = mysqli_query($conn,"SELECT name FROM subcounty WHERE logid='".mysqli_real_escape_string($conn,$row['subcounty_id'])."'");
                        while($drow = mysqli_fetch_assoc($qresult)) {  
                            echo $drow['name'];  
                        } ?></td>
                        <td><?php echo $row['contact_name']; ?></td>
                        <td><?php echo $row['contact_phone']; ?></td>
                        <td><?php echo dateto_display($row['registration_date']); ?></td>
                        <td><?php echo $row['created_by']; ?></td>
                        <td><?php echo $row['created_on']; ?></td>
                        <td align="center"><a href="index.php?page=provider_group&D=cGFydD0x&p=<?=urlencode($row['logid'])?>" title="View groups"><i class="fa fa-lg fa-cog text-info"></i></a></td>
                        <td align="center">
                            <?php $edit="part=2&rec=".$row['logid']; ?>
                            <a href="?/<?=$page?>&D=<?php echo osteens($edit); ?>" style="color: #1ea7e1;"><i class="fa fa-pencil"></i> Edit</a>
                        </td>
                        <td align="center">
                            <input name="del[]" type="checkbox" value="<?=$row['logid']?>" style="cursor:pointer;" />
                        </td>
                    </tr>
                <?php } ?>
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
        <input type="hidden" name="txtcount" id="txtcount" value="<?php echo $mycount; ?>"> 
    <?php } ?>
    </form>
</div>
<?php } ?>