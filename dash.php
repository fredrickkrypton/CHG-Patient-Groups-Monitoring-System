<?php
$title = "Dashboard";
$conn  = $conn ?? ($GLOBALS['conn'] ?? null);

$currency  = 'UGX';
$is_leader = (($_SESSION['userrole'] ?? 0) == 4);          // group leaders only see their own group
$own_group = $is_leader ? (string)($_SESSION['groupid'] ?? '') : '';

/* ------------------------------------------------------------------ *
 * Helpers (every query is guarded: a missing table or column shows "-"
 * on the dashboard instead of breaking the whole page)
 * ------------------------------------------------------------------ */
if (!function_exists('dash_rows')) {
    // Returns an array of rows, or null when the query fails
    function dash_rows($conn, $sql, $types = '', $params = array()) {
        if (!$conn) { return null; }
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) { return null; }
            if ($types !== '') { $stmt->bind_param($types, ...$params); }
            if (!$stmt->execute()) { $stmt->close(); return null; }
            $res  = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('dash.php: ' . $e->getMessage());
            return null;
        }
    }
}
if (!function_exists('dash_scalar')) {
    function dash_scalar($conn, $sql, $types = '', $params = array()) {
        $rows = dash_rows($conn, $sql, $types, $params);
        if ($rows === null || !$rows) { return null; }
        $first = reset($rows);
        return reset($first);
    }
}
if (!function_exists('dash_money_short')) {
    // 3200000 -> "UGX 3.2M", 450000 -> "UGX 450K", 1250 -> "UGX 1,250"
    function dash_money_short($amount, $currency = 'UGX') {
        $a = (float)$amount; $sign = $a < 0 ? '-' : ''; $a = abs($a);
        if ($a >= 1000000000)  { $n = rtrim(rtrim(number_format($a / 1000000000, 1), '0'), '.') . 'B'; }
        elseif ($a >= 1000000) { $n = rtrim(rtrim(number_format($a / 1000000, 1), '0'), '.') . 'M'; }
        elseif ($a >= 100000)  { $n = rtrim(rtrim(number_format($a / 1000, 1), '0'), '.') . 'K'; }
        else                   { $n = number_format($a, 0); }
        return $currency . ' ' . $sign . $n;
    }
}
if (!function_exists('dash_link')) {
    // Link a card to its page only when that page file exists
    function dash_link($page) { return file_exists($page . '.php') ? 'index.php?page=' . $page : ''; }
}

$today       = date('Y-m-d');
$month_key   = date('Y-m');
$start_30    = date('Y-m-d', strtotime('-29 days'));
$dexpr       = "LEFT(REPLACE(REPLACE(transactiondate,'-',''),' ',''),8)";   // YYYYMMDD from either stored date format

/* ------------------------------------------------------------------ *
 * Card numbers
 * ------------------------------------------------------------------ */
// Members
if ($is_leader) { $members = dash_scalar($conn, "SELECT COUNT(*) FROM `member` WHERE group_id=?", 's', array($own_group)); }
else            { $members = dash_scalar($conn, "SELECT COUNT(*) FROM `member`"); }

// Prescriptions (this month)
if ($is_leader) {
    $rx_month = dash_scalar($conn, "SELECT COUNT(*) FROM prescription p JOIN `member` m ON m.logid = p.member_id WHERE m.group_id=? AND DATE_FORMAT(p.dispensation_dt,'%Y-%m')=?", 'ss', array($own_group, $month_key));
    $rx_all   = dash_scalar($conn, "SELECT COUNT(*) FROM prescription p JOIN `member` m ON m.logid = p.member_id WHERE m.group_id=?", 's', array($own_group));
} else {
    $rx_month = dash_scalar($conn, "SELECT COUNT(*) FROM prescription WHERE DATE_FORMAT(dispensation_dt,'%Y-%m')=?", 's', array($month_key));
    $rx_all   = dash_scalar($conn, "SELECT COUNT(*) FROM prescription");
}

// Dispensing (medicines issued, cumulative)
if ($is_leader) {
    $dispensed = dash_scalar($conn, "SELECT COUNT(*) FROM prescription_details d JOIN prescription p ON p.logid = d.prescription_id JOIN `member` m ON m.logid = p.member_id WHERE m.group_id=?", 's', array($own_group));
} else {
    $dispensed = dash_scalar($conn, "SELECT COUNT(*) FROM prescription_details");
}

// Deposits (not shown to group leaders)
$dep_total = null; $dep_today = null;
if (!$is_leader) {
    $dep_total = dash_scalar($conn, "SELECT COALESCE(SUM(amount),0) FROM mpesa");
    $dep_today = dash_scalar($conn, "SELECT COALESCE(SUM(amount),0) FROM mpesa WHERE $dexpr = ?", 's', array(date('Ymd')));
}

$fmt = function ($v) { return ($v === null) ? '-' : number_format((float)$v); };

/* ------------------------------------------------------------------ *
 * Chart data (last 30 days)
 * ------------------------------------------------------------------ */
$days = array();
for ($i = 29; $i >= 0; $i--) { $days[date('Y-m-d', strtotime("-$i days"))] = 0; }

// Daily enrollment = members registered per day
$enrol = $days; $enrol_ok = false;
$sql   = "SELECT DATE(created_on) AS d, COUNT(*) AS n FROM `member` WHERE created_on >= ?" . ($is_leader ? " AND group_id=?" : "") . " GROUP BY DATE(created_on)";
$rows  = $is_leader ? dash_rows($conn, $sql, 'ss', array($start_30, $own_group)) : dash_rows($conn, $sql, 's', array($start_30));
if ($rows !== null) { $enrol_ok = true; foreach ($rows as $r) { if (isset($enrol[$r['d']])) { $enrol[$r['d']] = (int)$r['n']; } } }

// Daily collection = deposits per day
$coll = $days; $coll_ok = false;
if (!$is_leader) {
    $rows = dash_rows($conn,
        "SELECT STR_TO_DATE($dexpr,'%Y%m%d') AS d, SUM(amount) AS t FROM mpesa WHERE $dexpr >= ? GROUP BY d",
        's', array(str_replace('-', '', $start_30)));
    if ($rows !== null) { $coll_ok = true; foreach ($rows as $r) { if (isset($coll[$r['d']])) { $coll[$r['d']] = (float)$r['t']; } } }
}

// Medical conditions: members per condition (group leaders only see their own group)
$conditions_sql = "SELECT COALESCE(c.description, 'No medical condition') AS label, COUNT(*) AS n
                     FROM `member` m
                     LEFT JOIN conditions c ON c.logid = m.medical_condition"
                . ($is_leader ? " WHERE m.group_id=?" : "")
                . " GROUP BY c.logid, c.description
                    ORDER BY n DESC";

$cond = array();
$rows = $is_leader
    ? dash_rows($conn, $conditions_sql, 's', array($own_group))
    : dash_rows($conn, $conditions_sql);
if ($rows) {
    foreach ($rows as $r) { $cond[] = array('label' => (string)$r['label'], 'y' => (int)$r['n']); }
}

$enrol_pts = array(); foreach ($enrol as $d => $n) { $enrol_pts[] = array('d' => $d, 'y' => $n); }
$coll_pts  = array(); foreach ($coll  as $d => $n) { $coll_pts[]  = array('d' => $d, 'y' => $n); }
$jf = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

$l_members = dash_link('member') ?: dash_link('members');
$l_rx      = dash_link('prescription');
$l_disp    = dash_link('prescription_details');
$l_dep     = dash_link('mpesa');
?>

<style>
    /* Ensure charts have explicit relative bounds to prevent layout leakage */
    .dashboard {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    /* ==========================================
       MODERN STAT CARDS (icon + bar style)
       ========================================== */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 10px;
    }

    .card {
        background: #ffffff;
        padding: 18px 20px 16px;
        border-radius: 12px;
        border: 3px solid #0284c7;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.1);
        transition: transform 0.15s, box-shadow 0.15s;
        position: relative;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.2);
    }

    a.card-link { display: block; text-decoration: none; color: inherit; }
    a.card-link:focus-visible .card { outline: 2px solid #1ea7e1; outline-offset: 3px; }

    .card-top {
        margin-bottom: 12px;
    }

    .card-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .card h3 {
        margin: 0 0 6px 0;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #8a94a6;
    }

    .card p {
        margin: 0 0 8px 0;
        font-size: 26px;
        font-weight: 800;
        color: #1a3550;
    }

    .card-sub {
        display: block;
        font-size: 12px;
        color: #8a94a6;
        font-weight: 500;
        margin-bottom: 14px;
    }

    .card-sub i {
        margin-right: 4px;
    }

    .card-bar {
        height: 4px;
        border-radius: 4px;
        width: 100%;
    }

    /* COLOR THEMES (icon + bottom bar accents) */
    .card-blue .card-icon   { background: #e6f4fd; color: #1ea7e1; }
    .card-blue .card-bar    { background: linear-gradient(90deg, #1ea7e1, #cdeafb); }

    .card-red .card-icon    { background: #fdeaea; color: #e02020; }
    .card-red .card-bar     { background: linear-gradient(90deg, #e02020, #fbcaca); }

    .card-green .card-icon  { background: #e6f9f1; color: #12b76a; }
    .card-green .card-bar   { background: linear-gradient(90deg, #12b76a, #c8f4e0); }

    .card-purple .card-icon { background: #f0ecfd; color: #7c5cf0; }
    .card-purple .card-bar  { background: linear-gradient(90deg, #7c5cf0, #ddd3fb); }

    /* PANELS */
    .panel {
        background: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        border: 1px solid #e2e8f0;
    }
    .panel h3 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #334155;
        display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap;
    }
    .panel h3 small { font-size: 12px; font-weight: 500; color: #8a94a6; }
    .chart-empty {
        height: 300px; display: flex; align-items: center; justify-content: center; text-align: center;
        color: #8a94a6; font-size: 14px; border: 1px dashed #d5dde8; border-radius: 8px; padding: 20px;
    }

    /* CRITICAL OVERRIDE: Neutralizes CanvasJS from blocking page interactions */
    .canvasjs-chart-container, .canvasjs-chart-canvas {
        position: relative !important;
    }
    .canvasjs-chart-credit {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
</style>

<div class="dashboard">

    <div class="cards">

        <?php if ($l_members) { echo '<a class="card-link" href="' . htmlspecialchars($l_members) . '">'; } ?>
        <div class="card card-blue">
            <div class="card-top">
                <div class="card-icon"><i class="fa fa-users"></i></div>
            </div>
            <h3>Members</h3>
            <p><?= $fmt($members) ?></p>
            <span class="card-sub"><i class="fa fa-signal"></i> Registered</span>
            <div class="card-bar"></div>
        </div>
        <?php if ($l_members) { echo '</a>'; } ?>

        <?php if ($l_rx) { echo '<a class="card-link" href="' . htmlspecialchars($l_rx) . '">'; } ?>
        <div class="card card-red" title="<?= $rx_all === null ? '' : number_format((float)$rx_all) . ' prescriptions in total' ?>">
            <div class="card-top">
                <div class="card-icon"><i class="fa fa-file-prescription"></i></div>
            </div>
            <h3>Prescriptions</h3>
            <p><?= $fmt($rx_month) ?></p>
            <span class="card-sub"><i class="fa fa-clock"></i> This month</span>
            <div class="card-bar"></div>
        </div>
        <?php if ($l_rx) { echo '</a>'; } ?>

        <?php if ($l_disp) { echo '<a class="card-link" href="' . htmlspecialchars($l_disp) . '">'; } ?>
        <div class="card card-green">
            <div class="card-top">
                <div class="card-icon"><i class="fa fa-exchange-alt"></i></div>
            </div>
            <h3>Dispensing</h3>
            <p><?= $fmt($dispensed) ?></p>
            <span class="card-sub"><i class="fa fa-arrow-trend-up"></i> Cumulative</span>
            <div class="card-bar"></div>
        </div>
        <?php if ($l_disp) { echo '</a>'; } ?>

        <?php if (!$is_leader) { ?>
        <?php if ($l_dep) { echo '<a class="card-link" href="' . htmlspecialchars($l_dep) . '">'; } ?>
        <div class="card card-purple" title="<?= $dep_total === null ? '' : htmlspecialchars($currency . ' ' . number_format((float)$dep_total, 0)) . ' in total, ' . htmlspecialchars($currency . ' ' . number_format((float)$dep_today, 0)) . ' today' ?>">
            <div class="card-top">
                <div class="card-icon"><i class="fa fa-coins"></i></div>
            </div>
            <h3>Deposits</h3>
            <p><?= $dep_total === null ? '-' : htmlspecialchars(dash_money_short($dep_total, $currency)) ?></p>
            <span class="card-sub"><i class="fa fa-wallet"></i> Total collected</span>
            <div class="card-bar"></div>
        </div>
        <?php if ($l_dep) { echo '</a>'; } ?>
        <?php } ?>

    </div>

    <div class="panel">
        <h3>Daily Enrollment <small>New members, last 30 days</small></h3>
        <?php if ($enrol_ok) { ?>
        <div id="chartxContainer" style="height:300px; width:100%;"></div>
        <?php } else { ?>
        <div class="chart-empty">Enrollment dates are not available yet. The members table needs a <strong>&nbsp;created_on&nbsp;</strong> column.</div>
        <?php } ?>
    </div>

    <?php if (!$is_leader) { ?>
    <div class="panel">
        <h3>Daily Collection <small>Deposits in <?= htmlspecialchars($currency) ?>, last 30 days</small></h3>
        <?php if ($coll_ok) { ?>
        <div id="chartContainer4" style="height:300px; width:100%;"></div>
        <?php } else { ?>
        <div class="chart-empty">Deposit data is not available yet. Check that the deposits table has <strong>&nbsp;amount&nbsp;</strong> and <strong>&nbsp;transactiondate&nbsp;</strong> columns.</div>
        <?php } ?>
    </div>
    <?php } ?>

    <div class="panel">
        <h3>Medical Conditions <small>Members per condition</small></h3>
        <?php if ($cond) { ?>
        <div id="chartContainer3" style="height:300px; width:100%;"></div>
        <?php } else { ?>
        <div class="chart-empty">No members with a medical condition recorded yet.</div>
        <?php } ?>
    </div>

</div>

<script>
function initDashboardCharts() {
    function toDate(s) { var p = s.split('-'); return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10)); }

    var enrol = <?= json_encode($enrol_pts, $jf) ?>;
    var coll  = <?= json_encode($coll_pts, $jf) ?>;
    var cond  = <?= json_encode($cond, $jf) ?>;
    var cur   = <?= json_encode($currency, $jf) ?>;

    // Chart 1: daily enrollment
    if (document.getElementById("chartxContainer")) {
        new CanvasJS.Chart("chartxContainer", {
            animationEnabled: true,
            theme: "light2",
            title: { text: "" },
            axisX: { valueFormatString: "DD MMM", intervalType: "day", interval: 3 },
            axisY: { title: "New members", minimum: 0, interval: 1, includeZero: true },
            toolTip: { shared: true },
            data: [{
                type: "splineArea",
                color: "#1ea7e1",
                fillOpacity: .15,
                markerSize: 5,
                xValueFormatString: "DD MMM YYYY",
                yValueFormatString: "#,##0",
                dataPoints: enrol.map(function (r) { return { x: toDate(r.d), y: r.y }; })
            }]
        }).render();
    }

    // Chart 2: daily collection
    if (document.getElementById("chartContainer4")) {
        new CanvasJS.Chart("chartContainer4", {
            animationEnabled: true,
            theme: "light2",
            axisX: { valueFormatString: "DD MMM", intervalType: "day", interval: 3 },
            axisY: { title: cur, prefix: cur + " ", includeZero: true, valueFormatString: "#,##0" },
            toolTip: { shared: true },
            data: [{
                type: "column",
                color: "#7c5cf0",
                xValueFormatString: "DD MMM YYYY",
                yValueFormatString: "#,##0",
                dataPoints: coll.map(function (r) { return { x: toDate(r.d), y: r.y }; })
            }]
        }).render();
    }

    // Chart 3: medical conditions
    if (document.getElementById("chartContainer3") && cond.length) {
        new CanvasJS.Chart("chartContainer3", {
            animationEnabled: true,
            theme: "light2",
            axisY: { includeZero: true, interval: 1 },
            data: [{
                type: "bar",
                color: "#12b76a",
                yValueFormatString: "#,##0",
                dataPoints: cond.slice().reverse()
            }]
        }).render();
    }
}

// Fire immediately if page elements are already loaded, or bind cleanly to window state
if (document.readyState === "complete" || document.readyState === "interactive") {
    initDashboardCharts();
} else {
    document.addEventListener("DOMContentLoaded", initDashboardCharts);
}
</script>