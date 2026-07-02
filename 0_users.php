<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-04-01 10:37:35
 
 
//include('header.php');
$page="0_users";
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
$username=strtolower(($_POST['username']));
$fullnames=ucwords(($_POST['fullnames']));
$password=md5(($_POST['password']));
$email=(($_POST['email']));
$phone=ucwords(($_POST['phone']));
$role=ucwords(($_POST['role']));
$groupid=$_POST['groupid'];
$profpic=date('ymdHis').'_'.basename($_FILES['profpic']['name']);
$filenamex='profpic';

uploadthisfile($filenamex); 

if($errorupload==1) { $profpic=''; } 

$active=ucwords(($_POST['active']));

if(trim($active)!='') { 

$phone= "+254".trim(substr(str_replace(' ', '',$phone), -9));

 $sql = "INSERT INTO 0_users (username,fullnames,password,email,phone,role,profpic,active,groupid)
VALUES ('$username','$fullnames','$password','$email','$phone','$role','$profpic','$active','$groupid')";
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
if($groupid>0)
{
$query = "SELECT * FROM member WHERE phone_no='$phone'";
$results = @mysqli_query($conn, $query);
if (@mysqli_num_rows($results) == 0) {
$mnumber='M'.str_pad(nextsequence($conn,'Member'),4, '0', STR_PAD_LEFT);
$sqls = "insert into  member SET  mnumber='$mnumber',phone_no='$phone',group_id='$groupid',name='$fullnames',isleader='1',active='Yes',created_by='$username',created_on='$timestamp'";
if ($conn->query($sqls) === TRUE) {
	create_db_sms($conn,1,'gleader',$phone,'xx');
	 }
}
	
}							  
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
$usernamex=strtolower(($_POST['usernamex']));
$fullnames=ucwords(($_POST['fullnames']));
$password=(($_POST['password']));
$email=(($_POST['email']));
$phone=ucwords(($_POST['phone']));
$role=ucwords(($_POST['role']));
$profpic=date('ymdHis').'_'.basename($_FILES['profpic']['name']);
  $groupid=$_POST['groupid'];
$filenamex='profpic';
 uploadthisfile($filenamex);

if($errorupload==1) { $profpic=''; } 
if($nofilesattahced==1) { $profpic=$_POST['hiddenfile']; } 

$active=ucwords(($_POST['active']));
$logid=$_POST['logid'];

if($password!='')
{
$sq=",password='".md5($password)."'";	
}

if(trim($active)!='') { 

$phone= "+254".trim(substr(str_replace(' ', '',$phone), -9));

$sql = "UPDATE  0_users SET  fullnames='$fullnames',email='$email',phone='$phone',role='$role',groupid='$groupid',active='$active' $sq WHERE logid='$logid'";
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
							  
if($groupid>0)
{
$query = "SELECT * FROM member WHERE phone_no='$phone'";
$results = @mysqli_query($conn, $query);
if (@mysqli_num_rows($results) == 0) {
$mnumber='M'.str_pad(nextsequence($conn,'Member'),4, '0', STR_PAD_LEFT);
  $sqls = "insert into  member SET  mnumber='$mnumber',phone_no='$phone',group_id='$groupid',name='$fullnames',isleader='1',active='Yes',created_by='$usernamex',created_on='$timestamp'";
if ($conn->query($sqls) === TRUE) {
	create_db_sms($conn,1,'gleader',$phone,'xx');
	 }
}
	
}
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
	  
     $sql = "update  0_users set active='No' WHERE logid='".$_POST['del'][$i]."'";
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
 _title($title='Users'); ?>

<form method='POST' action='' class='rounded' name='0_users' style='min-width:70%'  enctype='multipart/form-data'>


									
<? if($part=='1')
{ ?>

<div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                    <h2><?=ucwords(Users);?><small>new entry</small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content  center-block'>
					
 
<!--fields-->
 <div class="item form-group">
<label for="username" class="col-sm-3 ">Username : </label>
<div class="col-sm-6">
<input type="text"  class="form-control form-control-sm " name="username" id="username" placeholder="Username" value="<? echo $_POST['username']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

 <div class="item form-group">
<label for="fullnames" class="col-sm-3 ">Fullnames : </label>
<div class="col-sm-6">
<input type="text"  class="form-control form-control-sm " name="fullnames" id="fullnames" placeholder="Fullnames" value="<? echo $_POST['fullnames']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>


 <div class="item form-group"><label for="email" class="col-sm-3 ">Email : </label>
<div class="col-sm-6">
<input type="email" class="form-control form-control-sm " name="email" id="email" placeholder="Email" value="<? echo $_POST['email']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

 <div class="item form-group">
<label for="phone" class="col-sm-3 ">Phone : </label>
<div class="col-sm-6">
<input type="text"  class="form-control form-control-sm " name="phone" id="phone" placeholder="Phone" value="<? echo $_POST['phone']?>" autocomplete="off" onblur="js_validate(this,'general')" required="required">

</div>
</div>

<div class="item form-group"><label for="role" class="col-sm-3 ">Role : </label>
<div class="col-sm-6"> 
<select class="form-control " id="role" name="role" >
<? if($_POST['role'])
{ $qresult = mysqli_query($conn,"SELECT roleid,rolename FROM roles where roleid='".$_POST['role']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['roleid']?>"><? echo $drow['rolename']?></option><? }
 } ?>
<option value="">Select Role</option><?
$qresult = mysqli_query($conn,"SELECT roleid,rolename FROM roles ORDER BY rolename");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['roleid']?>"><? echo $drow['rolename']?></option>
<? } ?>
</select>
</div>
</div>

<div class="item form-group"><label for="role" class="col-sm-3 ">Member Group : </label>
<div class="col-sm-6"> 
<select class="form-control " id="groupid" name="groupid" >
<? if($_POST['groupid'])
{ $qresult = mysqli_query($conn,"SELECT * FROM provider_group where logid='".$_POST['groupid']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } ?>
<option value="">Select Group</option><?
$qresult = mysqli_query($conn,"SELECT * FROM provider_group ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div>
<div class="item form-group" style="display:none"><label for="profpic" class="col-sm-3 ">Profpic : </label>
<div class="col-sm-5"> 
<input type="file"  class="custom-file-input " name="profpic" id="profpic">
<label class="custom-file-label" for="customFile">Choose file</label>
</div> 
</div>

<div class="item form-group"><label for="active" class="col-sm-3 manda">Active : </label>
<div class="col-sm-6"> 
<select class="form-control manda" id="active" name="active"  required='required'>
<? if($_POST['active'])
{ ?>
<option value="<? echo $_POST['active']?>"><? echo $_POST['active']?></option>	
	
<? } ?>

<option value="">Select Active</option>
<option value="Yes">Yes</option>
<option value="No">No</option>
</select>
</div>
</div>

<div class="item form-group">
<label for="username" class="col-sm-3 ">Password : </label>
<div class="col-sm-6">
<input type="text"  class="form-control form-control-sm manda" name="password" id="password" placeholder="Password" value="" autocomplete="off" onblur="js_validate(this,'general')" required="required">
</div>
</div>

<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Save data" type="submit" name="0_users_save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span>  <i class="fa fa-save"></i></button>
 

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
<? $result = mysqli_query($conn,"SELECT * FROM 0_users WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group"><label for="username" class="col-sm-3 ">Username : </label>
<div class="col-sm-6"> 
<input type="text"  class="form-control form-control-sm " name="username" id="username" placeholder="Username" value="<? echo $row['username']?>" autocomplete="off"  onblur="js_validate(this,'general')" disabled="disabled">

<input type="hidden"  class="form-control form-control-sm " name="usernamex" id="usernamex" placeholder="Username" value="<? echo $row['username']?>" autocomplete="off"  >
</div>
</div>
<div class="item form-group"><label for="fullnames" class="col-sm-3 ">Fullnames : </label>
<div class="col-sm-6"> 
<input type="text"  class="form-control form-control-sm " name="fullnames" id="fullnames" placeholder="Fullnames" value="<? echo $row['fullnames']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="email" class="col-sm-3 ">Email : </label>
<div class="col-sm-6">
<input type="email" class="form-control form-control-sm " name="email" id="email" placeholder="Email" value="<? echo $row['email']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="phone" class="col-sm-3 ">Phone : </label>
<div class="col-sm-6"> 
<input type="text"  class="form-control form-control-sm " name="phone" id="phone" placeholder="Phone" value="<? echo $row['phone']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group"><label for="role" class="col-sm-3 ">Role : </label>
<div class="col-sm-6">
<select class="form-control " id="role" name="role" ><?
$qresult = mysqli_query($conn,"SELECT roleid,rolename FROM roles WHERE roleid='".$row['role']."'");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['roleid']?>"><? echo $drow['rolename']?></option><? } ?>
<option value="">Select Role</option>
<?
$qresult = mysqli_query($conn,"SELECT roleid,rolename FROM roles ORDER BY rolename");
while($drow = mysqli_fetch_assoc($qresult))
{ ?><option value="<? echo $drow['roleid']?>"><? echo $drow['rolename']?></option><? } ?>
</select>
</div>
</div>

<div class="item form-group"><label for="role" class="col-sm-3 ">Member Group : </label>
<div class="col-sm-6"> 
<select class="form-control " id="groupid" name="groupid" >
<? if($row['groupid'])
{ $qresult = mysqli_query($conn,"SELECT * FROM provider_group where logid='".$row['groupid']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } ?>
<option value="">Select Group</option><?
$qresult = mysqli_query($conn,"SELECT * FROM provider_group ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>
</div>
</div><div class="item form-group" style="display:none"><label for="profpic" class="col-sm-3 ">Profpic : </label>
<div class="col-sm-5">
<input type="file"  class="custom-file-input " name="profpic" id="profpic">
<input type="hidden" value="<? echo $row['profpic']?>" name="hiddenfile">
<label class="custom-file-label" for="customFile">Choose file</label>
</div> 
</div>

<div class="item form-group"><label for="active" class="col-sm-3 manda">Active : </label>
<div class="col-sm-6">
<select class="form-control manda" id="active" name="active"  required='required'>
<option value="<? echo $row['active']?>"><? echo $row['active']?></option>
<option value="">Select Active</option>
<option value="Yes">Yes</option>
<option value="No">No</option>

</select>
</div>
</div>


<div class="item form-group">
<label for="username" class="col-sm-3 ">Password : </label>
<div class="col-sm-6">
<input type="text"  class="form-control form-control-sm " name="password" id="password" placeholder="Password" value="" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

<br />
<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 
 <button data-placement="bottom" title="Update data" type="submit" name="0_users_update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

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
<script src="vendors/jquery/dist/jquery.min.js"></script>
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
<th>Username</th>
<th>Fullnames</th>
<th>Email</th>
<th>Phone</th>
<th>Role</th>
<th>Profpic</th>
<th>Active</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>#</th>
<th>Username</th>
<th>Fullnames</th>
<th>Email</th>
<th>Phone</th>
<th>Role</th>
<th>Profpic</th>
<th>Active</th>
<th></th>
<th>
<button   name="0_users_delete" value="Delete" type="submit" class="button sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>

</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = mysqli_query($conn,"SELECT * FROM 0_users where username like '%$search%' OR fullnames like '%$search%' OR email like '%$search%' OR phone like '%$search%' OR role like '%$search%' OR profpic like '%$search%' OR active like '%$search%'");
 }
else {
$result = mysqli_query($conn,"SELECT * FROM 0_users");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?=$mycount?></td>
<td><? echo $row['username']?></td>
<td><? echo $row['fullnames']?></td>
<td><? echo $row['email']?></td>
<td><? echo $row['phone']?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  rolename FROM roles WHERE roleid='".$row['role']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['rolename'];  } ?></td>
<td>
 <? if($row['profpic']!='')
			  {?>  
              <img src="uploads/<?=$row['profpic']?>" alt="..." height="50px" >
              <? } else {?>
                <img src="images/img.jpg" alt="..."  height="50px"><? } ?>
			 </td>
<td><? echo $row['active']?></td>

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
					 