<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-22 16:57:57
//
// Prescription details = the medicines on one prescription (?page=prescription_details&p=<prescription id>)

global $title;
$conn = $GLOBALS['conn'] ?? null;

$page = "prescription_details";

// ---------- Safe defaults ----------
$part      = isset($_GET['part']) ? trim((string)$_GET['part']) : '';
$rec       = isset($_GET['rec'])  ? trim((string)$_GET['rec'])  : '';
$pid       = (int)($_GET['p'] ?? 0);                     // parent prescription
$username  = $_SESSION['userusername'] ?? ($_SESSION['username'] ?? ($username ?? 'System'));
$deleted   = 0;
$alert     = '';
$error     = '';
$saved_ok  = false;
$timestamp = date('Y-m-d H:i:s');

// ---------- Routing: plain ?part=1&rec=5  or encoded ?D=<base64> ----------
if (!empty($_GET['D'])) {
    if (function_exists('workout')) {
        workout($_GET['D']);
    } else {
        parse_str((string)base64_decode(strtr($_GET['D'], '-_,', '+/=')), $__d);
        if (isset($__d['part'])) { $part = (string)$__d['part']; }
        if (isset($__d['rec']))  { $rec  = (string)$__d['rec']; }
    }
    $part = trim((string)$part);
}

if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['prescription_details_delete'])) { caniacess($page . "_delete"); }
}

// Role 4 (group leader) can only work with prescriptions of members in their own group
$is_leader = (($_SESSION['userrole'] ?? 0) == 4);
$own_group = $is_leader ? (int)($_SESSION['groupid'] ?? 0) : 0;

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */
if (!function_exists('pd_e')) {
    function pd_e($v) { return htmlspecialchars((string)($v === null ? '' : $v), ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('pd_notify')) {
    // PNotify toast plus an inline banner (same behaviour as the Members page)
    function pd_notify($type, $title, $text) {
        $f = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        echo "<script> window.addEventListener('load', function () {
            if (typeof PNotify !== 'undefined') {
                new PNotify({ title: " . json_encode($title, $f) . ", text: " . json_encode($text, $f) . ", nonblock: { nonblock: !0 }, type: " . json_encode($type, $f) . ", styling: 'bootstrap3' });
            }
        }) </script>";
        $bg = ($type === 'success') ? '#e6f7f2' : '#fdecea';
        $fg = ($type === 'success') ? '#1f7a63' : '#c0392b';
        echo "<div style='margin:10px 15px; padding:10px 16px; border-radius:6px; background:$bg; color:$fg; font-weight:600;'>"
           . htmlspecialchars($title . ': ' . $text) . "</div>";
    }
}

if (!function_exists('pd_fetch_all')) {
    function pd_fetch_all($conn, $sql, $types = '', $params = array()) {
        if (!$conn) { return array(); }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('prescription_details.php prepare failed: ' . $conn->error); return array(); }
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!$stmt->execute()) {
            error_log('prescription_details.php execute failed: ' . $stmt->error);
            $stmt->close();
            return array();
        }
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('pd_exec')) {
    // Returns affected rows, or false on failure (MySQL error number kept in $GLOBALS['pd_errno']).
    function pd_exec($conn, $sql, $types, $params) {
        $GLOBALS['pd_errno'] = 0;
        if (!$conn) { return false; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('prescription_details.php prepare failed: ' . $conn->error); return false; }
        $stmt->bind_param($types, ...$params);
        $ok       = $stmt->execute();
        $affected = $stmt->affected_rows;
        if (!$ok) {
            $GLOBALS['pd_errno'] = $stmt->errno;
            error_log('prescription_details.php execute failed: ' . $stmt->error);
        }
        $stmt->close();
        return $ok ? $affected : false;
    }
}

if (!function_exists('pd_dateval')) {
    function pd_dateval($v) {
        $v = (string)$v;
        if ($v === '' || strpos($v, '0000') === 0) { return ''; }
        return substr($v, 0, 10);
    }
}

if (!function_exists('pd_status')) {
    function pd_status($from, $to) {
        $today = date('Y-m-d');
        $from  = pd_dateval($from);
        $to    = pd_dateval($to);
        if ($to !== '' && $to < $today)     { return array('Expired',  'expired'); }
        if ($from !== '' && $from > $today) { return array('Upcoming', 'upcoming'); }
        return array('Active', 'active');
    }
}

// Validates the posted medicine + quantity. Returns [error, unit_of_measure_id taken from the medicine].
if (!function_exists('pd_check')) {
    function pd_check($conn, $molecule, $qty) {
        if ($molecule === '') { return array('Select the medicine.', ''); }
        if ($qty === '' || !is_numeric($qty) || (float)$qty <= 0) { return array('Enter a quantity greater than zero.', ''); }
        $m = pd_fetch_all($conn, "SELECT logid, unit_of_measure_id FROM medicine WHERE logid=?", 's', array($molecule));
        if (!$m) { return array('The selected medicine was not found.', ''); }
        return array('', (string)$m[0]['unit_of_measure_id']);
    }
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// PARENT PRESCRIPTION (everything on this page belongs to it)
// ---------------------------------------------------------------------------
$parent = null;
if ($pid > 0) {
    $sql = "SELECT p.*, m.name AS member_name, m.mnumber AS member_number
              FROM prescription p
              LEFT JOIN `member` m ON m.logid = p.member_id
             WHERE p.logid=?" . ($is_leader ? " AND m.group_id=?" : "");
    $found  = $is_leader ? pd_fetch_all($conn, $sql, 'ss', array($pid, (string)$own_group)) : pd_fetch_all($conn, $sql, 's', array($pid));
    $parent = $found ? $found[0] : null;
}

$ref_label = '';
if ($parent) {
    $ref       = trim((string)($parent['reference'] ?? ''));
    $ref_label = ($ref !== '') ? $ref : 'Prescription #' . $pid;
}

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if ($parent && isset($_POST['prescription_details_save'])) {
    $molecule = trim((string)($_POST['molecule_type'] ?? ''));
    $qty      = trim((string)($_POST['quantity'] ?? ''));
    list($error, $unit_id) = pd_check($conn, $molecule, $qty);

    if ($error === '') {
        $res = pd_exec(
            $conn,
            "INSERT INTO prescription_details (prescription_id, molecule_type, unit_of_measure_id, quantity, created_by, created_on) VALUES (?,?,?,?,?,?)",
            'ssssss',
            array($pid, $molecule, $unit_id, $qty, $username, $timestamp)
        );
        if ($res !== false) {
            $alert = 1; $saved_ok = true;
            if (function_exists('audittrail')) { audittrail($conn, 'prescription_details', $conn->insert_id, 'inserting', $timestamp, $username); }
        } else {
            $error = ($GLOBALS['pd_errno'] == 1062)
                ? 'This medicine is already on the prescription. Edit its quantity instead.'
                : 'The medicine could not be saved. Please try again.';
        }
    }
    if ($alert !== '') { pd_notify('success', 'Success', 'Medicine saved successfully!'); }
    if ($error !== '') { pd_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if ($parent && isset($_POST['prescription_details_update'])) {
    $logid    = (int)($_POST['logid'] ?? 0);
    $molecule = trim((string)($_POST['molecule_type'] ?? ''));
    $qty      = trim((string)($_POST['quantity'] ?? ''));
    list($error, $unit_id) = pd_check($conn, $molecule, $qty);

    if ($error === '') {
        $owned = ($logid > 0) ? pd_fetch_all($conn, "SELECT logid FROM prescription_details WHERE logid=? AND prescription_id=?", 'ss', array($logid, $pid)) : array();
        if (!$owned) { $error = 'Medicine entry not found.'; }
    }

    if ($error === '') {
        $res = pd_exec(
            $conn,
            "UPDATE prescription_details SET molecule_type=?, unit_of_measure_id=?, quantity=? WHERE logid=? AND prescription_id=?",
            'sssss',
            array($molecule, $unit_id, $qty, $logid, $pid)
        );
        if ($res !== false) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, 'prescription_details', $logid, 'updatings', $timestamp, $username); }
        } else {
            $error = ($GLOBALS['pd_errno'] == 1062)
                ? 'This medicine is already on the prescription. Edit that entry instead.'
                : 'The medicine could not be updated. Please try again.';
        }
    }
    if ($alert !== '') { pd_notify('success', 'Success', 'Medicine updated successfully!'); $part = ''; }
    if ($error !== '') { pd_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if ($parent && isset($_POST['prescription_details_delete'])) {
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['del'] ?? array()))));
    foreach ($ids as $del_id) {
        $res = pd_exec($conn, "DELETE FROM prescription_details WHERE logid=? AND prescription_id=?", 'ss', array($del_id, $pid));
        if ($res !== false && $res > 0) {
            $deleted++;
            if (function_exists('audittrail')) { audittrail($conn, 'prescription_details', $del_id, 'deleting', $timestamp, $username); }
        }
    }
    if ($deleted > 0) { pd_notify('success', 'Success', 'Deleted successfully'); }
    else              { pd_notify('error', 'Error', 'No record selected'); }
}

if (function_exists('_title')) { _title($title = 'Prescription Details'); } else { $title = 'Prescription Details'; }

// ---------------------------------------------------------------------------
// FORM VALUES (add / edit)
// ---------------------------------------------------------------------------
$v = ['logid' => '', 'molecule_type' => '', 'quantity' => ''];
$edit_found = false;
$medicines  = [];

if ($parent && ($part == '1' || $part == '2')) {
    if ($part == '1' && isset($_POST['prescription_details_save']) && !$saved_ok) {   // keep what was typed after an error
        $v['molecule_type'] = $_POST['molecule_type'] ?? '';
        $v['quantity']      = $_POST['quantity'] ?? '';
    }
    if ($part == '2') {
        $found_rows = pd_fetch_all($conn, "SELECT * FROM prescription_details WHERE logid=? AND prescription_id=?", 'ss', array((int)$rec, $pid));
        if ($found_rows) {
            $edit_found = true;
            $row = $found_rows[0];
            $v['logid']         = $row['logid'];
            $v['molecule_type'] = $row['molecule_type'];
            $v['quantity']      = $row['quantity'];
            if (isset($_POST['prescription_details_update'])) {
                $v['molecule_type'] = $_POST['molecule_type'] ?? $v['molecule_type'];
                $v['quantity']      = $_POST['quantity'] ?? $v['quantity'];
            }
        }
    }
    $medicines = pd_fetch_all($conn, "SELECT md.logid, md.name, md.unit_of_measure_id, u.name AS unit_name
                                        FROM medicine md
                                        LEFT JOIN unit_of_measure u ON u.logid = md.unit_of_measure_id
                                       ORDER BY md.name");
}

// ---------------------------------------------------------------------------
// LIST DATA
// ---------------------------------------------------------------------------
$rows = []; $mycount = 0;
if ($parent && $part != '1' && $part != '2') {
    $rows = pd_fetch_all(
        $conn,
        "SELECT d.*, md.name AS medicine_name, u.name AS unit_name
           FROM prescription_details d
           LEFT JOIN medicine md        ON md.logid = d.molecule_type
           LEFT JOIN unit_of_measure u  ON u.logid  = d.unit_of_measure_id
          WHERE d.prescription_id=?
          ORDER BY d.logid",
        's',
        array($pid)
    );
    $mycount = count($rows);
}

$link_list = "index.php?page=" . $page . "&amp;p=" . $pid;
$link_new  = "index.php?page=" . $page . "&amp;part=1&amp;p=" . $pid;
$link_back = "index.php?page=prescription";
?>

<style>
/* ---------- Panel ---------- */
form[name='prescription_details'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='prescription_details'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='prescription_details'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='prescription_details'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='prescription_details'] .x_content { padding: 24px 28px; }
form[name='prescription_details'] .x_title { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
form[name='prescription_details'] .x_title .clearfix { display: none; }
form[name='prescription_details'] .panel_toolbox {
    list-style: none; margin: 0 0 0 auto; padding: 0; float: none;
    display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 8px;
}

/* ---------- Prescription summary cards ---------- */
form[name='prescription_details'] .gs-summary { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 4px; }
form[name='prescription_details'] .gs-card {
    flex: 1 1 190px; background: #fff; border: 1px solid #e3e8ee;
    border-left: 4px solid #1a73e8; border-radius: 8px; padding: 12px 16px;
}
form[name='prescription_details'] .gs-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
form[name='prescription_details'] .gs-card .val { font-size: 1.05rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }
form[name='prescription_details'] .gs-card .sub { font-size: .8rem; color: #8a94a6; }

/* ---------- Form grid ---------- */
form[name='prescription_details'] .mb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px 28px;
}
form[name='prescription_details'] .mb-field label {
    display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='prescription_details'] .mb-field label .req { color: #e74c3c; }
form[name='prescription_details'] .form-control {
    width: 100%; height: 38px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='prescription_details'] .form-control::placeholder { color: #9bbcf0; }
form[name='prescription_details'] .form-control:focus {
    border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38, 185, 154, .18); outline: none;
}
form[name='prescription_details'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='prescription_details'] .form-control:required:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231, 76, 60, .15); }
form[name='prescription_details'] .form-control[readonly] { background: #f7f9fc; color: #8a94a6; cursor: default; }
form[name='prescription_details'] .mb-hint { display: block; margin-top: 4px; font-size: .75rem; color: #8a94a6; }
form[name='prescription_details'] .mb-actions { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e3e8ee; display: flex; gap: 10px; flex-wrap: wrap; }

/* ---------- Buttons ---------- */
form[name='prescription_details'] .btn {
    border-radius: 6px; padding: .45em 1.4em; font-weight: 600; letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='prescription_details'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='prescription_details'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='prescription_details'] .btn-success:hover { background: #1f9c82; }
form[name='prescription_details'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='prescription_details'] .btn-primary:hover { background: #1558b8; }
form[name='prescription_details'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='prescription_details'] .btn-secondary:hover { background: #6f7a8d; }
form[name='prescription_details'] .btn-danger    { background: #e74c3c; border-color: #e74c3c; color: #fff; }
form[name='prescription_details'] .btn-danger:hover { background: #c0392b; }
form[name='prescription_details'] .btn:focus-visible,
form[name='prescription_details'] a:focus-visible { outline: 2px solid #1a73e8; outline-offset: 2px; }

/* ---------- Table ---------- */
form[name='prescription_details'] .mb-table-wrapper {
    width: 100%; overflow-x: auto; background: #fff;
    border: 1px solid #e3e8ee; border-radius: 8px; padding: 10px;
}
form[name='prescription_details'] .mb-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
form[name='prescription_details'] .mb-table-wrapper th,
form[name='prescription_details'] .mb-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
form[name='prescription_details'] .mb-table-wrapper thead th,
form[name='prescription_details'] .mb-table-wrapper tfoot th,
form[name='prescription_details'] .mb-table-wrapper tfoot td {
    background: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
form[name='prescription_details'] .mb-table-wrapper tfoot tr.gs-total td { background: #2a3f54 !important; color: #fff !important; border-bottom: 0 !important; }
form[name='prescription_details'] .mb-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
form[name='prescription_details'] .mb-table-wrapper a { color: #1a73e8; font-weight: 600; }
form[name='prescription_details'] .mb-table-wrapper .fa-pencil:hover { color: #1f9c82 !important; }
form[name='prescription_details'] .mb-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
form[name='prescription_details'] .mb-table-wrapper .num { text-align: right; }
form[name='prescription_details'] .mb-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; }
form[name='prescription_details'] .mb-badge.active   { background: #26b99a; }
form[name='prescription_details'] .mb-badge.upcoming { background: #1a73e8; }
form[name='prescription_details'] .mb-badge.expired  { background: #e74c3c; }
form[name='prescription_details'] .mb-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='prescription_details'] .x_content { padding: 16px 14px; }
    form[name='prescription_details'] .gs-card { flex: 1 1 100%; }
}
@media (prefers-reduced-motion: reduce) {
    form[name='prescription_details'] .btn, form[name='prescription_details'] .form-control { transition: none; }
    form[name='prescription_details'] .btn:hover { transform: none; }
}

/* ---------- Print ---------- */
@media print {
    form[name='prescription_details'] .panel_toolbox,
    form[name='prescription_details'] .gs-skip,
    form[name='prescription_details'] tr.no-print { display: none !important; }
    form[name='prescription_details'] .x_panel { box-shadow: none; border: 0; }
    form[name='prescription_details'] .mb-table-wrapper { overflow: visible; border: 0; }
}
</style>

<form method="POST" action="" class="rounded" name="prescription_details" style="min-width:100%" enctype="multipart/form-data">
<div class="col-md-12 col-sm-12 col-xs-12">

<?php if (!$parent) { ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= pd_e($title) ?><small> prescription</small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <div class="mb-error">Prescription not found, or you do not have access to it.</div>
      <div class="mb-actions"><a href="<?= $link_back ?>" class="btn btn-secondary btn-sm"><i class="fa fa-reply"></i> Back to prescriptions</a></div>
    </div>
  </div>

<?php } else {
    $pst = pd_status($parent['valid_from'] ?? '', $parent['valid_to'] ?? ''); ?>

  <div class="gs-summary">
    <div class="gs-card">
      <div class="lbl">Patient</div>
      <div class="val"><?= (($parent['member_name'] ?? '') !== '') ? pd_e($parent['member_name']) : 'Unknown Member' ?></div>
      <div class="sub"><?= pd_e($parent['member_number'] ?? '') ?></div>
    </div>
    <div class="gs-card">
      <div class="lbl">Dispensed on</div>
      <div class="val"><?= pd_e(pd_dateval($parent['dispensation_dt'] ?? '') ?: '-') ?></div>
    </div>
    <div class="gs-card">
      <div class="lbl">Valid</div>
      <div class="val"><?= pd_e(pd_dateval($parent['valid_from'] ?? '') ?: '-') ?> to <?= pd_e(pd_dateval($parent['valid_to'] ?? '') ?: '-') ?></div>
    </div>
    <div class="gs-card">
      <div class="lbl">Status</div>
      <div class="val"><span class="mb-badge <?= $pst[1] ?>"><?= $pst[0] ?></span></div>
    </div>
  </div>

<?php if ($part == '1' || $part == '2') {
    $is_edit = ($part == '2'); ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= pd_e($title) ?><small> <?= pd_e($ref_label) ?> &ndash; <?= $is_edit ? 'update medicine' : 'add medicine' ?></small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
    <?php if ($is_edit && !$edit_found) { ?>
      <div class="mb-error">Medicine entry not found. It may have been deleted.</div>
      <div class="mb-actions"><a href="<?= $link_list ?>" class="btn btn-secondary btn-sm">Back</a></div>
    <?php } else {
        $unit_now = '';
        foreach ($medicines as $md) { if ((string)$md['logid'] === (string)$v['molecule_type']) { $unit_now = (string)($md['unit_name'] ?? ''); break; } } ?>

      <div class="mb-grid">
        <div class="mb-field">
          <label for="molecule_type">Medicine <span class="req">*</span></label>
          <select class="form-control" id="molecule_type" name="molecule_type" required>
            <option value="">Select Medicine</option>
            <?php foreach ($medicines as $md) { ?>
            <option value="<?= pd_e($md['logid']) ?>" data-unit="<?= pd_e($md['unit_name'] ?? '') ?>" <?= ((string)$md['logid'] === (string)$v['molecule_type']) ? 'selected' : '' ?>><?= pd_e($md['name']) ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="unit_name">Unit of measure</label>
          <input type="text" readonly class="form-control" id="unit_name" value="<?= pd_e($unit_now) ?>" placeholder="Set by the medicine" tabindex="-1">
          <span class="mb-hint">Comes from the selected medicine.</span>
        </div>

        <div class="mb-field">
          <label for="quantity">Quantity <span class="req">*</span></label>
          <input type="number" required min="0" step="any" class="form-control" name="quantity" id="quantity" placeholder="Quantity" value="<?= pd_e($v['quantity']) ?>" autocomplete="off">
        </div>
      </div>

      <div class="mb-actions">
        <?php if ($is_edit) { ?>
        <button type="submit" name="prescription_details_update" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Update</button>
        <input type="hidden" name="logid" value="<?= (int)$v['logid'] ?>">
        <?php } else { ?>
        <button type="submit" name="prescription_details_save" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Save</button>
        <a href="<?= $link_new ?>" class="btn btn-primary btn-sm"><i class="fa fa-file"></i> New</a>
        <?php } ?>
        <a href="<?= $link_list ?>" class="btn btn-secondary btn-sm"><i class="fa fa-search"></i> View</a>
        <a href="<?= $link_back ?>" class="btn btn-secondary btn-sm"><i class="fa fa-reply"></i> Back to prescriptions</a>
      </div>

    <?php } ?>
    </div>
  </div>

<?php } else { ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= pd_e($title) ?><small> <?= pd_e($ref_label) ?></small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="<?= $link_new ?>" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Add Medicine</a></li>
        <li><button type="button" class="btn btn-sm btn-secondary" onclick="window.print();"><i class="fa fa-print"></i> Print</button></li>
        <li><a href="<?= $link_back ?>" class="btn btn-sm btn-secondary text-white"><i class="fa fa-reply"></i> Back</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="mb-table-wrapper">
        <table id="datatable" role="grid" class="table table-striped table-bordered table-sm dataTable">
          <thead>
            <tr>
              <th width="1px">#</th>
              <th>Medicine</th>
              <th>Unit Of Measure</th>
              <th class="num">Quantity</th>
              <th>Created By</th>
              <th>Created On</th>
              <th class="gs-skip" width="1%">Edit</th>
              <th class="gs-skip" width="1%">Del</th>
            </tr>
          </thead>
          <tbody>
          <?php $n = 0; foreach ($rows as $row) { $n++; ?>
            <tr>
              <td><?= $n ?></td>
              <td><strong><?= (($row['medicine_name'] ?? '') !== '') ? pd_e($row['medicine_name']) : 'Unknown medicine' ?></strong></td>
              <td><?= pd_e($row['unit_name'] ?? '') ?></td>
              <td class="num"><?= pd_e($row['quantity'] ?? '') ?></td>
              <td><?= pd_e($row['created_by'] ?? '') ?></td>
              <td><?= pd_e($row['created_on'] ?? '') ?></td>
              <td class="gs-skip" align="center"><a href="index.php?page=<?= $page ?>&amp;part=2&amp;rec=<?= urlencode($row['logid']) ?>&amp;p=<?= $pid ?>" title="Go to edit" aria-label="Edit medicine"><i class="fa fa-lg fa-pencil"></i></a></td>
              <td class="gs-skip" align="center"><input name="del[]" type="checkbox" value="<?= pd_e($row['logid']) ?>" aria-label="Select medicine <?= $n ?>"></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr>
              <td colspan="8" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <i class="fa fa-info-circle fa-lg"></i> No medicines added yet. Click <a href="<?= $link_new ?>"><u>Add Medicine</u></a> to get started.
              </td>
            </tr>
          <?php } ?>
          </tbody>
          <tfoot>
            <tr class="gs-total">
              <td colspan="3" class="num">Total medicines</td>
              <td colspan="5"><?= (int)$mycount ?></td>
            </tr>
            <tr class="no-print">
              <td colspan="7" style="text-align:right;">With selected items:</td>
              <td><button name="prescription_details_delete" value="Delete" type="submit" class="btn btn-danger btn-sm" title="Delete selected" onclick="return confirm('Please confirm deletion of the checked medicines');"><i class="fa fa-trash"></i></button></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
<?php } ?>

<?php } ?>

</div>
</form>

<script>
(function () {
    // Show the unit of measure of the chosen medicine
    var med = document.getElementById('molecule_type'), unit = document.getElementById('unit_name');
    if (med && unit) {
        var fill = function () {
            var opt = med.options[med.selectedIndex];
            unit.value = (opt && opt.value) ? (opt.getAttribute('data-unit') || '-') : '';
        };
        med.addEventListener('change', fill);
        fill();
    }
})();
</script>
<?php } ?>