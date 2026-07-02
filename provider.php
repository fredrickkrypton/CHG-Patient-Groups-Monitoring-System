<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-21 20:00:53
 
 
//include('header.php');
$page="provider";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['provider_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="provider";

 if(isset($_POST['provider_save']))
{
$name=ucwords(($_POST['name']));
$subcounty_id=ucwords(($_POST['subcounty_id']));
$contact_name=ucwords(($_POST['contact_name']));
$contact_phone=ucwords(($_POST['contact_phone']));
$registration_date=dateto_mysql($_POST['registration_date']);
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));

if(trim($name)!=''&&trim($subcounty_id)!=''&&trim($contact_name)!=''&&trim($contact_phone)!=''&&trim($registration_date)!='') { 
$sql = "INSERT INTO provider (name,subcounty_id,contact_name,contact_phone,registration_date,created_by,created_on)
VALUES ('$name','$subcounty_id','$contact_name','$contact_phone','$registration_date','$created_by','$created_on')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'provider','','inserting',$timestamp,$username); }
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
 if(isset($_POST['provider_update']))
{
$name=ucwords(($_POST['name']));
$subcounty_id=ucwords(($_POST['subcounty_id']));
$contact_name=ucwords(($_POST['contact_name']));
$contact_phone=ucwords(($_POST['contact_phone']));
$registration_date=dateto_mysql($_POST['registration_date']);
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));
$logid=$_POST['logid'];

if(trim($name)!=''&&trim($subcounty_id)!=''&&trim($contact_name)!=''&&trim($contact_phone)!=''&&trim($registration_date)!='') { 
$sql = "UPDATE  provider SET  name='$name',subcounty_id='$subcounty_id',contact_name='$contact_name',contact_phone='$contact_phone',registration_date='$registration_date',created_by='$created_by',created_on='$created_on' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'provider',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['provider_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'provider',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM provider  WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='Provider'); ?>

<form method='POST' action='' class='rounded' name='provider' style='min-width:70%'  enctype='multipart/form-data'>


									
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
<label for="name" class="col-sm-3 manda">Name : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="name" id="name" placeholder="Name" value="<? echo $_POST['name']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

<div class="item form-group"><label for="subcounty_id" class="col-sm-3 manda">Subcounty id : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="subcounty_id" name="subcounty_id"  required='required'>
<? if($_POST['subcounty_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty where logid='".$_POST['subcounty_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } ?>
<option value="">Select Subcounty id</option><?
$qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div>

 <div class="item form-group">
<label for="contact_name" class="col-sm-3 manda">Contact name : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="contact_name" id="contact_name" placeholder="Contact name" value="<? echo $_POST['contact_name']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

 <div class="item form-group">
<label for="contact_phone" class="col-sm-3 manda">Contact phone : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="contact_phone" id="contact_phone" placeholder="Contact phone" value="<? echo $_POST['contact_phone']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

<div class="item form-group"><label for="registration_date" class="col-sm-3 manda">Registration date : </label>
<div class="col-sm-8">
<input type="text" class="form-control form-control-sm" value="<? echo date('d/m/Y')?>" name="registration_date" id="registration_date" onClick="return xCal('registration_date','/',4)"  >
</div>
</div>



<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Save data" type="submit" name="provider_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

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
<? $result = mysqli_query($conn,"SELECT * FROM provider WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group"><label for="name" class="col-sm-3 manda">Name : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="name" id="name" placeholder="Name" value="<? echo $row['name']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="subcounty_id" class="col-sm-3 manda">Subcounty id : </label>
<div class="col-sm-8">
<select class="form-control manda" id="subcounty_id" name="subcounty_id"  required='required'><?
$qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty WHERE logid='".$row['subcounty_id']."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
<option value="">Select Subcounty id</option>
<?
$qresult = mysqli_query($conn,"SELECT logid,name FROM subcounty ORDER BY name");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
</select>
</div>
</div><div class="item form-group"><label for="contact_name" class="col-sm-3 manda">Contact name : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="contact_name" id="contact_name" placeholder="Contact name" value="<? echo $row['contact_name']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="contact_phone" class="col-sm-3 manda">Contact phone : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="contact_phone" id="contact_phone" placeholder="Contact phone" value="<? echo $row['contact_phone']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="registration_date" class="col-sm-3 manda">Registration date : </label>
<div class="col-sm-8">
<input type="text" class="form-control form-control-sm" value="<? echo dateto_normal($row['registration_date'])?>" name="registration_date" id="registration_date" onClick="return xCal('registration_date','/',4)">
</div>
</div>
 
<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 
 <button data-placement="bottom" title="Update data" type="submit" name="provider_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

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
<th>Name</th>
<th>Subcounty Id</th>
<th>Contact Name</th>
<th>Contact Phone</th>
<th>Registration Date</th>
<th>Created By</th>
<th>Created On</th>
<th>Group</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>#</th>
<th>Name</th>
<th>Subcounty Id</th>
<th>Contact Name</th>
<th>Contact Phone</th>
<th>Registration Date</th>
<th>Created By</th>
<th>Created On</th>
<th>Group</th>
<th></th>
<th>
<button   name="provider_delete" value="Delete" type="submit" class="button sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = mysqli_query($conn,"SELECT * FROM provider where name like '%$search%' OR subcounty_id like '%$search%' OR contact_name like '%$search%' OR contact_phone like '%$search%' OR registration_date like '%$search%' OR created_by like '%$search%' OR created_on like '%$search%'");
 }
else {
$result = mysqli_query($conn,"SELECT * FROM provider");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?=$mycount?></td>
<td><? echo $row['name']?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  name FROM subcounty WHERE logid='".$row['subcounty_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['name'];  } ?></td>
<td><? echo $row['contact_name']?></td>
<td><? echo $row['contact_phone']?></td>
<td><? echo dateto_display($row['registration_date'])?></td>
<td><? echo $row['created_by']?></td>
<td><? echo $row['created_on']?></td>
<td align="center"><a href="?/provider_group&D=cGFydD0x&p=<?=$row['logid']?>"><i class="fa fa-lg fa-cog"></i></a></td>
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

<?php //include("footer.php"); ?>
					 