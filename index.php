<?php
// Set to false on the live server so visitors never see PHP warnings
$debug = true;
error_reporting(E_ALL);
ini_set('display_errors', $debug ? 1 : 0);
ini_set('display_startup_errors', $debug ? 1 : 0);

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

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
$projectName = "CHG Patient Groups Monitoring System";
$orgEmail    = "cityhealth16@gmail.com";
$orgPhone    = "+256 780 731 458";
$orgAddress  = "P.O. Box 390919, Fort Portal";
$currency    = "UGX";          // used for deposit totals on the dashboard

// footer links
$orgMailSubject = "Enquiry";
$orgGmail  = "https://mail.google.com/mail/?view=cm&fs=1&to=" . rawurlencode($orgEmail) . "&su=" . rawurlencode($orgMailSubject);   // opens Gmail on the web (computers)
$orgMailto = "mailto:" . $orgEmail . "?subject=" . rawurlencode($orgMailSubject);                                                 // opens the Gmail / mail app (phones)
$orgWhatsapp = preg_replace('/\D+/', '', $orgPhone);   // digits only, as wa.me needs: 256780731458
$orgMapQuery = "Fort Portal, Uganda";                  // what Google Maps searches for; use your exact place name or "lat,lng" for a precise pin

// first letter of the user's name, shown in the round avatar in the header
$userName    = trim((string)$_SESSION['userfullnames']);
$userInitial = strtoupper(function_exists('mb_substr') ? mb_substr($userName, 0, 1) : substr($userName, 0, 1));

// ---------------------------------------------------------------------------
// Routing (only plain page names are accepted, so ?page=../x.php cannot load other files)
// ---------------------------------------------------------------------------
$page = isset($_GET['page']) ? trim((string)$_GET['page']) : 'dashboard';
if (!preg_match('/^[A-Za-z0-9_]+$/', $page) || in_array(strtolower($page), ['index', 'server', 'login', 'menu', 'stop'], true)) {
    $page = 'dashboard';
}
$pageFile = $page . ".php";

if ($page == "dashboard") {
    $pageFile = "dash.php";
}
$pageLabel = ($page == 'dashboard') ? 'Dashboard' : ucwords(str_replace('_', ' ', $page));

// ---------------------------------------------------------------------------
// Deposit totals (read straight from the mpesa table, so they follow every save / edit / delete)
// ---------------------------------------------------------------------------
if (!function_exists('chg_deposit_totals')) {
    function chg_deposit_totals($conn) {
        $out = ['ok' => false, 'total' => 0.0, 'today' => 0.0, 'month' => 0.0, 'count' => 0];
        if (!$conn) { return $out; }
        try {
            // transactiondate may be Y-m-d or the 14-digit M-Pesa stamp; compare on YYYYMMDD either way
            $d   = "LEFT(REPLACE(REPLACE(transactiondate,'-',''),' ',''),8)";
            $sql = "SELECT COUNT(*) AS c,
                           COALESCE(SUM(amount),0) AS t,
                           COALESCE(SUM(CASE WHEN $d = ? THEN amount ELSE 0 END),0) AS td,
                           COALESCE(SUM(CASE WHEN LEFT($d,6) = ? THEN amount ELSE 0 END),0) AS mo
                    FROM mpesa";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { return $out; }
            $today = date('Ymd'); $month = date('Ym');
            $stmt->bind_param('ss', $today, $month);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res && ($r = $res->fetch_assoc())) {
                    $out = ['ok' => true, 'total' => (float)$r['t'], 'today' => (float)$r['td'], 'month' => (float)$r['mo'], 'count' => (int)$r['c']];
                }
            }
            $stmt->close();
        } catch (Throwable $e) {
            error_log('index.php deposit totals: ' . $e->getMessage());
        }
        return $out;
    }
}


// 3200000 -> "UGX 3.2M", 450000 -> "UGX 450K", 1250 -> "UGX 1,250"
if (!function_exists('chg_money_short')) {
    function chg_money_short($amount, $currency = 'UGX') {
        $a = (float)$amount; $sign = $a < 0 ? '-' : ''; $a = abs($a);
        if ($a >= 1000000000)  { $n = rtrim(rtrim(number_format($a / 1000000000, 1), '0'), '.') . 'B'; }
        elseif ($a >= 1000000) { $n = rtrim(rtrim(number_format($a / 1000000, 1), '0'), '.') . 'M'; }
        elseif ($a >= 100000)  { $n = rtrim(rtrim(number_format($a / 1000, 1), '0'), '.') . 'K'; }
        else                   { $n = number_format($a, 0); }
        return $currency . ' ' . $sign . $n;
    }
}

// Full figure: "UGX 3,215,400"
if (!function_exists('chg_money_full')) {
    function chg_money_full($amount, $currency = 'UGX') { return $currency . ' ' . number_format((float)$amount, 0); }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0e6ba8">
    <title><?php echo htmlspecialchars($pageLabel . ' | ' . $projectName); ?></title>
    <link rel="stylesheet" href="vendors/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>

    <style>
        /* ===== HEADER SIZE  (change this one number to make the header taller / shorter) ===== */
        :root { --chg-topbar-h: 84px; }

        /* ===== CHG BRANDING / HEADER ===== */
        header.topbar {
            min-height: var(--chg-topbar-h) !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-inline: 26px;
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            background: linear-gradient(135deg, #0b5a8f 0%, #0e6ba8 55%, #1388c9 100%) !important;
            border-bottom: 3px solid #1ea7e1;
            box-shadow: 0 4px 14px rgba(8, 40, 66, .20);
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; min-width: 0; }

        /* menu button */
        header.topbar #menuToggle {
            width: 48px; height: 48px; flex: 0 0 48px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: 12px; cursor: pointer;
            background: rgba(255,255,255,.14); color: #fff; font-size: 22px;
            transition: background .15s ease;
        }
        header.topbar #menuToggle:hover { background: rgba(255,255,255,.26); }

        /* logo */
        .brand-logo {
            height: 60px; width: 60px; flex: 0 0 60px; object-fit: contain; border-radius: 50%;
            border: 3px solid #1ea7e1; background: #ffffff; padding: 3px;
            box-shadow: 0 3px 10px rgba(0,0,0,.22);
        }
        .brand-logo-fallback {
            height: 60px; width: 60px; flex: 0 0 60px; border-radius: 50%; border: 3px solid #1ea7e1; background: #ffffff;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; font-weight: 800; color: #e02020;
            box-shadow: 0 3px 10px rgba(0,0,0,.22);
        }
        .brand-title { font-size: 25px; font-weight: 700; color: #ffffff; margin: 0; white-space: nowrap; letter-spacing: .3px; }
        .brand-chg { color: #ffd54f; font-weight: 800; }

        /* user + logout */
        .topbar .top-right { display: flex; align-items: center; gap: 18px; flex: 0 0 auto; }
        .topbar .user-chip { display: flex; align-items: center; gap: 12px; }
        .topbar .user-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: #1ea7e1; color: #fff; font-size: 18px; font-weight: 700;
            border: 2px solid rgba(255,255,255,.55);
        }
        .topbar .user-name { font-size: 16px; font-weight: 600; color: #fff; }
        .topbar .top-right .logout {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 12px 24px; border-radius: 999px;
            font-size: 15px; font-weight: 700; color: #fff; text-decoration: none;
            background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.45);
            transition: background .15s ease, border-color .15s ease, transform .1s ease;
        }
        .topbar .top-right .logout:hover { background: #e74c3c; border-color: #e74c3c; color: #fff; transform: translateY(-1px); }
        .topbar .top-right .logout i { font-size: 16px; }


        /* ===== BREADCRUMB ===== */
        .chg-crumbs {
            display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
            margin: 0 0 12px; padding: 9px 14px; font-size: 13px; color: #6b7a8c;
            background: #fff; border: 1px solid #e3e8ee; border-radius: 8px;
        }
        .chg-crumbs a { color: #1a73e8; text-decoration: none; font-weight: 600; }
        .chg-crumbs a:hover { text-decoration: underline; }
        .chg-crumbs .sep { color: #b8c2cf; font-size: 10px; }
        .chg-crumbs .here { color: #2a3f54; font-weight: 700; }
        .chg-crumbs .back { margin-left: auto; }

        /* ===== SIDEBAR: current page highlight + mobile overlay ===== */
        .sidebar a.chg-active {
            background: rgba(30,167,225,.20) !important;
            border-left: 3px solid #1ea7e1;
            color: #fff !important; font-weight: 700;
        }
        .chg-overlay {
            position: fixed; inset: 0; background: rgba(15,30,45,.45);
            opacity: 0; visibility: hidden; transition: opacity .2s; z-index: 98;
        }
        .chg-overlay.show { opacity: 1; visibility: visible; }

        /* ===== PAGE CONTENT ===== */
        .content { animation: chgFade .25s ease-out; }
        @keyframes chgFade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

        /* thin progress bar while a page loads */
        .chg-loading {
            position: fixed; top: 0; left: 0; height: 3px; width: 0; z-index: 9999;
            background: linear-gradient(90deg, #1ea7e1, #26b99a, #ffd54f);
            transition: width 8s cubic-bezier(.1,.7,.2,1);
        }
        .chg-loading.go { width: 92%; }

        /* back to top */
        .chg-top {
            position: fixed; right: 18px; bottom: 18px; width: 42px; height: 42px; border: 0; border-radius: 50%;
            background: #0e6ba8; color: #fff; font-size: 16px; cursor: pointer; box-shadow: 0 3px 10px rgba(0,0,0,.25);
            opacity: 0; visibility: hidden; transition: opacity .2s, transform .15s; z-index: 90;
        }
        .chg-top.show { opacity: 1; visibility: visible; }
        .chg-top:hover { transform: translateY(-2px); background: #0b5a8f; }

        /* ===== FOOTER polish ===== */
        .footer .company { font-weight: 700; letter-spacing: .02em; }
        .footer .tagline { font-style: italic; opacity: .9; margin: 2px 0 8px; }
        .footer .contact { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px 22px; }
        .footer .contact i { margin-right: 6px; }
        .footer .contact a {
            color: inherit; text-decoration: none;
            padding: 4px 12px; border-radius: 999px;
            transition: background .15s ease, color .15s ease;
        }
        .footer .contact a:hover,
        .footer .contact a:focus-visible { background: rgba(255,255,255,.16); text-decoration: underline; }
        .footer .contact a .fa-whatsapp { color: #25d366; }
        .footer .contact a .fa-envelope { color: #ffd54f; }
        .footer .contact a .fa-map-marker-alt { color: #ff7a6b; }
        .footer .copyright { margin-top: 8px; font-size: 12px; opacity: .8; }

        /* footer: same blue as the header, accent line on top, contact details as chips */
        footer.footer {
            background: linear-gradient(135deg, #1388c9 0%, #0e6ba8 45%, #0b5a8f 100%) !important;
            color: #fff;
            text-align: center;
            padding: 30px 20px 18px;
            margin-top: 28px;
            border-top: 3px solid #1ea7e1;
            box-shadow: 0 -4px 14px rgba(8, 40, 66, .12);
        }
        footer.footer .company { font-size: 21px; font-weight: 800; letter-spacing: .04em; color: #fff; }
        footer.footer .tagline { font-size: 14px; font-style: italic; color: #cfe9f7; opacity: 1; margin: 4px 0 18px; }
        footer.footer .contact { gap: 12px 14px; }
        footer.footer .contact a {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: 999px;
            font-size: 14px; font-weight: 500; color: #fff;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.24);
            transition: background .15s ease, transform .1s ease, border-color .15s ease;
        }
        footer.footer .contact a i { margin: 0; font-size: 16px; }
        footer.footer .contact a:hover,
        footer.footer .contact a:focus-visible {
            background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.5);
            text-decoration: none; transform: translateY(-2px);
        }
        footer.footer .copyright {
            margin-top: 20px; padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,.18);
            font-size: 12px; color: #cfe9f7; opacity: 1;
        }
        @media (max-width: 640px) {
            footer.footer { padding: 24px 14px 16px; }
            footer.footer .company { font-size: 18px; }
            footer.footer .contact a { width: 100%; max-width: 340px; justify-content: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            footer.footer .contact a { transition: none; transform: none; }
        }

        /* ===== ACCESSIBILITY ===== */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2px solid #1ea7e1; outline-offset: 2px;
        }
        @media (prefers-reduced-motion: reduce) {
            .content { animation: none; }
            .chg-top, .chg-overlay, .chg-loading { transition: none; }
            header.topbar #menuToggle, .topbar .top-right .logout { transition: none; transform: none; }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1100px) {
            .brand-title { font-size: 20px; }
        }
        @media (max-width: 900px) {
            :root { --chg-topbar-h: 72px; }
            header.topbar { padding-inline: 16px; gap: 12px; }
            .brand-title { font-size: 17px; }
            .topbar .user-name { display: none; }
        }
        @media (max-width: 640px) {
            :root { --chg-topbar-h: 64px; }
            .topbar-left { gap: 10px; }
            header.topbar #menuToggle { width: 42px; height: 42px; flex-basis: 42px; font-size: 19px; }
            .brand-title { font-size: 13px; white-space: normal; line-height: 1.2; }
            .brand-logo, .brand-logo-fallback { height: 42px; width: 42px; flex-basis: 42px; }
            .topbar .user-avatar { width: 38px; height: 38px; font-size: 16px; }
            .topbar .top-right .logout { padding: 10px 14px; }
            .topbar .top-right .logout span { display: none; }
            .chg-crumbs .back { display: none; }
        }

        /* ===== PRINT ===== */
        @media print {
            .sidebar, .topbar, .footer, .chg-crumbs, .chg-top, .chg-overlay, .chg-loading { display: none !important; }
            .main-content { margin: 0 !important; width: 100% !important; }
        }
    </style>
</head>
<body>

    <div class="chg-loading" id="chgLoading"></div>
    <div class="chg-overlay" id="chgOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="profile">
            <?php if(!empty($_SESSION['userprofpic'])) { ?>
                <img src="uploads/<?php echo htmlspecialchars($_SESSION['userprofpic']); ?>" class="profile-image" alt="Profile photo">
            <?php } else { ?>
                <img src="images/img.jpg" class="profile-image" alt="Profile photo">
            <?php } ?>
            <h3><?php echo htmlspecialchars($_SESSION['userfullnames']); ?></h3>
        </div>
        <?php include("menu.php"); ?>
    </div>

    <div class="main-content" id="mainContent">

        <header class="topbar">
            <div class="topbar-left">
                <button id="menuToggle" aria-label="Toggle menu" title="Menu">
                    <i class="fa fa-bars"></i>
                </button>
                <img src="images/chg-logo.jpg" alt="CHG Logo" class="brand-logo"
                     onerror="this.outerHTML='<div class=&quot;brand-logo-fallback&quot;>CHG</div>';">
                <h1 class="brand-title">
                    <span class="brand-chg">CHG</span> Patient Groups Monitoring System
                </h1>
            </div>


            <div class="top-right">
                <span class="user-chip">
                    <span class="user-avatar" aria-hidden="true"><?php echo htmlspecialchars($userInitial); ?></span>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['userfullnames']); ?></span>
                </span>
                <a href="?logout=logout" class="logout" title="Log out" onclick="return confirm('Log out of the system?');">
                    <i class="fa fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </header>

        <main class="content">
            <?php if ($page != 'dashboard') { ?>
            <nav class="chg-crumbs" aria-label="Breadcrumb">
                <a href="index.php"><i class="fa fa-home"></i> Dashboard</a>
                <i class="fa fa-chevron-right sep"></i>
                <span class="here"><?php echo htmlspecialchars($pageLabel); ?></span>
                <a class="back" href="javascript:history.back()"><i class="fa fa-arrow-left"></i> Back</a>
            </nav>
            <?php } ?>

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

                <a id="chgEmailLink" href="<?php echo htmlspecialchars($orgGmail); ?>" data-mailto="<?php echo htmlspecialchars($orgMailto); ?>" target="_blank" rel="noopener noreferrer" title="Email us with Gmail" aria-label="Email <?php echo htmlspecialchars($orgEmail); ?> with Gmail">
                    <i class="fa fa-envelope"></i>
                    <?php echo htmlspecialchars($orgEmail); ?>
                </a>

                <a href="https://wa.me/<?php echo htmlspecialchars($orgWhatsapp); ?>?text=<?php echo rawurlencode('Hello City Health Group, '); ?>" target="_blank" rel="noopener noreferrer" title="Chat with us on WhatsApp" aria-label="Chat on WhatsApp with <?php echo htmlspecialchars($orgPhone); ?>">
                    <i class="fa-brands fa-whatsapp"></i>
                    <?php echo htmlspecialchars($orgPhone); ?>
                </a>

                <a href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo rawurlencode($orgMapQuery); ?>" target="_blank" rel="noopener noreferrer" title="Open in Google Maps" aria-label="Open <?php echo htmlspecialchars($orgMapQuery); ?> in Google Maps">
                    <i class="fa fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($orgAddress); ?>
                </a>

            </div>

            <div class="copyright">
                © <?php echo date("Y"); ?> City Health Group Limited. All Rights Reserved.
            </div>

        </footer>
    </div>

    <button class="chg-top" id="chgTop" aria-label="Back to top" title="Back to top"><i class="fa fa-arrow-up"></i></button>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const menuToggle  = document.getElementById("menuToggle");
        const sidebar     = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");
        const overlay     = document.getElementById("chgOverlay");
        const loading     = document.getElementById("chgLoading");
        const topBtn      = document.getElementById("chgTop");

        function closeSidebar() {
            sidebar.classList.remove("active");
            mainContent.classList.remove("sidebar-open");
            overlay.classList.remove("show");
        }

        menuToggle.addEventListener("click", function (event) {
            event.stopPropagation();
            sidebar.classList.toggle("active");
            mainContent.classList.toggle("sidebar-open");
            overlay.classList.toggle("show", sidebar.classList.contains("active") && window.innerWidth <= 900);
        });

        overlay.addEventListener("click", closeSidebar);
        document.addEventListener("keydown", function (e) { if (e.key === "Escape") { closeSidebar(); } });

        document.querySelector(".content").addEventListener("click", function () {
            if (sidebar.classList.contains("active")) { closeSidebar(); }
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

        // Highlight the menu item of the page you are on (and open its submenu)
        (function () {
            const current = new URLSearchParams(window.location.search).get('page') || 'dashboard';
            sidebar.querySelectorAll('a[href]').forEach(function (a) {
                const href = a.getAttribute('href') || '';
                const m = href.match(/[?&]page=([A-Za-z0-9_]+)/);
                const target = m ? m[1] : (href === 'index.php' || href === './' ? 'dashboard' : null);
                if (target && target === current) {
                    a.classList.add('chg-active');
                    const parentLi = a.closest('ul.child_menu') ? a.closest('ul.child_menu').parentElement : null;
                    if (parentLi) { parentLi.classList.add('menu-open'); }
                }
            });
        })();

        // Progress bar when moving between pages or submitting a form
        function startLoading() { loading.classList.add('go'); }
        document.addEventListener('click', function (e) {
            const a = e.target.closest('a[href]');
            if (!a || a.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey) { return; }
            const href = a.getAttribute('href') || '';
            if (href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0 || a.hasAttribute('download')) { return; }
            if (a.classList.contains('logout')) { return; }
            startLoading();
        });
        document.addEventListener('submit', startLoading);
        window.addEventListener('pageshow', function () { loading.classList.remove('go'); });

        // Email link: Gmail on the web for computers, the Gmail / mail app on phones and tablets
        (function () {
            const mail = document.getElementById('chgEmailLink');
            if (mail && /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                mail.setAttribute('href', mail.getAttribute('data-mailto'));
                mail.removeAttribute('target');
            }
        })();

        // Back-to-top button
        window.addEventListener('scroll', function () {
            topBtn.classList.toggle('show', window.scrollY > 300);
        }, { passive: true });
        topBtn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    });
    </script>
</body>
</html>