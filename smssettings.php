<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2023-04-09 14:02:00

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

// ---------- Safe defaults ----------
$conn      = $conn ?? ($GLOBALS['conn'] ?? null);
$page      = "smssettings";
$part      = $part ?? '';
$rec       = $rec ?? '';
$alert     = '';
$error     = '';
$deleted   = 0;
$username  = $username ?? ($_SESSION['username'] ?? ($_SESSION['userusername'] ?? 'System'));
$timestamp = date('Y-m-d H:i:s');

// ---------- Routing: read ?D= (base64 of "part=1" or "part=2&rec=5") ----------
if (function_exists('workout') && isset($_GET['D'])) {
    workout($_GET['D']);
} elseif (!empty($_GET['D'])) {
    parse_str((string)base64_decode($_GET['D']), $__d);
    if (isset($__d['part'])) { $part = $__d['part']; }
    if (isset($__d['rec']))  { $rec  = $__d['rec']; }
}
$part = trim((string)$part);

// Builds the ?D= value for links (uses osteens() when it exists)
function sms_d($q) { return function_exists('osteens') ? osteens($q) : base64_encode($q); }

if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['smssettings_delete'])) { caniacess($page . "_delete"); }
}

function sms_notify($type, $title, $text) {
    echo "<script> window.addEventListener('load', function () {
        new PNotify({ title: " . json_encode($title) . ", text: " . json_encode($text) . ", nonblock: { nonblock: !0 }, type: '$type', styling: 'bootstrap3' }); }) </script>";
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['smssettings_save'])) {
    $smstype = strtoupper(trim($_POST['smstype'] ?? ''));
    $member  = trim($_POST['member'] ?? '');
    $gleader = trim($_POST['gleader'] ?? '');

    if ($smstype !== '') {
        $stmt = $conn->prepare("INSERT INTO smssettings (smstype, member, gleader, username, timestamp) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $smstype, $member, $gleader, $username, $timestamp);
        if ($stmt->execute()) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, 'smssettings', '', 'inserting', $timestamp, $username); }
        } else {
            $error = "Error: you are duplicating data!";
        }
        $stmt->close();
    } else {
        $error = "Fill all mandatory fields in red.";
    }
    if ($alert !== '') { sms_notify('success', 'Success', 'Data saved successfully!'); }
    if ($error !== '') { sms_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if (isset($_POST['smssettings_update'])) {
    $smstype = strtoupper(trim($_POST['smstype'] ?? ''));
    $member  = trim($_POST['member'] ?? '');
    $gleader = trim($_POST['gleader'] ?? '');
    $logid   = $_POST['logid'] ?? '';

    if ($smstype !== '' && $logid !== '') {
        $stmt = $conn->prepare("UPDATE smssettings SET smstype=?, member=?, gleader=?, username=?, timestamp=? WHERE logid=?");
        $stmt->bind_param("ssssss", $smstype, $member, $gleader, $username, $timestamp, $logid);
        if ($stmt->execute()) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, 'smssettings', $logid, 'updatings', $timestamp, $username); }
        } else {
            $error = "Error: you are duplicating data!";
        }
        $stmt->close();
    } else {
        $error = "Fill all mandatory fields in red.";
    }
    if ($alert !== '') { sms_notify('success', 'Success', 'Data updated successfully!'); }
    if ($error !== '') { sms_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['smssettings_delete'])) {
    foreach (($_POST['del'] ?? []) as $id) {
        if ($id === '') { continue; }
        if (function_exists('audittrail')) { audittrail($conn, 'smssettings', $id, 'deleting', $timestamp, $username); }
        $stmt = $conn->prepare("DELETE FROM smssettings WHERE logid=?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) { $deleted++; }
        $stmt->close();
    }
    if ($deleted > 0) { sms_notify('success', 'Success', 'Deleted successfully'); }
    else              { sms_notify('error', 'Error', 'No record selected'); }
}

$title = 'Sms Templates';
if (function_exists('_title')) { _title($title); }
?>

<style>
/* ---------- Panel ---------- */
form[name='smssettings'] .x_panel {
    background: #fff;
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    overflow: hidden;
    margin: 1rem auto;
}
form[name='smssettings'] .x_title {
    background: #f7f9fc;
    border-bottom: 1px solid #e3e8ee;
    padding: 14px 20px;
    margin: 0;
}
form[name='smssettings'] .x_title h2 {
    font-size: 1.15rem;
    font-weight: 600;
    color: #1a73e8;
    margin: 0;
}
form[name='smssettings'] .x_content { padding: 24px 28px; overflow-x: auto; }
form[name='smssettings'] .panel_toolbox { list-style: none; margin: 0; padding: 0; }
form[name='smssettings'] .panel_toolbox a { color: #1a73e8; font-weight: 600; }
form[name='smssettings'] .panel_toolbox a:hover { color: #1558b8; text-decoration: none; }

/* ---------- Form rows ---------- */
form[name='smssettings'] .item.form-group {
    display: flex;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
form[name='smssettings'] .item.form-group > label {
    flex: 0 0 25%;
    max-width: 25%;
    padding: 6px 14px 0 0;
    margin: 0;
    font-weight: 600;
    font-size: .85rem;
    color: #1a73e8;
    text-align: center;
}
form[name='smssettings'] .item.form-group > label.manda::after { content: " *"; color: #e74c3c; }
form[name='smssettings'] .item.form-group > div[class*='col-sm-'] { flex: 1 1 0; max-width: 66%; }

/* ---------- Inputs ---------- */
form[name='smssettings'] .form-control {
    width: 100%;
    border: 1px solid #cfd7e3;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: .9rem;
    color: #1a73e8;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='smssettings'] input.form-control { text-align: center; }
form[name='smssettings'] textarea.form-control { min-height: 110px; resize: vertical; }
form[name='smssettings'] .form-control::placeholder { color: #9bbcf0; }
form[name='smssettings'] .form-control:focus {
    border-color: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .18);
    outline: none;
}
form[name='smssettings'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='smssettings'] .form-control:required:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, .15);
}

/* ---------- Divider & buttons ---------- */
form[name='smssettings'] .ln_solid { border-top: 1px solid #e3e8ee; margin: 20px 0 18px; }
form[name='smssettings'] .btn {
    border-radius: 6px;
    padding: .45em 1.4em !important;
    font-weight: 600;
    letter-spacing: .03em;
    transition: transform .1s, box-shadow .15s, background .15s;
}
form[name='smssettings'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='smssettings'] .btn-success { background: #26b99a; border-color: #26b99a; }
form[name='smssettings'] .btn-success:hover { background: #1f9c82; }
form[name='smssettings'] .btn-info { background: #1a73e8; border-color: #1a73e8; }
form[name='smssettings'] .btn-info:hover { background: #1558b8; }

/* ---------- Table ---------- */
form[name='smssettings'] .table { width: 100%; margin-bottom: 0; }
form[name='smssettings'] .table th,
form[name='smssettings'] .table td { padding: 10px 14px; vertical-align: middle; font-size: 13px; }
form[name='smssettings'] .table thead th {
    background: #f7f9fc;
    color: #1a73e8;
    font-weight: 700;
    border-bottom: 2px solid #26b99a;
    white-space: nowrap;
}
form[name='smssettings'] .table td.sms-text { max-width: 320px; white-space: normal; word-break: break-word; }
form[name='smssettings'] .table tfoot th { background: #f7f9fc; border-top: 1px solid #e3e8ee; }
form[name='smssettings'] .table-striped tbody tr:hover { background: rgba(38, 185, 154, .07); }
form[name='smssettings'] .fa-pencil { color: #1a73e8; }
form[name='smssettings'] .fa-pencil:hover { color: #1f9c82; }
form[name='smssettings'] .table input[type='checkbox'] { accent-color: #e74c3c; cursor: pointer; }
form[name='smssettings'] button[name='smssettings_delete'] {
    background: #e74c3c; border: 0; color: #fff; border-radius: 6px; padding: 5px 12px;
}
form[name='smssettings'] button[name='smssettings_delete']:hover { background: #c0392b; }
form[name='smssettings'] .sms-error { color: #c0392b; padding: 14px; }

/* ---------- Mobile ---------- */
@media (max-width: 767px) {
    form[name='smssettings'] { min-width: 100% !important; }
    form[name='smssettings'] .item.form-group > label,
    form[name='smssettings'] .item.form-group > div[class*='col-sm-'] { flex: 0 0 100%; max-width: 100%; }
    form[name='smssettings'] .item.form-group > label { padding-bottom: 4px; }
    form[name='smssettings'] .x_content { padding: 18px 14px; }
}
</style>

<form method="POST" action="" class="rounded" name="smssettings" style="min-width:70%" enctype="multipart/form-data">

<?php if ($part == '1') { ?>
<div class="col-md-8 offset-2">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a class="link"><i class="fa fa-chevron-up"></i></a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content center-block">

      <div class="item form-group">
        <label for="smstype" class="col-sm-3 manda">Sms type :</label>
        <div class="col-sm-8">
          <input type="text" required="required" class="form-control form-control-sm" name="smstype" id="smstype" placeholder="Sms type" value="<?= htmlspecialchars($_POST['smstype'] ?? '') ?>" autocomplete="off" onblur="js_validate(this,'general')">
        </div>
      </div>

      <div class="item form-group">
        <label for="member" class="col-sm-3">Member SMS :</label>
        <div class="col-sm-8">
          <textarea class="form-control form-control-sm" name="member" id="member"><?= htmlspecialchars($_POST['member'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="item form-group">
        <label for="gleader" class="col-sm-3">Group leader SMS :</label>
        <div class="col-sm-8">
          <textarea class="form-control form-control-sm" name="gleader" id="gleader"><?= htmlspecialchars($_POST['gleader'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="ln_solid"></div>
      <div class="form-group row text-center">
        <div class="col-md">
          <button title="Save data" type="submit" name="smssettings_save" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>&D=<?= sms_d('part=1') ?>" title="Add New Record"><button type="button" name="new" class="btn btn-success btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button></a>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>" title="Go to records"><button type="button" class="btn btn-success btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button></a>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>" title="Back"><button type="button" class="btn btn-info btn-sm"><i class="fa fa-reply" style="float:none;"></i> &nbsp;<span>Back</span></button></a>
        </div>
      </div>

    </div><!-- x_content -->
  </div><!-- x_panel -->
</div><!-- col-md-8 -->

<?php } elseif ($part == '2') { ?>
<div class="col-md-8 offset-2">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a class="link"><i class="fa fa-chevron-up"></i></a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content center-block">
      <?php
      $stmt = $conn->prepare("SELECT * FROM smssettings WHERE logid=?");
      $row_found = false;
      if ($stmt) {
          $stmt->bind_param("s", $rec);
          $stmt->execute();
          $result = $stmt->get_result();
      } else {
          $result = false;
          echo "<div class='sms-error'>Query error: " . htmlspecialchars($conn->error) . "</div>";
      }
      while ($result && $row = $result->fetch_assoc()) { ?>

        <div class="item form-group">
          <label for="smstype" class="col-sm-3 manda">Sms type :</label>
          <div class="col-sm-8">
            <input type="text" required="required" class="form-control form-control-sm" name="smstype" id="smstype" placeholder="Sms type" value="<?= htmlspecialchars($row['smstype'] ?? '') ?>" autocomplete="off" onblur="js_validate(this,'general')">
          </div>
        </div>

        <div class="item form-group">
          <label for="member" class="col-sm-3">Member SMS :</label>
          <div class="col-sm-8">
            <textarea class="form-control form-control-sm" name="member" id="member"><?= htmlspecialchars($row['member'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="item form-group">
          <label for="gleader" class="col-sm-3">Group leader SMS :</label>
          <div class="col-sm-8">
            <textarea class="form-control form-control-sm" name="gleader" id="gleader"><?= htmlspecialchars($row['gleader'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="ln_solid"></div>
        <div class="form-group row text-center">
          <div class="col-md">
            <button title="Update data" type="submit" name="smssettings_update" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
            &nbsp; &nbsp;
            <a href="index.php?page=<?= $page ?>&D=<?= sms_d('part=1') ?>" title="Add New Record"><button type="button" name="new" class="btn btn-success btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button></a>
            &nbsp; &nbsp;
            <a href="index.php?page=<?= $page ?>" title="Go to records"><button type="button" class="btn btn-success btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button></a>
            &nbsp; &nbsp;
            <a href="index.php?page=<?= $page ?>" title="Back"><button type="button" class="btn btn-info btn-sm"><i class="fa fa-reply" style="float:none;"></i> &nbsp;<span>Back</span></button></a>
          </div>
        </div>
        <input name="logid" type="hidden" value="<?= htmlspecialchars($row['logid']) ?>" />

      <?php } if ($stmt) { $stmt->close(); } ?>
    </div><!-- x_content -->
  </div><!-- x_panel -->
</div><!-- col-md-8 -->

<?php } else { ?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="index.php?page=<?= $page ?>&D=<?= sms_d('part=1') ?>" class="link"><i class="fa fa-plus"></i> Create</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <table id="datatable" style="width:100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
        <thead>
          <tr>
            <th width="1px">#</th>
            <th>Sms Type</th>
            <th>Member Sms</th>
            <th>Group Leader Sms</th>
            <th>Username</th>
            <th>Timestamp</th>
            <th width="1%">Edit</th>
            <th width="1%">Del</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th colspan="7"></th>
            <th><button name="smssettings_delete" value="Delete" type="submit" class="button sample btn-sm" onclick="return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button></th>
          </tr>
        </tfoot>
        <tbody>
        <?php
        $mycount = 0;
        if (isset($_POST['tbn_search'])) {
            $s = $conn->real_escape_string($_POST['search'] ?? '');
            $result = mysqli_query($conn, "SELECT * FROM smssettings WHERE smstype LIKE '%$s%' OR member LIKE '%$s%' OR gleader LIKE '%$s%' OR username LIKE '%$s%' OR timestamp LIKE '%$s%'");
        } else {
            $result = mysqli_query($conn, "SELECT * FROM smssettings");
        }
        if (!$result) {
            echo "<tr><td colspan='8' class='sms-error'>Query error: " . htmlspecialchars($conn->error) . "</td></tr>";
        }
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $mycount++; ?>
          <tr>
            <td><?= $mycount ?></td>
            <td><?= htmlspecialchars($row['smstype'] ?? '') ?></td>
            <td class="sms-text"><?= htmlspecialchars($row['member'] ?? '') ?></td>
            <td class="sms-text"><?= htmlspecialchars($row['gleader'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['timestamp'] ?? '') ?></td>
            <td align="center">
              <?php $edit = "part=2&rec=" . $row['logid']; ?>
              <a href="index.php?page=<?= $page ?>&D=<?= sms_d($edit) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a>
            </td>
            <td align="center"><input name="del[]" type="checkbox" value="<?= htmlspecialchars($row['logid']) ?>" /></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
      <input type="hidden" name="txtcount" id="txtcount" value="<?= $mycount ?>">
    </div>
  </div>
</div>
<?php } ?>

</form>
<?php } ?>