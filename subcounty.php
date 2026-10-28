<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
$page = "subcounty";

// ---------- Decode the D parameter (only if present) ----------
if (function_exists('workout') && isset($_GET['D'])) {
    workout($_GET['D']);
}

// Plain link support: index.php?page=subcounty&part=1&p=<county logid>
if (isset($_GET['part']) && $_GET['part'] === '1') {
    $part = '1';
}

// ---------- Safe defaults (these were undefined before -> warnings) ----------
$conn      = $conn ?? ($GLOBALS['conn'] ?? null);
$part      = $part ?? '';
$rec       = $rec ?? '';
$alert     = '';
$error     = '';
$deleted   = 0;
$p         = trim($_GET['p'] ?? '');     // county logid passed from the county page
$username  = $username ?? ($_SESSION['username'] ?? ($_SESSION['userusername'] ?? 'System'));
$timestamp = date('Y-m-d H:i:s');

function subcounty_d($q) { return function_exists('osteens') ? osteens($q) : base64_encode($q); }

function subcounty_notify($type, $title, $text) {
    echo "<script> window.addEventListener('load', function () {
        new PNotify({ title: " . json_encode($title) . ", text: " . json_encode($text) . ", nonblock: { nonblock: !0 }, type: '$type', styling: 'bootstrap3' }); }) </script>";
}

if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['subcounty_delete'])) { caniacess($page . "_delete"); }
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['subcounty_save'])) {
    $name      = ucwords(trim($_POST['name'] ?? ''));
    $county_id = trim($_POST['county_id'] ?? '');
    if ($name !== '' && $county_id !== '') {
        $stmt = $conn->prepare("INSERT INTO subcounty (name, county_id, created_by, created_on) VALUES (?,?,?,?)");
        if ($stmt === false) {
            $error = "SQL error: " . $conn->error;
        } else {
            $stmt->bind_param("ssss", $name, $county_id, $username, $timestamp);
            if ($stmt->execute()) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'subcounty', '', 'inserting', $timestamp, $username); }
            } else {
                $error = "Error: you are duplicating data!";
            }
            $stmt->close();
        }
    } else {
        $error = "Fill all mandatory fields in red.";
    }
    if ($alert !== '') { subcounty_notify('success', 'Success', 'Data saved successfully!'); }
    if ($error !== '') { subcounty_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE  (created_by / created_on are left unchanged)
// ---------------------------------------------------------------------------
if (isset($_POST['subcounty_update'])) {
    $name      = ucwords(trim($_POST['name'] ?? ''));
    $county_id = trim($_POST['county_id'] ?? '');
    $logid     = $_POST['logid'] ?? '';
    if ($name !== '' && $county_id !== '' && $logid !== '') {
        $stmt = $conn->prepare("UPDATE subcounty SET name=?, county_id=? WHERE logid=?");
        if ($stmt === false) {
            $error = "SQL error: " . $conn->error;
        } else {
            $stmt->bind_param("sss", $name, $county_id, $logid);
            if ($stmt->execute()) {
                $alert = 1;
                if (function_exists('audittrail')) { audittrail($conn, 'subcounty', $logid, 'updatings', $timestamp, $username); }
            } else {
                $error = "Error: you are duplicating data!";
            }
            $stmt->close();
        }
    } else {
        $error = "Fill all mandatory fields in red.";
    }
    if ($alert !== '') { subcounty_notify('success', 'Success', 'Data updated successfully!'); }
    if ($error !== '') { subcounty_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['subcounty_delete'])) {
    foreach (($_POST['del'] ?? []) as $id) {
        if ($id === '') { continue; }
        if (function_exists('audittrail')) { audittrail($conn, 'subcounty', $id, 'deleting', $timestamp, $username); }
        $stmt = $conn->prepare("DELETE FROM subcounty WHERE logid=?");
        if ($stmt === false) { continue; }
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) { $deleted++; }
        $stmt->close();
    }
    if ($deleted > 0) { subcounty_notify('success', 'Success', 'Deleted successfully'); }
    else              { subcounty_notify('error', 'Error', 'No record selected'); }
}

$title = 'Sub-county';
if (function_exists('_title')) { _title($title); }

// Plain links used on this page
$link_new  = "index.php?page={$page}&part=1" . ($p !== '' ? "&p=" . urlencode($p) : '');
$link_list = "index.php?page={$page}" . ($p !== '' ? "&p=" . urlencode($p) : '');
$link_back = "index.php?page=county";
?>

<style>
  .subcounty-field {
    display: block;
    width: 100%;
    height: 38px;
    padding: 6px 12px;
    font-size: 14px;
    color: #495057;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 6px;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
    transition: border-color .2s ease, box-shadow .2s ease;
  }
  .subcounty-field::placeholder { color: #adb5bd; }
  .subcounty-field:hover { border-color: #3aa0e8; }
  .subcounty-field:focus {
    outline: none;
    border-color: #3aa0e8;
    box-shadow: 0 0 0 3px rgba(58,160,232,0.2);
  }
  .item.form-group label.manda { font-weight: 600; color: #3a4a5c; padding-top: 8px; }
  .item.form-group label.manda::after { content: " *"; color: #dc3545; }
</style>

<form method="POST" action="" class="rounded" name="subcounty" style="min-width:70%" enctype="multipart/form-data">

<?php if ($part == '1') {
    $sel_county = $_POST['county_id'] ?? $p;
?>
<div class="col-md-8 offset-2">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small>new entry</small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a class="link"><i class="fa fa-chevron-up"></i></a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content center-block">

      <div class="item form-group">
        <label for="name" class="col-sm-3 manda">Sub County Name : </label>
        <div class="col-sm-8">
          <input type="text" required="required" class="form-control form-control-sm manda subcounty-field" name="name" id="name" placeholder="Sub County Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="off" onblur="js_validate(this,'general')">
        </div>
      </div>

      <div class="item form-group">
        <label for="county_id" class="col-sm-3 manda">District : </label>
        <div class="col-sm-8">
          <select class="form-control manda subcounty-field" id="county_id" name="county_id" required="required">
            <option value="">Select District</option>
            <?php
            $qresult = mysqli_query($conn, "SELECT logid, name FROM county ORDER BY name");
            while ($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
              <option value="<?= htmlspecialchars($drow['logid']) ?>" <?= ((string)$drow['logid'] === (string)$sel_county) ? 'selected' : '' ?>><?= htmlspecialchars($drow['name']) ?></option>
            <?php } ?>
          </select>
        </div>
      </div>

      <br />
      <div class="ln_solid"></div>
      <div class="form-group row text-center">
        <div class="col-md">
          <button title="Save data" type="submit" name="subcounty_save" style="padding:0.4em;" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
          &nbsp; &nbsp;
          <a href="<?= $link_new ?>" title="Add New Record"><button type="button" name="new" style="padding:0.4em;" class="btn btn-success btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button></a>
          &nbsp; &nbsp;
          <a href="<?= $link_list ?>" title="Go to records"><button type="button" style="padding:0.4em;" class="btn btn-success btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button></a>
          &nbsp; &nbsp;
          <a href="<?= $link_back ?>" title="Back to districts"><button type="button" style="padding:0.4em;" class="btn btn-success btn-sm btn-info"><i class="fa fa-reply" style="float:none;"></i> &nbsp;<span>Back</span></button></a>
        </div>
      </div>

    </div><!-- x_content -->
  </div><!-- x_panel -->
</div><!-- col-md-8 -->

<?php } elseif ($part == '2') { ?>
<div class="col-md-8 offset-2">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small>update entry</small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a class="link"><i class="fa fa-chevron-up"></i></a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content center-block">
      <?php
      $stmt = $conn->prepare("SELECT * FROM subcounty WHERE logid=?");
      if ($stmt === false) {
          echo "<div class='alert alert-danger'>SQL error: " . htmlspecialchars($conn->error) . "</div>";
      } else {
      $stmt->bind_param("s", $rec);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) { ?>

        <div class="item form-group">
          <label for="name" class="col-sm-3 manda">Sub County Name : </label>
          <div class="col-sm-8">
            <input type="text" required="required" class="form-control form-control-sm manda subcounty-field" name="name" id="name" placeholder="Sub County Name" value="<?= htmlspecialchars($row['name'] ?? '') ?>" autocomplete="off" onblur="js_validate(this,'general')">
          </div>
        </div>

        <div class="item form-group">
          <label for="county_id" class="col-sm-3 manda">District : </label>
          <div class="col-sm-8">
            <select class="form-control manda subcounty-field" id="county_id" name="county_id" required="required">
              <option value="">Select District</option>
              <?php
              $qresult = mysqli_query($conn, "SELECT logid, name FROM county ORDER BY name");
              while ($qresult && $drow = mysqli_fetch_assoc($qresult)) { ?>
                <option value="<?= htmlspecialchars($drow['logid']) ?>" <?= ((string)$drow['logid'] === (string)$row['county_id']) ? 'selected' : '' ?>><?= htmlspecialchars($drow['name']) ?></option>
              <?php } ?>
            </select>
          </div>
        </div>

        <br />
        <div class="ln_solid"></div>
        <div class="form-group row text-center">
          <div class="col-md">
            <button title="Update data" type="submit" name="subcounty_update" style="padding:0.4em;" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
            &nbsp; &nbsp;
            <a href="<?= $link_new ?>" title="Add New Record"><button type="button" name="new" style="padding:0.4em;" class="btn btn-success btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button></a>
            &nbsp; &nbsp;
            <a href="<?= $link_list ?>" title="Go to records"><button type="button" style="padding:0.4em;" class="btn btn-success btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button></a>
            &nbsp; &nbsp;
            <a href="<?= $link_back ?>" title="Back to districts"><button type="button" style="padding:0.4em;" class="btn btn-success btn-sm"><i class="fa fa-reply" style="float:none;"></i> &nbsp;<span>District</span></button></a>
          </div>
        </div>
        <input name="logid" type="hidden" value="<?= htmlspecialchars($row['logid']) ?>" />

      <?php }
      $stmt->close();
      } ?>
    </div><!-- x_content -->
  </div><!-- x_panel -->
</div><!-- col-md-8 -->

<?php } else { // ---------------- LIST (only shown when part is empty) ----------------
    $th = 'style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600; padding:8px 10px;"';
    $td = 'style="border:1px solid #e6e6e6; padding:8px 10px;"';
?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel" style="background:#fff; border:1px solid #eee; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.06); padding:20px;">
    <div class="x_title" style="border:none; margin-bottom:15px; display:flex; align-items:center; justify-content:space-between;">
      <div style="color:#c98a3e; font-size:14px;"><span>&raquo;</span> <?= htmlspecialchars(ucwords($title)) ?> existing records</div>
      <ul class="nav navbar-right panel_toolbox" style="margin:0; list-style:none; padding:0;">
        <li style="display:inline-block;">
          <a href="<?= $link_back ?>" class="btn btn-info" style="border-radius:20px; padding:8px 18px; font-weight:600; font-size:13px; color:#fff !important;"><i class="fa fa-reply"></i>&nbsp; Districts</a>
          <a href="<?= $link_new ?>" class="btn btn-primary" style="background-color:#3aa0e8 !important; border-color:#3aa0e8 !important; color:#fff !important; border-radius:20px; padding:8px 18px; font-weight:600; font-size:13px;"><i class="fa fa-plus"></i>&nbsp; Create New</a>
        </li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">

      <table id="datatable" style="width:100%; border-collapse:collapse;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
        <thead>
          <tr>
            <th width="1px" <?= $th ?>>#</th>
            <th <?= $th ?>>Sub County Name</th>
            <th <?= $th ?>>District</th>
            <th <?= $th ?>>Created By</th>
            <th <?= $th ?>>Created On</th>
            <th width="1%" <?= $th ?>>Edit</th>
            <th width="1%" <?= $th ?>>Del</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th <?= $th ?>>#</th>
            <th <?= $th ?>>Sub County Name</th>
            <th <?= $th ?>>District</th>
            <th <?= $th ?>>Created By</th>
            <th <?= $th ?>>Created On</th>
            <th style="border:1px solid #e6e6e6; background:#f4f6f9;"></th>
            <th style="border:1px solid #e6e6e6; background:#f4f6f9; text-align:center;">
              <button name="subcounty_delete" value="Delete" type="submit" class="button sample btn-sm btn-danger" onclick="return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
            </th>
          </tr>
        </tfoot>
        <tbody>
        <?php
        $mycount = 0;
        $sql = "SELECT s.*, c.name AS county_name
                FROM subcounty s
                LEFT JOIN county c ON c.logid = s.county_id";
        if ($p !== '') {
            $stmt = $conn->prepare($sql . " WHERE s.county_id=? ORDER BY s.name");
            if ($stmt) { $stmt->bind_param("s", $p); }
        } else {
            $stmt = $conn->prepare($sql . " ORDER BY s.name");
        }

        if ($stmt === false) {
            // Show the real MySQL error instead of a fatal TypeError
            echo "<tr><td colspan='7' style='color:#dc3545;padding:15px;'>SQL error: " . htmlspecialchars($conn->error) . "</td></tr>";
        } else {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $mycount++;
                $edit = "part=2&rec=" . $row['logid']; ?>
          <tr>
            <td <?= $td ?>><?= $mycount ?></td>
            <td <?= $td ?>><?= htmlspecialchars($row['name'] ?? '') ?></td>
            <td <?= $td ?>><?= htmlspecialchars($row['county_name'] ?? '') ?></td>
            <td <?= $td ?>><?= htmlspecialchars($row['created_by'] ?? '') ?></td>
            <td <?= $td ?>><?= htmlspecialchars($row['created_on'] ?? '') ?></td>
            <td <?= $td ?> align="center">
              <a href="index.php?page=<?= $page ?>&D=<?= subcounty_d($edit) ?>&p=<?= urlencode($p) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a>
            </td>
            <td <?= $td ?> align="center"><input name="del[]" type="checkbox" value="<?= htmlspecialchars($row['logid']) ?>" /></td>
          </tr>
        <?php }
            $stmt->close();
            if ($mycount == 0) { ?>
          <tr>
            <td colspan="7" align="center" style="border:1px solid #e6e6e6; padding:20px; font-style:italic; color:#888;">
              No sub-county records found. <a href="<?= $link_new ?>" style="color:#3aa0e8; text-decoration:underline;">Create New</a>
            </td>
          </tr>
        <?php }
        } ?>
        </tbody>
      </table>
      <input type="hidden" name="txtcount" id="txtcount" value="<?= $mycount ?>">

    </div>
  </div>
</div>
<?php } ?>

</form>
<?php } ?>