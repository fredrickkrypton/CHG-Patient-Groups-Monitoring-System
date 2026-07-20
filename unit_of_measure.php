<?php
/*
Copyright (C) Innovee Systems Erps. This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-21 20:06:14

$page = "unit_of_measure";
if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

// Check if $_GET['D'] is set before processing to avoid the Undefined Key warning
if (function_exists('workout') && isset($_GET['D'])) { 
    workout($_GET['D']); 
}

// Fallback initialization to resolve the Undefined Variable warnings
if (!isset($part)) {
    $part = isset($_GET['part']) ? trim($_GET['part']) : '';
}

$timestamp = date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['unit_of_measure_delete'])) { caniacess($page."_delete"); }

if($_SESSION['stop']==1) {  
    include('stop.php'); 
} else {

    $alert = '';
    $error = '';
    $deleted = 0;
    $rec = isset($_GET['rec']) ? intval($_GET['rec']) : 0;

    if(isset($_POST['unit_of_measure_save'])) {
        $name = ucwords(trim($_POST['name'] ?? ''));
        $created_by = ucwords(($username));
        $created_on = ucwords(($timestamp));

        if($name != '') { 
            $sql = "INSERT INTO unit_of_measure (name,created_by,created_on) VALUES ('" . mysqli_real_escape_string($conn, $name) . "','$created_by','$created_on')";
            if ($conn->query($sql) === TRUE) {  
                $alert = 1; 
                audittrail($conn,'unit_of_measure','','inserting',$timestamp,$username); 
                $part = '';
            } else { 
                $error = "Error: you are duplicating data!"; 
            }
        } else {  
            $error = "Fill all mandatory fields in red.";  
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
    if(isset($_POST['unit_of_measure_update'])) {
        $name = ucwords(trim($_POST['name'] ?? ''));
        $created_by = ucwords(($username));
        $created_on = ucwords(($timestamp));
        $logid = intval($_POST['logid'] ?? 0);

        if($name != '' && $logid > 0) { 
            $sql = "UPDATE unit_of_measure SET name='" . mysqli_real_escape_string($conn, $name) . "',created_by='$created_by',created_on='$created_on' WHERE logid='$logid'";
            if ($conn->query($sql) === TRUE) {  
                $alert = 1; 
                audittrail($conn,'unit_of_measure',$logid,'updatings',$timestamp,$username); 
                $part = '';
            } else { 
                $error = "Error: you are duplicating data!"; 
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
    if(isset($_POST['unit_of_measure_delete'])) { 
        $count = intval($_POST['txtcount'] ?? 0);
        for($i=0;$i<$count;$i++){ 
            if(!empty($_POST['del'][$i])) { 
                $del_id = mysqli_real_escape_string($conn, $_POST['del'][$i]);
                audittrail($conn,'unit_of_measure',$del_id,'deleting',$timestamp,$username);
                $sql = "DELETE FROM unit_of_measure WHERE logid='$del_id'";
                if ($conn->query($sql) === TRUE) { 
                    $deleted = $deleted + 1; 
                }
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
    
    //---------------------------------------------------------------------------------------------------- 
    _title($title='Unit Of Measure'); 
    ?>

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
            color: #333333;
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
        .chg-db-error-box {
            background-color: #fdf2f2;
            border: 1px solid #f8b4b4;
            color: #9b1c1c;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
        }
    </style>

    <div class="chg-container">
        <div class="chg-view-header">
            <h2 class="chg-view-title">
                <?= htmlspecialchars(ucwords($title)); ?>
                <span style="font-size: 13px; color: #7f8c8d; font-weight: normal; margin-left: 5px;">
                    <?php echo ($part=='1') ? '» New Entry' : (($part=='2') ? '» Update Entry' : '» Existing Records Matrix'); ?>
                </span>
            </h2>
            <?php if ($part != '1') { ?>
                <div>
                    <a href="index.php?page=<?=$page?>&part=1" class="chg-blue-btn">
                        <i class="fa fa-plus"></i> Create New
                    </a>
                </div>
            <?php } ?>
        </div>

        <form method='POST' action='' name='unit_of_measure' enctype='multipart/form-data' id="uomForm">
            
            <?php if($part=='1') { ?>
                <!-- ==================== CREATE LAYOUT ==================== -->
                <div style="max-width: 600px; margin-top: 15px;">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight:bold; font-size:13px; margin-bottom:5px;">Name : <span style="color:#e74c3c;">*</span></label>
                        <input type="text" required='required' class="form-control" name="name" id="name" placeholder="Name" value="<? echo htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="off">
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 15px; border-top:1px solid #eef2f5; display:flex; gap:10px;">
                        <button type="submit" name="unit_of_measure_save" class="chg-blue-btn"><i class="fa fa-save"></i> Save</button>
                        <a href="index.php?page=<?=$page?>" class="btn btn-secondary btn-sm" style="padding:7px 14px;">Back</a>
                    </div>
                </div>

            <?php } elseif($part=='2') { ?>
                <!-- ==================== EDIT LAYOUT ==================== -->
                <div style="max-width: 600px; margin-top: 15px;">
                    <?php 
                    $rec_safe = mysqli_real_escape_string($conn, $rec);
                    try {
                        $result = mysqli_query($conn,"SELECT * FROM unit_of_measure WHERE logid='$rec_safe'");
                        if($row = mysqli_fetch_assoc($result)) { 
                        ?> 
                            <div style="margin-bottom: 15px;">
                                <label style="display:block; font-weight:bold; font-size:13px; margin-bottom:5px;">Name : <span style="color:#e74c3c;">*</span></label>
                                <input type="text" required='required' class="form-control" name="name" id="name" placeholder="Name" value="<? echo htmlspecialchars($row['name']) ?>" autocomplete="off">
                            </div>
                            
                            <div style="margin-top: 20px; padding-top: 15px; border-top:1px solid #eef2f5; display:flex; gap:10px;">
                                <button type="submit" name="unit_of_measure_update" class="chg-blue-btn"><i class="fa fa-save"></i> Update</button>
                                <a href="index.php?page=<?=$page?>" class="btn btn-secondary btn-sm" style="padding:7px 14px;">Cancel</a>
                            </div>
                            <input name="logid" type="hidden" value="<?=$row['logid']?>" />
                        <?php 
                        }
                    } catch (mysqli_sql_exception $e) {
                        echo "<div class='chg-db-error-box'><i class='fa fa-exclamation-triangle'></i> <strong>Database Notice:</strong> Table missing. Please construct the 'unit_of_measure' table inside phpMyAdmin.</div>";
                    }
                    ?> 
                </div>

            <?php } else { ?>
                <!-- ==================== DATA GRID TABLE LAYOUT ==================== -->
                <div style="overflow-x: auto; width:100%;">
                    <table class="chg-data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">#</th>
                                <th>Name</th>
                                <th>Created By</th>
                                <th>Created On</th>
                                <th style="width: 70px; text-align: center;">Edit</th> 
                                <th style="width: 70px; text-align: center;">Del</th> 
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $mycount = 0;
                        $table_exists = true;

                        try {
                            if(isset($_POST['tbn_search'])) {
                                $search = mysqli_real_escape_string($conn, $_POST['search'] ?? '');
                                $result = mysqli_query($conn,"SELECT * FROM unit_of_measure WHERE name LIKE '%$search%' OR created_by LIKE '%$search%' OR created_on LIKE '%$search%'");
                            } else {
                                $result = mysqli_query($conn,"SELECT * FROM unit_of_measure ORDER BY logid DESC");
                            }

                            if ($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { 
                                    $mycount++; 
                                    ?> 				  
                                    <tr>
                                        <td style="text-align: center;"><?=$mycount?></td>
                                        <td><strong><? echo htmlspecialchars($row['name'])?></strong></td>
                                        <td><? echo htmlspecialchars($row['created_by'])?></td>
                                        <td><? echo htmlspecialchars($row['created_on'])?></td>
                                        <td align="center">
                                            <a href="index.php?page=<?=$page?>&part=2&rec=<?=$row['logid']?>" style="color: #1ea7e1;"><i class="fa fa-pencil"></i> Edit</a>
                                        </td>
                                        <td align="center">
                                            <input name="del[]" type="checkbox" value="<?=$row['logid']?>" style="cursor:pointer;" />
                                        </td>
                                    </tr>
                                <?php 
                                } 
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center; color: #7f8c8d; padding: 25px; font-style: italic;'>No unit of measure records found.</td></tr>";
                            }
                        } catch (mysqli_sql_exception $e) {
                            $table_exists = false;
                            echo "<tr><td colspan='6' style='padding: 20px; text-align: center;'><div class='chg-db-error-box' style='margin:0;'><i class='fa fa-exclamation-triangle'></i> <strong>Database Error:</strong> Table 'afya.unit_of_measure' does not exist in your database. Please create it using phpMyAdmin to resolve this block.</div></td></tr>";
                        }
                        ?>
                        </tbody>
                        <?php if ($mycount > 0 && $table_exists) { ?>
                        <tfoot>
                            <tr style="background-color: #f5f5f5; font-weight: bold;">
                                <td colspan="5" style="text-align: right; vertical-align: middle; padding-right:15px;">With Selected Items:</td>
                                <td style="text-align: center;">
                                    <button name="unit_of_measure_delete" type="submit" class="btn btn-danger btn-sm" onClick="return confirm('Please confirm deletion of checked records');">
                                        <i class="fa fa-trash"></i> Del
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                        <?php } ?>
                    </table>
                </div>
                <input type="hidden" name="txtcount" id="txtcount" value="<? echo $mycount;?>"> 
            <?php } ?>
        </form>
    </div>
<?php } ?>