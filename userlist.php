<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:33:37
 
 
//include('header.php');
$page="userlist";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['0_users_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="0_users";

 if(isset($_POST['0_users_save']))
{
$username=$username;
$fullnames=ucwords(($_POST['fullnames']));
$password=ucwords(($_POST['password']));
$sec=ucwords(($_POST['sec']));
$email=ucwords(($_POST['email']));
$phone=ucwords(($_POST['phone']));
$role=ucwords(($_POST['role']));
$profpic=ucwords(($_POST['profpic']));
$active=ucwords(($_POST['active']));
$lastlogin=ucwords(($_POST['lastlogin']));

if(trim($username)!='') { 
$sql = "INSERT INTO 0_users (username,fullnames,password,sec,email,phone,role,profpic,active,lastlogin)
VALUES ('$username','$fullnames','$password','$sec','$email','$phone','$role','$profpic','$active','$lastlogin')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'0_users','','inserting',$timestamp,$username); }
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
 if(isset($_POST['0_users_update']))
{
$username=$username;
$fullnames=ucwords(($_POST['fullnames']));
$password=ucwords(($_POST['password']));
$sec=ucwords(($_POST['sec']));
$email=ucwords(($_POST['email']));
$phone=ucwords(($_POST['phone']));
$role=ucwords(($_POST['role']));
$profpic=ucwords(($_POST['profpic']));
$active=ucwords(($_POST['active']));
$lastlogin=ucwords(($_POST['lastlogin']));
$logid=$_POST['logid'];

if(trim($username)!='') { 
$sql = "UPDATE  0_users SET  username='$username',fullnames='$fullnames',password='$password',sec='$sec',email='$email',phone='$phone',role='$role',profpic='$profpic',active='$active',lastlogin='$lastlogin' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'0_users',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['0_users_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'0_users',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM 0_users  WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='User List'); ?> <script src="vendors/moment/min/moment.min.js"></script>
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
 
 <div class="input-group datex">
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
<th>Full Names</th>
    <th>User Name</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Active</th>
    <th>Last Login</th>
    </tr>
</thead>
<tfoot>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$from = dateto_mysql($_POST['start_date']);
$to =dateto_mysql($_POST['end_date']). " 24:00:00";
$search=$_POST['search'];
$result = mysqli_query($conn,$fg="SELECT * from 0_users where  lastlogin>='$from' and lastlogin<='$to' and (username like '%$search%' OR fullnames like '%$search%' OR email like '%$search%') order by fullnames");   $fg;
 }
else {
$result = mysqli_query($conn,"SELECT * FROM 0_users  order by fullnames ");
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
<td ><span style="white-space:nowrap"><? echo $row['fullnames']?></span></td>
<td style="white-space:nowrap"> <? echo $row['username']?> </td>
<td ><span style="white-space:nowrap"><? echo $row['email']?></span></td>
<td ><span style="white-space:nowrap"><? echo $row['phone']?></span></td>
<td><span style="white-space:nowrap"><? echo $row['active']?></span></td>
<td><span style="white-space:nowrap"><? echo $row['lastlogin']?></span></td>
</tr>

<? } ?>
<tr>
  <td>&nbsp;</td>
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