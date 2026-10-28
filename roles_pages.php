<?php
/*
 * roles_pages.php - Registers the pages that access rights can be given to
 * (list / add / edit / delete). Same design and routing as roles.php:
 *   index.php?page=roles_pages            list
 *   index.php?page=roles_pages&part=1     create
 *   index.php?page=roles_pages&part=2&rec=5   edit
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
$page      = "roles_pages";
$username  = $_SESSION['userusername'] ?? ($_SESSION['username'] ?? 'System');
$alert     = '';
$error     = '';

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */
if (!function_exists('rp_route')) {
    function rp_route($page, $query = '') { return 'index.php?page=' . $page . ($query !== '' ? '&' . $query : ''); }
}
if (!function_exists('rp_e')) {
    function rp_e($v) { return htmlspecialchars((string)($v === null ? '' : $v), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('rp_notify')) {
    function rp_notify($type, $title, $text) {
        $f = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        echo "<script>window.addEventListener('load', function () {
            if (typeof PNotify !== 'undefined') {
                new PNotify({ title: " . json_encode($title, $f) . ", text: " . json_encode($text, $f) . ", nonblock: { nonblock: !0 }, type: " . json_encode($type, $f) . ", styling: 'bootstrap3' });
            }
        }); </script>";
        $bg = ($type === 'success') ? '#e6f7f2' : '#fdecea';
        $fg = ($type === 'success') ? '#1f7a63' : '#c0392b';
        echo "<div style='margin:10px 15px; padding:10px 16px; border-radius:6px; background:$bg; color:$fg; font-weight:600;'>" . rp_e($title . ': ' . $text) . "</div>";
    }
}
if (!function_exists('rp_can')) {
    // Does the logged-in role hold roles_pages_<action>?  (used to hide buttons the user cannot use)
    function rp_can($action) {
        $list = array_map('trim', explode(',', (string)($_SESSION['allaccess'] ?? '')));
        return in_array('roles_pages_' . $action, $list, true);
    }
}
if (!function_exists('rp_rows')) {
    function rp_rows($conn, $sql, $types = '', $params = array()) {
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
if (!function_exists('rp_exec')) {
    function rp_exec($conn, $sql, $types, $params) {
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

// Pages of the system that should be registered, with the group each one belongs to
$known_pages = array(
    'Main Menu'     => array('member', 'prescription', 'prescription_details', 'stock_transaction', 'mpesa', 'sms'),
    'Data Analysis' => array('member_statement', 'group_statement', 'group_statement_detailed', 'defaulter_list', 'userlist', 'audittrail'),
    'Settings'      => array('settings', 'logistics_party', 'conditions', 'medicine', 'unit_of_measure', 'smssettings', 'county', 'subcounty', 'provider', 'roles', 'roles_pages', 'roles_access', '0_users'),
);

$listUrl      = rp_route($page);
$newUrl       = rp_route($page, 'part=1');
$dashboardUrl = 'index.php';

/* ------------------------------------------------------------------ *
 * Access control
 * ------------------------------------------------------------------ */
if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['delete']))           { caniacess($page . "_delete"); }
    if (isset($_POST['register_missing'])) { caniacess($page . "_add"); }
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

    /* -------------------------- SAVE -------------------------- */
    if (isset($_POST['save'])) {
        $groups   = trim((string)($_POST['groups'] ?? ''));
        $filename = trim((string)($_POST['filename'] ?? ''));

        if ($groups === '' || $filename === '') {
            $error = "Fill all mandatory fields in red.";
        } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $filename)) {
            $error = "Filename may contain only letters, numbers and underscores (no .php).";
        } elseif (rp_rows($conn, "SELECT logid FROM roles_pages WHERE filename=?", 's', array($filename))) {
            $error = "The page '$filename' is already registered.";
        } else {
            $res = rp_exec($conn, "INSERT INTO roles_pages (`groups`, filename, username, `timestamp`) VALUES (?,?,?,?)", 'ssss', array($groups, $filename, $username, $timestamp));
            if ($res !== false) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'roles_pages', '', 'inserting', $timestamp, $username); }
            } else {
                $error = "Could not save the page. Database said: " . ($conn ? $conn->error : 'no connection');
            }
        }
        if ($alert !== '') { rp_notify('success', 'Success', 'Page registered successfully!'); }
        if ($error !== '') { rp_notify('error', 'Error', $error); }
    }

    /* ------------------------- UPDATE ------------------------- */
    if (isset($_POST['update'])) {
        $groups   = trim((string)($_POST['groups'] ?? ''));
        $filename = trim((string)($_POST['filename'] ?? ''));
        $logid    = (int)($_POST['logid'] ?? 0);

        if ($groups === '' || $filename === '' || $logid <= 0) {
            $error = "Fill all mandatory fields in red.";
        } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $filename)) {
            $error = "Filename may contain only letters, numbers and underscores (no .php).";
        } elseif (rp_rows($conn, "SELECT logid FROM roles_pages WHERE filename=? AND logid<>?", 'si', array($filename, $logid))) {
            $error = "Another page is already registered as '$filename'.";
        } else {
            $res = rp_exec($conn, "UPDATE roles_pages SET `groups`=?, filename=?, username=?, `timestamp`=? WHERE logid=?", 'ssssi', array($groups, $filename, $username, $timestamp, $logid));
            if ($res !== false) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'roles_pages', $logid, 'updatings', $timestamp, $username); }
            } else {
                $error = "Could not update the page. Database said: " . ($conn ? $conn->error : 'no connection');
            }
        }
        if ($alert !== '') { rp_notify('success', 'Success', 'Page updated successfully!'); }
        if ($error !== '') { rp_notify('error', 'Error', $error); }
    }

    /* ------------------------- DELETE ------------------------- */
    if (isset($_POST['delete'])) {
        $deleted = 0;
        foreach ((array)($_POST['del'] ?? array()) as $id) {
            $id = (int)$id;
            if ($id <= 0) { continue; }
            if (function_exists('audittrail')) { audittrail($conn, 'roles_pages', $id, 'deleting', $timestamp, $username); }
            rp_exec($conn, "DELETE FROM roles_access WHERE pages=?", 's', array((string)$id));   // no orphaned rights
            $r = rp_exec($conn, "DELETE FROM roles_pages WHERE logid=?", 'i', array($id));
            if ($r !== false && $r > 0) { $deleted++; }
        }
        if ($deleted > 0) { rp_notify('success', 'Success', 'Deleted successfully'); }
        else              { rp_notify('error', 'Error', 'No record selected'); }
    }

    /* ------------------ REGISTER MISSING PAGES ------------------ */
    if (isset($_POST['register_missing'])) {
        $have = array();
        $rows = rp_rows($conn, "SELECT filename FROM roles_pages");
        foreach (($rows ?: array()) as $r) { $have[strtolower($r['filename'])] = true; }
        $added = 0;
        foreach ($known_pages as $grp => $files) {
            foreach ($files as $f) {
                if (isset($have[strtolower($f)]) || !file_exists(__DIR__ . '/' . $f . '.php')) { continue; }
                $r = rp_exec($conn, "INSERT INTO roles_pages (`groups`, filename, username, `timestamp`) VALUES (?,?,?,?)", 'ssss', array($grp, $f, $username, $timestamp));
                if ($r !== false) { $added++; }
            }
        }
        if ($added > 0) {
            if (function_exists('audittrail')) { audittrail($conn, 'roles_pages', '', 'inserting', $timestamp, $username); }
            rp_notify('success', 'Success', "$added page(s) registered. Now tick their rights under Settings > Access.");
        } else {
            rp_notify('error', 'Error', 'Nothing to register.');
        }
    }

    if (function_exists('_title')) { _title('System Pages'); }

    // Form values
    $f_groups = ($alert == '' && isset($_POST['groups']))   ? $_POST['groups']   : '';
    $f_file   = ($alert == '' && isset($_POST['filename'])) ? $_POST['filename'] : '';
    $group_names = array();
    if ($part == '1' || $part == '2') {
        foreach ((rp_rows($conn, "SELECT DISTINCT `groups` AS g FROM roles_pages ORDER BY `groups`") ?: array()) as $g) { $group_names[] = $g['g']; }
        foreach (array_keys($known_pages) as $g) { if (!in_array($g, $group_names, true)) { $group_names[] = $g; } }
    }
?>

<style>
/* ===== Pages (roles_pages) - same design as the Roles page (scoped) ===== */
.chg-form .x_panel { background:#fff; border:1px solid #eee; border-radius:6px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:25px; }
.chg-form .x_title { border-bottom:1px solid #eee; padding-bottom:12px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.chg-form .x_title h2 { font-size:14px; font-weight:400; color:#c98a3e; margin:0; float:none; }
.chg-form .x_title h2 small { margin-left:6px; font-size:12px; color:#95a5a6; text-transform:none; }
.chg-form .x_title .clearfix { display:none; }
.chg-form .panel_toolbox { margin:0; padding:0; list-style:none; float:none; display:flex; gap:8px; flex-wrap:wrap; }
.chg-form .panel_toolbox li { list-style:none; display:inline-block; }
.chg-form .btn-create { background:#3aa0e8; border:1px solid #3aa0e8; color:#fff !important; border-radius:20px; padding:8px 18px; font-size:13px; font-weight:600; text-decoration:none; display:inline-block; line-height:1.2; cursor:pointer; }
.chg-form .btn-create:hover { background:#2b8fd6; border-color:#2b8fd6; }
.chg-form .btn-create.alt { background:#26b99a; border-color:#26b99a; }
.chg-form .btn-create.alt:hover { background:#1f9e84; }
.chg-form .item.form-group { margin-bottom:18px; display:flex; align-items:center; }
.chg-form .col-form-label { font-weight:600; color:#34495e; font-size:14px; text-align:right; padding-right:15px; }
.chg-form .form-control { border:1px solid #ccc; border-radius:4px; padding:8px 12px; font-size:14px; color:#495057; background:#fff; width:100%; transition:all .2s; }
.chg-form .form-control:focus { border-color:#3aa0e8; outline:0; box-shadow:0 0 0 .2rem rgba(58,160,232,.15); }
.chg-form .hint { display:block; margin-top:4px; font-size:12px; color:#8a94a6; }
.chg-form .form-group.row.text-center { margin-top:25px; padding-top:20px; border-top:1px solid #eee; }
.chg-form .btn { font-weight:600; border-radius:4px; padding:7px 18px; color:#fff; }
.chg-form .btn-success { background:#26b99a; border-color:#26b99a; } .chg-form .btn-success:hover { background:#1f9e84; }
.chg-form .btn-primary { background:#3aa0e8; border-color:#3aa0e8; } .chg-form .btn-primary:hover { background:#2b8fd6; }
.chg-form .btn-secondary { background:#7f8c8d; border-color:#7f8c8d; } .chg-form .btn-secondary:hover { background:#6c7a89; }
.chg-form .btn-info { background:#34495e; border-color:#34495e; } .chg-form .btn-info:hover { background:#2c3e50; }
.chg-form table.table { width:100%; margin-bottom:10px; border-collapse:collapse; border:1px solid #e6e6e6; }
.chg-form table.table thead th, .chg-form table.table tfoot th { background:#f4f6f9; color:#3aa0e8; font-weight:600; font-size:12px; text-align:center; padding:10px 12px; border:1px solid #e6e6e6; white-space:nowrap; }
.chg-form table.table tbody td { padding:10px 12px; font-size:13px; color:#2c3e50; vertical-align:middle; border:1px solid #e6e6e6; }
.chg-form table.table tbody tr.grp td { background:#edf2f7; font-weight:700; color:#2d3748; }
.chg-form table.table-striped tbody tr:nth-of-type(odd):not(.grp) { background:rgba(0,0,0,.012); }
.chg-form table.table-striped tbody tr:hover:not(.grp) { background:rgba(58,160,232,.05); }
.chg-form .empty-row { text-align:center; font-style:italic; color:#888; padding:20px !important; font-size:12px; }
.chg-form .empty-row a { color:#3aa0e8; text-decoration:underline; }
.chg-form .fa-edit { color:#3aa0e8; font-size:16px; }
.chg-form table input[type="checkbox"] { cursor:pointer; width:16px; height:16px; accent-color:#e74c3c; }
.chg-form button[name="delete"] { background:#e74c3c; border:1px solid #e74c3c; color:#fff; padding:4px 10px; border-radius:4px; cursor:pointer; }
.chg-form button[name="delete"]:hover { background:#c0392b; }
.chg-form .badge-file { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:600; color:#fff; }
.chg-form .badge-file.ok { background:#26b99a; } .chg-form .badge-file.no { background:#e74c3c; }
.chg-form .notice { background:#fff7e6; border:1px solid #ffd591; color:#8a5a00; border-radius:6px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; font-size:13px; }
</style>

<form method="POST" action="" class="rounded chg-form" name="roles_pages" style="min-width:70%" enctype="multipart/form-data">

<?php if ($part == '1') { /* ============ ADD ============ */ ?>
    <div class="col-md-8 offset-2">
        <div class="x_panel">
            <div class="x_title">
                <h2>&raquo; System Pages<small>new entry</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="btn-create" href="<?= $listUrl ?>"><i class="fa fa-list"></i>&nbsp; View Records</a></li>
                </ul>
            </div>
            <div class="x_content center-block">
                <div class="item form-group">
                    <label for="groups" class="col-sm-3 col-form-label">Group :</label>
                    <div class="col-sm-5">
                        <input type="text" required class="form-control" name="groups" id="groups" list="grouplist" placeholder="e.g. Main Menu" value="<?= rp_e($f_groups) ?>" autocomplete="off">
                        <datalist id="grouplist"><?php foreach ($group_names as $g) { echo '<option value="' . rp_e($g) . '">'; } ?></datalist>
                        <span class="hint">The heading it appears under on the Access page.</span>
                    </div>
                </div>
                <div class="item form-group">
                    <label for="filename" class="col-sm-3 col-form-label">Page name :</label>
                    <div class="col-sm-5">
                        <input type="text" required class="form-control" name="filename" id="filename" placeholder="e.g. mpesa" value="<?= rp_e($f_file) ?>" autocomplete="off" pattern="[A-Za-z0-9_]+" title="Letters, numbers and underscores only">
                        <span class="hint">The file name without .php. It must match the page's address (index.php?page=<em>name</em>).</span>
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
                <h2>&raquo; System Pages<small>update entry</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="btn-create" href="<?= $listUrl ?>"><i class="fa fa-list"></i>&nbsp; View Records</a></li>
                </ul>
            </div>
            <div class="x_content center-block">
            <?php
            $found = false;
            foreach ((rp_rows($conn, "SELECT * FROM roles_pages WHERE logid=?", 'i', array((int)$rec)) ?: array()) as $row) { $found = true; ?>
                <div class="item form-group">
                    <label for="groups" class="col-sm-3 col-form-label">Group :</label>
                    <div class="col-sm-5">
                        <input type="text" required class="form-control" name="groups" id="groups" list="grouplist" value="<?= rp_e($row['groups'] ?? '') ?>" autocomplete="off">
                        <datalist id="grouplist"><?php foreach ($group_names as $g) { echo '<option value="' . rp_e($g) . '">'; } ?></datalist>
                    </div>
                </div>
                <div class="item form-group">
                    <label for="filename" class="col-sm-3 col-form-label">Page name :</label>
                    <div class="col-sm-5">
                        <input type="text" required class="form-control" name="filename" id="filename" value="<?= rp_e($row['filename'] ?? '') ?>" autocomplete="off" pattern="[A-Za-z0-9_]+" title="Letters, numbers and underscores only">
                        <span class="hint">Renaming a page changes what the menu and permission checks look for.</span>
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
                <input name="logid" type="hidden" value="<?= (int)$row['logid'] ?>" />
            <?php }
            if (!$found) { ?>
                <p class="empty-row">Page not found. <a href="<?= $listUrl ?>">Back to the list</a></p>
            <?php } ?>
            </div>
        </div>
    </div>

<?php } else { /* ============ LIST ============ */
    $registered = array();
    $all = rp_rows($conn, "SELECT * FROM roles_pages ORDER BY `groups`, filename");
    foreach (($all ?: array()) as $r) { $registered[strtolower($r['filename'])] = true; }

    // Pages that exist as files but are not registered yet
    $missing = array();
    foreach ($known_pages as $grp => $files) {
        foreach ($files as $f) { if (!isset($registered[strtolower($f)]) && file_exists(__DIR__ . '/' . $f . '.php')) { $missing[] = $f; } }
    }
    $canAdd = rp_can('add'); $canEdit = rp_can('edit'); $canDel = rp_can('delete');
?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>&raquo; System Pages<small>existing records</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <?php if ($canAdd) { ?><li><a class="btn-create" href="<?= $newUrl ?>"><i class="fa fa-plus"></i>&nbsp; Create New</a></li><?php } ?>
                </ul>
            </div>
            <div class="x_content">

                <?php if ($missing && $canAdd) { ?>
                <div class="notice">
                    <span><i class="fa fa-exclamation-triangle"></i> <strong><?= count($missing) ?></strong> page(s) exist in the system but are not registered, so nobody (not even the administrator) can be given rights to them:
                        <strong><?= rp_e(implode(', ', $missing)) ?></strong></span>
                    <button type="submit" name="register_missing" class="btn-create alt" onclick="return confirm('Register these pages now?');"><i class="fa fa-magic"></i>&nbsp; Register them</button>
                </div>
                <?php } ?>

                <table id="datatable" style="width:100%;" role="grid" class="table table-striped table-bordered dataTable table-sm">
                    <thead>
                        <tr>
                            <th>Group</th>
                            <th>Page name</th>
                            <th>Page file</th>
                            <th>Saved by</th>
                            <th>Timestamp</th>
                            <?php if ($canEdit) { ?><th width="1%">Edit</th><?php } ?>
                            <?php if ($canDel)  { ?><th width="1%">Del</th><?php } ?>
                        </tr>
                    </thead>
                    <?php if ($canDel) { ?>
                    <tfoot>
                        <tr>
                            <th colspan="<?= 5 + ($canEdit ? 1 : 0) ?>" style="text-align:right;">With selected items:</th>
                            <th><button name="delete" value="Delete" type="submit" onclick="return confirm('Delete the checked pages? Rights given to them will be removed too.');"><i class="fa fa-trash"></i></button></th>
                        </tr>
                    </tfoot>
                    <?php } ?>
                    <tbody>
                    <?php
                    $mycount = 0;
                    if ($all === false) { ?>
                        <tr><td colspan="7" class="empty-row" style="color:#c0392b;">Could not read the pages table: <?= rp_e($conn ? $conn->error : 'no connection') ?></td></tr>
                    <?php } else {
                        foreach ($all as $row) {
                            $mycount++;
                            $exists = file_exists(__DIR__ . '/' . $row['filename'] . '.php') || $row['filename'] === 'dashboard';
                            $editUrl = rp_route($page, 'part=2&rec=' . (int)$row['logid']); ?>
                        <tr>
                            <td><?= rp_e($row['groups'] ?? '') ?></td>
                            <td><strong><?= rp_e($row['filename'] ?? '') ?></strong></td>
                            <td align="center"><span class="badge-file <?= $exists ? 'ok' : 'no' ?>"><?= $exists ? 'Found' : 'Missing' ?></span></td>
                            <td><?= rp_e($row['username'] ?? '') ?></td>
                            <td><?= rp_e($row['timestamp'] ?? '') ?></td>
                            <?php if ($canEdit) { ?><td align="center"><a href="<?= $editUrl ?>" title="Go to edit"><i class="fa fa-edit"></i></a></td><?php } ?>
                            <?php if ($canDel)  { ?><td align="center"><input name="del[]" type="checkbox" value="<?= (int)$row['logid'] ?>" /></td><?php } ?>
                        </tr>
                    <?php }
                        if ($mycount == 0) { ?>
                        <tr><td colspan="7" class="empty-row">No pages registered. <?php if ($canAdd) { ?>Click <a href="<?= $newUrl ?>">Create New</a> to get started.<?php } ?></td></tr>
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