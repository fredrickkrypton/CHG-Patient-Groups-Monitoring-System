<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBALS
global $title;
$conn = isset($GLOBALS['conn']) ? $GLOBALS['conn'] : null;

$page = "0_users";

// Ensure variables are safely initialized to avoid notice level errors
$part = isset($_GET['part']) ? $_GET['part'] : '';
$rec = isset($_GET['rec']) ? $_GET['rec'] : 0;
$username_session = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';
$deleted = 0;
$alert = '';
$error = '';

// SAFE CHECK: Handles URL parameter unpacking cleanly if workout() is undefined
if (function_exists('workout')) {
    if (isset($_GET['D'])) { workout($_GET['D']); }
} else {
    if (!empty($_GET['D'])) {
        parse_str(base64_decode($_GET['D']), $output);
        foreach ($output as $key => $val) {
            $$key = $val;
        }
    }
}

$timestamp = date('Y-m-d H:i:s');

if (function_exists('caniacess')) {
    if($part == '') { caniacess($page."_view"); }
    if($part == '1') { caniacess($page."_add"); }
    if($part == '2') { caniacess($page."_edit"); }
    if(isset($_POST['0_users_delete'])) { caniacess($page."_delete"); }
}

if(isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {  
    include('stop.php'); 
} else {

//---------------------------------------------------------------------------------------------------- 
// INSERT/SAVE LOGIC (With Profile Picture Upload Buffer Processing)
if(isset($_POST['0_users_save'])) {
    $username_input = strtolower(trim($_POST['username']));
    $fullnames      = ucwords($_POST['fullnames']);
    $password       = md5($_POST['password']);
    $email          = trim($_POST['email']);
    $phone          = ucwords($_POST['phone']);
    $role           = ucwords($_POST['role']);
    $groupid        = isset($_POST['groupid']) ? $_POST['groupid'] : '';
    $active         = ucwords($_POST['active']);

    // Handle Profile Image Upload safely
    $profpic = '';
    if (!empty($_FILES['profpic']['name'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = date('ymdHis') . "_" . basename($_FILES["profpic"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["profpic"]["tmp_name"], $target_file)) {
            $profpic = $filename;
        }
    }

    if($username_input != '' && $fullnames != '' && $_POST['password'] != '') { 
        // Duplicate username prevention check
        $check = $conn ? mysqli_query($conn, "SELECT logid FROM 0_users WHERE username='$username_input'") : null;
        if ($check && mysqli_num_rows($check) > 0) {
            $error = "The username '$username_input' is already taken.";
        } else {
            $sql = "INSERT INTO 0_users (username, fullnames, password, email, phone, role, groupid, profpic, active, timestamp)
                    VALUES ('$username_input', '$fullnames', '$password', '$email', '$phone', '$role', '$groupid', '$profpic', '$active', '$timestamp')";
            if ($conn && $conn->query($sql) === TRUE) {  
                $alert = 1; 
                if(function_exists('audittrail')) { audittrail($conn, '0_users', '', 'inserting', $timestamp, $username_session); }
            } else { 
                $error = "Database insertion error: " . ($conn ? $conn->error : ''); 
            }
        }
    } else {  
        $error = "Fill all mandatory fields marked with an asterisk (*).";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'System user added successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// UPDATE LOGIC
if(isset($_POST['0_users_update'])) {
    $fullnames = ucwords($_POST['fullnames']);
    $email     = trim($_POST['email']);
    $phone     = ucwords($_POST['phone']);
    $role      = ucwords($_POST['role']);
    $groupid   = isset($_POST['groupid']) ? $_POST['groupid'] : '';
    $active    = ucwords($_POST['active']);
    $logid     = $_POST['logid'];

    // Conditional password modifier block
    $password_update = "";
    if(!empty($_POST['password'])) {
        $enc_pass = md5($_POST['password']);
        $password_update = ", password='$enc_pass'";
    }

    // Dynamic Image tracking modifier update string check
    $pic_update = "";
    if (!empty($_FILES['profpic']['name'])) {
        $target_dir = "uploads/";
        $filename = date('ymdHis') . "_" . basename($_FILES["profpic"]["name"]);
        if (move_uploaded_file($_FILES["profpic"]["tmp_name"], $target_dir . $filename)) {
            $pic_update = ", profpic='$filename'";
        }
    }

    if($fullnames != '') { 
        $sql = "UPDATE 0_users SET fullnames='$fullnames', email='$email', phone='$phone', role='$role', groupid='$groupid', active='$active' $password_update $pic_update WHERE logid='$logid'";
        if ($conn && $conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) { audittrail($conn, '0_users', $logid, 'updatings', $timestamp, $username_session); }
        } else { 
            $error = "Error altering user context properties."; 
        }
    } else {  
        $error = "Full names cannot be empty.";  
    }  
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'User account updated perfectly!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
// DELETE LOGIC
if(isset($_POST['0_users_delete'])) { 
    $count = isset($_POST['txtcount']) ? $_POST['txtcount'] : 0;
    for($i=0; $i<$count; $i++) { 
        if(!empty($_POST['del'][$i])) { 
            if(function_exists('audittrail')) {
                audittrail($conn, '0_users', $_POST['del'][$i], 'deleting', $timestamp, $username_session);
            }
            $sql = "DELETE FROM 0_users WHERE logid='".$_POST['del'][$i]."'";
            if ($conn && $conn->query($sql) === TRUE) { $deleted++; }
        }  
    } 
    if ($deleted > 0) { $alert = 1; } else { $error = "No user entry row selected."; }
    
    if($alert != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Success', text: 'User removed from access permissions.', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if($error != '') {
        echo "<script> window.addEventListener('load', function () { 
            new PNotify({ title: 'Error', text: '$error', nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }	  
}

$display_title = 'System Account Management';
if (function_exists('_title')) {
    _title($title = $display_title); 
} ?>

<style>
  .custom-table-wrapper {
    width: 100% !important;
    overflow-x: auto !important;
    margin-top: 15px;
    background: #fff;
    border: 1px solid #e3e6f0;
    border-radius: 4px;
    padding: 10px;
  }
  .custom-table-wrapper table {
    width: 100% !important;
    max-width: none !important;
    white-space: nowrap !important;
  }
  .custom-table-wrapper th, .custom-table-wrapper td {
    padding: 10px 14px !important;
    vertical-align: middle !important;
    font-size: 13px !important;
  }
  .custom-table-wrapper th {
    background-color: #f8f9fc !important;
    color: #4e73df !important;
    font-weight: 700 !important;
  }
  .manda { color: #ff0000; font-weight: bold; }
  .user-avatar-preview {
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #e3e6f0;
  }
</style>

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <form method='POST' action='' class='form-horizontal form-label-left' name='0_users' style='min-width:100%' enctype='multipart/form-data'>
                                            
        <?php if($part=='1') { ?>
        <div class='col-md-8 offset-md-2 col-sm-12'>
            <div class='x_panel'>
                <div class='x_title'>
                    <h2><?=ucwords($display_title);?><small> register operator</small></h2>
                    <div class='clearfix'></div>
                </div>
                <div class='x_content'>
                    <br />
                    
                    <div class="item form-group row">
                        <label for="username" class="col-form-label col-md-3 col-sm-3 label-align">Unique Username <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" required="required" class="form-control form-control-sm" name="username" id="username" placeholder="e.g. john_doe" autocomplete="off">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="fullnames" class="col-form-label col-md-3 col-sm-3 label-align">Full Names <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" required="required" class="form-control form-control-sm" name="fullnames" id="fullnames" placeholder="e.g. Johnathan Doe">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="password" class="col-form-label col-md-3 col-sm-3 label-align">Account Password <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="password" required="required" class="form-control form-control-sm" name="password" id="password" placeholder="••••••••" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="email" class="col-form-label col-md-3 col-sm-3 label-align">Email Address :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="email" class="form-control form-control-sm" name="email" id="email" placeholder="john@example.com">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="phone" class="col-form-label col-md-3 col-sm-3 label-align">Phone Number :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="text" class="form-control form-control-sm" name="phone" id="phone" placeholder="e.g. +256700000000">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="role" class="col-form-label col-md-3 col-sm-3 label-align">System Access Level <span class="manda">*</span> :</label>
                        <div class="col-md-8 col-sm-8">
                            <select required="required" class="form-control form-control-sm" name="role" id="role">
                                <option value="">-- Assign Matrix Role Security Level --</option>
                                <?php 
                                $r_res = $conn ? mysqli_query($conn, "SELECT roleid, rolename FROM roles ORDER BY rolename ASC") : null;
                                while($r_res && $r_row = mysqli_fetch_assoc($r_res)) {
                                    echo "<option value='".$r_row['roleid']."'>".htmlspecialchars($r_row['rolename'])."</option>";
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="profpic" class="col-form-label col-md-3 col-sm-3 label-align">Profile Picture :</label>
                        <div class="col-md-8 col-sm-8">
                            <input type="file" class="form-control-file" name="profpic" id="profpic" accept="image/*">
                        </div>
                    </div>

                    <div class="item form-group row">
                        <label for="active" class="col-form-label col-md-3 col-sm-3 label-align">Active Status :</label>
                        <div class="col-md-8 col-sm-8">
                            <select class="form-control form-control-sm" name="active" id="active">
                                <option value="Yes">Yes (Granted Authorization)</option>
                                <option value="No">No (Suspended / Disabled)</option>
                            </select>
                        </div>
                    </div>

                    <div class="ln_solid"></div>
                    <div class="form-group row text-center">
                        <div class="col-md-12">
                            <button type="submit" name="0_users_save" class="btn btn-success btn-sm"><span>Create User</span> <i class="fa fa-user-plus"></i></button>
                            &nbsp; &nbsp;
                            <a href="index.php?page=<?=$page?>&part=1" class="btn btn-primary btn-sm"><span>Clear</span> <i class="fa fa-refresh"></i></a>
                            &nbsp; &nbsp;
                            <a href="index.php?page=<?=$page?>" class="btn btn-info btn-sm"><span>View Registry</span> <i class="fa fa-users"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php } elseif($part=='2') { ?>
        <div class='col-md-8 offset-md-2 col-sm-12'>
            <div class='x_panel'>
                <div class='x_title'>
                    <h2><?=ucwords($display_title);?><small> modify attributes</small></h2>
                    <div class='clearfix'></div>
                </div>
                <div class='x_content'>
                    <br />
                    <?php 
                    $result = $conn ? mysqli_query($conn,"SELECT * FROM 0_users WHERE logid='$rec'") : null;
                    while($result && $row = mysqli_fetch_assoc($result)) { ?> 
                        
                        <div class="item form-group row">
                            <label class="col-form-label col-md-3 col-sm-3 label-align">Account Username :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['username'] ?? '')?>" disabled>
                                <small class="text-danger">Registration username strings cannot be mutated after assignment.</small>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="fullnames" class="col-form-label col-md-3 col-sm-3 label-align">Full Names <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" required="required" class="form-control form-control-sm" name="fullnames" id="fullnames" value="<?php echo htmlspecialchars($row['fullnames'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="password" class="col-form-label col-md-3 col-sm-3 label-align">New Password :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="password" class="form-control form-control-sm" name="password" id="password" placeholder="Leave totally blank to preserve current password entry string" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="email" class="col-form-label col-md-3 col-sm-3 label-align">Email Address :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="email" class="form-control form-control-sm" name="email" id="email" value="<?php echo htmlspecialchars($row['email'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="phone" class="col-form-label col-md-3 col-sm-3 label-align">Phone Number :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="text" class="form-control form-control-sm" name="phone" id="phone" value="<?php echo htmlspecialchars($row['phone'] ?? '')?>">
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="role" class="col-form-label col-md-3 col-sm-3 label-align">Security Group Level <span class="manda">*</span> :</label>
                            <div class="col-md-8 col-sm-8">
                                <select required="required" class="form-control form-control-sm" name="role" id="role">
                                    <?php 
                                    $r_res = mysqli_query($conn, "SELECT roleid, rolename FROM roles ORDER BY rolename ASC");
                                    while($r_res && $r_row = mysqli_fetch_assoc($r_res)) {
                                        $sel = ($r_row['roleid'] == $row['role']) ? 'selected' : '';
                                        echo "<option value='".$r_row['roleid']."' $sel>".htmlspecialchars($r_row['rolename'])."</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="profpic" class="col-form-label col-md-3 col-sm-3 label-align">Overwrite Profile Image :</label>
                            <div class="col-md-8 col-sm-8">
                                <input type="file" class="form-control-file" name="profpic" id="profpic" accept="image/*">
                                <?php if(!empty($row['profpic']) && file_exists("uploads/".$row['profpic'])) { ?>
                                    <div class="mt-2">
                                        <img src="uploads/<?=$row['profpic']?>" class="user-avatar-preview" height="45" width="45">
                                        <span class="small text-muted ml-2">Active assigned avatar file asset</span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="item form-group row">
                            <label for="active" class="col-form-label col-md-3 col-sm-3 label-align">Active Authorized Status :</label>
                            <div class="col-md-8 col-sm-8">
                                <select class="form-control form-control-sm" name="active" id="active">
                                    <option value="Yes" <?php echo ($row['active'] == 'Yes') ? 'selected':''?>>Yes (Granted Authorization)</option>
                                    <option value="No" <?php echo ($row['active'] == 'No') ? 'selected':''?>>No (Suspended / Disabled)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group row text-center">
                            <div class="col-md-12">
                                <button type="submit" name="0_users_update" class="btn btn-success btn-sm"><span>Commit Updates</span> <i class="fa fa-save"></i></button>
                                &nbsp; &nbsp; 
                                <a href="index.php?page=<?=$page?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
                                &nbsp; &nbsp; 
                                <a href="index.php?page=<?=$page?>" class="btn btn-info btn-sm"><span>View Registry</span> <i class="fa fa-users"></i></a>
                            </div>
                        </div> 
                        <input name="logid" type="hidden" value="<?=$row['logid']?>" />
                    <?php } ?> 
                </div>
            </div>
        </div>

        <?php } else { ?>
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2><?=ucwords($display_title);?><small> authorized personnel directory</small></h2>
                    <ul class="nav navbar-right panel_toolbox" style="list-style: none;">
                        <li><a href="index.php?page=<?=$page?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-user-plus"></i> Register New User</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div class="custom-table-wrapper">
                        <table id="datatable" role="grid" class="table table-striped table-bordered dataTable table-sm">
                            <thead>
                                <tr>
                                    <th width="4%">#</th>
                                    <th width="10%">Username</th>
                                    <th>Personnel Name</th>
                                    <th>Email Address</th>
                                    <th>Phone Axis</th>
                                    <th>Security Access Level Group</th>
                                    <th width="5%">Avatar</th>
                                    <th width="6%">Active Status</th>
                                    <th width="1%">Edit</th> 
                                    <th width="1%">Del</th> 
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Personnel Name</th>
                                    <th>Email Address</th>
                                    <th>Phone Axis</th>
                                    <th>Security Access Level Group</th>
                                    <th>Avatar</th>
                                    <th>Active Status</th>
                                    <th></th>
                                    <th>
                                        <button name="0_users_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onClick="javascript: return confirm('Please confirm removal of personnel?');"><i class="fa fa-user-times"></i></button>
                                    </th> 
                                </tr>
                            </footer>
                            <tbody>
                            <?php $mycount=0;
                            if(isset($_POST['tbn_search']) && $conn) {
                                $search = $conn->real_escape_string($_POST['search']);
                                $result = mysqli_query($conn,"SELECT * FROM 0_users WHERE username LIKE '%$search%' OR fullnames LIKE '%$search%' OR email LIKE '%$search%'");
                            } else {
                                $result = $conn ? mysqli_query($conn,"SELECT * FROM 0_users ORDER BY logid DESC") : null;
                            }

                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { $mycount++; ?> 				  
                                    <tr>
                                        <td><?=$mycount?></td>
                                        <td><code class="text-dark"><strong><?php echo htmlspecialchars($row['username'] ?? '')?></strong></code></td>
                                        <td><strong><?php echo htmlspecialchars($row['fullnames'] ?? '')?></strong></td>
                                        <td><?php echo htmlspecialchars(!empty($row['email']) ? $row['email'] : '-')?></td>
                                        <td><?php echo htmlspecialchars(!empty($row['phone']) ? $row['phone'] : '-')?></td>
                                        <td><?php
                                            $r_id = $row['role'];
                                            $r_q = mysqli_query($conn, "SELECT rolename FROM roles WHERE roleid='$r_id'");
                                            $r_data = mysqli_fetch_assoc($r_q);
                                            echo htmlspecialchars($r_data['rolename'] ?? 'Unassigned Role Group');
                                        ?></td>
                                        <td align="center">
                                            <?php if(!empty($row['profpic']) && file_exists("uploads/".$row['profpic'])) { ?>
                                                <img src="uploads/<?=$row['profpic']?>" class="user-avatar-preview" height="40" width="40" alt="Avatar">
                                            <?php } else { ?>
                                                <img src="images/img.jpg" class="user-avatar-preview" height="40" width="40" alt="Default">
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if(($row['active'] ?? 'No') == 'Yes') { ?>
                                                <span class="badge badge-success"><i class="fa fa-unlock"></i> Authorized</span>
                                            <?php } else { ?>
                                                <span class="badge badge-danger"><i class="fa fa-lock"></i> Suspended</span>
                                            <?php } ?>
                                        </td>
                                        <td align="center">
                                            <a href="index.php?page=<?=$page?>&part=2&rec=<?=$row['logid']?>" title="Go to edit"><i class="fa fa-lg fa-pencil text-primary"></i></a>
                                        </td>
                                        <td align="center">
                                            <input name="del[]" id="del[]" type="checkbox" value="<?=$row['logid']?>" />
                                        </td>
                                    </tr>
                                <?php } 
                            } else { ?>
                                <tr>
                                    <td colspan="10" class="text-center" style="padding: 30px; color: #73879C; font-size: 14px;">
                                        <i class="fa fa-user-times fa-lg"></i> User accounts directory index is currently tracking empty logs. Click <a href="index.php?page=<?=$page?>&part=1" class="alert-link"><u>Register New Operator Account</u></a> to initiate logs.
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="txtcount" id="txtcount" value="<?php echo $mycount;?>"> 
                </div>
            </div>
        </div>
        <?php } ?>

        </form>
    </div>
</div>
<style>
/* ---------- Panel ---------- */
form[name='0_users'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='0_users'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='0_users'] .x_title h2 {
    font-size: 1.15rem;
    font-weight: 600;
    color: #1a73e8;
    margin: 0;
}
form[name='0_users'] .x_title h2 small {
    margin-left: 10px;
    font-size: .75rem;
    font-weight: 400;
    color: #8a94a6;
    text-transform: uppercase;
    letter-spacing: .05em;
}
form[name='0_users'] .x_content {
    padding: 24px 28px;
}

/* ---------- Labels ---------- */
form[name='0_users'] .item.form-group {
    margin-bottom: 18px;
}
form[name='0_users'] .col-form-label {
    font-weight: 600;
    font-size: .85rem;
    color: #1a73e8 !important;
    text-align: center;
    padding-right: 0;
}
.manda { color: #e74c3c; font-weight: bold; }

/* ---------- Inputs, selects ---------- */
form[name='0_users'] .form-control {
    border: 1px solid #cfd7e3;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: .9rem;
    color: #1a73e8;
    text-align: center;
    text-align-last: center;          /* centres the selected option in dropdowns */
    background-color: #fff;
    transition: border-color .15s, box-shadow .15s, background-color .15s;
}
form[name='0_users'] .form-control::placeholder {
    color: #9bbcf0;
    font-size: .85rem;
}
form[name='0_users'] .form-control:hover:not(:disabled) {
    border-color: #9fb3cf;
}
form[name='0_users'] .form-control:focus {
    border-color: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .18);
    background-color: #fcfffe;
    outline: none;
}
form[name='0_users'] .form-control:required {
    border-left: 4px solid #e74c3c;
}
form[name='0_users'] .form-control:required:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, .15);
}
form[name='0_users'] .form-control:disabled {
    background: #f1f4f8;
    color: #8a94a6;
    cursor: not-allowed;
}
form[name='0_users'] .text-danger {
    display: block;
    margin-top: 4px;
    text-align: center;
    font-size: .75rem;
}

/* ---------- File upload (profile picture) ---------- */
form[name='0_users'] .form-control-file {
    width: 100%;
    padding: 6px;
    border: 1px dashed #9bbcf0;
    border-radius: 6px;
    background: #f7faff;
    color: #1a73e8;
    font-size: .85rem;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
form[name='0_users'] .form-control-file:hover {
    border-color: #26b99a;
    background: #f2fbf8;
}
form[name='0_users'] .form-control-file::file-selector-button {
    margin-right: 12px;
    padding: 5px 14px;
    border: 0;
    border-radius: 5px;
    background: #1a73e8;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
form[name='0_users'] .form-control-file::file-selector-button:hover {
    background: #1558b8;
}
form[name='0_users'] .mt-2 { text-align: center; }

/* ---------- Divider & buttons ---------- */
form[name='0_users'] .ln_solid {
    border-top: 1px solid #e3e8ee;
    margin: 24px 0 18px;
}
form[name='0_users'] .btn {
    border-radius: 6px;
    padding: .45em 1.4em;
    font-weight: 600;
    letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='0_users'] .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, .15);
}
form[name='0_users'] .btn-success { background: #26b99a; border-color: #26b99a; }
form[name='0_users'] .btn-success:hover { background: #1f9c82; box-shadow: 0 4px 10px rgba(38, 185, 154, .35); }

/* ---------- Users table ---------- */
.custom-table-wrapper {
    width: 100% !important;
    overflow-x: auto !important;
    margin-top: 15px;
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 8px;
    padding: 10px;
}
.custom-table-wrapper table {
    width: 100% !important;
    max-width: none !important;
    white-space: nowrap !important;
}
.custom-table-wrapper th,
.custom-table-wrapper td {
    padding: 10px 14px !important;
    vertical-align: middle !important;
    font-size: 13px !important;
}
.custom-table-wrapper thead th,
.custom-table-wrapper tfoot th {
    background-color: #f7f9fc !important;
    color: #1a73e8 !important;
    font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
.custom-table-wrapper tbody tr:hover { background-color: rgba(38, 185, 154, .07) !important; }
.custom-table-wrapper .badge { padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; }
.custom-table-wrapper .badge-success { background: #26b99a; color: #fff; }
.custom-table-wrapper .badge-danger  { background: #e74c3c; color: #fff; }
.custom-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }

.user-avatar-preview {
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #26b99a;
}

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='0_users'] .col-form-label { padding-bottom: 4px; }
    form[name='0_users'] .x_content { padding: 18px 14px; }
}
</style>
<?php } ?>