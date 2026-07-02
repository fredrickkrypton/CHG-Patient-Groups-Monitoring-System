<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:05:32
 
 
//include('header.php');
$page="audittrail";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['audittrail_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="audittrail";

 if(isset($_POST['audittrail_save']))
{
$tablename=ucwords(($_POST['tablename']));
$id=ucwords(($_POST['id']));
$action=ucwords(($_POST['action']));
$timestamp=$timestamp;
$username=$username;

if(trim($tablename)!='') { 
$sql = "INSERT INTO audittrail (tablename,id,action,timestamp,username)
VALUES ('$tablename','$id','$action','$timestamp','$username')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'audittrail','','inserting',$timestamp,$username); }
 else { $error=("Error: you are duplicating data!"); }
 }   else {  $error="Fill all mandatory fields in red.";  }  
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
                                  text: '$error',
								  nonblock: { nonblock: !0 },
                                  type: 'error',
                                  styling: 'bootstrap3'
                              }); }) </script>";
}
}  
 
 //---------------------------------------------------------------------------------------------------- 
 if(isset($_POST['audittrail_update']))
{
$tablename=ucwords(($_POST['tablename']));
$id=ucwords(($_POST['id']));
$action=ucwords(($_POST['action']));
$timestamp=$timestamp;
$username=$username;
$logid=$_POST['logid'];

if(trim($tablename)!='') { 
$sql = "UPDATE  audittrail SET  tablename='$tablename',id='$id',action='$action',timestamp='$timestamp',username='$username' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'audittrail',$logid,'updatings',$timestamp,$username); }
 else { $error=("Error: you are duplicating data!"); }
 }   else {  $error="Fill all mandatory fields in red.";  }  
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
                                  text: '$error',
								  nonblock: { nonblock: !0 },
                                  type: 'error',
                                  styling: 'bootstrap3'
                              }); }) </script>";
}
}  
 
 //---------------------------------------------------------------------------------------------------- 
 if(isset($_POST['audittrail_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'audittrail',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM audittrail  WHERE logid='".$_POST['del'][$i]."'";
if ($conn->query($sql) === TRUE) { $deleted=$deleted+1; }
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
                                  text: '',
								  nonblock: { nonblock: !0 },
                                  type: 'error',
                                  styling: 'bootstrap3'
                              }); }) </script>";
}	  
}
 //---------------------------------------------------------------------------------------------------- 
 _title($title='Audit Trail'); ?>
 <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>

 <link rel="stylesheet" type="text/css" media="all" href="daterangepicker.css" />

<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>-->

<script src="vendors/jquery/dist/jquery.min.js"></script>
      
    
          
<form method='POST' action='' class='rounded' name='modeoftravel' style='min-width:70%'  enctype='multipart/form-data'>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
  <div class="x_title"><table width="100%" border="0">
  <tr> 
    <td>  <h2><?=ucwords($title);?></h2> </td>
    <td width="15%"> 
 
 <div class="input-group date">
<input type="text" class="form-control form-control-sm manda" value="<? echo $_POST['start_date']?>" name="start_date" id="start_date" > <span class="input-group-addon">
                 <span class="fa fa-calendar">
                   
</div>
</td>
<td width="15%"> 
<div class="input-group date">
<input type="text" class="form-control form-control-sm manda" value="<? echo $_POST['end_date']?>" name="end_date" id="end_date" > <span class="input-group-addon">
                 <span class="fa fa-calendar">
                   
</div>
     
 </td>
  </tr>
</table>

                  
					<div class="clearfix"></div>
  </div>
<div class="x_content" id="datepicker1">
<table id="example" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered  table-sm table-responsive"><thead>
<tr>
<th>Timestamp</th>
    <th>Table name</th>
    <th>Id</th>
    <th>Action</th>
    <th>Username</th>
    </tr>
</thead>
<tfoot>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$from = dateto_mysql($_POST['start_date']);
$to =dateto_mysql($_POST['end_date']);
$search=$_POST['search'];
$result = mysqli_query($conn,$fg="SELECT * from audittrail where  timestamp>='$from' and timestamp<='$to' and (username like '%$search%' OR action like '%$search%' OR tablename like '%$search%') order by timestamp"); $fg;
 }
else {
$result = mysqli_query($conn,"SELECT * FROM audittrail  order by timestamp limit 10 ");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 
    
    <div class="modal fade" id="exampleModal<?=$mycount?>" tabindex="-1" role="dialog" aria-labelledby="bb" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">LOAN : <? echo $row['logid']?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
     <select name="repay_repaid<? echo $row['logid']?>" id="repay_repaid<? echo $row['logid']?>" class="form-control form-control-sm manda">
     <option value="<?=$row['repaid']?>"><?=$row['repaid']?></option>
     <option value="Yes">Yes</option>
     <option value="No">No</option>
     </select>
     <div class="input-group date">
     <input name="repay_date<? echo $row['logid']?>" id="repay_date<? echo $row['logid']?>" type="text" value="<? if($row['repaiddate']) { echo dateto_normal($row['repaiddate']); } else { echo date('d-m-Y'); } ?>" class="form-control form-control-sm manda"/>
     </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="closeloan(<? echo $row['logid']?>)">Save changes</button>
      </div>
    </div>
  </div>
</div>				  
<tr>
<td ><span style="white-space:nowrap"><? echo $row['timestamp']?></span></td>
<td style="white-space:nowrap"> <? echo $row['tablename']?> </td>
<td ><span style="white-space:nowrap"><? echo $row['id']?></span></td>
<td ><span style="white-space:nowrap"><? echo $row['action']?></span></td>
<td><span style="white-space:nowrap"><? echo $row['username']?></span></td>
</tr>

<? } ?>
<tr>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  </tr>


</tbody>
</table>
</div>
 
</form>
 <script>
$(document).ready(function() {
	
	$('#start_date').daterangepicker({
         locale: {
      format: 'DD-MM-YYYY'
    },
	singleDatePicker: true,
		   

        });
		
		
		$('#end_date').daterangepicker({
         locale: {
      format: 'DD-MM-YYYY'
    },
	singleDatePicker: true,
		   

        });
		
		
		
		
		
   
   

} );

$(document).ready(function() {
    $('#example').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    } );
} );

function closeloan(id)
{
 
const params = {
            loanid: id,
			repaid: document.getElementById('repay_repaid'+id).value,
			repaiddate:document.getElementById('repay_date'+id).value,
			repaidby:"<?=$_SESSION['userfullnames']?>"	 
            
        }

        const http = new XMLHttpRequest()
        http.open('POST', 'activeloan_update.php')
        http.setRequestHeader('Content-type', 'application/json')
        http.send(JSON.stringify(params)) // Make sure to stringify
        http.onload = function() {
        
		alert(http.responseText);
   window.location = 'index.php?/activeloan';
        }
 
}
</script>
<style>
.btn-group .btn {
    margin-bottom: 0;
    margin-right: 0;
    font-size: 12px;
}
</style>
<input name="txtcount" type="hidden" value="<? echo $mycount?>">
<? } ?>