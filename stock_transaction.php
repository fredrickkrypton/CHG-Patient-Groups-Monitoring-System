<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

// TARGET VARIABLE ACCESSIBILITY VIA SUPERGLOBALS
global $title;
$conn = $GLOBALS['conn'] ?? null;

$page = "stock_transaction";

// ---------- Safe defaults ----------
$part      = isset($_GET['part']) ? trim((string)$_GET['part']) : '';
$rec       = isset($_GET['rec'])  ? trim((string)$_GET['rec'])  : '';
$username  = $_SESSION['userusername'] ?? ($_SESSION['username'] ?? ($username ?? 'System'));
$deleted   = 0;
$alert     = '';
$error     = '';
$timestamp = date('Y-m-d H:i:s');
$today     = date('Y-m-d');
$trans_types = array('Dispensed', 'Received', 'Adjustment');

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
    if (isset($_POST['stock_transaction_delete'])) { caniacess($page . "_delete"); }
}

// Role 4 (group leader) can only work with transactions of recipients in their own group
$is_leader = (($_SESSION['userrole'] ?? 0) == 4);
$own_group = $is_leader ? (int)($_SESSION['groupid'] ?? 0) : 0;

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */
if (!function_exists('st_e')) {
    function st_e($v) { return htmlspecialchars((string)($v === null ? '' : $v), ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('st_notify')) {
    // PNotify toast plus an inline banner (same behaviour as the Members page)
    function st_notify($type, $title, $text) {
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

if (!function_exists('st_fetch_all')) {
    function st_fetch_all($conn, $sql, $types = '', $params = array()) {
        if (!$conn) { return array(); }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('stock_transaction.php prepare failed: ' . $conn->error); return array(); }
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!$stmt->execute()) {
            error_log('stock_transaction.php execute failed: ' . $stmt->error);
            $stmt->close();
            return array();
        }
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('st_exec')) {
    // Returns affected rows, or false on failure.
    function st_exec($conn, $sql, $types, $params) {
        if (!$conn) { return false; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('stock_transaction.php prepare failed: ' . $conn->error); return false; }
        $stmt->bind_param($types, ...$params);
        $ok       = $stmt->execute();
        $affected = $stmt->affected_rows;
        if (!$ok) { error_log('stock_transaction.php execute failed: ' . $stmt->error); }
        $stmt->close();
        return $ok ? $affected : false;
    }
}

// Accepts Y-m-d or d/m/Y. Returns Y-m-d, $default when blank, or false when invalid.
if (!function_exists('st_date')) {
    function st_date($v, $default) {
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

if (!function_exists('st_dateval')) {
    function st_dateval($v) {
        $v = (string)$v;
        if ($v === '' || strpos($v, '0000') === 0) { return ''; }
        return substr($v, 0, 10);
    }
}

// 10.00 -> 10, 2.50 -> 2.5
if (!function_exists('st_num')) {
    function st_num($n) {
        $s = (string)$n;
        if (strpos($s, '.') !== false) { $s = rtrim(rtrim($s, '0'), '.'); }
        return ($s === '') ? '0' : $s;
    }
}

// Validates a posted transaction. Returns an error message, or '' when everything is fine.
if (!function_exists('st_check')) {
    function st_check($conn, $types, $type, $recipient, $date, $medicine, $qty, $line, $is_leader, $own_group) {
        if (!in_array($type, $types, true)) { return 'Select the transaction type.'; }
        if ($recipient === '')              { return 'Select the patient / recipient.'; }
        if ($date === false)                { return 'Enter a valid transaction date.'; }
        if ($medicine === '')               { return 'Select the medicine.'; }
        if (!ctype_digit($qty) || (int)$qty <= 0) { return 'Enter a whole-number quantity greater than zero.'; }

        if ($is_leader) {
            $found = st_fetch_all($conn, "SELECT logid FROM `member` WHERE logid=? AND group_id=?", 'ss', array($recipient, (string)$own_group));
        } else {
            $found = st_fetch_all($conn, "SELECT logid FROM `member` WHERE logid=?", 's', array($recipient));
        }
        if (!$found) { return 'The selected patient / recipient was not found.'; }

        if (!st_fetch_all($conn, "SELECT logid FROM medicine WHERE logid=?", 's', array($medicine))) {
            return 'The selected medicine was not found.';
        }

        if ($line !== '') {
            if (!ctype_digit($line)) { return 'The prescription line is not valid.'; }
            $ok = st_fetch_all(
                $conn,
                "SELECT d.logid FROM prescription_details d JOIN prescription p ON p.logid = d.prescription_id WHERE d.logid=? AND p.member_id=?",
                'ss',
                array($line, $recipient)
            );
            if (!$ok) { return 'That prescription line does not belong to the selected patient.'; }
        }
        return '';
    }
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['stock_transaction_save'])) {
    $trans_type  = trim((string)($_POST['trans_type'] ?? ''));
    $recipient   = trim((string)($_POST['recipient_id'] ?? ''));
    $trans_date  = st_date($_POST['trans_date'] ?? '', $today);
    $medicine    = trim((string)($_POST['medicine_id'] ?? ''));
    $qty         = trim((string)($_POST['quantity'] ?? ''));
    $line        = ($trans_type === 'Dispensed') ? trim((string)($_POST['prescription_detail_id'] ?? '')) : '';

    $error = st_check($conn, $trans_types, $trans_type, $recipient, $trans_date, $medicine, $qty, $line, $is_leader, $own_group);

    if ($error === '') {
        $cols = array('trans_type', 'recipient_id', 'trans_date', 'medicine_id', 'quantity', 'created_by', 'created_on');
        $args = array($trans_type, $recipient, $trans_date, $medicine, $qty, $username, $timestamp);
        if ($line !== '') { $cols[] = 'prescription_detail_id'; $args[] = $line; }

        $res = st_exec(
            $conn,
            "INSERT INTO stock_transaction (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")",
            str_repeat('s', count($args)),
            $args
        );
        if ($res !== false) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, 'stock_transaction', $conn->insert_id, 'inserting', $timestamp, $username); }
        } else {
            $error = 'The transaction could not be saved. Please try again.';
        }
    }
    if ($alert !== '') { st_notify('success', 'Success', 'Transaction saved successfully!'); $part = ''; }
    if ($error !== '') { st_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['stock_transaction_update'])) {
    $logid       = (int)($_POST['logid'] ?? 0);
    $trans_type  = trim((string)($_POST['trans_type'] ?? ''));
    $recipient   = trim((string)($_POST['recipient_id'] ?? ''));
    $trans_date  = st_date($_POST['trans_date'] ?? '', false);
    $medicine    = trim((string)($_POST['medicine_id'] ?? ''));
    $qty         = trim((string)($_POST['quantity'] ?? ''));
    $line        = ($trans_type === 'Dispensed') ? trim((string)($_POST['prescription_detail_id'] ?? '')) : '';

    $error = ($logid <= 0) ? 'Transaction not found.' : st_check($conn, $trans_types, $trans_type, $recipient, $trans_date, $medicine, $qty, $line, $is_leader, $own_group);

    if ($error === '' && $is_leader) {
        $owned = st_fetch_all($conn, "SELECT s.logid FROM stock_transaction s JOIN `member` m ON m.logid = s.recipient_id WHERE s.logid=? AND m.group_id=?", 'ss', array($logid, (string)$own_group));
        if (!$owned) { $error = 'Transaction not found.'; }
    }

    if ($error === '') {
        $sql  = "UPDATE stock_transaction SET trans_type=?, recipient_id=?, trans_date=?, medicine_id=?, quantity=?, prescription_detail_id=" . ($line !== '' ? '?' : 'NULL') . " WHERE logid=?"
              . ($is_leader ? " AND recipient_id IN (SELECT logid FROM `member` WHERE group_id=?)" : "");
        $args = array($trans_type, $recipient, $trans_date, $medicine, $qty);
        if ($line !== '') { $args[] = $line; }
        $args[] = $logid;
        if ($is_leader) { $args[] = (string)$own_group; }

        $res = st_exec($conn, $sql, str_repeat('s', count($args)), $args);
        if ($res !== false) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, 'stock_transaction', $logid, 'updatings', $timestamp, $username); }
        } else {
            $error = 'The transaction could not be updated. Please try again.';
        }
    }
    if ($alert !== '') { st_notify('success', 'Success', 'Transaction updated successfully!'); $part = ''; }
    if ($error !== '') { st_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['stock_transaction_delete'])) {
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['del'] ?? array()))));
    foreach ($ids as $del_id) {
        $sql  = "DELETE FROM stock_transaction WHERE logid=?"
              . ($is_leader ? " AND recipient_id IN (SELECT logid FROM `member` WHERE group_id=?)" : "");
        $args = array($del_id);
        if ($is_leader) { $args[] = (string)$own_group; }
        $res = st_exec($conn, $sql, str_repeat('s', count($args)), $args);
        if ($res !== false && $res > 0) {
            $deleted++;
            if (function_exists('audittrail')) { audittrail($conn, 'stock_transaction', $del_id, 'deleting', $timestamp, $username); }
        }
    }
    if ($deleted > 0) { st_notify('success', 'Success', 'Deleted successfully'); }
    else              { st_notify('error', 'Error', 'No record selected'); }
}

$display_title = 'Stock Transactions / Dispensing';
if (function_exists('_title')) { _title($title = $display_title); } else { $title = $display_title; }

// ---------------------------------------------------------------------------
// FORM VALUES (add / edit)
// ---------------------------------------------------------------------------
$v = [
    'logid' => '', 'trans_type' => 'Dispensed', 'recipient_id' => '', 'trans_date' => $today,
    'medicine_id' => '', 'prescription_detail_id' => '', 'quantity' => '', 'created_by' => $username,
];
$edit_found = false;
$post_keys  = ['trans_type', 'recipient_id', 'trans_date', 'medicine_id', 'prescription_detail_id', 'quantity'];

if ($part == '1' && isset($_POST['stock_transaction_save'])) {          // keep what was typed after an error
    foreach ($post_keys as $k) { $v[$k] = $_POST[$k] ?? $v[$k]; }
}
if ($part == '2') {
    if ($is_leader) {
        $found_rows = st_fetch_all($conn, "SELECT s.* FROM stock_transaction s JOIN `member` m ON m.logid = s.recipient_id WHERE s.logid=? AND m.group_id=?", 'ss', array((int)$rec, (string)$own_group));
    } else {
        $found_rows = st_fetch_all($conn, "SELECT * FROM stock_transaction WHERE logid=?", 's', array((int)$rec));
    }
    if ($found_rows) {
        $edit_found = true;
        $row = $found_rows[0];
        foreach ($v as $k => $_) { if (isset($row[$k])) { $v[$k] = $row[$k]; } }
        $v['trans_date'] = st_dateval($row['trans_date'] ?? '');
        $v['quantity']   = st_num($row['quantity'] ?? '');
        if (empty($row['prescription_detail_id'])) { $v['prescription_detail_id'] = ''; }
        if (isset($_POST['stock_transaction_update'])) {
            foreach ($post_keys as $k) { $v[$k] = $_POST[$k] ?? $v[$k]; }
        }
    }
}

// dropdown data
$medicines = []; $members = []; $lines = [];
$medicines = st_fetch_all($conn, "SELECT logid, name FROM medicine ORDER BY name");   // form and list filter
if ($part == '1' || $part == '2') {
    if ($is_leader) {
        $members = st_fetch_all($conn, "SELECT logid, name FROM `member` WHERE group_id=? ORDER BY name", 's', array((string)$own_group));
        $lines   = st_fetch_all(
            $conn,
            "SELECT d.logid, d.prescription_id, d.molecule_type, d.quantity, p.member_id, md.name AS medicine_name
               FROM prescription_details d
               JOIN prescription p ON p.logid = d.prescription_id
               JOIN `member` m     ON m.logid = p.member_id
               LEFT JOIN medicine md ON md.logid = d.molecule_type
              WHERE m.group_id=?
              ORDER BY d.logid DESC LIMIT 1000",
            's',
            array((string)$own_group)
        );
    } else {
        $members = st_fetch_all($conn, "SELECT logid, name FROM `member` ORDER BY name");
        $lines   = st_fetch_all(
            $conn,
            "SELECT d.logid, d.prescription_id, d.molecule_type, d.quantity, p.member_id, md.name AS medicine_name
               FROM prescription_details d
               JOIN prescription p ON p.logid = d.prescription_id
               LEFT JOIN medicine md ON md.logid = d.molecule_type
              ORDER BY d.logid DESC LIMIT 1000"
        );
    }
}

// ---------------------------------------------------------------------------
// LIST: filters, data, summary
// ---------------------------------------------------------------------------
$rows = []; $mycount = 0;
$search = ''; $sel_type = ''; $sel_med = 0; $start_date = ''; $end_date = '';
$qty_by = ['Dispensed' => 0, 'Received' => 0, 'Adjustment' => 0];
$csv_name = 'stock_transactions';

if ($part != '1' && $part != '2') {
    $is_date = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

    $search     = trim((string)($_POST['search'] ?? ''));
    $sel_type   = in_array($_POST['trans_type'] ?? '', $trans_types, true) ? $_POST['trans_type'] : '';
    $sel_med    = (int)($_POST['medicine_id'] ?? 0);
    $start_date = (isset($_POST['start_date']) && $is_date($_POST['start_date'])) ? $_POST['start_date'] : '';
    $end_date   = (isset($_POST['end_date'])   && $is_date($_POST['end_date']))   ? $_POST['end_date']   : '';
    if ($start_date !== '' && $end_date !== '' && $start_date > $end_date) { $tmp = $start_date; $start_date = $end_date; $end_date = $tmp; }

    $where = []; $types = ''; $params = [];
    if ($is_leader)          { $where[] = "r.group_id = ?";               $types .= 's'; $params[] = (string)$own_group; }
    if ($sel_type !== '')    { $where[] = "s.trans_type = ?";             $types .= 's'; $params[] = $sel_type; }
    if ($sel_med > 0)        { $where[] = "s.medicine_id = ?";            $types .= 's'; $params[] = (string)$sel_med; }
    if ($start_date !== '')  { $where[] = "DATE(s.trans_date) >= ?";      $types .= 's'; $params[] = $start_date; }
    if ($end_date !== '')    { $where[] = "DATE(s.trans_date) <= ?";      $types .= 's'; $params[] = $end_date; }
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = "(r.name LIKE ? OR md.name LIKE ? OR s.created_by LIKE ? OR s.trans_type LIKE ?)";
        $types  .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }

    $rows = st_fetch_all(
        $conn,
        "SELECT s.*, r.name AS recipient_name, md.name AS medicine_name
           FROM stock_transaction s
           LEFT JOIN `member` r  ON r.logid  = s.recipient_id
           LEFT JOIN medicine md ON md.logid = s.medicine_id"
        . ($where ? " WHERE " . implode(" AND ", $where) : "")
        . " ORDER BY s.logid DESC",
        $types,
        $params
    );
    $mycount = count($rows);
    foreach ($rows as $r) {
        if (isset($qty_by[$r['trans_type']])) { $qty_by[$r['trans_type']] += (float)$r['quantity']; }
    }
    if ($start_date !== '' || $end_date !== '') {
        $csv_name = 'stock_transactions_' . ($start_date !== '' ? $start_date : 'start') . '_to_' . ($end_date !== '' ? $end_date : 'today');
    }
}
?>

<style>
/* ---------- Panel ---------- */
form[name='stock_transaction'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='stock_transaction'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;
}
form[name='stock_transaction'] .x_title .clearfix { display: none; }
form[name='stock_transaction'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='stock_transaction'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='stock_transaction'] .x_content { padding: 24px 28px; }
form[name='stock_transaction'] .panel_toolbox {
    list-style: none; margin: 0 0 0 auto; padding: 0; float: none;
    display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 8px;
}

/* ---------- Form grid ---------- */
form[name='stock_transaction'] .mb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px 28px;
}
form[name='stock_transaction'] .mb-field label {
    display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='stock_transaction'] .mb-field label .req { color: #e74c3c; }
form[name='stock_transaction'] .form-control {
    width: 100%; height: 38px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='stock_transaction'] .form-control::placeholder { color: #9bbcf0; }
form[name='stock_transaction'] .form-control:focus {
    border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38, 185, 154, .18); outline: none;
}
form[name='stock_transaction'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='stock_transaction'] .form-control:required:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231, 76, 60, .15); }
form[name='stock_transaction'] .form-control[readonly],
form[name='stock_transaction'] .form-control:disabled { background: #f7f9fc; color: #8a94a6; cursor: default; }
form[name='stock_transaction'] .mb-hint { display: block; margin-top: 4px; font-size: .75rem; color: #8a94a6; }
form[name='stock_transaction'] .mb-actions { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e3e8ee; display: flex; gap: 10px; flex-wrap: wrap; }

/* ---------- Buttons ---------- */
form[name='stock_transaction'] .btn {
    border-radius: 6px; padding: .45em 1.4em; font-weight: 600; letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='stock_transaction'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='stock_transaction'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='stock_transaction'] .btn-success:hover { background: #1f9c82; }
form[name='stock_transaction'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='stock_transaction'] .btn-primary:hover { background: #1558b8; }
form[name='stock_transaction'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='stock_transaction'] .btn-secondary:hover { background: #6f7a8d; }
form[name='stock_transaction'] .btn-danger    { background: #e74c3c; border-color: #e74c3c; color: #fff; }
form[name='stock_transaction'] .btn-danger:hover { background: #c0392b; }
form[name='stock_transaction'] .btn:focus-visible,
form[name='stock_transaction'] a:focus-visible { outline: 2px solid #1a73e8; outline-offset: 2px; }

/* ---------- Filter bar ---------- */
form[name='stock_transaction'] .gs-filter {
    display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;
    background: #f7f9fc; border: 1px solid #e3e8ee; border-radius: 8px;
    padding: 14px 16px; margin-bottom: 18px;
}
form[name='stock_transaction'] .gs-field { flex: 1 1 150px; min-width: 130px; }
form[name='stock_transaction'] .gs-field.gs-wide { flex: 2 1 220px; }
form[name='stock_transaction'] .gs-field label {
    display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='stock_transaction'] .gs-field .form-control { height: 36px; }
form[name='stock_transaction'] .gs-field.gs-btn { flex: 0 0 auto; display: flex; gap: 8px; flex-wrap: wrap; }
form[name='stock_transaction'] .gs-field.gs-btn .btn {
    height: 36px; padding: 0 1.2em; display: inline-flex; align-items: center; gap: 6px;
}

/* ---------- Summary cards ---------- */
form[name='stock_transaction'] .gs-summary { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; }
form[name='stock_transaction'] .gs-card {
    flex: 1 1 160px; background: #fff; border: 1px solid #e3e8ee;
    border-left: 4px solid #1a73e8; border-radius: 8px; padding: 12px 16px;
}
form[name='stock_transaction'] .gs-card.green { border-left-color: #26b99a; }
form[name='stock_transaction'] .gs-card.amber { border-left-color: #d68910; }
form[name='stock_transaction'] .gs-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
form[name='stock_transaction'] .gs-card .val { font-size: 1.3rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }

/* ---------- Table ---------- */
form[name='stock_transaction'] .mb-table-wrapper {
    width: 100%; overflow-x: auto; background: #fff;
    border: 1px solid #e3e8ee; border-radius: 8px; padding: 10px;
}
form[name='stock_transaction'] .mb-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
form[name='stock_transaction'] .mb-table-wrapper th,
form[name='stock_transaction'] .mb-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
form[name='stock_transaction'] .mb-table-wrapper thead th,
form[name='stock_transaction'] .mb-table-wrapper tfoot th,
form[name='stock_transaction'] .mb-table-wrapper tfoot td {
    background: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
form[name='stock_transaction'] .mb-table-wrapper tfoot tr.gs-total td { background: #2a3f54 !important; color: #fff !important; border-bottom: 0 !important; }
form[name='stock_transaction'] .mb-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
form[name='stock_transaction'] .mb-table-wrapper a { color: #1a73e8; font-weight: 600; }
form[name='stock_transaction'] .mb-table-wrapper .fa-pencil:hover { color: #1f9c82 !important; }
form[name='stock_transaction'] .mb-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
form[name='stock_transaction'] .mb-table-wrapper .num { text-align: right; }
form[name='stock_transaction'] .mb-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; }
form[name='stock_transaction'] .mb-badge.dispensed  { background: #1a73e8; }
form[name='stock_transaction'] .mb-badge.received   { background: #26b99a; }
form[name='stock_transaction'] .mb-badge.adjustment { background: #d68910; }
form[name='stock_transaction'] .mb-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='stock_transaction'] .x_content { padding: 16px 14px; }
    form[name='stock_transaction'] .gs-field,
    form[name='stock_transaction'] .gs-field.gs-wide,
    form[name='stock_transaction'] .gs-field.gs-btn { flex: 1 1 100%; }
    form[name='stock_transaction'] .gs-field.gs-btn .btn { flex: 1; justify-content: center; }
}
@media (prefers-reduced-motion: reduce) {
    form[name='stock_transaction'] .btn, form[name='stock_transaction'] .form-control { transition: none; }
    form[name='stock_transaction'] .btn:hover { transform: none; }
}

/* ---------- Print ---------- */
@media print {
    form[name='stock_transaction'] .gs-filter,
    form[name='stock_transaction'] .panel_toolbox,
    form[name='stock_transaction'] .gs-skip,
    form[name='stock_transaction'] tr.no-print { display: none !important; }
    form[name='stock_transaction'] .x_panel { box-shadow: none; border: 0; }
    form[name='stock_transaction'] .mb-table-wrapper { overflow: visible; border: 0; }
}
</style>

<form method="POST" action="" class="rounded" name="stock_transaction" style="min-width:100%" enctype="multipart/form-data">
<div class="col-md-12 col-sm-12 col-xs-12">

<?php if ($part == '1' || $part == '2') {
    $is_edit = ($part == '2'); ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= st_e($display_title) ?><small><?= $is_edit ? ' update entry' : ' new entry' ?></small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
    <?php if ($is_edit && !$edit_found) { ?>
      <div class="mb-error">Transaction not found, or you do not have access to it.</div>
      <div class="mb-actions"><a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a></div>
    <?php } else { ?>

      <div class="mb-grid">
        <div class="mb-field">
          <label for="trans_type">Transaction type <span class="req">*</span></label>
          <select class="form-control" name="trans_type" id="trans_type" required>
            <?php foreach ($trans_types as $tt) { ?>
            <option value="<?= $tt ?>" <?= ($v['trans_type'] === $tt) ? 'selected' : '' ?>><?= $tt ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="recipient_id">Patient / Recipient <span class="req">*</span></label>
          <select class="form-control" name="recipient_id" id="recipient_id" required>
            <option value="">Select Member</option>
            <?php foreach ($members as $m) { ?>
            <option value="<?= st_e($m['logid']) ?>" <?= ((string)$m['logid'] === (string)$v['recipient_id']) ? 'selected' : '' ?>><?= st_e($m['name']) ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="trans_date">Transaction date <span class="req">*</span></label>
          <input type="date" required class="form-control" name="trans_date" id="trans_date" value="<?= st_e($v['trans_date']) ?>">
        </div>

        <div class="mb-field">
          <label for="medicine_id">Medicine <span class="req">*</span></label>
          <select class="form-control" name="medicine_id" id="medicine_id" required>
            <option value="">Select Medicine</option>
            <?php foreach ($medicines as $md) { ?>
            <option value="<?= st_e($md['logid']) ?>" <?= ((string)$md['logid'] === (string)$v['medicine_id']) ? 'selected' : '' ?>><?= st_e($md['name']) ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="quantity">Quantity <span class="req">*</span></label>
          <input type="number" required min="1" step="1" class="form-control" name="quantity" id="quantity" placeholder="e.g. 10" value="<?= st_e($v['quantity']) ?>" autocomplete="off">
        </div>

        <div class="mb-field">
          <label for="prescription_detail_id">Prescription line</label>
          <select class="form-control" name="prescription_detail_id" id="prescription_detail_id">
            <option value="">None</option>
            <?php foreach ($lines as $ln) { ?>
            <option value="<?= st_e($ln['logid']) ?>"
                    data-member="<?= st_e($ln['member_id']) ?>" data-medicine="<?= st_e($ln['molecule_type']) ?>"
                    <?= ((string)$ln['logid'] === (string)$v['prescription_detail_id']) ? 'selected' : '' ?>>#<?= st_e($ln['logid']) ?> &ndash; Rx <?= st_e($ln['prescription_id']) ?> &ndash; <?= st_e($ln['medicine_name'] ?? 'Unknown') ?> &times; <?= st_e(st_num($ln['quantity'])) ?></option>
            <?php } ?>
          </select>
          <span class="mb-hint">Dispensing only. Lists lines for the chosen patient and medicine.</span>
        </div>

        <div class="mb-field">
          <label for="handled_by">Handled by</label>
          <input type="text" readonly class="form-control" id="handled_by" value="<?= st_e($is_edit ? $v['created_by'] : $username) ?>">
        </div>
      </div>

      <div class="mb-actions">
        <?php if ($is_edit) { ?>
        <button type="submit" name="stock_transaction_update" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Update</button>
        <input type="hidden" name="logid" value="<?= (int)$v['logid'] ?>">
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Cancel</a>
        <?php } else { ?>
        <button type="submit" name="stock_transaction_save" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Save</button>
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a>
        <?php } ?>
      </div>

    <?php } ?>
    </div>
  </div>

<?php } else { ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= st_e($display_title) ?><small><?= ($start_date !== '' || $end_date !== '')
            ? ' ' . st_e($start_date !== '' ? $start_date : 'start') . ' to ' . st_e($end_date !== '' ? $end_date : 'today')
            : ' history log' ?></small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="index.php?page=<?= $page ?>&amp;part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Create New</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="gs-filter">
        <div class="gs-field gs-wide">
          <label for="search">Search</label>
          <input type="text" class="form-control" name="search" id="search" placeholder="Patient, medicine or user" value="<?= st_e($search) ?>" autocomplete="off">
        </div>
        <div class="gs-field">
          <label for="f_type">Type</label>
          <select class="form-control" id="f_type" name="trans_type">
            <option value="">All</option>
            <?php foreach ($trans_types as $tt) { ?>
            <option value="<?= $tt ?>" <?= ($sel_type === $tt) ? 'selected' : '' ?>><?= $tt ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="gs-field gs-wide">
          <label for="f_med">Medicine</label>
          <select class="form-control" id="f_med" name="medicine_id">
            <option value="">All Medicines</option>
            <?php foreach ($medicines as $md) { ?>
            <option value="<?= st_e($md['logid']) ?>" <?= ((int)$md['logid'] === $sel_med) ? 'selected' : '' ?>><?= st_e($md['name']) ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="gs-field">
          <label for="start_date">From</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?= st_e($start_date) ?>">
        </div>
        <div class="gs-field">
          <label for="end_date">To</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?= st_e($end_date) ?>">
        </div>
        <div class="gs-field gs-btn">
          <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Reset</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="stExportCSV();"><i class="fa fa-download"></i> CSV</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
        </div>
      </div>

      <div class="gs-summary">
        <div class="gs-card"><div class="lbl">Transactions</div><div class="val"><?= (int)$mycount ?></div></div>
        <div class="gs-card"><div class="lbl">Dispensed qty</div><div class="val"><?= st_e(number_format($qty_by['Dispensed'], 2, '.', ',')) ?></div></div>
        <div class="gs-card green"><div class="lbl">Received qty</div><div class="val"><?= st_e(number_format($qty_by['Received'], 2, '.', ',')) ?></div></div>
        <div class="gs-card amber"><div class="lbl">Adjustment qty</div><div class="val"><?= st_e(number_format($qty_by['Adjustment'], 2, '.', ',')) ?></div></div>
      </div>

      <div class="mb-table-wrapper">
        <table id="st_table" role="grid" class="table table-striped table-bordered table-sm dataTable">
          <thead>
            <tr>
              <th width="1px">#</th>
              <th>Type</th>
              <th>Patient / Recipient</th>
              <th>Transaction Date</th>
              <th>Medicine</th>
              <th>Rx Line</th>
              <th class="num">Quantity</th>
              <th>Handled By</th>
              <th>Timestamp</th>
              <th class="gs-skip" width="1%">Edit</th>
              <th class="gs-skip" width="1%">Del</th>
            </tr>
          </thead>
          <tbody>
          <?php $n = 0; foreach ($rows as $row) { $n++;
              $bt = strtolower((string)$row['trans_type']);
              if (!in_array($bt, ['dispensed', 'received', 'adjustment'], true)) { $bt = 'dispensed'; } ?>
            <tr>
              <td><?= $n ?></td>
              <td><span class="mb-badge <?= $bt ?>"><?= st_e($row['trans_type']) ?></span></td>
              <td><strong><?= (($row['recipient_name'] ?? '') !== '') ? st_e($row['recipient_name']) : 'Unknown Recipient' ?></strong></td>
              <td><?= st_e(st_dateval($row['trans_date'] ?? '')) ?></td>
              <td><?= (($row['medicine_name'] ?? '') !== '') ? st_e($row['medicine_name']) : 'Unknown Item' ?></td>
              <td><?= !empty($row['prescription_detail_id']) ? st_e($row['prescription_detail_id']) : '-' ?></td>
              <td class="num"><strong><?= st_e(st_num($row['quantity'] ?? '')) ?></strong></td>
              <td><?= st_e($row['created_by'] ?? '') ?></td>
              <td><?= st_e($row['created_on'] ?? '') ?></td>
              <td class="gs-skip" align="center"><a href="index.php?page=<?= $page ?>&amp;part=2&amp;rec=<?= urlencode($row['logid']) ?>" title="Go to edit" aria-label="Edit transaction"><i class="fa fa-lg fa-pencil"></i></a></td>
              <td class="gs-skip" align="center"><input name="del[]" type="checkbox" value="<?= st_e($row['logid']) ?>" aria-label="Select transaction <?= $n ?>"></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr class="no-export">
              <td colspan="11" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <?php if ($search !== '' || $sel_type !== '' || $sel_med > 0 || $start_date !== '' || $end_date !== '') { ?>
                <i class="fa fa-info-circle fa-lg"></i> No transactions match these filters. <a href="index.php?page=<?= $page ?>"><u>Reset filters</u></a>
                <?php } else { ?>
                <i class="fa fa-info-circle fa-lg"></i> No transactions recorded yet. Click <a href="index.php?page=<?= $page ?>&amp;part=1"><u>Create New</u></a> to record one.
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
          </tbody>
          <tfoot>
            <tr class="gs-total">
              <td colspan="6" class="num">Total transactions</td>
              <td colspan="5"><?= (int)$mycount ?></td>
            </tr>
            <tr class="no-print">
              <td colspan="10" style="text-align:right;">With selected items:</td>
              <td><button name="stock_transaction_delete" value="Delete" type="submit" class="btn btn-danger btn-sm" title="Delete selected" onclick="return confirm('Please confirm deletion of the checked transactions');"><i class="fa fa-trash"></i></button></td>
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
    // Prescription line: only for dispensing, and only lines of the chosen patient + medicine
    var type = document.getElementById('trans_type'), rec = document.getElementById('recipient_id'),
        med = document.getElementById('medicine_id'), line = document.getElementById('prescription_detail_id');
    if (type && rec && med && line) {
        var first = true;
        var apply = function () {
            var dispensing = (type.value === 'Dispensed');
            line.disabled = !dispensing;
            var keep = first ? line.value : '';
            first = false;
            Array.prototype.forEach.call(line.options, function (o) {
                if (!o.value) { return; }
                var ok = dispensing
                      && (!rec.value || o.getAttribute('data-member') === rec.value)
                      && (!med.value || o.getAttribute('data-medicine') === med.value);
                if (dispensing && o.value === keep) { ok = true; }
                o.hidden = !ok; o.disabled = !ok;
            });
            var sel = line.options[line.selectedIndex];
            if (!dispensing || (sel && sel.value && sel.disabled)) { line.value = ''; }
        };
        [type, rec, med].forEach(function (el) { el.addEventListener('change', apply); });
        apply();
    }
})();

// Export the visible list to CSV (no external libraries; skips Edit / Del columns)
function stExportCSV() {
    var table = document.getElementById('st_table');
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