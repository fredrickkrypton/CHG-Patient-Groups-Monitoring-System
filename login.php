<?php  
// Safely check if a session is already active before trying to start one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables to stop PHP 8 warnings
$hide = "";
$messg = "";

if (!isset($_SESSION['fixedlogintime'])) { $_SESSION['fixedlogintime'] = ''; }
if (!isset($_SESSION['fixeduser'])) { $_SESSION['fixeduser'] = ''; }
if (!isset($_SESSION['fixedpass'])) { $_SESSION['fixedpass'] = ''; }

// If they are already fully authenticated, bypass form and go to dashboard
if (isset($_SESSION['userusername']) && $_SESSION['userusername'] != '') {
    header("Location: index.php");
    exit();
}

// --- AUTHENTICATION BACKEND LOGIC ---
if (isset($_POST['login'])) {
    include('db.php'); 
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $md5_password = md5($password);
    
    $query = "SELECT * FROM `0_users` WHERE `username`='$username' AND `password`='$md5_password' AND `active`='Active'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Populate system session keys
        $_SESSION['userusername'] = $user['username'];
        $_SESSION['userfullnames'] = $user['fullnames'];
        $_SESSION['userrole'] = $user['role'];
        $_SESSION['userprofpic'] = $user['profpic'];
        
        // Force session storage save before redirecting
        session_write_close();
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Invalid Username or Password!');</script>";
    }
}
?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>LOGIN </title>
<? echo '
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- jQuery custom content scroller -->
    <link href="vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css" rel="stylesheet"/>

<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <!-- bootstrap-datetimepicker -->
    <link href="vendors/bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css" rel="stylesheet">
	
	  <link href="vendors/normalize-css/normalize.css" rel="stylesheet">
    <link href="vendors/ion.rangeSlider/css/ion.rangeSlider.css" rel="stylesheet">
    <link href="vendors/ion.rangeSlider/css/ion.rangeSlider.skinFlat.css" rel="stylesheet">
    <!-- Bootstrap Colorpicker -->
    <link href="vendors/mjolnic-bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css" rel="stylesheet">
     
	
	 <!-- PNotify -->
    <link href="vendors/pnotify/dist/pnotify.css" rel="stylesheet">
    <link href="vendors/pnotify/dist/pnotify.buttons.css" rel="stylesheet">
    <link href="vendors/pnotify/dist/pnotify.nonblock.css" rel="stylesheet">
	
	 
	
    <!-- Custom Theme Style -->
	 <link href="vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    
	<link href="build/css/custom.min.css" rel="stylesheet">
	<link href="ccss.css" rel="stylesheet">
	';?>
 
  <body class="login">
    <div>
   

      <div class="login_wrapper" style="margin-top: 1%;">
        <div class="animate form login_form">
          <section class="login_content">
            <form action="" method="post">
              <h1>Login Form</h1>
<style>
 .flip-box {
background-color: transparent;
width: 350px;
height: 100px;
}

/* This container is needed to position the front and back side */
.flip-box-inner {
  position: relative;
  width: 100%;
  height: 100%;
  text-align: center;
  transition: transform 0.8s;
  transform-style: preserve-3d;
}

/* Do an horizontal flip when you move the mouse over the flip box container */
.flip-box:hover .flip-box-inner {
  transform: rotateY(180deg);
}

/* Position the front and back side */
.flip-box-front, .flip-box-back {
  position: absolute;
  width: 100%;
  height: 100%;
  -webkit-backface-visibility: hidden; /* Safari */
  backface-visibility: hidden;
}

 

/* Style the back side */
.flip-box-back {
 
  transform: rotateY(180deg);
}
</style>   
               <div class="flip-box">
  <div class="flip-box-inner">
    <div class="flip-box-front">
    <img src="images/eadmin.png"  class="loading_arrow"  height="100">
    </div>
    <div class="flip-box-back">
      <!--<img src="images/eadmin.png"  class="loading_arrow"  height="100">-->
    </div>
  </div>
</div> 
<div>
<? if($hide)
{ ?>	<br><span style="color:red">* Check email for security code.<br><?=$messg?></span>
<input type="text" class="form-control" placeholder="Security Code" required="" name="secc" id="secc" value="<?=$_POST['secc']?>"/> 
<? } ?>

            <input type="text" class="form-control" placeholder="Username" required="" name="username" id="username" value="<?=$_SESSION['fixeduser']?>" <?=$hide?> />
              </div>
              <div>
                <input type="password" class="form-control" placeholder="Password" required="" name="password" id="password"  value="<?=$_SESSION['fixedpass']?>" <?=$hide?>/>
                
              </div>
              <div align="center">
                <input name="login" type="submit" style="background-color: burlywood;float: none; margin-left:0;width: 40%;" class="btn btn-default submit" value="<? if($hide=='') { echo "Proceed"; } else { echo "Log in"; }  ?>"> 
              </div>

              <div class="clearfix"></div>

              <div class="separator">
                 
                <div>
                  <!--<h1 style="font-size: 10;"><i class="fa fa-paw"></i> Innovee Systems</h1>-->
                  <p>©<?=date("Y")?> All Rights Reserved.</p>
                </div>
              </div>
            </form>
          </section>
        </div>

        <div id="register" class="animate form registration_form">
          <section class="login_content">
            <form>
              <h1>Create Account</h1>
              <div>
                <input type="text" class="form-control" placeholder="Username" required="" />
              </div>
              <div>
                <input type="email" class="form-control" placeholder="Email" required="" />
              </div>
              <div>
                <input type="password" class="form-control" placeholder="Password" required="" />
              </div>
              <div>
                <a class="btn btn-default submit" href="index.html">Submit</a>
              </div>

              <div class="clearfix"></div>

              <div class="separator">
                <p class="change_link">Already a member ?
                  <a href="#signin" class="to_register"> Log in </a>
                </p>

                <div class="clearfix"></div>
                <br />

                <div>
                   <h5 style="font-size: 20;"><i class="fa fa-paw"></i> Intech Limited</h5>
                  <p>©<?=date("Y")?> All Rights Reserved.</p>
                </div>
              </div>
            </form>
          </section>
        </div>
      </div>
    </div> 
   <script src="vendors/jquery/dist/jquery.min.js"></script>
<script src="vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="vendors/fastclick/lib/fastclick.js"></script>
<script src="vendors/nprogress/nprogress.js"></script>
<script src="vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js"></script>
 



 
    <!-- bootstrap-daterangepicker -->
    <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- bootstrap-datetimepicker -->    
    <script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
 
    <!-- iCheck -->
    <script src="vendors/iCheck/icheck.min.js"></script>
   
    <script src="vendors/jszip/dist/jszip.min.js"></script>
    <script src="vendors/pdfmake/build/pdfmake.min.js"></script>
    <script src="vendors/pdfmake/build/vfs_fonts.js"></script>
       
    <script src="vendors/ion.rangeSlider/js/ion.rangeSlider.min.js"></script>
    <!-- Bootstrap Colorpicker -->
    <script src="vendors/mjolnic-bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js"></script>
    <!-- jquery.inputmask -->
    <script src="vendors/jquery.inputmask/dist/min/jquery.inputmask.bundle.min.js"></script>
    <!-- jQuery Knob -->
    <script src="vendors/jquery-knob/dist/jquery.knob.min.js"></script>
    <!-- Cropper --> 

<!-- PNotify -->

    <script src="vendors/pnotify/dist/pnotify.js"></script>
    <script src="vendors/pnotify/dist/pnotify.buttons.js"></script>
    <script src="vendors/pnotify/dist/pnotify.nonblock.js"></script>
    <!-- Custom Theme Scripts -->
    <script src="build/js/custom.min.js"></script>