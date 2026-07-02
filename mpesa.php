<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-22 15:23:28
 
 
//include('header.php');
$page="mpesa";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['mpesa_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="mpesa";

 if(isset($_POST['mpesa_save']))
{
$transactiondate=ucwords(($_POST['transactiondate']));
$phonenumber=ucwords(($_POST['phonenumber']));
$amount=ucwords(($_POST['amount']));
$mpesareceiptnumber=ucwords(($_POST['mpesareceiptnumber']));
$username=$username;
$timestamp=$timestamp;

if(trim($transactiondate)!=''&&trim($phonenumber)!=''&&trim($amount)!=''&&trim($mpesareceiptnumber)!='') { 

$qresult = mysqli_query($conn,$df="SELECT logid,group_id,regfee FROM member where phone_no='$phonenumber'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  
	$groupid=$drow['group_id'];
	$memberid=$drow['logid'];
	$regfee=$drow['regfee'];
	 }
if($regfee==1) { $ctype=1; }  else { $ctype=1; }
	 
$sql = "INSERT INTO mpesa (transactiondate,phonenumber,amount,mpesareceiptnumber,username,timestamp,groupid,memberid,ctype)
VALUES ('$transactiondate','$phonenumber','$amount','$mpesareceiptnumber','$username','$timestamp','$groupid','$memberid','$ctype')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'mpesa','','inserting',$timestamp,$username); }
 else { $error=("Error: you are duplicating data!"); }
 }   else {  $error="Fill all mandatory fields in red.";  }  
if($alert!='')
{
create_db_sms($conn,3,'member',$phonenumber,$amount);
//create_db_sms($db,3,'gleader',$phone,$amount);
 
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
 if(isset($_POST['mpesa_update']))
{
$transactiondate=ucwords(($_POST['transactiondate']));
$phonenumber=ucwords(($_POST['phonenumber']));
$amount=ucwords(($_POST['amount']));
$mpesareceiptnumber=ucwords(($_POST['mpesareceiptnumber']));
$username=$username;
$timestamp=$timestamp;
$logid=$_POST['logid'];

if(trim($transactiondate)!=''&&trim($phonenumber)!=''&&trim($amount)!=''&&trim($mpesareceiptnumber)!='') { 

$qresult = mysqli_query($conn,$df="SELECT logid,group_id,regfee FROM member where phone_no='$phonenumber'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  
	$groupid=$drow['group_id'];
	$memberid=$drow['logid'];
	$regfee=$drow['regfee'];
	 }
if($regfee==1) { $ctype=1; }  else { $ctype=1; }

$sql = "UPDATE  mpesa SET  transactiondate='$transactiondate',phonenumber='$phonenumber',amount='$amount',mpesareceiptnumber='$mpesareceiptnumber',username='$username',timestamp='$timestamp',groupid='$groupid',memberid='$memberid',ctype='$ctype' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'mpesa',$logid,'updatings',$timestamp,$username); }
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
 if(isset($_POST['mpesa_delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'mpesa',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM mpesa  WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='Mpesa'); ?>

<form method='POST' action='' class='rounded' name='mpesa' style='min-width:70%'  enctype='multipart/form-data'>


									
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
<label for="transactiondate" class="col-sm-3 manda">Transactiondate : </label>
<div class="col-sm-8">
<input type="date"  required='required' class="form-control form-control-sm manda" name="transactiondate" id="transactiondate" placeholder="Transactiondate" value="<? echo date('Y-m-d')?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

<div class="item form-group"><label for="phonenumber" class="col-sm-3 manda">Member  : </label>
<div class="col-sm-8"> 
<select class="form-control manda" id="phonenumber" name="phonenumber"  required='required'>
<? if($_POST['phonenumber'])
{ $qresult = mysqli_query($conn,"SELECT logid,name,phone_no FROM member where logid='".$_POST['phonenumber']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['phone_no']?>"><? echo $drow['phone_no']?> <? echo $drow['name']?></option><? }
 } else {
$qresult = mysqli_query($conn,"SELECT logid,name,phone_no FROM member where logid='".$row['phonenumber']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['phone_no']?>"><? echo $drow['phone_no']?> <? echo $drow['name']?></option><? } 
	 
 }?>
<option value="">Select Member</option><?
if($_SESSION['userrole']==4)
{
	$group_id=" where group_id='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" where group_id!=''";
}
$qresult = mysqli_query($conn,$df="SELECT logid,name,phone_no FROM member $group_id ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['phone_no']?>"><? echo $drow['phone_no']?> <? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div>

 <div class="item form-group">
<label for="amount" class="col-sm-3 manda">Amount : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="amount" id="amount" placeholder="Amount" value="<? echo $_POST['amount']?>" autocomplete="off" onblur="js_validate(this,'numeric')">
</div>
</div>

 <div class="item form-group">
<label for="mpesareceiptnumber" class="col-sm-3 manda">Mpesa Reference : </label>
<div class="col-sm-8">
<input type="text"  required='required' class="form-control form-control-sm manda" name="mpesareceiptnumber" id="mpesareceiptnumber" placeholder="Mpesa Reference" value="<? echo $_POST['mpesareceiptnumber']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>



<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Save data" type="submit" name="mpesa_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

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
<? $result = mysqli_query($conn,"SELECT * FROM mpesa WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group"><label for="transactiondate" class="col-sm-3 manda">Transactiondate : </label>
<div class="col-sm-8"> 
<input type="date"  required='required' class="form-control form-control-sm manda" name="transactiondate" id="transactiondate" placeholder="Transactiondate" value="<? echo $row['transactiondate']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="phonenumber" class="col-sm-3 manda">Phone number : </label>
<div class="col-sm-8">
<select class="form-control manda" id="phonenumber" name="phonenumber"  required='required'>
<? if($_POST['phonenumber'])
{ $qresult = mysqli_query($conn,"SELECT logid,name,phone_no FROM member where phone_no='".$_POST['phonenumber']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['phone_no']?>"><? echo $drow['phone_no']?> <? echo $drow['name']?></option><? }
 } else {
$qresult = mysqli_query($conn,"SELECT logid,name,phone_no FROM member where phone_no='".$row['phonenumber']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['phone_no']?>"><? echo $drow['phone_no']?> <? echo $drow['name']?></option><? } 
	 
 }?>
<option value="">Select Member</option><?
if($_SESSION['userrole']==4)
{
	$group_id=" where group_id='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" where group_id!=''";
}
$qresult = mysqli_query($conn,$df="SELECT logid,name,phone_no FROM member $group_id ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['phone_no']?>"><? echo $drow['phone_no']?> <? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div><div class="item form-group"><label for="amount" class="col-sm-3 manda">Amount : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="amount" id="amount" placeholder="Amount" value="<? echo $row['amount']?>" autocomplete="off"  onblur="js_validate(this,'numeric')">
</div>
</div>
<div class="item form-group"><label for="mpesareceiptnumber" class="col-sm-3 manda">Mpesa Reference : </label>
<div class="col-sm-8"> 
<input type="text"  required='required' class="form-control form-control-sm manda" name="mpesareceiptnumber" id="mpesareceiptnumber" placeholder="Mpesa Reference" value="<? echo $row['mpesareceiptnumber']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 
 <button data-placement="bottom" title="Update data" type="submit" name="mpesa_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

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
<th>Transactiondate</th>
<th>Phone Number</th>
<th>Amount</th>
<th>Mpesa Reference</th>
<th>Fee Type</th>
<th>Username</th>
<th>Timestamp</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>#</th>
<th>Transactiondate</th>
<th>Phone Number</th>
<th>Amount</th>
<th>Mpesa Reference</th>
<th>Fee Type</th>
<th>Username</th>
<th>Timestamp</th>
<th></th>
<th>
<button   name="mpesa_delete" value="Delete" type="submit" class="button sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;
if($_SESSION['userrole']==4)
{
	$group_id=" where groupid='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" where phonenumber!=''";
}
 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = mysqli_query($conn,$fg="SELECT * FROM mpesa $group_id and transactiondate like '%$search%' OR phonenumber like '%$search%' OR amount like '%$search%' OR mpesareceiptnumber like '%$search%' OR username like '%$search%' OR timestamp like '%$search%'");
 }
else {
$result = mysqli_query($conn,$fg="SELECT * FROM mpesa $group_id");
}
  $fg;
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?=$mycount?></td>
<td><? echo $row['transactiondate']?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  name FROM member WHERE phone_no='".$row['phonenumber']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['name'];  } echo ' - '.$row['phonenumber']?></td>
<td><? echo $row['amount']?></td>
<td><? echo $row['mpesareceiptnumber']?></td>
<td><?  echo "Contribution";   ?></td>
<td><? echo $row['username']?></td>
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
	
	$('#transactiondate').daterangepicker({
         locale: {
      format: 'DD-MM-YYYY'
    },
	singleDatePicker: true,
		   

        });

} );
</script>
<?php //include("footer.php"); ?>
					 