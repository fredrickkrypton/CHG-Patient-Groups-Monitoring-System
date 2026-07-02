<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-11-30 10:59:54
 
 
//include('header.php');
$page="sms";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['sms_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="sms";

 if(isset($_POST['sms_save']))
{
$date=dateto_mysql($_POST['date']);
$smstype=ucwords(($_POST['smstype']));
$phone=ucwords(($_POST['phone']));
$sms=ucwords(($_POST['sms']));
$sent=ucwords(($_POST['sent']));
$timestamp=$timestamp;

$array = explode(',', $phone); //split string into array seperated by ', '
foreach($array as $value) //loop over values
{

if(trim($phone)!='') { 
 $phone= "+254".trim(substr(str_replace(' ', '',$value), -9));
 $sql = "INSERT INTO sms (date,smstype,phone,sms,sent,timestamp)
VALUES ('$date','$smstype','$value','$sms','$sent','$timestamp')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'sms','','inserting',$timestamp,$username); }
 else { $error=("Error: you are duplicating data!"); }
}   else {  $error="Fill all mandatory fields in red.";  }  
 
 }
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
 if(isset($_POST['sms_update']))
{
$date=dateto_mysql($_POST['date']);
$smstype=ucwords(($_POST['smstype']));
$phone=ucwords(($_POST['phone']));
$sms=ucwords(($_POST['sms']));
$sent=ucwords(($_POST['sent']));
$timestamp=$timestamp;
$logid=$_POST['logid'];

if(trim($date)!='') { 
$phone= "+254".trim(substr(str_replace(' ', '',$phone), -9));
$sql = "UPDATE  sms SET  date='$date',smstype='$smstype',phone='$phone',sms='$sms',sent='$sent',timestamp='$timestamp' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'sms',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['sms_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'sms',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM sms  WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='Sms Sent'); ?>

<form method='POST' action='' class='rounded' name='sms' style='min-width:70%'  enctype='multipart/form-data'>


									
<? if($part=='1')
{ ?>

<div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                    <h2><?=ucwords($title);?><small>new entry</small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content  center-block'>
					
 
<!--fields-->
<div class="item form-group"><label for="date" class="col-sm-3 manda">Date : </label>
<div class="col-sm-8">
<input type="text" class="form-control form-control-sm" value="<? echo date('d/m/Y')?>" name="date" id="date" onClick="return xCal('date','/',4)"  >
</div>
</div>

<div class="item form-group"><label for="phoneg" class="col-sm-3 manda">Group : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="group" name="group"  onchange="submit()">
<? if($_POST['group'])
{ $qresult = mysqli_query($conn,$gh="SELECT group_id,provider_group.name, GROUP_CONCAT(phone_no) phone_no
FROM member,provider_group WHERE member.group_id=provider_group.logid and member.phone_no in ('".$_POST['group']."')
GROUP BY provider_group.logid ");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $gphone_no=$drow['phone_no']?>"><? echo $drow['name']?> </option><? }
 } ?>
<option value="">Group</option><?
$qresult = mysqli_query($conn,"SELECT group_id,provider_group.name, GROUP_CONCAT(phone_no) phone_no
FROM member,provider_group WHERE member.group_id=provider_group.logid
GROUP BY provider_group.logid");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['phone_no']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
 
</div>
</div>

 <div class="item form-group">
<label for="phone" class="col-sm-3 ">Phone : </label>
<div class="col-sm-8">
<input type="text"  class="form-control form-control-sm " name="phone" id="phone" placeholder="Phone" value="<? if(isset($_POST['group'])) { echo $_POST['group']; } else { echo $_POST['phone']; } ?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

<div class="item form-group"><label for="sms" class="col-sm-3 ">Sms : </label>
<div class="col-sm-8">
<textarea class="form-control form-control-sm " name="sms" id="sms"><? echo $_POST['sms']?></textarea>
</div>
</div>

<div class="item form-group"><label for="sent" class="col-sm-3 ">Sent : </label>
<div class="col-sm-8"> 
<select class="form-control " id="sent" name="sent" >
<? if($_POST['sent'])
{ ?>
<option value="<? echo $_POST['sent']?>"><? echo $_POST['sent']?></option>	
	
<? } ?>

 
<option value="0">Pending</option> 
</select>
</div>
</div>


<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Save data" type="submit" name="sms_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

&nbsp; &nbsp;

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" data-placement="bottom" title="Add New Record">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

&nbsp; &nbsp;

<a href="?/<?=$page?>" data-placement="bottom" title="Go to records">
<button type="button" name="exit" style="padding: 0.4em;" class="btn btn-success btn-sm">
<span>View</span>  <i class="fa fa-search custom"></i></button></a>

&nbsp; &nbsp;

<a href="?/<?=$page?>" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-success btn-sm btn-info"><i class="fa fa-reply" style="float: none;"></i> &nbsp;
<span>Back</span> </button></a>
</div>
</div>
</div>
</div>
</div>


<? } 
 elseif($part=='2') { ?><div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                    <h2><?=ucwords($title);?><small>update entry</small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content center-block'>

<!--fields-->
<? $result = mysqli_query($conn,"SELECT * FROM sms WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group"><label for="date" class="col-sm-3 manda">Date : </label>
<div class="col-sm-8">
<input type="text" class="form-control form-control-sm" value="<? echo dateto_normal($row['date'])?>" name="date" id="date" onClick="return xCal('date','/',4)">
</div>
</div>
 
<div class="item form-group"><label for="phone" class="col-sm-3 ">Phone : </label>
<div class="col-sm-8"> 
<input type="text"  class="form-control form-control-sm " name="phone" id="phone" placeholder="Phone" value="<? echo $row['phone']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="sms" class="col-sm-3 ">Sms : </label>
<div class="col-sm-8">
<textarea class="form-control form-control-sm "   name="sms" id="sms"><? echo $row['sms']?></textarea>
</div>
</div>
<div class="item form-group"><label for="sent" class="col-sm-3 ">Sent : </label>
<div class="col-sm-8">
<select class="form-control " id="sent" name="sent" >
<option value="<? echo $row['sent']?>"><? echo $row['sent']?></option>
<option value="">Select Sent</option>
<option value="0">0</option>
<option value="1">1</option>

</select>
</div>
</div><br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 
 <button data-placement="bottom" title="Update data" type="submit" name="sms_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

&nbsp; &nbsp; 

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" data-placement="bottom" title="Add New Record">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

&nbsp; &nbsp; 

<a href="?/<?=$page?>" data-placement="bottom" title="Go to records">
<button type="button" name="exit" style="padding: 0.4em;" class="btn btn-success btn-sm">
<span>View</span>  <i class="fa fa-search custom"></i></button></a>

 &nbsp; &nbsp;

<a href="?/<?=$page?>" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-success btn-sm"><i class="fa fa-reply" style="float: none;"></i> &nbsp;
<span>Back</span>  </button></a>
 

</div>
</div>
</div> 
<input name="logid" type="hidden" value="<?=$row['logid']?>" />
 <? } ?> 


</div>
</div>
<?  } else { ?>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
                  <div class="x_title">
                  <script src="vendors/jquery/dist/jquery.min.js"></script>
                    <h2><?=ucwords($title);?><small> existing records</small></h2>
					<ul class="nav navbar-right panel_toolbox">
                     <li><a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" class="link"><i class="fa fa-plus"></i> Create</a>
                     </ul>
					<div class="clearfix"></div>
                  </div>
<div class="x_content">

 											 
												 
<table id="datatable" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
<thead>
<tr>
<th width="1px">#</th>
<th>Date</th>
<th>Phone</th>
<th>Message</th>
<th>Status</th>
<th>Timestamp</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>#</th>
<th>Date</th>
<th>Phone</th>
<th>Message</th>
<th>Status</th>
<th>Timestamp</th>
<th></th>
<th>
<button   name="sms_delete" value="Delete" type="submit" class="button sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = mysqli_query($conn,"SELECT * FROM sms where date like '%$search%' OR smstype like '%$search%' OR phone like '%$search%' OR sms like '%$search%' OR sent like '%$search%' OR timestamp like '%$search%'");
 }
else {
$result = mysqli_query($conn,"SELECT * FROM sms order by date desc limit 100");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?=$mycount?></td>
<td><? echo dateto_display($row['date'])?></td>
<td><? echo $row['phone']?></td>
<td><? echo $row['sms']?></td>
<td><? if($row['sent']==1) { echo 'sent'; } else { echo 'pending'; }?></td>
<td><? echo $row['timestamp']?></td>

<td  align="center"><? $edit="part=2&rec=".$row['logid']; ?>
<a href="?/<?=$page?>&D=<? echo osteens($edit)?>" data-placement="bottom" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a></td>
<td   align="center">
<input name="del[]" id="del[]" type="checkbox" value="<?=$row['logid']?>" />
</td>
</tr>
<? } ?>

</tbody>
</table>
<input type="hidden" name="txtcount" id="txtcount" value="<? echo $mycount;?>"> 
</div>
 
 
</div>
</div>
  <? } ?>

 
</form>
<? } ?>
<script>
 $(document).ready(function() {
    $('#datatable').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    } );
} );
</script>
<?php //include("footer.php"); ?>
					 