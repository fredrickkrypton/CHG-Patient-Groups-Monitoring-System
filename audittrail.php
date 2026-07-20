<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:05:32
 
$page="audittrail";
if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

if (function_exists('workout') && isset($_GET['D'])) { 
    workout($_GET['D']); 
}

// Pre-initialize variables to prevent notice chains
if (!isset($part)) { 
    $part = ''; 
}
$timestamp=date('Y-m-d H:i:s');
$alert = '';
$error = '';
$deleted = 0;

if(function_exists('caniacess')) {
    if($part=='') { caniacess($page."_view"); }
    if($part=='1') { caniacess($page."_add"); }
    if($part=='2') { caniacess($page."_edit"); }
    if(isset($_POST['audittrail_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop']==1) {  
    include('stop.php'); 
} else {

if(isset($_POST['audittrail_save'])) {
    $tablename=ucwords(($_POST['tablename'] ?? ''));
    $id=ucwords(($_POST['id'] ?? ''));
    $action=ucwords(($_POST['action'] ?? ''));

    if(trim($tablename)!='') { 
        $sql = "INSERT INTO audittrail (tablename,id,action,timestamp,username)
        VALUES ('$tablename','$id','$action','$timestamp','$username')";
        if ($conn->query($sql) === TRUE) {  
            $alert=1; 
            if(function_exists('audittrail')) { audittrail($conn,'audittrail','','inserting',$timestamp,$username); }
        } else { 
            $error="Error: you are duplicating data!"; 
        }
    } else {  
        $error="Fill all mandatory fields in red.";  
    }  
    
    if($alert!='') {
        echo "<script> window.addEventListener('load', function () { 
        if(typeof PNotify !== 'undefined') {
            new PNotify({ title: 'Success', text: 'Data saved successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); 
        } else { alert('Data saved successfully!'); }
        }) </script>";
    }
    if($error!='') {
        echo "<script> window.addEventListener('load', function () { 
        if(typeof PNotify !== 'undefined') {
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); 
        } else { alert('$error'); }
        }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['audittrail_update'])) {
    $tablename=ucwords(($_POST['tablename'] ?? ''));
    $id=ucwords(($_POST['id'] ?? ''));
    $action=ucwords(($_POST['action'] ?? ''));
    $logid=$_POST['logid'] ?? 0;

    if(trim($tablename)!='') { 
        $sql = "UPDATE audittrail SET tablename='$tablename',id='$id',action='$action',timestamp='$timestamp',username='$username' WHERE logid='$logid'";
        if ($conn->query($sql) === TRUE) {  
            $alert=1; 
            if(function_exists('audittrail')) { audittrail($conn,'audittrail',$logid,'updatings',$timestamp,$username); }
        } else { 
            $error="Error: you are duplicating data!"; 
        }
    } else {  
        $error="Fill all mandatory fields in red.";  
    }  
    
    if($alert!='') {
        echo "<script> window.addEventListener('load', function () { 
        if(typeof PNotify !== 'undefined') {
            new PNotify({ title: 'Success', text: 'Data updated successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); 
        } else { alert('Data updated successfully!'); }
        }) </script>";
    }
    if($error!='') {
        echo "<script> window.addEventListener('load', function () { 
        if(typeof PNotify !== 'undefined') {
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); 
        } else { alert('$error'); }
        }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['audittrail_delete'])) { 
    $count = $_POST['txtcount'] ?? 0;
    for($i=0;$i<$count;$i++){ 
        if(!empty($_POST['del'][$i])) { 
            if(function_exists('audittrail')) { audittrail($conn,'audittrail',$_POST['del'][$i],'deleting',$timestamp,$username); }
            $sql = "DELETE FROM audittrail WHERE logid='".mysqli_real_escape_string($conn, $_POST['del'][$i])."'";
            if ($conn->query($sql) === TRUE) { $deleted=$deleted+1; }
        }  
    } 
    if ($deleted>0) { $alert=1; } else { $error="No record selected"; }
    if($alert!='') {
        echo "<script> window.addEventListener('load', function () { 
        if(typeof PNotify !== 'undefined') {
            new PNotify({ title: 'Success', text: 'Deleted successfully', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); 
        } else { alert('Deleted successfully'); }
        }) </script>";
    }
    if($error!='') {
        echo "<script> window.addEventListener('load', function () { 
        if(typeof PNotify !== 'undefined') {
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); 
        } else { alert('$error'); }
        }) </script>";
    }	  
}

//---------------------------------------------------------------------------------------------------- 
if(function_exists('_title')) { _title($title='Audit Trail'); } else { $title='Audit Trail'; } ?> 

<!-- FIX 2: Correct Asset Ordering Sequence -->
<script src="vendors/jquery/dist/jquery.min.js"></script>
<script src="vendors/moment/min/moment.min.js"></script>
<script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
<script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
<link rel="stylesheet" type="text/css" media="all" href="vendors/bootstrap-daterangepicker/daterangepicker.css" />
      
<form method='POST' action='' class='rounded page-filter-form' name='modeoftravel' enctype='multipart/form-data'>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
  
  <!-- Modern Clean Flex Header Layout -->
  <div class="x_title_flex">
      <h2><?=ucwords($title);?></h2> 
      
      <div class="filter-flex-container">
          <div class="filter-item">
              <label for="start_date">Start Date</label>
              <div class="input-group picker-group-start">
                  <!-- FIX 1: Enforced safe initialization fallback value and added native browser readonly protection -->
                  <input type="text" class="form-control form-control-sm manda" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('d-m-Y'); ?>" name="start_date" id="start_date" autocomplete="off" readonly style="background-color: #fff; cursor: pointer;"> 
                  <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
              </div>
          </div>
          
          <div class="filter-item">
              <label for="end_date">End Date</label>
              <div class="input-group picker-group-end">
                  <!-- FIX 1: Enforced safe initialization fallback value and added native browser readonly protection -->
                  <input type="text" class="form-control form-control-sm manda" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('d-m-Y'); ?>" name="end_date" id="end_date" autocomplete="off" readonly style="background-color: #fff; cursor: pointer;"> 
                  <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
              </div>
          </div>

          <div class="filter-item action-item">
              <label>&nbsp;</label>
              <input type="submit" name="tbn_search" id="tbn_search" value="Filter View" class="btn btn-primary btn-sm btn-block"/>
          </div>
      </div>
  </div>

<div class="x_content" id="datepicker1">
<table id="example" style="width: 100%;" role="grid" class="table table-striped table-bordered table-sm table-responsive custom-data-table">
<thead>
    <tr>
        <th>Timestamp</th>
        <th>Table name</th>
        <th>Id</th>
        <th>Action</th>
        <th>Username</th>
    </tr>
</thead>
<tbody>
<?php 
$mycount=0;
if(isset($_POST['tbn_search'])) {
    $from = function_exists('dateto_mysql') ? dateto_mysql($_POST['start_date'])." 00:00:00" : date('Y-m-d')." 00:00:00";
    $to = function_exists('dateto_mysql') ? dateto_mysql($_POST['end_date'])." 23:59:59" : date('Y-m-d')." 23:59:59";
    $search = mysqli_real_escape_string($conn, $_POST['search'] ?? '');
    
    $result = mysqli_query($conn, "SELECT * from audittrail where timestamp>='$from' and timestamp<='$to' and (username like '%$search%' OR action like '%$search%' OR tablename like '%$search%') order by timestamp");   
} else {
    $result = mysqli_query($conn,"SELECT * FROM audittrail order by timestamp DESC limit 10");
}

if ($result) {
    while($row = mysqli_fetch_assoc($result)) { 
        $mycount=$mycount+1; 
        ?> 
        <tr>
            <td><span style="white-space:nowrap"><?php echo htmlspecialchars($row['timestamp'] ?? ''); ?></span></td>
            <td style="white-space:nowrap"><?php echo htmlspecialchars($row['tablename'] ?? ''); ?></td>
            <td><span style="white-space:nowrap"><?php echo htmlspecialchars($row['id'] ?? ''); ?></span></td>
            <td><span style="white-space:nowrap"><?php echo htmlspecialchars($row['action'] ?? ''); ?></span></td>
            <td><span style="white-space:nowrap"><?php echo htmlspecialchars($row['username'] ?? ''); ?></span></td>
        </tr>
    <?php 
    } 
} ?>
</tbody>
</table>
</div>
</div>
</div> 
</form>

<script>
$(document).ready(function() {
    $('.picker-group-start').daterangepicker({
        locale: { format: 'DD-MM-YYYY' },
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: true
    }, function(start) {
        $('#start_date').val(start.format('DD-MM-YYYY'));
    });

    $('.picker-group-end').daterangepicker({
        locale: { format: 'DD-MM-YYYY' },
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: true
    }, function(end) {
        $('#end_date').val(end.format('DD-MM-YYYY'));
    });

    if ($.fn.DataTable) {
        $('#example').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
        });
    }
});
</script>

<!-- Clean Dashboard Flex Layout CSS Styles -->
<style>
.x_title_flex {
    border-bottom: 2px solid #E6ECEF !important;
    padding-bottom: 12px !important;
    margin-bottom: 20px !important;
    display: block !important;
}
.x_title_flex h2 {
    margin: 0 0 10px 0 !important;
    font-size: 18px !important;
    font-weight: 600 !important;
    color: #2A3F54 !important;
    float: none !important;
}
.filter-flex-container {
    display: flex !important;
    flex-wrap: nowrap !important;
    flex-direction: row !important;
    gap: 15px !important;
    align-items: flex-end !important;
    background: #f7f9fa !important;
    padding: 12px 15px !important;
    border-radius: 6px !important;
    border: 1px solid #e5e9ec !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
.filter-item {
    flex: 1 !important;
    min-width: 150px !important;
    max-width: 250px !important;
    display: block !important;
}
.filter-item.action-item {
    flex: 0.5 !important;
    min-width: 120px !important;
}
.filter-item label {
    font-size: 12px !important;
    font-weight: 700 !important;
    color: #555 !important;
    margin-bottom: 5px !important;
    display: block !important;
}
.filter-item input {
    height: 34px !important;
    border-radius: 4px !important;
    width: 100% !important;
    display: block !important;
}
.input-group {
    display: flex !important;
    position: relative !important;
    width: 100% !important;
    cursor: pointer !important;
}
.input-group-addon {
    padding: 6px 12px !important;
    background-color: #eee !important;
    border: 1px solid #ccc !important;
    border-left: 0 !important;
    border-radius: 0 4px 4px 0 !important;
    display: flex !important;
    align-items: center !important;
    cursor: pointer !important; 
}
.input-group-addon:hover {
    background-color: #ddd !important;
}
.custom-data-table {
    margin-top: 15px !important;
    width: 100% !important;
}
.page-filter-form {
    width: 100% !important;
}
@media (max-width: 768px) {
    .filter-flex-container {
        flex-direction: column !important;
        align-items: stretch !important;
        flex-wrap: wrap !important;
    }
    .filter-item {
        max-width: 100% !important;
        width: 100% !important;
    }
}
</style>
<input name="txtcount" type="hidden" value="<?php echo $mycount; ?>">
<?php } ?>