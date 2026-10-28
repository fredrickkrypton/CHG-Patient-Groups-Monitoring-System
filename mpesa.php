<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

global $title;
$conn = $GLOBALS['conn'] ?? null;

$page = "mpesa";

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
    if (isset($_POST['mpesa_delete'])) { caniacess($page . "_delete"); }
}

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */
if (!function_exists('mp_e')) {
    function mp_e($v) { return htmlspecialchars((string)($v === null ? '' : $v), ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('mp_notify')) {
    // PNotify toast plus an inline banner
    function mp_notify($type, $title, $text) {
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

if (!function_exists('mp_fetch_all')) {
    // Returns an array of rows, or false when the query fails
    function mp_fetch_all($conn, $sql, $types = '', $params = array()) {
        if (!$conn) { return false; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('mpesa.php prepare failed: ' . $conn->error); return false; }
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!$stmt->execute()) {
            error_log('mpesa.php execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('mp_exec')) {
    // Returns array(ok, affected_rows, errno, insert_id)
    function mp_exec($conn, $sql, $types, $params) {
        $out = array('ok' => false, 'affected' => 0, 'errno' => 0, 'id' => 0, 'error' => '');
        if (!$conn) { return $out; }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log('mpesa.php prepare failed: ' . $conn->error); $out['errno'] = $conn->errno; $out['error'] = $conn->error; return $out; }
        $stmt->bind_param($types, ...$params);
        $out['ok']       = $stmt->execute();
        $out['affected'] = $stmt->affected_rows;
        $out['errno']    = $stmt->errno;
        $out['id']       = $stmt->insert_id;
        $out['error']    = $stmt->error;
        if (!$out['ok']) { error_log('mpesa.php execute failed: ' . $stmt->error); }
        $stmt->close();
        return $out;
    }
}

// Accepts Y-m-d, "Y-m-d H:i:s" or the 14-digit M-Pesa stamp (YYYYMMDDHHMMSS). Returns the value or false.
if (!function_exists('mp_clean_date')) {
    function mp_clean_date($v) {
        $v = trim((string)$v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})( \d{2}:\d{2}(:\d{2})?)?$/', $v, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) { return $v; }
        if (preg_match('/^(\d{4})(\d{2})(\d{2})\d{6}$/', $v, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) { return $v; }
        return false;
    }
}

// Y-m-d part of any accepted stored date ('' when unknown)
if (!function_exists('mp_ymd')) {
    function mp_ymd($v) {
        $v = trim((string)$v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m)) { return $m[1] . '-' . $m[2] . '-' . $m[3]; }
        if (preg_match('/^(\d{4})(\d{2})(\d{2})\d{6}$/', $v, $m)) { return $m[1] . '-' . $m[2] . '-' . $m[3]; }
        return '';
    }
}

// Friendly display date
if (!function_exists('mp_show_date')) {
    function mp_show_date($v) {
        $v = trim((string)$v);
        if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $v, $m)) { return date('d M Y H:i', strtotime("$m[1]-$m[2]-$m[3] $m[4]:$m[5]:$m[6]")); }
        $ymd = mp_ymd($v);
        if ($ymd === '') { return $v; }
        $t = strtotime($v);
        return (strlen($v) > 10 && $t) ? date('d M Y H:i', $t) : date('d M Y', strtotime($ymd));
    }
}

if (!function_exists('mp_clean_phone')) {
    function mp_clean_phone($p) {
        $p = preg_replace('/[\s\-\(\)\.]/', '', trim((string)$p));
        return preg_match('/^\+?\d{9,15}$/', $p) ? $p : false;
    }
}

if (!function_exists('mp_clean_amount')) {
    function mp_clean_amount($a) {
        $a = str_replace(',', '', trim((string)$a));
        return (is_numeric($a) && (float)$a > 0) ? round((float)$a, 2) : false;
    }
}

if (!function_exists('mp_clean_receipt')) {
    function mp_clean_receipt($r) {
        $r = strtoupper(trim((string)$r));
        return preg_match('/^[A-Z0-9]{6,20}$/', $r) ? $r : false;
    }
}

if (!function_exists('mp_validate')) {
    // Returns array(error, date, phone, amount, receipt)
    function mp_validate($d, $p, $a, $r) {
        $date = mp_clean_date($d); $phone = mp_clean_phone($p); $amt = mp_clean_amount($a); $rcpt = mp_clean_receipt($r);
        $err = '';
        if ($date === false)      { $err = 'Enter a valid transaction date.'; }
        elseif ($phone === false) { $err = 'Enter a valid phone number (9 to 15 digits, e.g. 254712345678).'; }
        elseif ($amt === false)   { $err = 'Enter an amount greater than zero.'; }
        elseif ($rcpt === false)  { $err = 'Receipt reference must be 6 to 20 letters or digits.'; }
        return array($err, $date, $phone, $amt, $rcpt);
    }
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// Make sure the mpesa table exists (once per login session; harmless if it already exists)
// ---------------------------------------------------------------------------
if ($conn && empty($_SESSION['mpesa_table_ok'])) {
    $conn->query("CREATE TABLE IF NOT EXISTS `mpesa` (
        `logid`              INT(11)       NOT NULL AUTO_INCREMENT,
        `transactiondate`    VARCHAR(20)   DEFAULT NULL,
        `phonenumber`        VARCHAR(20)   NOT NULL,
        `amount`             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `mpesareceiptnumber` VARCHAR(30)   NOT NULL,
        `username`           VARCHAR(100)  DEFAULT NULL,
        `timestamp`          DATETIME      DEFAULT NULL,
        PRIMARY KEY (`logid`),
        UNIQUE KEY `uniq_mpesa_receipt` (`mpesareceiptnumber`),
        KEY `idx_mpesa_phone` (`phonenumber`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION['mpesa_table_ok'] = 1; }
}

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['mpesa_save'])) {
    list($error, $date, $phone, $amount, $receipt) = mp_validate($_POST['transactiondate'] ?? '', $_POST['phonenumber'] ?? '', $_POST['amount'] ?? '', $_POST['mpesareceiptnumber'] ?? '');
    if ($error === '') {
        $res = mp_exec($conn, "INSERT INTO mpesa (transactiondate, phonenumber, amount, mpesareceiptnumber, username, `timestamp`) VALUES (?,?,?,?,?,?)",
                       'ssdsss', array($date, $phone, $amount, $receipt, $username, $timestamp));
        if ($res['ok']) {
            $alert = 'Deposit record added successfully!';
            if (function_exists('audittrail')) { audittrail($conn, 'mpesa', $res['id'], 'inserting', $timestamp, $username); }
        } elseif ($res['errno'] == 1062) {
            $error = 'This receipt reference is already recorded.';
        } else {
            $error = 'The deposit could not be saved. Database said: ' . ($res['error'] !== '' ? $res['error'] : 'unknown error');
        }
    }
    if ($alert !== '') { mp_notify('success', 'Success', $alert); $part = ''; }
    if ($error !== '') { mp_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['mpesa_update'])) {
    $logid = (int)($_POST['logid'] ?? 0);
    list($error, $date, $phone, $amount, $receipt) = mp_validate($_POST['transactiondate'] ?? '', $_POST['phonenumber'] ?? '', $_POST['amount'] ?? '', $_POST['mpesareceiptnumber'] ?? '');
    if ($error === '' && $logid <= 0) { $error = 'Transaction not found.'; }
    if ($error === '') {
        $res = mp_exec($conn, "UPDATE mpesa SET transactiondate=?, phonenumber=?, amount=?, mpesareceiptnumber=? WHERE logid=?",
                       'ssdsi', array($date, $phone, $amount, $receipt, $logid));
        if ($res['ok']) {
            $alert = 'Transaction updated successfully!';
            if (function_exists('audittrail')) { audittrail($conn, 'mpesa', $logid, 'updatings', $timestamp, $username); }
        } elseif ($res['errno'] == 1062) {
            $error = 'Another transaction already uses this receipt reference.';
        } else {
            $error = 'The transaction could not be updated. Database said: ' . ($res['error'] !== '' ? $res['error'] : 'unknown error');
        }
    }
    if ($alert !== '') { mp_notify('success', 'Success', $alert); $part = ''; }
    if ($error !== '') { mp_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['mpesa_delete'])) {
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['del'] ?? array()))));
    foreach ($ids as $del_id) {
        $res = mp_exec($conn, "DELETE FROM mpesa WHERE logid=?", 'i', array($del_id));
        if ($res['ok'] && $res['affected'] > 0) {
            $deleted++;
            if (function_exists('audittrail')) { audittrail($conn, 'mpesa', $del_id, 'deleting', $timestamp, $username); }
        }
    }
    if ($deleted > 0) { mp_notify('success', 'Success', 'Transaction(s) removed.'); }
    else              { mp_notify('error', 'Error', 'No record selected'); }
}

$display_title = 'Deposits Tracking';
if (function_exists('_title')) { _title($title = $display_title); } else { $title = $display_title; }

// ---------------------------------------------------------------------------
// FORM VALUES (add / edit)
// ---------------------------------------------------------------------------
$v = ['logid' => '', 'transactiondate' => $today, 'phonenumber' => '', 'amount' => '', 'mpesareceiptnumber' => '', 'username' => $username];
$edit_found = false;

if ($part == '1' && isset($_POST['mpesa_save'])) {            // keep what was typed after an error
    foreach (['transactiondate', 'phonenumber', 'amount', 'mpesareceiptnumber'] as $k) { $v[$k] = $_POST[$k] ?? $v[$k]; }
}
if ($part == '2') {
    $found_rows = mp_fetch_all($conn, "SELECT * FROM mpesa WHERE logid=?", 'i', array((int)$rec));
    if ($found_rows) {
        $edit_found = true;
        foreach ($v as $k => $_) { if (isset($found_rows[0][$k])) { $v[$k] = $found_rows[0][$k]; } }
        if (isset($_POST['mpesa_update'])) {
            foreach (['transactiondate', 'phonenumber', 'amount', 'mpesareceiptnumber'] as $k) { $v[$k] = $_POST[$k] ?? $v[$k]; }
        }
    }
}
// Date input type: a real date picker for plain dates, text for M-Pesa 14-digit stamps so nothing is lost
$date_is_plain = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v['transactiondate']);

// ---------------------------------------------------------------------------
// LIST: filters, data, summary
// ---------------------------------------------------------------------------
$rows = []; $mycount = 0; $list_error = '';
$search = ''; $sel_payer = ''; $start_date = ''; $end_date = '';
$total_amount = 0.0; $mapped = 0; $unmapped = 0; $csv_name = 'deposits';

if ($part != '1' && $part != '2') {
    $is_date = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

    $search     = trim((string)($_POST['search'] ?? ''));
    $start_date = (isset($_POST['start_date']) && $is_date($_POST['start_date'])) ? $_POST['start_date'] : '';
    $end_date   = (isset($_POST['end_date'])   && $is_date($_POST['end_date']))   ? $_POST['end_date']   : '';
    if ($start_date !== '' && $end_date !== '' && $start_date > $end_date) { $tmp = $start_date; $start_date = $end_date; $end_date = $tmp; }
    $sel_payer  = in_array($_POST['payer'] ?? '', ['member', 'unmapped'], true) ? $_POST['payer'] : '';

    // Comparable YYYYMMDD from either date format stored in transactiondate
    $dexpr = "LEFT(REPLACE(REPLACE(p.transactiondate,'-',''),' ',''),8)";
    $where = []; $types = ''; $params = [];
    if ($start_date !== '') { $where[] = "$dexpr >= ?"; $types .= 's'; $params[] = str_replace('-', '', $start_date); }
    if ($end_date !== '')   { $where[] = "$dexpr <= ?"; $types .= 's'; $params[] = str_replace('-', '', $end_date); }
    if ($sel_payer === 'member')   { $where[] = "EXISTS (SELECT 1 FROM `member` mm WHERE mm.phone_no = p.phonenumber)"; }
    if ($sel_payer === 'unmapped') { $where[] = "NOT EXISTS (SELECT 1 FROM `member` mm WHERE mm.phone_no = p.phonenumber)"; }
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = "(p.phonenumber LIKE ? OR p.mpesareceiptnumber LIKE ? OR p.username LIKE ? OR EXISTS (SELECT 1 FROM `member` mm WHERE mm.phone_no = p.phonenumber AND mm.name LIKE ?))";
        $types  .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }

    $fetched = mp_fetch_all(
        $conn,
        "SELECT p.*, (SELECT mm.name FROM `member` mm WHERE mm.phone_no = p.phonenumber LIMIT 1) AS member_name
           FROM mpesa p"
        . ($where ? " WHERE " . implode(" AND ", $where) : "")
        . " ORDER BY p.logid DESC LIMIT 500",
        $types,
        $params
    );
    if ($fetched === false) {
        $list_error = 'SQL error: ' . ($conn ? $conn->error : 'No database connection');
    } else {
        $rows    = $fetched;
        $mycount = count($rows);
        foreach ($rows as $r) {
            $total_amount += (float)($r['amount'] ?? 0);
            if (($r['member_name'] ?? '') !== '' && $r['member_name'] !== null) { $mapped++; } else { $unmapped++; }
        }
    }

    if ($start_date !== '' || $end_date !== '') {
        $csv_name = 'deposits_' . ($start_date !== '' ? $start_date : 'start') . '_to_' . ($end_date !== '' ? $end_date : 'today');
    }
}

// The list posts to a clean URL so filters never re-open the add form
$form_action = ($part == '1' || $part == '2') ? '' : 'index.php?page=' . $page;
?>

<style>
/* ---------- Panel ---------- */
form[name='mpesa'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='mpesa'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;
}
form[name='mpesa'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='mpesa'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='mpesa'] .x_title .clearfix { display: none; }
form[name='mpesa'] .x_content { padding: 24px 28px; }
form[name='mpesa'] .panel_toolbox {
    list-style: none; margin: 0 0 0 auto; padding: 0; float: none;
    display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 8px;
}

/* ---------- Form grid ---------- */
form[name='mpesa'] .mb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px 28px;
}
form[name='mpesa'] .mb-field label {
    display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='mpesa'] .mb-field label .req { color: #e74c3c; }
form[name='mpesa'] .form-control {
    width: 100%; height: 38px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='mpesa'] .form-control::placeholder { color: #9bbcf0; }
form[name='mpesa'] .form-control:focus {
    border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38, 185, 154, .18); outline: none;
}
form[name='mpesa'] .mb-field .form-control:required { border-left: 4px solid #e74c3c; }
form[name='mpesa'] .mb-field .form-control:required:focus { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231, 76, 60, .15); }
form[name='mpesa'] .form-control[readonly] { background: #f7f9fc; color: #8a94a6; cursor: default; }
form[name='mpesa'] .mb-upper { text-transform: uppercase; }
form[name='mpesa'] .mb-hint { display: block; margin-top: 4px; font-size: .75rem; color: #8a94a6; }
form[name='mpesa'] .mb-actions { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e3e8ee; display: flex; gap: 10px; flex-wrap: wrap; }

/* ---------- Buttons ---------- */
form[name='mpesa'] .btn {
    border-radius: 6px; padding: .45em 1.4em; font-weight: 600; letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='mpesa'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='mpesa'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='mpesa'] .btn-success:hover { background: #1f9c82; }
form[name='mpesa'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='mpesa'] .btn-primary:hover { background: #1558b8; }
form[name='mpesa'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='mpesa'] .btn-secondary:hover { background: #6f7a8d; }
form[name='mpesa'] .btn-danger    { background: #e74c3c; border-color: #e74c3c; color: #fff; }
form[name='mpesa'] .btn-danger:hover { background: #c0392b; }
form[name='mpesa'] .btn:focus-visible,
form[name='mpesa'] a:focus-visible { outline: 2px solid #1a73e8; outline-offset: 2px; }

/* ---------- Filter bar ---------- */
form[name='mpesa'] .gs-filter {
    display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;
    background: #f7f9fc; border: 1px solid #e3e8ee; border-radius: 8px;
    padding: 14px 16px; margin-bottom: 18px;
}
form[name='mpesa'] .gs-field { flex: 1 1 150px; min-width: 130px; }
form[name='mpesa'] .gs-field.gs-wide { flex: 2 1 220px; }
form[name='mpesa'] .gs-field label {
    display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='mpesa'] .gs-field .form-control { height: 36px; }
form[name='mpesa'] .gs-field.gs-btn { flex: 0 0 auto; display: flex; gap: 8px; flex-wrap: wrap; }
form[name='mpesa'] .gs-field.gs-btn .btn {
    height: 36px; padding: 0 1.2em; display: inline-flex; align-items: center; gap: 6px;
}

/* ---------- Summary cards ---------- */
form[name='mpesa'] .gs-summary { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; }
form[name='mpesa'] .gs-card {
    flex: 1 1 150px; background: #fff; border: 1px solid #e3e8ee;
    border-left: 4px solid #26b99a; border-radius: 8px; padding: 12px 16px;
}
form[name='mpesa'] .gs-card.blue   { border-left-color: #1a73e8; }
form[name='mpesa'] .gs-card.orange { border-left-color: #f0a030; }
form[name='mpesa'] .gs-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
form[name='mpesa'] .gs-card .val { font-size: 1.3rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }

/* ---------- Table ---------- */
form[name='mpesa'] .mb-table-wrapper {
    width: 100%; overflow-x: auto; background: #fff;
    border: 1px solid #e3e8ee; border-radius: 8px; padding: 10px;
}
form[name='mpesa'] .mb-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
form[name='mpesa'] .mb-table-wrapper th,
form[name='mpesa'] .mb-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
form[name='mpesa'] .mb-table-wrapper td.mp-code { font-family: Consolas, monospace; font-weight: 700; }
form[name='mpesa'] .mb-table-wrapper thead th,
form[name='mpesa'] .mb-table-wrapper tfoot th,
form[name='mpesa'] .mb-table-wrapper tfoot td {
    background: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
form[name='mpesa'] .mb-table-wrapper tfoot tr.gs-total td { background: #2a3f54 !important; color: #fff !important; border-bottom: 0 !important; }
form[name='mpesa'] .mb-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
form[name='mpesa'] .mb-table-wrapper a { color: #1a73e8; font-weight: 600; }
form[name='mpesa'] .mb-table-wrapper .fa-pencil:hover { color: #1f9c82 !important; }
form[name='mpesa'] .mb-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
form[name='mpesa'] .mb-table-wrapper .num { text-align: right; }
form[name='mpesa'] .mb-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; }
form[name='mpesa'] .mb-badge.member   { background: #26b99a; }
form[name='mpesa'] .mb-badge.unmapped { background: #f0a030; }
form[name='mpesa'] .mb-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='mpesa'] .x_content { padding: 16px 14px; }
    form[name='mpesa'] .gs-field,
    form[name='mpesa'] .gs-field.gs-wide,
    form[name='mpesa'] .gs-field.gs-btn { flex: 1 1 100%; }
    form[name='mpesa'] .gs-field.gs-btn .btn { flex: 1; justify-content: center; }
}
@media (prefers-reduced-motion: reduce) {
    form[name='mpesa'] .btn, form[name='mpesa'] .form-control { transition: none; }
    form[name='mpesa'] .btn:hover { transform: none; }
}

/* ---------- Print ---------- */
@media print {
    form[name='mpesa'] .gs-filter,
    form[name='mpesa'] .panel_toolbox,
    form[name='mpesa'] .gs-skip,
    form[name='mpesa'] tr.no-print { display: none !important; }
    form[name='mpesa'] .x_panel { box-shadow: none; border: 0; }
    form[name='mpesa'] .mb-table-wrapper { overflow: visible; border: 0; }
}
</style>

<form method="POST" action="<?= mp_e($form_action) ?>" class="rounded" name="mpesa" style="min-width:100%" enctype="multipart/form-data">
<div class="col-md-12 col-sm-12 col-xs-12">

<?php if ($part == '1' || $part == '2') {
    $is_edit = ($part == '2'); ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= mp_e($display_title) ?><small><?= $is_edit ? ' update entry' : ' new entry' ?></small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
    <?php if ($is_edit && !$edit_found) { ?>
      <div class="mb-error">Transaction not found.</div>
      <div class="mb-actions"><a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a></div>
    <?php } else { ?>

      <div class="mb-grid">
        <div class="mb-field">
          <label for="transactiondate">Transaction date <span class="req">*</span></label>
          <?php if ($date_is_plain) { ?>
          <input type="date" required class="form-control" name="transactiondate" id="transactiondate" value="<?= mp_e($v['transactiondate']) ?>">
          <?php } else { ?>
          <input type="text" required class="form-control" name="transactiondate" id="transactiondate" value="<?= mp_e($v['transactiondate']) ?>" autocomplete="off">
          <span class="mb-hint">Accepted: YYYY-MM-DD or the M-Pesa stamp YYYYMMDDHHMMSS.</span>
          <?php } ?>
        </div>

        <div class="mb-field">
          <label for="phonenumber">Phone number <span class="req">*</span></label>
          <input type="text" required class="form-control" name="phonenumber" id="phonenumber" placeholder="e.g. 256712345678" autocomplete="off" value="<?= mp_e($v['phonenumber']) ?>">
        </div>

        <div class="mb-field">
          <label for="amount">Amount deposited <span class="req">*</span></label>
          <input type="number" step="0.01" min="0.01" required class="form-control" name="amount" id="amount" placeholder="0.00" value="<?= mp_e($v['amount']) ?>">
        </div>

        <div class="mb-field">
          <label for="mpesareceiptnumber">Receipt reference ID <span class="req">*</span></label>
          <input type="text" required class="form-control mb-upper" name="mpesareceiptnumber" id="mpesareceiptnumber" placeholder="e.g. QX789JK12L" autocomplete="off" maxlength="20" value="<?= mp_e($v['mpesareceiptnumber']) ?>">
          <span class="mb-hint">6 to 20 letters or digits. Saved in capitals and must be unique.</span>
        </div>

        <div class="mb-field">
          <label for="handled_by">Handled by</label>
          <input type="text" readonly class="form-control" id="handled_by" value="<?= mp_e($is_edit ? $v['username'] : $username) ?>">
        </div>
      </div>

      <div class="mb-actions">
        <?php if ($is_edit) { ?>
        <button type="submit" name="mpesa_update" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Update</button>
        <input type="hidden" name="logid" value="<?= (int)$v['logid'] ?>">
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Cancel</a>
        <?php } else { ?>
        <button type="submit" name="mpesa_save" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Save</button>
        <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Back</a>
        <?php } ?>
      </div>

    <?php } ?>
    </div>
  </div>

<?php } else { ?>
  <div class="x_panel">
    <div class="x_title">
      <h2><?= mp_e($display_title) ?><small><?= ($start_date !== '' || $end_date !== '')
            ? ' ' . mp_e($start_date !== '' ? $start_date : 'start') . ' to ' . mp_e($end_date !== '' ? $end_date : 'today')
            : ' transaction logs' ?></small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="index.php?page=<?= $page ?>&amp;part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Add Deposit</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="gs-filter">
        <div class="gs-field gs-wide">
          <label for="search">Search</label>
          <input type="text" class="form-control" name="search" id="search" placeholder="Member, phone, receipt or user" value="<?= mp_e($search) ?>" autocomplete="off">
        </div>
        <div class="gs-field">
          <label for="f_payer">Payer</label>
          <select class="form-control" id="f_payer" name="payer">
            <option value="">All</option>
            <option value="member"   <?= $sel_payer === 'member'   ? 'selected' : '' ?>>Registered members</option>
            <option value="unmapped" <?= $sel_payer === 'unmapped' ? 'selected' : '' ?>>Unmapped numbers</option>
          </select>
        </div>
        <div class="gs-field">
          <label for="start_date">Paid from</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?= mp_e($start_date) ?>">
        </div>
        <div class="gs-field">
          <label for="end_date">Paid to</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?= mp_e($end_date) ?>">
        </div>
        <div class="gs-field gs-btn">
          <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Reset</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="mpExportCSV();"><i class="fa fa-download"></i> CSV</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
        </div>
      </div>

      <div class="gs-summary">
        <div class="gs-card blue"><div class="lbl">Transactions</div><div class="val"><?= (int)$mycount ?></div></div>
        <div class="gs-card"><div class="lbl">Total amount</div><div class="val"><?= number_format($total_amount, 2) ?></div></div>
        <div class="gs-card blue"><div class="lbl">From members</div><div class="val"><?= (int)$mapped ?></div></div>
        <div class="gs-card orange"><div class="lbl">Unmapped</div><div class="val"><?= (int)$unmapped ?></div></div>
      </div>

      <div class="mb-table-wrapper">
        <table id="mpesa_table" role="grid" class="table table-striped table-bordered table-sm dataTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Transaction Date</th>
              <th>Payer / Member</th>
              <th class="num">Amount</th>
              <th>Receipt Ref</th>
              <th>Allocation</th>
              <th>Handled By</th>
              <th>Recorded</th>
              <th class="gs-skip" width="1%">Edit</th>
              <th class="gs-skip" width="1%">Del</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($list_error !== '') { ?>
            <tr class="no-export"><td colspan="10" class="mb-error"><?= mp_e($list_error) ?></td></tr>
          <?php } else {
              foreach ($rows as $row) {
                  $has_member = (($row['member_name'] ?? '') !== '' && $row['member_name'] !== null); ?>
            <tr>
              <td><?= mp_e($row['logid']) ?></td>
              <td><?= mp_e(mp_show_date($row['transactiondate'] ?? '')) ?></td>
              <td>
                <?php if ($has_member) { ?>
                  <strong><?= mp_e($row['member_name']) ?></strong> (<?= mp_e($row['phonenumber']) ?>)
                <?php } else { ?>
                  <span class="mb-badge unmapped">Unmapped</span> <?= mp_e($row['phonenumber']) ?>
                <?php } ?>
              </td>
              <td class="num"><strong><?= number_format((float)($row['amount'] ?? 0), 2) ?></strong></td>
              <td class="mp-code"><?= mp_e($row['mpesareceiptnumber'] ?? '') ?></td>
              <td><span class="mb-badge member">Contribution / Premium</span></td>
              <td><?= mp_e($row['username'] ?? '') ?></td>
              <td><?= mp_e($row['timestamp'] ?? '') ?></td>
              <td class="gs-skip" align="center"><a href="index.php?page=<?= $page ?>&amp;part=2&amp;rec=<?= urlencode($row['logid']) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a></td>
              <td class="gs-skip" align="center"><input name="del[]" type="checkbox" value="<?= mp_e($row['logid']) ?>" aria-label="Select transaction <?= mp_e($row['logid']) ?>"></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr class="no-export">
              <td colspan="10" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <?php if ($search !== '' || $sel_payer !== '' || $start_date !== '' || $end_date !== '') { ?>
                <i class="fa fa-info-circle fa-lg"></i> No transactions match these filters. <a href="index.php?page=<?= $page ?>"><u>Reset filters</u></a>
                <?php } else { ?>
                <i class="fa fa-info-circle fa-lg"></i> No deposits logged yet. Click <a href="index.php?page=<?= $page ?>&amp;part=1"><u>Add Deposit</u></a> to record one.
                <?php } ?>
              </td>
            </tr>
          <?php }
          } ?>
          </tbody>
          <tfoot>
            <tr class="gs-total">
              <td colspan="3" class="num">Total</td>
              <td class="num"><?= number_format($total_amount, 2) ?></td>
              <td colspan="6"><?= (int)$mycount ?> transaction<?= $mycount == 1 ? '' : 's' ?></td>
            </tr>
            <tr class="no-print">
              <td colspan="9" style="text-align:right;">With selected items:</td>
              <td><button name="mpesa_delete" value="Delete" type="submit" class="btn btn-danger btn-sm" title="Delete selected" onclick="return confirm('Please confirm deletion of the checked transactions');"><i class="fa fa-trash"></i></button></td>
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
    // Receipt codes are always stored in capitals
    var r = document.getElementById('mpesareceiptnumber');
    if (r) { r.addEventListener('input', function () { var p = r.selectionStart; r.value = r.value.toUpperCase(); r.setSelectionRange(p, p); }); }
})();

// Export the visible list to CSV (no external libraries; skips Edit / Del columns)
function mpExportCSV() {
    var table = document.getElementById('mpesa_table');
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