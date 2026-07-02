<?php
// ensure page is defined safely
$page = $_GET['page'] ?? 'dashboard';
?>

<!-- sidebar menu -->
<div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
    <div class="menu_section">

        <ul class="nav side-menu">

            <!-- ========================= -->
            <!-- MAIN MENU -->
            <!-- ========================= -->

            <li>
                <a href="index.php?page=member"
                   class="<?= ($page == 'member') ? 'activemenu' : '' ?>">
                    <i class="fa fa-folder-o"></i> Members
                </a>
            </li>

            <li>
                <a href="index.php?page=prescription"
                   class="<?= ($page == 'prescription' || $page == 'prescription_details') ? 'activemenu' : '' ?>">
                    <i class="fa fa-user-md"></i> Prescriptions
                </a>
            </li>

            <li>
                <a href="index.php?page=stock_transaction"
                   class="<?= ($page == 'stock_transaction') ? 'activemenu' : '' ?>">
                    <i class="fa fa-exchange"></i> Dispensing
                </a>
            </li>

            <li>
                <a href="index.php?page=mpesa"
                   class="<?= ($page == 'mpesa') ? 'activemenu' : '' ?>">
                    <i class="fa fa-table"></i> Deposits
                </a>
            </li>

            <li>
                <a href="index.php?page=sms"
                   class="<?= ($page == 'sms') ? 'activemenu' : '' ?>">
                    <i class="fa fa-comment"></i> SMS Sent
                </a>
            </li>

            <!-- ========================= -->
            <!-- DATA ANALYSIS -->
            <!-- ========================= -->

            <li class="<?= in_array($page, [
                'group_statement',
                'member_statement',
                'group_statement_detailed',
                'defaulter_list',
                'audittrail',
                'userlist'
            ]) ? 'active' : '' ?>">

                <a>
                    <i class="fa fa-bar-chart-o"></i> Data Analysis <span class="fa fa-chevron-down"></span>
                </a>

                <ul class="nav child_menu">

                    <li>
                        <a href="index.php?page=member_statement"
                           class="<?= ($page == 'member_statement') ? 'activemenu' : '' ?>">
                            Member Statement
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=group_statement"
                           class="<?= ($page == 'group_statement') ? 'activemenu' : '' ?>">
                            Group Statement
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=group_statement_detailed"
                           class="<?= ($page == 'group_statement_detailed') ? 'activemenu' : '' ?>">
                            Detailed Contribution Summary
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=defaulter_list"
                           class="<?= ($page == 'defaulter_list') ? 'activemenu' : '' ?>">
                            Defaulters List
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=userlist"
                           class="<?= ($page == 'userlist') ? 'activemenu' : '' ?>">
                            User List
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=audittrail"
                           class="<?= ($page == 'audittrail') ? 'activemenu' : '' ?>">
                            Audit Trail
                        </a>
                    </li>

                </ul>
            </li>

            <!-- ========================= -->
            <!-- SETTINGS -->
            <!-- ========================= -->

            <li class="<?= in_array($page, [
                'settings',
                'logistics_party',
                'medicine',
                'unit_of_measure',
                'tenorperiod',
                'county',
                'provider',
                'smssettings',
                'conditions',
                'subcounty',
                'roles',
                'roles_pages',
                'roles_access',
                '0_users'
            ]) ? 'active' : '' ?>">

                <a>
                    <i class="fa fa-cogs"></i> Settings <span class="fa fa-chevron-down"></span>
                </a>

                <ul class="nav child_menu">

                    <li>
                        <a href="index.php?page=settings"
                           class="<?= ($page == 'settings') ? 'activemenu' : '' ?>">
                            Company Settings
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=logistics_party"
                           class="<?= ($page == 'logistics_party') ? 'activemenu' : '' ?>">
                            Logistics Party
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=conditions"
                           class="<?= ($page == 'conditions') ? 'activemenu' : '' ?>">
                            Medical Conditions
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=medicine"
                           class="<?= ($page == 'medicine') ? 'activemenu' : '' ?>">
                            Medicine
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=unit_of_measure"
                           class="<?= ($page == 'unit_of_measure') ? 'activemenu' : '' ?>">
                            Units of Measure
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=smssettings"
                           class="<?= ($page == 'smssettings') ? 'activemenu' : '' ?>">
                            SMS Settings
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=county"
                           class="<?= ($page == 'county') ? 'activemenu' : '' ?>">
                            County
                        </a>
                    </li>

                    <li>
                        <a href="index.php?page=provider"
                           class="<?= ($page == 'provider') ? 'activemenu' : '' ?>">
                            Provider
                        </a>
                    </li>

                    <!-- Access Sub-menu (Kept clean as flat list options or single sub-group) -->
                    <li>
                        <a href="index.php?page=roles" class="<?= ($page == 'roles') ? 'activemenu' : '' ?>">Roles</a>
                    </li>
                    <li>
                        <a href="index.php?page=roles_pages" class="<?= ($page == 'roles_pages') ? 'activemenu' : '' ?>">Pages</a>
                    </li>
                    <li>
                        <a href="index.php?page=roles_access" class="<?= ($page == 'roles_access') ? 'activemenu' : '' ?>">Access</a>
                    </li>
                    <li>
                        <a href="index.php?page=0_users" class="<?= ($page == '0_users') ? 'activemenu' : '' ?>">Users</a>
                    </li>

                </ul>
            </li>

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