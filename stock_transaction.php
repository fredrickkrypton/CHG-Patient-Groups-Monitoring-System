<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBALS
global $title;
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

$page = "stock_transaction";

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
    if(isset($_POST['stock_transaction_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {  
    include('stop.php'); 
} else {

//---------------------------------------------------------------------------------------------------- 
// INSERT/SAVE LOGIC
if(isset($_POST['stock_transaction_save'])) {
    $trans_type             = ucwords($_POST['trans_type']);
    $sender_id              = isset($_POST['sender_id']) ? ucwords($_POST['sender_id']) : '';
    $recipient_id           = ucwords($_POST['recipient_id']);
    $trans_date             = !empty($_POST['trans_date']) ? $_POST['trans_date'] : date('Y-m-d');
    $medicine_id            = ucwords($_POST['medicine_id']);
    $prescription_detail_id = ucwords($_POST['prescription_detail_id']);
    $quantity               = ucwords($_POST['quantity']);

    if (function_exists('dateto_mysql')) { 
        $trans_date = dateto_mysql($trans_date); 
    }

    $created_by = $username;
    $created_on = $timestamp;

    if(trim($recipient_id) != '' && trim($medicine_id) != '' && trim($quantity) != '') { 
        $sql = "INSERT INTO stock_transaction (trans_type, sender_id, recipient_id, trans_date, medicine_id, prescription_detail_id, quantity, created_by, created_on)
                VALUES ('$trans_type', '$sender_id', '$recipient_id', '$trans_date', '$medicine_id', '$prescription_detail_id', '$quantity', '$created_by', '$created_on')";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'stock_transaction', '', 'inserting', $timestamp, $username); }
        } else { 
            $error = "Error saving transaction to database."; 
            if($conn) { $error .= " " . $conn->error; }
        }
    } else {  
        $error = "Fill all mandatory fields marked with an asterisk (*).";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Dispensation tracked successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// UPDATE LOGIC
if(isset($_POST['stock_transaction_update'])) {
    $trans_type             = ucwords($_POST['trans_type']);
    $sender_id              = isset($_POST['sender_id']) ? ucwords($_POST['sender_id']) : '';
    $recipient_id           = ucwords($_POST['recipient_id']);
    $trans_date             = $_POST['trans_date'];
    $medicine_id            = ucwords($_POST['medicine_id']);
    $prescription_detail_id = ucwords($_POST['prescription_detail_id']);
    $quantity               = ucwords($_POST['quantity']);
    $logid                  = $_POST['logid'];

    if (function_exists('dateto_mysql')) { 
        $trans_date = dateto_mysql($trans_date); 
    }

    if(trim($recipient_id) != '') { 
        $sql = "UPDATE stock_transaction SET trans_type='$trans_type', sender_id='$sender_id', recipient_id='$recipient_id', trans_date='$trans_date', medicine_id='$medicine_id', prescription_detail_id='$prescription_detail_id', quantity='$quantity' WHERE logid='$logid'";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'stock_transaction', $logid, 'updatings', $timestamp, $username); }
        } else { 
            $error = "Error updating transaction history entry."; 
        }
    } else {  
        $error = "Fill all mandatory fields marked with an asterisk (*).";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Transaction updated successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// DELETE LOGIC
if(isset($_POST['stock_transaction_delete'])) { 
    $count = isset($_POST['txtcount']) ? $_POST['txtcount'] : 0;
    for($i=0; $i<$count; $i++) { 
        if(!empty($_POST['del'][$i])) { 
            if(function_exists('audittrail')) {
                audittrail($conn, 'stock_transaction', $_POST['del'][$i], 'deleting', $timestamp, $username);
            }
            $sql = "DELETE FROM stock_transaction WHERE logid='".$_POST['del'][$i]."'";
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

$display_title = 'Stock Transactions / Dispensing';
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
        <form method='POST' action='' class='form-horizontal form-label-left' name='stock_transaction' style='min-width:100%' enctype='multipart/form-data'>
                                            
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
                        <label for="trans_type" class="col-form-label col-md-3 col-sm-3 label-align">Transaction Type <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <select class="form-control form-control-sm" name="trans_type" id="trans_type">
                                <option value="Dispensed">Dispensed</option>
                                <option value="Received">Received</option>
                                <option value="Adjustment">Adjustment</option>
                            </select>
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="recipient_id" class="col-form-label col-md-3 col-sm-3 label-align">Patient / Recipient <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <select required="required" class="form-control form-control-sm" name="recipient_id" id="recipient_id">
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
                        <label for="trans_date" class="col-form-label col-md-3 col-sm-3 label-align">Transaction Date <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="date" required="required" class="form-control form-control-sm" name="trans_date" id="trans_date" value="<?=date('Y-m-d')?>">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="medicine_id" class="col-form-label col-md-3 col-sm-3 label-align">Medicine Selected <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <select required="required" class="form-control form-control-sm" name="medicine_id" id="medicine_id">
                                <option value="">-- Select Medicine Item --</option>
                                <?php 
                                $med_res = $conn ? mysqli_query($conn, "SELECT logid, name FROM medicine ORDER BY name ASC") : null;
                                while($med_res && $med_row = mysqli_fetch_assoc($med_res)) {
                                    echo "<option value='".$med_row['logid']."'>".htmlspecialchars($med_row['name'])."</option>";
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="prescription_detail_id" class="col-form-label col-md-3 col-sm-3 label-align">Prescription Reference Line :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="number" class="form-control form-control-sm" name="prescription_detail_id" id="prescription_detail_id" placeholder="Optional Prescription Details ID Trace Code" autocomplete="off">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="quantity" class="col-form-label col-md-3 col-sm-3 label-align">Quantity <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="number" required="required" min="1" class="form-control form-control-sm" name="quantity" id="quantity" placeholder="e.g. 10">
                        </div>
                    </div>

                    <div class="ln_solid"></div>
                    <div class="form-group row text-center">
                        <div class="col-md-12">
                            <button type="submit" name="stock_transaction_save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
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
                    $result = $conn ? mysqli_query($conn,"SELECT * FROM stock_transaction WHERE logid='$rec'") : null;
                    while($result && $row = mysqli_fetch_assoc($result)) { ?> 
                        
                        <div class="item form-group row">
                            <label for="trans_type" class="col-form-label col-md-3 col-sm-3 label-align">Transaction Type <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <select class="form-control form-control-sm" name="trans_type" id="trans_type">
                                    <option value="Dispensed" <?php echo ($row['trans_type'] == 'Dispensed') ? 'selected':''?>>Dispensed</option>
                                    <option value="Received" <?php echo ($row['trans_type'] == 'Received') ? 'selected':''?>>Received</option>
                                    <option value="Adjustment" <?php echo ($row['trans_type'] == 'Adjustment') ? 'selected':''?>>Adjustment</option>
                                </select>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="recipient_id" class="col-form-label col-md-3 col-sm-3 label-align">Patient / Recipient <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <select required="required" class="form-control form-control-sm" name="recipient_id" id="recipient_id">
                                    <?php 
                                    $m_res = mysqli_query($conn, "SELECT logid, name FROM member ORDER BY name ASC");
                                    while($m_res && $m_row = mysqli_fetch_assoc($m_res)) {
                                        $sel = ($m_row['logid'] == $row['recipient_id']) ? 'selected' : '';
                                        echo "<option value='".$m_row['logid']."' $sel>".htmlspecialchars($m_row['name'])."</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="trans_date" class="col-form-label col-md-3 col-sm-3 label-align">Transaction Date <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="date" required="required" class="form-control form-control-sm" name="trans_date" id="trans_date" value="<?php echo htmlspecialchars($row['trans_date'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="medicine_id" class="col-form-label col-md-3 col-sm-3 label-align">Medicine Selected <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <select required="required" class="form-control form-control-sm" name="medicine_id" id="medicine_id">
                                    <?php 
                                    $med_res = mysqli_query($conn, "SELECT logid, name FROM medicine ORDER BY name ASC");
                                    while($med_res && $med_row = mysqli_fetch_assoc($med_res)) {
                                        $sel = ($med_row['logid'] == $row['medicine_id']) ? 'selected' : '';
                                        echo "<option value='".$med_row['logid']."' $sel>".htmlspecialchars($med_row['name'])."</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="prescription_detail_id" class="col-form-label col-md-3 col-sm-3 label-align">Prescription Reference Line :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="number" class="form-control form-control-sm" name="prescription_detail_id" id="prescription_detail_id" value="<?php echo htmlspecialchars($row['prescription_detail_id'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="quantity" class="col-form-label col-md-3 col-sm-3 label-align">Quantity <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="number" required="required" min="1" class="form-control form-control-sm" name="quantity" id="quantity" value="<?php echo htmlspecialchars($row['quantity'] ?? '')?>">
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group row text-center">
                            <div class="col-md-12">
                                <button type="submit" name="stock_transaction_update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
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
                    <h2><?=ucwords($display_title);?><small> history log</small></h2>
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
                                    <th>Type</th>
                                    <th>Patient / Recipient</th>
                                    <th>Transaction Date</th>
                                    <th>Medicine Item</th>
                                    <th>Prescription Item Line ID</th>
                                    <th>Quantity</th>
                                    <th>Handled By</th>
                                    <th>Timestamp</th>
                                    <th width="1%">Edit</th> 
                                    <th width="1%">Del</th> 
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Patient / Recipient</th>
                                    <th>Transaction Date</th>
                                    <th>Medicine Item</th>
                                    <th>Prescription Item Line ID</th>
                                    <th>Quantity</th>
                                    <th>Handled By</th>
                                    <th>Timestamp</th>
                                    <th></th>
                                    <th>
                                        <button name="stock_transaction_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onClick="javascript: return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
                                    </th> 
                                </tr>
                            </footer>
                            <tbody>
                            <?php $mycount=0;
                            if(isset($_POST['tbn_search']) && $conn) {
                                $search = $conn->real_escape_string($_POST['search']);
                                $result = mysqli_query($conn,"SELECT * FROM stock_transaction WHERE trans_type LIKE '%$search%' OR created_by LIKE '%$search%'");
                            } else {
                                $result = $conn ? mysqli_query($conn,"SELECT * FROM stock_transaction ORDER BY logid DESC") : null;
                            }

                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { $mycount++; ?> 				  
                                    <tr>
                                        <td><?=$mycount?></td>
                                        <td><span class="badge <?php echo ($row['trans_type']=='Dispensed') ? 'badge-info' : 'badge-success';?>"><?php echo htmlspecialchars($row['trans_type'])?></span></td>
                                        <td><?php
                                            $r_id = $row['recipient_id'];
                                            $r_q = mysqli_query($conn, "SELECT name FROM member WHERE logid='$r_id'");
                                            $r_data = mysqli_fetch_assoc($r_q);
                                            echo htmlspecialchars($r_data['name'] ?? 'Unknown Recipient');
                                        ?></td>
                                        <td><?php echo htmlspecialchars($row['trans_date'] ?? '')?></td>
                                        <td><?php
                                            $m_id = $row['medicine_id'];
                                            $m_q = mysqli_query($conn, "SELECT name FROM medicine WHERE logid='$m_id'");
                                            $m_data = mysqli_fetch_assoc($m_q);
                                            echo htmlspecialchars($m_data['name'] ?? 'Unknown Item');
                                        ?></td>
                                        <td><?php echo htmlspecialchars($row['prescription_detail_id'] ?? '-')?></td>
                                        <td><strong><?php echo htmlspecialchars($row['quantity'])?></strong></td>
                                        <td><?php echo htmlspecialchars($row['created_by'])?></td>
                                        <td><?php echo htmlspecialchars($row['created_on'])?></td>
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
                                    <td colspan="11" class="text-center" style="padding: 30px; color: #73879C; font-size: 14px;">
                                        <i class="fa fa-info-circle fa-lg"></i> No transaction logs trace found. Click <a href="index.php?page=<?=$page?>&part=1" class="alert-link"><u>Create New</u></a> to record stock dispensing.
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