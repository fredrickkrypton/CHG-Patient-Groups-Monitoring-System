<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBALS
global $title;
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

$page = "prescription";

// Ensure variables are safely initialized to avoid notice level errors
$part = isset($_GET['part']) ? $_GET['part'] : '';
$rec = isset($_GET['rec']) ? $_GET['rec'] : 0;
$username = isset($username) ? $username : (isset($_SESSION['username']) ? $_SESSION['username'] : 'System');
$deleted = 0;
$alert = '';
$error = '';

// SAFE CHECK: Handles URL parameter unpacking cleanly if workout() is undefined
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

$timestamp = date('Y-m-d H:i:s');

if (function_exists('caniacess')) {
    if($part == '') { caniacess($page."_view"); }
    if($part == '1') { caniacess($page."_add"); }
    if($part == '2') { caniacess($page."_edit"); }
    if(isset($_POST['prescription_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {  
    include('stop.php'); 
} else {

//---------------------------------------------------------------------------------------------------- 
// INSERT/SAVE LOGIC
if(isset($_POST['prescription_save'])) {
    $group_id       = ucwords($_POST['group_id']);
    $member_id      = ucwords($_POST['member_id']);
    $dispensationdt = !empty($_POST['dispensationdt']) ? $_POST['dispensationdt'] : date('Y-m-d');
    $valid_from     = !empty($_POST['valid_from']) ? $_POST['valid_from'] : date('Y-m-d');
    $valid_to       = !empty($_POST['valid_to']) ? $_POST['valid_to'] : date('Y-m-d', strtotime('+1 month'));

    if (function_exists('dateto_mysql')) { 
        $dispensationdt = dateto_mysql($dispensationdt); 
        $valid_from     = dateto_mysql($valid_from); 
        $valid_to       = dateto_mysql($valid_to); 
    }

    $created_by = $username;
    $created_on = $timestamp;

    if(trim($member_id) != '') { 
        $sql = "INSERT INTO prescription (group_id, member_id, dispensation_dt, valid_from, valid_to, created_by, created_on)
                VALUES ('$group_id', '$member_id', '$dispensationdt', '$valid_from', '$valid_to', '$created_by', '$created_on')";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'prescription', '', 'inserting', $timestamp, $username); }
        } else { 
            $error = "Error saving data context."; 
            if($conn) { $error .= " " . $conn->error; }
        }
    } else {  
        $error = "Fill all mandatory fields marked in red.";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Prescription saved successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// UPDATE LOGIC
if(isset($_POST['prescription_update'])) {
    $group_id       = ucwords($_POST['group_id']);
    $member_id      = ucwords($_POST['member_id']);
    $dispensationdt = $_POST['dispensationdt'];
    $valid_from     = $_POST['valid_from'];
    $valid_to       = $_POST['valid_to'];
    $logid          = $_POST['logid'];

    if (function_exists('dateto_mysql')) { 
        $dispensationdt = dateto_mysql($dispensationdt); 
        $valid_from     = dateto_mysql($valid_from); 
        $valid_to       = dateto_mysql($valid_to); 
    }

    if(trim($member_id) != '') { 
        $sql = "UPDATE prescription SET group_id='$group_id', member_id='$member_id', dispensation_dt='$dispensationdt', valid_from='$valid_from', valid_to='$valid_to' WHERE logid='$logid'";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'prescription', $logid, 'updatings', $timestamp, $username); }
        } else { 
            $error = "Error updating database record."; 
        }
    } else {  
        $error = "Fill all mandatory fields marked in red.";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Prescription updated successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// DELETE LOGIC
if(isset($_POST['prescription_delete'])) { 
    $count = isset($_POST['txtcount']) ? $_POST['txtcount'] : 0;
    for($i=0; $i<$count; $i++) { 
        if(!empty($_POST['del'][$i])) { 
            if(function_exists('audittrail')) {
                audittrail($conn, 'prescription', $_POST['del'][$i], 'deleting', $timestamp, $username);
            }
            $sql = "DELETE FROM prescription WHERE logid='".$_POST['del'][$i]."'";
            if ($conn && $conn->query($sql) === TRUE) { $deleted++; }
        }  
    } 
    if ($deleted > 0) { $alert = 1; } else { $error = "No record selected"; }
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Deleted successfully', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }	  
}

$display_title = 'Prescriptions';
if (function_exists('_title')) {
    _title($title = $display_title); 
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
  .manda { color: #ff0000; font-weight: bold; }
</style>

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <form method='POST' action='' class='form-horizontal form-label-left' name='prescription' style='min-width:100%' enctype='multipart/form-data'>
                                            
        <?php if($part=='1') { ?>
        <div class='col-md-8 offset-md-2 col-sm-12'>
            <div class='x_panel'>
                <div class='x_title'>
                    <h2><?=ucwords($display_title);?><small> new entry</small></h2>
                    <div class='clearfix'></div>
                </div>
                <div class='x_content'>
                    <br />
                    
                    <div class="item form-group row">
                        <label for="member_id" class="col-form-label col-md-3 col-sm-3 label-align">Patient / Member <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <select required="required" class="form-control form-control-sm" name="member_id" id="member_id">
                                <option value="">-- Select Member --</option>
                                <?php 
                                $m_res = $conn ? mysqli_query($conn, "SELECT logid, name FROM member ORDER BY name ASC") : null;
                                while($m_res && $m_row = mysqli_fetch_assoc($m_res)) {
                                    echo "<option value='".$m_row['logid']."'>".htmlspecialchars($m_row['name'])."</option>";
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="group_id" class="col-form-label col-md-3 col-sm-3 label-align">Group / Scheme :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" class="form-control form-control-sm" name="group_id" id="group_id" placeholder="Group No / Scheme Name" autocomplete="off">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="dispensationdt" class="col-form-label col-md-3 col-sm-3 label-align">Dispensed On <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="date" required="required" class="form-control form-control-sm" name="dispensationdt" id="dispensationdt" value="<?=date('Y-m-d')?>">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="valid_from" class="col-form-label col-md-3 col-sm-3 label-align">Valid From <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="date" required="required" class="form-control form-control-sm" name="valid_from" id="valid_from" value="<?=date('Y-m-d')?>">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="valid_to" class="col-form-label col-md-3 col-sm-3 label-align">Valid To <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="date" required="required" class="form-control form-control-sm" name="valid_to" id="valid_to" value="<?=date('Y-m-d', strtotime('+1 month'))?>">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label class="col-form-label col-md-3 col-sm-3 label-align">Handled By :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" readonly class="form-control form-control-sm bg-light" value="<?=htmlspecialchars($username)?>">
                        </div>
                    </div>

                    <div class="ln_solid"></div>
                    <div class="form-group row text-center">
                        <div class="col-md-12">
                            <button type="submit" name="prescription_save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
                            &nbsp; &nbsp;
                            <a href="index.php?page=<?=$page?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
                            &nbsp; &nbsp;
                            <a href="index.php?page=<?=$page?>" class="btn btn-info btn-sm"><span>View</span> <i class="fa fa-search"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php } elseif($part=='2') { ?>
        <div class='col-md-8 offset-md-2 col-sm-12'>
            <div class='x_panel'>
                <div class='x_title'>
                    <h2><?=ucwords($display_title);?><small> update entry</small></h2>
                    <div class='clearfix'></div>
                </div>
                <div class='x_content'>
                    <br />
                    <?php 
                    $result = $conn ? mysqli_query($conn,"SELECT * FROM prescription WHERE logid='$rec'") : null;
                    while($result && $row = mysqli_fetch_assoc($result)) { ?> 
                        
                        <div class="item form-group row">
                            <label for="member_id" class="col-form-label col-md-3 col-sm-3 label-align">Patient / Member <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <select required="required" class="form-control form-control-sm" name="member_id" id="member_id">
                                    <?php 
                                    $m_res = mysqli_query($conn, "SELECT logid, name FROM member ORDER BY name ASC");
                                    while($m_res && $m_row = mysqli_fetch_assoc($m_res)) {
                                        $sel = ($m_row['logid'] == $row['member_id']) ? 'selected' : '';
                                        echo "<option value='".$m_row['logid']."' $sel>".htmlspecialchars($m_row['name'])."</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="group_id" class="col-form-label col-md-3 col-sm-3 label-align">Group / Scheme :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" class="form-control form-control-sm" name="group_id" id="group_id" value="<?php echo htmlspecialchars($row['group_id'] ?? '')?>" autocomplete="off">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="dispensationdt" class="col-form-label col-md-3 col-sm-3 label-align">Dispensed On <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="date" required="required" class="form-control form-control-sm" name="dispensationdt" id="dispensationdt" value="<?php echo htmlspecialchars($row['dispensation_dt'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="valid_from" class="col-form-label col-md-3 col-sm-3 label-align">Valid From <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="date" required="required" class="form-control form-control-sm" name="valid_from" id="valid_from" value="<?php echo htmlspecialchars($row['valid_from'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="valid_to" class="col-form-label col-md-3 col-sm-3 label-align">Valid To <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="date" required="required" class="form-control form-control-sm" name="valid_to" id="valid_to" value="<?php echo htmlspecialchars($row['valid_to'] ?? '')?>">
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group row text-center">
                            <div class="col-md-12">
                                <button type="submit" name="prescription_update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
                                &nbsp; &nbsp; 
                                <a href="index.php?page=<?=$page?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
                                &nbsp; &nbsp; 
                                <a href="index.php?page=<?=$page?>" class="btn btn-info btn-sm"><span>View</span> <i class="fa fa-search"></i></a>
                            </div>
                        </div> 
                        <input name="logid" type="hidden" value="<?=$row['logid']?>" />
                    <?php } ?> 
                </div>
            </div>
        </div>

        <?php } else { ?>
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2><?=ucwords($display_title);?><small> records</small></h2>
                    <ul class="nav navbar-right panel_toolbox" style="list-style: none;">
                        <li><a href="index.php?page=<?=$page?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Create New</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div class="custom-table-wrapper">
                        <table id="datatable" role="grid" class="table table-striped table-bordered dataTable table-sm">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Patient Name</th>
                                    <th>Group/Scheme</th>
                                    <th>Dispensed On</th>
                                    <th>Valid From</th>
                                    <th>Valid To</th>
                                    <th>Handled By</th>
                                    <th width="1%">Meds</th> 
                                    <th width="1%">Edit</th> 
                                    <th width="1%">Del</th> 
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Patient Name</th>
                                    <th>Group/Scheme</th>
                                    <th>Dispensed On</th>
                                    <th>Valid From</th>
                                    <th>Valid To</th>
                                    <th>Handled By</th>
                                    <th></th>
                                    <th></th>
                                    <th>
                                        <button name="prescription_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onClick="javascript: return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
                                    </th> 
                                </tr>
                            </footer>
                            <tbody>
                            <?php $mycount=0;
                            if(isset($_POST['tbn_search']) && $conn) {
                                $search = $conn->real_escape_string($_POST['search']);
                                $result = mysqli_query($conn,"SELECT * FROM prescription WHERE group_id LIKE '%$search%' OR created_by LIKE '%$search%'");
                            } else {
                                $result = $conn ? mysqli_query($conn,"SELECT * FROM prescription ORDER BY logid DESC") : null;
                            }

                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { $mycount++; ?> 				  
                                    <tr>
                                        <td><?=$mycount?></td>
                                        <td><?php
                                            $m_id = $row['member_id'];
                                            $m_q = mysqli_query($conn, "SELECT name FROM member WHERE logid='$m_id'");
                                            $m_data = mysqli_fetch_assoc($m_q);
                                            echo htmlspecialchars($m_data['name'] ?? 'Unknown Member');
                                        ?></td>
                                        <td><?php echo htmlspecialchars($row['group_id'] ?? '-')?></td>
                                        <td><?php echo htmlspecialchars($row['dispensation_dt'] ?? '')?></td>
                                        <td><?php echo htmlspecialchars($row['valid_from'] ?? '')?></td>
                                        <td><?php echo htmlspecialchars($row['valid_to'] ?? '')?></td>
                                        <td><?php echo htmlspecialchars($row['created_by'] ?? '')?></td>
                                        
                                        <td align="center">
                                            <a href="index.php?page=prescription_details&part=1&p=<?=$row['logid']?>" title="Add/Manage Medication"><i class="fa fa-lg fa-medkit text-success"></i></a>
                                        </td>
                                        <td align="center">
                                            <a href="index.php?page=<?=$page?>&part=2&rec=<?=$row['logid']?>" title="Go to edit"><i class="fa fa-lg fa-pencil text-primary"></i></a>
                                        </td>
                                        <td align="center">
                                            <input name="del[]" id="del[]" type="checkbox" value="<?=$row['logid']?>" />
                                        </td>
                                    </tr>
                                <?php } 
                            } else { ?>
                                <tr>
                                    <td colspan="10" class="text-center" style="padding: 30px; color: #73879C; font-size: 14px;">
                                        <i class="fa fa-info-circle fa-lg"></i> No prescriptions logged yet. Click <a href="index.php?page=<?=$page?>&part=1" class="alert-link"><u>Create New</u></a> to get started.
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="txtcount" id="txtcount" value="<?php echo $mycount;?>"> 
                </div>
            </div>
        </div>
        <?php } ?>

        </form>
    </div>
</div>
<?php } ?>