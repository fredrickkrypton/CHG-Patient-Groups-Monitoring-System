<?php 
if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

// FIX: Check if $_GET['D'] is set before processing to avoid the Undefined Key warning
if (function_exists('workout') && isset($_GET['D'])) { 
    workout($_GET['D']); 
}

// FIX: Initialize variables to prevent "Undefined variable" warnings
if (!isset($part)) { $part = ''; }
if (!isset($rec))  { $rec = '';  }

$timestamp=date('Y-m-d H:i:s');
$page="roles";

if(function_exists('caniacess')) {
    if($part=='') { caniacess($page."_view"); }
    if($part=='1') { caniacess($page."_add"); }
    if($part=='2') { caniacess($page."_edit"); }
    if(isset($_POST['delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop']==1)
{  
    include('stop.php'); 
}
else 
{
    if(isset($_POST['save']))
    {
        $rolename=$_POST['rolename'];
        $roledescription=$_POST['roledescription'];
        $username = isset($username) ? $username : 'System';
        $timestamp=$timestamp;

        if(trim($rolename)!='') { 
            $sql = "INSERT INTO roles (rolename,roledescription,username,timestamp)
                    VALUES ('$rolename','$roledescription','$username','$timestamp')";
            if ($conn->query($sql) === TRUE) {  
                $alert=1; 
                if(function_exists('audittrail')) { 
                    audittrail($conn,'roles','','inserting',$timestamp,$username); 
                } 
            } else { 
                $error=("Error: " . $sql . "<br>" . $conn->error); 
            }
        } else {  
            $error=("Fill all mandatory fields in red.");  
        }  
        
        if(!empty($alert))
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
        if(!empty($error))
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
 
    if(isset($_POST['update']))
    {
        $rolename=$_POST['rolename'];
        $roledescription=$_POST['roledescription'];
        $username = isset($username) ? $username : 'System';
        $timestamp=$timestamp;
        $logid=$_POST['logid'];

        if(trim($rolename)!='') { 
            $sql = "UPDATE roles SET rolename='$rolename',roledescription='$roledescription',username='$username',timestamp='$timestamp' WHERE roleid='$logid'";
            if ($conn->query($sql) === TRUE) {  
                $alert=1; 
                if(function_exists('audittrail')) { 
                    audittrail($conn,'roles',$logid,'updatings',$timestamp,$username); 
                } 
            } else { 
                $error=("Error: " . $sql . "<br>" . $conn->error); 
            }
        } else {  
            $error=("Fill all mandatory fields in red.");  
        }  
        
        if(!empty($alert))
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
        if(!empty($error))
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
 
    if(isset($_POST['delete']))
    { 
        $count = isset($_POST['txtcount']) ? intval($_POST['txtcount']) : 0;
        $deleted = 0;
        for($i=0;$i<$count;$i++){ 
            if(isset($_POST['del'][$i]) && $_POST['del'][$i]!='')
            { 
                if(function_exists('audittrail')) {
                    audittrail($conn,'roles',$_POST['del'][$i],'deleting',$timestamp,$username);
                }
                $sql = "DELETE FROM roles WHERE roleid='".$_POST['del'][$i]."'";
                if ($conn->query($sql) === TRUE) { $deleted=$deleted+1; }
            }  
        } 
        if ($deleted>0) { $alert=1; } else { $error=("No record selected"); }
        
        if(!empty($alert))
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
        if(!empty($error))
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

    if(function_exists('_title')) { _title('Roles'); } 
?>

<style>
/* CSS Styling Integration */
.x_panel {
    background: #ffffff;
    border: 1px solid #e6e9ed;
    border-radius: 8px;
    padding: 22px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    margin-bottom: 25px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.x_title h2 {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
    float: left;
}
.x_title h2 small {
    margin-left: 10px;
    font-size: 12px;
    color: #95a5a6;
    font-weight: 400;
    text-transform: uppercase;
}
.item.form-group {
    margin-bottom: 18px;
    display: flex;
    align-items: center;
}
.col-form-label {
    font-weight: 600;
    color: #34495e;
    font-size: 14px;
    text-align: right;
    padding-right: 15px;
}
.form-control.manda, textarea.form-control {
    border: 1px solid #cccccc;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 14px;
    color: #495057;
    transition: all 0.2s ease-in-out;
    background-color: #ffffff;
    width: 100%;
}
.form-control.manda:focus, textarea.form-control:focus {
    border-color: #3498db;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15);
}
.table {
    width: 100%;
    margin-bottom: 20px;
    border-collapse: collapse;
}
.table th, .table td {
    padding: 12px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
    font-size: 14px;
    color: #2c3e50;
}
.table thead tr {
    background-color: #2c3e50 !important;
}
.table thead tr th {
    color: #ffffff !important;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    border: none;
    padding: 14px 15px;
}
.table tfoot tr {
    background-color: #f8f9fa;
    border-top: 2px solid #cbd5e0;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.01);
}
.table-striped tbody tr:hover {
    background-color: rgba(52, 152, 219, 0.04);
}
.fa-edit {
    color: #3498db;
    font-size: 16px;
}
.table input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
    accent-color: #e74c3c;
}
.form-group.row.text-center {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}
.btn-success { background-color: #2ecc71; border-color: #2ecc71; color: #fff; font-weight: 600; padding: 7px 18px; border-radius: 4px; }
.btn-success:hover { background-color: #27ae60; border-color: #27ae60; }
.btn-primary { background-color: #3498db; border-color: #3498db; color: #fff; font-weight: 600; padding: 7px 18px; border-radius: 4px; }
.btn-primary:hover { background-color: #2980b9; }
.btn-secondary { background-color: #7f8c8d; border-color: #7f8c8d; color: #fff; font-weight: 600; padding: 7px 18px; border-radius: 4px; }
.btn-secondary:hover { background-color: #6c7a89; }
.btn-info { background-color: #34495e; border-color: #34495e; color: #fff; font-weight: 600; padding: 7px 18px; border-radius: 4px; }
.btn-info:hover { background-color: #2c3e50; }
button[name="delete"] { background-color: #e74c3c !important; border-color: #e74c3c !important; color: #fff !important; padding: 5px 12px; border-radius: 4px; cursor: pointer; }
button[name="delete"]:hover { background-color: #c0392b !important; }
</style>

<form method='POST' action='' class='rounded' name='roles' style='min-width:70%' enctype='multipart/form-data'>
<?php if($part=='1') { ?>
    <div class='col-md-8 offset-2'>
        <div class='x_panel'>
            <div class='x_title'>
                <h2><?=ucwords($page);?><small>New Entry</small></h2>
                <ul class='nav navbar-right panel_toolbox'>
                    <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a></li>
                </ul>
                <div class='clearfix'></div>
            </div>
            <div class='x_content center-block'>
                <div class="item form-group">
                    <label for="rolename" class="col-sm-3 col-form-label">Rolename : </label>
                    <div class="col-sm-5">
                        <input type="text" required='required' class="form-control form-control-sm manda" name="rolename" id="rolename" placeholder="Rolename" value="<?php echo isset($_POST['rolename']) ? htmlspecialchars($_POST['rolename']) : ''; ?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>

                <div class="item form-group">
                    <label for="roledescription" class="col-sm-3 col-form-label">Roledescription : </label>
                    <div class="col-sm-5">
                        <textarea class="form-control form-control-sm" name="roledescription" id="roledescription"><?php echo isset($_POST['roledescription']) ? htmlspecialchars($_POST['roledescription']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="ln_solid"></div>
                <div class="form-group row text-center">
                    <div class="col-md">
                        <button data-placement="bottom" title="Save data" type="submit" name="save" style="padding: 0.4em;" class="btn btn-success btn-sm">
                            <span>Save</span> <i class="fa fa-save"></i>
                        </button>
                        &nbsp; &nbsp;
                        <li><a href="?<?=$page?>&part=1" class="collapse-link"><i class="fa fa-plus"></i> Create</a></li>
                            <button type="button" name="new" class="btn btn-primary btn-sm" ><span>New</span> <i class="fa fa-file custom"></i></button>
                        </a>
                        &nbsp; &nbsp;
                        <a href="?/<?=$page?>" data-placement="bottom" title="Go to records">
                            <button type="button" name="exit" class="btn btn-secondary btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button>
                        </a>
                        &nbsp; &nbsp;
                        <a href="?" data-placement="bottom" title="Go to dashboard">
                            <button type="button" name="exit" class="btn btn-info btn-sm" ><span>Dashboard</span> <i class="fa fa-angle-double-right custom"></i></button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php } elseif($part=='2') { ?>
    <div class='col-md-8 offset-2'>
        <div class='x_panel'>
            <div class='x_title'>
                <h2><?=ucwords($page);?><small>Update Entry</small></h2>
                <ul class='nav navbar-right panel_toolbox'>
                    <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a></li>
                </ul>
                <div class='clearfix'></div>
            </div>
            <div class='x_content center-block'>
            <?php 
            $result = mysqli_query($conn,"SELECT * FROM roles WHERE roleid='$rec'");
            while($row = mysqli_fetch_assoc($result)) { 
                ?> 
                <div class="item form-group">
                    <label for="rolename" class="col-sm-3 col-form-label">Rolename : </label>
                    <div class="col-sm-5">
                        <input type="text" required='required' class="form-control form-control-sm manda" name="rolename" id="rolename" placeholder="Rolename" value="<?php echo htmlspecialchars($row['rolename']); ?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="roledescription" class="col-sm-3 col-form-label">Roledescription : </label>
                    <div class="col-sm-5">
                        <textarea class="form-control form-control-sm" name="roledescription" id="roledescription"><?php echo htmlspecialchars($row['roledescription']); ?></textarea>
                    </div>
                </div>

                <div class="ln_solid"></div>
                <div class="form-group row text-center">
                    <div class="col-md">
                        <button data-placement="bottom" title="Update data" type="submit" name="update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
                        &nbsp; &nbsp;
                        <a href="?/<?=$page?>&D=<?php echo function_exists('osteens') ? osteens('part=1') : ''; ?>" data-placement="bottom" title="Go to update page">
                            <button type="button" name="new" style="padding: 0.4em;" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button>
                        </a>
                        &nbsp; &nbsp;
                        <a href="?/<?=$page?>" data-placement="bottom" title="Go to records">
                            <button type="button" name="exit" class="btn btn-secondary btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button>
                        </a>
                        &nbsp; &nbsp;
                        <a href="?" data-placement="bottom" title="Go to dashboard">
                            <button type="button" name="exit" class="btn btn-info btn-sm"><span>Dashboard</span> <i class="fa fa-angle-double-right custom"></i></button>
                        </a>
                    </div>
                </div>
                <input name="logid" type="hidden" value="<?=$row['roleid']?>" />
            <?php } ?> 
            </div>
        </div>
    </div>

<?php } else { ?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Existing<small><?=ucwords($page);?></small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="?/<?=$page?>&D=<?php echo function_exists('osteens') ? osteens('part=1') : ''; ?>" class="collapse-link"><i class="fa fa-plus"></i> Create</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table id="datatable" style="width: 100%;" role="grid" class="table table-striped table-bordered dataTable table-sm">
                    <thead>
                        <tr>
                            <th>Role name</th>
                            <th>Role description</th>
                            <th>Username</th>
                            <th>Timestamp</th>
                            <th width="1%">Edit</th> 
                            <th width="1%">Del</th> 
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Role name</th>
                            <th>Role description</th>
                            <th>Username</th>
                            <th>Timestamp</th>
                            <th></th>
                            <th>
                                <button name="delete" value="Delete" type="submit" onClick="javascript: return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
                            </th> 
                        </tr>
                    </tfoot>
                    <tbody>
                    <?php 
                    $mycount=0;
                    if(isset($_POST['tbn_search'])) {
                        $search = mysqli_real_escape_string($conn, $_POST['search']);
                        $result = mysqli_query($conn,"SELECT * FROM roles where rolename like '%$search%' OR roledescription like '%$search%' OR username like '%$search%' OR timestamp like '%$search%'");
                    } else {
                        $result = mysqli_query($conn,"SELECT * FROM roles");
                    }
                    while($row = mysqli_fetch_assoc($result)) { 
                        $mycount=$mycount+1; 
                        ?> 				  
                        <tr>
                          <!-- Rolename is usually required, but safe-guarding it too -->
                          <td><?php echo htmlspecialchars($row['rolename'] ?? ''); ?></td>
                          
                          <!-- FIX: Safe handling for potentially NULL database columns -->
                          <td><?php echo htmlspecialchars($row['roledescription'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($row['username'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($row['timestamp'] ?? ''); ?></td>

                          <td align="center">
                              <?php $edit="part=2&rec=".$row['roleid']; ?>
                              <a href="?/<?=$page?>&D=<?php echo function_exists('osteens') ? osteens($edit) : ''; ?>" data-placement="bottom" title="Go to edit"><i class="fa fa-edit"></i></a>
                          </td>
                          <td align="center">
                              <?php if($row['roleid'] > 2) { ?>
                                  <input name="del[]" id="del[]" type="checkbox" value="<?=$row['roleid']?>" />
                              <?php } ?>
                          </td>
q                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <input type="hidden" name="txtcount" id="txtcount" value="<?php echo $mycount;?>"> 
<?php } ?>
</form>
<?php } ?>