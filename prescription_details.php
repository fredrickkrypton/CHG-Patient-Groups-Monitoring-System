<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-22 16:57:57
 
 
//include('header.php');
$page="prescription_details";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['prescription_details_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="prescription_details";

 if(isset($_POST['prescription_details_save']))
{
$prescription_id=ucwords(($_POST['prescription_id']));
$molecule_type=ucwords(($_POST['molecule_type']));
$unit_of_measure_id=ucwords(($_POST['unit_of_measure_id']));
$quantity=ucwords(($_POST['quantity']));
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));

if(trim($prescription_id)!=''&&trim($molecule_type)!=''&&trim($unit_of_measure_id)!=''&&trim($quantity)!='') { 
$sql = "INSERT INTO prescription_details (prescription_id,molecule_type,unit_of_measure_id,quantity,created_by,created_on)
VALUES ('$prescription_id','$molecule_type','$unit_of_measure_id','$quantity','$created_by','$created_on')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'prescription_details','','inserting',$timestamp,$username); }
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
 if(isset($_POST['prescription_details_update']))
{
$prescription_id=ucwords(($_POST['prescription_id']));
$molecule_type=ucwords(($_POST['molecule_type']));
$unit_of_measure_id=ucwords(($_POST['unit_of_measure_id']));
$quantity=ucwords(($_POST['quantity']));
$created_by=ucwords(($username));
$created_on=ucwords(($timestamp));
$logid=$_POST['logid'];

if(trim($prescription_id)!=''&&trim($molecule_type)!=''&&trim($unit_of_measure_id)!=''&&trim($quantity)!='') { 
$sql = "UPDATE  prescription_details SET  prescription_id='$prescription_id',molecule_type='$molecule_type',unit_of_measure_id='$unit_of_measure_id',quantity='$quantity',created_by='$created_by',created_on='$created_on' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'prescription_details',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['prescription_details_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'prescription_details',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM prescription_details  WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='Prescription Details'); ?>

<form method='POST' action='' class='rounded' name='prescription_details' style='min-width:70%'  enctype='multipart/form-data'>


									
<? if($part=='1')
{ ?>

<div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                   <? 
					$qresult = @mysqli_query($conn,"SELECT * FROM prescription where logid='".$_GET['p']."'");
  while($drow = @mysqli_fetch_assoc($qresult))
    { ?>
     <h2><?=ucwords($title). " - ".$drow['reference']?><small style="color:blue">
	<?	echo return_tb($conn,'member','mnumber','logid',$drow['member_id'])." : ".return_tb($conn,'member','name','logid',$drow['member_id']);
	}
	 ?></small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content  center-block'>
					
 
<!--fields-->
<div class="item form-group" style="display:none">
<label for="prescription_id" class="col-sm-3 manda">Prescription : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="prescription_id" name="prescription_id"  required='required'>
<?  
{ $qresult = mysqli_query($conn,"SELECT logid,logid FROM prescription where logid='".$_GET['p']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['logid']?></option><? }
 } ?>
 
</select>
</div>
</div>

<div class="item form-group"><label for="molecule_type" class="col-sm-3 manda">Molecule type : </label>
<div class="col-sm-5"> 
<select class="form-control manda" id="molecule_type" name="molecule_type"  required='required' onchange="submit()">
<? if($_POST['molecule_type'])
{ $qresult = mysqli_query($conn,"SELECT logid,name,unit_of_measure_id FROM medicine where logid='".$_POST['molecule_type']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']; $unit_of_measure_id=$drow['unit_of_measure_id'];?></option><? }
 } ?>
<option value="">Select Molecule type</option><?
$qresult = mysqli_query($conn,"SELECT logid,name FROM medicine ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
</div>

 
 <div class="col-sm-3"> 
<select class="form-control manda" id="unit_of_measure_id" name="unit_of_measure_id"  required='required'>
<? $qresult = mysqli_query($conn,"SELECT logid,name FROM unit_of_measure where logid='".$unit_of_measure_id."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
   ?>
 
</select>
</div>
</div>

 <div class="item form-group">
<label for="quantity" class="col-sm-3 manda">Quantity : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="quantity" id="quantity" placeholder="Quantity" value="<? echo $_POST['quantity']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>



<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Save data" type="submit" name="prescription_details_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

&nbsp; &nbsp;

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>&p=<?=$_GET['p']?>" data-placement="bottom" title="Add New Record">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

&nbsp; &nbsp;

<a href="?/<?=$page?>&p=<?=$_GET['p']?>" data-placement="bottom" title="Go to records">
<button type="button" name="exit" style="padding: 0.4em;" class="btn btn-success btn-sm">
<span>View</span>  <i class="fa fa-search custom"></i></button></a>

&nbsp; &nbsp;

<a href="?/prescription" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-success btn-sm btn-info"><i class="fa fa-reply" style="float: none;"></i> &nbsp;
<span>Back  to prescription</span> </button></a>
</div>
</div>
</div>
</div>
</div>


<? } 
 elseif($part=='2') { ?><div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                   <? 
					$qresult = @mysqli_query($conn,"SELECT * FROM prescription where logid='".$_GET['p']."'");
  while($drow = @mysqli_fetch_assoc($qresult))
    { ?>
     <h2><?=ucwords($title). " - ".$drow['reference']?><small style="color:blue">
	<?	echo return_tb($conn,'member','mnumber','logid',$drow['member_id'])." : ".return_tb($conn,'member','name','logid',$drow['member_id']);
	}
	 ?></small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content center-block'>

<!--fields-->
<? $result = mysqli_query($conn,"SELECT * FROM prescription_details WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group" style="display:none">
<label for="prescription_id" class="col-sm-3 manda">Prescription : </label>
<div class="col-sm-8">
<select class="form-control manda" id="prescription_id" name="prescription_id"  required='required'><?
$qresult = mysqli_query($conn,"SELECT logid,logid FROM prescription WHERE logid='".$row['prescription_id']."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['logid']?></option><? } ?>
<option value="">Select Prescription id</option>
<?
$qresult = mysqli_query($conn,"SELECT logid,logid FROM prescription ORDER BY logid");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['logid']?></option><? } ?>
</select>
</div>
</div>
<div class="item form-group"><label for="molecule_type" class="col-sm-3 manda">Molecule type : </label>
<div class="col-sm-5">
<select class="form-control manda" id="molecule_type" name="molecule_type"  required='required' onchange="submit()"><?

if($_POST['molecule_type'])
{
$qresult = mysqli_query($conn,"SELECT logid,name,unit_of_measure_id FROM medicine WHERE logid='".$_POST['molecule_type']."'");
	
} else { 
$qresult = mysqli_query($conn,"SELECT logid,name,unit_of_measure_id FROM medicine WHERE logid='".$row['molecule_type']."'");
}
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']; $unit_of_measure_id=$drow['unit_of_measure_id']; ?></option><? } ?>
<option value="">Select Molecule type</option>
<?
$qresult = mysqli_query($conn,"SELECT logid,name FROM medicine ORDER BY name");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
</select>
</div>
 <div class="col-sm-3">
<select class="form-control manda" id="unit_of_measure_id" name="unit_of_measure_id"  required='required'><?
$qresult = mysqli_query($conn,"SELECT logid,name FROM unit_of_measure WHERE logid='".$unit_of_measure_id."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
 
</select>
</div>
</div><div class="item form-group"><label for="quantity" class="col-sm-3 manda">Quantity : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="quantity" id="quantity" placeholder="Quantity" value="<? echo $row['quantity']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 
 <button data-placement="bottom" title="Update data" type="submit" name="prescription_details_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

&nbsp; &nbsp; 

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>&p=<?=$_GET['p']?>" data-placement="bottom" title="Add New Record">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

&nbsp; &nbsp; 

<a href="?/<?=$page?>&p=<?=$_GET['p']?>" data-placement="bottom" title="Go to records">
<button type="button" name="exit" style="padding: 0.4em;" class="btn btn-success btn-sm">
<span>View</span>  <i class="fa fa-search custom"></i></button></a>

 &nbsp; &nbsp;

<a href="?/prescription" data-placement="bottom" title="Go to dashboard"><button style="padding: 0.4em;" type="button" name="exit" class="btn btn-success btn-sm"><i class="fa fa-reply" style="float: none;"></i> &nbsp;
<span>Back to prescription</span>  </button></a>
 

</div>
</div>
</div> 
<input name="logid" type="hidden" value="<?=$row['logid']?>" />
 <? } ?> 


</div>
</div>
<?  } 
 { ?>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
                  <div class="x_title">
                   <? 
				if($_GET['D']=='')
				{	$qresult = @mysqli_query($conn,"SELECT * FROM prescription where logid='".$_GET['p']."'");
  while($drow = @mysqli_fetch_assoc($qresult))
    { ?>
     <h2><?=ucwords($title). " - ".$drow['reference']?><small style="color:blue">
	<?	echo return_tb($conn,'member','mnumber','logid',$drow['member_id'])." : ".return_tb($conn,'member','name','logid',$drow['member_id']);
	}
	 ?></small></h2>
     <? } ?>
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
<th>Molecule Type</th>
<th>Unit Of Measure Id</th>
<th>Quantity</th>
<th>Created By</th>
<th>Created On</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>#</th>
<th>Molecule Type</th>
<th>Unit Of Measure Id</th>
<th>Quantity</th>
<th>Created By</th>
<th>Created On</th>
<th></th>
<th>
<button   name="prescription_details_delete" value="Delete" type="submit" class="button sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;
  
$result = mysqli_query($conn,"SELECT * FROM prescription_details where prescription_id='".$_GET['p']."'");
   while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?=$mycount?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  name FROM medicine WHERE logid='".$row['molecule_type']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['name'];  } ?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  name FROM unit_of_measure WHERE logid='".$row['unit_of_measure_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['name'];  } ?></td>
<td><? echo $row['quantity']?></td>
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
					 