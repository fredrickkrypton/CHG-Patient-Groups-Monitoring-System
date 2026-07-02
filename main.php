<div class="right_col" role="main">
<div class="" style="min-height:105vh;">&nbsp;
<script type="text/javascript">
        var GB_ROOT_DIR = "greybox/";
    </script>
    <script type="text/javascript" src="greybox/AJS.js"></script>
    <script type="text/javascript" src="greybox/AJS_fx.js"></script>
    <script type="text/javascript" src="greybox/gb_scripts.js"></script>
    <link href="greybox/gb_styles.css" rel="stylesheet" type="text/css" media="all" />
<?php 
// Initialize strict variables to prevent crash on PHP 8
if (!isset($_SESSION['logouttime']) || !is_numeric($_SESSION['logouttime'])) { 
    $_SESSION['logouttime'] = 30; // Default to 30 minutes if not set
}
if (!isset($_SESSION['systemexpiry'])) { 
    $_SESSION['systemexpiry'] = time(); 
}
if (!isset($page)) { 
    $page = ''; 
}

$exptime = $_SESSION['logouttime'] * 60;

if (isset($_GET['mc'])) {
    $_SESSION['mc'] = $_GET['mc']; 
}

// Safely evaluate timeout
if (time() - $_SESSION['systemexpiry'] > $exptime) { 
    session_unset();
    session_destroy();
    echo '<script> window.location.replace("index.php"); </script>';
    exit();
} else {
    $_SESSION['systemexpiry'] = time();
}

// Handle routing layouts safely
if ($page == 'index.php' || $page == 'index.php?' || $page == '') { 
    include('dash.php'); 
} else { 
    if (!file_exists($page . '.php')) {
        include('machine.php');
    } else {
        ?>
        <style> 
            .main_container { width:max-content; }
        </style>
        <?php
        include($page . '.php');
    }
}

// Prevent undefined filename or function call crashing the script layout end
if (isset($filename) && function_exists('_title')) {
    _title($title);
}
?> 
</div>
</div>