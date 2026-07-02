<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-22 18:30:34
 
 
//include('header.php');
$page="stock_transaction";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['stock_transaction_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="stock_transaction";

 if(isset($_POST['stock_transaction_save']))
{
$trans_type=ucwords(($_POST['trans_type']));
$sender_id=ucwords(($_POST['sender_id']));
$recipient_id=ucwords(($_POST['recipient_id']));
$trans_date=dateto_mysql($_POST['trans_date']);
$medicine_id=ucwords(($_POST['medicine_id']));
$prescription_detail_id=ucwords(($_POST['prescription_detail_id']));
$quantity=ucwords(($_POST['quantity']));
$created_by=ucwords(($_POST['created_by']));
$created_on=ucwords(($_POST['created_on']));

if(trim($trans_type)!=''&&trim($trans_date)!=''&&trim($medicine_id)!=''&&trim($quantity)!='') { 
$sql = "INSERT INTO stock_transaction (trans_type,sender_id,recipient_id,trans_date,medicine_id,prescription_detail_id,quantity,created_by,created_on)
VALUES ('$trans_type','$sender_id','$recipient_id','$trans_date','$medicine_id','$prescription_detail_id','$quantity','$username','$timestamp')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'stock_transaction','','inserting',$timestamp,$username); }
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
 if(isset($_POST['stock_transaction_update']))
{
$trans_type=ucwords(($_POST['trans_type']));
$sender_id=ucwords(($_POST['sender_id']));
$recipient_id=ucwords(($_POST['recipient_id']));
$trans_date=dateto_mysql($_POST['trans_date']);
$medicine_id=ucwords(($_POST['medicine_id']));
$prescription_detail_id=ucwords(($_POST['prescription_detail_id']));
$quantity=ucwords(($_POST['quantity']));
$created_by=ucwords(($_POST['created_by']));
$created_on=ucwords(($_POST['created_on']));
$logid=$_POST['logid'];

if(trim($trans_type)!=''&&trim($trans_date)!=''&&trim($medicine_id)!=''&&trim($quantity)!='') { 
$groupid=return_tb($conn,'member','group_id','logid',$recipient_id);
$sql = "UPDATE  stock_transaction SET  trans_type='$trans_type',sender_id='$sender_id',recipient_id='$recipient_id',trans_date='$trans_date',medicine_id='$medicine_id',prescription_detail_id='$prescription_detail_id',quantity='$quantity',groupid='$groupid' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'stock_transaction',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['stock_transaction_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'stock_transaction',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM stock_transaction  WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='Stock Transaction'); ?>

<form method='POST' action='' class='rounded' name='stock_transaction' style='min-width:70%'  enctype='multipart/form-data'>


									
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
<div class="item form-group"><label for="trans_type" class="col-sm-3 manda">Trans type : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="trans_type" name="trans_type"  required='required' onchange="submit()">
<? if($_POST['trans_type'])
{ ?>
<option value="<? echo $_POST['trans_type']?>"><? echo $_POST['trans_type']?></option>	
	
<? } 
if($_SESSION['userrole']==4)
{
	echo '<option value="To_patient">To_patient</option>';
}
else {?>

<option value="">Select Trans type</option>
<option value="To_patient">To_patient</option>
<option value="Adjustment">Adjustment</option>
<option value="Transfer">Transfer</option>
<? } ?>
</select>
</div>
</div>

<div class="item form-group" style="display:none">
<label for="sender_id" class="col-sm-3 manda">Sender : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="sender_id" name="sender_id"  >
<? if($_POST['sender_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,party_name FROM logistics_party where logid='".$_POST['sender_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['party_name']?></option><? }
 } ?>
<option value="">Select Sender id</option><?
$qresult = mysqli_query($conn,"SELECT logid,party_name FROM logistics_party ORDER BY party_name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['party_name']?></option>
<? } ?>
</select>
</div>
</div>

<?
if($_POST['trans_type']=='To_patient'||$_SESSION['userrole']==4)
{?>
<div class="item form-group">
<label for="recipient_id" class="col-sm-3 ">Recipient : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="recipient_id" name="recipient_id"  required='required'>
<? if($_POST['recipient_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM member where logid='".$_POST['recipient_id']."'");
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
	$group_id=" where group_id!=''";
}
$qresult = mysqli_query($conn,$df="SELECT logid,name FROM member $group_id ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
<? $df?>
</div>
</div>
<? } ?>
<div class="item form-group"><label for="trans_date" class="col-sm-3 manda">Trans date : </label>
<div class="col-sm-8">
<input type="text" class="form-control form-control-sm" value="<? echo date('d/m/Y')?>" name="trans_date" id="trans_date" onClick="return xCal('trans_date','/',4)"  >
</div>
</div>

<div class="item form-group">
<label for="medicine_id" class="col-sm-3 manda">Medicine : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="medicine_id" name="medicine_id"  required='required'>
<? if($_POST['medicine_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM medicine where logid='".$_POST['medicine_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } ?>
<option value="">Select Medicine id</option><?
$qresult = mysqli_query($conn,"SELECT logid,name FROM medicine ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div>
<? if($_POST['trans_type']=='To_patient') { ?>
<div class="item form-group"><label for="prescription_detail_id" class="col-sm-3 ">Prescription detail : </label>
<div class="col-sm-8"> 
<select class="form-control " id="prescription_detail_id" name="prescription_detail_id" <? if($_POST['trans_type']=='To_patient') { echo 'required';} ?> >
<? if($_POST['prescription_detail_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,logid FROM prescription_details where logid='".$_POST['prescription_detail_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['logid']?></option><? }
 } ?>
<option value="">Select Prescription detail</option><?
$qresult = mysqli_query($conn,"SELECT logid,logid FROM prescription_details ORDER BY logid");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['logid']?></option>
<? } ?>
</select>
</div>
</div>
<? } ?>

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

 <button data-placement="bottom" title="Save data" type="submit" name="stock_transaction_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

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
<? $result = mysqli_query($conn,"SELECT * FROM stock_transaction WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group"><label for="trans_type" class="col-sm-3 manda">Trans type : </label>
<div class="col-sm-8">
<select class="form-control manda" id="trans_type" name="trans_type"  required='required'>
<option value="<? echo $row['trans_type']?>"><? echo $row['trans_type']?></option>
<option value="">Select Trans type</option>
<option value="To_patient">To_patient</option>
<option value="Adjustment">Adjustment</option>
<option value="Transfer">Transfer</option>

</select>
</div>
</div><div class="item form-group" style="display:none">
<label for="sender_id" class="col-sm-3 manda">Sender : </label>
<div class="col-sm-8">
<select class="form-control manda" id="sender_id" name="sender_id" ><?
$qresult = mysqli_query($conn,"SELECT logid,party_name FROM logistics_party WHERE logid='".$row['sender_id']."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['party_name']?></option><? } ?>
<option value="">Select Sender id</option>
<?
$qresult = mysqli_query($conn,"SELECT logid,party_name FROM logistics_party ORDER BY party_name");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['party_name']?></option><? } ?>
</select>
</div>
</div><div class="item form-group"><label for="recipient_id" class="col-sm-3 ">Recipient : </label>
<div class="col-sm-8">
<select class="form-control manda" id="recipient_id" name="recipient_id"  required='required'>
<? if($_POST['recipient_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM member where logid='".$_POST['recipient_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } else {
$qresult = mysqli_query($conn,"SELECT logid,name FROM member where logid='".$row['recipient_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } 
	 
 }?>
<option value="">Select Member</option><?
if($_SESSION['userrole']==4)
{
	$group_id=" where group_id='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" where group_id!=''";
}
$qresult = mysqli_query($conn,$df="SELECT logid,name FROM member $group_id ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div><div class="item form-group"><label for="trans_date" class="col-sm-3 manda">Trans date : </label>
<div class="col-sm-8">
<input type="text" class="form-control form-control-sm" value="<? echo dateto_normal($row['trans_date'])?>" name="trans_date" id="trans_date" onClick="return xCal('trans_date','/',4)">
</div>
</div>
 
<div class="item form-group"><label for="medicine_id" class="col-sm-3 manda">Medicine id : </label>
<div class="col-sm-8">
<select class="form-control manda" id="medicine_id" name="medicine_id"  required='required'><?
$qresult = mysqli_query($conn,"SELECT logid,name FROM medicine WHERE logid='".$row['medicine_id']."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
<option value="">Select Medicine id</option>
<?
$qresult = mysqli_query($conn,"SELECT logid,name FROM medicine ORDER BY name");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? } ?>
</select>
</div>
</div><div class="item form-group"><label for="prescription_detail_id" class="col-sm-3 ">Prescription detail : </label>
<div class="col-sm-8">
<select class="form-control " id="prescription_detail_id" name="prescription_detail_id" ><?
$qresult = mysqli_query($conn,"SELECT logid,logid FROM prescription_details WHERE logid='".$row['prescription_detail_id']."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['logid']?></option><? } ?>
<option value="">Select Prescription detail</option>
<?
$qresult = mysqli_query($conn,"SELECT logid,logid FROM prescription_details ORDER BY logid");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['logid']?>"><? echo $drow['logid']?></option><? } ?>
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

 
 <button data-placement="bottom" title="Update data" type="submit" name="stock_transaction_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

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
<th>Trans Type</th>
<th>Recipient Id</th>
<th>Trans Date</th>
<th>Medicine Id</th>
<th>Prescription Detail</th>
<th>Quantity</th>
<th>Created By</th>
<th>Created On</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>#</th>
<th>Trans Type</th>
<th>Recipient Id</th>
<th>Trans Date</th>
<th>Medicine Id</th>
<th>Prescription Detail</th>
<th>Quantity</th>
<th>Created By</th>
<th>Created On</th>
<th></th>
<th>
<button   name="stock_transaction_delete" value="Delete" type="submit" class="button sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;

if($_SESSION['userrole']==4)
{
	$group_id=" where groupid='".$_SESSION['groupid']."'  ";
}
else {
	$group_id=" where groupid!=''";
}

 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = mysqli_query($conn,$fgh="SELECT * FROM stock_transaction $group_id  and (trans_type like '%$search%' OR sender_id like '%$search%' OR recipient_id like '%$search%' OR trans_date like '%$search%' OR medicine_id like '%$search%' OR prescription_detail_id like '%$search%' OR quantity like '%$search%' OR created_by like '%$search%' OR created_on like '%$search%')");
 }
else {
$result = mysqli_query($conn,"SELECT * FROM stock_transaction $group_id");
}
  $fgh;
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?=$mycount?></td>
<td><? echo $row['trans_type']?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  name FROM member WHERE logid='".$row['recipient_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['name'];  } ?></td>
<td><? echo dateto_display($row['trans_date'])?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  name FROM medicine WHERE logid='".$row['medicine_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['name'];  } ?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  logid FROM prescription_details WHERE logid='".$row['prescription_detail_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['logid'];  } ?></td>
<td><? echo $row['quantity']?></td>
<td><? echo $row['created_by']?></td>
<td><? echo $row['created_on']?></td>

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
					 