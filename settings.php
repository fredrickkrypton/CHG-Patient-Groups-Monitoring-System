<?php  

// 1. Load the database connection first
include('db.php'); 

$timestamp = date('Y-m-d H:i:s');
$part = 2;
$rec = 1;

// Fix missing parameter definitions for PHP 8 compatibility
if (!isset($page)) { $page = 'settings'; }

if($part == '') { if(function_exists('caniacess')) { caniacess($page."_view"); } }
if($part == '1') { if(function_exists('caniacess')) { caniacess($page."_add"); } }
if($part == '2') { if(function_exists('caniacess')) { caniacess($page."_edit"); } }
if(isset($_POST['delete'])) { if(function_exists('caniacess')) { caniacess($page."_delete"); } }

// Fix Undefined array key "stop" check safely
if(isset($_SESSION['stop']) && $_SESSION['stop'] == 1) {   
    include('stop.php'); 
} else {

if(isset($_POST['save'])) { }   
 
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['update'])) { 
    $companyname = $_POST['companyname'];
    $phone = $_POST['phone'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $currency = $_POST['currency'];
    $logouttime = $_POST['logouttime'];
    $rounding = $_POST['rounding'];
    $logid = $_POST['logid'];

    $helpline = $_POST['helpline'];
    $terms = $_POST['terms'];
    $moreinfo = $_POST['moreinfo'];

    if(trim($companyname) != '' && trim($logouttime) != '') { 
        $sql = "UPDATE settings SET companyname='$companyname',phone='$phone',mobile='$mobile',address='$address',email='$email',currency='$currency',logouttime='$logouttime',rounding='$rounding',helpline='$helpline',terms='$terms',moreinfo='$moreinfo' WHERE logid='$logid'";
        if ($conn->query($sql) === TRUE) {  
            $alert = 1; 
            if(function_exists('audittrail')) {
                $username = isset($_SESSION['userusername']) ? $_SESSION['userusername'] : 'admin';
                audittrail($conn,'settings',$logid,'updatings',$timestamp,$username); 
            }
        } else { 
            $error = ("Error: " . $sql . "<br>" . $conn->error); 
        }
    } else {  
        $error = ("Fill all mandatory fields in red.");  
    }   

    if(isset($alert) && $alert != '') {
        echo "<script> window.addEventListener('load', function () { 
        new PNotify({
                      title: 'Success',
                      text: 'Data updated successfully!',
                      nonblock: { nonblock: !0 },
                      type: 'success',
                      styling: 'bootstrap3'
                  }); }) </script>";
    }
    if(isset($error) && $error != '') {
        echo "<script> window.addEventListener('load', function () { 
        new PNotify({
                      title: 'Error',
                      text: '$error',
                      nonblock: { nonblock: !0 },
                      type: 'error',
                      styling: 'bootstrap3'
                  }); }) </script>";
    }
}  
 
//---------------------------------------------------------------------------------------------------- 
if(isset($_POST['delete'])) { }
//---------------------------------------------------------------------------------------------------- 

$title = 'General Settings'; 
if (function_exists('_title')) {
    _title($title); 
} else {
    echo "<h2>" . ucwords($title) . "</h2>";
}
?>

<form method='POST' action='' class='rounded' name='settings' style='min-width:70%' enctype='multipart/form-data'>
<?php if($part=='1') { ?>
    <div class='col-md-8 offset-2'>
        <div class='x_panel'>
          <div class='x_title'>
            <h2><?=ucwords($title);?><small>new entry</small></h2>
              <ul class='nav navbar-right panel_toolbox'>
                  <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a></li>
              </ul>
              <div class='clearfix'></div>
          </div>
          <div class='x_content center-block'>
            <div class="item form-group">
                <label for="companyname" class="col-sm-3 col-form-label">Companyname : </label>
                <div class="col-sm-5">
                    <input type="text" required='required' class="form-control form-control-sm manda" name="companyname" id="companyname" placeholder="Companyname" value="<?php echo isset($_POST['companyname']) ? $_POST['companyname'] : ''; ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="phone" class="col-sm-3 col-form-label">Phone : </label>
                <div class="col-sm-5">
                    <input type="text" class="form-control form-control-sm " name="phone" id="phone" placeholder="Phone" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="mobile" class="col-sm-3 col-form-label">Mobile : </label>
                <div class="col-sm-5">
                    <input type="text" class="form-control form-control-sm " name="mobile" id="mobile" placeholder="Mobile" value="<?php echo isset($_POST['mobile']) ? $_POST['mobile'] : ''; ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="address" class="col-sm-3 col-form-label">Address : </label>
                <div class="col-sm-5">
                    <textarea class="form-control form-control-sm " name="address" id="address"><?php echo isset($_POST['address']) ? $_POST['address'] : ''; ?></textarea>
                </div>
            </div>

            <div class="item form-group">
                <label for="moreinfo" class="col-sm-3 col-form-label">USSD More info : </label>
                <div class="col-sm-5">
                    <textarea class="form-control form-control-sm " name="moreinfo" id="moreinfo"><?php echo isset($_POST['moreinfo']) ? $_POST['moreinfo'] : ''; ?></textarea>
                </div>
            </div>

            <div class="item form-group">
                <label for="email" class="col-sm-3 col-form-label">Email : </label>
                <div class="col-sm-5">
                    <input type="email" required="required" class="form-control form-control-sm " name="email" id="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="currency" class="col-sm-3 col-form-label">Currency : </label>
                <div class="col-sm-5">
                    <input type="text" class="form-control form-control-sm " name="currency" id="currency" placeholder="Currency" value="<?php echo isset($_POST['currency']) ? $_POST['currency'] : ''; ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="logouttime" class="col-sm-3 col-form-label">Logout time(mins) : </label>
                <div class="col-sm-5">
                    <input type="text" required='required' class="form-control form-control-sm manda" name="logouttime" id="logouttime" placeholder="Logouttime" value="<?php echo isset($_POST['logouttime']) ? $_POST['logouttime'] : ''; ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="item form-group">
                <label for="rounding" class="col-sm-3 col-form-label">Rounding : </label>
                <div class="col-sm-5">
                    <input type="text" required='required' class="form-control form-control-sm manda" name="rounding" id="rounding" placeholder="rounding" value="<?php echo isset($_POST['rounding']) ? $_POST['rounding'] : ''; ?>" autocomplete="off" onblur="js_validate(this,'general')">
                </div>
            </div>

            <div class="ln_solid"></div>
            <div class="form-group row text-center">
                <div class="col-md">
                     <button type="submit" name="save" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Save</span> <i class="fa fa-save"></i></button>
                </div>
            </div>
          </div>
        </div>
    </div>

<?php } elseif($part=='2') { ?>
    <div class='col-md-8 offset-2'>
        <div class='x_panel'>
          <div class='x_title'>
            <h2><?=ucwords($title);?><small>update entry</small></h2>
              <ul class='nav navbar-right panel_toolbox'>
                  <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a></li>
              </ul>
              <div class='clearfix'></div>
          </div>
          <div class='x_content center-block'>
            <?php 
            $result = mysqli_query($conn,"SELECT * FROM settings WHERE logid='$rec'");
            if ($result && mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) { 
            ?> 
                <div class="item form-group">
                    <label for="companyname" class="col-sm-3 col-form-label">Company name : </label>
                    <div class="col-sm-5">
                        <input type="text" required='required' class="form-control form-control-sm manda" name="companyname" id="companyname" placeholder="Companyname" value="<?php echo $row['companyname']?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="phone" class="col-sm-3 col-form-label">Phone : </label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control form-control-sm " name="phone" id="phone" placeholder="Phone" value="<?php echo $row['phone']?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="mobile" class="col-sm-3 col-form-label">Mobile : </label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control form-control-sm " name="mobile" id="mobile" placeholder="Mobile" value="<?php echo $row['mobile']?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="address" class="col-sm-3 col-form-label">Address : </label>
                    <div class="col-sm-5">
                        <textarea class="form-control form-control-sm " name="address" id="address"><?php echo $row['address']?></textarea>
                    </div>
                </div>

                <div class="item form-group">
                    <label for="terms" class="col-sm-3 col-form-label">USSD T&C : </label>
                    <div class="col-sm-5">
                        <textarea class="form-control form-control-sm " name="terms" id="terms"><?php echo $row['terms']?></textarea>
                    </div>
                </div>
                <div class="item form-group">
                    <label for="moreinfo" class="col-sm-3 col-form-label">USSD More info : </label>
                    <div class="col-sm-5">
                        <textarea class="form-control form-control-sm " name="moreinfo" id="moreinfo"><?php echo $row['moreinfo']?></textarea>
                    </div>
                </div>

                <div class="item form-group">
                    <label for="helpline" class="col-sm-3 col-form-label">USSD Helpline : </label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control form-control-sm " name="helpline" id="helpline" placeholder="helpline" value="<?php echo $row['helpline']?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>

                <div class="item form-group">
                    <label for="email" class="col-sm-3 col-form-label">Email : </label>
                    <div class="col-sm-5">
                        <input type="email" required="required" class="form-control form-control-sm " name="email" id="email" placeholder="Email" value="<?php echo $row['email']?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="currency" class="col-sm-3 col-form-label">Currency : </label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control form-control-sm " name="currency" id="currency" placeholder="Currency" value="<?php echo $row['currency']?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="logouttime" class="col-sm-3 col-form-label">Logout time(min) : </label>
                    <div class="col-sm-5">
                        <input type="text" required='required' class="form-control form-control-sm manda" name="logouttime" id="logouttime" placeholder="minutes" value="<?php echo $row['logouttime']?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>
                <div class="item form-group">
                    <label for="rounding" class="col-sm-3 col-form-label">Rounding : </label>
                    <div class="col-sm-5">
                        <input type="text" required='required' class="form-control form-control-sm manda" name="rounding" id="rounding" placeholder="rounding" value="<?php echo $row['rounding']?>" autocomplete="off" onblur="js_validate(this,'general')">
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="form-group row text-center">
                    <div class="col-md">
                         <button type="submit" name="update" style="padding: 0.4em;" class="btn btn-success btn-sm"><span>Update</span> <i class="fa fa-save"></i></button>
                    </div>
                </div>
                <input name="logid" type="hidden" value="<?=$row['logid']?>" />
            <?php 
                } 
            } else {
                echo "<p class='text-danger text-center'>No configurations found in the settings database table.</p>";
            }
            ?> 
          </div>
        </div>
    </div>
<?php } ?>
</form>
<?php } ?>