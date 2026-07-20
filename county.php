<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-21 19:56:02
 
 
//include('header.php');
$page="District";
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

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['county_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="county";

 if(isset($_POST['county_save']))
{
$name=ucwords(($_POST['name']));
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));

if(trim($name)!=''&&trim($created_by)!=''&&trim($created_on)!='') { 
$sql = "INSERT INTO county (name,created_by,created_on)
VALUES ('$name','$created_by','$created_on')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'District','','inserting',$timestamp,$username); }
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
 if(isset($_POST['county_update']))
{
$name=ucwords(($_POST['name']));
$created_by=ucwords(($username));
$created_on=ucwords(($_POST['created_on']));
$logid=$_POST['logid'];

if(trim($name)!=''&&trim($created_by)!=''&&trim($created_on)!='') { 
$sql = "UPDATE  county SET  name='$name',created_by='$created_by',created_on='$created_on' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'District',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['county_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'county',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM county  WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='County'); ?>

<form method='POST' action='' class='rounded' name='county' style='min-width:70%'  enctype='multipart/form-data'>


									
<? if($part=='1')
{ ?>

<div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title' style="color: #007bff;">
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



<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Save data" type="submit" name="county_save" style="padding: 0.4em;" class="btn btn-primary btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

&nbsp; &nbsp;

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" data-placement="bottom" title="Add New Record">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-primary btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

&nbsp; &nbsp;

<a href="?/<?=$page?>" data-placement="bottom" title="Go to records">
<button type="button" name="exit" style="padding: 0.4em;" class="btn btn-primary btn-sm">
<span>View</span>  <i class="fa fa-search custom"></i></button></a>

&nbsp; &nbsp;

<a href="?/<?=$page?>" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-primary btn-sm btn-info"><i class="fa fa-reply" style="float: none;"></i> &nbsp;
<span>Back</span> </button></a>
</div>
</div>
</div>
</div>
</div>


<? } 
 elseif($part=='2') { ?><div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title' style="color: #007bff;">
                    <h2><?=ucwords($title);?><small>update entry</small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content center-block'>

<!--fields-->
<? $result = mysqli_query($conn,"SELECT * FROM county WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group"><label for="name" class="col-sm-3 manda">Name : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="name" id="name" placeholder="Name" value="<? echo $row['name']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 
 <button data-placement="bottom" title="Update data" type="submit" name="county_update" style="padding: 0.4em;" class="btn btn-primary btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

&nbsp; &nbsp; 

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" data-placement="bottom" title="Add New Record">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-primary btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

&nbsp; &nbsp; 

<a href="?/<?=$page?>" data-placement="bottom" title="Go to records">
<button type="button" name="exit" style="padding: 0.4em;" class="btn btn-primary btn-sm">
<span>View</span>  <i class="fa fa-search custom"></i></button></a>

 &nbsp; &nbsp;

<a href="?/<?=$page?>" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-primary btn-sm"><i class="fa fa-reply" style="float: none;"></i> &nbsp;
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
<div class="x_panel" style="background:#fff; border:1px solid #eee; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.06); padding:20px;">
                  <div class="x_title" style="border:none; margin-bottom:15px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="color:#c98a3e; font-size:14px;">
                      <span style="color:#c98a3e;">&raquo;</span> <?=ucwords($title);?> existing records
                    </div>
					<ul class="nav navbar-right panel_toolbox" style="margin:0;">
                     <li>
                     <a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" class="btn btn-primary" style="background-color:#3aa0e8 !important; border-color:#3aa0e8 !important; color:#fff !important; border-radius:20px; padding:8px 18px; font-weight:600; font-size:13px;"><i class="fa fa-plus"></i>&nbsp; Create New</a>
                     </li>
                     </ul>
					<div class="clearfix"></div>
                  </div>
<div class="x_content">

 											 
												 
<table id="datatable" style="width: 100%; border-collapse: collapse;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
<thead>
<tr>
<th width="1px" style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">#</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Name</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Created By</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Created On</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Sub-county</th>
<th width="1%" style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Edit</th> <th width="1%" style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">#</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Name</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Created By</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Created On</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;">Sub-county</th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9;"></th>
<th style="border:1px solid #e6e6e6; background:#f4f6f9;">
<button   name="county_delete" value="Delete" type="submit" class="button sample btn-sm btn-danger btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = mysqli_query($conn,"SELECT * FROM county where name like '%$search%' OR created_by like '%$search%' OR created_on like '%$search%'");
 }
else {
$result = mysqli_query($conn,"SELECT * FROM county");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td style="border:1px solid #e6e6e6;"><?=$mycount?></td>
<td style="border:1px solid #e6e6e6;"><? echo $row['name']?></td>
<td style="border:1px solid #e6e6e6;"><? echo $row['created_by']?></td>
<td style="border:1px solid #e6e6e6;"><? echo $row['created_on']?></td>
<td style="border:1px solid #e6e6e6;" align="center"><a href="?/subcounty&D=cGFydD0x&p=<?=$row['logid']?>"><i class="fa fa-lg fa-cog"></i></a></td>
<td style="border:1px solid #e6e6e6;"  align="center"><? $edit="part=2&rec=".$row['logid']; ?>
  <a href="?/<?=$page?>&D=<? echo osteens($edit)?>" data-placement="bottom" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a></td>
<td style="border:1px solid #e6e6e6;"   align="center">
<input name="del[]" id="del[]" type="checkbox" value="<?=$row['logid']?>" />
</td>
</tr>
<? } 
if($mycount==0) { ?>
<tr>
<td colspan="7" align="center" style="border:1px solid #e6e6e6; padding:20px; font-style:italic; color:#888;">
No county records found. Click <a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" style="color:#3aa0e8; text-decoration:underline;">Create New</a> to get started.
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