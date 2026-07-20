<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBALS
global $title;
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

$page = "sms";

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
    if(isset($_POST['sms_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {  
    include('stop.php'); 
} else {

//---------------------------------------------------------------------------------------------------- 
// INSERT/SAVE LOGIC (Supports Single or Multi Comma-Separated Outbox Batches)
if(isset($_POST['sms_save'])) {
    $date    = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $smstype = ucwords($_POST['smstype']);
    $phone   = trim($_POST['phone']);
    $sms     = ucwords($_POST['sms']);
    $sent    = isset($_POST['sent']) ? intval($_POST['sent']) : 0;

    if (function_exists('dateto_mysql')) { 
        $date = dateto_mysql($date); 
    }

    if($phone != '' && $sms != '') { 
        // Explode numbers separated by commas to execute transactional loops safely
        $array = explode(',', $phone);
        $success_flag = false;
        
        foreach($array as $num) {
            $clean_num = trim($num);
            if(!empty($clean_num)) {
                $sql = "INSERT INTO sms (date, smstype, phone, sms, sent, timestamp)
                        VALUES ('$date', '$smstype', '$clean_num', '$sms', '$sent', '$timestamp')";
                if ($conn && $conn->query($sql) === TRUE) {
                    $success_flag = true;
                }
            }
        }
        
        if ($success_flag) {
            $alert = 1;
            if(function_exists('audittrail')) { audittrail($conn, 'sms', '', 'inserting', $timestamp, $username); }
        } else {
            $error = "Error executing outbound queue loop strings.";
        }
    } else {  
        $error = "Recipient phone number(s) and message bodies cannot be left blank.";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'SMS added to outbox queue successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// UPDATE LOGIC
if(isset($_POST['sms_update'])) {
    $date    = $_POST['date'];
    $smstype = ucwords($_POST['smstype']);
    $phone   = trim($_POST['phone']);
    $sms     = ucwords($_POST['sms']);
    $sent    = intval($_POST['sent']);
    $logid   = $_POST['logid'];

    if (function_exists('dateto_mysql')) { 
        $date = dateto_mysql($date); 
    }

    if($phone != '') { 
        $sql = "UPDATE sms SET date='$date', smstype='$smstype', phone='$phone', sms='$sms', sent='$sent' WHERE logid='$logid'";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, 'sms', $logid, 'updatings', $timestamp, $username); }
        } else { 
            $error = "Database edit update failure."; 
        }
    } else {  
        $error = "Phone destination parameter missing.";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'SMS metadata altered cleanly.', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// DELETE LOGIC
if(isset($_POST['sms_delete'])) { 
    $count = isset($_POST['txtcount']) ? $_POST['txtcount'] : 0;
    for($i=0; $i<$count; $i++) { 
        if(!empty($_POST['del'][$i])) { 
            if(function_exists('audittrail')) {
                audittrail($conn, 'sms', $_POST['del'][$i], 'deleting', $timestamp, $username);
            }
            $sql = "DELETE FROM sms WHERE logid='".$_POST['del'][$i]."'";
            if ($conn && $conn->query($sql) === TRUE) { $deleted++; }
        }  
    } 
    if ($deleted > 0) { $alert = 1; } else { $error = "No item checked to purge."; }
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'Selected queue messages deleted.', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }	  
}

$display_title = 'SMS Alerts Dispatcher';
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
        <form method='POST' action='' class='form-horizontal form-label-left' name='sms' style='min-width:100%' enctype='multipart/form-data'>
                                            
        <?php if($part=='1') { ?>
        <div class='col-md-8 offset-md-2 col-sm-12'>
            <div class='x_panel'>
                <div class='x_title'>
                    <h2><?=ucwords($display_title);?><small> dispatch center</small></h2>
                    <div class='clearfix'></div>
                </div>
                <div class='x_content'>
                    <br />
                    
                    <div class="item form-group row">
                        <label for="date" class="col-form-label col-md-3 col-sm-3 label-align">Schedule Date <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="date" required="required" class="form-control form-control-sm" name="date" id="date" value="<?=date('Y-m-d')?>">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="smstype" class="col-form-label col-md-3 col-sm-3 label-align">Alert Category :</label>
                        <div class="col-md-8 col-sm-8">
                            <select class="form-control form-control-sm" name="smstype" id="smstype">
                                <option value="Reminder">Prescription Reminder</option>
                                <option value="Payment">Payment Confirmation</option>
                                <option value="Stock Notification">Stock Notice</option>
                                <option value="General Broadcast">General System Broadcast</option>
                            </select>
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="phone" class="col-form-label col-md-3 col-sm-3 label-align">Phone Number(s) <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" required="required" class="form-control form-control-sm" name="phone" id="phone" placeholder="e.g. 254712345678, 254787654321" autocomplete="off">
                            <small class="text-muted">Separate multiple target contacts cleanly via commas (<code>,</code>).</small>
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="sms" class="col-form-label col-md-3 col-sm-3 label-align">SMS Message Body <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <textarea required="required" maxlength="160" class="form-control form-control-sm" rows="4" name="sms" id="sms" placeholder="Write message body here... (Max 160 characters)"></textarea>
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="sent" class="col-form-label col-md-3 col-sm-3 label-align">Immediate Pipeline Status :</label>
                        <div class="col-md-8 col-sm-8">
                            <select class="form-control form-control-sm" name="sent" id="sent">
                                <option value="0">Pending Gateway Processing</option>
                                <option value="1">Mark Sent Natively</option>
                            </select>
                        </div>
                    </div>

                    <div class="ln_solid"></div>
                    <div class="form-group row text-center">
                        <div class="col-md-12">
                            <button type="submit" name="sms_save" class="btn btn-success btn-sm"><span>Queue & Dispatch</span> <i class="fa fa-paper-plane"></i></button>
                            &nbsp; &nbsp;
                            <a href="index.php?page=<?=$page?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
                            &nbsp; &nbsp;
                            <a href="index.php?page=<?=$page?>" class="btn btn-info btn-sm"><span>View Outbox Log</span> <i class="fa fa-search"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php } elseif($part=='2') { ?>
        <div class='col-md-8 offset-md-2 col-sm-12'>
            <div class='x_panel'>
                <div class='x_title'>
                    <h2><?=ucwords($display_title);?><small> modify record context</small></h2>
                    <div class='clearfix'></div>
                </div>
                <div class='x_content'>
                    <br />
                    <?php 
                    $result = $conn ? mysqli_query($conn,"SELECT * FROM sms WHERE logid='$rec'") : null;
                    while($result && $row = mysqli_fetch_assoc($result)) { ?> 
                        
                        <div class="item form-group row">
                            <label for="date" class="col-form-label col-md-3 col-sm-3 label-align">Schedule Date <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" required="required" class="form-control form-control-sm" name="date" id="date" value="<?php echo htmlspecialchars($row['date'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="smstype" class="col-form-label col-md-3 col-sm-3 label-align">Alert Category :</label>
                            <div class="col-md-8 col-sm-8">
                                <select class="form-control form-control-sm" name="smstype" id="smstype">
                                    <option value="Reminder" <?php echo ($row['smstype'] ?? '' == 'Reminder') ? 'selected':''?>>Prescription Reminder</option>
                                    <option value="Payment" <?php echo ($row['smstype'] ?? '' == 'Payment') ? 'selected':''?>>Payment Confirmation</option>
                                    <option value="Stock Notification" <?php echo ($row['smstype'] ?? '' == 'Stock Notification') ? 'selected':''?>>Stock Notice</option>
                                    <option value="General Broadcast" <?php echo ($row['smstype'] ?? '' == 'General Broadcast') ? 'selected':''?>>General System Broadcast</option>
                                </select>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="phone" class="col-form-label col-md-3 col-sm-3 label-align">Phone Number <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" required="required" class="form-control form-control-sm" name="phone" id="phone" value="<?php echo htmlspecialchars($row['phone'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="sms" class="col-form-label col-md-3 col-sm-3 label-align">SMS Message Body <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <textarea required="required" maxlength="160" class="form-control form-control-sm" rows="4" name="sms" id="sms"><?php echo htmlspecialchars($row['sms'] ?? '')?></textarea>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="sent" class="col-form-label col-md-3 col-sm-3 label-align">Pipeline Status :</label>
                            <div class="col-md-8 col-sm-8">
                                <select class="form-control form-control-sm" name="sent" id="sent">
                                    <option value="0" <?php echo ($row['sent'] == 0) ? 'selected':''?>>Pending Gateway Processing</option>
                                    <option value="1" <?php echo ($row['sent'] == 1) ? 'selected':''?>>Mark Sent Natively</option>
                                </select>
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group row text-center">
                            <div class="col-md-12">
                                <button type="submit" name="sms_update" class="btn btn-success btn-sm"><span>Update Log</span> <i class="fa fa-save"></i></button>
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
                    <h2><?=ucwords($display_title);?><small> historic pipeline outbox logs</small></h2>
                    <ul class="nav navbar-right panel_toolbox" style="list-style: none;">
                        <li><a href="index.php?page=<?=$page?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Compose Alert SMS</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div class="custom-table-wrapper">
                        <table id="datatable" role="grid" class="table table-striped table-bordered dataTable table-sm">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Schedule Date</th>
                                    <th>Recipient Phone Target</th>
                                    <th>Message Body Text</th>
                                    <th>Delivery Status</th>
                                    <th>System Timestamp</th>
                                    <th width="1%">Edit</th> 
                                    <th width="1%">Del</th> 
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Schedule Date</th>
                                    <th>Recipient Phone Target</th>
                                    <th>Message Body Text</th>
                                    <th>Delivery Status</th>
                                    <th>System Timestamp</th>
                                    <th></th>
                                    <th>
                                        <button name="sms_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onClick="javascript: return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
                                    </th> 
                                </tr>
                            </footer>
                            <tbody>
                            <?php $mycount=0;
                            if(isset($_POST['tbn_search']) && $conn) {
                                $search = $conn->real_escape_string($_POST['search']);
                                $result = mysqli_query($conn,"SELECT * FROM sms WHERE phone LIKE '%$search%' OR sms LIKE '%$search%' OR smstype LIKE '%$search%'");
                            } else {
                                $result = $conn ? mysqli_query($conn,"SELECT * FROM sms ORDER BY date DESC LIMIT 100") : null;
                            }

                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { $mycount++; ?> 				  
                                    <tr>
                                        <td><?=$mycount?></td>
                                        <td><?php
                                            if (function_exists('dateto_display')) {
                                                echo htmlspecialchars(dateto_display($row['date']));
                                            } else {
                                                echo htmlspecialchars($row['date'] ?? '');
                                            }
                                        ?></td>
                                        <td><code class="text-dark"><strong><?php echo htmlspecialchars($row['phone'] ?? '')?></strong></code></td>
                                        <td><small><?php echo htmlspecialchars($row['sms'] ?? '')?></small></td>
                                        <td>
                                            <?php if(($row['sent'] ?? 0) == 1) { ?>
                                                <span class="badge badge-success"><i class="fa fa-check"></i> Sent / Handover</span>
                                            <?php } else { ?>
                                                <span class="badge badge-warning"><i class="fa fa-clock-o"></i> Pending Queue</span>
                                            <?php } ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($row['timestamp'] ?? '')?></small></td>
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
                                    <td colspan="8" class="text-center" style="padding: 30px; color: #73879C; font-size: 14px;">
                                        <i class="fa fa-envelope-o fa-lg"></i> Outbox system pipeline is empty. Click <a href="index.php?page=<?=$page?>&part=1" class="alert-link"><u>Compose Alert SMS</u></a> to push alerts.
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