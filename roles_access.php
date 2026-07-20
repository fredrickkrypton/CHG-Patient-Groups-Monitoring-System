<?



// SAFE CHECK: Ensure core helper functions (workout, caniacess, get_access, checked, _title, audittrail) are loaded
if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}


$page="roles_access";
$timestamp=date('Y-m-d H:i:s');
 $part=1;
 $rec=1;
if(function_exists('caniacess')) {
    if($part=='') { caniacess($page."_view"); }
    if($part=='1') { caniacess($page."_add"); }
    if($part=='2') { caniacess($page."_edit"); }
    if(isset($_POST['delete'])) { caniacess($page."_delete"); }
}
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
 if(isset($_POST['save']))
{
$role=$_POST['role'];
$sql = "DELETE FROM roles_access  WHERE role='".$role."'";
if ($conn->query($sql) === TRUE) { }

$username=$username;
$timestamp=$timestamp;
//$alert=1; audittrail($conn,'roles_access','','inserting',$timestamp,$username);
$count = $_POST['txtcount'];
for($i=0;$i<$count;$i++){ 
      if($_POST['pages'][$i]!='')
	  { 
$view=$_POST['view'][$i];
$canadd=$_POST['canadd'][$i];
$canedit=$_POST['canedit'][$i];
$candelete=$_POST['candelete'][$i];
$pages=$_POST['pages'][$i];

	if($view!=''||$canadd!=''||$canedit!=''||$candelete!='')
	{  
$sql = "INSERT INTO roles_access (role,pages,view,canadd,canedit,candelete,username,timestamp)
VALUES ('$role','$pages','$view','$canadd','$canedit','$candelete','$username','$timestamp')";
if ($conn->query($sql) === TRUE) { $alert=1;  }
	}
	  }
	  else {
		$error="Select role!";  
		  
	  }
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
if(function_exists('get_access')) { get_access($conn,$_SESSION['userrole']); }
}  
 
 //---------------------------------------------------------------------------------------------------- 
 if(isset($_POST['update']))
{ }  
 
 //---------------------------------------------------------------------------------------------------- 
 if(isset($_POST['delete']))
{ }
 //---------------------------------------------------------------------------------------------------- 
 if(function_exists('_title')) { _title($title='Page access'); } ?>

<form method='POST' action='' class='rounded' name='roles_access' style='min-width:70%'  enctype='multipart/form-data'>
<? if($part=='1')
{ ?><div class='col-md-8 offset-2'>
                <div class='x_panel'>
                  <div class='x_title'>
                    <h2><?=ucwords($title);?></h2>
					<ul class='nav navbar-right panel_toolbox'>
                      <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a>
                      </li>
                     </ul>
					<div class='clearfix'></div>
                  </div>
					<div class='x_content  center-block'><div class="item form-group btnflat">
<label for="role" class="col-sm-3 col-form-label"><strong>Role </strong> </label>
<div class="col-sm-5">
<select class="form-control manda" id="role" name="role"  required='required' onchange="submit()">
<? if($_POST['role'])
{ $qresult = mysqli_query($conn,"SELECT roleid,rolename FROM roles where roleid='".$_POST['role']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $roleid=$drow['roleid']?>"><? echo $drow['rolename']?></option><? }
 } ?>
<option value="">Select Role</option><?
$qresult = mysqli_query($conn,"SELECT roleid,rolename FROM roles ORDER BY rolename");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['roleid']?>"><? echo $drow['rolename']?></option>
<? } ?>
</select>
 
</div>
</div>
<div class="ln_solid"></div>
<style>
.btnflat {
border:0;
background:#E2E2E2;
box-shadow: none;
border-radius:2px;
}
</style>
 
<table  border="0" class="table text-center table-striped table-bordered">
  <tr style="background-color:#666;color:#FFF">
    <td width="1%"><strong>Page</strong></td>
    <td width="1%"><strong>View</strong></td>
    <td width="1%"><strong>Add</strong></td>
    <td width="1%"><strong>Edit</strong></td>
    <td width="1%"><strong>Delete</strong></td>
  </tr>
  <script type="text/javascript">
//<![CDATA[

function CheckAlldel(flag,c) {
	
  var sel =document.querySelectorAll('[id^='+c+']');
  for (var i=0; i<sel.length; i++) {  
    if (sel[i].id == c) { sel[i].checked = flag; }
  }
}
//]]>
</script>
  <? if($_POST['role'])
{ 
$qresult = mysqli_query($conn,"SELECT groups FROM roles_pages group by groups"); 
}
$counts=0;
$countgroup=0;
  while($drw = mysqli_fetch_assoc($qresult))
    { $countgroup=$countgroup+1;?>  <tr>
    <td colspan="5" style="background-color:#CCC" align="left"><strong>
      <?=$drw['groups']?>
    </strong></td>
    </tr>
  
<? $qresultv = mysqli_query($conn,"SELECT * FROM roles_pages where groups='".$drw['groups']."' order by filename");
  while($drow = mysqli_fetch_assoc($qresultv))
    { $counts=$counts+1; ?>  <tr>
    <td align="left"> <?=ucwords(str_replace("_"," ",str_replace("and"," & ",$drow['pagename'])));?>
    <input type="hidden" name="pages[<? echo $counts-1?>]" value="<? echo $drow['logid'];?>" id="$countgroup"/> </td>
    <td>
    <? $access=function_exists('checked') ? checked($conn,$roleid,$drow['logid']) : '';?>
    <input type="checkbox" name="view[<? echo $counts-1?>]"  id="view<? echo $countgroup?>" value="Yes" <? if(explode(",",$access)[0]=="Yes") { echo "checked"; } ?> <? if($drow['sview']=="0") { echo 'hidden'; }?> /></td>
    <td><input type="checkbox" name="canadd[<? echo $counts-1?>]" id="add<? echo $countgroup?>"  value="Yes"  <? if(explode(",",$access)[1]=="Yes") { echo "checked"; } ?> <? if($drow['sadd']=="0") { echo 'hidden'; }?> /></td>
    <td><input type="checkbox" name="canedit[<? echo $counts-1?>]" id="edit<? echo $countgroup?>"  value="Yes"  <? if(explode(",",$access)[2]=="Yes") { echo "checked"; } ?> <? if($drow['sedit']=="0") { echo 'hidden'; }?>/></td>
    <td><input type="checkbox" name="candelete[<? echo $counts-1?>]" id="del<? echo $countgroup?>"  value="Yes"  <? if(explode(",",$access)[3]=="Yes") { echo "checked"; } ?> <? if($drow['sdelete']=="0") { echo 'hidden'; }?> /></td>
  </tr>
  <? } ?>
  <tr>
  <td></td>
  <td>
  <input type="button" class="btnflat" value="✔" onClick="CheckAlldel(true,'view<? echo $countgroup?>')"> 
  <input type="button" class="btnflat" value="✘" onClick="CheckAlldel(false,'view<? echo $countgroup?>')">
  </td>
      <td>
      <input type="button" class="btnflat" value="✔"  onClick="CheckAlldel(true,'add<? echo $countgroup?>')"> 
      <input type="button" class="btnflat" value="✘" onClick="CheckAlldel(false,'add<? echo $countgroup?>')"> 
      </td>
      <td>
      <input type="button" class="btnflat" value="✔" onClick="CheckAlldel(true,'edit<? echo $countgroup?>')"> 
      <input type="button" class="btnflat" value="✘" onClick="CheckAlldel(false,'edit<? echo $countgroup?>')">
      </td>
      <td><input type="button" class="btnflat" value="✔" onClick="CheckAlldel(true,'del<? echo $countgroup?>')"> 
      <input type="button" class="btnflat" value="✘" onClick="CheckAlldel(false,'del<? echo $countgroup?>')">
      </td>
  </tr>
  <? } ?>
</table>

<div class="ln_solid"></div>
<div class="form-group row text-center">
<div class="col-md">
 <button data-placement="bottom" title="Save data" type="submit" name="save" style="padding: 0.4em;" class="btn btn-success btn-sm">
 <span>Save</span>  <i class="fa fa-save"></i></button>


&nbsp; &nbsp;
<input type="hidden" name="txtcount" id="txtcount" value="<? echo $counts;?>">
<a href="?" data-placement="bottom" title="Go to dashboard">
<button type="button" name="exit" class="btn btn-info btn-sm" ><span>Dashboard</span>  <i class="fa fa-angle-double-right custom"></i></button></a>
</div>
</div>
</div>
</div>
</div>
</div>

<? } 
  else { ?><div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
                  <div class="x_title">
                    <h2>Existing<small><?=ucwords($page);?></small></h2>
					<ul class="nav navbar-right panel_toolbox">
                     <li><a href="?/<?=$page?>&D=<? echo function_exists('osteens') ? osteens('part=1') : ''; ?>" class="collapse-link"><i class="fa fa-plus"></i> Manage</a>
                     </ul>
					<div class="clearfix"></div>
                  </div>
<div class="x_content">
<table id="datatable" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
            <thead>
<tr style="background-color:#666;color:#FFF">
<th>Role</th>
<th>Pages</th>
<th>View</th>
<th>Canadd</th>
<th>Canedit</th>
<th>Candelete</th>
</tr>
</thead>
<tfoot>
<tr>
<th>Role</th>
<th>Pages</th>
<th>View</th>
<th>Canadd</th>
<th>Canedit</th>
<th>Candelete</th>
</tr>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$search=$_POST['search'];
$result = mysqli_query($conn,"SELECT * FROM roles_access where role like '%$search%' OR pages like '%$search%' OR view like '%$search%' OR canadd like '%$search%' OR canedit like '%$search%' OR candelete like '%$search%' OR username like '%$search%' OR timestamp like '%$search%'");
 }
else {
$result = mysqli_query($conn,"SELECT * FROM roles_access group by role,pages");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><?
$qresult = mysqli_query($conn,"SELECT  rolename FROM roles WHERE roleid='".$row['role']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo $drow['rolename'];  } ?></td>
<td><?
$qresult = mysqli_query($conn,"SELECT  filename FROM roles_pages WHERE logid='".$row['pages']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {  echo ucwords($drow['filename']);  } ?></td>
<td><? echo $row['view']?></td>
<td><? echo $row['canadd']?></td>
<td><? echo $row['canedit']?></td>
<td><? echo $row['candelete']?></td>
</tr>
<? } ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
 
  <? } ?>
</form>
<style>
  /* Container Panels & General Typography */
.x_panel {
    background: #ffffff;
    border: 1px solid #e6e9ed;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    margin-bottom: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.x_title h2 {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
    float: left;
}

/* Form Dropdown Area Styling */
.item.form-group.btnflat {
    background: #f8f9fa;
    border-left: 4px solid #3498db;
    padding: 15px 20px;
    border-radius: 4px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.item.form-group.btnflat label {
    margin: 0;
    color: #34495e;
    font-size: 14px;
}

.form-control.manda {
    border: 1px solid #cccccc;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 14px;
    color: #495057;
    transition: border-color 0.15s ease-in-out;
    background-color: #fff;
    width: 100%;
    max-width: 300px;
}

.form-control.manda:focus {
    border-color: #3498db;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

/* Modern Data Table Grids */
.table {
    width: 100%;
    margin-bottom: 20px;
    border-collapse: collapse;
    background-color: transparent;
}

.table th, 
.table td {
    padding: 12px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
    font-size: 14px;
}

/* Table Header Row */
.table tr[style*="background-color:#666"] {
    background-color: #2c3e50 !important;
    color: #ffffff !important;
}

.table tr[style*="background-color:#666"] td {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    border: none;
}

/* Module Category Group Header Breaks */
.table td[style*="background-color:#CCC"] {
    background-color: #edf2f7 !important;
    color: #2d3748 !important;
    font-weight: 700 !important;
    font-size: 13px;
    letter-spacing: 0.3px;
    border-top: 2px solid #cbd5e0;
    border-bottom: 2px solid #cbd5e0;
    padding: 10px 15px;
}

/* Checkbox alignment & Custom scaling */
.table input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
    accent-color: #2ecc71; /* Modern green accent tint for checked values */
    vertical-align: middle;
}

/* Quick Feature Select Buttons (Check/Uncheck Inline controls) */
.btnflat[type="button"] {
    border: 1px solid #dcdde1;
    background: #f8f9fa;
    color: #2f3542;
    padding: 4px 10px;
    font-size: 11px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 2px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Color Check (✔) buttons green on hover */
.btnflat[value="✔"]:hover {
    background-color: #2ecc71;
    border-color: #2ecc71;
    color: #ffffff;
}

/* Color Uncheck (✘) buttons red on hover */
.btnflat[value="✘"]:hover {
    background-color: #e74c3c;
    border-color: #e74c3c;
    color: #ffffff;
}

/* Action Execution Buttons Block */
.form-group.row.text-center {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.btn-success {
    background-color: #2ecc71;
    border-color: #2ecc71;
    color: #ffffff;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.btn-success:hover {
    background-color: #27ae60;
    border-color: #27ae60;
}

.btn-info {
    background-color: #34495e;
    border-color: #34495e;
    color: #ffffff;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.btn-info:hover {
    background-color: #2c3e50;
    border-color: #2c3e50;
}

/* Table Striping Alternate Effects */
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.01);
}

.table-striped tbody tr:hover {
    background-color: rgba(52, 152, 219, 0.04);
}
</style>
<? } ?>
