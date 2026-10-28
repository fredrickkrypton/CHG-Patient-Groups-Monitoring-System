<?php
// ensure page is defined safely
$page = $_GET['page'] ?? 'dashboard';

// helper to check view access for a given page (exact match, e.g. "member_view")
if (!function_exists('hasaccess')) {
    function hasaccess($pagename) {
        $list = array_map('trim', explode(',', (string)($_SESSION['allaccess'] ?? '')));
        return in_array($pagename . '_view', $list, true);
    }
}

// one menu link; $also = other pages that should keep this entry highlighted
if (!function_exists('menu_link')) {
    function menu_link($target, $label, $current, $icon = '', $also = array()) {
        $on   = ($current === $target) || in_array($current, $also, true);
        $html = '<li><a href="index.php?page=' . rawurlencode($target) . '"'
              . ($on ? ' class="activemenu" aria-current="page"' : '') . '>';
        if ($icon !== '') { $html .= '<i class="fa ' . htmlspecialchars($icon) . '" aria-hidden="true"></i> '; }
        return $html . '<span class="mi-text">' . htmlspecialchars($label) . '</span></a></li>';
    }
}

// which collapsible sections the user can see at all
$da_pages = ['member_statement','group_statement','group_statement_detailed','defaulter_list','userlist','audittrail'];
$settings_pages = ['settings','logistics_party','conditions','medicine','unit_of_measure','smssettings','county','provider','roles','roles_pages','roles_access','0_users'];

$da_visible = false;
foreach ($da_pages as $dp) { if (hasaccess($dp)) { $da_visible = true; break; } }
$settings_visible = false;
foreach ($settings_pages as $sp) { if (hasaccess($sp)) { $settings_visible = true; break; } }
?>

<style>
/* ==========================================================================
   Sidebar menu  -  dark navy with a green accent (matches the app pages)
   Scoped to #sidebar-menu / .sidebar-footer; the collapsed (.nav-sm) mode
   keeps the theme's own styling.
   ========================================================================== */
body:not(.nav-sm) .left_col { background: linear-gradient(180deg, #2a3f54 0%, #1f3144 100%) !important; }

body:not(.nav-sm) #sidebar-menu { padding: 4px 12px 16px; }
body:not(.nav-sm) #sidebar-menu .menu_section { margin-bottom: 0; }

/* ---------- Section captions ---------- */
#sidebar-menu .menu-caption {
    list-style: none;
    padding: 16px 12px 6px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: rgba(226, 233, 242, .42);
}
.nav-sm #sidebar-menu .menu-caption { display: none; }

/* ---------- Top level items ---------- */
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li {
    margin: 3px 0;
    border-right: 0 !important;
}
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li > a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 12px;
    border-radius: 10px;
    border-right: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    text-shadow: none !important;
    color: #cfd8e3;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background .18s ease, color .18s ease, transform .18s ease;
}
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li > a > i:first-child {
    flex: 0 0 32px;
    width: 32px;
    height: 32px;
    margin: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: rgba(255, 255, 255, .07);
    color: #9fb1c5;
    font-size: 15px;
    opacity: 1;
    transition: background .18s ease, color .18s ease, box-shadow .18s ease;
}
#sidebar-menu .mi-text { flex: 1; min-width: 0; }

/* hover */
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li > a:hover {
    background: rgba(255, 255, 255, .08) !important;
    color: #fff;
    transform: translateX(2px);
}
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li > a:hover > i:first-child {
    background: rgba(38, 185, 154, .22);
    color: #3fd6b6;
}

/* the page you are on */
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li > a.activemenu {
    background: linear-gradient(90deg, rgba(38, 185, 154, .30), rgba(38, 185, 154, .07)) !important;
    box-shadow: inset 3px 0 0 #26b99a !important;
    color: #fff;
    font-weight: 600;
}
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li > a.activemenu > i:first-child {
    background: #26b99a;
    color: #fff;
    box-shadow: 0 4px 10px rgba(38, 185, 154, .35);
}

/* an open section (Data Analysis / Settings) */
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li.active > a,
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li.current-page > a {
    background: rgba(255, 255, 255, .06) !important;
    box-shadow: none !important;
    color: #fff;
}
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li.active > a > i:first-child {
    background: rgba(38, 185, 154, .22);
    color: #3fd6b6;
}

/* chevron: always a down arrow, rotated when the section is open */
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li > a .fa-chevron-down {
    margin: 0 0 0 auto;
    float: none;
    font-size: 10px;
    color: rgba(226, 233, 242, .5);
    transition: transform .2s ease;
}
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li > a .fa-chevron-down:before { content: "\f078"; }
body:not(.nav-sm) #sidebar-menu .nav.side-menu > li.active > a .fa-chevron-down { transform: rotate(180deg); color: #3fd6b6; }

/* show a section that is open on page load, even before the theme's JS runs */
.nav-md #sidebar-menu li.active > ul.child_menu { display: block; }

/* ---------- Sub menu ---------- */
body:not(.nav-sm) #sidebar-menu .nav.child_menu {
    margin: 4px 0 8px 27px;
    padding: 0 0 0 10px;
    border-left: 1px solid rgba(255, 255, 255, .12);
    background: transparent !important;
}
body:not(.nav-sm) #sidebar-menu .nav.child_menu > li { margin: 0; }
body:not(.nav-sm) #sidebar-menu .nav.child_menu > li:before,
body:not(.nav-sm) #sidebar-menu .nav.child_menu > li:after { display: none !important; }

body:not(.nav-sm) #sidebar-menu .nav.child_menu > li > a {
    position: relative;
    display: block;
    margin: 2px 0;
    padding: 7px 12px 7px 20px;
    border-radius: 8px;
    background: transparent !important;
    color: #aebdcd;
    font-size: 13px;
    font-weight: 400;
    transition: background .18s ease, color .18s ease;
}
body:not(.nav-sm) #sidebar-menu .nav.child_menu > li > a:before {
    content: "";
    position: absolute;
    left: 8px;
    top: 50%;
    width: 5px;
    height: 5px;
    margin-top: -2.5px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .28);
    transition: background .18s ease, box-shadow .18s ease;
}
body:not(.nav-sm) #sidebar-menu .nav.child_menu > li > a:hover {
    background: rgba(255, 255, 255, .07) !important;
    color: #fff;
}
body:not(.nav-sm) #sidebar-menu .nav.child_menu > li > a:hover:before { background: #3fd6b6; }
body:not(.nav-sm) #sidebar-menu .nav.child_menu > li > a.activemenu {
    background: rgba(38, 185, 154, .16) !important;
    color: #fff;
    font-weight: 600;
}
body:not(.nav-sm) #sidebar-menu .nav.child_menu > li > a.activemenu:before {
    background: #26b99a;
    box-shadow: 0 0 0 3px rgba(38, 185, 154, .28);
}

/* ---------- Footer buttons ---------- */
.sidebar-footer {
    padding: 10px 12px;
    background: rgba(0, 0, 0, .22) !important;
    border-top: 1px solid rgba(255, 255, 255, .07);
}
.sidebar-footer table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px; }
.sidebar-footer td { width: 50%; padding: 0; }
.sidebar-footer a {
    float: none !important;
    width: auto !important;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 38px;
    padding: 0 !important;
    border-radius: 10px;
    background: rgba(255, 255, 255, .08) !important;
    color: #cfd8e3;
    font-size: 16px;
    transition: background .18s ease, color .18s ease, transform .18s ease;
}
.sidebar-footer a:hover { color: #fff; transform: translateY(-1px); }
.sidebar-footer td:first-child a:hover { background: #1a73e8 !important; }
.sidebar-footer td:last-child a:hover  { background: #e74c3c !important; }

/* ---------- Keyboard focus ---------- */
#sidebar-menu a:focus-visible,
.sidebar-footer a:focus-visible { outline: 2px solid #26b99a; outline-offset: 2px; }

@media (prefers-reduced-motion: reduce) {
    #sidebar-menu a, #sidebar-menu a > i, #sidebar-menu .fa-chevron-down, .sidebar-footer a { transition: none !important; transform: none !important; }
}
</style>

<!-- sidebar menu -->
<div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
    <div class="menu_section">

        <ul class="nav side-menu">

            <!-- ========================= -->
            <!-- MAIN MENU -->
            <!-- ========================= -->

            <li class="menu-caption">Main menu</li>

            <?php if (hasaccess('member')) { echo menu_link('member', 'Members', $page, 'fa-users', ['member_profile']); } ?>
            <?php if (hasaccess('prescription')) { echo menu_link('prescription', 'Prescriptions', $page, 'fa-user-md', ['prescription_details']); } ?>
            <?php if (hasaccess('stock_transaction')) { echo menu_link('stock_transaction', 'Dispensing', $page, 'fa-exchange'); } ?>
            <?php if (hasaccess('mpesa')) { echo menu_link('mpesa', 'Deposits', $page, 'fa-money'); } ?>
            <?php if (hasaccess('sms')) { echo menu_link('sms', 'SMS Sent', $page, 'fa-comments-o'); } ?>

            <?php if ($da_visible || $settings_visible) { ?>
            <li class="menu-caption">Reports &amp; setup</li>
            <?php } ?>

            <!-- ========================= -->
            <!-- DATA ANALYSIS -->
            <!-- ========================= -->

            <?php if ($da_visible) { ?>
            <li class="<?= in_array($page, $da_pages, true) ? 'active' : '' ?>">
                <a><i class="fa fa-bar-chart-o" aria-hidden="true"></i> <span class="mi-text">Data Analysis</span> <span class="fa fa-chevron-down"></span></a>
                <ul class="nav child_menu">
                    <?php if (hasaccess('member_statement'))          { echo menu_link('member_statement', 'Member Statement', $page); } ?>
                    <?php if (hasaccess('group_statement'))           { echo menu_link('group_statement', 'Group Statement', $page); } ?>
                    <?php if (hasaccess('group_statement_detailed'))  { echo menu_link('group_statement_detailed', 'Detailed Contribution Summary', $page); } ?>
                    <?php if (hasaccess('defaulter_list'))            { echo menu_link('defaulter_list', 'Defaulters List', $page); } ?>
                    <?php if (hasaccess('userlist'))                  { echo menu_link('userlist', 'User List', $page); } ?>
                    <?php if (hasaccess('audittrail'))                { echo menu_link('audittrail', 'Audit Trail', $page); } ?>
                </ul>
            </li>
            <?php } ?>

            <!-- ========================= -->
            <!-- SETTINGS -->
            <!-- ========================= -->

            <?php if ($settings_visible) { ?>
            <li class="<?= in_array($page, $settings_pages, true) ? 'active' : '' ?>">
                <a><i class="fa fa-cogs" aria-hidden="true"></i> <span class="mi-text">Settings</span> <span class="fa fa-chevron-down"></span></a>
                <ul class="nav child_menu">
                    <?php if (hasaccess('settings'))         { echo menu_link('settings', 'Company Settings', $page); } ?>
                    <?php if (hasaccess('logistics_party'))  { echo menu_link('logistics_party', 'Logistics Party', $page); } ?>
                    <?php if (hasaccess('conditions'))       { echo menu_link('conditions', 'Medical Conditions', $page); } ?>
                    <?php if (hasaccess('medicine'))         { echo menu_link('medicine', 'Medicine', $page); } ?>
                    <?php if (hasaccess('unit_of_measure'))  { echo menu_link('unit_of_measure', 'Units of Measure', $page); } ?>
                    <?php if (hasaccess('smssettings'))      { echo menu_link('smssettings', 'SMS Settings', $page); } ?>
                    <?php if (hasaccess('county'))           { echo menu_link('county', 'County', $page); } ?>
                    <?php if (hasaccess('provider'))         { echo menu_link('provider', 'Provider', $page); } ?>
                    <?php if (hasaccess('roles'))            { echo menu_link('roles', 'Roles', $page); } ?>
                    <?php if (hasaccess('roles_pages'))      { echo menu_link('roles_pages', 'Pages', $page); } ?>
                    <?php if (hasaccess('roles_access'))     { echo menu_link('roles_access', 'Access', $page); } ?>
                    <?php if (hasaccess('0_users'))          { echo menu_link('0_users', 'Users', $page); } ?>
                </ul>
            </li>
            <?php } ?>

        </ul>
    </div>
</div>

<!-- footer buttons -->
<div class="sidebar-footer hidden-small">
    <table width="100%">
        <tr>
            <td>
                <a href="index.php?page=settings" title="Settings" aria-label="Settings">
                    <span class="fa fa-cogs"></span>
                </a>
            </td>

            <td>
                <a href="index.php?logout=logout" title="Logout" aria-label="Logout">
                    <span class="fa fa-lock"></span>
                </a>
            </td>
        </tr>
    </table>
</div>