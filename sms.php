<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

global $title;
$conn = $GLOBALS['conn'] ?? null;

$page = "sms";

// ---------- Safe defaults ----------
$part      = isset($_GET['part']) ? trim((string)$_GET['part']) : '';
$rec       = isset($_GET['rec'])  ? trim((string)$_GET['rec'])  : '';
$username  = $_SESSION['userusername'] ?? ($_SESSION['username'] ?? ($username ?? 'System'));
$deleted   = 0;
$alert     = '';
$error     = '';
$timestamp = date('Y-m-d H:i:s');
$today     = date('Y-m-d');

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
    if (isset($_POST['sms_delete'])) { caniacess($page . "_delete"); }
}

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */
if (!function_exists('sms_e')) {
    function sms_e($v) { return htmlspecialchars((string)($v === null ? '' : $v), ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('sms_notify')) {
    // PNotify toast plus an inline banner
    function sms_notify($type, $title, $text) {
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

if (!function_exists('sms_fetch_all')) {
    function sms_fetch_all($conn, $sql, $types = '', $params = array()) {
        if (!$conn) { return false; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('sms.php prepare failed: ' . $conn->error); return false; }
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!$stmt->execute()) {
            error_log('sms.php execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('sms_exec')) {
    // Returns affected rows, or false on failure.
    function sms_exec($conn, $sql, $types, $params) {
        if (!$conn) { return false; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('sms.php prepare failed: ' . $conn->error); return false; }
        $stmt->bind_param($types, ...$params);
        $ok       = $stmt->execute();
        $affected = $stmt->affected_rows;
        if (!$ok) { error_log('sms.php execute failed: ' . $stmt->error); }
        $stmt->close();
        return $ok ? $affected : false;
    }
}

// Valid Y-m-d date or false
if (!function_exists('sms_clean_date')) {
    function sms_clean_date($d) {
        $d  = trim((string)$d);
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return ($dt && $dt->format('Y-m-d') === $d) ? $d : false;
    }
}

// One phone number: drops spaces / dashes / brackets, allows a leading +
if (!function_exists('sms_clean_phone')) {
    function sms_clean_phone($p) {
        $p = preg_replace('/[\s\-\(\)\.]/', '', trim((string)$p));
        return preg_match('/^\+?\d{9,15}$/', $p) ? $p : false;
    }
}

if (!function_exists('sms_dateval')) {
    function sms_dateval($v) {
        $v = (string)$v;
        if ($v === '' || strpos($v, '0000') === 0) { return ''; }
        return substr($v, 0, 10);
    }
}

$sms_types = [
    'Reminder'           => 'Prescription Reminder',
    'Payment'            => 'Payment Confirmation',
    'Payment Reminder'   => 'Payment Reminder (Remind to Pay)',
    'Stock Notification' => 'Stock Notice',
    'General Broadcast'  => 'General System Broadcast',
];

// Suggested message for each category (each must stay within 160 characters)
$sms_templates = [
    'Reminder'           => 'Dear member, this is a reminder to collect your prescription medication on your next scheduled visit. Thank you.',
    'Payment'            => 'Dear member, we confirm receipt of your payment. Thank you for your continued support.',
    'Payment Reminder'   => 'Dear member, this is a friendly reminder that your payment is due. Please make your payment as soon as possible. Thank you.',
    'Stock Notification' => 'Dear member, your medication is now available in stock. Please visit the facility to collect it. Thank you.',
    'General Broadcast'  => 'Dear member, we have an important announcement. Please contact your group leader or the facility for more details. Thank you.',
];

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// Make sure the sms table exists (once per login session, harmless if it exists)
// ---------------------------------------------------------------------------
if ($conn && empty($_SESSION['sms_table_ok'])) {
    $conn->query("CREATE TABLE IF NOT EXISTS `sms` (
        `logid`     INT(11)      NOT NULL AUTO_INCREMENT,
        `date`      DATE         DEFAULT NULL,
        `smstype`   VARCHAR(50)  DEFAULT NULL,
        `phone`     VARCHAR(20)  NOT NULL,
        `sms`       VARCHAR(255) NOT NULL,
        `sent`      TINYINT(1)   NOT NULL DEFAULT 0,
        `timestamp` DATETIME     DEFAULT NULL,
        PRIMARY KEY (`logid`),
        KEY `idx_sms_date` (`date`),
        KEY `idx_sms_sent` (`sent`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION['sms_table_ok'] = 1; }
}

// ---------------------------------------------------------------------------
// SAVE (one or many comma-separated numbers)
// ---------------------------------------------------------------------------
if (isset($_POST['sms_save'])) {
    $date    = sms_clean_date($_POST['date'] ?? '') ?: $today;
    $smstype = array_key_exists($_POST['smstype'] ?? '', $sms_types) ? $_POST['smstype'] : 'General Broadcast';
    $sms     = trim((string)($_POST['sms'] ?? ''));
    $sent    = (($_POST['sent'] ?? '0') == '1') ? 1 : 0;
    $numbers = array_unique(array_filter(array_map('trim', explode(',', (string)($_POST['phone'] ?? '')))));

    if (!$conn) {
        $error = "No database connection.";
    } elseif (empty($numbers) || $sms === '') {
        $error = "Recipient phone number(s) and message cannot be left blank.";
    } elseif (mb_strlen($sms) > 160) {
        $error = "Message is longer than 160 characters.";
    } else {
        $stmt = $conn->prepare("INSERT INTO sms (`date`, smstype, phone, sms, sent, `timestamp`) VALUES (?,?,?,?,?,?)");
        if ($stmt === false) {
            $error = "SQL error: " . $conn->error;
        } else {
            $saved = 0; $bad = [];
            foreach ($numbers as $num) {
                $clean = sms_clean_phone($num);
                if ($clean === false) { $bad[] = $num; continue; }
                $stmt->bind_param("ssssis", $date, $smstype, $clean, $sms, $sent, $timestamp);
                if ($stmt->execute()) { $saved++; }
            }
            $stmt->close();
            if ($saved > 0) {
                $alert = "$saved message(s) added to the outbox queue." . ($bad ? " Skipped invalid number(s): " . implode(', ', $bad) : '');
                if (function_exists('audittrail')) { audittrail($conn, 'sms', '', 'inserting', $timestamp, $username); }
            } else {
                $error = $bad ? "Invalid phone number(s): " . implode(', ', $bad) : "The message could not be saved.";
            }
        }
    }
    if ($alert !== '') { sms_notify('success', 'Success', $alert); $part = ''; }
    if ($error !== '') { sms_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['sms_update'])) {
    $logid   = (int)($_POST['logid'] ?? 0);
    $date    = sms_clean_date($_POST['date'] ?? '');
    $smstype = array_key_exists($_POST['smstype'] ?? '', $sms_types) ? $_POST['smstype'] : 'General Broadcast';
    $phone   = sms_clean_phone($_POST['phone'] ?? '');
    $sms     = trim((string)($_POST['sms'] ?? ''));
    $sent    = (($_POST['sent'] ?? '0') == '1') ? 1 : 0;

    if (!$conn) {
        $error = "No database connection.";
    } elseif ($logid <= 0) {
        $error = "Message not found.";
    } elseif ($date === false || $phone === false || $sms === '') {
        $error = "Check the date, phone number and message. They must all be valid.";
    } elseif (mb_strlen($sms) > 160) {
        $error = "Message is longer than 160 characters.";
    } else {
        $res = sms_exec($conn, "UPDATE sms SET `date`=?, smstype=?, phone=?, sms=?, sent=? WHERE logid=?", 'ssssii',
                        array($date, $smstype, $phone, $sms, $sent, $logid));
        if ($res !== false) {
            $alert = "SMS updated successfully.";
            if (function_exists('audittrail')) { audittrail($conn, 'sms', $logid, 'updatings', $timestamp, $username); }
        } else {
            $error = "The message could not be updated. Please try again.";
        }
    }
    if ($alert !== '') { sms_notify('success', 'Success', $alert); $part = ''; }
    if ($error !== '') { sms_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['sms_delete'])) {
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['del'] ?? array()))));
    foreach ($ids as $del_id) {
        $res = sms_exec($conn, "DELETE FROM sms WHERE logid=?", 'i', array($del_id));
        if ($res !== false && $res > 0) {
            $deleted++;
            if (function_exists('audittrail')) { audittrail($conn, 'sms', $del_id, 'deleting', $timestamp, $username); }
        }
    }
    if ($deleted > 0) { sms_notify('success', 'Success', 'Deleted successfully'); }
    else              { sms_notify('error', 'Error', 'No record selected'); }
}

$display_title = 'SMS Alerts';
if (function_exists('_title')) { _title($title = $display_title); } else { $title = $display_title; }

// ---------------------------------------------------------------------------
// FORM VALUES (add / edit)
// ---------------------------------------------------------------------------
$v = ['logid' => '', 'date' => $today, 'smstype' => 'Reminder', 'phone' => '', 'sms' => '', 'sent' => 0];
$edit_found = false;

if ($part == '1' && isset($_POST['sms_save'])) {              // keep what was typed after an error
    foreach (['date', 'smstype', 'phone', 'sms', 'sent'] as $k) { $v[$k] = $_POST[$k] ?? $v[$k]; }
}
if ($part == '2') {
    $found_rows = sms_fetch_all($conn, "SELECT * FROM sms WHERE logid=?", 'i', array((int)$rec));
    if ($found_rows) {
        $edit_found = true;
        $row = $found_rows[0];
        foreach ($v as $k => $_) { if (isset($row[$k])) { $v[$k] = $row[$k]; } }
        $v['date'] = sms_dateval($row['date'] ?? '');
        if (isset($_POST['sms_update'])) {
            foreach (['date', 'smstype', 'phone', 'sms', 'sent'] as $k) { $v[$k] = $_POST[$k] ?? $v[$k]; }
        }
    }
}

// ---------------------------------------------------------------------------
// LIST: filters, data, summary
// ---------------------------------------------------------------------------
$rows = []; $mycount = 0; $list_error = '';
$search = ''; $sel_type = ''; $sel_status = ''; $start_date = ''; $end_date = '';
$cnt = ['sent' => 0, 'pending' => 0]; $recipients = 0; $csv_name = 'sms_outbox';

if ($part != '1' && $part != '2') {
    $is_date = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

    $search     = trim((string)($_POST['search'] ?? ''));
    $start_date = (isset($_POST['start_date']) && $is_date($_POST['start_date'])) ? $_POST['start_date'] : '';
    $end_date   = (isset($_POST['end_date'])   && $is_date($_POST['end_date']))   ? $_POST['end_date']   : '';
    if ($start_date !== '' && $end_date !== '' && $start_date > $end_date) { $tmp = $start_date; $start_date = $end_date; $end_date = $tmp; }
    $sel_type   = array_key_exists($_POST['smstype'] ?? '', $sms_types) ? $_POST['smstype'] : '';
    $sel_status = in_array($_POST['status'] ?? '', ['sent', 'pending'], true) ? $_POST['status'] : '';

    $where = []; $types = ''; $params = [];
    if ($start_date !== '') { $where[] = "`date` >= ?"; $types .= 's'; $params[] = $start_date; }
    if ($end_date !== '')   { $where[] = "`date` <= ?"; $types .= 's'; $params[] = $end_date; }
    if ($sel_type !== '')   { $where[] = "smstype = ?"; $types .= 's'; $params[] = $sel_type; }
    if ($sel_status === 'sent')    { $where[] = "sent = 1"; }
    if ($sel_status === 'pending') { $where[] = "sent = 0"; }
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = "(phone LIKE ? OR sms LIKE ? OR smstype LIKE ?)";
        $types  .= 'sss';
        array_push($params, $like, $like, $like);
    }

    $fetched = sms_fetch_all(
        $conn,
        "SELECT * FROM sms" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY `date` DESC, logid DESC LIMIT 500",
        $types,
        $params
    );
    if ($fetched === false) {
        $list_error = 'SQL error: ' . ($conn ? $conn->error : 'No database connection');
    } else {
        $rows    = $fetched;
        $mycount = count($rows);
        $seen    = [];
        foreach ($rows as $r) {
            if ((int)($r['sent'] ?? 0) === 1) { $cnt['sent']++; } else { $cnt['pending']++; }
            $seen[(string)($r['phone'] ?? '')] = true;
        }
        $recipients = count($seen);
    }

    if ($start_date !== '' || $end_date !== '') {
        $csv_name = 'sms_outbox_' . ($start_date !== '' ? $start_date : 'start') . '_to_' . ($end_date !== '' ? $end_date : 'today');
    }
}

// The list posts to a clean URL so filters never re-open the add form
$form_action = ($part == '1' || $part == '2') ? '' : 'index.php?page=' . $page;
?>

<style>
/* ---------- Panel ---------- */
form[name='sms'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='sms'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;
}
form[name='sms'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='sms'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='sms'] .x_title .clearfix { display: none; }
form[name='sms'] .x_content { padding: 24px 28px; }
form[name='sms'] .panel_toolbox {
    list-style: none; margin: 0 0 0 auto; padding: 0; float: none;
    display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 8px;
}

/* ---------- Form grid ---------- */
form[name='sms'] .mb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px 28px;
}
form[name='sms'] .mb-field.mb-full { grid-column: 1 / -1; }
form[name='sms'] .mb-field label {
    display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='sms'] .mb-field label .req { color: #e74c3c; }
form[name='sms'] .form-control {
    width: 100%; height: 38px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='sms'] textarea.form-control { height: auto; min-height: 110px; resize: vertical; }
form[name='sms'] .form-control::placeholder { color: #9bbcf0; }
form[name='sms'] .form-control:focus {
    border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38, 185, 154, .18); outline: none;
}
form[name='sms'] .mb-field .form-control:required { border-left: 4px solid #e74c3c; }
form[name='sms'] .mb-field .form-control:required:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231, 76, 60, .15); }
form[name='sms'] .mb-hint { display: block; margin-top: 4px; font-size: .75rem; color: #8a94a6; }
form[name='sms'] .sms-counter { float: right; margin-top: 4px; font-size: .75rem; color: #8a94a6; }
form[name='sms'] .sms-counter.warn { color: #e74c3c; font-weight: 700; }
form[name='sms'] .mb-actions { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e3e8ee; display: flex; gap: 10px; flex-wrap: wrap; }

/* ---------- Buttons ---------- */
form[name='sms'] .btn {
    border-radius: 6px; padding: .45em 1.4em; font-weight: 600; letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='sms'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='sms'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='sms'] .btn-success:hover { background: #1f9c82; }
form[name='sms'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='sms'] .btn-primary:hover { background: #1558b8; }
form[name='sms'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='sms'] .btn-secondary:hover { background: #6f7a8d; }
form[name='sms'] .btn-danger    { background: #e74c3c; border-color: #e74c3c; color: #fff; }
form[name='sms'] .btn-danger:hover { background: #c0392b; }
form[name='sms'] .btn:focus-visible,
form[name='sms'] a:focus-visible { outline: 2px solid #1a73e8; outline-offset: 2px; }

/* ---------- Filter bar ---------- */
form[name='sms'] .gs-filter {
    display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;
    background: #f7f9fc; border: 1px solid #e3e8ee; border-radius: 8px;
    padding: 14px 16px; margin-bottom: 18px;
}
form[name='sms'] .gs-field { flex: 1 1 150px; min-width: 130px; }
form[name='sms'] .gs-field.gs-wide { flex: 2 1 220px; }
form[name='sms'] .gs-field label {
    display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='sms'] .gs-field .form-control { height: 36px; }
form[name='sms'] .gs-field.gs-btn { flex: 0 0 auto; display: flex; gap: 8px; flex-wrap: wrap; }
form[name='sms'] .gs-field.gs-btn .btn {
    height: 36px; padding: 0 1.2em; display: inline-flex; align-items: center; gap: 6px;
}

/* ---------- Summary cards ---------- */
form[name='sms'] .gs-summary { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; }
form[name='sms'] .gs-card {
    flex: 1 1 150px; background: #fff; border: 1px solid #e3e8ee;
    border-left: 4px solid #26b99a; border-radius: 8px; padding: 12px 16px;
}
form[name='sms'] .gs-card.blue   { border-left-color: #1a73e8; }
form[name='sms'] .gs-card.orange { border-left-color: #f0a030; }
form[name='sms'] .gs-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
form[name='sms'] .gs-card .val { font-size: 1.3rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }

/* ---------- Table ---------- */
form[name='sms'] .mb-table-wrapper {
    width: 100%; overflow-x: auto; background: #fff;
    border: 1px solid #e3e8ee; border-radius: 8px; padding: 10px;
}
form[name='sms'] .mb-table-wrapper table { width: 100% !important; max-width: none !important; margin-bottom: 0; }
form[name='sms'] .mb-table-wrapper th,
form[name='sms'] .mb-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; white-space: nowrap; }
form[name='sms'] .mb-table-wrapper td.sms-body { white-space: normal; min-width: 260px; max-width: 420px; word-break: break-word; color: #495057; }
form[name='sms'] .mb-table-wrapper td.sms-phone { font-family: Consolas, monospace; font-weight: 600; }
form[name='sms'] .mb-table-wrapper thead th,
form[name='sms'] .mb-table-wrapper tfoot th,
form[name='sms'] .mb-table-wrapper tfoot td {
    background: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
form[name='sms'] .mb-table-wrapper tfoot tr.gs-total td { background: #2a3f54 !important; color: #fff !important; border-bottom: 0 !important; }
form[name='sms'] .mb-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
form[name='sms'] .mb-table-wrapper a { color: #1a73e8; font-weight: 600; }
form[name='sms'] .mb-table-wrapper .fa-pencil:hover { color: #1f9c82 !important; }
form[name='sms'] .mb-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
form[name='sms'] .mb-table-wrapper .num { text-align: right; }
form[name='sms'] .mb-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; }
form[name='sms'] .mb-badge.sent    { background: #26b99a; }
form[name='sms'] .mb-badge.pending { background: #f0a030; }
form[name='sms'] .mb-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='sms'] .x_content { padding: 16px 14px; }
    form[name='sms'] .gs-field,
    form[name='sms'] .gs-field.gs-wide,
    form[name='sms'] .gs-field.gs-btn { flex: 1 1 100%; }
    form[name='sms'] .gs-field.gs-btn .btn { flex: 1; justify-content: center; }
}
@media (prefers-reduced-motion: reduce) {
    form[name='sms'] .btn, form[name='sms'] .form-control { transition: none; }
    form[name='sms'] .btn:hover { transform: none; }
}

/* ---------- Print ---------- */
@media print {
    form[name='sms'] .gs-filter,
    form[name='sms'] .panel_toolbox,
    form[name='sms'] .gs-skip,
    form[name='sms'] tr.no-print { display: none !important; }
    form[name='sms'] .x_panel { box-shadow: none; border: 0; }
    form[name='sms'] .mb-table-wrapper { overflow: visible; border: 0; }
}
</style>

<form method="POST" action="<?= sms_e($form_action) ?>" class="rounded" name="sms" style="min-width:100%" enctype="multipart/form-data">
<div class="col-md-12 col-sm-12 col-xs-12">

<?php if ($part == '1' || $part == '2') {
    $is_edit = ($part == '2'); ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= sms_e($display_title) ?><small><?= $is_edit ? ' update entry' : ' new entry' ?></small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
    <?php if ($is_edit && !$edit_found) { ?>
      <div class="mb-error">Message not found.</div>
      <div class="mb-actions"><a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a></div>
    <?php } else { ?>

      <div class="mb-grid">
        <div class="mb-field">
          <label for="date">Schedule date <span class="req">*</span></label>
          <input type="date" required class="form-control" name="date" id="date" value="<?= sms_e($v['date']) ?>">
        </div>

        <div class="mb-field">
          <label for="smstype">Alert category</label>
          <select class="form-control" name="smstype" id="smstype">
            <?php foreach ($sms_types as $val => $label) { ?>
            <option value="<?= sms_e($val) ?>" <?= ((string)$v['smstype'] === (string)$val) ? 'selected' : '' ?>><?= sms_e($label) ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-field">
          <label for="sent">Delivery status</label>
          <select class="form-control" name="sent" id="sent">
            <option value="0" <?= ((int)$v['sent'] === 0) ? 'selected' : '' ?>>Pending gateway processing</option>
            <option value="1" <?= ((int)$v['sent'] === 1) ? 'selected' : '' ?>>Mark as sent</option>
          </select>
        </div>

        <div class="mb-field mb-full">
          <label for="phone"><?= $is_edit ? 'Phone number' : 'Phone number(s)' ?> <span class="req">*</span></label>
          <input type="text" required class="form-control" name="phone" id="phone" autocomplete="off"
                 placeholder="<?= $is_edit ? '254712345678' : 'e.g. 254712345678, 254787654321' ?>" value="<?= sms_e($v['phone']) ?>">
          <?php if (!$is_edit) { ?><span class="mb-hint">Separate several numbers with commas. Spaces and dashes are removed automatically.</span><?php } ?>
        </div>

        <div class="mb-field mb-full">
          <label for="sms">SMS message <span class="req">*</span></label>
          <textarea required maxlength="160" class="form-control" rows="4" name="sms" id="sms" placeholder="Write the message here (max 160 characters)"><?= sms_e($v['sms']) ?></textarea>
          <span class="sms-counter" id="sms_counter">0 / 160</span>
        </div>
      </div>

      <div class="mb-actions">
        <?php if ($is_edit) { ?>
        <button type="submit" name="sms_update" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Update</button>
        <input type="hidden" name="logid" value="<?= (int)$v['logid'] ?>">
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Cancel</a>
        <?php } else { ?>
        <button type="submit" name="sms_save" class="btn btn-success btn-sm"><i class="fa fa-paper-plane"></i> Queue &amp; Dispatch</button>
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a>
        <?php } ?>
      </div>

    <?php } ?>
    </div>
  </div>

<?php } else { ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= sms_e($display_title) ?><small><?= ($start_date !== '' || $end_date !== '')
            ? ' ' . sms_e($start_date !== '' ? $start_date : 'start') . ' to ' . sms_e($end_date !== '' ? $end_date : 'today')
            : ' outbox log' ?></small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="index.php?page=<?= $page ?>&amp;part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Compose SMS</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="gs-filter">
        <div class="gs-field gs-wide">
          <label for="search">Search</label>
          <input type="text" class="form-control" name="search" id="search" placeholder="Phone number or message" value="<?= sms_e($search) ?>" autocomplete="off">
        </div>
        <div class="gs-field">
          <label for="f_type">Category</label>
          <select class="form-control" id="f_type" name="smstype">
            <option value="">All</option>
            <?php foreach ($sms_types as $val => $label) { ?>
            <option value="<?= sms_e($val) ?>" <?= ($sel_type === $val) ? 'selected' : '' ?>><?= sms_e($label) ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="gs-field">
          <label for="f_status">Status</label>
          <select class="form-control" id="f_status" name="status">
            <option value="">All</option>
            <option value="sent"    <?= $sel_status === 'sent'    ? 'selected' : '' ?>>Sent</option>
            <option value="pending" <?= $sel_status === 'pending' ? 'selected' : '' ?>>Pending</option>
          </select>
        </div>
        <div class="gs-field">
          <label for="start_date">Scheduled from</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?= sms_e($start_date) ?>">
        </div>
        <div class="gs-field">
          <label for="end_date">Scheduled to</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?= sms_e($end_date) ?>">
        </div>
        <div class="gs-field gs-btn">
          <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Reset</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="smsExportCSV();"><i class="fa fa-download"></i> CSV</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
        </div>
      </div>

      <div class="gs-summary">
        <div class="gs-card blue"><div class="lbl">Messages</div><div class="val"><?= (int)$mycount ?></div></div>
        <div class="gs-card blue"><div class="lbl">Recipients</div><div class="val"><?= (int)$recipients ?></div></div>
        <div class="gs-card"><div class="lbl">Sent</div><div class="val"><?= (int)$cnt['sent'] ?></div></div>
        <div class="gs-card orange"><div class="lbl">Pending</div><div class="val"><?= (int)$cnt['pending'] ?></div></div>
      </div>

      <div class="mb-table-wrapper">
        <table id="sms_table" role="grid" class="table table-striped table-bordered table-sm dataTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Schedule Date</th>
              <th>Category</th>
              <th>Recipient Phone</th>
              <th>Message</th>
              <th>Status</th>
              <th>Created</th>
              <th class="gs-skip" width="1%">Edit</th>
              <th class="gs-skip" width="1%">Del</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($list_error !== '') { ?>
            <tr class="no-export"><td colspan="9" class="mb-error"><?= sms_e($list_error) ?></td></tr>
          <?php } else {
              foreach ($rows as $row) {
                  $is_sent = ((int)($row['sent'] ?? 0) === 1);
                  $d       = sms_dateval($row['date'] ?? '');
                  $dshow   = ($d !== '') ? date('d M Y', strtotime($d)) : '';
                  $cat     = $sms_types[$row['smstype'] ?? ''] ?? ($row['smstype'] ?? ''); ?>
            <tr>
              <td><?= sms_e($row['logid']) ?></td>
              <td><?= sms_e($dshow) ?></td>
              <td><?= sms_e($cat) ?></td>
              <td class="sms-phone"><?= sms_e($row['phone'] ?? '') ?></td>
              <td class="sms-body"><?= sms_e($row['sms'] ?? '') ?></td>
              <td><span class="mb-badge <?= $is_sent ? 'sent' : 'pending' ?>"><?= $is_sent ? 'Sent' : 'Pending' ?></span></td>
              <td><?= sms_e($row['timestamp'] ?? '') ?></td>
              <td class="gs-skip" align="center"><a href="index.php?page=<?= $page ?>&amp;part=2&amp;rec=<?= urlencode($row['logid']) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a></td>
              <td class="gs-skip" align="center"><input name="del[]" type="checkbox" value="<?= sms_e($row['logid']) ?>" aria-label="Select message <?= sms_e($row['logid']) ?>"></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr class="no-export">
              <td colspan="9" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <?php if ($search !== '' || $sel_status !== '' || $sel_type !== '' || $start_date !== '' || $end_date !== '') { ?>
                <i class="fa fa-info-circle fa-lg"></i> No messages match these filters. <a href="index.php?page=<?= $page ?>"><u>Reset filters</u></a>
                <?php } else { ?>
                <i class="fa fa-envelope-o fa-lg"></i> The outbox is empty. Click <a href="index.php?page=<?= $page ?>&amp;part=1"><u>Compose SMS</u></a> to queue a message.
                <?php } ?>
              </td>
            </tr>
          <?php }
          } ?>
          </tbody>
          <tfoot>
            <tr class="gs-total">
              <td colspan="5" class="num">Total messages</td>
              <td colspan="4"><?= (int)$mycount ?></td>
            </tr>
            <tr class="no-print">
              <td colspan="8" style="text-align:right;">With selected items:</td>
              <td><button name="sms_delete" value="Delete" type="submit" class="btn btn-danger btn-sm" title="Delete selected" onclick="return confirm('Please confirm deletion of the checked messages');"><i class="fa fa-trash"></i></button></td>
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
    // Live character counter for the message box
    var box = document.getElementById('sms'), out = document.getElementById('sms_counter');
    if (box && out) {
        var update = function () {
            var n = box.value.length;
            out.textContent = n + ' / 160';
            out.className = 'sms-counter' + (n >= 150 ? ' warn' : '');
        };
        box.addEventListener('input', update);
        update();

        // Message follows the chosen category. It is only replaced while the box is empty
        // or still holds one of the suggested messages, so text you typed yourself is never overwritten.
        var cat = document.getElementById('smstype');
        var templates = <?= json_encode($sms_templates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var isNewEntry = <?= ($part == '1') ? 'true' : 'false' ?>;
        if (cat) {
            var isTemplate = function (t) {
                t = t.trim();
                return Object.keys(templates).some(function (k) { return templates[k] === t; });
            };
            var applyTemplate = function () {
                var t = templates[cat.value];
                if (!t) { return; }
                if (box.value.trim() === '' || isTemplate(box.value)) {
                    box.value = t;
                    update();
                }
            };
            cat.addEventListener('change', applyTemplate);
            if (isNewEntry) { applyTemplate(); }   // pre-fill the new-message form on load
        }
    }
})();

// Export the visible list to CSV (no external libraries; skips Edit / Del columns)
function smsExportCSV() {
    var table = document.getElementById('sms_table');
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