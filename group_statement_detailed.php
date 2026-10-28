<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:33:37
//
// Group Contribution Detailed = read-only report.
// Lists every member who made an M-Pesa payment in the chosen period, with their totals.

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

$conn = $conn ?? ($GLOBALS['conn'] ?? null);
$page = "group_statement_detailed";

if (function_exists('workout') && isset($_GET['D'])) {
    workout($_GET['D']);
}
if (function_exists('caniacess')) { caniacess($page . "_view"); }

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

if (function_exists('_title')) { _title($title = 'Group Contribution Detailed'); } else { $title = 'Group Contribution Detailed'; }

// ---------------------------------------------------------------------------
// FILTER INPUTS
// ---------------------------------------------------------------------------
$is_date = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

$start_date = (isset($_POST['start_date']) && $is_date($_POST['start_date'])) ? $_POST['start_date'] : date('Y-m-01');
$end_date   = (isset($_POST['end_date'])   && $is_date($_POST['end_date']))   ? $_POST['end_date']   : date('Y-m-d');
if ($start_date > $end_date) { $tmp = $start_date; $start_date = $end_date; $end_date = $tmp; }

$is_group_leader_role = (isset($_SESSION['userrole']) && $_SESSION['userrole'] == 4);   // role 4 = own group only
$own_group = $is_group_leader_role ? (int)($_SESSION['groupid'] ?? 0) : 0;
$sel_group = $is_group_leader_role ? $own_group : (int)($_POST['group_id'] ?? 0);

$from = $start_date . " 00:00:00";
$to   = $end_date   . " 23:59:59";

// ---------------------------------------------------------------------------
// DATA
// ---------------------------------------------------------------------------
$sql_error = '';

// does mpesa have a "registration" column? (the old page showed it but never loaded it)
$has_reg = false;
if ($rc = mysqli_query($conn, "SHOW COLUMNS FROM `mpesa` LIKE 'registration'")) {
    $has_reg = (mysqli_num_rows($rc) > 0);
}

// groups (dropdown + name lookup)
$group_names = [];
if ($is_group_leader_role) {
    $st = $conn->prepare("SELECT logid, name FROM provider_group WHERE logid=? ORDER BY name");
    $st->bind_param("i", $own_group);
} else {
    $st = $conn->prepare("SELECT logid, name FROM provider_group WHERE logid>0 ORDER BY name");
}
if ($st) {
    $st->execute();
    $rs = $st->get_result();
    while ($g = $rs->fetch_assoc()) { $group_names[(string)$g['logid']] = $g['name']; }
    $st->close();
} else { $sql_error = $conn->error; }

// group leaders, one lookup for everybody
$leaders = [];
if ($sql_error === '' && ($rs = mysqli_query($conn, "SELECT group_id, `name` FROM `member` WHERE isleader=1"))) {
    while ($l = mysqli_fetch_assoc($rs)) { $leaders[(string)$l['group_id']][] = $l['name']; }
}

// members with payments in the period, with their totals
$rows = [];
$tot_reg = 0; $tot_con = 0;
if ($sql_error === '') {
    $reg_sel = $has_reg ? ", SUM(registration) AS registration" : "";
    $sql = "SELECT m.logid, m.mnumber, m.`name`, m.group_id, t.contribution" . ($has_reg ? ", t.registration" : "") . "
              FROM `member` m
              JOIN (SELECT memberid, SUM(amount) AS contribution $reg_sel
                      FROM mpesa
                     WHERE transactiondate >= ? AND transactiondate <= ?
                     GROUP BY memberid) t ON t.memberid = m.logid"
         . ($sel_group > 0 ? " WHERE m.group_id = ?" : "")
         . " ORDER BY m.group_id, m.`name`";
    $st = $conn->prepare($sql);
    if ($st) {
        if ($sel_group > 0) { $st->bind_param("ssi", $from, $to, $sel_group); }
        else                { $st->bind_param("ss",  $from, $to); }
        if ($st->execute()) {
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $r['contribution'] = (float)($r['contribution'] ?? 0);
                $r['registration'] = $has_reg ? (float)($r['registration'] ?? 0) : 0;
                $tot_con += $r['contribution'];
                $tot_reg += $r['registration'];
                $rows[] = $r;
            }
        } else { $sql_error = $st->error; }
        $st->close();
    } else { $sql_error = $conn->error; }
}
$mycount = count($rows);
$colspan = $has_reg ? 6 : 5;
?>

<style>
/* ---------- Panel ---------- */
form[name='group_statement_detailed'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='group_statement_detailed'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='group_statement_detailed'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='group_statement_detailed'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='group_statement_detailed'] .x_content { padding: 20px 28px; }

/* ---------- Filter bar ---------- */
form[name='group_statement_detailed'] .gs-filter {
    display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;
    background: #f7f9fc; border: 1px solid #e3e8ee; border-radius: 8px;
    padding: 14px 16px; margin-bottom: 18px;
}
form[name='group_statement_detailed'] .gs-field { flex: 1 1 160px; min-width: 140px; }
form[name='group_statement_detailed'] .gs-field.gs-wide { flex: 2 1 220px; }
form[name='group_statement_detailed'] .gs-field label {
    display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700;
    color: #1a73e8; text-transform: uppercase; letter-spacing: .04em;
}
form[name='group_statement_detailed'] .gs-field.gs-btn { flex: 0 0 auto; display: flex; gap: 8px; flex-wrap: wrap; }
form[name='group_statement_detailed'] .form-control {
    width: 100%; height: 36px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='group_statement_detailed'] .form-control:focus {
    border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38, 185, 154, .18); outline: none;
}

/* ---------- Buttons ---------- */
form[name='group_statement_detailed'] .btn {
    height: 36px; border-radius: 6px; padding: 0 1.2em; font-weight: 600; letter-spacing: .03em;
    display: inline-flex; align-items: center; gap: 6px;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='group_statement_detailed'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='group_statement_detailed'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='group_statement_detailed'] .btn-success:hover { background: #1f9c82; }
form[name='group_statement_detailed'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='group_statement_detailed'] .btn-secondary:hover { background: #6f7a8d; }
form[name='group_statement_detailed'] .btn-primary   { background: #1a73e8; border-color: #1a73e8; color: #fff; }
form[name='group_statement_detailed'] .btn-primary:hover { background: #1558b8; }

/* ---------- Summary cards ---------- */
.gs-summary { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; }
.gs-card {
    flex: 1 1 170px; background: #fff; border: 1px solid #e3e8ee;
    border-left: 4px solid #26b99a; border-radius: 8px; padding: 12px 16px;
}
.gs-card.blue { border-left-color: #1a73e8; }
.gs-card .lbl { font-size: .72rem; font-weight: 700; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
.gs-card .val { font-size: 1.3rem; font-weight: 700; color: #2a3f54; margin-top: 2px; }

/* ---------- Table ---------- */
.gs-table-wrapper {
    width: 100%; overflow-x: auto; background: #fff;
    border: 1px solid #e3e8ee; border-radius: 8px; padding: 10px;
}
.gs-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
.gs-table-wrapper th, .gs-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
.gs-table-wrapper thead th {
    background: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
.gs-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
.gs-table-wrapper .num { text-align: right; }
.gs-table-wrapper tfoot td { background: #2a3f54 !important; color: #fff !important; font-weight: 700 !important; }
.gs-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='group_statement_detailed'] .x_content { padding: 14px; }
    form[name='group_statement_detailed'] .gs-field,
    form[name='group_statement_detailed'] .gs-field.gs-wide,
    form[name='group_statement_detailed'] .gs-field.gs-btn { flex: 1 1 100%; }
    form[name='group_statement_detailed'] .gs-field.gs-btn .btn { flex: 1; justify-content: center; }
}

/* ---------- Print ---------- */
@media print {
    form[name='group_statement_detailed'] .gs-filter { display: none !important; }
    form[name='group_statement_detailed'] .x_panel { box-shadow: none; border: 0; }
}
</style>

<form method="POST" action="" class="rounded" name="group_statement_detailed" style="min-width:100%">
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> <?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?></small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="gs-filter">
        <div class="gs-field gs-wide">
          <label for="group_id">Group</label>
          <select class="form-control" id="group_id" name="group_id">
            <?php if (!$is_group_leader_role) { ?><option value="">All Groups</option><?php } ?>
            <?php foreach ($group_names as $gid => $gname) { ?>
            <option value="<?= htmlspecialchars($gid) ?>" <?= ((int)$gid === $sel_group) ? 'selected' : '' ?>><?= htmlspecialchars($gname) ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="gs-field">
          <label for="start_date">Start Date</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="gs-field">
          <label for="end_date">End Date</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="gs-field gs-btn">
          <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm">Reset</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="gsExportCSV();"><i class="fa fa-download"></i> CSV</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
        </div>
      </div>

      <?php if ($sql_error !== '') { ?>
        <div class="gs-error">Query error: <?= htmlspecialchars($sql_error) ?></div>
      <?php } else { ?>

      <div class="gs-summary">
        <div class="gs-card blue"><div class="lbl">Members paid</div><div class="val"><?= (int)$mycount ?></div></div>
        <?php if ($has_reg) { ?>
        <div class="gs-card"><div class="lbl">Registration</div><div class="val"><?= number_format($tot_reg, 2) ?></div></div>
        <?php } ?>
        <div class="gs-card"><div class="lbl">Contribution</div><div class="val"><?= number_format($tot_con, 2) ?></div></div>
      </div>

      <div class="gs-table-wrapper">
        <table id="gs_table" role="grid" class="table table-striped table-bordered table-sm">
          <thead>
            <tr>
              <th>Member No</th>
              <th>Names</th>
              <th>Group</th>
              <th>Group Leader</th>
              <?php if ($has_reg) { ?><th class="num">Registration</th><?php } ?>
              <th class="num">Contribution</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row) { $gk = (string)$row['group_id']; ?>
            <tr>
              <td><?= htmlspecialchars($row['mnumber'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
              <td><?= htmlspecialchars($group_names[$gk] ?? '-') ?></td>
              <td><?= htmlspecialchars(isset($leaders[$gk]) ? implode(', ', $leaders[$gk]) : '-') ?></td>
              <?php if ($has_reg) { ?><td class="num"><?= number_format($row['registration'], 2) ?></td><?php } ?>
              <td class="num"><?= number_format($row['contribution'], 2) ?></td>
            </tr>
          <?php } ?>
          <?php if ($mycount == 0) { ?>
            <tr>
              <td colspan="<?= $colspan ?>" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <i class="fa fa-info-circle fa-lg"></i> No contributions found for this period. Try a wider date range.
              </td>
            </tr>
          <?php } ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="<?= $has_reg ? 4 : 4 ?>" class="num">Total</td>
              <?php if ($has_reg) { ?><td class="num"><?= number_format($tot_reg, 2) ?></td><?php } ?>
              <td class="num"><?= number_format($tot_con, 2) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <?php } ?>

    </div>
  </div>
</div>
</form>

<script>
// Export the table to CSV (no external libraries needed)
function gsExportCSV() {
    var table = document.getElementById('gs_table');
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
    a.download = 'group_contribution_<?= htmlspecialchars($start_date) ?>_to_<?= htmlspecialchars($end_date) ?>.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
</script>
<?php } ?>