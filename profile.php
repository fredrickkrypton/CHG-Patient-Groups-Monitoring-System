<?php
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//
// My Profile = the logged-in user edits THEIR OWN details only
// (full names, email, phone, profile picture and password).
// Accounts are created and managed by admins on the Users page (0_users).
//
// Save this file over the old profile page (keep the same file name so the menu link still works).

if (!function_exists('workout') && file_exists(__DIR__ . '/server.php')) {
    require_once(__DIR__ . '/server.php');
}

$conn      = $conn ?? ($GLOBALS['conn'] ?? null);
$me        = $_SESSION['userusername'] ?? '';
$timestamp = date('Y-m-d H:i:s');
$alert     = '';
$error     = '';

// This page only needs a login (no Page Access entry). Reset the "stop" flag that a
// previously visited page may have left behind, otherwise stop.php would be shown.
$_SESSION['stop'] = 0;

function mp_notify($type, $title, $text) {
    $f = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    echo "<script> window.addEventListener('load', function () {
        if (typeof PNotify !== 'undefined') {
            new PNotify({ title: " . json_encode($title, $f) . ", text: " . json_encode($text, $f) . ", nonblock: { nonblock: !0 }, type: " . json_encode($type, $f) . ", styling: 'bootstrap3' });
        }
    }) </script>";
    $bg = ($type === 'success') ? '#e6f7f2' : '#fdecea';
    $fg = ($type === 'success') ? '#1f7a63' : '#c0392b';
    echo "<div style='margin:10px 15px; padding:10px 16px; border-radius:6px; background:$bg; color:$fg; font-weight:600;'>"
       . htmlspecialchars($title . ': ' . $text) . "</div>";
}
function mp_e($v) { return htmlspecialchars((string)($v === null ? '' : $v), ENT_QUOTES, 'UTF-8'); }

if (function_exists('_title')) { _title($title = 'My Profile'); } else { $title = 'My Profile'; }

// ---------------------------------------------------------------------------
// LOAD THE LOGGED-IN USER
// ---------------------------------------------------------------------------
$user = null;
if ($me !== '' && $conn) {
    $st = $conn->prepare("SELECT u.*, r.rolename FROM `0_users` u LEFT JOIN roles r ON r.roleid = u.role WHERE u.username = ? LIMIT 1");
    if ($st) {
        $st->bind_param("s", $me);
        $st->execute();
        $user = $st->get_result()->fetch_assoc();
        $st->close();
    }
}

// ---------------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------------
if ($user && isset($_POST['profile_update'])) {
    $fullnames = ucwords(trim($_POST['fullnames'] ?? ''));
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $cur_pw    = (string)($_POST['current_password'] ?? '');
    $new_pw    = (string)($_POST['password'] ?? '');
    $new_pw2   = (string)($_POST['password2'] ?? '');
    $profpic   = $user['profpic'] ?? '';
    $new_file  = '';

    if ($fullnames === '')                                  { $error = "Enter your full names."; }
    elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = "Enter a valid email address."; }
    elseif ($phone === '')                                  { $error = "Enter your phone number."; }

    // ---- optional password change (needs the current password) ----
    $set_password = false;
    if ($error === '' && $new_pw !== '') {
        $stored = (string)($user['password'] ?? '');
        $ok = (strlen($stored) > 0 && $stored[0] === '$') ? password_verify($cur_pw, $stored) : (md5($cur_pw) === $stored);
        if (!$ok)                       { $error = "The current password is not correct."; }
        elseif (strlen($new_pw) < 6)    { $error = "The new password must be at least 6 characters."; }
        elseif ($new_pw !== $new_pw2)   { $error = "The new password and its confirmation do not match."; }
        else                            { $set_password = true; }
    }

    // ---- optional profile picture (real images only, max 2 MB) ----
    if ($error === '' && !empty($_FILES['profpic']['name'])) {
        $f = $_FILES['profpic'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $info = ($f['error'] === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name'])) ? @getimagesize($f['tmp_name']) : false;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if ($f['error'] !== UPLOAD_ERR_OK)           { $error = "The picture could not be uploaded. Please try again."; }
        elseif (!in_array($ext, $allowed, true) || !$info) { $error = "Choose a JPG, PNG, GIF or WEBP image."; }
        elseif ($f['size'] > 2 * 1024 * 1024)        { $error = "The picture must be smaller than 2 MB."; }
        else {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
            $new_file = (int)$user['logid'] . '_' . date('ymdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($f['tmp_name'], $dir . $new_file)) { $error = "The picture could not be saved on the server."; $new_file = ''; }
        }
    }

    if ($error === '') {
        $pic_to_save = ($new_file !== '') ? $new_file : $profpic;
        if ($set_password) {
            $hash = md5($new_pw);   // the login in server.php compares md5 - keep in step with it
            $st = $conn->prepare("UPDATE `0_users` SET fullnames=?, email=?, phone=?, profpic=?, password=? WHERE username=?");
            if ($st) { $st->bind_param("ssssss", $fullnames, $email, $phone, $pic_to_save, $hash, $me); }
        } else {
            $st = $conn->prepare("UPDATE `0_users` SET fullnames=?, email=?, phone=?, profpic=? WHERE username=?");
            if ($st) { $st->bind_param("sssss", $fullnames, $email, $phone, $pic_to_save, $me); }
        }
        if ($st && $st->execute()) {
            $alert = 1;
            // remove the old picture file, refresh the session so the top bar shows the new details
            if ($new_file !== '' && $profpic !== '' && basename($profpic) === $profpic && is_file(__DIR__ . '/uploads/' . $profpic)) {
                @unlink(__DIR__ . '/uploads/' . $profpic);
            }
            $_SESSION['userfullnames'] = $fullnames;
            $_SESSION['useremail']     = $email;
            $_SESSION['userphone']     = $phone;
            $_SESSION['userprofpic']   = $pic_to_save;
            if (function_exists('audittrail')) { audittrail($conn, '0_users', $user['logid'], 'updatings', $timestamp, $me); }
            $user['fullnames'] = $fullnames; $user['email'] = $email; $user['phone'] = $phone; $user['profpic'] = $pic_to_save;
        } else {
            $error = "Your profile could not be saved: " . ($st ? $st->error : $conn->error);
            if ($new_file !== '') { @unlink(__DIR__ . '/uploads/' . $new_file); }
        }
        if ($st) { $st->close(); }
    }
    if ($alert !== '') { mp_notify('success', 'Success', 'Profile updated successfully!'); }
    if ($error !== '') { mp_notify('error', 'Error', $error); }
}

$pic = ($user && ($user['profpic'] ?? '') !== '' && basename($user['profpic']) === $user['profpic'] && is_file(__DIR__ . '/uploads/' . $user['profpic']))
     ? 'uploads/' . $user['profpic'] : '';
?>

<style>
form[name='myprofile'] .x_panel {
    background: #fff; border: 1px solid #e3e8ee; border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06); overflow: hidden; margin: 1rem auto;
}
form[name='myprofile'] .x_title { background: #f7f9fc; border-bottom: 1px solid #e3e8ee; padding: 14px 20px; margin: 0; }
form[name='myprofile'] .x_title h2 { font-size: 1.15rem; font-weight: 600; color: #1a73e8; margin: 0; }
form[name='myprofile'] .x_title h2 small { margin-left: 10px; font-size: .75rem; font-weight: 400; color: #8a94a6; text-transform: uppercase; letter-spacing: .05em; }
form[name='myprofile'] .x_content { padding: 24px 28px; }

form[name='myprofile'] .pf-top { display: flex; align-items: center; gap: 18px; margin-bottom: 22px; flex-wrap: wrap; }
form[name='myprofile'] .pf-avatar {
    width: 88px; height: 88px; border-radius: 50%; object-fit: cover; border: 3px solid #26b99a;
    background: #f1f4f8; display: flex; align-items: center; justify-content: center; color: #8a94a6; font-size: 34px;
}
form[name='myprofile'] .pf-who .n { font-size: 1.2rem; font-weight: 700; color: #2a3f54; }
form[name='myprofile'] .pf-who .s { color: #8a94a6; font-size: .85rem; }
form[name='myprofile'] .pf-role { display: inline-block; margin-top: 4px; padding: 3px 12px; border-radius: 20px; background: #1a73e8; color: #fff; font-size: 11px; font-weight: 600; }

form[name='myprofile'] .pf-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
form[name='myprofile'] .pf-section-title { font-size: .95rem; font-weight: 700; color: #1a73e8; margin: 0 0 14px; padding-bottom: 6px; border-bottom: 2px solid #26b99a; }
form[name='myprofile'] .pf-field { margin-bottom: 16px; }
form[name='myprofile'] .pf-field label { display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 700; color: #1a73e8; text-transform: uppercase; letter-spacing: .04em; }
form[name='myprofile'] .pf-field label .req { color: #e74c3c; }
form[name='myprofile'] .form-control {
    width: 100%; height: 38px; border: 1px solid #cfd7e3; border-radius: 6px;
    padding: 6px 10px; font-size: .9rem; color: #1a73e8; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
form[name='myprofile'] .form-control::placeholder { color: #9bbcf0; }
form[name='myprofile'] .form-control:focus { border-color: #26b99a; box-shadow: 0 0 0 3px rgba(38,185,154,.18); outline: none; }
form[name='myprofile'] .form-control:required { border-left: 4px solid #e74c3c; }
form[name='myprofile'] .form-control[readonly] { background: #f7f9fc; color: #8a94a6; cursor: default; }
form[name='myprofile'] input[type='file'] { width: 100%; padding: 6px; border: 1px dashed #9bbcf0; border-radius: 6px; background: #f7faff; color: #1a73e8; font-size: .85rem; }
form[name='myprofile'] .pf-hint { display: block; margin-top: 4px; font-size: .75rem; color: #8a94a6; }
form[name='myprofile'] .pf-actions { margin-top: 22px; padding-top: 18px; border-top: 1px solid #e3e8ee; display: flex; gap: 10px; flex-wrap: wrap; }
form[name='myprofile'] .btn { border-radius: 6px; padding: .45em 1.4em; font-weight: 600; letter-spacing: .03em; transition: transform .1s, box-shadow .15s, background .15s; }
form[name='myprofile'] .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
form[name='myprofile'] .btn-success   { background: #26b99a; border-color: #26b99a; color: #fff; }
form[name='myprofile'] .btn-success:hover { background: #1f9c82; }
form[name='myprofile'] .btn-secondary { background: #8a94a6; border-color: #8a94a6; color: #fff; }
form[name='myprofile'] .btn-secondary:hover { background: #6f7a8d; }
form[name='myprofile'] .pf-error { color: #c0392b; padding: 14px; text-align: center; }
@media (max-width: 767px) { form[name='myprofile'] .x_content { padding: 16px 14px; } }
</style>

<form method="POST" action="" class="rounded" name="myprofile" style="min-width:100%" enctype="multipart/form-data" autocomplete="off">
<div class="col-md-10 offset-md-1 col-sm-12">
  <div class="x_panel">
    <div class="x_title">
      <h2><?= mp_e($title) ?><small> your account</small></h2>
    </div>
    <div class="x_content">

    <?php if (!$user) { ?>
      <div class="pf-error"><i class="fa fa-exclamation-circle"></i> Your account could not be loaded. Please log out and log in again.</div>
    <?php } else { ?>

      <div class="pf-top">
        <?php if ($pic !== '') { ?>
          <img class="pf-avatar" id="pf_preview" src="<?= mp_e($pic) ?>" alt="Profile picture">
        <?php } else { ?>
          <div class="pf-avatar" id="pf_placeholder"><i class="fa fa-user"></i></div>
          <img class="pf-avatar" id="pf_preview" src="" alt="Profile picture" style="display:none;">
        <?php } ?>
        <div class="pf-who">
          <div class="n"><?= mp_e($user['fullnames'] ?? '') ?></div>
          <div class="s">@<?= mp_e($user['username'] ?? '') ?></div>
          <?php if (($user['rolename'] ?? '') !== '') { ?><span class="pf-role"><?= mp_e($user['rolename']) ?></span><?php } ?>
        </div>
      </div>

      <div class="pf-cols">
        <div>
          <div class="pf-section-title">Personal details</div>

          <div class="pf-field">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" value="<?= mp_e($user['username'] ?? '') ?>" readonly>
            <span class="pf-hint">Your username and role are set by an administrator.</span>
          </div>
          <div class="pf-field">
            <label for="fullnames">Full names <span class="req">*</span></label>
            <input type="text" required class="form-control" name="fullnames" id="fullnames" value="<?= mp_e($user['fullnames'] ?? '') ?>">
          </div>
          <div class="pf-field">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" required class="form-control" name="email" id="email" value="<?= mp_e($user['email'] ?? '') ?>">
          </div>
          <div class="pf-field">
            <label for="phone">Phone <span class="req">*</span></label>
            <input type="text" required class="form-control" name="phone" id="phone" value="<?= mp_e($user['phone'] ?? '') ?>">
          </div>
          <div class="pf-field">
            <label for="profpic">Profile picture</label>
            <input type="file" name="profpic" id="profpic" accept="image/jpeg,image/png,image/gif,image/webp">
            <span class="pf-hint">JPG, PNG, GIF or WEBP, up to 2 MB. Leave empty to keep the current picture.</span>
          </div>
        </div>

        <div>
          <div class="pf-section-title">Change password</div>
          <div class="pf-field">
            <label for="current_password">Current password</label>
            <input type="password" class="form-control" name="current_password" id="current_password" autocomplete="current-password">
          </div>
          <div class="pf-field">
            <label for="password">New password</label>
            <input type="password" class="form-control" name="password" id="password" autocomplete="new-password" placeholder="At least 6 characters">
          </div>
          <div class="pf-field">
            <label for="password2">Confirm new password</label>
            <input type="password" class="form-control" name="password2" id="password2" autocomplete="new-password">
            <span class="pf-hint">Leave all three password boxes empty to keep your current password.</span>
          </div>
        </div>
      </div>

      <div class="pf-actions">
        <button type="submit" name="profile_update" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Update profile</button>
        <a href="index.php" class="btn btn-secondary btn-sm"><i class="fa fa-reply"></i> Back to dashboard</a>
      </div>

    <?php } ?>
    </div>
  </div>
</div>
</form>

<script>
// live preview of the chosen picture
(function () {
    var input = document.getElementById('profpic'), img = document.getElementById('pf_preview'), ph = document.getElementById('pf_placeholder');
    if (!input || !img) { return; }
    input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        if (!f || !f.type || f.type.indexOf('image/') !== 0) { return; }
        img.src = URL.createObjectURL(f);
        img.style.display = '';
        if (ph) { ph.style.display = 'none'; }
    });
})();
</script>