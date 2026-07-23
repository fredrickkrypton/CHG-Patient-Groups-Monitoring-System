<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
require_once('server.php');

// Redirect if not logged in
if (!isset($_SESSION['userfullnames'])) {
    header("Location: login.php");
    exit();
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Routing logic
$page = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';
$pageFile = $page . ".php";

if ($page == "dashboard") {
    $pageFile = "dash.php";
}

$projectName = "CHG Patient Groups Monitoring System";
$orgEmail    = "cityhealth16@gmail.com";
$orgPhone    = "+256 780 731 458";
$orgAddress  = "P.O. Box 390919, Fort Portal";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($projectName); ?></title>
    <link rel="stylesheet" href="vendors/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>

    <style>
        /* ===== CHG BRANDING ===== */
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            height: 38px;
            width: 38px;
            object-fit: contain;
            border-radius: 50%;
            border: 2px solid #1ea7e1;
            background: #ffffff;
            padding: 2px;
        }

        .brand-logo-fallback {
            height: 38px;
            width: 38px;
            border-radius: 50%;
            border: 2px solid #1ea7e1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #e02020;
        }

        .brand-title {
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            white-space: nowrap;
            letter-spacing: 0.2px;
        }

        .brand-chg {
            color: #ffd54f;
            font-weight: 800;
        }

        @media (max-width: 640px) {
            .brand-title {
                font-size: 13px;
            }

            .brand-logo,
            .brand-logo-fallback {
                height: 30px;
                width: 30px;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div class="profile">
            <?php if(!empty($_SESSION['userprofpic'])) { ?>
                <img src="uploads/<?php echo htmlspecialchars($_SESSION['userprofpic']); ?>" class="profile-image">
            <?php } else { ?>
                <img src="images/img.jpg" class="profile-image">
            <?php } ?>
            <h3><?php echo htmlspecialchars($_SESSION['userfullnames']); ?></h3>
        </div>
        <?php include("menu.php"); ?>
    </div>

    <div class="main-content" id="mainContent">

        <header class="topbar">
            <div class="topbar-left">
                <button id="menuToggle">
                    <i class="fa fa-bars"></i>
                </button>
                <img src="images/chg-logo.jpg" alt="CHG Logo" class="brand-logo"
                     onerror="this.outerHTML='<div class=&quot;brand-logo-fallback&quot;>CHG</div>';">
                <h1 class="brand-title">
                    <span class="brand-chg">CHG</span> Patient Groups Monitoring System
                </h1>
            </div>

            <div class="top-right">
                <span>
                    <?php echo htmlspecialchars($_SESSION['userfullnames']); ?>
                </span>
                <a href="?logout=logout" class="logout">
                    <i class="fa fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <main class="content">
            <?php
            if (file_exists($pageFile)) {
                include($pageFile);
            } else {
                echo "<div class='alert alert-danger'>The requested module <strong>" . htmlspecialchars($page) . "</strong> could not be found within the core distribution layer.</div>";
            }
            ?>
        </main>

        <footer class="footer">

            <div class="company">
                City Health Group Limited
            </div>

            <div class="tagline">
                "Bettering Health Care Together"
            </div>

            <div class="contact">

                <span>
                    <i class="fa fa-envelope"></i>
                    cityhealth16@gmail.com
                </span>

                <span>
                    <i class="fa fa-phone"></i>
                    +256 780 731 458
                </span>

                <span>
                    <i class="fa fa-map-marker-alt"></i>
                    P.O. Box 390919, Fort Portal
                </span>

            </div>

            <div class="copyright">
                © <?php echo date("Y"); ?> City Health Group Limited. All Rights Reserved.
            </div>

        </footer>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const menuToggle = document.getElementById("menuToggle");
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");

        menuToggle.addEventListener("click", function (event) {
            event.stopPropagation();
            sidebar.classList.toggle("active");
            mainContent.classList.toggle("sidebar-open");
        });

        document.querySelector(".content").addEventListener("click", function () {
            if (sidebar.classList.contains("active")) {
                sidebar.classList.remove("active");
                mainContent.classList.remove("sidebar-open");
            }
        });

        // Collapsible submenu toggle (Data Analysis, Settings, etc.)
        document.querySelectorAll('.nav.side-menu > li').forEach(function (li) {
            const link = li.querySelector(':scope > a');
            const submenu = li.querySelector(':scope > ul.child_menu');

            if (link && submenu) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation(); // don't let it bubble to the sidebar-close click handler

                    // Close any other open submenu (comment out this block to allow multiple open at once)
                    document.querySelectorAll('.nav.side-menu > li.menu-open').forEach(function (openLi) {
                        if (openLi !== li) openLi.classList.remove('menu-open');
                    });

                    li.classList.toggle('menu-open');
                });
            }
        });
    });
    </script>
</body>
</html>