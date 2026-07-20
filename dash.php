<?php $title = "Dashboard"; ?>

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

        <div class="card card-blue">
            <div class="card-top">
                <div class="card-icon"><i class="fa fa-users"></i></div>
            </div>
            <h3>Members</h3>
            <p>120</p>
            <span class="card-sub"><i class="fa fa-signal"></i> Active</span>
            <div class="card-bar"></div>
        </div>

        <div class="card card-red">
            <div class="card-top">
                <div class="card-icon"><i class="fa fa-file-prescription"></i></div>
            </div>
            <h3>Prescriptions</h3>
            <p>80</p>
            <span class="card-sub"><i class="fa fa-clock"></i> This month</span>
            <div class="card-bar"></div>
        </div>

        <div class="card card-green">
            <div class="card-top">
                <div class="card-icon"><i class="fa fa-exchange-alt"></i></div>
            </div>
            <h3>Dispensing</h3>
            <p>55</p>
            <span class="card-sub"><i class="fa fa-arrow-trend-up"></i> Cumulative</span>
            <div class="card-bar"></div>
        </div>

        <div class="card card-purple">
            <div class="card-top">
                <div class="card-icon"><i class="fa fa-coins"></i></div>
            </div>
            <h3>Deposits</h3>
            <p>UGX 3.2M</p>
            <span class="card-sub"><i class="fa fa-wallet"></i> Total collected</span>
            <div class="card-bar"></div>
        </div>

    </div>

    <div class="panel">
        <h3>Daily Enrollment</h3>
        <div id="chartxContainer" style="height:300px; width:100%;"></div>
    </div>

    <div class="panel">
        <h3>Daily Collection</h3>
        <div id="chartContainer4" style="height:300px; width:100%;"></div>
    </div>

    <div class="panel">
        <h3>Medical Conditions</h3>
        <div id="chartContainer3" style="height:300px; width:100%;"></div>
    </div>

</div>

<script>
function initDashboardCharts() {
    // Chart 1
    var chart1 = new CanvasJS.Chart("chartxContainer", {
        animationEnabled: true,
        theme: "light2",
        title: { text: "" },
        data: [{
            type: "spline",
            dataPoints: [
                { x: new Date(2025, 0, 1), y: 30 },
                { x: new Date(2025, 0, 2), y: 40 },
                { x: new Date(2025, 0, 3), y: 35 },
                { x: new Date(2025, 0, 4), y: 50 }
            ]
        }]
    });
    chart1.render();

    // Chart 2
    var chart2 = new CanvasJS.Chart("chartContainer4", {
        animationEnabled: true,
        theme: "light2",
        data: [{
            type: "column",
            dataPoints: [
                { x: 1, y: 10 },
                { x: 2, y: 20 },
                { x: 3, y: 15 }
            ]
        }]
    });
    chart2.render();

    // Chart 3
    var chart3 = new CanvasJS.Chart("chartContainer3", {
        animationEnabled: true,
        theme: "light2",
        data: [{
            type: "area",
            dataPoints: [
                { x: 1, y: 5 },
                { x: 2, y: 10 },
                { x: 3, y: 15 }
            ]
        }]
    });
    chart3.render();
}

// Fire immediately if page elements are already loaded, or bind cleanly to window state
if (document.readyState === "complete" || document.readyState === "interactive") {
    initDashboardCharts();
} else {
    document.addEventListener("DOMContentLoaded", initDashboardCharts);
}
</script>