<?php
// ensure page is defined safely
$page = $_GET['page'] ?? 'dashboard';

// helper to check view access for a given page
function hasaccess($pagename) {
    $access = $_SESSION['allaccess'] ?? '';
    return strpos($access, $pagename . '_view') !== false;
}
?>

<!-- sidebar menu -->
<div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
    <div class="menu_section">

        <ul class="nav side-menu">

            <!-- ========================= -->
            <!-- MAIN MENU -->
            <!-- ========================= -->

            <?php if (hasaccess('member')) { ?>
<li>
    <a href="index.php?page=member" class="<?= ($page == 'member') ? 'activemenu' : '' ?>">
        <i class="fa fa-folder-o"></i> Members
    </a>
</li>
<?php } ?>

<?php if (hasaccess('prescription')) { ?>
<li>
    <a href="index.php?page=prescription" class="<?= ($page == 'prescription' || $page == 'prescription_details') ? 'activemenu' : '' ?>">
        <i class="fa fa-user-md"></i> Prescriptions
    </a>
</li>
<?php } ?>

<?php if (hasaccess('stock_transaction')) { ?>
<li>
    <a href="index.php?page=stock_transaction" class="<?= ($page == 'stock_transaction') ? 'activemenu' : '' ?>">
        <i class="fa fa-exchange"></i> Dispensing
    </a>
</li>
<?php } ?>

<?php if (hasaccess('mpesa')) { ?>
<li>
    <a href="index.php?page=mpesa" class="<?= ($page == 'mpesa') ? 'activemenu' : '' ?>">
        <i class="fa fa-table"></i> Deposits
    </a>
</li>
<?php } ?>

<?php if (hasaccess('sms')) { ?>
<li>
    <a href="index.php?page=sms" class="<?= ($page == 'sms') ? 'activemenu' : '' ?>">
        <i class="fa fa-comment"></i> SMS Sent
    </a>
</li>
<?php } ?>

            <!-- ========================= -->
            <!-- DATA ANALYSIS -->
            <!-- ========================= -->

            <?php
$da_pages = ['member_statement','group_statement','group_statement_detailed','defaulter_list','userlist','audittrail'];
$da_visible = false;
foreach ($da_pages as $p) { if (hasaccess($p)) { $da_visible = true; break; } }
if ($da_visible) {
?>
<li class="<?= in_array($page, $da_pages) ? 'active' : '' ?>">
    <a><i class="fa fa-bar-chart-o"></i> Data Analysis <span class="fa fa-chevron-down"></span></a>
    <ul class="nav child_menu">
        <?php if (hasaccess('member_statement')) { ?>
        <li><a href="index.php?page=member_statement" class="<?= ($page == 'member_statement') ? 'activemenu' : '' ?>">Member Statement</a></li>
        <?php } ?>
        <?php if (hasaccess('group_statement')) { ?>
        <li><a href="index.php?page=group_statement" class="<?= ($page == 'group_statement') ? 'activemenu' : '' ?>">Group Statement</a></li>
        <?php } ?>
        <?php if (hasaccess('group_statement_detailed')) { ?>
        <li><a href="index.php?page=group_statement_detailed" class="<?= ($page == 'group_statement_detailed') ? 'activemenu' : '' ?>">Detailed Contribution Summary</a></li>
        <?php } ?>
        <?php if (hasaccess('defaulter_list')) { ?>
        <li><a href="index.php?page=defaulter_list" class="<?= ($page == 'defaulter_list') ? 'activemenu' : '' ?>">Defaulters List</a></li>
        <?php } ?>
        <?php if (hasaccess('userlist')) { ?>
        <li><a href="index.php?page=userlist" class="<?= ($page == 'userlist') ? 'activemenu' : '' ?>">User List</a></li>
        <?php } ?>
        <?php if (hasaccess('audittrail')) { ?>
        <li><a href="index.php?page=audittrail" class="<?= ($page == 'audittrail') ? 'activemenu' : '' ?>">Audit Trail</a></li>
        <?php } ?>
    </ul>
</li>
<?php } ?>

            <!-- ========================= -->
            <!-- SETTINGS -->
            <!-- ========================= -->

            <?php
$settings_pages = ['settings','logistics_party','conditions','medicine','unit_of_measure','smssettings','county','provider','roles','roles_pages','roles_access','0_users'];
$settings_visible = false;
foreach ($settings_pages as $p) { if (hasaccess($p)) { $settings_visible = true; break; } }
if ($settings_visible) {
?>
<li class="<?= in_array($page, $settings_pages) ? 'active' : '' ?>">
    <a><i class="fa fa-cogs"></i> Settings <span class="fa fa-chevron-down"></span></a>
    <ul class="nav child_menu">
        <?php if (hasaccess('settings')) { ?>
        <li><a href="index.php?page=settings" class="<?= ($page == 'settings') ? 'activemenu' : '' ?>">Company Settings</a></li>
        <?php } ?>
        <?php if (hasaccess('logistics_party')) { ?>
        <li><a href="index.php?page=logistics_party" class="<?= ($page == 'logistics_party') ? 'activemenu' : '' ?>">Logistics Party</a></li>
        <?php } ?>
        <?php if (hasaccess('conditions')) { ?>
        <li><a href="index.php?page=conditions" class="<?= ($page == 'conditions') ? 'activemenu' : '' ?>">Medical Conditions</a></li>
        <?php } ?>
        <?php if (hasaccess('medicine')) { ?>
        <li><a href="index.php?page=medicine" class="<?= ($page == 'medicine') ? 'activemenu' : '' ?>">Medicine</a></li>
        <?php } ?>
        <?php if (hasaccess('unit_of_measure')) { ?>
        <li><a href="index.php?page=unit_of_measure" class="<?= ($page == 'unit_of_measure') ? 'activemenu' : '' ?>">Units of Measure</a></li>
        <?php } ?>
        <?php if (hasaccess('smssettings')) { ?>
        <li><a href="index.php?page=smssettings" class="<?= ($page == 'smssettings') ? 'activemenu' : '' ?>">SMS Settings</a></li>
        <?php } ?>
        <?php if (hasaccess('county')) { ?>
        <li><a href="index.php?page=county" class="<?= ($page == 'county') ? 'activemenu' : '' ?>">County</a></li>
        <?php } ?>
        <?php if (hasaccess('provider')) { ?>
        <li><a href="index.php?page=provider" class="<?= ($page == 'provider') ? 'activemenu' : '' ?>">Provider</a></li>
        <?php } ?>
        <?php if (hasaccess('roles')) { ?>
        <li><a href="index.php?page=roles" class="<?= ($page == 'roles') ? 'activemenu' : '' ?>">Roles</a></li>
        <?php } ?>
        <?php if (hasaccess('roles_pages')) { ?>
        <li><a href="index.php?page=roles_pages" class="<?= ($page == 'roles_pages') ? 'activemenu' : '' ?>">Pages</a></li>
        <?php } ?>
        <?php if (hasaccess('roles_access')) { ?>
        <li><a href="index.php?page=roles_access" class="<?= ($page == 'roles_access') ? 'activemenu' : '' ?>">Access</a></li>
        <?php } ?>
        <?php if (hasaccess('0_users')) { ?>
        <li><a href="index.php?page=0_users" class="<?= ($page == '0_users') ? 'activemenu' : '' ?>">Users</a></li>
        <?php } ?>
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
                <a href="index.php?page=settings" title="Settings">
                    <span class="fa fa-cogs"></span>
                </a>
            </td>

            <td>
                <a href="index.php?logout=logout" title="Logout">
                    <span class="fa fa-lock"></span>
                </a>
            </td>
        </tr>
    </table>
</div>