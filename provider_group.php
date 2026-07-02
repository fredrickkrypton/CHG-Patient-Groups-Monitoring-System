<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-21 20:20:24
 
 
//include('header.php');
$page="provider_group";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['provider_group_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="provider_group";

 if(isset($_POST['provider_group_save']))
{
$name=ucwords(($_POST['name']));
$paybill_no=ucwords(($_POST['paybill_no']));
$provider_id=ucwords(($_POST['provider_id']));
$date_registered=dateto_mysql($_POST['date_registered']);
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));

if(trim($name)!=''&&trim($paybill_no)!=''&&trim($provider_id)!=''&&trim($date_registered)!=''&&trim($created_by)!=''&&trim($created_on)!='') { 
$sql = "INSERT INTO provider_group (name,paybill_no,provider_id,date_registered,created_by,created_on)
VALUES ('$name','$paybill_no','$provider_id','$date_registered','$created_by','$created_on')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'provider_group','','inserting',$timestamp,$username); }
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
 if(isset($_POST['provider_group_update']))
{
$name=ucwords(($_POST['name']));
$paybill_no=ucwords(($_POST['paybill_no']));
$provider_id=ucwords(($_POST['provider_id']));
$date_registered=dateto_mysql($_POST['date_registered']);
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));
$logid=$_POST['logid'];

if(trim($name)!=''&&trim($paybill_no)!=''&&trim($provider_id)!=''&&trim($date_registered)!=''&&trim($created_by)!=''&&trim($created_on)!='') { 
$sql = "UPDATE  provider_group SET  name='$name',paybill_no='$paybill_no',provider_id='$provider_id',date_registered='$date_registered',created_by='$created_by',created_on='$created_on' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'provider_group',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['provider_group_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'provider_group',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM provider_group  WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='Provider Group'); ?>

<form method='POST' action='' class='rounded' name='provider_group' style='min-width:70%'  enctype='multipart/form-data'>


									
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

 <div class="item form-group">
<label for="paybill_no" class="col-sm-3 manda">Paybill no : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="paybill_no" id="paybill_no" placeholder="Paybill no" value="<? echo $_POST['paybill_no']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

<div class="item form-group"><label for="provider_id" class="col-sm-3 manda">Provider id : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="provider_id" name="provider_id"  required='required'>
<? 
if($_GET['p'])
{
$qresult = mysqli_query($conn,"SELECT logid,name FROM provider where logid='".$_GET['p']."' ORDER BY name");
} 
else {
	if($_POST['provider_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM provider where logid='".$_POST['provider_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 }  ?>
<option value="">Select Provider id</option><?
}
if($_GET['p'])
{
$qresult = mysqli_query($conn,"SELECT logid,name FROM provider where logid='".$_GET['p']."' ORDER BY name");
} else {
$qresult = mysqli_query($conn,"SELECT logid,name FROM provider ORDER BY name");
}
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div>

<div class="item form-group"><label for="date_registered" class="col-sm-3 manda">Date registered : </label>
<div class="col-sm-8">
<input type="text" class="form-control form-control-sm" value="<? echo date('d/m/Y')?>" name="date_registered" id="date_registered" onClick="return xCal('date_registered','/',4)"  >
</div>
</div>



<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Save data" type="submit" name="provider_group_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

&nbsp; &nbsp;

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>&p=<?=$_GET['p']?>" data-placement="bottom" title="Add New Record">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

 
&nbsp; &nbsp;

  

<a href="?/provider" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-success btn-sm btn-info"><i class="fa fa-reply" style="float: none;"></i> &nbsp;
<span>Provider</span> </button></a>
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
<? $result = mysqli_query($conn,"SELECT * FROM provider_group WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group"><label for="name" class="col-sm-3 manda">Name : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="name" id="name" placeholder="Name" value="<? echo $row['name']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="paybill_no" class="col-sm-3 manda">Paybill no : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="paybill_no" id="paybill_no" placeholder="Paybill no" value="<? echo $row['paybill_no']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="provider_id" class="col-sm-3 manda">Provider id : </label>
<div class="col-sm-8">
<select class="form-control manda" id="provider_id" name="provider_id"  required='required'><?
$qresult = mysqli_query($conn,"SELECT logid,name FROM provider WHERE logid='".$row['provider_id']."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
<option value="">Select Provider id</option>
<?
/*$qresult = mysqli_query($conn,"SELECT logid,name FROM provider ORDER BY name");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }*/ ?>
</select>
</div>
</div><div class="item form-group"><label for="date_registered" class="col-sm-3 manda">Date registered : </label>
<div class="col-sm-8">
<input type="text" class="form-control form-control-sm" value="<? echo dateto_normal($row['date_registered'])?>" name="date_registered" id="date_registered" onClick="return xCal('date_registered','/',4)">
</div>
</div>
 
<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 
 <button data-placement="bottom" title="Update data" type="submit" name="provider_group_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

&nbsp; &nbsp; 

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>&p=<?=$_GET['p']?>" data-placement="bottom" title="Add New Record">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

 
 &nbsp; &nbsp;

 

<a href="?/provider" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-success btn-sm btn-info"><i class="fa fa-reply" style="float: none;"></i> &nbsp;
<span>Provider</span> </button></a>

</div>
</div>
</div> 
<input name="logid" type="hidden" value="<?=$row['logid']?>" />
 <? } ?> 


</div>
</div>
<?  }   { ?>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
                  <div class="x_title">
                    <h2><?=ucwords($title);?><small> existing records</small></h2>
					<ul class="nav navbar-right panel_toolbox">
                     <li><a href="?/<?=$page?>&D=<? echo osteens('part=1')?>&p=<?=$_GET['p']?>" class="link"><i class="fa fa-plus"></i> Create</a>
                     </ul>
					<div class="clearfix"></div>
                  </div>
<div class="x_content">

 											 
												 
<table id="datatable" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
<thead>
<tr>
<th width="1px">#</th>
<th>Name</th>
<th>Paybill No</th>
<th>Provider Id</th>
<th>Date Registered</th>
<th>Created By</th>
<th>Created On</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>#</th>
<th>Name</th>
<th>Paybill No</th>
<th>Provider Id</th>
<th>Date Registered</th>
<th>Created By</th>
<th>Created On</th>
<th></th>
<th>
<button   name="provider_group_delete" value="Delete" type="submit" class="button sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;
 
$result = mysqli_query($conn,"SELECT * FROM provider_group where provider_id='".$_GET['p']."'");
   while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?=$mycount?></td>
<td><? echo $row['name']?></td>
<td><? echo $row['paybill_no']?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  name FROM provider WHERE logid='".$row['provider_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['name'];  } ?></td>
<td><? echo dateto_display($row['date_registered'])?></td>
<td><? echo $row['created_by']?></td>
<td><? echo $row['created_on']?></td>
<td  align="center"><? $edit="part=2&rec=".$row['logid']; ?>
  <a href="?/<?=$page?>&D=<? echo osteens($edit)?>&p=<?=$_GET['p']?>" data-placement="bottom" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a></td>
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
					 