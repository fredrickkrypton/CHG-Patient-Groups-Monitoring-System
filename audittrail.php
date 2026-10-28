<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:05:32
//
// Audit Trail = read-only log. Records are written by audittrail() from the other pages,
// so this page only VIEWS and FILTERS them (no add / edit / delete on purpose).

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

$conn = $conn ?? ($GLOBALS['conn'] ?? null);
$page = "audittrail";

if (function_exists('workout') && isset($_GET['D'])) {
    workout($_GET['D']);
}
if (function_exists('caniacess')) { caniacess($page . "_view"); }

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

if (function_exists('_title')) { _title($title = 'Audit Trail'); } else { $title = 'Audit Trail'; }

// ---------------------------------------------------------------------------
// FILTER INPUTS  (dates come from <input type="date"> as YYYY-MM-DD)
// ---------------------------------------------------------------------------
$is_date = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

$start_date = (isset($_POST['start_date']) && $is_date($_POST['start_date'])) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date   = (isset($_POST['end_date'])   && $is_date($_POST['end_date']))   ? $_POST['end_date']   : date('Y-m-d');
$search     = trim($_POST['search'] ?? '');

if ($start_date > $end_date) { $tmp = $start_date; $start_date = $end_date; $end_date = $tmp; }

$from  = $start_date . " 00:00:00";
$to    = $end_date   . " 23:59:59";
$like  = '%' . $search . '%';
$limit = 1000;

// ---------------------------------------------------------------------------
// QUERY
// ---------------------------------------------------------------------------
$rows = [];
$sql_error = '';
$stmt = $conn->prepare(
    "SELECT `timestamp`, `tablename`, `id`, `action`, `username`
       FROM `audittrail`
      WHERE `timestamp` >= ? AND `timestamp` <= ?
        AND (`username` LIKE ? OR `action` LIKE ? OR `tablename` LIKE ? OR `id` LIKE ?)
      ORDER BY `timestamp` DESC
      LIMIT $limit"
);
if ($stmt) {
    $stmt->bind_param("ssssss", $from, $to, $like, $like, $like, $like);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    } else {
        $sql_error = $stmt->error;
    }
    $stmt->close();
} else {
    $sql_error = $conn->error;
}
$mycount = count($rows);
?>

<style>
/* ---------- Panel ---------- */
form[name='audittrail'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='audittrail'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='audittrail'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='audittrail'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='audittrail'] .x_content { padding: 20px 28px; }

/* ---------- Filter bar ---------- */
form[name='audittrail'] .at-filter {
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
form[name='audittrail'] .at-field { flex: 1 1 170px; min-width: 150px; }
form[name='audittrail'] .at-field label {
    display: block;
    margin-bottom: 5px;
    font-size: .78rem;
    font-weight: 700;
    color: #1a73e8;
    text-transform: uppercase;
    letter-spacing: .04em;
}
form[name='audittrail'] .at-field.at-btn { flex: 0 0 auto; display: flex; gap: 8px; }
form[name='audittrail'] .form-control {
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
form[name='audittrail'] .form-control::placeholder { color: #9bbcf0; }
form[name='audittrail'] .form-control:focus {
    border-color: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .18);
    outline: none;
}

/* ---------- Buttons ---------- */
form[name='audittrail'] .btn {
    height: 36px;
    border-radius: 6px;
    padding: 0 1.4em;
    font-weight: 600;
    letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='audittrail'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='audittrail'] .btn-success { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='audittrail'] .btn-success:hover { background: #1f9c82; }
form[name='audittrail'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='audittrail'] .btn-secondary:hover { background: #6f7a8d; }

/* ---------- Table ---------- */
.at-table-wrapper {
    width: 100%;
    overflow-x: auto;
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 8px;
    padding: 10px;
}
.at-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
.at-table-wrapper th, .at-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
.at-table-wrapper thead th {
    background: #f7f9fc !important;
    color: #1a73e8 !important;
    font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
.at-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
.at-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; background: #8a94a6; }
.at-badge.ins { background: #26b99a; }
.at-badge.upd { background: #1a73e8; }
.at-badge.del { background: #e74c3c; }
.at-summary { margin: 0 0 10px; font-size: 12px; color: #8a94a6; }
.at-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='audittrail'] .x_content { padding: 14px; }
    form[name='audittrail'] .at-field { flex: 1 1 100%; }
    form[name='audittrail'] .at-field.at-btn { flex: 1 1 100%; }
    form[name='audittrail'] .at-field.at-btn .btn { flex: 1; }
}
</style>

<form method="POST" action="" class="rounded" name="audittrail" style="min-width:100%">
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> activity log</small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="at-filter">
        <div class="at-field">
          <label for="start_date">Start Date</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="at-field">
          <label for="end_date">End Date</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="at-field">
          <label for="search">Search</label>
          <input type="text" class="form-control" name="search" id="search" placeholder="User, action, table or ID" value="<?= htmlspecialchars($search) ?>" autocomplete="off">
        </div>
        <div class="at-field at-btn">
          <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;">Reset</a>
        </div>
      </div>

      <p class="at-summary">
        Showing <?= (int)$mycount ?> record<?= $mycount == 1 ? '' : 's' ?>
        from <?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?><?= $mycount >= $limit ? " (limited to the latest $limit)" : '' ?>.
      </p>

      <div class="at-table-wrapper">
        <table id="datatable" role="grid" class="table table-striped table-bordered table-sm dataTable">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Table name</th>
              <th>Id</th>
              <th>Action</th>
              <th>Username</th>
            </tr>
          </thead>
          <tbody>
          <?php
          if ($sql_error !== '') {
              echo "<tr><td colspan='5' class='at-error'>Query error: " . htmlspecialchars($sql_error) . "</td></tr>";
          } else {
              foreach ($rows as $row) {
                  $act = strtolower($row['action'] ?? '');
                  $cls = (strpos($act, 'insert') !== false) ? 'ins' : ((strpos($act, 'updat') !== false) ? 'upd' : ((strpos($act, 'delet') !== false) ? 'del' : '')); ?>
            <tr>
              <td><?= htmlspecialchars($row['timestamp'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['tablename'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
              <td><span class="at-badge <?= $cls ?>"><?= htmlspecialchars($row['action'] ?? '') ?></span></td>
              <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr>
              <td colspan="5" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <i class="fa fa-info-circle fa-lg"></i> No audit records found for this period. Try a wider date range.
              </td>
            </tr>
          <?php }
          } ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
</form>
<?php } ?>