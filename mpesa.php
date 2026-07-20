<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBALS
global $title;
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

$page = "mpesa";

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
    if(isset($_POST['mpesa_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {  
    include('stop.php'); 
} else {

//---------------------------------------------------------------------------------------------------- 
// INSERT/SAVE LOGIC
if(isset($_POST['mpesa_save'])) {
    $transactiondate    = ucwords($_POST['transactiondate']);
    $phonenumber        = ucwords($_POST['phonenumber']);
    $amount             = ucwords($_POST['amount']);
    $mpesareceiptnumber = strtoupper($_POST['mpesareceiptnumber']); // standardizing transaction codes to uppercase

    $created_by = $username;
    $created_on = $timestamp;

    if(trim($transactiondate) != '' && trim($phonenumber) != '' && trim($amount) != '' && trim($mpesareceiptnumber) != '') { 
        $sql = "INSERT INTO mpesa (transactiondate, phonenumber, amount, mpesareceiptnumber, username, timestamp)
                VALUES ('$transactiondate', '$phonenumber', '$amount', '$mpesareceiptnumber', '$created_by', '$created_on')";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'mpesa', '', 'inserting', $timestamp, $username); }
        } else { 
            $error = "Error saving deposit transaction."; 
            if($conn) { $error .= " " . $conn->error; }
        }
    } else {  
        $error = "All fields are mandatory. Please fill in missing details.";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Deposit record added successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// UPDATE LOGIC
if(isset($_POST['mpesa_update'])) {
    $transactiondate    = ucwords($_POST['transactiondate']);
    $phonenumber        = ucwords($_POST['phonenumber']);
    $amount             = ucwords($_POST['amount']);
    $mpesareceiptnumber = strtoupper($_POST['mpesareceiptnumber']);
    $logid              = $_POST['logid'];

    if(trim($mpesareceiptnumber) != '') { 
        $sql = "UPDATE mpesa SET transactiondate='$transactiondate', phonenumber='$phonenumber', amount='$amount', mpesareceiptnumber='$mpesareceiptnumber' WHERE logid='$logid'";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'mpesa', $logid, 'updatings', $timestamp, $username); }
        } else { 
            $error = "Error updating deposition reference log."; 
        }
    } else {  
        $error = "Receipt reference cannot be empty.";  
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
if(isset($_POST['mpesa_delete'])) { 
    $count = isset($_POST['txtcount']) ? $_POST['txtcount'] : 0;
    for($i=0; $i<$count; $i++) { 
        if(!empty($_POST['del'][$i])) { 
            if(function_exists('audittrail')) {
                audittrail($conn, 'mpesa', $_POST['del'][$i], 'deleting', $timestamp, $username);
            }
            $sql = "DELETE FROM mpesa WHERE logid='".$_POST['del'][$i]."'";
            if ($conn && $conn->query($sql) === TRUE) { $deleted++; }
        }  
    } 
    if ($deleted > 0) { $alert = 1; } else { $error = "No record selected"; }
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Transaction removed safely.', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }	  
}

$display_title = 'Deposits Tracking';
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
        <form method='POST' action='' class='form-horizontal form-label-left' name='mpesa' style='min-width:100%' enctype='multipart/form-data'>
                                            
        <?php if($part=='1') { ?>
        <div class='col-md-8 offset-md-2 col-sm-12'>
            <div class='x_panel'>
                <div class='x_title'>
                    <h2><?=ucwords($display_title);?><small> new ledger log</small></h2>
                    <div class='clearfix'></div>
                </div>
                <div class='x_content'>
                    <br />
                    
                    <div class="item form-group row">
                        <label for="transactiondate" class="col-form-label col-md-3 col-sm-3 label-align">Transaction Date <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="date" required="required" class="form-control form-control-sm" name="transactiondate" id="transactiondate" value="<?=date('Y-m-d')?>">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="phonenumber" class="col-form-label col-md-3 col-sm-3 label-align">Phone Number <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" required="required" class="form-control form-control-sm" name="phonenumber" id="phonenumber" placeholder="e.g. 254712345678" autocomplete="off">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="amount" class="col-form-label col-md-3 col-sm-3 label-align">Amount Deposited <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="number" step="0.01" required="required" class="form-control form-control-sm" name="amount" id="amount" placeholder="0.00">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="mpesareceiptnumber" class="col-form-label col-md-3 col-sm-3 label-align">Receipt Reference ID <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" required="required" class="form-control form-control-sm" name="mpesareceiptnumber" id="mpesareceiptnumber" placeholder="e.g. QX789JK12" autocomplete="off">
                        </div>
                    </div>

                    <div class="ln_solid"></div>
                    <div class="form-group row text-center">
                        <div class="col-md-12">
                            <button type="submit" name="mpesa_save" class="btn btn-success btn-sm"><span>Save Log</span> <i class="fa fa-save"></i></button>
                            &nbsp; &nbsp;
                            <a href="index.php?page=<?=$page?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
                            &nbsp; &nbsp;
                            <a href="index.php?page=<?=$page?>" class="btn btn-info btn-sm"><span>View Records</span> <i class="fa fa-search"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php } elseif($part=='2') { ?>
        <div class='col-md-8 offset-md-2 col-sm-12'>
            <div class='x_panel'>
                <div class='x_title'>
                    <h2><?=ucwords($display_title);?><small> edit transaction summary</small></h2>
                    <div class='clearfix'></div>
                </div>
                <div class='x_content'>
                    <br />
                    <?php 
                    $result = $conn ? mysqli_query($conn,"SELECT * FROM mpesa WHERE logid='$rec'") : null;
                    while($result && $row = mysqli_fetch_assoc($result)) { ?> 
                        
                        <div class="item form-group row">
                            <label for="transactiondate" class="col-form-label col-md-3 col-sm-3 label-align">Transaction Date <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" required="required" class="form-control form-control-sm" name="transactiondate" id="transactiondate" value="<?php echo htmlspecialchars($row['transactiondate'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="phonenumber" class="col-form-label col-md-3 col-sm-3 label-align">Phone Number <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" required="required" class="form-control form-control-sm" name="phonenumber" id="phonenumber" value="<?php echo htmlspecialchars($row['phonenumber'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="amount" class="col-form-label col-md-3 col-sm-3 label-align">Amount Deposited <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="number" step="0.01" required="required" class="form-control form-control-sm" name="amount" id="amount" value="<?php echo htmlspecialchars($row['amount'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="mpesareceiptnumber" class="col-form-label col-md-3 col-sm-3 label-align">Receipt Reference ID <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" required="required" class="form-control form-control-sm" name="mpesareceiptnumber" id="mpesareceiptnumber" value="<?php echo htmlspecialchars($row['mpesareceiptnumber'] ?? '')?>">
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group row text-center">
                            <div class="col-md-12">
                                <button type="submit" name="mpesa_update" class="btn btn-success btn-sm"><span>Update Log</span> <i class="fa fa-save"></i></button>
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
                    <h2><?=ucwords($display_title);?><small> incoming transaction trace logs</small></h2>
                    <ul class="nav navbar-right panel_toolbox" style="list-style: none;">
                        <li><a href="index.php?page=<?=$page?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Add Deposit Entry</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div class="custom-table-wrapper">
                        <table id="datatable" role="grid" class="table table-striped table-bordered dataTable table-sm">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Transaction Date</th>
                                    <th>Payer Account / Member Name</th>
                                    <th>Amount Paid</th>
                                    <th>Receipt Ref Number</th>
                                    <th>Allocation Type</th>
                                    <th>Handled By</th>
                                    <th>System Timestamp</th>
                                    <th width="1%">Edit</th> 
                                    <th width="1%">Del</th> 
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Transaction Date</th>
                                    <th>Payer Account / Member Name</th>
                                    <th>Amount Paid</th>
                                    <th>Receipt Ref Number</th>
                                    <th>Allocation Type</th>
                                    <th>Handled By</th>
                                    <th>System Timestamp</th>
                                    <th></th>
                                    <th>
                                        <button name="mpesa_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onClick="javascript: return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
                                    </th> 
                                </tr>
                            </footer>
                            <tbody>
                            <?php $mycount=0;
                            if(isset($_POST['tbn_search']) && $conn) {
                                $search = $conn->real_escape_string($_POST['search']);
                                $result = mysqli_query($conn,"SELECT * FROM mpesa WHERE phonenumber LIKE '%$search%' OR mpesareceiptnumber LIKE '%$search%' OR username LIKE '%$search%'");
                            } else {
                                $result = $conn ? mysqli_query($conn,"SELECT * FROM mpesa ORDER BY logid DESC") : null;
                            }

                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { $mycount++; ?> 				  
                                    <tr>
                                        <td><?=$mycount?></td>
                                        <td><?php echo htmlspecialchars($row['transactiondate'] ?? '')?></td>
                                        <td><?php
                                            $phone = $row['phonenumber'];
                                            $m_q = mysqli_query($conn, "SELECT name FROM member WHERE phone_no='$phone'");
                                            if ($m_q && mysqli_num_rows($m_q) > 0) {
                                                $m_data = mysqli_fetch_assoc($m_q);
                                                echo htmlspecialchars($m_data['name']) . ' - (' . htmlspecialchars($phone) . ')';
                                            } else {
                                                echo 'Unmapped Source - (' . htmlspecialchars($phone) . ')';
                                            }
                                        ?></td>
                                        <td><strong><?php echo number_format((float)($row['amount'] ?? 0), 2)?></strong></td>
                                        <td><code class="text-dark"><strong><?php echo htmlspecialchars($row['mpesareceiptnumber'] ?? '')?></strong></td>
                                        <td><span class="badge badge-success">Contribution / Premium</span></td>
                                        <td><?php echo htmlspecialchars($row['username'] ?? '')?></td>
                                        <td><?php echo htmlspecialchars($row['timestamp'] ?? '')?></td>
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
                                        <i class="fa fa-info-circle fa-lg"></i> No mobile deposits logged. Click <a href="index.php?page=<?=$page?>&part=1" class="alert-link"><u>Add Deposit Entry</u></a> to balance the module tracking system.
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