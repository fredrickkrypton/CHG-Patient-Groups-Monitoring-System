<?php
// Make sure core helpers (workout, caniacess, get_access, checked, _title, audittrail) are loaded
if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

// ---------- Safe defaults (prevents "undefined variable / key" warnings) ----------
$conn      = $conn ?? ($GLOBALS['conn'] ?? null);
$page      = "roles_access";
$timestamp = date('Y-m-d H:i:s');
$part      = 1;
$rec       = 1;
$alert     = '';
$error     = '';
$username  = $_SESSION['username'] ?? ($_SESSION['userusername'] ?? 'System');
$roleid    = $_POST['role'] ?? '';
$qresult   = false;

if (function_exists('caniacess')) {
    if ($part == '')  { caniacess($page . "_view"); }
    if ($part == '1') { caniacess($page . "_add"); }
    if ($part == '2') { caniacess($page . "_edit"); }
    if (isset($_POST['delete'])) { caniacess($page . "_delete"); }
}

if (isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {
    include('stop.php');
} else {

// ---------------------------------------------------------------------------
// SAVE
// ---------------------------------------------------------------------------
if (isset($_POST['save'])) {
    $role  = trim($_POST['role'] ?? '');
    $count = (int)($_POST['txtcount'] ?? 0);

    if ($role === '') {
        $error = "Select role!";
    } else {
        // Remove the role's existing permissions, then re-insert
        $del = $conn->prepare("DELETE FROM roles_access WHERE role = ?");
        $del->bind_param("s", $role);
        $del->execute();
        $del->close();

        $ins = $conn->prepare(
            "INSERT INTO roles_access (role, pages, `view`, canadd, canedit, candelete, username, timestamp)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        for ($i = 0; $i < $count; $i++) {
            $pages = $_POST['pages'][$i] ?? '';
            if ($pages === '') { continue; }

            // Unchecked checkboxes are not submitted, so default to ''
            $view      = $_POST['view'][$i]      ?? '';
            $canadd    = $_POST['canadd'][$i]    ?? '';
            $canedit   = $_POST['canedit'][$i]   ?? '';
            $candelete = $_POST['candelete'][$i] ?? '';

            if ($view !== '' || $canadd !== '' || $canedit !== '' || $candelete !== '') {
                $ins->bind_param("ssssssss", $role, $pages, $view, $canadd, $canedit, $candelete, $username, $timestamp);
                if ($ins->execute()) { $alert = 1; }
            }
        }
        $ins->close();

        if ($alert === '' ) { $error = "No permissions were selected."; }
        if (function_exists('get_access') && isset($_SESSION['userrole'])) {
            get_access($conn, $_SESSION['userrole']);
        }
    }

    if ($alert !== '') {
        echo "<script> window.addEventListener('load', function () {
            new PNotify({ title: 'Success', text: 'Data saved successfully!', nonblock: { nonblock: !0 }, type: 'success', styling: 'bootstrap3' }); }) </script>";
    }
    if ($error !== '') {
        echo "<script> window.addEventListener('load', function () {
            new PNotify({ title: 'Error', text: " . json_encode($error) . ", nonblock: { nonblock: !0 }, type: 'error', styling: 'bootstrap3' }); }) </script>";
    }
}

$title = 'Page access';
if (function_exists('_title')) { _title($title); }
?>

<form method="POST" action="" class="rounded" name="roles_access" style="min-width:70%" enctype="multipart/form-data">
<?php if ($part == '1') { ?>
<div class="col-md-8 offset-2">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= htmlspecialchars(ucwords($title)) ?></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content center-block">

      <div class="item form-group btnflat">
        <label for="role" class="col-sm-3 col-form-label"><strong>Role</strong></label>
        <div class="col-sm-5">
          <select class="form-control manda" id="role" name="role" required="required" onchange="this.form.submit()">
            <option value="">Select Role</option>
            <?php
            $rq = mysqli_query($conn, "SELECT roleid, rolename FROM roles ORDER BY rolename");
            while ($rq && $r = mysqli_fetch_assoc($rq)) {
                $sel = ((string)$r['roleid'] === (string)$roleid) ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($r['roleid']) . "' $sel>" . htmlspecialchars($r['rolename']) . "</option>";
            } ?>
          </select>
        </div>
      </div>
      <div class="ln_solid"></div>

      <script type="text/javascript">
      function CheckAlldel(flag, c) {
          var sel = document.querySelectorAll('[id^=' + c + ']');
          for (var i = 0; i < sel.length; i++) {
              if (sel[i].id == c) { sel[i].checked = flag; }
          }
      }
      </script>

      <table border="0" class="table text-center table-striped table-bordered">
        <tr style="background-color:#666;color:#FFF">
          <td width="1%"><strong>Page</strong></td>
          <td width="1%"><strong>View</strong></td>
          <td width="1%"><strong>Add</strong></td>
          <td width="1%"><strong>Edit</strong></td>
          <td width="1%"><strong>Delete</strong></td>
        </tr>
        <?php
        $counts = 0;
        $countgroup = 0;
        if ($roleid !== '') {
            $qresult = mysqli_query($conn, "SELECT `groups` FROM roles_pages GROUP BY `groups`");
        }
        while ($qresult && $drw = mysqli_fetch_assoc($qresult)) {
            $countgroup++; ?>
            <tr>
              <td colspan="5" style="background-color:#CCC" align="left"><strong><?= htmlspecialchars($drw['groups']) ?></strong></td>
            </tr>
            <?php
            $gstmt = $conn->prepare("SELECT * FROM roles_pages WHERE `groups` = ? ORDER BY filename");
            $gstmt->bind_param("s", $drw['groups']);
            $gstmt->execute();
            $qresultv = $gstmt->get_result();
            while ($drow = $qresultv->fetch_assoc()) {
                $counts++;
                $access = function_exists('checked') ? checked($conn, $roleid, $drow['logid']) : '';
                $perm   = array_pad(explode(",", (string)$access), 4, '');
                ?>
                <tr>
                  <td align="left">
                    <?= htmlspecialchars(ucwords(str_replace("_", " ", str_replace("and", " & ", $drow['pagename'])))) ?>
                    <input type="hidden" name="pages[<?= $counts - 1 ?>]" value="<?= htmlspecialchars($drow['logid']) ?>" />
                  </td>
                  <td><input type="checkbox" name="view[<?= $counts - 1 ?>]"      id="view<?= $countgroup ?>" value="Yes" <?= $perm[0] == "Yes" ? "checked" : "" ?> <?= ($drow['sview']   ?? '') == "0" ? "hidden" : "" ?> /></td>
                  <td><input type="checkbox" name="canadd[<?= $counts - 1 ?>]"    id="add<?= $countgroup ?>"  value="Yes" <?= $perm[1] == "Yes" ? "checked" : "" ?> <?= ($drow['sadd']    ?? '') == "0" ? "hidden" : "" ?> /></td>
                  <td><input type="checkbox" name="canedit[<?= $counts - 1 ?>]"   id="edit<?= $countgroup ?>" value="Yes" <?= $perm[2] == "Yes" ? "checked" : "" ?> <?= ($drow['sedit']   ?? '') == "0" ? "hidden" : "" ?> /></td>
                  <td><input type="checkbox" name="candelete[<?= $counts - 1 ?>]" id="del<?= $countgroup ?>"  value="Yes" <?= $perm[3] == "Yes" ? "checked" : "" ?> <?= ($drow['sdelete'] ?? '') == "0" ? "hidden" : "" ?> /></td>
                </tr>
            <?php } $gstmt->close(); ?>
            <tr>
              <td></td>
              <?php foreach (['view', 'add', 'edit', 'del'] as $p) { ?>
              <td>
                <input type="button" class="btnflat" value="✔" onclick="CheckAlldel(true,'<?= $p . $countgroup ?>')">
                <input type="button" class="btnflat" value="✘" onclick="CheckAlldel(false,'<?= $p . $countgroup ?>')">
              </td>
              <?php } ?>
            </tr>
        <?php } ?>
      </table>

      <div class="ln_solid"></div>
      <div class="form-group row text-center">
        <div class="col-md">
          <button data-placement="bottom" title="Save data" type="submit" name="save" style="padding:0.4em;" class="btn btn-success btn-sm">
            <span>Save</span> <i class="fa fa-save"></i></button>
          &nbsp; &nbsp;
          <input type="hidden" name="txtcount" id="txtcount" value="<?= $counts ?>">
          <a href="?" data-placement="bottom" title="Go to dashboard">
            <button type="button" name="exit" class="btn btn-info btn-sm"><span>Dashboard</span> <i class="fa fa-angle-double-right custom"></i></button></a>
        </div>
      </div>

    </div><!-- x_content -->
  </div><!-- x_panel -->
</div><!-- col-md-8 -->

<?php } else { ?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2>Existing<small><?= htmlspecialchars(ucwords($page)) ?></small></h2>
      <ul class="nav navbar-right panel_toolbox">
        <li><a href="?/<?= $page ?>&D=<?= function_exists('osteens') ? osteens('part=1') : '' ?>" class="collapse-link"><i class="fa fa-plus"></i> Manage</a></li>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <table id="datatable" style="width:100%;" role="grid" class="table table-striped table-bordered dataTable table-sm">
        <thead>
          <tr style="background-color:#666;color:#FFF">
            <th>Role</th><th>Pages</th><th>View</th><th>Canadd</th><th>Canedit</th><th>Candelete</th>
          </tr>
        </thead>
        <tbody>
        <?php
        if (isset($_POST['tbn_search'])) {
            $s = $conn->real_escape_string($_POST['search'] ?? '');
            $result = mysqli_query($conn, "SELECT * FROM roles_access WHERE role LIKE '%$s%' OR pages LIKE '%$s%' OR `view` LIKE '%$s%' OR canadd LIKE '%$s%' OR canedit LIKE '%$s%' OR candelete LIKE '%$s%' OR username LIKE '%$s%' OR timestamp LIKE '%$s%'");
        } else {
            $result = mysqli_query($conn, "SELECT * FROM roles_access GROUP BY role, pages");
        }
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rn = $conn->prepare("SELECT rolename FROM roles WHERE roleid = ?");
            $rn->bind_param("s", $row['role']); $rn->execute();
            $rname = $rn->get_result()->fetch_assoc()['rolename'] ?? ''; $rn->close();

            $pn = $conn->prepare("SELECT filename FROM roles_pages WHERE logid = ?");
            $pn->bind_param("s", $row['pages']); $pn->execute();
            $pname = $pn->get_result()->fetch_assoc()['filename'] ?? ''; $pn->close();
            ?>
          <tr>
            <td><?= htmlspecialchars($rname) ?></td>
            <td><?= htmlspecialchars(ucwords($pname)) ?></td>
            <td><?= htmlspecialchars($row['view']) ?></td>
            <td><?= htmlspecialchars($row['canadd']) ?></td>
            <td><?= htmlspecialchars($row['canedit']) ?></td>
            <td><?= htmlspecialchars($row['candelete']) ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php } ?>
</form>

<style>
.x_panel { background:#fff; border:1px solid #e6e9ed; border-radius:8px; padding:20px; box-shadow:0 4px 6px rgba(0,0,0,.02); margin-bottom:20px; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.x_title h2 { font-size:18px; font-weight:600; color:#2c3e50; margin:0; float:left; }

.item.form-group.btnflat { background:#f8f9fa; border-left:4px solid #3498db; padding:15px 20px; border-radius:4px; margin-bottom:25px; display:flex; align-items:center; gap:15px; }
.item.form-group.btnflat label { margin:0; color:#34495e; font-size:14px; }
.form-control.manda { border:1px solid #ccc; border-radius:4px; padding:8px 12px; font-size:14px; color:#495057; transition:border-color .15s; background:#fff; width:100%; max-width:300px; }
.form-control.manda:focus { border-color:#3498db; outline:0; box-shadow:0 0 0 .2rem rgba(52,152,219,.25); }

.table { width:100%; margin-bottom:20px; border-collapse:collapse; background:transparent; }
.table th, .table td { padding:12px 15px; vertical-align:middle; border-bottom:1px solid #e9ecef; font-size:14px; }
.table tr[style*="background-color:#666"] { background:#2c3e50 !important; color:#fff !important; }
.table tr[style*="background-color:#666"] td { font-weight:600; text-transform:uppercase; font-size:12px; letter-spacing:.5px; border:none; }
.table td[style*="background-color:#CCC"] { background:#edf2f7 !important; color:#2d3748 !important; font-weight:700 !important; font-size:13px; border-top:2px solid #cbd5e0; border-bottom:2px solid #cbd5e0; padding:10px 15px; }
.table input[type="checkbox"] { cursor:pointer; width:16px; height:16px; accent-color:#2ecc71; vertical-align:middle; }

.btnflat[type="button"] { border:1px solid #dcdde1; background:#f8f9fa; color:#2f3542; padding:4px 10px; font-size:11px; border-radius:4px; cursor:pointer; transition:all .2s; margin:2px; display:inline-flex; align-items:center; justify-content:center; }
.btnflat[value="✔"]:hover { background:#2ecc71; border-color:#2ecc71; color:#fff; }
.btnflat[value="✘"]:hover { background:#e74c3c; border-color:#e74c3c; color:#fff; }

.form-group.row.text-center { margin-top:25px; padding-top:20px; border-top:1px solid #e9ecef; }
.btn-success { background:#2ecc71; border-color:#2ecc71; color:#fff; font-weight:600; padding:8px 20px; border-radius:4px; transition:background-color .2s; }
.btn-success:hover { background:#27ae60; border-color:#27ae60; }
.btn-info { background:#34495e; border-color:#34495e; color:#fff; font-weight:600; padding:8px 20px; border-radius:4px; transition:background-color .2s; }
.btn-info:hover { background:#2c3e50; border-color:#2c3e50; }
.table-striped tbody tr:nth-of-type(odd) { background:rgba(0,0,0,.01); }
.table-striped tbody tr:hover { background:rgba(52,152,219,.04); }
</style>
<?php } ?>