<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-22 16:42:00
 
 
//include('header.php');
$page="prescription";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['prescription_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="prescription";

 if(isset($_POST['prescription_save']))
{
$group_id=ucwords(($_POST['group_id']));
$member_id=ucwords(($_POST['member_id']));
$dispensationdt=dateto_mysql($_POST['dispensationdt']);
$valid_from=dateto_mysql($_POST['valid_from']);
$valid_to=dateto_mysql($_POST['valid_to']);
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));

if(trim($group_id)!=''&&trim($member_id)!=''&&trim($dispensationdt)!=''&&trim($valid_from)!=''&&trim($valid_to)!='') { 
 $reference='Rx'.str_pad(nextsequence($conn,'Rx'),4, '0', STR_PAD_LEFT);
  $sql = "INSERT INTO prescription (reference,group_id,member_id,dispensation_dt,valid_from,valid_to,created_by,created_on)
VALUES ('$reference','$group_id','$member_id','$dispensationdt','$valid_from','$valid_to','$created_by','$created_on')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'prescription','','inserting',$timestamp,$username); }
 else { $error=("Error: you are duplicating data!"); }
 }   else {  $error="Fill all mandatory fields in red.";  }  
if($alert!='')
{
	$qresult = mysqli_query($conn,"SELECT logid  FROM prescription where member_id='$member_id' and created_on='$timestamp'");
  while($drow = mysqli_fetch_assoc($qresult))
    {
		$logid=$drow['logid'];
	}
	
	echo "<script> window.addEventListener('load', function () { 
	new PNotify({
                                  title: 'Success',
                                  text: 'Data saved successfully!',
								  nonblock: { nonblock: !0 },
                                  type: 'success',
                                  styling: 'bootstrap3'
                              }); });
							  
							window.location.assign('index.php?/prescription_details&D=cGFydD0x&p=$logid');
							   </script>";
							  
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
 if(isset($_POST['prescription_update']))
{
$group_id=ucwords(($_POST['group_id']));
$member_id=ucwords(($_POST['member_id']));
$dispensationdt=dateto_mysql($_POST['dispensationdt']);
$valid_from=dateto_mysql($_POST['valid_from']);
$valid_to=dateto_mysql($_POST['valid_to']);
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));
$logid=$_POST['logid'];

if(trim($group_id)!=''&&trim($member_id)!=''&&trim($dispensationdt)!=''&&trim($valid_from)!=''&&trim($valid_to)!='') { 
$sql = "UPDATE  prescription SET  group_id='$group_id',member_id='$member_id',dispensation_dt='$dispensationdt',valid_from='$valid_from',valid_to='$valid_to',created_by='$created_by' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'prescription',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['prescription_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'prescription',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM prescription  WHERE logid='".$_POST['del'][$i]."'";
if ($conn->query($sql) === TRUE) { $deleted=$deleted+1; }

$sql = "DELETE FROM prescription_details  WHERE prescription_id='".$_POST['del'][$i]."'";
if ($conn->query($sql) === TRUE) {  }
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
 _title($title='Prescription'); ?>

<form method='POST' action='' class='rounded' name='prescription' style='min-width:70%'  enctype='multipart/form-data'>


									
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
<div class="item form-group">
<label for="group_id" class="col-sm-3 manda">Group : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="group_id" name="group_id"  required='required' onchange="submit()">
<?  
	if($_POST['group_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group where logid='".$_POST['group_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } else {
	  
	$qresult = @mysqli_query($conn,"SELECT logid,name FROM provider_group WHERE logid='".$_GET['g']."'");
 while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 }?>
<!--<option value="">Select Group </option>--><?
if($_SESSION['userrole']==4)
{
	$group_id=" where logid='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" ";
}
$qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group $group_id ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div>

<div class="item form-group"><label for="member_id" class="col-sm-3 manda">Member : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="member_id" name="member_id"  required='required'>
<? if($_POST['member_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM member where logid='".$_POST['member_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } else {
$qresult = mysqli_query($conn,"SELECT logid,name FROM member where logid='".$_GET['m']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } 
	 
 }?>
<option value="">Select Member</option><?
if($_SESSION['userrole']==4)
{
	$group_id=" where group_id='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" where group_id='".$_POST['group_id']."'";
}
$qresult = mysqli_query($conn,$df="SELECT logid,name FROM member $group_id ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
<? $df?>
</div>
</div>

<div class="item form-group"><label for="dispensationdt" class="col-sm-3 manda">Dispensation dt : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<? echo date('d/m/Y')?>" name="dispensationdt" id="dispensationdt" onClick="return xCal('dispensationdt','/',4)"  >
</div>
</div>

<div class="item form-group"><label for="valid_from" class="col-sm-3 manda">Valid from : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<? echo date('d/m/Y')?>" name="valid_from" id="valid_from" onClick="return xCal('valid_from','/',4)"  >
</div>
</div>

<div class="item form-group"><label for="valid_to" class="col-sm-3 manda">Valid to : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<? echo date('d/m/Y')?>" name="valid_to" id="valid_to" onClick="return xCal('valid_to','/',4)"  >
</div>
</div>



<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Save data" type="submit" name="prescription_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

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
<? $result = mysqli_query($conn,"SELECT * FROM prescription WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
    <div class="item form-group">
<label for="group_id" class="col-sm-3 manda">Reference : </label>
<div class="col-sm-8"><?=$row['reference']?>
</div>
</div>

<div class="item form-group">
<label for="group_id" class="col-sm-3 manda">Group : </label>
<div class="col-sm-8">
<select class="form-control manda" id="group_id" name="group_id"  required='required'><?

	$qresult = @mysqli_query($conn,"SELECT logid,name FROM provider_group WHERE logid='".$row['group_id']."'");
 
while($drow = @mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
<option value="">Select Group id</option>
<?
if($_SESSION['userrole']==4)
{
	$group_id=" where group_id='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" ";
}
$qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group $group_id ORDER BY name");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
</select>
</div>
</div><div class="item form-group"><label for="member_id" class="col-sm-3 manda">Member : </label>
<div class="col-sm-8">
<select class="form-control manda" id="member_id" name="member_id"  required='required'><?
$qresult = mysqli_query($conn,"SELECT logid,name FROM member WHERE logid='".$row['member_id']."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
<option value="">Select Member</option>
<?
if($_SESSION['userrole']==4)
{
	$group_id=" where group_id='".$_SESSION['groupid']."' ";
}
else {
	$group_id="";
}
$qresult = mysqli_query($conn,"SELECT logid,name FROM member $group_id ORDER BY name");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
</select>
</div>
</div><div class="item form-group"><label for="dispensationdt" class="col-sm-3 manda">Dispensation dt : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<? echo dateto_mysql($row['dispensation_dt'])?>" name="dispensationdt" id="dispensationdt" onClick="return xCal('dispensationdt','/',4)">
</div>
</div>
 
<div class="item form-group"><label for="valid_from" class="col-sm-3 manda">Valid from : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<? echo dateto_mysql($row['valid_from'])?>" name="valid_from" id="valid_from" onClick="return xCal('valid_from','/',4)">
</div>
</div>
 
<div class="item form-group"><label for="valid_to" class="col-sm-3 manda">Valid to : </label>
<div class="col-sm-8">
<input type="date" class="form-control form-control-sm" value="<? echo dateto_mysql($row['valid_to'])?>" name="valid_to" id="valid_to" onClick="return xCal('valid_to','/',4)">
</div>
</div>
 
<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 
 <button data-placement="bottom" title="Update data" type="submit" name="prescription_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

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
<th>Reference</th>
<th>Member</th>
<th>Dispensation Dt</th>
<th>Valid From</th>
<th>Valid To</th>
<th>Created By</th>
<th>Created On</th>
<th>Details</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>#</th>
<th>Reference</th>
<th>Member</th>
<th>Dispensation Dt</th>
<th>Valid From</th>
<th>Valid To</th>
<th>Created By</th>
<th>Created On</th>
<th>Details</th>
<th></th>
<th>
<button   name="prescription_delete" value="Delete" type="submit" class="button sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;

if($_SESSION['userrole']==4)
{
	$group_id=" where group_id='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" where group_id!=''";
}
 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = @mysqli_query($conn,$gh="SELECT * FROM prescription $group_id group_id like '%$search%' OR member_id like '%$search%' OR dispensation_dt like '%$search%' OR valid_from like '%$search%' OR valid_to like '%$search%' OR created_by like '%$search%' OR created_on like '%$search%'");
 }
else {
$result = @mysqli_query($conn,$gh="SELECT * FROM prescription $group_id");
}
$gh;
  while($row = @mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?=$mycount?></td>
<td><?=$row['reference'];?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  name FROM member WHERE logid='".$row['member_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['name'];  } ?></td>
<td><? echo dateto_display($row['dispensation_dt'])?></td>
<td><? echo dateto_display($row['valid_from'])?></td>
<td><? echo dateto_display($row['valid_to'])?></td>
<td><? echo $row['created_by']?></td>
<td><? echo $row['created_on']?></td>
<td align="center">
<a href="?/prescription_details&D=cGFydD0x&p=<?=$row['logid']?>" data-placement="bottom" title="Go to edit"><i class="fa fa-lg fa-medkit"></i></a>
</td>

<td align="center"><? $edit="part=2&rec=".$row['logid']; ?>
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

<?php //include("footer.php"); ?>
					 