<?  //$_SESSION['conn']=$conn =@mysqli_connect('localhost', 'root', '', 'afya');
function return_target($conn,$from1,$to1,$facility)
{
	$number=0;
$dresultsx = @mysqli_query($conn,$fg="SELECT COUNT(logid) AS number  FROM loans where facilitype='$facility' and  disbursed='Disbursed' and trans_date>='$from1' and trans_date<='$to1'");
    $fg;
 
 //$messx = fopen("txt.txt", "a") or die("Unable to open file!");
 //fwrite($messx, $fg ."\n");
// fclose($messx);
 
	 while ($drow = mysqli_fetch_array($dresultsx)) 
	 	{ 
		$number=$drow['number'];
		}	
//	if($to1=='2021-06-13') { $number=30; }
return $number;
}
function dates($date,$days)
{
$date = strtotime($date);
$date = strtotime("$days day", $date);

return date('Y-m-d', $date);
}
function dailyvalues($conn,$facility)
{
for($x=0;$x<100;$x++)
{
$manvalue=0;
$now=(7*$x)-7+1; 
$end=7*$x;
if(dates('2021-07-05',"+".$end)<=date('Y-m-d'))
{
//dates('2021-07-19',"+".$now)."  ".dates('2021-07-19',"+".$end);
if($facility=="Target")
{ $data.="300,"; }
else {
	 if($facility==1) { $manvalue=31; } else { $manvalue=0; } 
$data.=(return_target($conn,'2021-07-05',dates('2021-07-05',"+".$end),$facility)+$manvalue).",";	
}
}
}
  
return rtrim($data, ',');
}

 //echo dailyvalues($conn,"1"); 
?>
<script src="https://code.highcharts.com/highcharts.js"></script>
<style>
.highcharts-figure, .highcharts-data-table table {
    min-width: 310px; 
    max-width: 100%;
    margin: 1em auto;
}

#containerx {
    height: 400px;
}

.highcharts-data-table table {
	font-family: Verdana, sans-serif;
	border-collapse: collapse;
	border: 1px solid #EBEBEB;
	margin: 10px auto;
	text-align: center;
	width: 100%;
	max-width: 500px;
}
.highcharts-data-table caption {
    padding: 1em 0;
    font-size: 1.2em;
    color: #555;
}
.highcharts-data-table th {
	font-weight: 600;
    padding: 0.5em;
}
.highcharts-data-table td, .highcharts-data-table th, .highcharts-data-table caption {
    padding: 0.5em;
}
.highcharts-data-table thead tr, .highcharts-data-table tr:nth-child(even) {
    background: #f8f8f8;
}
.highcharts-data-table tr:hover {
    background: #f1f7ff;
}

</style>
<figure class="highcharts-figure">
    <div id="containerx"></div>
</figure>
<script>
Highcharts.chart('containerx', {

    title: {
        text: 'Weekly loan disbursements vs Target'
    },

    subtitle: {
        text: 'Source: pharmaccess'
    },

    yAxis: {
        title: {
            text: 'Achieved'
        }
    },

    xAxis: {
        type: 'datetime',
            dateTimeLabelFormats: {
                second: '%H:%M:%S',
                minute: '%H:%M',
                hour: '%H:%M',
                day: '%e. %b',
                week: '%e. %b',
                month: '%b \'%y',
                year: '%Y'
            },
            tickInterval: 24 * 3600 * 1000*7 // interval of 1 day
    },

    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'middle'
    },

    plotOptions: {
        series: {
            label: {
                connectorAllowed: false
            },
            pointStart: Date.UTC(2021, 6, 5) // feb 12, 2015
        }
    },

    series: [{
        name: 'NHIF',
        data: [<? echo dailyvalues($conn,"1");?>],
		pointInterval: 24 * 3600 * 1000 * 7
    }, {
        name: 'Treatment',
        data: [<? echo dailyvalues($conn,"2");?>],
		pointInterval: 24 * 3600 * 1000 * 7
    }, {
        name: 'Target',
        data: [<? echo dailyvalues($conn,"Target");?>],
		pointInterval: 24 * 3600 * 1000 * 7
    }],

    responsive: {
        rules: [{
            condition: {
                maxWidth: 1000
            },
            chartOptions: {
                legend: {
                    layout: 'horizontal',
                    align: 'center',
                    verticalAlign: 'bottom'
                }
            }
        }]
    }

});


</script>
