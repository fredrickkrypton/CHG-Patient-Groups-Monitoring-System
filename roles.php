<?php
/*
 * roles.php - Roles management (list / add / edit / delete)
 *   index.php?page=roles                 list
 *   index.php?page=roles&part=1          create
 *   index.php?page=roles&part=2&rec=5    edit
 */
if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

$conn = $conn ?? ($GLOBALS['conn'] ?? null);

// Encoded links (D=...) still work; plain part / rec in the URL win
if (function_exists('workout') && isset($_GET['D'])) { workout($_GET['D']); }
if (isset($_GET['part'])) { $part = trim((string)$_GET['part']); }
if (isset($_GET['rec']))  { $rec  = trim((string)$_GET['rec']); }
if (!isset($part)) { $part = ''; }
if (!isset($rec))  { $rec  = ''; }

$timestamp = date('Y-m-d H:i:s');
$page      = "roles";
$username  = $_SESSION['userusername'] ?? ($_SESSION['username'] ?? 'System');
$alert     = '';
$error     = '';

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */
if (!function_exists('rl_route')) {
    function rl_route($page, $query = '') { return 'index.php?page=' . $page . ($query !== '' ? '&' . $query : ''); }
}
if (!function_exists('rl_e')) {
    function rl_e($v) { return htmlspecialchars((string)($v === null ? '' : $v), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('rl_notify')) {
    function rl_notify($type, $title, $text) {
        $f = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        echo "<script>window.addEventListener('load', function () {
            if (typeof PNotify !== 'undefined') {
                new PNotify({ title: " . json_encode($title, $f) . ", text: " . json_encode($text, $f) . ", nonblock: { nonblock: !0 }, type: " . json_encode($type, $f) . ", styling: 'bootstrap3' });
            }
        }); </script>";
        $bg = ($type === 'success') ? '#e6f7f2' : '#fdecea';
        $fg = ($type === 'success') ? '#1f7a63' : '#c0392b';
        echo "<div style='margin:10px 15px; padding:10px 16px; border-radius:6px; background:$bg; color:$fg; font-weight:600;'>" . rl_e($title . ': ' . $text) . "</div>";
    }
}
if (!function_exists('rl_can')) {
    // Does the logged-in role hold roles_<action>?  (hides buttons the user cannot use)
    function rl_can($action) {
        $list = array_map('trim', explode(',', (string)($_SESSION['allaccess'] ?? '')));
        return in_array('roles_' . $action, $list, true);
    }
}
if (!function_exists('rl_rows')) {
    function rl_rows($conn, $sql, $types = '', $params = array()) {
        if (!$conn) { return false; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { return false; }
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!$stmt->execute()) { $stmt->close(); return false; }
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
        $stmt->close();
        return $rows;
    }
}
if (!function_exists('rl_exec')) {
    function rl_exec($conn, $sql, $types, $params) {
        if (!$conn) { return false; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { return false; }
        $stmt->bind_param($types, ...$params);
        $ok  = $stmt->execute();
        $aff = $stmt->affected_rows;
        $stmt->close();
        return $ok ? $aff : false;
    }
}

$listUrl      = rl_route($page);
$newUrl       = rl_route($page, 'part=1');
$dashboardUrl = 'index.php';

/* ------------------------------------------------------------------ *
 * Access control
 * ------------------------------------------------------------------ */
if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['delete'])) { caniacess($page . "_delete"); }
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

    /* -------------------------- SAVE -------------------------- */
    if (isset($_POST['save'])) {
        $rolename        = trim((string)($_POST['rolename'] ?? ''));
        $roledescription = trim((string)($_POST['roledescription'] ?? ''));

        if ($rolename === '') {
            $error = "Fill all mandatory fields in red.";
        } elseif (rl_rows($conn, "SELECT roleid FROM roles WHERE rolename=?", 's', array($rolename))) {
            $error = "A role named '$rolename' already exists.";
        } else {
            $res = rl_exec($conn, "INSERT INTO roles (rolename, roledescription, username, `timestamp`) VALUES (?,?,?,?)", 'ssss', array($rolename, $roledescription, $username, $timestamp));
            if ($res !== false) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'roles', '', 'inserting', $timestamp, $username); }
            } else {
                $error = "Could not save the role. Database said: " . ($conn ? $conn->error : 'no connection');
            }
        }
        if ($alert !== '') { rl_notify('success', 'Success', 'Role saved successfully. Now tick its pages under Settings > Access.'); }
        if ($error !== '') { rl_notify('error', 'Error', $error); }
    }

    /* ------------------------- UPDATE ------------------------- */
    if (isset($_POST['update'])) {
        $rolename        = trim((string)($_POST['rolename'] ?? ''));
        $roledescription = trim((string)($_POST['roledescription'] ?? ''));
        $logid           = (int)($_POST['logid'] ?? 0);

        if ($rolename === '' || $logid <= 0) {
            $error = "Fill all mandatory fields in red.";
        } elseif (rl_rows($conn, "SELECT roleid FROM roles WHERE rolename=? AND roleid<>?", 'si', array($rolename, $logid))) {
            $error = "Another role is already named '$rolename'.";
        } else {
            $res = rl_exec($conn, "UPDATE roles SET rolename=?, roledescription=?, username=?, `timestamp`=? WHERE roleid=?", 'ssssi', array($rolename, $roledescription, $username, $timestamp, $logid));
            if ($res !== false) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'roles', $logid, 'updatings', $timestamp, $username); }
            } else {
                $error = "Could not update the role. Database said: " . ($conn ? $conn->error : 'no connection');
            }
        }
        if ($alert !== '') { rl_notify('success', 'Success', 'Role updated successfully!'); }
        if ($error !== '') { rl_notify('error', 'Error', $error); }
    }

    /* ------------------------- DELETE ------------------------- */
    if (isset($_POST['delete'])) {
        $deleted = 0; $blocked = array();
        foreach ((array)($_POST['del'] ?? array()) as $id) {
            $id = (int)$id;
            if ($id <= 2) { continue; }                                   // system roles (1 and 2) are protected
            $used = rl_rows($conn, "SELECT COUNT(*) AS c FROM `0_users` WHERE role=?", 's', array((string)$id));
            if ($used && (int)$used[0]['c'] > 0) { $blocked[] = $id; continue; }   // do not orphan users
            if (function_exists('audittrail')) { audittrail($conn, 'roles', $id, 'deleting', $timestamp, $username); }
            rl_exec($conn, "DELETE FROM roles_access WHERE role=?", 's', array((string)$id));   // remove its rights too
            $r = rl_exec($conn, "DELETE FROM roles WHERE roleid=?", 'i', array($id));
            if ($r !== false && $r > 0) { $deleted++; }
        }
        if ($deleted > 0)  { rl_notify('success', 'Success', 'Deleted successfully'); }
        if ($blocked)      { rl_notify('error', 'Not deleted', 'Some roles still have users assigned. Move those users to another role first.'); }
        if ($deleted == 0 && !$blocked) { rl_notify('error', 'Error', 'No record selected'); }
    }

    if (function_exists('_title')) { _title('Roles'); }

    // Keep typed values on the add form unless the save succeeded
    $f_name = ($alert == '' && isset($_POST['rolename']))        ? $_POST['rolename']        : '';
    $f_desc = ($alert == '' && isset($_POST['roledescription'])) ? $_POST['roledescription'] : '';
?>

<style>
/* ===== Roles - same design as the other admin pages (scoped) ===== */
.chg-form .x_panel { background:#fff; border:1px solid #eee; border-radius:6px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:25px; }
.chg-form .x_title { border-bottom:1px solid #eee; padding-bottom:12px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.chg-form .x_title h2 { font-size:14px; font-weight:400; color:#c98a3e; margin:0; float:none; }
.chg-form .x_title h2 small { margin-left:6px; font-size:12px; color:#95a5a6; text-transform:none; }
.chg-form .x_title .clearfix { display:none; }
.chg-form .panel_toolbox { margin:0; padding:0; list-style:none; float:none; display:flex; gap:8px; flex-wrap:wrap; }
.chg-form .panel_toolbox li { list-style:none; display:inline-block; }
.chg-form .btn-create { background:#3aa0e8; border:1px solid #3aa0e8; color:#fff !important; border-radius:20px; padding:8px 18px; font-size:13px; font-weight:600; text-decoration:none; display:inline-block; line-height:1.2; cursor:pointer; }
.chg-form .btn-create:hover { background:#2b8fd6; border-color:#2b8fd6; }
.chg-form .item.form-group { margin-bottom:18px; display:flex; align-items:center; }
.chg-form .col-form-label { font-weight:600; color:#34495e; font-size:14px; text-align:right; padding-right:15px; }
.chg-form .form-control { border:1px solid #ccc; border-radius:4px; padding:8px 12px; font-size:14px; color:#495057; background:#fff; width:100%; transition:all .2s; }
.chg-form .form-control:focus { border-color:#3aa0e8; outline:0; box-shadow:0 0 0 .2rem rgba(58,160,232,.15); }
.chg-form .form-control:required { border-left:4px solid #e74c3c; }
.chg-form .form-group.row.text-center { margin-top:25px; padding-top:20px; border-top:1px solid #eee; }
.chg-form .btn { font-weight:600; border-radius:4px; padding:7px 18px; color:#fff; }
.chg-form .btn-success { background:#26b99a; border-color:#26b99a; } .chg-form .btn-success:hover { background:#1f9e84; }
.chg-form .btn-primary { background:#3aa0e8; border-color:#3aa0e8; } .chg-form .btn-primary:hover { background:#2b8fd6; }
.chg-form .btn-secondary { background:#7f8c8d; border-color:#7f8c8d; } .chg-form .btn-secondary:hover { background:#6c7a89; }
.chg-form .btn-info { background:#34495e; border-color:#34495e; } .chg-form .btn-info:hover { background:#2c3e50; }
.chg-form table.table { width:100%; margin-bottom:10px; border-collapse:collapse; border:1px solid #e6e6e6; }
.chg-form table.table thead th, .chg-form table.table tfoot th { background:#f4f6f9; color:#3aa0e8; font-weight:600; font-size:12px; text-align:center; padding:10px 12px; border:1px solid #e6e6e6; white-space:nowrap; }
.chg-form table.table tbody td { padding:10px 12px; font-size:13px; color:#2c3e50; vertical-align:middle; border:1px solid #e6e6e6; }
.chg-form table.table-striped tbody tr:nth-of-type(odd) { background:rgba(0,0,0,.012); }
.chg-form table.table-striped tbody tr:hover { background:rgba(58,160,232,.05); }
.chg-form .empty-row { text-align:center; font-style:italic; color:#888; padding:20px !important; font-size:12px; }
.chg-form .empty-row a { color:#3aa0e8; text-decoration:underline; }
.chg-form .fa-edit { color:#3aa0e8; font-size:16px; }
.chg-form table input[type="checkbox"] { cursor:pointer; width:16px; height:16px; accent-color:#e74c3c; }
.chg-form button[name="delete"] { background:#e74c3c; border:1px solid #e74c3c; color:#fff; padding:4px 10px; border-radius:4px; cursor:pointer; }
.chg-form button[name="delete"]:hover { background:#c0392b; }
.chg-form .pill { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:600; color:#fff; background:#26b99a; }
.chg-form .pill.lock { background:#95a5a6; }
</style>

<form method="POST" action="" class="rounded chg-form" name="roles" style="min-width:70%" enctype="multipart/form-data">

<?php if ($part == '1') { /* ============ ADD ============ */ ?>
    <div class="col-md-8 offset-2">
        <div class="x_panel">
            <div class="x_title">
                <h2>&raquo; Roles<small>new entry</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="btn-create" href="<?= $listUrl ?>"><i class="fa fa-list"></i>&nbsp; View Records</a></li>
                </ul>
            </div>
            <div class="x_content center-block">
                <div class="item form-group">
                    <label for="rolename" class="col-sm-3 col-form-label">Role name :</label>
                    <div class="col-sm-5">
                        <input type="text" required class="form-control" name="rolename" id="rolename" placeholder="e.g. Group Leader" value="<?= rl_e($f_name) ?>" autocomplete="off">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="roledescription" class="col-sm-3 col-form-label">Description :</label>
                    <div class="col-sm-5">
                        <textarea class="form-control" name="roledescription" id="roledescription" rows="3" placeholder="What this role is for"><?= rl_e($f_desc) ?></textarea>
                    </div>
                </div>

                <div class="ln_solid"></div>
                <div class="form-group row text-center">
                    <div class="col-md">
                        <button title="Save data" type="submit" name="save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
                        &nbsp; &nbsp;
                        <a href="<?= $newUrl ?>" title="Add new record"><button type="button" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button></a>
                        &nbsp; &nbsp;
                        <a href="<?= $listUrl ?>" title="Go to records"><button type="button" class="btn btn-secondary btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button></a>
                        &nbsp; &nbsp;
                        <a href="<?= $dashboardUrl ?>" title="Go to dashboard"><button type="button" class="btn btn-info btn-sm"><span>Dashboard</span> <i class="fa fa-angle-double-right custom"></i></button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php } elseif ($part == '2') { /* ============ EDIT ============ */ ?>
    <div class="col-md-8 offset-2">
        <div class="x_panel">
            <div class="x_title">
                <h2>&raquo; Roles<small>update entry</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="btn-create" href="<?= $listUrl ?>"><i class="fa fa-list"></i>&nbsp; View Records</a></li>
                </ul>
            </div>
            <div class="x_content center-block">
            <?php
            $found = false;
            foreach ((rl_rows($conn, "SELECT * FROM roles WHERE roleid=?", 'i', array((int)$rec)) ?: array()) as $row) { $found = true; ?>
                <div class="item form-group">
                    <label for="rolename" class="col-sm-3 col-form-label">Role name :</label>
                    <div class="col-sm-5">
                        <input type="text" required class="form-control" name="rolename" id="rolename" value="<?= rl_e($row['rolename'] ?? '') ?>" autocomplete="off">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="roledescription" class="col-sm-3 col-form-label">Description :</label>
                    <div class="col-sm-5">
                        <textarea class="form-control" name="roledescription" id="roledescription" rows="3"><?= rl_e($row['roledescription'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="ln_solid"></div>
                <div class="form-group row text-center">
                    <div class="col-md">
                        <button title="Update data" type="submit" name="update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
                        &nbsp; &nbsp;
                        <a href="<?= $newUrl ?>" title="Add new record"><button type="button" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button></a>
                        &nbsp; &nbsp;
                        <a href="<?= $listUrl ?>" title="Go to records"><button type="button" class="btn btn-secondary btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button></a>
                        &nbsp; &nbsp;
                        <a href="<?= $dashboardUrl ?>" title="Go to dashboard"><button type="button" class="btn btn-info btn-sm"><span>Dashboard</span> <i class="fa fa-angle-double-right custom"></i></button></a>
                    </div>
                </div>
                <input name="logid" type="hidden" value="<?= (int)$row['roleid'] ?>" />
            <?php }
            if (!$found) { ?>
                <p class="empty-row">Role not found. <a href="<?= $listUrl ?>">Back to the list</a></p>
            <?php } ?>
            </div>
        </div>
    </div>

<?php } else { /* ============ LIST ============ */
    $canAdd = rl_can('add'); $canEdit = rl_can('edit'); $canDel = rl_can('delete');

    $list = rl_rows($conn, "SELECT r.*, (SELECT COUNT(*) FROM `0_users` u WHERE u.role = r.roleid) AS users FROM roles r ORDER BY r.roleid");
    $has_users_col = ($list !== false);
    if ($list === false) { $list = rl_rows($conn, "SELECT * FROM roles ORDER BY roleid"); }   // fall back if the users table cannot be read
    $cols = 5 + ($has_users_col ? 1 : 0) + ($canEdit ? 1 : 0) + ($canDel ? 1 : 0);
?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>&raquo; Roles<small>existing records</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <?php if ($canAdd) { ?><li><a class="btn-create" href="<?= $newUrl ?>"><i class="fa fa-plus"></i>&nbsp; Create New</a></li><?php } ?>
                </ul>
            </div>
            <div class="x_content">
                <table id="datatable" style="width:100%;" role="grid" class="table table-striped table-bordered dataTable table-sm">
                    <thead>
                        <tr>
                            <th>Role name</th>
                            <th>Description</th>
                            <?php if ($has_users_col) { ?><th>Users</th><?php } ?>
                            <th>Saved by</th>
                            <th>Timestamp</th>
                            <?php if ($canEdit) { ?><th width="1%">Edit</th><?php } ?>
                            <?php if ($canDel)  { ?><th width="1%">Del</th><?php } ?>
                        </tr>
                    </thead>
                    <?php if ($canDel) { ?>
                    <tfoot>
                        <tr>
                            <th colspan="<?= $cols - 1 ?>" style="text-align:right;">With selected items:</th>
                            <th><button name="delete" value="Delete" type="submit" onclick="return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button></th>
                        </tr>
                    </tfoot>
                    <?php } ?>
                    <tbody>
                    <?php
                    $mycount = 0;
                    if ($list === false) { ?>
                        <tr><td colspan="<?= $cols ?>" class="empty-row" style="color:#c0392b;">Could not read the roles table: <?= rl_e($conn ? $conn->error : 'no connection') ?></td></tr>
                    <?php } else {
                        foreach ($list as $row) {
                            $mycount++;
                            $editUrl = rl_route($page, 'part=2&rec=' . (int)$row['roleid']); ?>
                        <tr>
                            <td><strong><?= rl_e($row['rolename'] ?? '') ?></strong><?php if ((int)$row['roleid'] <= 2) { ?> <span class="pill lock" title="System role: cannot be deleted">System</span><?php } ?></td>
                            <td><?= rl_e($row['roledescription'] ?? '') ?></td>
                            <?php if ($has_users_col) { ?><td align="center"><span class="pill"><?= (int)($row['users'] ?? 0) ?></span></td><?php } ?>
                            <td><?= rl_e($row['username'] ?? '') ?></td>
                            <td><?= rl_e($row['timestamp'] ?? '') ?></td>
                            <?php if ($canEdit) { ?><td align="center"><a href="<?= $editUrl ?>" title="Go to edit"><i class="fa fa-edit"></i></a></td><?php } ?>
                            <?php if ($canDel)  { ?><td align="center"><?php if ((int)$row['roleid'] > 2) { ?><input name="del[]" type="checkbox" value="<?= (int)$row['roleid'] ?>" /><?php } ?></td><?php } ?>
                        </tr>
                    <?php }
                        if ($mycount == 0) { ?>
                        <tr><td colspan="<?= $cols ?>" class="empty-row">No roles found. <?php if ($canAdd) { ?>Click <a href="<?= $newUrl ?>">Create New</a> to get started.<?php } ?></td></tr>
                    <?php }
                    } ?>
                    </tbody>
                </table>
                <input type="hidden" name="txtcount" id="txtcount" value="<?= $mycount ?>">
            </div>
        </div>
    </div>
<?php } ?>
</form>
<?php } ?>