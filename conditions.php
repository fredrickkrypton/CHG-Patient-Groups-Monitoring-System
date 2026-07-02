<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBALS
global $title;
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

$page = "conditions";

// Ensure variables are safely initialized to avoid notice level errors
$part = isset($_GET['part']) ? $_GET['part'] : '';
$rec = isset($_GET['rec']) ? $_GET['rec'] : 0;
$username = isset($username) ? $username : (isset($_SESSION['username']) ? $_SESSION['username'] : 'System');
$deleted = 0;
$alert = '';
$error = '';

// SAFE CHECK: Handles 'D' parameter safely if core decryption function is missing
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
    if(isset($_POST['conditions_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {  
    include('stop.php'); 
} else {

//---------------------------------------------------------------------------------------------------- 
// INSERT/SAVE LOGIC
if(isset($_POST['conditions_save'])) {
    $description = ucwords($_POST['description']);
    $status      = isset($_POST['status']) ? $_POST['status'] : 'Active';
    $notes       = isset($_POST['notes']) ? $_POST['notes'] : '';

    if(trim($description) != '') { 
        $sql = "INSERT INTO conditions (description, status, notes, username, timestamp) 
                VALUES ('$description', '$status', '$notes', '$username', '$timestamp')";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'conditions', '', 'inserting', $timestamp, $username); }
        } else { 
            $error = "Error: Database insertion failure."; 
            if($conn) { $error .= " " . $conn->error; }
        }
    } else {  
        $error = "Fill all mandatory fields marked with an asterisk (*).";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Data saved successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// UPDATE LOGIC
if(isset($_POST['conditions_update'])) {
    $description = ucwords($_POST['description']);
    $status      = $_POST['status'];
    $notes       = $_POST['notes'];
    $logid       = $_POST['logid'];

    if(trim($description) != '') { 
        $sql = "UPDATE conditions SET description='$description', status='$status', notes='$notes', username='$username', timestamp='$timestamp' WHERE logid='$logid'";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'conditions', $logid, 'updatings', $timestamp, $username); }
        } else { 
            $error = "Error updating database entry."; 
        }
    } else {  
        $error = "Fill all mandatory fields marked with an asterisk (*).";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Data updated successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// DELETE LOGIC
if(isset($_POST['conditions_delete'])) { 
    $count = isset($_POST['txtcount']) ? $_POST['txtcount'] : 0;
    for($i=0; $i<$count; $i++) { 
        if(!empty($_POST['del'][$i])) { 
            if(function_exists('audittrail')) {
                audittrail($conn, 'conditions', $_POST['del'][$i], 'deleting', $timestamp, $username);
            }
            $sql = "DELETE FROM conditions WHERE logid='".$_POST['del'][$i]."'";
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

// Global scope definition fixes template title warnings
$display_title = 'Medical Conditions';
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
        <form method='POST' action='' class='form-horizontal form-label-left' name='conditions' style='min-width:100%' enctype='multipart/form-data'>
                                            
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
                        <label for="description" class="col-form-label col-md-3 col-sm-3 label-align">Description <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" required='required' class="form-control form-control-sm" name="description" id="description" placeholder="e.g. Hypertension, Diabetes Type II" value="<?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''?>" autocomplete="off">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="status" class="col-form-label col-md-3 col-sm-3 label-align">Status <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <select class="form-control form-control-sm" name="status" id="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="notes" class="col-form-label col-md-3 col-sm-3 label-align">Notes / Remarks :</label>
                        <div class="col-md-8 col-sm-8">
                            <textarea class="form-control rows-3" name="notes" id="notes" placeholder="Enter condition remarks or restrictions..." style="resize:vertical;"></textarea>
                        </div>
                    </div>

                    <div class="ln_solid"></div>
                    <div class="form-group row text-center">
                        <div class="col-md-12">
                            <button type="submit" name="conditions_save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
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
                    $result = $conn ? mysqli_query($conn,"SELECT * FROM conditions WHERE logid='$rec'") : null;
                    while($result && $row = mysqli_fetch_assoc($result)) { ?> 
                        
                        <div class="item form-group row">
                            <label for="description" class="col-form-label col-md-3 col-sm-3 label-align">Description <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8"> 
                                <input type="text" required='required' class="form-control form-control-sm" name="description" id="description" value="<?php echo htmlspecialchars($row['description'])?>" autocomplete="off">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="status" class="col-form-label col-md-3 col-sm-3 label-align">Status <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <select class="form-control form-control-sm" name="status" id="status">
                                    <option value="Active" <?php echo ($row['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($row['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="notes" class="col-form-label col-md-3 col-sm-3 label-align">Notes / Remarks :</label>
                            <div class="col-md-8 col-sm-8">
                                <textarea class="form-control rows-3" name="notes" id="notes" style="resize:vertical;"><?php echo htmlspecialchars($row['notes'] ?? '')?></textarea>
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group row text-center">
                            <div class="col-md-12">
                                <button type="submit" name="conditions_update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
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
                    <h2><?=ucwords($display_title);?><small> existing records</small></h2>
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
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Username</th>
                                    <th>Timestamp</th>
                                    <th width="1%">Edit</th> 
                                    <th width="1%">Del</th> 
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Username</th>
                                    <th>Timestamp</th>
                                    <th></th>
                                    <th>
                                        <button name="conditions_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onClick="javascript: return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
                                    </th> 
                                </tr>
                            </footer>
                            <tbody>
                            <?php $mycount=0;
                            if(isset($_POST['tbn_search']) && $conn) {
                                $search = $conn->real_escape_string($_POST['search']);
                                $result = mysqli_query($conn,"SELECT * FROM conditions WHERE description LIKE '%$search%' OR username LIKE '%$search%' OR timestamp LIKE '%$search%'");
                            } else {
                                $result = $conn ? mysqli_query($conn,"SELECT * FROM conditions") : null;
                            }

                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { $mycount++; ?> 				  
                                    <tr>
                                        <td><?=$mycount?></td>
                                        <td><?php echo htmlspecialchars($row['description'])?></td>
                                        <td><span class="badge <?php echo ($row['status'] == 'Active') ? 'badge-success' : 'badge-secondary'; ?>"><?php echo htmlspecialchars($row['status'] ?? 'Active')?></span></td>
                                        <td><?php echo htmlspecialchars($row['notes'] ?? '-')?></td>
                                        <td><?php echo htmlspecialchars($row['username'])?></td>
                                        <td><?php echo htmlspecialchars($row['timestamp'])?></td>
                                        <td align="center">
                                            <a href="index.php?page=<?=$page?>&part=2&rec=<?=$row['logid']?>" data-placement="bottom" title="Go to edit"><i class="fa fa-lg fa-pencil text-primary"></i></a>
                                        </td>
                                        <td align="center">
                                            <input name="del[]" id="del[]" type="checkbox" value="<?=$row['logid']?>" />
                                        </td>
                                    </tr>
                                <?php } 
                            } else { ?>
                                <tr>
                                    <td colspan="8" class="text-center" style="padding: 30px; color: #73879C; font-size: 14px;">
                                        <i class="fa fa-info-circle fa-lg"></i> No conditions found. Click <a href="index.php?page=<?=$page?>&part=1" class="alert-link"><u>Create New</u></a> to get started.
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