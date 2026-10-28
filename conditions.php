<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

global $title;
$conn = $GLOBALS['conn'] ?? null;

$page = "conditions";

// ---------- Safe defaults ----------
$part      = isset($_GET['part']) ? trim((string)$_GET['part']) : '';
$rec       = isset($_GET['rec'])  ? trim((string)$_GET['rec'])  : '';
$username  = $username ?? ($_SESSION['username'] ?? ($_SESSION['userusername'] ?? 'System'));
$deleted   = 0;
$alert     = '';
$error     = '';
$saved_ok  = false;
$timestamp = date('Y-m-d H:i:s');

// ---------- Routing ----------
// Works with  index.php?page=conditions&part=1&rec=5   (plain)
// and with    index.php?page=conditions&D=<base64 of "part=1">   (encoded)
if (!empty($_GET['D'])) {
    if (function_exists('workout')) {
        workout($_GET['D']);
    } else {
        parse_str((string)base64_decode($_GET['D']), $__d);
        if (isset($__d['part'])) { $part = (string)$__d['part']; }
        if (isset($__d['rec']))  { $rec  = (string)$__d['rec']; }
    }
    $part = trim((string)$part);
}

if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['conditions_delete'])) { caniacess($page . "_delete"); }
}

function cond_notify($type, $title, $text) {
    // popup (needs PNotify) ...
    echo "<script> window.addEventListener('load', function () {
        if (typeof PNotify !== 'undefined') {
            new PNotify({ title: " . json_encode($title) . ", text: " . json_encode($text) . ", nonblock: { nonblock: !0 }, type: '$type', styling: 'bootstrap3' });
        }
    }) </script>";
    // ... plus a message that always shows, even if PNotify is not loaded
    $bg = ($type === 'success') ? '#e6f7f2' : '#fdecea';
    $fg = ($type === 'success') ? '#1f7a63' : '#c0392b';
    echo "<div style='margin:10px 15px; padding:10px 16px; border-radius:6px; background:$bg; color:$fg; font-weight:600;'>"
       . htmlspecialchars($title . ': ' . $text) . "</div>";
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['conditions_save'])) {
    $description = ucwords(trim($_POST['description'] ?? ''));
    $status      = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
    $notes       = trim($_POST['notes'] ?? '');

    if ($description !== '') {
        $stmt = $conn->prepare("INSERT INTO `conditions` (description, status, notes, username, timestamp) VALUES (?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $description, $status, $notes, $username, $timestamp);
            if ($stmt->execute()) {
                $alert = 1; $saved_ok = true;
                $new_id = $conn->insert_id;
                if (function_exists('audittrail')) { audittrail($conn, 'conditions', '', 'inserting', $timestamp, $username); }
            } else {
                $error = "Could not save: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Fill all mandatory fields marked with an asterisk (*).";
    }
    if ($alert !== '') {
        cond_notify('success', 'Success', 'Data saved successfully! (ID ' . ($new_id ?? '?') . ')');
        $part = '';   // show the records table so the new entry is visible
    }
    if ($error !== '') { cond_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['conditions_update'])) {
    $description = ucwords(trim($_POST['description'] ?? ''));
    $status      = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
    $notes       = trim($_POST['notes'] ?? '');
    $logid       = $_POST['logid'] ?? '';

    if ($description !== '' && $logid !== '') {
        $stmt = $conn->prepare("UPDATE `conditions` SET description=?, status=?, notes=?, username=?, timestamp=? WHERE logid=?");
        if ($stmt) {
            $stmt->bind_param("ssssss", $description, $status, $notes, $username, $timestamp, $logid);
            if ($stmt->execute()) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'conditions', $logid, 'updatings', $timestamp, $username); }
            } else {
                $error = "Could not update: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Fill all mandatory fields marked with an asterisk (*).";
    }
    if ($alert !== '') {
        cond_notify('success', 'Success', 'Data updated successfully!');
        $part = '';   // back to the records table
    }
    if ($error !== '') { cond_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['conditions_delete'])) {
    foreach (($_POST['del'] ?? []) as $id) {
        if ($id === '') { continue; }
        if (function_exists('audittrail')) { audittrail($conn, 'conditions', $id, 'deleting', $timestamp, $username); }
        $stmt = $conn->prepare("DELETE FROM `conditions` WHERE logid=?");
        if ($stmt) {
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) { $deleted++; }
            $stmt->close();
        }
    }
    if ($deleted > 0) { cond_notify('success', 'Success', 'Deleted successfully'); }
    else              { cond_notify('error', 'Error', 'No record selected'); }
}

$display_title = 'Medical Conditions';
if (function_exists('_title')) { _title($title = $display_title); }
?>

<style>
/* ---------- Panel ---------- */
form[name='conditions'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='conditions'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='conditions'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='conditions'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='conditions'] .x_content { padding: 24px 28px; }

/* ---------- Form rows ---------- */
form[name='conditions'] .item.form-group { margin-bottom: 18px; }
form[name='conditions'] .col-form-label {
    font-weight: 600;
    font-size: .85rem;
    color: #1a73e8 !important;
    text-align: center;
    padding-right: 0;
}
form[name='conditions'] .manda { color: #e74c3c; font-weight: bold; }

/* ---------- Inputs ---------- */
form[name='conditions'] .form-control {
    border: 1px solid #cfd7e3;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: .9rem;
    color: #1a73e8;
    background-color: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='conditions'] input.form-control,
form[name='conditions'] select.form-control { text-align: center; text-align-last: center; }
form[name='conditions'] textarea.form-control { min-height: 100px; resize: vertical; }
form[name='conditions'] .form-control::placeholder { color: #9bbcf0; }
form[name='conditions'] .form-control:focus {
    border-color: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .18);
    outline: none;
}
form[name='conditions'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='conditions'] .form-control:required:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, .15);
}

/* ---------- Divider & buttons ---------- */
form[name='conditions'] .ln_solid { border-top: 1px solid #e3e8ee; margin: 24px 0 18px; }
form[name='conditions'] .btn {
    border-radius: 6px;
    padding: .45em 1.4em;
    font-weight: 600;
    letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='conditions'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='conditions'] .btn-success { background: #26b99a; border-color: #26b99a; }
form[name='conditions'] .btn-success:hover { background: #1f9c82; }
form[name='conditions'] .btn-primary { background: #1a73e8; border-color: #1a73e8; }
form[name='conditions'] .btn-primary:hover { background: #1558b8; }

/* ---------- Table ---------- */
.custom-table-wrapper {
    width: 100% !important;
    overflow-x: auto !important;
    margin-top: 15px;
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 8px;
    padding: 10px;
}
.custom-table-wrapper table { width: 100% !important; max-width: none !important; white-space: nowrap !important; }
.custom-table-wrapper th, .custom-table-wrapper td {
    padding: 10px 14px !important; vertical-align: middle !important; font-size: 13px !important;
}
.custom-table-wrapper thead th, .custom-table-wrapper tfoot th {
    background-color: #f7f9fc !important; color: #1a73e8 !important; font-weight: 700 !important;
    border-bottom: 2px solid #26b99a !important;
}
.custom-table-wrapper td.cond-notes { white-space: normal !important; min-width: 220px; max-width: 360px; word-break: break-word; }
.custom-table-wrapper tbody tr:hover { background-color: rgba(38, 185, 154, .07) !important; }
.custom-table-wrapper .badge { padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; }
.custom-table-wrapper .badge-success   { background: #26b99a; }
.custom-table-wrapper .badge-secondary { background: #8a94a6; }
.custom-table-wrapper .fa-pencil:hover { color: #1f9c82 !important; }
.custom-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
.cond-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='conditions'] .x_content { padding: 18px 14px; }
    form[name='conditions'] .col-form-label { padding-bottom: 4px; }
}
</style>

<div class="row">
  <div class="col-md-12 col-sm-12 col-xs-12">
    <form method="POST" action="" class="form-horizontal form-label-left" name="conditions" style="min-width:100%" enctype="multipart/form-data">

    <?php if ($part == '1') {
        $form = $saved_ok ? [] : $_POST; ?>
    <div class="col-md-8 offset-md-2 col-sm-12">
      <div class="x_panel">
        <div class="x_title">
          <h2><?= htmlspecialchars(ucwords($display_title)) ?><small> new entry</small></h2>
          <div class="clearfix"></div>
        </div>
        <div class="x_content">
          <br />
          <div class="item form-group row">
            <label for="description" class="col-form-label col-md-3 col-sm-3 label-align">Description <span class="manda">*</span> :</label>
            <div class="col-md-8 col-sm-8">
              <input type="text" required="required" class="form-control form-control-sm" name="description" id="description" placeholder="e.g. Hypertension, Diabetes Type II" value="<?= htmlspecialchars($form['description'] ?? '') ?>" autocomplete="off">
            </div>
          </div>

          <div class="item form-group row">
            <label for="status" class="col-form-label col-md-3 col-sm-3 label-align">Status <span class="manda">*</span> :</label>
            <div class="col-md-8 col-sm-8">
              <select class="form-control form-control-sm" name="status" id="status">
                <option value="Active" <?= (($form['status'] ?? 'Active') == 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= (($form['status'] ?? '') == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
          </div>

          <div class="item form-group row">
            <label for="notes" class="col-form-label col-md-3 col-sm-3 label-align">Notes / Remarks :</label>
            <div class="col-md-8 col-sm-8">
              <textarea class="form-control" name="notes" id="notes" placeholder="Enter condition remarks or restrictions..."><?= htmlspecialchars($form['notes'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="ln_solid"></div>
          <div class="form-group row text-center">
            <div class="col-md-12">
              <button type="submit" name="conditions_save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
              &nbsp; &nbsp;
              <a href="index.php?page=<?= $page ?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
              &nbsp; &nbsp;
              <a href="index.php?page=<?= $page ?>" class="btn btn-primary btn-sm"><span>View</span> <i class="fa fa-search"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php } elseif ($part == '2') { ?>
    <div class="col-md-8 offset-md-2 col-sm-12">
      <div class="x_panel">
        <div class="x_title">
          <h2><?= htmlspecialchars(ucwords($display_title)) ?><small> update entry</small></h2>
          <div class="clearfix"></div>
        </div>
        <div class="x_content">
          <br />
          <?php
          $result = false;
          $stmt = $conn->prepare("SELECT * FROM `conditions` WHERE logid=?");
          if ($stmt) {
              $stmt->bind_param("s", $rec);
              $stmt->execute();
              $result = $stmt->get_result();
          } else {
              echo "<div class='cond-error'>Query error: " . htmlspecialchars($conn->error) . "</div>";
          }
          $found = false;
          while ($result && $row = $result->fetch_assoc()) { $found = true; ?>

          <div class="item form-group row">
            <label for="description" class="col-form-label col-md-3 col-sm-3 label-align">Description <span class="manda">*</span> :</label>
            <div class="col-md-8 col-sm-8">
              <input type="text" required="required" class="form-control form-control-sm" name="description" id="description" value="<?= htmlspecialchars($row['description'] ?? '') ?>" autocomplete="off">
            </div>
          </div>

          <div class="item form-group row">
            <label for="status" class="col-form-label col-md-3 col-sm-3 label-align">Status <span class="manda">*</span> :</label>
            <div class="col-md-8 col-sm-8">
              <select class="form-control form-control-sm" name="status" id="status">
                <option value="Active" <?= (($row['status'] ?? '') == 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= (($row['status'] ?? '') == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
          </div>

          <div class="item form-group row">
            <label for="notes" class="col-form-label col-md-3 col-sm-3 label-align">Notes / Remarks :</label>
            <div class="col-md-8 col-sm-8">
              <textarea class="form-control" name="notes" id="notes"><?= htmlspecialchars($row['notes'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="ln_solid"></div>
          <div class="form-group row text-center">
            <div class="col-md-12">
              <button type="submit" name="conditions_update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
              &nbsp; &nbsp;
              <a href="index.php?page=<?= $page ?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
              &nbsp; &nbsp;
              <a href="index.php?page=<?= $page ?>" class="btn btn-primary btn-sm"><span>View</span> <i class="fa fa-search"></i></a>
            </div>
          </div>
          <input name="logid" type="hidden" value="<?= htmlspecialchars($row['logid']) ?>" />
          <?php }
          if ($stmt) { $stmt->close(); }
          if (!$found && $result) { echo "<div class='cond-error'>Record not found.</div>"; } ?>
        </div>
      </div>
    </div>

    <?php } else { ?>
    <div class="col-md-12 col-sm-12 col-xs-12">
      <div class="x_panel">
        <div class="x_title">
          <h2><?= htmlspecialchars(ucwords($display_title)) ?><small> existing records</small></h2>
          <ul class="nav navbar-right panel_toolbox" style="list-style:none;">
            <li><a href="index.php?page=<?= $page ?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Create New</a></li>
          </ul>
          <div class="clearfix"></div>
        </div>
        <div class="x_content">
          <div class="custom-table-wrapper">
            <table id="datatable" role="grid" class="table table-striped table-bordered dataTable table-sm">
              <thead>
                <tr>
                  <th width="5%">#</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th>Notes</th>
                  <th>Username</th>
                  <th>Timestamp</th>
                  <th width="1%">Edit</th>
                  <th width="1%">Del</th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                  <th colspan="7"></th>
                  <th><button name="conditions_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onclick="return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button></th>
                </tr>
              </tfoot>
              <tbody>
              <?php
              $mycount = 0;
              if (isset($_POST['tbn_search'])) {
                  $s = $conn->real_escape_string($_POST['search'] ?? '');
                  $result = mysqli_query($conn, "SELECT * FROM `conditions` WHERE description LIKE '%$s%' OR username LIKE '%$s%' OR timestamp LIKE '%$s%'");
              } else {
                  $result = mysqli_query($conn, "SELECT * FROM `conditions` ORDER BY logid DESC");
              }
              if (!$result) {
                  echo "<tr><td colspan='8' class='cond-error'>Query error: " . htmlspecialchars($conn->error) . "</td></tr>";
              } else {
                  while ($row = mysqli_fetch_assoc($result)) { $mycount++; ?>
                <tr>
                  <td><?= $mycount ?></td>
                  <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                  <td><span class="badge <?= (($row['status'] ?? '') == 'Active') ? 'badge-success' : 'badge-secondary' ?>"><?= htmlspecialchars($row['status'] ?? 'Active') ?></span></td>
                  <td class="cond-notes"><?= htmlspecialchars(($row['notes'] ?? '') !== '' ? $row['notes'] : '-') ?></td>
                  <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['timestamp'] ?? '') ?></td>
                  <td align="center"><a href="index.php?page=<?= $page ?>&part=2&rec=<?= urlencode($row['logid']) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil text-primary"></i></a></td>
                  <td align="center"><input name="del[]" type="checkbox" value="<?= htmlspecialchars($row['logid']) ?>" /></td>
                </tr>
              <?php }
                  if ($mycount == 0) { ?>
                <tr>
                  <td colspan="8" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                    <i class="fa fa-info-circle fa-lg"></i> No conditions found. Click <a href="index.php?page=<?= $page ?>&part=1" class="alert-link"><u>Create New</u></a> to get started.
                  </td>
                </tr>
              <?php }
              } ?>
              </tbody>
            </table>
          </div>
          <input type="hidden" name="txtcount" id="txtcount" value="<?= $mycount ?>">
          <?php
          $dbn = ''; $tot = '?';
          if ($r1 = mysqli_query($conn, "SELECT DATABASE() AS d")) { $dbn = ($r1->fetch_assoc()['d'] ?? ''); }
          if ($r2 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `conditions`")) { $tot = ($r2->fetch_assoc()['c'] ?? '?'); }
          ?>
          <p style="margin-top:10px; font-size:12px; color:#8a94a6; text-align:right;">
            Database: <?= htmlspecialchars($dbn) ?> &nbsp;|&nbsp; Table: conditions &nbsp;|&nbsp; Rows in table: <?= htmlspecialchars((string)$tot) ?>
          </p>
        </div>
      </div>
    </div>
    <?php } ?>

    </form>
  </div>
</div>
<?php } ?>