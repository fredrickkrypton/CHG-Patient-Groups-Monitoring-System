<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-21 20:20:24

global $title, $conn, $part, $rec, $username;

$page="provider_group";
$timestamp=date('Y-m-d H:i:s');

// Initialise variables so PHP does not raise "Undefined variable/key" warnings
$alert = '';
$error = '';
$deleted = 0;
$saved_ok = false;

// Only decode the D parameter when it is present (the list view is opened without it)
if (isset($_GET['D']) && $_GET['D'] !== '') { workout($_GET['D']); }
if (!isset($part)) { $part = ''; }
if (!isset($rec))  { $rec  = ''; }
$part = trim((string)$part);

// Provider whose groups are being viewed (passed as &p=ID)
$p_raw = isset($_GET['p']) ? trim($_GET['p']) : '';
$p     = mysqli_real_escape_string($conn, $p_raw);
$pq    = ($p_raw !== '') ? "&p=".urlencode($p_raw) : '';

// Provider name (kept for reference; no longer printed in the headings)
$provider_name = '';
if ($p !== '') {
    $pn = mysqli_query($conn, "SELECT name FROM provider WHERE logid='$p'");
    if ($pn && ($pnrow = mysqli_fetch_assoc($pn))) { $provider_name = $pnrow['name']; }
}

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['provider_group_delete'])) { caniacess($page."_delete"); }

if(isset($_SESSION['stop']) && $_SESSION['stop']==1)
{  include('stop.php'); }
else 
{

//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['provider_group_save']))
{
    $name=mysqli_real_escape_string($conn, ucwords($_POST['name'] ?? ''));
    $paybill_no=mysqli_real_escape_string($conn, ucwords($_POST['paybill_no'] ?? ''));
    $provider_id=mysqli_real_escape_string($conn, ucwords($_POST['provider_id'] ?? ''));
    $date_registered=dateto_mysql($_POST['date_registered'] ?? '');
    $created_by=mysqli_real_escape_string($conn, ucwords($username));
    $created_on=mysqli_real_escape_string($conn, ucwords($timestamp));

    if(trim($name)!=''&&trim($paybill_no)!=''&&trim($provider_id)!=''&&trim($date_registered)!=''&&trim($created_by)!=''&&trim($created_on)!='') { 
        $sql = "INSERT INTO provider_group (name,paybill_no,provider_id,date_registered,created_by,created_on)
                VALUES ('$name','$paybill_no','$provider_id','$date_registered','$created_by','$created_on')";
        if ($conn->query($sql) === TRUE) {  
            $alert=1; 
            $saved_ok = true;
            audittrail($conn,'provider_group','','inserting',$timestamp,$username); 
        } else { 
            $error=("Error: you are duplicating data!"); 
        }
    } else {  
        $error="Fill all mandatory fields in red.";  
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
                                      text: '".addslashes($error)."',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['provider_group_update']))
{
    $name=mysqli_real_escape_string($conn, ucwords($_POST['name'] ?? ''));
    $paybill_no=mysqli_real_escape_string($conn, ucwords($_POST['paybill_no'] ?? ''));
    $provider_id=mysqli_real_escape_string($conn, ucwords($_POST['provider_id'] ?? ''));
    $date_registered=dateto_mysql($_POST['date_registered'] ?? '');
    $created_by=mysqli_real_escape_string($conn, ucwords($username));
    $created_on=mysqli_real_escape_string($conn, ucwords($timestamp));
    $logid=mysqli_real_escape_string($conn, $_POST['logid'] ?? '');

    if(trim($name)!=''&&trim($paybill_no)!=''&&trim($provider_id)!=''&&trim($date_registered)!=''&&trim($created_by)!=''&&trim($created_on)!='') { 
        $sql = "UPDATE provider_group SET name='$name',paybill_no='$paybill_no',provider_id='$provider_id',date_registered='$date_registered',created_by='$created_by',created_on='$created_on' WHERE logid='$logid'";
        if ($conn->query($sql) === TRUE) {  
            $alert=1; 
            audittrail($conn,'provider_group',$logid,'updatings',$timestamp,$username); 
        } else { 
            $error=("Error: you are duplicating data!"); 
        }
    } else {  
        $error="Fill all mandatory fields in red.";  
    }  

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
                                      text: '".addslashes($error)."',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['provider_group_delete']))
{ 
    if(!empty($_POST['del']) && is_array($_POST['del'])) {
        foreach($_POST['del'] as $delid) {
            if(trim($delid)==='') { continue; }
            $delid = mysqli_real_escape_string($conn, $delid);
            audittrail($conn,'provider_group',$delid,'deleting',$timestamp,$username);
            if ($conn->query("DELETE FROM provider_group WHERE logid='$delid'") === TRUE) { $deleted++; }
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
                                      text: '".addslashes($error)."',
									  nonblock: { nonblock: !0 },
                                      type: 'error',
                                      styling: 'bootstrap3'
                                  }); }) </script>";
    }	  
}

//---------------------------------------------------------------------------------------------------- 
_title($title='Provider Group'); ?>

<style>
/* ---------- Panel ---------- */
form[name='provider_group'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='provider_group'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='provider_group'] .x_title h2 {
    font-size: 1.15rem;
    font-weight: 600;
    color: #1a73e8;
    margin: 0;
}
form[name='provider_group'] .x_content { padding: 24px 28px; }
form[name='provider_group'] .panel_toolbox { list-style: none; margin: 0; padding: 0; }
form[name='provider_group'] .panel_toolbox a { color: #1a73e8; font-weight: 600; }
form[name='provider_group'] .panel_toolbox a:hover { color: #1558b8; text-decoration: none; }

/* ---------- Form rows (label left, field right) ---------- */
form[name='provider_group'] .item.form-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
form[name='provider_group'] .item.form-group > label {
    flex: 0 0 25%;
    max-width: 25%;
    padding-right: 14px;
    margin: 0;
    font-weight: 600;
    font-size: .85rem;
    color: #1a73e8;
    text-align: center;
}
form[name='provider_group'] .item.form-group > div[class*='col-sm-'] {
    flex: 1 1 0;
    max-width: 66%;
}

/* ---------- Inputs & selects ---------- */
form[name='provider_group'] .form-control {
    width: 100%;
    border: 1px solid #cfd7e3;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: .9rem;
    color: #1a73e8;
    text-align: center;
    text-align-last: center;
    background-color: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='provider_group'] .form-control::placeholder { color: #9bbcf0; }
form[name='provider_group'] .form-control:focus {
    border-color: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .18);
    outline: none;
}
form[name='provider_group'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='provider_group'] .form-control:required:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, .15);
}

/* ---------- Divider & buttons ---------- */
form[name='provider_group'] .ln_solid { border-top: 1px solid #e3e8ee; margin: 20px 0 18px; }
form[name='provider_group'] .btn {
    border-radius: 6px;
    padding: .45em 1.4em !important;
    font-weight: 600;
    letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='provider_group'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='provider_group'] .btn-success { background: #26b99a; border-color: #26b99a; }
form[name='provider_group'] .btn-success:hover { background: #1f9c82; }
form[name='provider_group'] .btn-info { background: #1a73e8; border-color: #1a73e8; }
form[name='provider_group'] .btn-info:hover { background: #1558b8; }

/* ---------- Table ---------- */
form[name='provider_group'] .x_content { overflow-x: auto; }
form[name='provider_group'] .table { width: 100%; margin-bottom: 0; white-space: nowrap; }
form[name='provider_group'] .table th,
form[name='provider_group'] .table td { padding: 10px 14px; vertical-align: middle; font-size: 13px; }
form[name='provider_group'] .table thead th {
    background: #f7f9fc;
    color: #1a73e8;
    font-weight: 700;
    border-bottom: 2px solid #26b99a;
}
form[name='provider_group'] .table tfoot th { background: #f7f9fc; border-top: 1px solid #e3e8ee; }
form[name='provider_group'] .table-striped tbody tr:hover { background: rgba(38, 185, 154, .07); }
form[name='provider_group'] .fa-pencil { color: #1a73e8; }
form[name='provider_group'] .fa-pencil:hover { color: #1f9c82; }
form[name='provider_group'] .table input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
form[name='provider_group'] button[name='provider_group_delete'] {
    background: #e74c3c; border: 0; color: #fff; border-radius: 6px; padding: 5px 12px;
}
form[name='provider_group'] button[name='provider_group_delete']:hover { background: #c0392b; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='provider_group'] { min-width: 100% !important; }
    form[name='provider_group'] .item.form-group > label,
    form[name='provider_group'] .item.form-group > div[class*='col-sm-'] { flex: 0 0 100%; max-width: 100%; }
    form[name='provider_group'] .item.form-group > label { padding-bottom: 4px; }
    form[name='provider_group'] .x_content { padding: 18px 14px; }
}
</style>

<form method='POST' action='' class='rounded' name='provider_group' style='min-width:70%'  enctype='multipart/form-data'>

<?php if($part=='1')
{ 
    // After a successful save, clear the form so the same record isn't saved twice
    $form = $saved_ok ? array() : $_POST;
?>

<div class='col-md-8 offset-2'>
    <div class='x_panel'>
        <div class='x_title'>
            <h2><?=ucwords($title);?></h2>
            <ul class='nav navbar-right panel_toolbox'>
                <li><a class='link'><i class='fa fa-chevron-up'></i></a></li>
            </ul>
            <div class='clearfix'></div>
        </div>
        <div class='x_content center-block'>

            <div class="item form-group">
                <label for="name" class="col-sm-3 manda">Group Name : </label>
                <div class="col-sm-8">
                    <input type="text" required='required' class="form-control form-control-sm manda" name="name" id="name" placeholder="Name" value="<?php echo htmlspecialchars($form['name'] ?? ''); ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="paybill_no" class="col-sm-3 manda">Paybill no : </label>
                <div class="col-sm-8">
                    <input type="text" required='required' class="form-control form-control-sm manda" name="paybill_no" id="paybill_no" placeholder="Paybill no" value="<?php echo htmlspecialchars($form['paybill_no'] ?? ''); ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="provider_id" class="col-sm-3 manda">Provider : </label>
                <div class="col-sm-8"> 
                    <select class="form-control manda" id="provider_id" name="provider_id" required='required'>
                    <?php
                    if($p !== '') {
                        // Opened from a provider: lock the dropdown to that provider
                        $qresult = mysqli_query($conn,"SELECT logid,name FROM provider WHERE logid='$p' ORDER BY name");
                    } else {
                        echo '<option value="">Select Provider</option>';
                        $qresult = mysqli_query($conn,"SELECT logid,name FROM provider ORDER BY name");
                    }
                    $posted_provider = $form['provider_id'] ?? '';
                    while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                        <option value="<?php echo $drow['logid']; ?>" <?php echo ((string)$posted_provider === (string)$drow['logid']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($drow['name']); ?></option>
                    <?php } ?>
                    </select>
                </div>
            </div>

            <div class="item form-group">
                <label for="date_registered" class="col-sm-3 manda">Date registered : </label>
                <div class="col-sm-8">
                    <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($form['date_registered'] ?? date('d/m/Y')); ?>" name="date_registered" id="date_registered" onClick="return xCal('date_registered','/',4)">
                </div>
            </div>

            <div class="ln_solid"></div>
            <div class="form-group row text-center">
                <div class="col-md">
                    <button data-placement="bottom" title="Save data" type="submit" name="provider_group_save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
                    &nbsp; &nbsp;
                    <a href="index.php?page=<?=$page?><?=$pq?>" title="View groups">
                        <button type="button" name="view" class="btn btn-success btn-sm"><span>View</span> <i class="fa fa-eye"></i></button></a>
                    &nbsp; &nbsp;
                    <a href="index.php?page=provider" title="Back to providers">
                        <button type="button" name="exit" class="btn btn-info btn-sm"><i class="fa fa-reply" style="float: none;"></i> &nbsp;<span>Provider</span></button></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php } 
elseif($part=='2') { ?>

<div class='col-md-8 offset-2'>
    <div class='x_panel'>
        <div class='x_title'>
            <h2><?=ucwords($title);?></h2>
            <ul class='nav navbar-right panel_toolbox'>
                <li><a class='link'><i class='fa fa-chevron-up'></i></a></li>
            </ul>
            <div class='clearfix'></div>
        </div>
        <div class='x_content center-block'>
        <?php $rec_safe = mysqli_real_escape_string($conn, $rec);
        $result = mysqli_query($conn,"SELECT * FROM provider_group WHERE logid='$rec_safe'");
        if(!$result) { echo "<p style='color:#c0392b'>Query error: ".htmlspecialchars($conn->error)."</p>"; }
        while($result && $row = mysqli_fetch_assoc($result)) { ?> 

            <div class="item form-group">
                <label for="name" class="col-sm-3 manda">Group Name : </label>
                <div class="col-sm-8"> 
                    <input type="text" required='required' class="form-control form-control-sm manda" name="name" id="name" placeholder="Name" value="<?php echo htmlspecialchars($row['name']); ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="paybill_no" class="col-sm-3 manda">Paybill no : </label>
                <div class="col-sm-8"> 
                    <input type="text" required='required' class="form-control form-control-sm manda" name="paybill_no" id="paybill_no" placeholder="Paybill no" value="<?php echo htmlspecialchars($row['paybill_no']); ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="provider_id" class="col-sm-3 manda">Provider : </label>
                <div class="col-sm-8">
                    <select class="form-control manda" id="provider_id" name="provider_id" required='required'>
                    <?php $qresult = mysqli_query($conn,"SELECT logid,name FROM provider WHERE logid='".mysqli_real_escape_string($conn,$row['provider_id'])."'");
                    while($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                        <option value="<?php echo $drow['logid']; ?>"><?php echo htmlspecialchars($drow['name']); ?></option>
                    <?php } ?>
                        <option value="">Select Provider</option>
                    </select>
                </div>
            </div>

            <div class="item form-group">
                <label for="date_registered" class="col-sm-3 manda">Date registered : </label>
                <div class="col-sm-8">
                    <input type="text" class="form-control form-control-sm" value="<?php echo dateto_normal($row['date_registered']); ?>" name="date_registered" id="date_registered" onClick="return xCal('date_registered','/',4)">
                </div>
            </div>

            <div class="ln_solid"></div>
            <div class="form-group row text-center">
                <div class="col-md">
                    <button data-placement="bottom" title="Update data" type="submit" name="provider_group_update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
                    &nbsp; &nbsp; 
                    <a href="index.php?page=<?=$page?><?=$pq?>" title="View groups">
                        <button type="button" name="view" class="btn btn-success btn-sm"><span>View</span> <i class="fa fa-eye"></i></button></a>
                    &nbsp; &nbsp;
                    <a href="index.php?page=provider" title="Back to providers">
                        <button type="button" name="exit" class="btn btn-info btn-sm"><i class="fa fa-reply" style="float: none;"></i> &nbsp;<span>Provider</span></button></a>
                </div>
            </div>
            <input name="logid" type="hidden" value="<?=$row['logid']?>" />
        <?php } ?> 
        </div>
    </div>
</div>

<?php } ?>

<!-- ==================== EXISTING RECORDS (always shown) ==================== -->
<div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
            <h2><?=ucwords($title);?></h2>
            <ul class="nav navbar-right panel_toolbox">
                <li><a href="index.php?page=provider" class="link"><i class="fa fa-reply"></i> Providers</a></li>
                <li><a href="index.php?page=<?=$page?>&D=<?php echo osteens('part=1'); ?><?=$pq?>" class="link"><i class="fa fa-plus"></i> Create</a></li>
            </ul>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">

            <table id="datatable" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
                <thead>
                    <tr>
                        <th width="1px">#</th>
                        <th>Group Name</th>
                        <th>Paybill No</th>
                        <th>Provider</th>
                        <th>Date Registered</th>
                        <th>Created By</th>
                        <th>Created On</th>
                        <th width="1%">Edit</th>
                        <th width="1%">Del</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th colspan="8"></th>
                        <th>
                            <button name="provider_group_delete" value="Delete" type="submit" class="button sample btn-sm" onClick="javascript: return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
                        </th> 
                    </tr>
                </tfoot>
                <tbody>
                <?php $mycount=0;
                if($p !== '') {
                    $result = mysqli_query($conn,"SELECT * FROM provider_group WHERE provider_id='$p'");
                } else {
                    $result = mysqli_query($conn,"SELECT * FROM provider_group");
                }
                if(!$result) { echo "<tr><td colspan='9' style='color:#c0392b'>Query error: ".htmlspecialchars($conn->error)."</td></tr>"; }
                while($result && $row = mysqli_fetch_assoc($result)) { $mycount=$mycount+1; ?> 				  
                    <tr>
                        <td><?=$mycount?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['paybill_no']); ?></td>
                        <td><?php
                        $qresult = mysqli_query($conn,"SELECT name FROM provider WHERE logid='".mysqli_real_escape_string($conn,$row['provider_id'])."'");
                        while($qresult && $drow = mysqli_fetch_assoc($qresult)) { echo htmlspecialchars($drow['name']); } ?></td>
                        <td><?php echo dateto_display($row['date_registered']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_on']); ?></td>
                        <td align="center"><?php $edit="part=2&rec=".$row['logid']; ?>
                            <a href="index.php?page=<?=$page?>&D=<?php echo osteens($edit); ?><?=$pq?>" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a></td>
                        <td align="center">
                            <input name="del[]" type="checkbox" value="<?=$row['logid']?>" />
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <input type="hidden" name="txtcount" id="txtcount" value="<?php echo $mycount; ?>"> 
        </div>
    </div>
</div>

</form>
<?php } ?>