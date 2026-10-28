<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:33:37
//
// User List = read-only report. Users are created/edited on the Users page (0_users),
// so this page only VIEWS and FILTERS them.

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

$conn = $conn ?? ($GLOBALS['conn'] ?? null);
$page = "userlist";

if (function_exists('workout') && isset($_GET['D'])) {
    workout($_GET['D']);
}
if (function_exists('caniacess')) { caniacess($page . "_view"); }

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

if (function_exists('_title')) { _title($title = 'User List'); } else { $title = 'User List'; }

// ---------------------------------------------------------------------------
// FILTER INPUTS
//  - dates are optional (filter on last login); blank = show everyone
// ---------------------------------------------------------------------------
$is_date = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

$start_date = (isset($_POST['start_date']) && $is_date($_POST['start_date'])) ? $_POST['start_date'] : '';
$end_date   = (isset($_POST['end_date'])   && $is_date($_POST['end_date']))   ? $_POST['end_date']   : '';
$search     = trim($_POST['search'] ?? '');
$active_f   = in_array($_POST['active_f'] ?? '', ['Yes', 'No'], true) ? $_POST['active_f'] : '';

if ($start_date !== '' && $end_date !== '' && $start_date > $end_date) {
    $tmp = $start_date; $start_date = $end_date; $end_date = $tmp;
}

// ---------------------------------------------------------------------------
// QUERY (password is never selected)
// ---------------------------------------------------------------------------
$where  = [];
$types  = '';
$params = [];

if ($start_date !== '') { $where[] = "`lastlogin` >= ?"; $types .= 's'; $params[] = $start_date . " 00:00:00"; }
if ($end_date   !== '') { $where[] = "`lastlogin` <= ?"; $types .= 's'; $params[] = $end_date   . " 23:59:59"; }
if ($active_f   !== '') { $where[] = "`active` = ?";     $types .= 's'; $params[] = $active_f; }
if ($search     !== '') {
    $like = '%' . $search . '%';
    $where[] = "(`fullnames` LIKE ? OR `username` LIKE ? OR `email` LIKE ? OR `phone` LIKE ?)";
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}

$sql = "SELECT `fullnames`, `username`, `email`, `phone`, `active`, `lastlogin` FROM `0_users`"
     . ($where ? " WHERE " . implode(" AND ", $where) : "")
     . " ORDER BY `fullnames`";

$rows = [];
$sql_error = '';
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($params) { $stmt->bind_param($types, ...$params); }
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
form[name='userlist'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='userlist'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='userlist'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='userlist'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='userlist'] .x_content { padding: 20px 28px; }

/* ---------- Filter bar ---------- */
form[name='userlist'] .ul-filter {
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
form[name='userlist'] .ul-field { flex: 1 1 160px; min-width: 140px; }
form[name='userlist'] .ul-field label {
    display: block;
    margin-bottom: 5px;
    font-size: .78rem;
    font-weight: 700;
    color: #1a73e8;
    text-transform: uppercase;
    letter-spacing: .04em;
}
form[name='userlist'] .ul-field.ul-btn { flex: 0 0 auto; display: flex; gap: 8px; }
form[name='userlist'] .form-control {
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
form[name='userlist'] .form-control::placeholder { color: #9bbcf0; }
form[name='userlist'] .form-control:focus {
    border-color: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .18);
    outline: none;
}

/* ---------- Buttons ---------- */
form[name='userlist'] .btn {
    height: 36px;
    border-radius: 6px;
    padding: 0 1.4em;
    font-weight: 600;
    letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='userlist'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='userlist'] .btn-success { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='userlist'] .btn-success:hover { background: #1f9c82; }
form[name='userlist'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='userlist'] .btn-secondary:hover { background: #6f7a8d; }

/* ---------- Table ---------- */
.ul-table-wrapper {
    width: 100%;
    overflow-x: auto;
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 8px;
    padding: 10px;
}
.ul-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap; margin-bottom: 0; }
.ul-table-wrapper th, .ul-table-wrapper td { padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important; }
.ul-table-wrapper thead th {
    background: #f7f9fc !important;
    color: #1a73e8 !important;
    font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
.ul-table-wrapper tbody tr:hover { background: rgba(38, 185, 154, .07) !important; }
.ul-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; background: #8a94a6; }
.ul-badge.yes { background: #26b99a; }
.ul-badge.no  { background: #e74c3c; }
.ul-never { color: #8a94a6; font-style: italic; }
.ul-summary { margin: 0 0 10px; font-size: 12px; color: #8a94a6; }
.ul-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='userlist'] .x_content { padding: 14px; }
    form[name='userlist'] .ul-field,
    form[name='userlist'] .ul-field.ul-btn { flex: 1 1 100%; }
    form[name='userlist'] .ul-field.ul-btn .btn { flex: 1; }
}
</style>

<form method="POST" action="" class="rounded" name="userlist" style="min-width:100%">
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> system users</small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <div class="ul-filter">
        <div class="ul-field">
          <label for="search">Search</label>
          <input type="text" class="form-control" name="search" id="search" placeholder="Name, username, email or phone" value="<?= htmlspecialchars($search) ?>" autocomplete="off">
        </div>
        <div class="ul-field">
          <label for="active_f">Status</label>
          <select class="form-control" name="active_f" id="active_f">
            <option value="">All</option>
            <option value="Yes" <?= $active_f === 'Yes' ? 'selected' : '' ?>>Active</option>
            <option value="No"  <?= $active_f === 'No'  ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
        <div class="ul-field">
          <label for="start_date">Last login from</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="ul-field">
          <label for="end_date">Last login to</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="ul-field ul-btn">
          <button type="submit" name="tbn_search" class="btn btn-success btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="index.php?page=<?= $page ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;">Reset</a>
        </div>
      </div>

      <p class="ul-summary">
        Showing <?= (int)$mycount ?> user<?= $mycount == 1 ? '' : 's' ?><?= ($where ? ' matching your filters' : '') ?>.
      </p>

      <div class="ul-table-wrapper">
        <table id="datatable" role="grid" class="table table-striped table-bordered table-sm dataTable">
          <thead>
            <tr>
              <th>Full Names</th>
              <th>User Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Active</th>
              <th>Last Login</th>
            </tr>
          </thead>
          <tbody>
          <?php
          if ($sql_error !== '') {
              echo "<tr><td colspan='6' class='ul-error'>Query error: " . htmlspecialchars($sql_error) . "</td></tr>";
          } else {
              foreach ($rows as $row) {
                  $is_active = (($row['active'] ?? '') === 'Yes');
                  $ll = $row['lastlogin'] ?? ''; ?>
            <tr>
              <td><strong><?= htmlspecialchars($row['fullnames'] ?? '') ?></strong></td>
              <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
              <td><?= htmlspecialchars(($row['email'] ?? '') !== '' ? $row['email'] : '-') ?></td>
              <td><?= htmlspecialchars(($row['phone'] ?? '') !== '' ? $row['phone'] : '-') ?></td>
              <td><span class="ul-badge <?= $is_active ? 'yes' : 'no' ?>"><?= $is_active ? 'Active' : 'Inactive' ?></span></td>
              <td><?php if ($ll !== '' && $ll !== null && strpos($ll, '0000') !== 0) { echo htmlspecialchars($ll); } else { echo "<span class='ul-never'>Never</span>"; } ?></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr>
              <td colspan="6" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <i class="fa fa-info-circle fa-lg"></i> No users found. Try clearing the filters.
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