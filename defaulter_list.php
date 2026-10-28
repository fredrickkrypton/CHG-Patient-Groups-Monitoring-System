<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:33:37
//
// Defaulter List = read-only report.
// A member is a defaulter for the chosen month when they have NO M-Pesa contribution in that month.

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

$conn = $conn ?? ($GLOBALS['conn'] ?? null);
$page = "defaulter_list";

// Monthly contribution each member is expected to make
$monthly_fee = 500;

if (function_exists('workout') && isset($_GET['D'])) {
    workout($_GET['D']);
}
if (function_exists('caniacess')) { caniacess($page . "_view"); }

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

if (function_exists('_title')) { _title($title = 'Defaulter List'); } else { $title = 'Defaulter List'; }

// ---------------------------------------------------------------------------
// FILTER INPUTS
// ---------------------------------------------------------------------------
$is_group_leader_role = (isset($_SESSION['userrole']) && $_SESSION['userrole'] == 4);   // role 4 = restricted to own group
$own_group = $is_group_leader_role ? (int)($_SESSION['groupid'] ?? 0) : 0;

$month = (int)($_POST['month'] ?? date('n'));
$year  = (int)($_POST['year']  ?? date('Y'));
if ($month < 1 || $month > 12)     { $month = (int)date('n'); }
if ($year  < 2000 || $year > 2100) { $year  = (int)date('Y'); }
$ym = sprintf('%04d-%02d', $year, $month);

$month_names = [1=>'January','February','March','April','May','June','July','August','September','October','November','December'];

// group filter (role 4 is forced to their own group, even if nothing was posted)
$sel_group = $is_group_leader_role ? $own_group : (int)($_POST['group_id'] ?? 0);

// ---------------------------------------------------------------------------
// DATA
// ---------------------------------------------------------------------------
$sql_error = '';

// 1. groups shown in the dropdown
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

// 2. contributions for the chosen month, one query for all members
$paid = [];
$like_ym = $ym . '%';
$st = $conn->prepare("SELECT memberid, SUM(amount) AS amt FROM mpesa WHERE transactiondate LIKE ? GROUP BY memberid");
if ($st) {
    $st->bind_param("s", $like_ym);
    $st->execute();
    $rs = $st->get_result();
    while ($p = $rs->fetch_assoc()) { $paid[(string)$p['memberid']] = (float)$p['amt']; }
    $st->close();
} else { $sql_error = $conn->error; }

// 3. groups to report on
$report = [];            // [ ['group'=>row, 'leaders'=>[], 'members'=>[], 'subtotal'=>x], ... ]
$grandt = 0;
$total_defaulters = 0;

if ($sql_error === '') {
    if ($sel_group > 0) {
        $gst = $conn->prepare("SELECT logid, name FROM provider_group WHERE logid=? ORDER BY name");
        $gst->bind_param("i", $sel_group);
    } else {
        $gst = $conn->prepare("SELECT logid, name FROM provider_group WHERE logid>0 ORDER BY name");
    }
    $lst = $conn->prepare("SELECT `name` FROM `member` WHERE group_id=? AND isleader=1");
    $mst = $conn->prepare("SELECT logid, `name`, mnumber, phone_no, activation_dt FROM `member` WHERE group_id=? ORDER BY `name`");

    if ($gst && $lst && $mst) {
        $gst->execute();
        $groups = $gst->get_result();
        while ($grp = $groups->fetch_assoc()) {
            $gid = (string)$grp['logid'];

            $leaders = [];
            $lst->bind_param("s", $gid);
            $lst->execute();
            $lr = $lst->get_result();
            while ($l = $lr->fetch_assoc()) { $leaders[] = $l['name']; }

            $members = [];
            $subtotal = 0;
            $mst->bind_param("s", $gid);
            $mst->execute();
            $mr = $mst->get_result();
            while ($m = $mr->fetch_assoc()) {
                // skip members who were activated after the chosen month
                $act = (string)($m['activation_dt'] ?? '');
                if (preg_match('/^(\d{4}-\d{2})/', $act, $mm) && strpos($act, '0000') !== 0 && $mm[1] > $ym) { continue; }

                $contributed = $paid[(string)$m['logid']] ?? 0;
                if ($contributed <= 0) {                  // no contribution this month = defaulter
                    $m['contributed'] = $contributed;
                    $m['due']         = $monthly_fee;
                    $members[]        = $m;
                    $subtotal        += $monthly_fee;
                }
            }
            $grandt += $subtotal;
            $total_defaulters += count($members);
            $report[] = ['group' => $grp, 'leaders' => $leaders, 'members' => $members, 'subtotal' => $subtotal];
        }
        $gst->close(); $lst->close(); $mst->close();
    } else {
        $sql_error = $conn->error;
    }
}
?>

<style>
/* ---------- Panel ---------- */
form[name='defaulter_list'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='defaulter_list'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='defaulter_list'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='defaulter_list'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='defaulter_list'] .x_content { padding: 20px 28px; }

/* ---------- Filter bar ---------- */
form[name='defaulter_list'] .df-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: flex-end;
    background: #f7f9fc;
    border: 1px solid #e3e8ee;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 18px;
}
form[name='defaulter_list'] .df-field { flex: 1 1 150px; min-width: 130px; }
form[name='defaulter_list'] .df-field.df-wide { flex: 2 1 220px; }
form[name='defaulter_list'] .df-field label {
    display: block;
    margin-bottom: 5px;
    font-size: .78rem;
    font-weight: 700;
    color: #1a73e8;
    text-transform: uppercase;
    letter-spacing: .04em;
}
form[name='defaulter_list'] .df-field.df-btn { flex: 0 0 auto; display: flex; gap: 8px; }
form[name='defaulter_list'] .form-control {
    width: 100%;
    height: 36px;
    border: 1px solid #cfd7e3;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: .9rem;
    color: #1a73e8;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='defaulter_list'] .form-control:focus {
    border-color: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .18);
    outline: none;
}

/* ---------- Buttons ---------- */
form[name='defaulter_list'] .btn {
    height: 36px;
    border-radius: 6px;
    padding: 0 1.3em;
    font-weight: 600;
    letter-spacing: .03em;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='defaulter_list'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='defaulter_list'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='defaulter_list'] .btn-success:hover { background: #1f9c82; }
form[name='defaulter_list'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='defaulter_list'] .btn-secondary:hover { background: #6f7a8d; }
form[name='defaulter_list'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='defaulter_list'] .btn-primary:hover { background: #1558b8; }

/* ---------- Summary cards ---------- */
.df-summary { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; }
.df-card {
    flex: 1 1 180px;
    background: #fff;
    border: 1px solid #e3e8ee;
    border-left: 4px solid #26b99a;
    border-radius: 8px;
    padding: 12px 16px;
}
.df-card.red  { border-left-color: #e74c3c; }
.df-card.blue { border-left-color: #1a73e8; }
.df-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
.df-card .val { font-size: 1.3rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }

/* ---------- Group blocks ---------- */
.df-group { border: 1px solid #e3e8ee; border-radius: 8px; margin-bottom: 16px; overflow: hidden; background: #fff; }
.df-group-head {
    display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px;
    background: #f7f9fc; padding: 10px 16px; border-bottom: 2px solid #26b99a;
}
.df-group-head .gname { font-weight: 700; color: #1a73e8; font-size: .98rem; }
.df-group-head .gleader { color: #5b6677; font-size: .85rem; }
.df-table-wrap { overflow-x: auto; }
.df-table { width: 100%; margin: 0; border-collapse: collapse; white-space: nowrap; }
.df-table th, .df-table td { padding: 9px 14px; font-size: 13px; border-bottom: 1px solid #eef1f4; }
.df-table thead th { background: #fff; color: #1a73e8; font-weight: 700; border-bottom: 1px solid #e3e8ee; }
.df-table tbody tr:hover { background: rgba(38, 185, 154, .07); }
.df-table td.num, .df-table th.num { text-align: right; }
.df-table tr.subtotal td { background: #fafbfc; font-weight: 700; color: #2a3f54; }
.df-empty { padding: 14px 16px; color: #73879C; font-size: 13px; }
.df-grand {
    display: flex; justify-content: space-between; align-items: center;
    background: #2a3f54; color: #fff; border-radius: 8px; padding: 12px 18px; font-weight: 700;
}
.df-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='defaulter_list'] .x_content { padding: 14px; }
    form[name='defaulter_list'] .df-field,
    form[name='defaulter_list'] .df-field.df-wide,
    form[name='defaulter_list'] .df-field.df-btn { flex: 1 1 100%; }
    form[name='defaulter_list'] .df-field.df-btn .btn { flex: 1; justify-content: center; }
}

/* ---------- Print ---------- */
@media print {
    form[name='defaulter_list'] .df-filter { display: none !important; }
    form[name='defaulter_list'] .x_panel { box-shadow: none; border: 0; }
    .df-grand { background: #fff !important; color: #000 !important; border: 1px solid #000; }
}
</style>

<form method="POST" action="" class="rounded" name="defaulter_list" style="min-width:100%">
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> <?= htmlspecialchars($month_names[$month] . ' ' . $year) ?></small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="df-filter">
        <div class="df-field df-wide">
          <label for="group_id">Group</label>
          <select class="form-control" id="group_id" name="group_id">
            <?php if (!$is_group_leader_role) { ?><option value="">All Groups</option><?php } ?>
            <?php foreach ($group_options as $g) { ?>
            <option value="<?= htmlspecialchars($g['logid']) ?>" <?= ((int)$g['logid'] === $sel_group) ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="df-field">
          <label for="month">Month</label>
          <select class="form-control" id="month" name="month">
            <?php foreach ($month_names as $num => $nm) { ?>
            <option value="<?= $num ?>" <?= $num === $month ? 'selected' : '' ?>><?= $nm ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="df-field">
          <label for="year">Year</label>
          <select class="form-control" id="year" name="year">
            <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 6; $y--) { ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="df-field df-btn">
          <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Reset</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
        </div>
      </div>

      <?php if ($sql_error !== '') { ?>
        <div class="df-error">Query error: <?= htmlspecialchars($sql_error) ?></div>
      <?php } else { ?>

      <div class="df-summary">
        <div class="df-card blue"><div class="lbl">Month</div><div class="val"><?= htmlspecialchars($month_names[$month] . ' ' . $year) ?></div></div>
        <div class="df-card red"><div class="lbl">Defaulters</div><div class="val"><?= (int)$total_defaulters ?></div></div>
        <div class="df-card"><div class="lbl">Total due</div><div class="val"><?= number_format($grandt, 2) ?></div></div>
      </div>

      <?php if (!$report) { ?>
        <div class="df-empty">No groups found.</div>
      <?php } ?>

      <?php foreach ($report as $blk) { ?>
      <div class="df-group">
        <div class="df-group-head">
          <span class="gname"><?= htmlspecialchars($blk['group']['name'] ?? '') ?></span>
          <span class="gleader">Group leader: <?= $blk['leaders'] ? htmlspecialchars(implode(', ', $blk['leaders'])) : '-' ?></span>
        </div>
        <div class="df-table-wrap">
          <table class="df-table">
            <thead>
              <tr>
                <th>Member No</th>
                <th>Name</th>
                <th>Phone</th>
                <th class="num">Contributed</th>
                <th class="num">Due</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($blk['members'] as $m) { ?>
              <tr>
                <td><?= htmlspecialchars($m['mnumber'] ?? '') ?></td>
                <td><?= htmlspecialchars($m['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($m['phone_no'] ?? '') ?></td>
                <td class="num"><?= number_format($m['contributed'], 2) ?></td>
                <td class="num"><?= number_format($m['due'], 2) ?></td>
              </tr>
            <?php } ?>
            <?php if (!$blk['members']) { ?>
              <tr><td colspan="5" class="df-empty"><i class="fa fa-check-circle" style="color:#26b99a;"></i> No defaulters in this group for <?= htmlspecialchars($month_names[$month] . ' ' . $year) ?>.</td></tr>
            <?php } ?>
              <tr class="subtotal">
                <td colspan="4" class="num">Sub-Total</td>
                <td class="num"><?= number_format($blk['subtotal'], 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <?php } ?>

      <div class="df-grand">
        <span>Grand Total Due</span>
        <span><?= number_format($grandt, 2) ?></span>
      </div>

      <?php } ?>

    </div>
  </div>
</div>
</form>
<?php } ?>