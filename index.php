<?php
session_start();

/* =========================
   PAGE ROUTER
========================= */
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$allowedPages = [
    'dashboard',
    'member',
    'prescription',
    'stock_transaction',
    'mpesa',
    'sms',
    'member_statement',
    'group_statement',
    'group_statement_detailed',
    'defaulter_list',
    'userlist',
    'audittrail',
    'settings',
    'logistics_party',
    'conditions',
    'medicine',
    'unit_of_measure',
    'smssettings',
    'county',
    'provider',
    'roles',
    'roles_pages',
    'roles_access',
    '0_users'
];

if (!in_array($page, $allowedPages)) {
    $pageFile = "vendors/404.php";
} else {
    // ✅ YOUR FILES ARE IN ROOT FOLDER
    if ($page === 'dashboard') {
        $pageFile = "dashboard.php"; // change if your dashboard file is different
    } else {
        $pageFile = $page . ".php";
    }
}
?>

<!-- CSS -->
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/vendors/css/dashboard.css">

<!-- Font Awesome (optional if you're using icons) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- CanvasJS (for charts) -->
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>


<!-- SIDEBAR -->
<aside class="sidebar">
    <?php include("menu.php"); ?>
</aside>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOP NAVBAR -->
    <header class="topbar">
        <div class="left">
            <button id="menuToggle"><i class="fa fa-bars"></i></button>
        </div>

        <div class="right">
            <div class="user-info">
                <?php if (!empty($_SESSION['userprofpic'])) { ?>
                    <img src="uploads/<?php echo htmlspecialchars($_SESSION['userprofpic']); ?>" width="35" height="35">
                <?php } else { ?>
                    <img src="images/img.jpg" width="35" height="35">
                <?php } ?>

                <span>
                    <?php echo htmlspecialchars($_SESSION['userfullnames']); ?>
                </span>
            </div>

            <a href="?logout=logout" class="logout">
                <i class="fa fa-sign-out"></i> Logout
            </a>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="content">

        <?php
        if (file_exists($pageFile)) {
            include($pageFile);
        } else {
            include("vendors/404.php");
        }
        ?>

    </main>

    <footer class="footer">
        © <?php echo date('Y'); ?> Pochi La Afya System
    </footer>

</div>