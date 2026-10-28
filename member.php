<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-03-22 16:00:23

global $title;
$conn = $GLOBALS['conn'] ?? null;

$page = "member";

// ---------- Safe defaults ----------
$part      = isset($_GET['part']) ? trim((string)$_GET['part']) : '';
$rec       = isset($_GET['rec'])  ? trim((string)$_GET['rec'])  : '';
$username  = $username ?? ($_SESSION['username'] ?? ($_SESSION['userusername'] ?? 'System'));
$deleted   = 0;
$alert     = '';
$error     = '';
$timestamp = date('Y-m-d H:i:s');

// ---------- Routing: plain ?part=1&rec=5  or encoded ?D=<base64> ----------
if (!empty($_GET['D'])) {
    if (function_exists('workout')) {
        workout($_GET['D']);
    } else {
        parse_str((string)base64_decode($_GET['D']), $__d);
        if (isset($__d['part'])) { $part = (string)$__d['part']; }
        if (isset($__d['rec']))  { $rec  = (string)$__d['rec']; }
    }
    $part = trim((string)$part);
}

if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['member_delete'])) { caniacess($page . "_delete"); }
}

// Role 4 (group leader) can only work with members of their own group
$role      = $_SESSION['userrole'] ?? 0;
$is_leader = ($role == 4);
$own_group = $is_leader ? (int)($_SESSION['groupid'] ?? 0) : 0;

function mem_notify($type, $title, $text) {
    echo "<script> window.addEventListener('load', function () {
        if (typeof PNotify !== 'undefined') {
            new PNotify({ title: " . json_encode($title) . ", text: " . json_encode($text) . ", nonblock: { nonblock: !0 }, type: '$type', styling: 'bootstrap3' });
        }
    }) </script>";
    $bg = ($type === 'success') ? '#e6f7f2' : '#fdecea';
    $fg = ($type === 'success') ? '#1f7a63' : '#c0392b';
    echo "<div style='margin:10px 15px; padding:10px 16px; border-radius:6px; background:$bg; color:$fg; font-weight:600;'>"
       . htmlspecialchars($title . ': ' . $text) . "</div>";
}

// date inputs arrive as YYYY-MM-DD; return that or $default
function mem_date($v, $default = null) {
    $v = trim((string)$v);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : $default;
}
// show only the date part of a stored value (ignores zero dates)
function mem_dateval($v) {
    $v = (string)$v;
    if ($v === '' || strpos($v, '0000') === 0) { return ''; }
    return substr($v, 0, 10);
}
// +254 + last 9 digits
function mem_phone($p) {
    $digits = preg_replace('/\D+/', '', (string)$p);
    return (strlen($digits) >= 9) ? '+254' . substr($digits, -9) : '';
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// COLLECT + VALIDATE (shared by save and update)
// ---------------------------------------------------------------------------
function mem_collect($is_leader, $own_group) {
    $d = [];
    $d['group_id']          = $is_leader ? $own_group : (int)($_POST['group_id'] ?? 0);
    $d['name']              = ucwords(trim($_POST['name'] ?? ''));
    $d['phone_no']          = mem_phone($_POST['phone_no'] ?? '');
    $d['nat_id']            = trim($_POST['nat_id'] ?? '');
    $d['dob']               = mem_date($_POST['dob'] ?? '');
    $d['gender']            = in_array($_POST['gender'] ?? '', ['Male', 'Female', 'Other'], true) ? $_POST['gender'] : '';
    $d['medical_condition'] = (int)($_POST['medical_condition'] ?? 0);
    $d['caregiver_name']    = ucwords(trim($_POST['caregiver_name'] ?? ''));
    $cp                     = trim($_POST['caregiver_phone'] ?? '');
    $d['caregiver_phone']   = ($cp === '') ? '' : (mem_phone($cp) ?: $cp);
    $d['registration_date'] = mem_date($_POST['registration_date'] ?? '', date('Y-m-d'));
    $d['active']            = in_array($_POST['active'] ?? '', ['Yes', 'No'], true) ? $_POST['active'] : '';
    $d['activation_dt']     = mem_date($_POST['activation_dt'] ?? '', date('Y-m-d'));
    $d['deactivation_dt']   = mem_date($_POST['deactivation_dt'] ?? '', null);   // NULL when empty
    $d['isleader']          = (($_POST['isleader'] ?? '') === '1') ? 1 : 0;
    $d['isleader_set']      = in_array($_POST['isleader'] ?? '', ['0', '1'], true);
    return $d;
}
function mem_valid($d) {
    if ($d['group_id'] <= 0)            { return "Select a group."; }
    if ($d['name'] === '')              { return "Enter the member name."; }
    if ($d['phone_no'] === '')          { return "Enter a valid phone number (at least 9 digits)."; }
    if ($d['nat_id'] === '')            { return "Enter the national ID."; }
    if ($d['dob'] === null)             { return "Enter the date of birth."; }
    if ($d['gender'] === '')            { return "Select the gender."; }
    if ($d['medical_condition'] <= 0)   { return "Select the medical condition."; }
    if ($d['active'] === '')            { return "Select the active status."; }
    if (!$d['isleader_set'])            { return "Select whether the member is a leader."; }
    return '';
}

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['member_save'])) {
    $d = mem_collect($is_leader, $own_group);
    $error = mem_valid($d);

    if ($error === '') {
        // next member number: always above the highest M-number already in the table,
        // and never lower than the system counter (idkeys), so it can never repeat
        $max_existing = 0;
        if ($rm = mysqli_query($conn, "SELECT COALESCE(MAX(CAST(SUBSTRING(mnumber,2) AS UNSIGNED)),0) AS n FROM `member`")) {
            $max_existing = (int)($rm->fetch_assoc()['n'] ?? 0);
        }
        $counter = function_exists('nextsequence') ? (int)nextsequence($conn, 'Member') : 0;
        $seq     = max($counter, $max_existing + 1);
        $mnumber = 'M' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $pin = ''; $pin2 = ''; $tnc = ''; $pinchecked = ''; $ussdstage = ''; $showmenu = ''; $user_id = 0; $regfee = 1;

        $stmt = $conn->prepare("INSERT INTO `member`
            (group_id, name, phone_no, nat_id, dob, gender, medical_condition, caregiver_name, caregiver_phone,
             registration_date, active, activation_dt, deactivation_dt, user_id, created_by, created_on,
             pin, pin2, tnc, pinchecked, ussdstage, showmenu, regfee, mnumber, isleader)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param(str_repeat('s', 25),
                $d['group_id'], $d['name'], $d['phone_no'], $d['nat_id'], $d['dob'], $d['gender'], $d['medical_condition'],
                $d['caregiver_name'], $d['caregiver_phone'], $d['registration_date'], $d['active'], $d['activation_dt'],
                $d['deactivation_dt'], $user_id, $username, $timestamp,
                $pin, $pin2, $tnc, $pinchecked, $ussdstage, $showmenu, $regfee, $mnumber, $d['isleader']);
            // try again with the next number if the member number is already taken
            $saved = false;
            for ($try = 0; $try < 10; $try++) {
                $mnumber = 'M' . str_pad($seq, 4, '0', STR_PAD_LEFT);
                if ($stmt->execute()) { $saved = true; break; }
                if ($stmt->errno == 1062 && stripos($stmt->error, 'mnumber') !== false) { $seq++; continue; }
                break;
            }
            if ($saved) {
                $alert = 1;
                // keep the system counter in step with the number just used
                $seq_esc = (int)$seq;
                @mysqli_query($conn, "UPDATE idkeys SET keyvalue = GREATEST(keyvalue, $seq_esc) WHERE keyname = 'Member'");
                if (function_exists('audittrail'))    { audittrail($conn, 'member', '', 'inserting', $timestamp, $username); }
                if (function_exists('create_db_sms')) { create_db_sms($conn, 7, 'member', $d['phone_no'], ''); }
            } elseif ($stmt->errno == 1062) {
                $error = "A member with the same phone number or national ID already exists. (" . $stmt->error . ")";
            } else {
                $error = "Could not save the member: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
    if ($alert !== '') { mem_notify('success', 'Success', 'Member saved successfully!'); $part = ''; }
    if ($error !== '') { mem_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE  (PINs / USSD fields are NOT touched: the form does not contain them)
// ---------------------------------------------------------------------------
if (isset($_POST['member_update'])) {
    $d     = mem_collect($is_leader, $own_group);
    $logid = (int)($_POST['logid'] ?? 0);
    $error = mem_valid($d);
    if ($error === '' && $logid <= 0) { $error = "Member not found."; }

    if ($error === '') {
        $sql = "UPDATE `member` SET group_id=?, name=?, phone_no=?, nat_id=?, dob=?, gender=?, medical_condition=?,
                    caregiver_name=?, caregiver_phone=?, registration_date=?, active=?, activation_dt=?, deactivation_dt=?, isleader=?
                WHERE logid=?" . ($is_leader ? " AND group_id=?" : "");
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if ($is_leader) {
                $stmt->bind_param(str_repeat('s', 16),
                    $d['group_id'], $d['name'], $d['phone_no'], $d['nat_id'], $d['dob'], $d['gender'], $d['medical_condition'],
                    $d['caregiver_name'], $d['caregiver_phone'], $d['registration_date'], $d['active'], $d['activation_dt'],
                    $d['deactivation_dt'], $d['isleader'], $logid, $own_group);
            } else {
                $stmt->bind_param(str_repeat('s', 15),
                    $d['group_id'], $d['name'], $d['phone_no'], $d['nat_id'], $d['dob'], $d['gender'], $d['medical_condition'],
                    $d['caregiver_name'], $d['caregiver_phone'], $d['registration_date'], $d['active'], $d['activation_dt'],
                    $d['deactivation_dt'], $d['isleader'], $logid);
            }
            if ($stmt->execute()) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'member', $logid, 'updatings', $timestamp, $username); }
            } else {
                $error = "Could not update the member: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
    if ($alert !== '') { mem_notify('success', 'Success', 'Member updated successfully!'); $part = ''; }
    if ($error !== '') { mem_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['member_delete'])) {
    foreach (($_POST['del'] ?? []) as $id) {
        $id = (int)$id;
        if ($id <= 0) { continue; }
        if (function_exists('audittrail')) { audittrail($conn, 'member', $id, 'deleting', $timestamp, $username); }
        $stmt = $conn->prepare("DELETE FROM `member` WHERE logid=?" . ($is_leader ? " AND group_id=?" : ""));
        if ($stmt) {
            if ($is_leader) { $stmt->bind_param("ii", $id, $own_group); } else { $stmt->bind_param("i", $id); }
            if ($stmt->execute() && $stmt->affected_rows > 0) { $deleted++; }
            $stmt->close();
        }
    }
    if ($deleted > 0) { mem_notify('success', 'Success', 'Deleted successfully'); }
    else              { mem_notify('error', 'Error', 'No record selected'); }
}

if (function_exists('_title')) { _title($title = 'Member'); } else { $title = 'Member'; }

// ---------------------------------------------------------------------------
// FORM VALUES (add / edit)
// ---------------------------------------------------------------------------
$v = [
    'group_id' => $is_leader ? $own_group : '', 'name' => '', 'phone_no' => '', 'nat_id' => '', 'dob' => '', 'gender' => '',
    'medical_condition' => '', 'caregiver_name' => '', 'caregiver_phone' => '',
    'registration_date' => date('Y-m-d'), 'isleader' => '', 'active' => '', 'activation_dt' => date('Y-m-d'),
    'deactivation_dt' => '', 'logid' => '',
];
$edit_found = false;

if ($part == '1' && isset($_POST['member_save'])) {            // keep what was typed after an error
    foreach (['group_id','name','phone_no','nat_id','dob','gender','medical_condition','caregiver_name','caregiver_phone','registration_date','isleader','active','activation_dt'] as $k) {
        $v[$k] = $_POST[$k] ?? $v[$k];
    }
    if ($is_leader) { $v['group_id'] = $own_group; }
}
if ($part == '2') {
    if (isset($_POST['member_update'])) {
        foreach (['group_id','name','phone_no','nat_id','dob','gender','medical_condition','caregiver_name','caregiver_phone','registration_date','isleader','active','activation_dt','deactivation_dt','logid'] as $k) {
            $v[$k] = $_POST[$k] ?? '';
        }
        $edit_found = true;
    } else {
        $sql = "SELECT * FROM `member` WHERE logid=?" . ($is_leader ? " AND group_id=?" : "");
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if ($is_leader) { $rid = (int)$rec; $stmt->bind_param("ii", $rid, $own_group); }
            else            { $rid = (int)$rec; $stmt->bind_param("i", $rid); }
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $edit_found = true;
                foreach ($v as $k => $_) { if (isset($row[$k])) { $v[$k] = $row[$k]; } }
                $v['dob']               = mem_dateval($row['dob'] ?? '');
                $v['registration_date'] = mem_dateval($row['registration_date'] ?? '');
                $v['activation_dt']     = mem_dateval($row['activation_dt'] ?? '');
                $v['deactivation_dt']   = mem_dateval($row['deactivation_dt'] ?? '');
                $v['isleader']          = ((string)($row['isleader'] ?? '0') === '1') ? '1' : '0';
            }
            $stmt->close();
        }
    }
}

// dropdown data (only needed on the form)
$groups = []; $conds = [];
if ($part == '1' || $part == '2') {
    if ($is_leader) { $st = $conn->prepare("SELECT logid, name FROM provider_group WHERE logid=? ORDER BY name"); $st->bind_param("i", $own_group); }
    else            { $st = $conn->prepare("SELECT logid, name FROM provider_group ORDER BY name"); }
    if ($st) { $st->execute(); $rs = $st->get_result(); while ($g = $rs->fetch_assoc()) { $groups[] = $g; } $st->close(); }
    if ($rs = mysqli_query($conn, "SELECT logid, description FROM `conditions` ORDER BY description")) {
        while ($c = mysqli_fetch_assoc($rs)) { $conds[] = $c; }
    }
}
?>

<style>
/* ---------- Panel ---------- */
form[name='member'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='member'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='member'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='member'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='member'] .x_content { padding: 24px 28px; }
form[name='member'] .panel_toolbox { list-style: none; margin: 0; padding: 0; }

/* ---------- Form grid ---------- */
form[name='member'] .mb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px 28px;
}
form[name='member'] .mb-field label {
    display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='member'] .mb-field label .req { color: #e74c3c; }
form[name='member'] .form-control {
    width: 100%; height: 38px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='member'] .form-control::placeholder { color: #9bbcf0; }
form[name='member'] .form-control:focus {
    border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38, 185, 154, .18); outline: none;
}
form[name='member'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='member'] .form-control:required:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231, 76, 60, .15); }
form[name='member'] .mb-actions { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e3e8ee; display: flex; gap: 10px; flex-wrap: wrap; }

/* ---------- Buttons ---------- */
form[name='member'] .btn {
    border-radius: 6px; padding: .45em 1.4em; font-weight: 600; letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='member'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='member'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='member'] .btn-success:hover { background: #1f9c82; }
form[name='member'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='member'] .btn-primary:hover { background: #1558b8; }
form[name='member'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='member'] .btn-secondary:hover { background: #6f7a8d; }
form[name='member'] .btn-danger    { background: #e74c3c; border-color: #e74c3c; color: #fff; }
form[name='member'] .btn-danger:hover { background: #c0392b; }

/* ---------- List ---------- */
form[name='member'] .mb-search { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
form[name='member'] .mb-search .form-control { max-width: 340px; }
.mb-table-wrapper {
    width: 100%; overflow-x: auto; background: #fff;
    border: 1px solid #e3e8ee; border-radius: 8px; padding: 10px;
}
.mb-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
.mb-table-wrapper th, .mb-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
.mb-table-wrapper thead th, .mb-table-wrapper tfoot th, .mb-table-wrapper tfoot td {
    background: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
.mb-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
.mb-table-wrapper a { color: #1a73e8; font-weight: 600; }
.mb-table-wrapper .fa-pencil:hover, .mb-table-wrapper .fa-user-md:hover { color: #1f9c82 !important; }
.mb-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
.mb-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; }
.mb-badge.yes { background: #26b99a; }
.mb-badge.no  { background: #e74c3c; }
.mb-error { color: #c0392b; padding: 14px; text-align: center; }

@media (max-width: 767px) {
    form[name='member'] .x_content { padding: 16px 14px; }
}
</style>

<form method="POST" action="" class="rounded" name="member" style="min-width:100%" enctype="multipart/form-data">
<div class="col-md-12 col-sm-12 col-xs-12">

<?php if ($part == '1' || $part == '2') {
    $is_edit = ($part == '2'); ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small><?= $is_edit ? ' update entry' : ' new entry' ?></small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
    <?php if ($is_edit && !$edit_found) { ?>
      <div class="mb-error">Member not found, or you do not have access to this member.</div>
      <div class="mb-actions"><a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a></div>
    <?php } else { ?>

      <div class="mb-grid">
        <div class="mb-field">
          <label for="group_id">Group <span class="req">*</span></label>
          <select class="form-control" id="group_id" name="group_id" required>
            <?php if (!$is_leader) { ?><option value="">Select Group</option><?php } ?>
            <?php foreach ($groups as $g) { ?>
            <option value="<?= htmlspecialchars($g['logid']) ?>" <?= ((string)$g['logid'] === (string)$v['group_id']) ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="name">Name <span class="req">*</span></label>
          <input type="text" required class="form-control" name="name" id="name" placeholder="Full name" value="<?= htmlspecialchars($v['name']) ?>" autocomplete="off">
        </div>

        <div class="mb-field">
          <label for="phone_no">Phone no <span class="req">*</span></label>
          <input type="text" required class="form-control" name="phone_no" id="phone_no" placeholder="0712345678" value="<?= htmlspecialchars($v['phone_no']) ?>" autocomplete="off">
        </div>

        <div class="mb-field">
          <label for="nat_id">National ID <span class="req">*</span></label>
          <input type="text" required class="form-control" name="nat_id" id="nat_id" placeholder="National ID" value="<?= htmlspecialchars($v['nat_id']) ?>" autocomplete="off">
        </div>

        <div class="mb-field">
          <label for="dob">Date of birth <span class="req">*</span></label>
          <input type="date" required class="form-control" name="dob" id="dob" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($v['dob']) ?>">
        </div>

        <div class="mb-field">
          <label for="gender">Gender <span class="req">*</span></label>
          <select class="form-control" id="gender" name="gender" required>
            <option value="">Select Gender</option>
            <?php foreach (['Male', 'Female', 'Other'] as $gx) { ?>
            <option value="<?= $gx ?>" <?= ($v['gender'] === $gx) ? 'selected' : '' ?>><?= $gx ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="medical_condition">Medical condition <span class="req">*</span></label>
          <select class="form-control" id="medical_condition" name="medical_condition" required>
            <option value="">Select Medical condition</option>
            <?php foreach ($conds as $c) { ?>
            <option value="<?= htmlspecialchars($c['logid']) ?>" <?= ((string)$c['logid'] === (string)$v['medical_condition']) ? 'selected' : '' ?>><?= htmlspecialchars($c['description']) ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="caregiver_name">Caregiver name</label>
          <input type="text" class="form-control" name="caregiver_name" id="caregiver_name" placeholder="Caregiver name" value="<?= htmlspecialchars($v['caregiver_name']) ?>" autocomplete="off">
        </div>

        <div class="mb-field">
          <label for="caregiver_phone">Caregiver phone</label>
          <input type="text" class="form-control" name="caregiver_phone" id="caregiver_phone" placeholder="Caregiver phone" value="<?= htmlspecialchars($v['caregiver_phone']) ?>" autocomplete="off">
        </div>

        <div class="mb-field">
          <label for="registration_date">Registration date</label>
          <input type="date" class="form-control" name="registration_date" id="registration_date" value="<?= htmlspecialchars($v['registration_date']) ?>">
        </div>

        <div class="mb-field">
          <label for="isleader">Leader <span class="req">*</span></label>
          <select class="form-control" id="isleader" name="isleader" required>
            <option value="">Select</option>
            <option value="1" <?= ((string)$v['isleader'] === '1') ? 'selected' : '' ?>>Yes</option>
            <option value="0" <?= ((string)$v['isleader'] === '0') ? 'selected' : '' ?>>No</option>
          </select>
        </div>

        <div class="mb-field">
          <label for="active">Active <span class="req">*</span></label>
          <select class="form-control" id="active" name="active" required>
            <option value="">Select Active</option>
            <option value="Yes" <?= ($v['active'] === 'Yes') ? 'selected' : '' ?>>Yes</option>
            <option value="No"  <?= ($v['active'] === 'No')  ? 'selected' : '' ?>>No</option>
          </select>
        </div>

        <div class="mb-field">
          <label for="activation_dt">Activation date</label>
          <input type="date" class="form-control" name="activation_dt" id="activation_dt" value="<?= htmlspecialchars($v['activation_dt']) ?>">
        </div>

        <?php if ($is_edit) { ?>
        <div class="mb-field">
          <label for="deactivation_dt">Deactivation date</label>
          <input type="date" class="form-control" name="deactivation_dt" id="deactivation_dt" value="<?= htmlspecialchars($v['deactivation_dt']) ?>">
        </div>
        <?php } ?>
      </div>

      <div class="mb-actions">
        <?php if ($is_edit) { ?>
        <button type="submit" name="member_update" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Update</button>
        <input type="hidden" name="logid" value="<?= htmlspecialchars($v['logid']) ?>">
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Cancel</a>
        <?php } else { ?>
        <button type="submit" name="member_save" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Save</button>
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a>
        <?php } ?>
      </div>

    <?php } ?>
    </div>
  </div>

<?php } else {
    // ------------------------------- LIST -------------------------------
    $search = trim($_POST['search'] ?? '');
    $list_error = '';
    $rows = [];

    $where = []; $types = ''; $params = [];
    if ($is_leader)    { $where[] = "m.group_id = ?"; $types .= 'i'; $params[] = $own_group; }
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = "(m.name LIKE ? OR m.phone_no LIKE ? OR m.nat_id LIKE ? OR m.mnumber LIKE ? OR m.caregiver_name LIKE ? OR g.name LIKE ? OR c.description LIKE ?)";
        $types .= 'sssssss';
        array_push($params, $like, $like, $like, $like, $like, $like, $like);
    }
    $sql = "SELECT m.*, g.name AS groupname, c.description AS condname
              FROM `member` m
              LEFT JOIN provider_group g ON g.logid = m.group_id
              LEFT JOIN `conditions` c   ON c.logid = m.medical_condition"
         . ($where ? " WHERE " . implode(" AND ", $where) : "")
         . " ORDER BY m.logid DESC";
    $st = $conn->prepare($sql);
    if ($st) {
        if ($params) { $st->bind_param($types, ...$params); }
        if ($st->execute()) {
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) { $rows[] = $r; }
        } else { $list_error = $st->error; }
        $st->close();
    } else { $list_error = $conn->error; }
    $mycount = count($rows);
?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> existing records</small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="index.php?page=<?= $page ?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Create New</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="mb-search">
        <input type="text" class="form-control" name="search" placeholder="Search name, phone, ID, member no, group or condition" value="<?= htmlspecialchars($search) ?>" autocomplete="off">
        <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-search"></i> Search</button>
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Reset</a>
      </div>

      <div class="mb-table-wrapper">
        <table id="datatable" role="grid" class="table table-striped table-bordered table-sm dataTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Member Ref</th>
              <th>Group</th>
              <th>Name</th>
              <th>Phone No</th>
              <th>Gender</th>
              <th>Medical Condition</th>
              <th>Caregiver Name</th>
              <th>Caregiver Phone</th>
              <th>Registration Date</th>
              <th>Active</th>
              <th style="text-align:center;">Rx</th>
              <th width="1%">Edit</th>
              <th width="1%">Del</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($list_error !== '') { ?>
            <tr><td colspan="14" class="mb-error">Query error: <?= htmlspecialchars($list_error) ?></td></tr>
          <?php } else {
              foreach ($rows as $row) { $act = (($row['active'] ?? '') === 'Yes'); ?>
            <tr>
              <td><?= htmlspecialchars($row['logid']) ?></td>
              <td><a href="index.php?page=member_profile&id=<?= urlencode($row['logid']) ?>"><?= htmlspecialchars($row['mnumber'] ?? '') ?></a></td>
              <td><?= htmlspecialchars($row['groupname'] ?? '') ?></td>
              <td><strong><?= htmlspecialchars($row['name'] ?? '') ?></strong></td>
              <td><?= htmlspecialchars($row['phone_no'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['gender'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['condname'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['caregiver_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['caregiver_phone'] ?? '') ?></td>
              <td><?= htmlspecialchars(mem_dateval($row['registration_date'] ?? '')) ?></td>
              <td><span class="mb-badge <?= $act ? 'yes' : 'no' ?>"><?= $act ? 'Yes' : 'No' ?></span></td>
              <td align="center"><a href="index.php?page=prescription&part=1&m=<?= urlencode($row['logid']) ?>&g=<?= urlencode($row['group_id']) ?>" title="Prescription"><i class="fa fa-user-md"></i></a></td>
              <td align="center"><a href="index.php?page=<?= $page ?>&part=2&rec=<?= urlencode($row['logid']) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a></td>
              <td align="center"><input name="del[]" type="checkbox" value="<?= htmlspecialchars($row['logid']) ?>"></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr>
              <td colspan="14" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <i class="fa fa-info-circle fa-lg"></i> No members found. Click <a href="index.php?page=<?= $page ?>&part=1"><u>Create New</u></a> to get started.
              </td>
            </tr>
          <?php }
          } ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="13" style="text-align:right;">With selected items:</td>
              <td><button name="member_delete" value="Delete" type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Please confirm deletion of the checked members');"><i class="fa fa-trash"></i></button></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <input type="hidden" name="txtcount" id="txtcount" value="<?= $mycount ?>">
    </div>
  </div>
<?php } ?>

</div>
</form>
<?php } ?>