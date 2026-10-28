<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

global $title;
$conn = $GLOBALS['conn'] ?? null;

// Must match the page name in menu.php and roles_pages (permission key = logistics_party_view/add/edit/delete)
$page = "logistics_party";

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
// Plain:    index.php?page=logistics_party&part=1   /   &part=2&rec=5
// Encoded:  index.php?page=logistics_party&D=<base64 of "part=1">
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
    if (isset($_POST['logistics_delete'])) { caniacess($page . "_delete"); }
}

function lg_notify($type, $title, $text) {
    echo "<script> window.addEventListener('load', function () {
        if (typeof PNotify !== 'undefined') {
            new PNotify({ title: " . json_encode($title) . ", text: " . json_encode($text) . ", nonblock: { nonblock: !0 }, type: '$type', styling: 'bootstrap3' });
        }
    }) </script>";
    $bg = ($type === 'success') ? '#e6f7f2' : '#fdecea';
    $fg = ($type === 'success') ? '#1f7a63' : '#c0392b';
    echo "<div style='margin:10px 15px; padding:10px 16px; border-radius:6px; background:$bg; color:$fg; font-weight:600;'>"
       . htmlspecialchars($title . ': ' . $text) . "</div>";
}

$allowed_status = ['Pending', 'Dispatched', 'Delivered'];

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['logistics_save'])) {
    $item_name     = ucwords(trim($_POST['item_name'] ?? ''));
    $quantity      = trim($_POST['quantity'] ?? '');
    $status        = in_array($_POST['status'] ?? '', $allowed_status, true) ? $_POST['status'] : '';
    $destination   = ucwords(trim($_POST['destination'] ?? ''));
    $dispatch_date = !empty($_POST['dispatch_date']) ? $_POST['dispatch_date'] : date('Y-m-d');
    if (function_exists('dateto_mysql')) { $dispatch_date = dateto_mysql($dispatch_date); }

    if ($item_name !== '' && $quantity !== '' && $status !== '') {
        $stmt = $conn->prepare("INSERT INTO `logistics_party` (item_name, quantity, status, destination, dispatch_date, created_by, created_on) VALUES (?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param("sssssss", $item_name, $quantity, $status, $destination, $dispatch_date, $username, $timestamp);
            if ($stmt->execute()) {
                $alert = 1; $saved_ok = true;
                if (function_exists('audittrail')) { audittrail($conn, 'logistics_party', '', 'inserting', $timestamp, $username); }
            } else {
                $error = "Could not save: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Fill all mandatory fields in red.";
    }
    if ($alert !== '') {
        lg_notify('success', 'Success', 'Data saved successfully!');
        $part = '';   // show the records table so the new entry is visible
    }
    if ($error !== '') { lg_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['logistics_update'])) {
    $item_name     = ucwords(trim($_POST['item_name'] ?? ''));
    $quantity      = trim($_POST['quantity'] ?? '');
    $status        = in_array($_POST['status'] ?? '', $allowed_status, true) ? $_POST['status'] : '';
    $destination   = ucwords(trim($_POST['destination'] ?? ''));
    $dispatch_date = !empty($_POST['dispatch_date']) ? $_POST['dispatch_date'] : date('Y-m-d');
    if (function_exists('dateto_mysql')) { $dispatch_date = dateto_mysql($dispatch_date); }
    $logid         = $_POST['logid'] ?? '';

    if ($item_name !== '' && $quantity !== '' && $status !== '' && $logid !== '') {
        $stmt = $conn->prepare("UPDATE `logistics_party` SET item_name=?, quantity=?, status=?, destination=?, dispatch_date=? WHERE logid=?");
        if ($stmt) {
            $stmt->bind_param("ssssss", $item_name, $quantity, $status, $destination, $dispatch_date, $logid);
            if ($stmt->execute()) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'logistics_party', $logid, 'updatings', $timestamp, $username); }
            } else {
                $error = "Could not update: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Fill all mandatory fields in red.";
    }
    if ($alert !== '') {
        lg_notify('success', 'Success', 'Data updated successfully!');
        $part = '';   // back to the records table
    }
    if ($error !== '') { lg_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['logistics_delete'])) {
    foreach (($_POST['del'] ?? []) as $id) {
        if ($id === '') { continue; }
        if (function_exists('audittrail')) { audittrail($conn, 'logistics_party', $id, 'deleting', $timestamp, $username); }
        $stmt = $conn->prepare("DELETE FROM `logistics_party` WHERE logid=?");
        if ($stmt) {
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) { $deleted++; }
            $stmt->close();
        }
    }
    if ($deleted > 0) { lg_notify('success', 'Success', 'Deleted successfully'); }
    else              { lg_notify('error', 'Error', 'No record selected'); }
}

if (function_exists('_title')) { _title($title = 'Logistics'); } else { $title = 'Logistics'; }
?>

<style>
/* ---------- Panel ---------- */
form[name='logistics'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='logistics'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='logistics'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='logistics'] .x_title h2 small {
    margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6;
    text-transform: uppercase; letter-spacing: .05em;
}
form[name='logistics'] .x_content { padding: 24px 28px; }
form[name='logistics'] .panel_toolbox { list-style: none; margin: 0; padding: 0; }

/* ---------- Form rows ---------- */
form[name='logistics'] .item.form-group { margin-bottom: 18px; }
form[name='logistics'] .col-form-label {
    font-weight: 600;
    font-size: .85rem;
    color: #1a73e8 !important;
    text-align: center;
    padding-right: 0;
}
form[name='logistics'] .manda { color: #e74c3c; font-weight: bold; }

/* ---------- Inputs ---------- */
form[name='logistics'] .form-control {
    border: 1px solid #cfd7e3;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: .9rem;
    color: #1a73e8;
    background-color: #fff;
    text-align: center;
    text-align-last: center;
    transition: border-color .15s, box-shadow .15s;
}
form[name='logistics'] .form-control::placeholder { color: #9bbcf0; }
form[name='logistics'] .form-control:focus {
    border-color: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .18);
    outline: none;
}
form[name='logistics'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='logistics'] .form-control:required:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, .15);
}

/* ---------- Divider & buttons ---------- */
form[name='logistics'] .ln_solid { border-top: 1px solid #e3e8ee; margin: 24px 0 18px; }
form[name='logistics'] .btn {
    border-radius: 6px;
    padding: .45em 1.4em;
    font-weight: 600;
    letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='logistics'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='logistics'] .btn-success { background: #26b99a; border-color: #26b99a; }
form[name='logistics'] .btn-success:hover { background: #1f9c82; }
form[name='logistics'] .btn-primary { background: #1a73e8; border-color: #1a73e8; }
form[name='logistics'] .btn-primary:hover { background: #1558b8; }
form[name='logistics'] .btn-info { background: #34495e; border-color: #34495e; }
form[name='logistics'] .btn-info:hover { background: #2c3e50; }

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
.custom-table-wrapper tbody tr:hover { background-color: rgba(38, 185, 154, .07) !important; }
.custom-table-wrapper .badge { padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; color: #fff; }
.custom-table-wrapper .badge-pending    { background: #f39c12; }
.custom-table-wrapper .badge-dispatched { background: #1a73e8; }
.custom-table-wrapper .badge-delivered  { background: #26b99a; }
.custom-table-wrapper .fa-pencil:hover { color: #1f9c82 !important; }
.custom-table-wrapper input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
.lg-error { color: #c0392b; padding: 14px; text-align: center; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='logistics'] .x_content { padding: 18px 14px; }
    form[name='logistics'] .col-form-label { padding-bottom: 4px; }
}
</style>

<form method="POST" action="" class="rounded" name="logistics" style="min-width:100%" enctype="multipart/form-data">

<?php if ($part == '1') {
    $form = $saved_ok ? [] : $_POST; ?>
<div class="col-md-8 offset-md-2">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> new entry</small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <br />
      <div class="item form-group row">
        <label for="item_name" class="col-form-label col-md-3 col-sm-3 label-align">Item Name <span class="manda">*</span> :</label>
        <div class="col-md-8 col-sm-8">
          <input type="text" required="required" class="form-control form-control-sm" name="item_name" id="item_name" placeholder="Item Name" value="<?= htmlspecialchars($form['item_name'] ?? '') ?>" autocomplete="off">
        </div>
      </div>

      <div class="item form-group row">
        <label for="quantity" class="col-form-label col-md-3 col-sm-3 label-align">Quantity <span class="manda">*</span> :</label>
        <div class="col-md-8 col-sm-8">
          <input type="text" required="required" class="form-control form-control-sm" name="quantity" id="quantity" placeholder="Quantity" value="<?= htmlspecialchars($form['quantity'] ?? '') ?>" autocomplete="off">
        </div>
      </div>

      <div class="item form-group row">
        <label for="status" class="col-form-label col-md-3 col-sm-3 label-align">Status <span class="manda">*</span> :</label>
        <div class="col-md-8 col-sm-8">
          <select required="required" class="form-control form-control-sm" name="status" id="status">
            <option value="">-- Select Status --</option>
            <?php foreach ($allowed_status as $st) { ?>
            <option value="<?= $st ?>" <?= (($form['status'] ?? '') === $st) ? 'selected' : '' ?>><?= $st ?></option>
            <?php } ?>
          </select>
        </div>
      </div>

      <div class="item form-group row">
        <label for="destination" class="col-form-label col-md-3 col-sm-3 label-align">Destination :</label>
        <div class="col-md-8 col-sm-8">
          <input type="text" class="form-control form-control-sm" name="destination" id="destination" placeholder="Destination" value="<?= htmlspecialchars($form['destination'] ?? '') ?>" autocomplete="off">
        </div>
      </div>

      <div class="item form-group row">
        <label for="dispatch_date" class="col-form-label col-md-3 col-sm-3 label-align">Dispatch Date :</label>
        <div class="col-md-8 col-sm-8">
          <input type="date" class="form-control form-control-sm" name="dispatch_date" id="dispatch_date" value="<?= htmlspecialchars($form['dispatch_date'] ?? date('Y-m-d')) ?>">
        </div>
      </div>

      <div class="ln_solid"></div>
      <div class="form-group row text-center">
        <div class="col-md-12">
          <button type="submit" name="logistics_save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>" class="btn btn-info btn-sm"><span>View</span> <i class="fa fa-search"></i></a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php } elseif ($part == '2') { ?>
<div class="col-md-8 offset-md-2">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> update entry</small></h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <br />
      <?php
      $result = false;
      $stmt = $conn->prepare("SELECT * FROM `logistics_party` WHERE logid=?");
      if ($stmt) {
          $stmt->bind_param("s", $rec);
          $stmt->execute();
          $result = $stmt->get_result();
      } else {
          echo "<div class='lg-error'>Query error: " . htmlspecialchars($conn->error) . "</div>";
      }
      $found = false;
      while ($result && $row = $result->fetch_assoc()) { $found = true; ?>

      <div class="item form-group row">
        <label for="item_name" class="col-form-label col-md-3 col-sm-3 label-align">Item Name <span class="manda">*</span> :</label>
        <div class="col-md-8 col-sm-8">
          <input type="text" required="required" class="form-control form-control-sm" name="item_name" id="item_name" value="<?= htmlspecialchars($row['item_name'] ?? '') ?>" autocomplete="off">
        </div>
      </div>

      <div class="item form-group row">
        <label for="quantity" class="col-form-label col-md-3 col-sm-3 label-align">Quantity <span class="manda">*</span> :</label>
        <div class="col-md-8 col-sm-8">
          <input type="text" required="required" class="form-control form-control-sm" name="quantity" id="quantity" value="<?= htmlspecialchars($row['quantity'] ?? '') ?>" autocomplete="off">
        </div>
      </div>

      <div class="item form-group row">
        <label for="status" class="col-form-label col-md-3 col-sm-3 label-align">Status <span class="manda">*</span> :</label>
        <div class="col-md-8 col-sm-8">
          <select required="required" class="form-control form-control-sm" name="status" id="status">
            <?php foreach ($allowed_status as $st) { ?>
            <option value="<?= $st ?>" <?= (($row['status'] ?? '') === $st) ? 'selected' : '' ?>><?= $st ?></option>
            <?php } ?>
          </select>
        </div>
      </div>

      <div class="item form-group row">
        <label for="destination" class="col-form-label col-md-3 col-sm-3 label-align">Destination :</label>
        <div class="col-md-8 col-sm-8">
          <input type="text" class="form-control form-control-sm" name="destination" id="destination" value="<?= htmlspecialchars($row['destination'] ?? '') ?>" autocomplete="off">
        </div>
      </div>

      <div class="item form-group row">
        <label for="dispatch_date" class="col-form-label col-md-3 col-sm-3 label-align">Dispatch Date :</label>
        <div class="col-md-8 col-sm-8">
          <input type="date" class="form-control form-control-sm" name="dispatch_date" id="dispatch_date" value="<?= htmlspecialchars($row['dispatch_date'] ?? '') ?>">
        </div>
      </div>

      <div class="ln_solid"></div>
      <div class="form-group row text-center">
        <div class="col-md-12">
          <button type="submit" name="logistics_update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>&part=1" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file"></i></a>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>" class="btn btn-info btn-sm"><span>View</span> <i class="fa fa-search"></i></a>
        </div>
      </div>
      <input name="logid" type="hidden" value="<?= htmlspecialchars($row['logid']) ?>" />
      <?php }
      if ($stmt) { $stmt->close(); }
      if (!$found && $result) { echo "<div class='lg-error'>Record not found.</div>"; } ?>
    </div>
  </div>
</div>

<?php } else { ?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small> existing records</small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="index.php?page=<?= $page ?>&part=1" class="btn btn-sm btn-primary text-white"><i class="fa fa-plus"></i> Create New</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <div class="custom-table-wrapper">
        <table id="datatable" role="grid" class="table table-striped table-bordered dataTable table-sm">
          <thead>
            <tr>
              <th width="1px">Logistics No.</th>
              <th>Item Name</th>
              <th>Quantity</th>
              <th>Status</th>
              <th>Destination</th>
              <th>Dispatch Date</th>
              <th>Created By</th>
              <th>Created On</th>
              <th width="1%">Edit</th>
              <th width="1%">Del</th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <th colspan="9"></th>
              <th><button name="logistics_delete" value="Delete" type="submit" class="btn btn-sm btn-danger btn-block" onclick="return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button></th>
            </tr>
          </tfoot>
          <tbody>
          <?php
          $mycount = 0;
          if (isset($_POST['tbn_search'])) {
              $s = $conn->real_escape_string($_POST['search'] ?? '');
              $result = mysqli_query($conn, "SELECT * FROM `logistics_party` WHERE item_name LIKE '%$s%' OR status LIKE '%$s%' OR destination LIKE '%$s%'");
          } else {
              $result = mysqli_query($conn, "SELECT * FROM `logistics_party` ORDER BY logid DESC");
          }
          if (!$result) {
              echo "<tr><td colspan='10' class='lg-error'>Query error: " . htmlspecialchars($conn->error) . "</td></tr>";
          } else {
              while ($row = mysqli_fetch_assoc($result)) {
                  $mycount++;
                  $st  = $row['status'] ?? '';
                  $cls = 'badge-' . strtolower($st); ?>
            <tr>
              <td><?= htmlspecialchars($row['logid']) ?></td>
              <td><?= htmlspecialchars($row['item_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['quantity'] ?? '') ?></td>
              <td><span class="badge <?= htmlspecialchars($cls) ?>"><?= htmlspecialchars($st) ?></span></td>
              <td><?= htmlspecialchars($row['destination'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['dispatch_date'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['created_by'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['created_on'] ?? '') ?></td>
              <td align="center"><a href="index.php?page=<?= $page ?>&part=2&rec=<?= urlencode($row['logid']) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil text-primary"></i></a></td>
              <td align="center"><input name="del[]" type="checkbox" value="<?= htmlspecialchars($row['logid']) ?>" /></td>
            </tr>
          <?php }
              if ($mycount == 0) { ?>
            <tr>
              <td colspan="10" class="text-center" style="padding:30px; color:#73879C; font-size:14px;">
                <i class="fa fa-info-circle fa-lg"></i> No logistical records found. Click <a href="index.php?page=<?= $page ?>&part=1" class="alert-link"><u>Create New</u></a> to get started.
              </td>
            </tr>
          <?php }
          } ?>
          </tbody>
        </table>
      </div>
      <input type="hidden" name="txtcount" id="txtcount" value="<?= $mycount ?>">
    </div>
  </div>
</div>
<?php } ?>

</form>
<?php } ?>