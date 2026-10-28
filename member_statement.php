<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:33:37
//
// Member Contribution Summary = read-only statement for one member:
// member details + every M-Pesa payment (optionally within a date range).

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

$conn = $conn ?? ($GLOBALS['conn'] ?? null);
$page = "member_statement";

if (function_exists('workout') && isset($_GET['D'])) {
    workout($_GET['D']);
}
if (function_exists('caniacess')) { caniacess($page . "_view"); }

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

if (function_exists('_title')) { _title($title = 'Member Contribution Summary'); } else { $title = 'Member Contribution Summary'; }

// ---------------------------------------------------------------------------
// FILTER INPUTS
// ---------------------------------------------------------------------------
$is_date = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

$is_group_leader_role = (isset($_SESSION['userrole']) && $_SESSION['userrole'] == 4);   // role 4 = own group only
$own_group = $is_group_leader_role ? (int)($_SESSION['groupid'] ?? 0) : 0;

$sel_group  = $is_group_leader_role ? $own_group : (int)($_POST['group_id'] ?? 0);
$sel_member = (int)($_POST['member'] ?? 0);
$start_date = (isset($_POST['start_date']) && $is_date($_POST['start_date'])) ? $_POST['start_date'] : '';
$end_date   = (isset($_POST['end_date'])   && $is_date($_POST['end_date']))   ? $_POST['end_date']   : '';
if ($start_date !== '' && $end_date !== '' && $start_date > $end_date) { $tmp = $start_date; $start_date = $end_date; $end_date = $tmp; }

$sql_error = '';

// ---------------------------------------------------------------------------
// DATA
// ---------------------------------------------------------------------------
// groups
$group_options = [];
if ($is_group_leader_role) {
    $st = $conn->prepare("SELECT logid, name FROM provider_group WHERE logid=? ORDER BY name");
    $st->bind_param("i", $own_group);
} else {
    $st = $conn->prepare("SELECT logid, name FROM provider_group WHERE logid>0 ORDER BY name");
}
if ($st) {
    $st->execute();
    $rs = $st->get_result();
    while ($g = $rs->fetch_assoc()) { $group_options[] = $g; }
    $st->close();
} else { $sql_error = $conn->error; }

// members of the selected group
$member_options = [];
if ($sql_error === '' && $sel_group > 0) {
    $st = $conn->prepare("SELECT logid, `name` FROM `member` WHERE group_id=? ORDER BY `name`");
    if ($st) {
        $st->bind_param("i", $sel_group);
        $st->execute();
        $rs = $st->get_result();
        while ($m = $rs->fetch_assoc()) { $member_options[] = $m; }
        $st->close();
    } else { $sql_error = $conn->error; }
}

// the chosen member (role 4 can only open members of their own group)
$member = null;
$tx = [];
$tot_all = 0; $tot_reg = 0; $tot_con = 0;

if ($sql_error === '' && $sel_member > 0) {
    $sql = "SELECT m.logid, m.mnumber, m.`name`, m.phone_no, m.nat_id, m.activation_dt, m.group_id, g.`name` AS groupname
              FROM `member` m
              JOIN provider_group g ON m.group_id = g.logid
             WHERE m.logid = ?" . ($is_group_leader_role ? " AND m.group_id = ?" : "");
    $st = $conn->prepare($sql);
    if ($st) {
        if ($is_group_leader_role) { $st->bind_param("ii", $sel_member, $own_group); }
        else                       { $st->bind_param("i",  $sel_member); }
        $st->execute();
        $member = $st->get_result()->fetch_assoc();
        $st->close();
    } else { $sql_error = $conn->error; }

    if ($member && $sql_error === '') {
        $types = "i"; $params = [$sel_member];
        $sql = "SELECT transactiondate, mpesareceiptnumber, amount, ctype FROM mpesa WHERE memberid = ?";
        if ($start_date !== '') { $sql .= " AND transactiondate >= ?"; $types .= "s"; $params[] = $start_date . " 00:00:00"; }
        if ($end_date   !== '') { $sql .= " AND transactiondate <= ?"; $types .= "s"; $params[] = $end_date   . " 23:59:59"; }
        $sql .= " ORDER BY transactiondate";
        $st = $conn->prepare($sql);
        if ($st) {
            $st->bind_param($types, ...$params);
            if ($st->execute()) {
                $rs = $st->get_result();
                while ($t = $rs->fetch_assoc()) {
                    $t['amount'] = (float)($t['amount'] ?? 0);
                    $tot_all += $t['amount'];
                    if ((string)$t['ctype'] === '0') { $tot_reg += $t['amount']; }
                    elseif ((string)$t['ctype'] === '1') { $tot_con += $t['amount']; }
                    $tx[] = $t;
                }
            } else { $sql_error = $st->error; }
            $st->close();
        } else { $sql_error = $conn->error; }
    }
}
?>

<style>
/* ---------- Panel ---------- */
form[name='member_statement'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='member_statement'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='member_statement'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='member_statement'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='member_statement'] .x_content { padding: 20px 28px; }

/* ---------- Filter bar ---------- */
form[name='member_statement'] .ms-filter {
    display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;
    background: #f7f9fc; border: 1px solid #e3e8ee; border-radius: 8px;
    padding: 14px 16px; margin-bottom: 18px;
}
form[name='member_statement'] .ms-field { flex: 1 1 170px; min-width: 150px; }
form[name='member_statement'] .ms-field label {
    display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='member_statement'] .ms-field.ms-btn { flex: 0 0 auto; display: flex; gap: 8px; flex-wrap: wrap; }
form[name='member_statement'] .form-control {
    width: 100%; height: 36px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='member_statement'] .form-control:focus {
    border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38, 185, 154, .18); outline: none;
}

/* ---------- Buttons ---------- */
form[name='member_statement'] .btn {
    height: 36px; border-radius: 6px; padding: 0 1.2em; font-weight: 600; letter-spacing: .03em;
    display: inline-flex; align-items: center; gap: 6px;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='member_statement'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='member_statement'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='member_statement'] .btn-success:hover { background: #1f9c82; }
form[name='member_statement'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='member_statement'] .btn-secondary:hover { background: #6f7a8d; }
form[name='member_statement'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='member_statement'] .btn-primary:hover { background: #1558b8; }

/* ---------- Statement ---------- */
.ms-info {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px;
    background: #f7f9fc; border: 1px solid #e3e8ee; border-radius: 8px;
    padding: 14px 16px; margin-bottom: 16px;
}
.ms-info .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
.ms-info .val { font-size: 1rem; font-weight: 700; color: #2a3f54; margin-top: 2px; word-break: break-word; }
.ms-summary { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 16px; }
.ms-card {
    flex: 1 1 160px; background: #fff; border: 1px solid #e3e8ee;
    border-left: 4px solid #26b99a; border-radius: 8px; padding: 10px 16px;
}
.ms-card.blue { border-left-color: #1a73e8; }
.ms-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
.ms-card .val { font-size: 1.2rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }

.ms-table-wrapper {
    width: 100%; overflow-x: auto; background: #fff;
    border: 1px solid #e3e8ee; border-radius: 8px; padding: 10px;
}
.ms-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
.ms-table-wrapper th, .ms-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
.ms-table-wrapper thead th {
    background: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
.ms-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
.ms-table-wrapper .num { text-align: right; }
.ms-table-wrapper tfoot td { background: #2a3f54 !important; color: #fff !important; font-weight: 700 !important; }
.ms-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; background: #8a94a6; }
.ms-badge.reg { background: #1a73e8; }
.ms-badge.con { background: #26b99a; }
.ms-hint { padding: 26px; text-align: center; color: #73879C; font-size: 14px; }
.ms-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='member_statement'] .x_content { padding: 14px; }
    form[name='member_statement'] .ms-field,
    form[name='member_statement'] .ms-field.ms-btn { flex: 1 1 100%; }
    form[name='member_statement'] .ms-field.ms-btn .btn { flex: 1; justify-content: center; }
}

/* ---------- Print: only the statement ---------- */
@media print {
    body * { visibility: hidden; }
    #printableArea, #printableArea * { visibility: visible; }
    #printableArea { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<form method="POST" action="" class="rounded" name="member_statement" style="min-width:100%">
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> member statement</small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="ms-filter">
        <div class="ms-field">
          <label for="group_id">Group</label>
          <select class="form-control" id="group_id" name="group_id" onchange="document.getElementById('member').selectedIndex = 0; this.form.submit();">
            <?php if (!$is_group_leader_role) { ?><option value="">Select Group</option><?php } ?>
            <?php foreach ($group_options as $g) { ?>
            <option value="<?= htmlspecialchars($g['logid']) ?>" <?= ((int)$g['logid'] === $sel_group) ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="ms-field">
          <label for="member">Member</label>
          <select class="form-control" id="member" name="member" required>
            <option value="">Select Member</option>
            <?php foreach ($member_options as $m) { ?>
            <option value="<?= htmlspecialchars($m['logid']) ?>" <?= ((int)$m['logid'] === $sel_member) ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="ms-field">
          <label for="start_date">From (optional)</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="ms-field">
          <label for="end_date">To (optional)</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="ms-field ms-btn">
          <button type="submit" name="button" class="btn btn-success btn-sm"><i class="fa fa-search"></i> View</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Reset</a>
          <?php if ($member) { ?>
          <button type="button" class="btn btn-primary btn-sm" onclick="msExportCSV();"><i class="fa fa-download"></i> CSV</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
          <?php } ?>
        </div>
      </div>

      <?php if ($sql_error !== '') { ?>
        <div class="ms-error">Query error: <?= htmlspecialchars($sql_error) ?></div>

      <?php } elseif ($sel_member > 0 && !$member) { ?>
        <div class="ms-hint"><i class="fa fa-exclamation-circle fa-lg"></i> Member not found, or you do not have access to this member.</div>

      <?php } elseif (!$member) { ?>
        <div class="ms-hint"><i class="fa fa-info-circle fa-lg"></i> Select a group and a member, then press <strong>View</strong>.</div>

      <?php } else { ?>
      <div id="printableArea">

        <div class="ms-info">
          <div><div class="lbl">Name</div><div class="val"><?= htmlspecialchars(strtoupper($member['name'] ?? '')) ?></div></div>
          <div><div class="lbl">Member No</div><div class="val"><?= htmlspecialchars($member['mnumber'] ?? '-') ?></div></div>
          <div><div class="lbl">Phone</div><div class="val"><?= htmlspecialchars(($member['phone_no'] ?? '') !== '' ? $member['phone_no'] : '-') ?></div></div>
          <div><div class="lbl">National ID</div><div class="val"><?= htmlspecialchars(($member['nat_id'] ?? '') !== '' ? $member['nat_id'] : '-') ?></div></div>
          <div><div class="lbl">Group</div><div class="val"><?= htmlspecialchars(strtoupper($member['groupname'] ?? '')) ?></div></div>
          <div><div class="lbl">Activation Date</div><div class="val"><?= htmlspecialchars(($member['activation_dt'] ?? '') !== '' ? $member['activation_dt'] : '-') ?></div></div>
          <?php if ($start_date !== '' || $end_date !== '') { ?>
          <div><div class="lbl">Period</div><div class="val"><?= htmlspecialchars(($start_date ?: 'start') . ' to ' . ($end_date ?: 'today')) ?></div></div>
          <?php } ?>
        </div>

        <div class="ms-summary">
          <div class="ms-card blue"><div class="lbl">Payments</div><div class="val"><?= count($tx) ?></div></div>
          <div class="ms-card"><div class="lbl">Registration</div><div class="val"><?= number_format($tot_reg, 2) ?></div></div>
          <div class="ms-card"><div class="lbl">Contribution</div><div class="val"><?= number_format($tot_con, 2) ?></div></div>
          <div class="ms-card blue"><div class="lbl">Total paid</div><div class="val"><?= number_format($tot_all, 2) ?></div></div>
        </div>

        <div class="ms-table-wrapper">
          <table id="ms_table" role="grid" class="table table-striped table-bordered table-sm">
            <thead>
              <tr>
                <th>Transaction Date</th>
                <th>Reference</th>
                <th>Type</th>
                <th class="num">Amount</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($tx as $t) {
                $ct = (string)($t['ctype'] ?? '');
                $tl = $ct === '0' ? 'Registration' : ($ct === '1' ? 'Contribution' : '-');
                $tc = $ct === '0' ? 'reg' : ($ct === '1' ? 'con' : ''); ?>
              <tr>
                <td><?= htmlspecialchars($t['transactiondate'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['mpesareceiptnumber'] ?? '') ?></td>
                <td><span class="ms-badge <?= $tc ?>"><?= $tl ?></span></td>
                <td class="num"><?= number_format($t['amount'], 2) ?></td>
              </tr>
            <?php } ?>
            <?php if (!$tx) { ?>
              <tr><td colspan="4" class="text-center" style="padding:26px; color:#73879C;">No payments found for this member<?= ($start_date !== '' || $end_date !== '') ? ' in this period' : '' ?>.</td></tr>
            <?php } ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="num">Total</td>
                <td class="num"><?= number_format($tot_all, 2) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

      </div>
      <?php } ?>

    </div>
  </div>
</div>
</form>

<script>
// Export the payments table to CSV (no external libraries needed)
function msExportCSV() {
    var table = document.getElementById('ms_table');
    if (!table) { return; }
    var lines = [];
    table.querySelectorAll('tr').forEach(function (tr) {
        var cells = [];
        tr.querySelectorAll('th,td').forEach(function (c) {
            var t = c.innerText.replace(/\s+/g, ' ').trim().replace(/"/g, '""');
            cells.push('"' + t + '"');
        });
        if (cells.length) { lines.push(cells.join(',')); }
    });
    var blob = new Blob(["\uFEFF" + lines.join("\r\n")], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'member_statement_<?= (int)$sel_member ?>.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
</script>
<?php } ?>