<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}
if (function_exists('workout') && isset($_GET['D'])) {
    workout($_GET['D']);
} elseif (!empty($_GET['D'])) {
    // fallback: D is base64 of "part=1" / "part=2&rec=5"
    parse_str((string)base64_decode($_GET['D']), $__d);
    if (isset($__d['part'])) { $part = $__d['part']; }
    if (isset($__d['rec']))  { $rec  = $__d['rec']; }
}

// Plain link support: index.php?page=county&part=1
// Only the "create" screen is accepted in plain form. Edit links still use the
// encoded D value because they carry a record id.
if (isset($_GET['part']) && $_GET['part'] === '1') {
    $part = '1';
}

function county_d($q) { return function_exists('osteens') ? osteens($q) : base64_encode($q); }

// ---------- Safe defaults ----------
$conn      = $conn ?? ($GLOBALS['conn'] ?? null);
// IMPORTANT: this must match the page name stored in roles_pages (county),
// otherwise caniacess() checks "District_add" and denies access.
$page      = "county";
$audit     = "District";            // label written to the audit trail (unchanged)
$part      = $part ?? '';
$rec       = $rec ?? '';
$alert     = '';
$error     = '';
$deleted   = 0;
$username  = $username ?? ($_SESSION['username'] ?? ($_SESSION['userusername'] ?? 'System'));
$timestamp = date('Y-m-d H:i:s');

if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['county_delete'])) { caniacess($page . "_delete"); }
}

function county_notify($type, $title, $text) {
    echo "<script> window.addEventListener('load', function () {
        new PNotify({ title: " . json_encode($title) . ", text: " . json_encode($text) . ", nonblock: { nonblock: !0 }, type: '$type', styling: 'bootstrap3' }); }) </script>";
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['county_save'])) {
    $name = ucwords(trim($_POST['name'] ?? ''));
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO county (name, created_by, created_on) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $username, $timestamp);
        if ($stmt->execute()) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, $audit, '', 'inserting', $timestamp, $username); }
        } else {
            $error = "Error: you are duplicating data!";
        }
        $stmt->close();
    } else {
        $error = "Fill all mandatory fields in red.";
    }
    if ($alert !== '') { county_notify('success', 'Success', 'Data saved successfully!'); }
    if ($error !== '') { county_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// UPDATE  (the old code required $_POST['created_on'], which the form never sends,
//          so updates always failed; created_by / created_on are now left unchanged)
// ---------------------------------------------------------------------------
if (isset($_POST['county_update'])) {
    $name  = ucwords(trim($_POST['name'] ?? ''));
    $logid = $_POST['logid'] ?? '';
    if ($name !== '' && $logid !== '') {
        $stmt = $conn->prepare("UPDATE county SET name=? WHERE logid=?");
        $stmt->bind_param("ss", $name, $logid);
        if ($stmt->execute()) {
            $alert = 1;
            if (function_exists('audittrail')) { audittrail($conn, $audit, $logid, 'updatings', $timestamp, $username); }
        } else {
            $error = "Error: you are duplicating data!";
        }
        $stmt->close();
    } else {
        $error = "Fill all mandatory fields in red.";
    }
    if ($alert !== '') { county_notify('success', 'Success', 'Data updated successfully!'); }
    if ($error !== '') { county_notify('error', 'Error', $error); }
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------
if (isset($_POST['county_delete'])) {
    foreach (($_POST['del'] ?? []) as $id) {
        if ($id === '') { continue; }
        if (function_exists('audittrail')) { audittrail($conn, 'county', $id, 'deleting', $timestamp, $username); }
        $stmt = $conn->prepare("DELETE FROM county WHERE logid=?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) { $deleted++; }
        $stmt->close();
    }
    if ($deleted > 0) { county_notify('success', 'Success', 'Deleted successfully'); }
    else              { county_notify('error', 'Error', 'No record selected'); }
}

$title = 'District';
if (function_exists('_title')) { _title($title); }
?>

<style>
  .county-field {
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
  .county-field::placeholder { color: #adb5bd; }
  .county-field:hover { border-color: #3aa0e8; }
  .county-field:focus {
    outline: none;
    border-color: #3aa0e8;
    box-shadow: 0 0 0 3px rgba(58,160,232,0.2);
  }
  .county-field:invalid:not(:placeholder-shown) {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220,53,69,0.15);
  }
  .item.form-group label.manda { font-weight: 600; color: #3a4a5c; padding-top: 8px; }
  .item.form-group label.manda::after { content: " *"; color: #dc3545; }
</style>

<form method="POST" action="" class="rounded" name="county" style="min-width:70%" enctype="multipart/form-data">

<?php if ($part == '1') { ?>
<div class="col-md-8 offset-2">
  <div class="x_panel">
    <div class="x_title" style="color:#007bff;">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small>new entry</small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a class="link"><i class="fa fa-chevron-up"></i></a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content center-block">

      <div class="item form-group">
        <label for="name" class="col-sm-3 manda">District Name : </label>
        <div class="col-sm-8">
          <input type="text" required="required" class="form-control form-control-sm manda county-field" name="name" id="name" placeholder="District Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="off" onblur="js_validate(this,'general')">
        </div>
      </div>

      <br />
      <div class="ln_solid"></div>
      <div class="form-group row text-center">
        <div class="col-md">
          <button title="Save data" type="submit" name="county_save" style="padding:0.4em;" class="btn btn-primary btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>&part=1" title="Add New Record"><button type="button" name="new" style="padding:0.4em;" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button></a>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>" title="Go to records"><button type="button" style="padding:0.4em;" class="btn btn-primary btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button></a>
          &nbsp; &nbsp;
          <a href="index.php?page=<?= $page ?>" title="Back"><button type="button" style="padding:0.4em;" class="btn btn-primary btn-sm btn-info"><i class="fa fa-reply" style="float:none;"></i> &nbsp;<span>Back</span></button></a>
        </div>
      </div>

    </div><!-- x_content -->
  </div><!-- x_panel -->
</div><!-- col-md-8 -->

<?php } elseif ($part == '2') { ?>
<div class="col-md-8 offset-2">
  <div class="x_panel">
    <div class="x_title" style="color:#007bff;">
      <h2><?= htmlspecialchars(ucwords($title)) ?><small>update entry</small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a class="link"><i class="fa fa-chevron-up"></i></a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content center-block">
      <?php
      $stmt = $conn->prepare("SELECT * FROM county WHERE logid=?");
      $stmt->bind_param("s", $rec);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) { ?>

        <div class="item form-group">
          <label for="name" class="col-sm-3 manda">District Name : </label>
          <div class="col-sm-8">
            <input type="text" required="required" class="form-control form-control-sm manda county-field" name="name" id="name" placeholder="District Name" value="<?= htmlspecialchars($row['name'] ?? '') ?>" autocomplete="off" onblur="js_validate(this,'general')">
          </div>
        </div>

        <br />
        <div class="ln_solid"></div>
        <div class="form-group row text-center">
          <div class="col-md">
            <button title="Update data" type="submit" name="county_update" style="padding:0.4em;" class="btn btn-primary btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
            &nbsp; &nbsp;
            <a href="index.php?page=<?= $page ?>&part=1" title="Add New Record"><button type="button" name="new" style="padding:0.4em;" class="btn btn-primary btn-sm"><span>New</span> <i class="fa fa-file custom"></i></button></a>
            &nbsp; &nbsp;
            <a href="index.php?page=<?= $page ?>" title="Go to records"><button type="button" style="padding:0.4em;" class="btn btn-primary btn-sm"><span>View</span> <i class="fa fa-search custom"></i></button></a>
            &nbsp; &nbsp;
            <a href="index.php?page=<?= $page ?>" title="Back"><button type="button" style="padding:0.4em;" class="btn btn-primary btn-sm"><i class="fa fa-reply" style="float:none;"></i> &nbsp;<span>Back</span></button></a>
          </div>
        </div>
        <input name="logid" type="hidden" value="<?= htmlspecialchars($row['logid']) ?>" />

      <?php } $stmt->close(); ?>
    </div><!-- x_content -->
  </div><!-- x_panel -->
</div><!-- col-md-8 -->

<?php } else {
    $th = 'style="border:1px solid #e6e6e6; background:#f4f6f9; color:#3aa0e8; font-weight:600;"';
    $td = 'style="border:1px solid #e6e6e6;"';
?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel" style="background:#fff; border:1px solid #eee; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.06); padding:20px;">
    <div class="x_title" style="border:none; margin-bottom:15px; display:flex; align-items:center; justify-content:space-between;">
      <div style="color:#c98a3e; font-size:14px;"><span>&raquo;</span> <?= htmlspecialchars(ucwords($title)) ?> existing records</div>
      <ul class="nav navbar-right panel_toolbox" style="margin:0;">
        <li>
          <a href="index.php?page=<?= $page ?>&part=1" class="btn btn-primary" style="background-color:#3aa0e8 !important; border-color:#3aa0e8 !important; color:#fff !important; border-radius:20px; padding:8px 18px; font-weight:600; font-size:13px;"><i class="fa fa-plus"></i>&nbsp; Create New</a>
        </li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <table id="datatable" style="width:100%; border-collapse:collapse;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered dataTable table-sm">
        <thead>
          <tr>
            <th width="1px" <?= $th ?>>#</th>
            <th <?= $th ?>>Name</th>
            <th <?= $th ?>>Created By</th>
            <th <?= $th ?>>Created On</th>
            <th <?= $th ?>>Sub-county</th>
            <th width="1%" <?= $th ?>>Edit</th>
            <th width="1%" <?= $th ?>>Del</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th <?= $th ?>>#</th>
            <th <?= $th ?>>Name</th>
            <th <?= $th ?>>Created By</th>
            <th <?= $th ?>>Created On</th>
            <th <?= $th ?>>Sub-county</th>
            <th style="border:1px solid #e6e6e6; background:#f4f6f9;"></th>
            <th style="border:1px solid #e6e6e6; background:#f4f6f9;">
              <button name="county_delete" value="Delete" type="submit" class="button sample btn-sm btn-danger" onclick="return confirm('Please confirm deletion');"><i class="fa fa-trash"></i></button>
            </th>
          </tr>
        </tfoot>
        <tbody>
        <?php
        $mycount = 0;
        if (isset($_POST['tbn_search'])) {
            $s = $conn->real_escape_string($_POST['search'] ?? '');
            $result = mysqli_query($conn, "SELECT * FROM county WHERE name LIKE '%$s%' OR created_by LIKE '%$s%' OR created_on LIKE '%$s%'");
        } else {
            $result = mysqli_query($conn, "SELECT * FROM county");
        }
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $mycount++; ?>
          <tr>
            <td <?= $td ?>><?= $mycount ?></td>
            <td <?= $td ?>><?= htmlspecialchars($row['name'] ?? '') ?></td>
            <td <?= $td ?>><?= htmlspecialchars($row['created_by'] ?? '') ?></td>
            <td <?= $td ?>><?= htmlspecialchars($row['created_on'] ?? '') ?></td>
            <td <?= $td ?> align="center"><a href="index.php?page=subcounty&D=cGFydD0x&p=<?= htmlspecialchars($row['logid']) ?>"><i class="fa fa-lg fa-cog"></i></a></td>
            <td <?= $td ?> align="center">
              <?php $edit = "part=2&rec=" . $row['logid']; ?>
              <a href="index.php?page=<?= $page ?>&D=<?= county_d($edit) ?>" title="Go to edit"><i class="fa fa-lg fa-pencil"></i></a>
            </td>
            <td <?= $td ?> align="center"><input name="del[]" type="checkbox" value="<?= htmlspecialchars($row['logid']) ?>" /></td>
          </tr>
        <?php }
        if ($mycount == 0) { ?>
          <tr>
            <td colspan="7" align="center" style="border:1px solid #e6e6e6; padding:20px; font-style:italic; color:#888;">
              No district records found. Click <a href="index.php?page=<?= $page ?>&part=1" style="color:#3aa0e8; text-decoration:underline;">Create New</a> to get started.
            </td>
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