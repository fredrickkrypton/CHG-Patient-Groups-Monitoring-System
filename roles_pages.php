<?
workout($_GET['D']);
$page="roles_pages";

$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{


 if(isset($_POST['save']))
{
$groups=$_POST['groups'];
$filename=$_POST['filename'];
$username=$username;
$timestamp=$timestamp;

if(trim($groups)!=''&&trim($filename)!='') { 
$sql = "INSERT INTO roles_pages (groups,filename,username,timestamp)
VALUES ('$groups','$filename','$username','$timestamp')";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'roles_pages','','inserting',$timestamp,$username); }
 else { $error=("Error: " . $sql . "<br>" . $conn->error); }
 }   else {  $error=("Fill all mandatory fields in red.");  }  
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
 if(isset($_POST['update']))
{
$groups=$_POST['groups'];
$filename=$_POST['filename'];
$username=$username;
$timestamp=$timestamp;
$logid=$_POST['logid'];

if(trim($groups)!=''&&trim($filename)!='') { 
$sql = "UPDATE  roles_pages SET  groups='$groups',filename='$filename',username='$username',timestamp='$timestamp' WHERE logid='$logid'";
if ($conn->query($sql) === TRUE) {  $alert=1; audittrail($conn,'roles_pages',$logid,'updatings',$timestamp,$username); }
 else { $error=("Error: " . $sql . "<br>" . $conn->error); }
 }   else {  $error=("Fill all mandatory fields in red.");  }  
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
 if(isset($_POST['delete']))
{ 
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['del'][$i]!='')
	  { 
	  audittrail($conn,'roles_pages',$_POST['del'][$i],'deleting',$timestamp,$username);
	  
     $sql = "DELETE FROM roles_pages  WHERE logid='".$_POST['del'][$i]."'";
if ($conn->query($sql) === TRUE) { $deleted=$deleted+1; }
	  }  
   } 
	  if ($deleted>0) { $alert=1; } else { $error=("No record selected"); }
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
                                  text: '$error',
								  nonblock: { nonblock: !0 },
                                  type: 'error',
                                  styling: 'bootstrap3'
                              }); }) </script>";
}	  
}
 //---------------------------------------------------------------------------------------------------- 
 _title($title='System Pages'); ?>

<form method='POST' action='' class='rounded' name='roles_pages' style='min-width:70%'  enctype='multipart/form-data'>
<? if($part=='1')
{ ?><div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                    <h2><?=ucwords($title);?><small>new entry</small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content  center-block'><div class="item form-group">
<label for="groups" class="col-sm-3 col-form-label">Groups : </label>
<div class="col-sm-5">
<input type="text"  required='required' class="form-control form-control-sm manda" name="groups" id="groups" placeholder="Groups" value="<? echo $_POST['groups']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>

<div class="item form-group">
<label for="filename" class="col-sm-3 col-form-label">Filename : </label>
<div class="col-sm-5">
<input type="text"  required='required' class="form-control form-control-sm manda" name="filename" id="filename" placeholder="Filename" value="<? echo $_POST['filename']?>" autocomplete="off" onblur="js_validate(this,'general')">
</div>
</div>




<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">
 <button data-placement="bottom" title="Save data" type="submit" name="save" style="padding: 0.4em;" class="btn btn-success btn-sm">
 <span>Save</span>  <i class="fa fa-save"></i></button>

&nbsp; &nbsp;

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" data-placement="bottom" title="Go to update page">
<button type="button" name="new" class="btn btn-primary btn-sm" ><span>New</span>  <i class="fa fa-file custom"></i></button></a>

&nbsp; &nbsp;

<a href="?/<?=$page?>" data-placement="bottom" title="Go to records">
<button type="button" name="exit" class="btn btn-secondary btn-sm"><span>View</span>  <i class="fa fa-search custom"></i></button></a>

&nbsp; &nbsp;

<a href="?" data-placement="bottom" title="Go to dashboard">
<button type="button" name="exit" class="btn btn-info btn-sm" ><span>Dashboard</span>  <i class="fa fa-angle-double-right custom"></i></button></a>
</div>
</div>
</div>
</div>
</div>
</div>

<? } 
 elseif($part=='2') { ?><div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                    <h2><?=ucwords($title);?><small>update Entry</small></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content center-block'>
<? $result = mysqli_query($conn,"SELECT * FROM roles_pages WHERE  logid='$rec'");
  while($row = mysqli_fetch_assoc($result))
    { ?> 
<div class="item form-group">
<label for="groups" class="col-sm-3 col-form-label">Groups : </label>
<div class="col-sm-5">
<input type="text"  required='required' class="form-control form-control-sm manda" name="groups" id="groups" placeholder="Groups" value="<? echo $row['groups']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>
<div class="item form-group">
<label for="filename" class="col-sm-3 col-form-label">Filename : </label>
<div class="col-sm-5">
<input type="text"  required='required' class="form-control form-control-sm manda" name="filename" id="filename" placeholder="Filename" value="<? echo $row['filename']?>" autocomplete="off"  onblur="js_validate(this,'general')">
</div>
</div>

<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">

 <button data-placement="bottom" title="Update data" type="submit" name="update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span>  <i class="fa fa-save"></i></button>

&nbsp; &nbsp;

<a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" data-placement="bottom" title="Go to update page">
<button type="button" name="new" style="padding: 0.4em;" class="btn btn-primary btn-sm"><span>New</span>  <i class="fa fa-file custom"></i></button></a>

&nbsp; &nbsp;

<a href="?/<?=$page?>" data-placement="bottom" title="Go to records">
<button type="button" name="exit" class="btn btn-secondary btn-sm">
<span>View</span>  <i class="fa fa-search custom"></i></button></a>

&nbsp; &nbsp;

<a href="?" data-placement="bottom" title="Go to dashboard"><button type="button" name="exit" class="btn btn-info btn-sm">
<span>Dashboard</span>  <i class="fa fa-angle-double-right custom"></i></button></a>
</div>
</div>
</div> 
<input name="logid" type="hidden" value="<?=$row['logid']?>" />
 <? } ?> 

</div>
</div>
</div>
<?  } else { ?><div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
                  <div class="x_title">
                    <h2><?=ucwords($title);?><small> existing records</small></h2>
					<ul class="nav navbar-right panel_toolbox">
                     <li><a href="?/<?=$page?>&D=<? echo osteens('part=1')?>" class="collapse-link"><i class="fa fa-plus"></i> Create</a>
                     </ul>
					<div class="clearfix"></div>
                  </div>
<div class="x_content">
<table id="datatable" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
            <thead>
<tr>
<th>Groups</th>
<th>Filename</th>
<th>Username</th>
<th>Timestamp</th>
<th width="1%">Edit</th> <th width="1%">Del</th> </tr>
</thead>
<tfoot>
<tr>
<th>Groups</th>
<th>Filename</th>
<th>Username</th>
<th>Timestamp</th>
<th></th>
<th>
<button   name="delete" value="Delete" type="submit" class="btn sample btn-sm btn-secondary btntim-sample btntim-nbuttun" onClick="javascript: return confirm('Please confirm deletion');" /><i class="fa fa-trash"></i></button>
</th> 
</tr>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = mysqli_query($conn,"SELECT * FROM roles_pages where groups like '%$search%' OR filename like '%$search%' OR username like '%$search%' OR timestamp like '%$search%'");
 }
else {
$result = mysqli_query($conn,"SELECT * FROM roles_pages ");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><? echo $row['groups']?></td>
<td><? echo $row['filename']?></td>
<td><? echo $row['username']?></td>
<td><? echo $row['timestamp']?></td>

<td  align="center"><? $edit="part=2&rec=".$row['logid']; ?>
<a href="?/<?=$page?>&D=<? echo osteens($edit)?>" data-placement="bottom" title="Go to edit"><i class="fa fa-edit"></i></a></td>
<td   align="center">
<input name="del[]" id="del[]" type="checkbox" value="<?=$row['logid']?>" />
</td>
</tr>
<? } ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<input type="hidden" name="txtcount" id="txtcount" value="<? echo $mycount;?>"> 
  <? } ?>
</form>
<? } ?>