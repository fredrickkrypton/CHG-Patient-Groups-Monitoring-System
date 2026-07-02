<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBAL
global $title;
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

// FIX: Matches menu.php query parameters exactly
$page = "logistics_party";

// Ensure variables are safely initialized to avoid notice level errors
$part = isset($_GET['part']) ? $_GET['part'] : '';
$rec = isset($_GET['rec']) ? $_GET['rec'] : 0;
$username = isset($username) ? $username : (isset($_SESSION['username']) ? $_SESSION['username'] : 'System');
$deleted = 0;
$alert = '';
$error = '';

// SAFE CHECK: Core helper function execution
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
    if(isset($_POST['logistics_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {  
    include('stop.php'); 
} else {

//----------------------------------------------------------------------------------------------------
// INSERT/SAVE LOGIC
if(isset($_POST['logistics_save'])) {
    $item_name = ucwords($_POST['item_name']);
    $quantity = ucwords($_POST['quantity']);
    $status = ucwords($_POST['status']);
    $destination = ucwords($_POST['destination']);
    $dispatch_date = !empty($_POST['dispatch_date']) ? $_POST['dispatch_date'] : date('Y-m-d');
    if (function_exists('dateto_mysql')) { $dispatch_date = dateto_mysql($dispatch_date); }
    $created_by = $username;
    $created_on = $timestamp;

    if(trim($item_name) != '' && trim($quantity) != '' && trim($status) != '') { 
        $sql = "INSERT INTO logistics (item_name, quantity, status, destination, dispatch_date, created_by, created_on)
                VALUES ('$item_name', '$quantity', '$status', '$destination', '$dispatch_date', '$created_by', '$created_on')";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'logistics', '', 'inserting', $timestamp, $username); }
        } else { 
            $error = "Error: System processing failure."; 
            if($conn) { $error .= " " . $conn->error; } 
        }
    } else {  
        $error = "Fill all mandatory fields in red.";  
    }  
    
    // Notifications
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
if(isset($_POST['logistics_update'])) {
    $item_name = ucwords($_POST['item_name']);
    $quantity = ucwords($_POST['quantity']);
    $status = ucwords($_POST['status']);
    $destination = ucwords($_POST['destination']);
    $dispatch_date = !empty($_POST['dispatch_date']) ? $_POST['dispatch_date'] : date('Y-m-d');
    if (function_exists('dateto_mysql')) { $dispatch_date = dateto_mysql($dispatch_date); }
    $logid = $_POST['logid'];

    if(trim($item_name) != '' && trim($quantity) != '' && trim($status) != '') { 
        $sql = "UPDATE logistics SET item_name='$item_name', quantity='$quantity', status='$status', destination='$destination', dispatch_date='$dispatch_date' WHERE logid='$logid'";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'logistics', $logid, 'updatings', $timestamp, $username); }
        } else { 
            $error = "Error updating data."; 
        }
    } else {  
        $error = "Fill all mandatory fields in red.";  
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
if(isset($_POST['logistics_delete'])) { 
    $count = isset($_POST['txtcount']) ? $_POST['txtcount'] : 0;
    for($i=0; $i<$count; $i++) { 
        if(!empty($_POST['del'][$i])) { 
            if(function_exists('audittrail')) {
                audittrail($conn, 'logistics', $_POST['del'][$i], 'deleting', $timestamp, $username);
            }
            $sql = "DELETE FROM logistics WHERE logid='".$_POST['del'][$i]."'";
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

if (function_exists('_title')) {
    _title($title='Logistics');
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

<form method='POST' action='' class='rounded' name='logistics' style='min-width:100%' enctype='multipart/form-data'>

<?php if($part=='1') { ?>
<div class='col-md-8 offset-md-2'>
    <div class='x_panel'>
        <div class='x_title'>
            <h2><?=ucwords($title);?><small> new entry</small></h2>
            <div class='clearfix'></div>
        </div>
        <div class='x_content'>
            <br />
            <div class="item form-group row">
                <label for="item_name" class="col-form-label col-md-3 col-sm-3 label-align">Item Name <span class="manda">*</span> :</label>
                <div class="col-md-8 col-sm-8">
                    <input type="text" required='required' class="form-control form-control-sm" name="item_name" id="item_name" placeholder="Item Name" value="<?php echo isset($_POST['item_name']) ? htmlspecialchars($_POST['item_name']) : ''?>" autocomplete="off">
                </div>
            </div>

            <div class="item form-group row">
                <label for="quantity" class="col-form-label col-md-3 col-sm-3 label-align">Quantity <span class="manda">*</span> :</label>
                <div class="col-md-8 col-sm-8">
                    <input type="text" required='required' class="form-control form-control-sm" name="quantity" id="quantity" placeholder="Quantity" value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : ''?>" autocomplete="off">
                </div>
            </div>

            <div class="item form-group row">
                <label for="status" class="col-form-label col-md-3 col-sm-3 label-align">Status <span class="manda">*</span> :</label>
                <div class="col-md-8 col-sm-8">
                    <select required="required" class="form-control form-control-sm" name="status" id="status">
                        <option value="">-- Select Status --</option>
                        <option value="Pending" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Dispatched" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Dispatched') ? 'selected' : ''; ?>>Dispatched</option>
                        <option value="Delivered" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                </div>
            </div>

            <div class="item form-group row">
                <label for="destination" class="col-form-label col-md-3 col-sm-3 label-align">Destination :</label>
                <div class="col-md-8 col-sm-8">
                    <input type="text" class="form-control form-control-sm" name="destination" id="destination" placeholder="Destination" value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : ''?>" autocomplete="off">
                </div>
            </div>

            <div class="item form-group row">
                <label for="dispatch_date" class="col-form-label col-md-3 col-sm-3 label-align">Dispatch Date :</label>
                <div class="col-md-8 col-sm-8">
                    <input type="date" class="form-control form-control-sm" name="dispatch_date" id="dispatch_date" value="<?php echo isset($_POST['dispatch_date']) ? htmlspecialchars($_POST['dispatch_date']) : date('Y-m-d')?>">
                </div>
            </div>

            <div class="ln_solid"></div>
            <div class="form-group row text-center">
                <div class="col-md-12">
                    <button type="submit" name="logistics_save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
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
<div class='col-md-8 offset-md-2'>
    <div class='x_panel'>
        <div class='x_title'>
            <h2><?=ucwords($title);?><small> update entry</small></h2>
            <div class='clearfix'></div>
        </div>
        <div class='x_content'>
            <br />
            <?php 
            $result = $conn ? mysqli_query($conn,"SELECT * FROM logistics WHERE logid='$rec'") : null;
            while($result && $row = mysqli_fetch_assoc($result)) { ?> 
                <div class="item form-group row">
                    <label for="item_name" class="col-form-label col-md-3 col-sm-3 label-align">Item Name <span class="manda">*</span> :</label>
                    <div class="col-md-8 col-sm-8"> 
                        <input type="text" required='required' class="form-control form-control-sm" name="item_name" id="item_name" value="<?php echo htmlspecialchars($row['item_name'])?>" autocomplete="off">
                    </div>
                </div>

                <div class="item form-group row">
                    <label for="quantity" class="col-form-label col-md-3 col-sm-3 label-align">Quantity <span class="manda">*</span> :</label>
                    <div class="col-md-8 col-sm-8"> 
                        <input type="text" required='required' class="form-control form-control-sm" name="quantity" id="quantity" value="<?php echo htmlspecialchars($row['quantity'])?>" autocomplete="off">
                    </div>
                </div>

                <div class="item form-group row">
                    <label for="status" class="col-form-label col-md-3 col-sm-3 label-align">Status <span class="manda">*</span> :</label>
                    <div class="col-md-8 col-sm-8">
                        <select required="required" class="form-control form-control-sm" name="status" id="status">
                            <option value="Pending" <?php echo ($row['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Dispatched" <?php echo ($row['status'] == 'Dispatched') ? 'selected' : ''; ?>>Dispatched</option>
                            <option value="Delivered" <?php echo ($row['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                        </select>
                    </div>
                </div>

                <div class="item form-group row">
                    <label for="destination" class="col-form-label col-md-3 col-sm-3 label-align">Destination :</label>
                    <div class="col-md-8 col-sm-8"> 
                        <input type="text" class="form-control form-control-sm" name="destination" id="destination" value="<?php echo htmlspecialchars($row['destination'])?>" autocomplete="off">
                    </div>
                </div>

                <div class="item form-group row">
                    <label for="dispatch_date" class="col-form-label col-md-3 col-sm-3 label-align">Dispatch Date :</label>
                    <div class="col-md-8 col-sm-8"> 
                        <input type="date" class="form-control form-control-sm" name="dispatch_date" id="dispatch_date" value="<?php echo htmlspecialchars($row['dispatch_date'])?>">
                    </div>
                </div>

                <div class="ln_solid"></div>
                <div class="form-group row text-center">
                    <div class="col-md-12">
                        <button type="submit" name="logistics_update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
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
            <h2><?=ucwords($title);?><small> existing records</small></h2>
            <ul class="nav navbar-right panel_toolbox">
                <li><a href="index.php?page=<?=$page?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Create New</a></li>
            </ul>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">

            <div class="custom-table-wrapper">
                <table id="datatable" role="grid" class="table table-striped table-bordered dataTable table-sm">
                    <thead>
                        <tr>
                            <th width="1px">Logistics No.</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Destination</th>
                            <th>Dispatch Date</th>
                            <th>Created By</th>
                            <th>Created On</th>
                            <th width="1%">Edit</th> 
                            <th width="1%">Del</th> 
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Logistics No.</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Destination</th>
                            <th>Dispatch Date</th>
                            <th>Created By</th>
                            <th>Created On</th>
                            <th></th>
                            <th>
                                <button name="logistics_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onClick="javascript: return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
                            </th> 
                        </tr>
                    </tfoot>
                    <tbody>
                    <?php $mycount=0;
                    if(isset($_POST['tbn_search']) && $conn) {
                        $search=$conn->real_escape_string($_POST['search']);
                        $result = @mysqli_query($conn,"SELECT * FROM logistics WHERE item_name LIKE '%$search%' OR status LIKE '%$search%' OR destination LIKE '%$search%'");
                    } else {
                        $result = $conn ? @mysqli_query($conn,"SELECT * FROM logistics") : null;
                    }

                    if($result && mysqli_num_rows($result) > 0) {
                        while($row = @mysqli_fetch_assoc($result)) { 
                            $mycount++; ?> 				  
                            <tr>
                                <td><?=$row['logid']?></td>
                                <td><?php echo htmlspecialchars($row['item_name'])?></td>
                                <td><?php echo htmlspecialchars($row['quantity'])?></td>
                                <td><?php echo htmlspecialchars($row['status'])?></td>
                                <td><?php echo htmlspecialchars($row['destination'])?></td>
                                <td><?php echo htmlspecialchars($row['dispatch_date'])?></td>
                                <td><?php echo htmlspecialchars($row['created_by'])?></td>
                                <td><?php echo htmlspecialchars($row['created_on'])?></td>
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
                            <td colspan="10" class="text-center" style="padding: 30px; color: #73879C; font-size: 14px;">
                                <i class="fa fa-info-circle fa-lg"></i> No logistical records found. Click <a href="index.php?page=<?=$page?>&part=1" class="alert-link"><u>Create New</u></a> to get started.
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
<?php } ?>