<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-04-01 12:38:33
//
// Member Profile = read-only page for one member:
// details, this month's status, deposit history and prescription (dispensing) history.
// Opened from the Members list:  index.php?page=member_profile&id=<member logid>

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

$conn = $conn ?? ($GLOBALS['conn'] ?? null);
$page = "member_profile";

// optional encoded parameters (?D=...) are only decoded when present
if (function_exists('workout') && !empty($_GET['D'])) { workout($_GET['D']); }
// The profile is part of the Members module, so it uses the Members "view" permission
// (member_view). No separate entry is needed in Page Access.
if (function_exists('caniacess')) { caniacess("member_view"); }

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

if (function_exists('_title')) { _title($title = 'Member Profile'); } else { $title = 'Member Profile'; }

$is_leader = (isset($_SESSION['userrole']) && $_SESSION['userrole'] == 4);   // role 4 = own group only
$own_group = $is_leader ? (int)($_SESSION['groupid'] ?? 0) : 0;
$id        = (int)($_GET['id'] ?? 0);

$m = null;
$deposits = []; $tot_all = 0; $tot_reg = 0; $tot_con = 0;
$month_amount = 0; $month_count = 0;
$rx = []; $rx_error = ''; $dep_error = '';
$pres = []; $pres_error = ''; $pres_meds = 0;

// ---------------------------------------------------------------------------
// MEMBER (role 4 can only open members of their own group)
// ---------------------------------------------------------------------------
if ($id > 0) {
    $sql = "SELECT m.*, g.`name` AS groupname, c.description AS condname
              FROM `member` m
              LEFT JOIN provider_group g ON g.logid = m.group_id
              LEFT JOIN `conditions` c   ON c.logid = m.medical_condition
             WHERE m.logid = ?" . ($is_leader ? " AND m.group_id = ?" : "");
    $st = $conn->prepare($sql);
    if ($st) {
        if ($is_leader) { $st->bind_param("ii", $id, $own_group); } else { $st->bind_param("i", $id); }
        $st->execute();
        $m = $st->get_result()->fetch_assoc();
        $st->close();
    }
}

if ($m) {
    // ---------------- deposits (M-Pesa) ----------------
    $st = $conn->prepare("SELECT transactiondate, mpesareceiptnumber, amount, ctype FROM mpesa WHERE memberid = ? ORDER BY transactiondate ASC");
    if ($st) {
        $st->bind_param("i", $id);
        if ($st->execute()) {
            $rs = $st->get_result();
            while ($d = $rs->fetch_assoc()) {
                $d['amount'] = (float)($d['amount'] ?? 0);
                $tot_all += $d['amount'];
                if ((string)$d['ctype'] === '0') { $tot_reg += $d['amount']; } else { $tot_con += $d['amount']; }
                $deposits[] = $d;
            }
        } else { $dep_error = $st->error; }
        $st->close();
    } else { $dep_error = $conn->error; }

    // ---------------- this month ----------------
    $from = date('Y-m-01') . " 00:00:00";
    $to   = date('Y-m-t')  . " 23:59:59";
    $st = $conn->prepare("SELECT COUNT(logid) AS n, COALESCE(SUM(amount),0) AS amt FROM mpesa WHERE memberid = ? AND ctype = '1' AND transactiondate >= ? AND transactiondate <= ?");
    if ($st) {
        $st->bind_param("iss", $id, $from, $to);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        $month_count  = (int)($r['n'] ?? 0);
        $month_amount = (float)($r['amt'] ?? 0);
        $st->close();
    }

    // ---------------- prescription / dispensing history ----------------
    $st = $conn->prepare("SELECT s.trans_date, md.`name` AS medicine, u.`name` AS uom, s.quantity, s.created_by, s.created_on
                            FROM stock_transaction s
                            LEFT JOIN medicine md        ON md.logid = s.medicine_id
                            LEFT JOIN unit_of_measure u  ON u.logid  = md.unit_of_measure_id
                           WHERE s.recipient_id = ? AND s.trans_type IN ('To_patient', 'Dispensed')
                           ORDER BY s.trans_date DESC");
    if ($st) {
        $st->bind_param("i", $id);
        if ($st->execute()) {
            $rs = $st->get_result();
            while ($x = $rs->fetch_assoc()) { $rx[] = $x; }
        } else { $rx_error = $st->error; }
        $st->close();
    } else { $rx_error = $conn->error; }

    // ---------------- prescriptions written for this member (with their medicines) ----------------
    $st = $conn->prepare("SELECT p.logid, p.dispensation_dt, p.valid_from, p.valid_to, p.created_by,
                                 d.logid AS detail_id, d.quantity, md.`name` AS medicine, u.`name` AS uom
                            FROM prescription p
                            LEFT JOIN prescription_details d ON d.prescription_id = p.logid
                            LEFT JOIN medicine md            ON md.logid = d.molecule_type
                            LEFT JOIN unit_of_measure u      ON u.logid  = d.unit_of_measure_id
                           WHERE p.member_id = ?
                           ORDER BY p.dispensation_dt DESC, p.logid DESC, d.logid ASC");
    if ($st) {
        $sid = (string)$id;
        $st->bind_param("s", $sid);
        if ($st->execute()) {
            $rs = $st->get_result();
            while ($x = $rs->fetch_assoc()) {
                $pk = (int)$x['logid'];
                if (!isset($pres[$pk])) {
                    $pres[$pk] = ['logid' => $pk, 'dispensation_dt' => $x['dispensation_dt'], 'valid_from' => $x['valid_from'],
                                  'valid_to' => $x['valid_to'], 'created_by' => $x['created_by'], 'items' => []];
                }
                if ($x['detail_id'] !== null) { $pres[$pk]['items'][] = $x; $pres_meds++; }
            }
        } else { $pres_error = $st->error; }
        $st->close();
    } else { $pres_error = $conn->error; }
}

// status of a prescription from its validity dates
function mp_status($from, $to) {
    $today = date('Y-m-d');
    $from = ((string)$from !== '' && strpos((string)$from, '0000') !== 0) ? substr((string)$from, 0, 10) : '';
    $to   = ((string)$to   !== '' && strpos((string)$to,   '0000') !== 0) ? substr((string)$to, 0, 10)   : '';
    if ($to !== '' && $to < $today)     { return ['Expired',  'expired']; }
    if ($from !== '' && $from > $today) { return ['Upcoming', 'upcoming']; }
    return ['Active', 'active'];
}
$pres_active = 0;
foreach ($pres as $pr) { if (mp_status($pr['valid_from'], $pr['valid_to'])[1] === 'active') { $pres_active++; } }

$active = $m && (($m['active'] ?? '') === 'Yes');
?>

<style>
.mp-wrap { font-family: inherit; }
.mp-panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem 0;
}
.mp-head {
    display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px;
    background: #f7f9fc; border-bottom: 1px solid #e3e8ee; padding: 14px 20px;
}
.mp-head h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
.mp-head h2 small { margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
.mp-tools { display: flex; gap: 8px; flex-wrap: wrap; }
.mp-btn {
    display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 1.1em;
    border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none !important;
    border: 1px solid transparent; cursor: pointer; transition: transform .1s, box-shadow .15s, background .15s;
}
.mp-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
.mp-btn.primary   { background: #1a73e8; color: #fff !important; }
.mp-btn.primary:hover { background: #1558b8; }
.mp-btn.secondary { background: #8a94a6; color: #fff !important; }
.mp-btn.secondary:hover { background: #6f7a8d; }
.mp-btn.success   { background: #26b99a; color: #fff !important; }
.mp-btn.success:hover { background: #1f9c82; }
.mp-body { padding: 20px 24px; }

/* profile grid */
.mp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px 24px; }
.mp-item .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
.mp-item .val { font-size: 1rem; font-weight: 700; color: #2a3f54; margin-top: 2px; word-break: break-word; }

/* cards */
.mp-cards { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 4px; }
.mp-card {
    flex: 1 1 170px; background: #fff; border: 1px solid #e3e8ee;
    border-left: 4px solid #26b99a; border-radius: 8px; padding: 12px 16px;
}
.mp-card.blue { border-left-color: #1a73e8; }
.mp-card.red  { border-left-color: #e74c3c; }
.mp-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
.mp-card .val { font-size: 1.25rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }

/* two-column area */
.mp-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 18px; }
.mp-section-title {
    font-size: .95rem; font-weight: 700; color: #1a73e8; margin: 0 0 10px;
    padding-bottom: 6px; border-bottom: 2px solid #26b99a;
}

/* tables */
.mp-table-wrap { overflow-x: auto; border: 1px solid #e3e8ee; border-radius: 8px; }
.mp-table { width: 100%; margin: 0; border-collapse: collapse; white-space: nowrap; }
.mp-table th, .mp-table td { padding: 9px 14px; font-size: 13px; border-bottom: 1px solid #eef1f4; text-align: left; }
.mp-table thead th { background: #f7f9fc; color: #1a73e8; font-weight: 700; border-bottom: 2px solid #26b99a; }
.mp-table tbody tr:hover { background: rgba(38, 185, 154, .07); }
.mp-table .num { text-align: right; }
.mp-table tfoot td { background: #2a3f54; color: #fff; font-weight: 700; }
.mp-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; background: #8a94a6; }
.mp-badge.yes { background: #26b99a; }
.mp-badge.no  { background: #e74c3c; }
.mp-badge.reg { background: #1a73e8; }
.mp-badge.con { background: #26b99a; }
.mp-empty { padding: 22px; text-align: center; color: #73879C; font-size: 13px; }
.mp-error { color: #c0392b; padding: 14px; text-align: center; }

.mp-badge.active   { background: #26b99a; }
.mp-badge.upcoming { background: #1a73e8; }
.mp-badge.expired  { background: #e74c3c; }
.mp-rx-table td { vertical-align: middle; }
.mp-rx-table tr.mp-g1 td { background: #fafbfd; }
.mp-rx-table tr.mp-first td { border-top: 2px solid #cbd5e0; }
.mp-rx-table td.mp-none { color: #8a94a6; font-style: italic; }
.mp-rx-table a { color: #1a73e8; font-weight: 600; }
.mp-rx-table tbody tr:hover td { background: rgba(38, 185, 154, .07); }
@media (max-width: 767px) {
    .mp-body { padding: 14px; }
    .mp-cols { grid-template-columns: 1fr; }
}
@media print {
    .mp-tools, .mp-noprint { display: none !important; }
    .mp-panel { box-shadow: none; border: 0; }
}
</style>

<div class="col-md-12 col-sm-12 col-xs-12 mp-wrap">

<?php if (!$m) { ?>
  <div class="mp-panel">
    <div class="mp-head">
      <h2><?= htmlspecialchars($title) ?></h2>
      <div class="mp-tools"><a href="index.php?page=member" class="mp-btn secondary"><i class="fa fa-arrow-left"></i> Back to members</a></div>
    </div>
    <div class="mp-body"><div class="mp-error"><i class="fa fa-exclamation-circle"></i> Member not found, or you do not have access to this member.</div></div>
  </div>

<?php } else { ?>

  <!-- ===================== PROFILE ===================== -->
  <div class="mp-panel">
    <div class="mp-head">
      <h2><?= htmlspecialchars($title) ?><small> <?= htmlspecialchars($m['mnumber'] ?? '') ?></small></h2>
      <div class="mp-tools">
        <a href="index.php?page=member" class="mp-btn secondary"><i class="fa fa-arrow-left"></i> Members</a>
        <a href="index.php?page=member&part=2&rec=<?= urlencode($m['logid']) ?>" class="mp-btn success"><i class="fa fa-pencil"></i> Edit</a>
        <a href="index.php?page=prescription&part=1&m=<?= urlencode($m['logid']) ?>&g=<?= urlencode($m['group_id']) ?>" class="mp-btn primary"><i class="fa fa-user-md"></i> New prescription</a>
        <button type="button" class="mp-btn primary" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
      </div>
    </div>
    <div class="mp-body">
      <div class="mp-section-title">Client Profile</div>
      <div class="mp-grid">
        <div class="mp-item"><div class="lbl">Client ID</div><div class="val"><?= htmlspecialchars($m['logid']) ?></div></div>
        <div class="mp-item"><div class="lbl">Name</div><div class="val"><?= htmlspecialchars($m['name'] ?? '') ?></div></div>
        <div class="mp-item"><div class="lbl">Gender</div><div class="val"><?= htmlspecialchars(($m['gender'] ?? '') !== '' ? $m['gender'] : '-') ?></div></div>
        <div class="mp-item"><div class="lbl">Phone no</div><div class="val"><?= htmlspecialchars(($m['phone_no'] ?? '') !== '' ? $m['phone_no'] : '-') ?></div></div>
        <div class="mp-item"><div class="lbl">National ID</div><div class="val"><?= htmlspecialchars(($m['nat_id'] ?? '') !== '' ? $m['nat_id'] : '-') ?></div></div>
        <div class="mp-item"><div class="lbl">Group</div><div class="val"><?= htmlspecialchars(($m['groupname'] ?? '') !== '' ? $m['groupname'] : '-') ?></div></div>
        <div class="mp-item"><div class="lbl">Medical condition</div><div class="val"><?= htmlspecialchars(($m['condname'] ?? '') !== '' ? $m['condname'] : '-') ?></div></div>
        <div class="mp-item"><div class="lbl">Caregiver</div><div class="val"><?= htmlspecialchars(trim(($m['caregiver_name'] ?? '') . ' ' . ($m['caregiver_phone'] ?? '')) ?: '-') ?></div></div>
        <div class="mp-item"><div class="lbl">Status</div><div class="val"><span class="mp-badge <?= $active ? 'yes' : 'no' ?>"><?= $active ? 'Active' : 'Inactive' ?></span></div></div>
      </div>
    </div>
  </div>

  <!-- ===================== CURRENT MONTH ===================== -->
  <div class="mp-panel">
    <div class="mp-body">
      <div class="mp-section-title">Current Month Status — <?= htmlspecialchars(date('F Y')) ?></div>
      <div class="mp-cards">
        <div class="mp-card <?= $month_amount > 0 ? '' : 'red' ?>"><div class="lbl">This month's contribution</div><div class="val"><?= number_format($month_amount, 2) ?></div></div>
        <div class="mp-card blue"><div class="lbl">Number of deposits</div><div class="val"><?= $month_count ?></div></div>
        <div class="mp-card <?= $month_amount > 0 ? '' : 'red' ?>"><div class="lbl">Paid this month</div><div class="val"><?= $month_amount > 0 ? 'Yes' : 'No' ?></div></div>
      </div>
    </div>
  </div>

  <!-- ===================== PRESCRIPTIONS ===================== -->
  <div class="mp-panel">
    <div class="mp-body">
      <div class="mp-section-title">Prescriptions</div>
      <div class="mp-cards" style="margin-bottom:14px;">
        <div class="mp-card blue"><div class="lbl">Prescriptions</div><div class="val"><?= count($pres) ?></div></div>
        <div class="mp-card"><div class="lbl">Active now</div><div class="val"><?= (int)$pres_active ?></div></div>
        <div class="mp-card blue"><div class="lbl">Medicines prescribed</div><div class="val"><?= (int)$pres_meds ?></div></div>
      </div>

      <?php if ($pres_error !== '') { ?>
        <div class="mp-error">Query error: <?= htmlspecialchars($pres_error) ?></div>
      <?php } elseif (!$pres) { ?>
        <div class="mp-empty">No prescriptions yet.
          <a href="index.php?page=prescription&part=1&m=<?= urlencode($m['logid']) ?>&g=<?= urlencode($m['group_id']) ?>"><u>Create a prescription</u></a></div>
      <?php } else { ?>
        <div class="mp-table-wrap">
          <table class="mp-table mp-rx-table">
            <thead>
              <tr>
                <th>Rx No.</th>
                <th>Dispensed</th>
                <th>Valid From</th>
                <th>Valid To</th>
                <th>Status</th>
                <th>Medicine</th>
                <th>Unit</th>
                <th class="num">Quantity</th>
                <th>By</th>
                <th class="mp-noprint">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php $gi = 0;
            foreach ($pres as $pr) {
                $gi++;
                $ps    = mp_status($pr['valid_from'], $pr['valid_to']);
                $items = $pr['items'];
                $span  = max(1, count($items));
                $cls   = 'mp-g' . ($gi % 2);
                $first = true;
                $rows_to_print = $items ? $items : [null];
                foreach ($rows_to_print as $it) { ?>
              <tr class="<?= $cls ?><?= $first ? ' mp-first' : '' ?>">
                <?php if ($first) { ?>
                <td rowspan="<?= $span ?>"><strong>#<?= (int)$pr['logid'] ?></strong></td>
                <td rowspan="<?= $span ?>"><?= htmlspecialchars(substr((string)$pr['dispensation_dt'], 0, 10)) ?></td>
                <td rowspan="<?= $span ?>"><?= htmlspecialchars(substr((string)$pr['valid_from'], 0, 10)) ?></td>
                <td rowspan="<?= $span ?>"><?= htmlspecialchars(substr((string)$pr['valid_to'], 0, 10)) ?></td>
                <td rowspan="<?= $span ?>"><span class="mp-badge <?= $ps[1] ?>"><?= $ps[0] ?></span></td>
                <?php } ?>
                <?php if ($it) { ?>
                <td><strong><?= htmlspecialchars(($it['medicine'] ?? '') !== '' ? $it['medicine'] : 'Unknown medicine') ?></strong></td>
                <td><?= htmlspecialchars(($it['uom'] ?? '') !== '' ? $it['uom'] : '-') ?></td>
                <td class="num"><?= htmlspecialchars($it['quantity'] ?? '') ?></td>
                <?php } else { ?>
                <td colspan="3" class="mp-none">No medicines on this prescription yet</td>
                <?php } ?>
                <?php if ($first) { ?>
                <td rowspan="<?= $span ?>"><?= htmlspecialchars($pr['created_by'] ?? '') ?></td>
                <td rowspan="<?= $span ?>" class="mp-noprint">
                  <a href="index.php?page=prescription_details&p=<?= (int)$pr['logid'] ?>" title="Manage medicines"><i class="fa fa-medkit"></i> Manage</a>
                  &nbsp;
                  <a href="index.php?page=prescription_details&part=1&p=<?= (int)$pr['logid'] ?>" title="Add medicine"><i class="fa fa-plus"></i> Add</a>
                </td>
                <?php } ?>
              </tr>
            <?php $first = false; } } ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
    </div>
  </div>

  <!-- ===================== HISTORY ===================== -->
  <div class="mp-panel">
    <div class="mp-body">
      <div class="mp-cols">

        <div>
          <div class="mp-section-title">Deposit History</div>
          <div class="mp-table-wrap">
            <table class="mp-table">
              <thead>
                <tr>
                  <th>Transaction Date</th>
                  <th>Reference</th>
                  <th>Type</th>
                  <th class="num">Amount</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($dep_error !== '') { ?>
                <tr><td colspan="4" class="mp-error">Query error: <?= htmlspecialchars($dep_error) ?></td></tr>
              <?php } else {
                  foreach ($deposits as $d) { $reg = ((string)$d['ctype'] === '0'); ?>
                <tr>
                  <td><?= htmlspecialchars($d['transactiondate'] ?? '') ?></td>
                  <td><?= htmlspecialchars($d['mpesareceiptnumber'] ?? '') ?></td>
                  <td><span class="mp-badge <?= $reg ? 'reg' : 'con' ?>"><?= $reg ? 'Registration' : 'Contribution' ?></span></td>
                  <td class="num"><?= number_format($d['amount'], 2) ?></td>
                </tr>
              <?php }
                  if (!$deposits) { ?>
                <tr><td colspan="4" class="mp-empty">No deposits recorded yet.</td></tr>
              <?php }
              } ?>
              </tbody>
              <tfoot>
                <tr><td colspan="3" class="num">Registration</td><td class="num"><?= number_format($tot_reg, 2) ?></td></tr>
                <tr><td colspan="3" class="num">Contributions</td><td class="num"><?= number_format($tot_con, 2) ?></td></tr>
                <tr><td colspan="3" class="num">Total Deposits</td><td class="num"><?= number_format($tot_all, 2) ?></td></tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div>
          <div class="mp-section-title">Dispensed Medicines</div>
          <div class="mp-table-wrap">
            <table class="mp-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Medicine</th>
                  <th>Unit</th>
                  <th class="num">Quantity</th>
                  <th>Dispensed by</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rx_error !== '') { ?>
                <tr><td colspan="5" class="mp-error">Query error: <?= htmlspecialchars($rx_error) ?></td></tr>
              <?php } else {
                  foreach ($rx as $x) { ?>
                <tr>
                  <td><?= htmlspecialchars($x['trans_date'] ?? '') ?></td>
                  <td><strong><?= htmlspecialchars($x['medicine'] ?? '-') ?></strong></td>
                  <td><?= htmlspecialchars(($x['uom'] ?? '') !== '' ? $x['uom'] : '-') ?></td>
                  <td class="num"><?= htmlspecialchars($x['quantity'] ?? '') ?></td>
                  <td><?= htmlspecialchars(trim(($x['created_by'] ?? '') . ' - ' . ($x['created_on'] ?? ''), ' -')) ?></td>
                </tr>
              <?php }
                  if (!$rx) { ?>
                <tr><td colspan="5" class="mp-empty">No medicine has been dispensed to this member yet (see Prescriptions above for what was prescribed).</td></tr>
              <?php }
              } ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

<?php } ?>

</div>
<?php } ?>