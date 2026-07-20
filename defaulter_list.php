<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:33:37
 
$page="defaulter_list";

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

if (function_exists('workout') && isset($_GET['D'])) { 
    workout($_GET['D']); 
}

// Pre-initialize $part to prevent "Undefined Variable" notice chains
if (!isset($part)) { 
    $part = ''; 
}

$timestamp=date('Y-m-d H:i:s');

// Clean permission evaluations
if(function_exists('caniacess')) {
    if($part=='') { caniacess($page."_view"); }
    if($part=='1') { caniacess($page."_add"); }
    if($part=='2') { caniacess($page."_edit"); }
    if(isset($_POST['0_users_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop']==1)
{  
    include('stop.php'); 
}
else 
{
 
function mycontribution($date,$memberid)
{ 
    global $conn;
    $amount = 0;
    $date = mysqli_real_escape_string($conn, $date);
    $memberid = mysqli_real_escape_string($conn, $memberid);
    
    $qresult = mysqli_query($conn,"SELECT sum(amount) as amount FROM mpesa where memberid='$memberid' and transactiondate like '$date%'");   
    if($qresult && $drow = mysqli_fetch_assoc($qresult))
    {
        $amount = $drow['amount'] ?? 0;	
    }
    return $amount;
}
 //---------------------------------------------------------------------------------------------------- 
 if(function_exists('_title')) { _title($title='Defaulter List'); } else { $title='Defaulter List'; } 
 ?> 
<script src="vendors/moment/min/moment.min.js"></script>
<script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
<script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
<link rel="stylesheet" type="text/css" media="all" href="daterangepicker.css" />
<script src="vendors/jquery/dist/jquery.min.js"></script>
      
<form method='POST' action='' class='rounded page-filter-form' name='modeoftravel' enctype='multipart/form-data'>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
  <div class="x_title_flex">
      <h2><?=ucwords($title);?></h2>
      
      <!-- Flex Filter Bar Container -->
      <div class="filter-flex-container">
          <div class="filter-item long-item">
              <label for="group_id">Group Filter</label>
              <select class="form-control manda" id="group_id" name="group_id">
              <?php if(isset($_POST['group_id']) && $_POST['group_id'] != '') { 
                  $p_group = mysqli_real_escape_string($conn, $_POST['group_id']);
                  $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group where logid='$p_group'");
                  while($drow = mysqli_fetch_assoc($qresult)) { ?>
                      <option value="<?php echo htmlspecialchars($drow['logid']); ?>"><?php echo htmlspecialchars($drow['name']); ?></option>
                  <?php }
               } ?>

               <?php
              if(isset($_SESSION['userrole']) && $_SESSION['userrole']==4) {
                  $group_clause = " where logid='".mysqli_real_escape_string($conn, $_SESSION['groupid'])."' ";
              } else {
                  $group_clause = " ";
                  ?>
                  <option value="">All Groups</option>
                  <?php
              }
              $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group $group_clause ORDER BY name");
              while($drow = mysqli_fetch_assoc($qresult)) { ?>
                  <option value="<?php echo htmlspecialchars($drow['logid']); ?>"><?php echo htmlspecialchars($drow['name']); ?></option>
              <?php } ?>
              </select>
          </div>
          
          <div class="filter-item">
              <label for="start_date">Start Date</label>
              <div class="input-group datex">
                  <input type="text" class="form-control form-control-sm manda" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('d-m-Y'); ?>" name="start_date" id="start_date" > 
                  <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
              </div>
          </div>

          <div class="filter-item">
              <label for="end_date">End Date</label>
              <div class="input-group datex">
                  <input type="text" class="form-control form-control-sm manda" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('d-m-Y'); ?>" name="end_date" id="end_date" > 
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
        <th> Names</th>
        <th colspan="2">Group Leader</th>
    </tr>
</thead>
<tbody>
<?php 
$mycount=0;
$ya500 = 0;
$grandt = 0;

$groupid = "";
if(isset($_POST['group_id']) && $_POST['group_id'] != '') {
    $groupid = " and logid=" . intval($_POST['group_id']);	
}

if(isset($_POST['tbn_search']) && isset($_POST['start_date'])) {
    $from = function_exists('dateto_mysql') ? dateto_mysql($_POST['start_date'])." 23:59:59" : date('Y-m-d')." 23:59:59";
} else {
    $from = date('Y-m-d')." 23:59:59";
}

$result = mysqli_query($conn,"SELECT * FROM provider_group where logid>0 $groupid");

if ($result) {
    while($row = mysqli_fetch_assoc($result)) { 
        $mycount=$mycount+1; 
        $gl_id = mysqli_real_escape_string($conn, $row['logid']);
        ?> 				  
        <tr class="group-header-row">
            <td style="white-space:nowrap"><strong><?php echo htmlspecialchars($row['name'] ?? ''); ?></strong></td>
            <td colspan="2" style="white-space:nowrap">
            <?php 
            $querya = "SELECT `name` FROM `member` WHERE group_id='$gl_id' AND isleader=1";
            $querya_res = @mysqli_query($conn, $querya);
            if($querya_res) {
                while($srow = @mysqli_fetch_assoc($querya_res)) {  
                    echo htmlspecialchars($srow['name'] ?? '') . ',';  
                }
            }
            ?>
            </td>
        </tr>

        <tr>
        <td colspan="3" class="nested-table-container"> 
          <table width="100%" border="0" class="table table-sm inner-member-table">
            <thead>
                <tr class="inner-hdr">
                  <td><strong>Member No</strong></td>
                  <td><strong>Name</strong></td>
                  <td><strong>Phone</strong></td>
                  <td width="10%" align="right"><strong>Contributed</strong></td>
                  <td width="10%" align="right"><strong>Due</strong></td>
                </tr>
            </thead>
            <tbody>
            <?php
            $Mquery = @mysqli_query($conn,"SELECT logid,`name`,mnumber,phone_no,activation_dt FROM `member` WHERE group_id='$gl_id'");
            if($Mquery) {
                while($mrow = @mysqli_fetch_assoc($Mquery)) {    
                    $date1 = date('Y-m', strtotime($from));
                    $date2 = date('Y-m', strtotime($from));
                    $mymnths = 0;
                    $contributed = 0;
                    
                    for($i = $date1; $i <= $date2; $i++) {
                        $mymnths += 500;
                        $contributed += mycontribution($i, $mrow['logid']);
                    }

                    if(($mymnths - $contributed) >= 500) { 
                        ?>
                        <tr style="background-color:#FFF">
                          <td>&nbsp;<?php echo htmlspecialchars($mrow['mnumber'] ?? ''); ?></td>
                          <td>&nbsp;<?php echo htmlspecialchars($mrow['name'] ?? ''); ?></td>
                          <td>&nbsp;<?php echo htmlspecialchars($mrow['phone_no'] ?? ''); ?></td>
                          <td align="right"><?php echo number_format($contributed, 2); ?></td>
                          <td align="right"><?php echo number_format(500, 2); $ya500 += 500; ?></td>
                        </tr>
                    <?php 
                    } 
                }
            } 
            ?>
            
            <tr class="sub-total-row">
                <td colspan="3" align="right"><strong>Sub-Total</strong></td>
                <td align="right">&nbsp;</td>
                <td align="right"><strong>
                  <?php 
                    echo number_format($ya500, 2);
                    $grandt += $ya500;
                    $ya500 = 0;
                  ?>
                </strong></td>
            </tr>
           </tbody>
          </table>
        </td>
        </tr>
    <?php 
    } 
}
?>
<tr class="grand-total-row">
  <td style="color:transparent; width:1px;">z</td>
  <td align="right"><strong>Grand Total Due</strong></td>
  <td width="10%" align="right"><strong><?php echo number_format($grandt, 2); ?></strong></td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
</form>

<script>
$(document).ready(function() {
	$('#start_date').daterangepicker({
        locale: { format: 'DD-MM-YYYY' },
	    singleDatePicker: true
    });
	$('#end_date').daterangepicker({
        locale: { format: 'DD-MM-YYYY' },
	    singleDatePicker: true
    });
});

$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#example').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
        });
    }
});

function closeloan(id) {
    const params = {
        loanid: id,
        repaid: document.getElementById('repay_repaid'+id).value,
        repaiddate: document.getElementById('repay_date'+id).value,
        repaidby: "<?php echo isset($_SESSION['userfullnames']) ? htmlspecialchars($_SESSION['userfullnames']) : 'System'; ?>"	 
    }

    const http = new XMLHttpRequest()
    http.open('POST', 'activeloan_update.php')
    http.setRequestHeader('Content-type', 'application/json')
    http.send(JSON.stringify(params))
    http.onload = function() {
        alert(http.responseText);
        window.location = 'index.php?/activeloan';
    }
}
</script>

<!-- Embedded Custom Responsive Stylesheets -->
<style>
.x_title_flex {
    border-bottom: 2px solid #E6ECEF;
    padding-bottom: 10px;
    margin-bottom: 15px;
}
.x_title_flex h2 {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}
.filter-flex-container {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
    background: #f9f9f9;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #eef1F2;
}
.filter-item {
    flex: 1;
    min-width: 160px;
}
.filter-item.long-item {
    flex: 1.5;
    min-width: 200px;
}
.filter-item.action-item {
    flex: 0.8;
    min-width: 120px;
}
.filter-item label {
    font-size: 12px;
    font-weight: bold;
    color: #555;
    margin-bottom: 4px;
    display: block;
}
.filter-item input, .filter-item select {
    height: 34px !important;
    border-radius: 4px !important;
}
.group-header-row {
    background-color: #ededed !important;
}
.nested-table-container {
    padding: 10px !important;
    background: #ffffff;
}
.inner-member-table {
    margin-bottom: 0px !important;
    border: 1px solid #ddd;
}
.inner-hdr {
    background: #f5f7fa;
}
.sub-total-row {
    background: #fafbfc;
}
.grand-total-row {
    background-color: #2a3f54 !important;
    color: #ffffff !important;
    font-size: 14px;
}
.btn-group .btn {
    margin-bottom: 0;
    margin-right: 0;
    font-size: 12px;
}
.page-filter-form {
    min-width: 70%;
    width: 100%;
}
@media (max-width: 768px) {
    .filter-flex-container {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-item {
        width: 100%;
    }
}
</style>
<input name="txtcount" type="hidden" value="<?php echo $mycount; ?>">
<?php } ?>