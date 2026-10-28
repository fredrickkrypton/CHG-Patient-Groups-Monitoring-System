<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

global $title;
$conn = $GLOBALS['conn'] ?? null;

$page = "prescription";

// ---------- Safe defaults ----------
$part      = isset($_GET['part']) ? trim((string)$_GET['part']) : '';
$rec       = isset($_GET['rec'])  ? trim((string)$_GET['rec'])  : '';
$username  = $_SESSION['userusername'] ?? ($_SESSION['username'] ?? ($username ?? 'System'));
$deleted   = 0;
$alert     = '';
$error     = '';
$timestamp = date('Y-m-d H:i:s');
$today     = date('Y-m-d');

// Pre-fill when arriving from the Members list (?part=1&m=<member>&g=<group>)
$pre_member = (int)($_GET['m'] ?? 0);
$pre_group  = trim((string)($_GET['g'] ?? ''));

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
    if (isset($_POST['prescription_delete'])) { caniacess($page . "_delete"); }
}

// Role 4 (group leader) can only work with prescriptions of members in their own group
$is_leader = (($_SESSION['userrole'] ?? 0) == 4);
$own_group = $is_leader ? (int)($_SESSION['groupid'] ?? 0) : 0;

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */
if (!function_exists('rx_e')) {
    function rx_e($v) { return htmlspecialchars((string)($v === null ? '' : $v), ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('rx_notify')) {
    // PNotify toast plus an inline banner (same behaviour as the Members page)
    function rx_notify($type, $title, $text) {
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

if (!function_exists('rx_fetch_all')) {
    function rx_fetch_all($conn, $sql, $types = '', $params = array()) {
        if (!$conn) { return array(); }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('prescription.php prepare failed: ' . $conn->error); return array(); }
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!$stmt->execute()) {
            error_log('prescription.php execute failed: ' . $stmt->error);
            $stmt->close();
            return array();
        }
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('rx_exec')) {
    // Returns affected rows, or false on failure.
    function rx_exec($conn, $sql, $types, $params) {
        if (!$conn) { return false; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('prescription.php prepare failed: ' . $conn->error); return false; }
        $stmt->bind_param($types, ...$params);
        $ok       = $stmt->execute();
        $affected = $stmt->affected_rows;
        if (!$ok) { error_log('prescription.php execute failed: ' . $stmt->error); }
        $stmt->close();
        return $ok ? $affected : false;
    }
}

// Accepts Y-m-d or d/m/Y. Returns Y-m-d, $default when blank, or false when invalid.
if (!function_exists('rx_date')) {
    function rx_date($v, $default) {
        $v = trim((string)$v);
        if ($v === '') { return $default; }
        if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $v)) {
            $v = function_exists('dateto_mysql') ? dateto_mysql($v) : date('Y-m-d', strtotime(str_replace('/', '-', $v)));
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return false;
        }
        return $v;
    }
}

// Date part of a stored value (blank for zero dates)
if (!function_exists('rx_dateval')) {
    function rx_dateval($v) {
        $v = (string)$v;
        if ($v === '' || strpos($v, '0000') === 0) { return ''; }
        return substr($v, 0, 10);
    }
}

if (!function_exists('rx_status')) {
    function rx_status($from, $to) {
        $today = date('Y-m-d');
        $from  = rx_dateval($from);
        $to    = rx_dateval($to);
        if ($to !== '' && $to < $today)     { return array('Expired',  'expired'); }
        if ($from !== '' && $from > $today) { return array('Upcoming', 'upcoming'); }
        return array('Active', 'active');
    }
}

if (!function_exists('rx_validate')) {
    function rx_validate($conn, $member_id, $dispensed, $from, $to, $is_leader = false, $own_group = 0) {
        if ($member_id === '') { return 'Select the patient / member.'; }
        if ($dispensed === false || $from === false || $to === false) { return 'Enter a valid date in every date field.'; }
        if ($to < $from) { return '"Valid to" cannot be earlier than "Valid from".'; }
        if ($is_leader) {
            $found = rx_fetch_all($conn, "SELECT logid FROM `member` WHERE logid=? AND group_id=?", 'ss', array($member_id, (string)$own_group));
        } else {
            $found = rx_fetch_all($conn, "SELECT logid FROM `member` WHERE logid=?", 's', array($member_id));
        }
        if (!$found) { return 'The selected patient / member was not found.'; }
        return '';
    }
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['prescription_save'])) {
    $group_id       = $is_leader ? (string)$own_group : trim((string)($_POST['group_id'] ?? ''));
    $member_id      = trim((string)($_POST['member_id'] ?? ''));
    $dispensationdt = rx_date($_POST['dispensationdt'] ?? '', date('Y-m-d'));
    $valid_from     = rx_date($_POST['valid_from'] ?? '', date('Y-m-d'));
    $valid_to       = rx_date($_POST['valid_to'] ?? '', date('Y-m-d', strtotime('+1 month')));

    $error = rx_validate($conn, $member_id, $dispensationdt, $valid_from, $valid_to, $is_leader, $own_group);

    if ($error === '') {
        $res = rx_exec(
            $conn,
            "INSERT INTO prescription (group_id, member_id, dispensation_dt, valid_from, valid_to, created_by, created_on) VALUES (?,?,?,?,?,?,?)",
            'sssssss',
            array($group_id, $member_id, $dispensationdt, $valid_from, $valid_to, $username, $timestamp)
        );
        if ($res !== false) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, 'prescription', $conn->insert_id, 'inserting', $timestamp, $username); }
        } else {
            $error = 'The prescription could not be saved. Please try again.';
        }
    }
    if ($alert !== '') { rx_notify('success', 'Success', 'Prescription saved successfully!'); $part = ''; }
    if ($error !== '') { rx_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['prescription_update'])) {
    $logid          = (int)($_POST['logid'] ?? 0);
    $group_id       = $is_leader ? (string)$own_group : trim((string)($_POST['group_id'] ?? ''));
    $member_id      = trim((string)($_POST['member_id'] ?? ''));
    $dispensationdt = rx_date($_POST['dispensationdt'] ?? '', false);
    $valid_from     = rx_date($_POST['valid_from'] ?? '', false);
    $valid_to       = rx_date($_POST['valid_to'] ?? '', false);

    $error = ($logid <= 0) ? 'Prescription not found.' : rx_validate($conn, $member_id, $dispensationdt, $valid_from, $valid_to, $is_leader, $own_group);

    if ($error === '' && $is_leader) {
        $owned = rx_fetch_all($conn, "SELECT p.logid FROM prescription p JOIN `member` m ON m.logid = p.member_id WHERE p.logid=? AND m.group_id=?", 'ss', array($logid, (string)$own_group));
        if (!$owned) { $error = 'Prescription not found.'; }
    }

    if ($error === '') {
        $sql  = "UPDATE prescription SET group_id=?, member_id=?, dispensation_dt=?, valid_from=?, valid_to=? WHERE logid=?"
              . ($is_leader ? " AND member_id IN (SELECT logid FROM `member` WHERE group_id=?)" : "");
        $args = array($group_id, $member_id, $dispensationdt, $valid_from, $valid_to, $logid);
        if ($is_leader) { $args[] = (string)$own_group; }
        $res = rx_exec($conn, $sql, str_repeat('s', count($args)), $args);
        if ($res !== false) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, 'prescription', $logid, 'updatings', $timestamp, $username); }
        } else {
            $error = 'The prescription could not be updated. Please try again.';
        }
    }
    if ($alert !== '') { rx_notify('success', 'Success', 'Prescription updated successfully!'); $part = ''; }
    if ($error !== '') { rx_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['prescription_delete'])) {
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['del'] ?? array()))));
    foreach ($ids as $del_id) {
        $sql  = "DELETE FROM prescription WHERE logid=?"
              . ($is_leader ? " AND member_id IN (SELECT logid FROM `member` WHERE group_id=?)" : "");
        $args = array($del_id);
        if ($is_leader) { $args[] = (string)$own_group; }
        $res = rx_exec($conn, $sql, str_repeat('s', count($args)), $args);
        if ($res !== false && $res > 0) {
            $deleted++;
            if (function_exists('audittrail')) { audittrail($conn, 'prescription', $del_id, 'deleting', $timestamp, $username); }
        }
    }
    if ($deleted > 0) { rx_notify('success', 'Success', 'Deleted successfully'); }
    else              { rx_notify('error', 'Error', 'No record selected'); }
}

$display_title = 'Prescriptions';
if (function_exists('_title')) { _title($title = $display_title); } else { $title = $display_title; }

// ---------------------------------------------------------------------------
// FORM VALUES (add / edit)
// ---------------------------------------------------------------------------
$v = [
    'logid' => '', 'member_id' => '', 'group_id' => $is_leader ? (string)$own_group : '',
    'dispensation_dt' => date('Y-m-d'), 'valid_from' => date('Y-m-d'), 'valid_to' => date('Y-m-d', strtotime('+1 month')),
    'created_by' => $username,
];
$edit_found = false;
$map = ['member_id' => 'member_id', 'group_id' => 'group_id', 'dispensation_dt' => 'dispensationdt', 'valid_from' => 'valid_from', 'valid_to' => 'valid_to'];

if ($part == '1') {
    if (isset($_POST['prescription_save'])) {                    // keep what was typed after an error
        foreach ($map as $col => $post) { $v[$col] = $_POST[$post] ?? $v[$col]; }
    } else {
        if ($pre_member > 0)                    { $v['member_id'] = (string)$pre_member; }
        if ($pre_group !== '' && !$is_leader)   { $v['group_id']  = $pre_group; }
    }
    if ($is_leader) { $v['group_id'] = (string)$own_group; }
}
if ($part == '2') {
    if ($is_leader) {
        $found_rows = rx_fetch_all($conn, "SELECT p.* FROM prescription p JOIN `member` m ON m.logid = p.member_id WHERE p.logid=? AND m.group_id=?", 'ss', array((int)$rec, (string)$own_group));
    } else {
        $found_rows = rx_fetch_all($conn, "SELECT * FROM prescription WHERE logid=?", 's', array((int)$rec));
    }
    if ($found_rows) {
        $edit_found = true;
        $row = $found_rows[0];
        foreach ($v as $k => $_) { if (isset($row[$k])) { $v[$k] = $row[$k]; } }
        $v['dispensation_dt'] = rx_dateval($row['dispensation_dt'] ?? '');
        $v['valid_from']      = rx_dateval($row['valid_from'] ?? '');
        $v['valid_to']        = rx_dateval($row['valid_to'] ?? '');
        if (isset($_POST['prescription_update'])) {
            foreach ($map as $col => $post) { $v[$col] = $_POST[$post] ?? $v[$col]; }
            if ($is_leader) { $v['group_id'] = (string)$own_group; }
        }
    }
}

// dropdown data for the add/edit form
$groups = []; $members = [];
if ($part == '1' || $part == '2') {
    if ($is_leader) {
        $groups  = rx_fetch_all($conn, "SELECT logid, name FROM provider_group WHERE logid=? ORDER BY name", 'i', array($own_group));
        $members = rx_fetch_all($conn, "SELECT logid, name, group_id FROM `member` WHERE group_id=? ORDER BY name", 's', array((string)$own_group));
    } else {
        $groups  = rx_fetch_all($conn, "SELECT logid, name FROM provider_group ORDER BY name");
        $members = rx_fetch_all($conn, "SELECT logid, name, group_id FROM `member` ORDER BY name");
    }
}

// ---------------------------------------------------------------------------
// LIST: filters, data, summary
// ---------------------------------------------------------------------------
$rows = []; $mycount = 0;
$search = ''; $sel_group = 0; $sel_status = ''; $start_date = ''; $end_date = '';
$group_options = []; $cnt = ['active' => 0, 'upcoming' => 0, 'expired' => 0]; $patients = 0; $csv_name = 'prescriptions';

if ($part != '1' && $part != '2') {
    $is_date = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

    $search     = trim((string)($_POST['search'] ?? ''));
    $start_date = (isset($_POST['start_date']) && $is_date($_POST['start_date'])) ? $_POST['start_date'] : '';
    $end_date   = (isset($_POST['end_date'])   && $is_date($_POST['end_date']))   ? $_POST['end_date']   : '';
    if ($start_date !== '' && $end_date !== '' && $start_date > $end_date) { $tmp = $start_date; $start_date = $end_date; $end_date = $tmp; }
    $sel_group  = $is_leader ? $own_group : (int)($_POST['group_id'] ?? 0);
    $sel_status = in_array($_POST['status'] ?? '', ['active', 'upcoming', 'expired'], true) ? $_POST['status'] : '';

    if ($is_leader) {
        $group_options = rx_fetch_all($conn, "SELECT logid, name FROM provider_group WHERE logid=? ORDER BY name", 'i', array($own_group));
    } else {
        $group_options = rx_fetch_all($conn, "SELECT logid, name FROM provider_group ORDER BY name");
    }

    $where = []; $types = ''; $params = [];
    if ($is_leader) {
        $where[] = "m.group_id = ?"; $types .= 's'; $params[] = (string)$own_group;
    } elseif ($sel_group > 0) {
        $where[] = "p.group_id = ?"; $types .= 's'; $params[] = (string)$sel_group;
    }
    if ($start_date !== '') { $where[] = "DATE(p.dispensation_dt) >= ?"; $types .= 's'; $params[] = $start_date; }
    if ($end_date !== '')   { $where[] = "DATE(p.dispensation_dt) <= ?"; $types .= 's'; $params[] = $end_date; }
    if ($sel_status === 'expired') {
        $where[] = "p.valid_to < ?"; $types .= 's'; $params[] = $today;
    } elseif ($sel_status === 'upcoming') {
        $where[] = "p.valid_from > ? AND (p.valid_to IS NULL OR p.valid_to >= ?)"; $types .= 'ss'; array_push($params, $today, $today);
    } elseif ($sel_status === 'active') {
        $where[] = "(p.valid_to IS NULL OR p.valid_to >= ?) AND (p.valid_from IS NULL OR p.valid_from <= ?)"; $types .= 'ss'; array_push($params, $today, $today);
    }
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = "(m.name LIKE ? OR g.name LIKE ? OR p.group_id LIKE ? OR p.created_by LIKE ?)";
        $types  .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }

    $rows = rx_fetch_all(
        $conn,
        "SELECT p.*, m.name AS member_name, g.name AS groupname
           FROM prescription p
           LEFT JOIN `member` m        ON m.logid = p.member_id
           LEFT JOIN provider_group g ON g.logid = p.group_id"
        . ($where ? " WHERE " . implode(" AND ", $where) : "")
        . " ORDER BY p.logid DESC",
        $types,
        $params
    );
    $mycount = count($rows);

    $seen = [];
    foreach ($rows as $r) {
        $st = rx_status($r['valid_from'] ?? '', $r['valid_to'] ?? '');
        $cnt[$st[1]]++;
        $seen[(string)($r['member_id'] ?? '')] = true;
    }
    $patients = count($seen);

    if ($start_date !== '' || $end_date !== '') {
        $csv_name = 'prescriptions_' . ($start_date !== '' ? $start_date : 'start') . '_to_' . ($end_date !== '' ? $end_date : 'today');
    }
}
?>

<style>
/* ---------- Panel ---------- */
form[name='prescription'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='prescription'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='prescription'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='prescription'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='prescription'] .x_content { padding: 24px 28px; }
form[name='prescription'] .x_title { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
form[name='prescription'] .x_title .clearfix { display: none; }
form[name='prescription'] .panel_toolbox {
    list-style: none; margin: 0 0 0 auto; padding: 0; float: none;
    display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 8px;
}

/* ---------- Form grid ---------- */
form[name='prescription'] .mb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px 28px;
}
form[name='prescription'] .mb-field label {
    display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='prescription'] .mb-field label .req { color: #e74c3c; }
form[name='prescription'] .form-control {
    width: 100%; height: 38px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='prescription'] .form-control::placeholder { color: #9bbcf0; }
form[name='prescription'] .form-control:focus {
    border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38, 185, 154, .18); outline: none;
}
form[name='prescription'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='prescription'] .form-control:required:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231, 76, 60, .15); }
form[name='prescription'] .form-control[readonly] { background: #f7f9fc; color: #8a94a6; cursor: default; }
form[name='prescription'] .mb-hint { display: block; margin-top: 4px; font-size: .75rem; color: #8a94a6; }
form[name='prescription'] .mb-actions { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e3e8ee; display: flex; gap: 10px; flex-wrap: wrap; }

/* ---------- Buttons ---------- */
form[name='prescription'] .btn {
    border-radius: 6px; padding: .45em 1.4em; font-weight: 600; letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='prescription'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='prescription'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='prescription'] .btn-success:hover { background: #1f9c82; }
form[name='prescription'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='prescription'] .btn-primary:hover { background: #1558b8; }
form[name='prescription'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='prescription'] .btn-secondary:hover { background: #6f7a8d; }
form[name='prescription'] .btn-danger    { background: #e74c3c; border-color: #e74c3c; color: #fff; }
form[name='prescription'] .btn-danger:hover { background: #c0392b; }
form[name='prescription'] .btn:focus-visible,
form[name='prescription'] a:focus-visible { outline: 2px solid #1a73e8; outline-offset: 2px; }

/* ---------- Filter bar ---------- */
form[name='prescription'] .gs-filter {
    display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;
    background: #f7f9fc; border: 1px solid #e3e8ee; border-radius: 8px;
    padding: 14px 16px; margin-bottom: 18px;
}
form[name='prescription'] .gs-field { flex: 1 1 150px; min-width: 130px; }
form[name='prescription'] .gs-field.gs-wide { flex: 2 1 220px; }
form[name='prescription'] .gs-field label {
    display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='prescription'] .gs-field .form-control { height: 36px; }
form[name='prescription'] .gs-field.gs-btn { flex: 0 0 auto; display: flex; gap: 8px; flex-wrap: wrap; }
form[name='prescription'] .gs-field.gs-btn .btn {
    height: 36px; padding: 0 1.2em; display: inline-flex; align-items: center; gap: 6px;
}

/* ---------- Summary cards ---------- */
form[name='prescription'] .gs-summary { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; }
form[name='prescription'] .gs-card {
    flex: 1 1 150px; background: #fff; border: 1px solid #e3e8ee;
    border-left: 4px solid #26b99a; border-radius: 8px; padding: 12px 16px;
}
form[name='prescription'] .gs-card.blue { border-left-color: #1a73e8; }
form[name='prescription'] .gs-card.red  { border-left-color: #e74c3c; }
form[name='prescription'] .gs-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
form[name='prescription'] .gs-card .val { font-size: 1.3rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }

/* ---------- Table ---------- */
form[name='prescription'] .mb-table-wrapper {
    width: 100%; overflow-x: auto; background: #fff;
    border: 1px solid #e3e8ee; border-radius: 8px; padding: 10px;
}
form[name='prescription'] .mb-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
form[name='prescription'] .mb-table-wrapper th,
form[name='prescription'] .mb-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
form[name='prescription'] .mb-table-wrapper thead th,
form[name='prescription'] .mb-table-wrapper tfoot th,
form[name='prescription'] .mb-table-wrapper tfoot td {
    background: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
form[name='prescription'] .mb-table-wrapper tfoot tr.gs-total td { background: #2a3f54 !important; color: #fff !important; border-bottom: 0 !important; }
form[name='prescription'] .mb-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
form[name='prescription'] .mb-table-wrapper a { color: #1a73e8; font-weight: 600; }
form[name='prescription'] .mb-table-wrapper .fa-pencil:hover,
form[name='prescription'] .mb-table-wrapper .fa-medkit:hover { color: #1f9c82 !important; }
form[name='prescription'] .mb-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
form[name='prescription'] .mb-table-wrapper .num { text-align: right; }
form[name='prescription'] .mb-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; }
form[name='prescription'] .mb-badge.active   { background: #26b99a; }
form[name='prescription'] .mb-badge.upcoming { background: #1a73e8; }
form[name='prescription'] .mb-badge.expired  { background: #e74c3c; }
form[name='prescription'] .mb-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='prescription'] .x_content { padding: 16px 14px; }
    form[name='prescription'] .gs-field,
    form[name='prescription'] .gs-field.gs-wide,
    form[name='prescription'] .gs-field.gs-btn { flex: 1 1 100%; }
    form[name='prescription'] .gs-field.gs-btn .btn { flex: 1; justify-content: center; }
}
@media (prefers-reduced-motion: reduce) {
    form[name='prescription'] .btn, form[name='prescription'] .form-control { transition: none; }
    form[name='prescription'] .btn:hover { transform: none; }
}

/* ---------- Print ---------- */
@media print {
    form[name='prescription'] .gs-filter,
    form[name='prescription'] .panel_toolbox,
    form[name='prescription'] .gs-skip,
    form[name='prescription'] tr.no-print { display: none !important; }
    form[name='prescription'] .x_panel { box-shadow: none; border: 0; }
    form[name='prescription'] .mb-table-wrapper { overflow: visible; border: 0; }
}
</style>

<form method="POST" action="" class="rounded" name="prescription" style="min-width:100%" enctype="multipart/form-data">
<div class="col-md-12 col-sm-12 col-xs-12">

<?php if ($part == '1' || $part == '2') {
    $is_edit = ($part == '2'); ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= rx_e($display_title) ?><small><?= $is_edit ? ' update entry' : ' new entry' ?></small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
    <?php if ($is_edit && !$edit_found) { ?>
      <div class="mb-error">Prescription not found, or you do not have access to it.</div>
      <div class="mb-actions"><a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a></div>
    <?php } else {
        // keep a legacy free-text group value selectable
        $group_known = false;
        foreach ($groups as $g) { if ((string)$g['logid'] === (string)$v['group_id']) { $group_known = true; break; } } ?>

      <div class="mb-grid">
        <div class="mb-field">
          <label for="member_id">Patient / Member <span class="req">*</span></label>
          <select class="form-control" id="member_id" name="member_id" required>
            <option value="">Select Member</option>
            <?php foreach ($members as $m) { ?>
            <option value="<?= rx_e($m['logid']) ?>" data-group="<?= rx_e($m['group_id']) ?>" <?= ((string)$m['logid'] === (string)$v['member_id']) ? 'selected' : '' ?>><?= rx_e($m['name']) ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="group_id">Group</label>
          <select class="form-control" id="group_id" name="group_id">
            <?php if (!$is_leader) { ?><option value="">Select Group</option><?php } ?>
            <?php if (!$is_leader && !$group_known && (string)$v['group_id'] !== '') { ?>
            <option value="<?= rx_e($v['group_id']) ?>" selected><?= rx_e($v['group_id']) ?></option>
            <?php } ?>
            <?php foreach ($groups as $g) { ?>
            <option value="<?= rx_e($g['logid']) ?>" <?= ((string)$g['logid'] === (string)$v['group_id']) ? 'selected' : '' ?>><?= rx_e($g['name']) ?></option>
            <?php } ?>
          </select>
          <?php if (!$is_leader) { ?><span class="mb-hint">Fills in from the member when left empty.</span><?php } ?>
        </div>

        <div class="mb-field">
          <label for="dispensationdt">Dispensed on <span class="req">*</span></label>
          <input type="date" required class="form-control" name="dispensationdt" id="dispensationdt" value="<?= rx_e($v['dispensation_dt']) ?>">
        </div>

        <div class="mb-field">
          <label for="valid_from">Valid from <span class="req">*</span></label>
          <input type="date" required class="form-control" name="valid_from" id="valid_from" value="<?= rx_e($v['valid_from']) ?>">
        </div>

        <div class="mb-field">
          <label for="valid_to">Valid to <span class="req">*</span></label>
          <input type="date" required class="form-control" name="valid_to" id="valid_to" value="<?= rx_e($v['valid_to']) ?>">
          <span class="mb-hint">On or after the valid from date.</span>
        </div>

        <div class="mb-field">
          <label for="handled_by">Handled by</label>
          <input type="text" readonly class="form-control" id="handled_by" value="<?= rx_e($is_edit ? $v['created_by'] : $username) ?>">
        </div>
      </div>

      <div class="mb-actions">
        <?php if ($is_edit) { ?>
        <button type="submit" name="prescription_update" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Update</button>
        <input type="hidden" name="logid" value="<?= (int)$v['logid'] ?>">
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Cancel</a>
        <?php } else { ?>
        <button type="submit" name="prescription_save" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Save</button>
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a>
        <?php } ?>
      </div>

    <?php } ?>
    </div>
  </div>

<?php } else { ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= rx_e($display_title) ?><small><?= ($start_date !== '' || $end_date !== '')
            ? ' ' . rx_e($start_date !== '' ? $start_date : 'start') . ' to ' . rx_e($end_date !== '' ? $end_date : 'today')
            : ' existing records' ?></small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="index.php?page=<?= $page ?>&amp;part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Create New</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="gs-filter">
        <div class="gs-field gs-wide">
          <label for="search">Search</label>
          <input type="text" class="form-control" name="search" id="search" placeholder="Patient, group or user" value="<?= rx_e($search) ?>" autocomplete="off">
        </div>
        <div class="gs-field">
          <label for="f_group">Group</label>
          <select class="form-control" id="f_group" name="group_id">
            <?php if (!$is_leader) { ?><option value="">All Groups</option><?php } ?>
            <?php foreach ($group_options as $g) { ?>
            <option value="<?= rx_e($g['logid']) ?>" <?= ((int)$g['logid'] === $sel_group) ? 'selected' : '' ?>><?= rx_e($g['name']) ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="gs-field">
          <label for="f_status">Status</label>
          <select class="form-control" id="f_status" name="status">
            <option value="">All</option>
            <option value="active"   <?= $sel_status === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="upcoming" <?= $sel_status === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
            <option value="expired"  <?= $sel_status === 'expired'  ? 'selected' : '' ?>>Expired</option>
          </select>
        </div>
        <div class="gs-field">
          <label for="start_date">Dispensed from</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?= rx_e($start_date) ?>">
        </div>
        <div class="gs-field">
          <label for="end_date">Dispensed to</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?= rx_e($end_date) ?>">
        </div>
        <div class="gs-field gs-btn">
          <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Reset</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="rxExportCSV();"><i class="fa fa-download"></i> CSV</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
        </div>
      </div>

      <div class="gs-summary">
        <div class="gs-card blue"><div class="lbl">Prescriptions</div><div class="val"><?= (int)$mycount ?></div></div>
        <div class="gs-card blue"><div class="lbl">Patients</div><div class="val"><?= (int)$patients ?></div></div>
        <div class="gs-card"><div class="lbl">Active</div><div class="val"><?= (int)$cnt['active'] ?></div></div>
        <div class="gs-card blue"><div class="lbl">Upcoming</div><div class="val"><?= (int)$cnt['upcoming'] ?></div></div>
        <div class="gs-card red"><div class="lbl">Expired</div><div class="val"><?= (int)$cnt['expired'] ?></div></div>
      </div>

      <div class="mb-table-wrapper">
        <table id="rx_table" role="grid" class="table table-striped table-bordered table-sm dataTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Patient Name</th>
              <th>Group</th>
              <th>Dispensed On</th>
              <th>Valid From</th>
              <th>Valid To</th>
              <th>Status</th>
              <th>Days Left</th>
              <th>Handled By</th>
              <th class="gs-skip" style="text-align:center;">Meds</th>
              <th class="gs-skip" width="1%">Edit</th>
              <th class="gs-skip" width="1%">Del</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row) {
              $st    = rx_status($row['valid_from'] ?? '', $row['valid_to'] ?? '');
              $gname = ($row['groupname'] ?? '') !== '' ? $row['groupname'] : ($row['group_id'] ?? '');
              $vt    = rx_dateval($row['valid_to'] ?? '');
              $left  = '';
              if ($st[1] === 'active' && $vt !== '') {
                  $d    = (int)floor((strtotime($vt) - strtotime($today)) / 86400);
                  $left = ($d <= 0) ? 'Ends today' : $d . ' day' . ($d == 1 ? '' : 's');
              } ?>
            <tr>
              <td><?= rx_e($row['logid']) ?></td>
              <td><strong><?= (($row['member_name'] ?? '') !== '') ? rx_e($row['member_name']) : 'Unknown Member' ?></strong></td>
              <td><?= rx_e($gname) ?></td>
              <td><?= rx_e(rx_dateval($row['dispensation_dt'] ?? '')) ?></td>
              <td><?= rx_e(rx_dateval($row['valid_from'] ?? '')) ?></td>
              <td><?= rx_e($vt) ?></td>
              <td><span class="mb-badge <?= $st[1] ?>"><?= $st[0] ?></span></td>
              <td><?= rx_e($left) ?></td>
              <td><?= rx_e($row['created_by'] ?? '') ?></td>
              <td class="gs-skip" align="center"><a href="index.php?page=prescription_details&amp;part=1&amp;p=<?= urlencode($row['logid']) ?>" title="Add/Manage Medication"><i class="fa fa-lg fa-medkit"></i></a></td>
              <td class="gs-skip" align="center"><a href="index.php?page=<?= $page ?>&amp;part=2&amp;rec=<?= urlencode($row['logid']) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a></td>
              <td class="gs-skip" align="center"><input name="del[]" type="checkbox" value="<?= rx_e($row['logid']) ?>" aria-label="Select prescription <?= rx_e($row['logid']) ?>"></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr class="no-export">
              <td colspan="12" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <?php if ($search !== '' || $sel_status !== '' || $start_date !== '' || $end_date !== '' || ($sel_group > 0 && !$is_leader)) { ?>
                <i class="fa fa-info-circle fa-lg"></i> No prescriptions match these filters. <a href="index.php?page=<?= $page ?>"><u>Reset filters</u></a>
                <?php } else { ?>
                <i class="fa fa-info-circle fa-lg"></i> No prescriptions found. Click <a href="index.php?page=<?= $page ?>&amp;part=1"><u>Create New</u></a> to get started.
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
          </tbody>
          <tfoot>
            <tr class="gs-total">
              <td colspan="7" class="num">Total prescriptions</td>
              <td colspan="5"><?= (int)$mycount ?></td>
            </tr>
            <tr class="no-print">
              <td colspan="11" style="text-align:right;">With selected items:</td>
              <td><button name="prescription_delete" value="Delete" type="submit" class="btn btn-danger btn-sm" title="Delete selected" onclick="return confirm('Please confirm deletion of the checked prescriptions');"><i class="fa fa-trash"></i></button></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
<?php } ?>

</div>
</form>

<script>
(function () {
    // "Valid to" can never be earlier than "Valid from"
    var from = document.getElementById('valid_from'), to = document.getElementById('valid_to');
    if (from && to) {
        var sync = function () {
            to.min = from.value;
            if (to.value && from.value && to.value < from.value) { to.value = from.value; }
        };
        from.addEventListener('change', sync);
        sync();
    }
    // Fill the group from the chosen member when the group is still empty
    var mem = document.getElementById('member_id'), grp = document.getElementById('group_id');
    if (mem && grp) {
        var fill = function () {
            var opt = mem.options[mem.selectedIndex];
            if (opt && opt.getAttribute('data-group') && !grp.value) { grp.value = opt.getAttribute('data-group'); }
        };
        mem.addEventListener('change', function () { grp.value = ''; fill(); });
        fill();
    }
})();

// Export the visible list to CSV (no external libraries; skips Meds / Edit / Del columns)
function rxExportCSV() {
    var table = document.getElementById('rx_table');
    if (!table) { return; }
    var lines = [];
    table.querySelectorAll('thead tr, tbody tr').forEach(function (tr) {
        if (tr.classList.contains('no-export')) { return; }
        var cells = [];
        tr.querySelectorAll('th,td').forEach(function (c) {
            if (c.classList.contains('gs-skip')) { return; }
            var t = c.innerText.replace(/\s+/g, ' ').trim().replace(/"/g, '""');
            cells.push('"' + t + '"');
        });
        if (cells.length) { lines.push(cells.join(',')); }
    });
    var blob = new Blob(["\uFEFF" + lines.join("\r\n")], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = <?= json_encode($csv_name . '.csv', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
</script>
<?php } ?>