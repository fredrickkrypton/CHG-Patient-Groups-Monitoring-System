<?  $title="Dashboard"; 
 
 
?>
<style>
.row {
    margin-right: 10px;
    margin-left: 10px; 
}
canvas {
	height:300px;
	width: 100%;
	
}
.chartContainer 
{
	height:300px;
}

</style>
<div class="row">
<div class="col-md-12">


 <table width="100%" border="0">
 <tr>
    <td>
   <script>
 window.addEventListener("load",function()
 {
 

var chart = new CanvasJS.Chart("chartxContainer", {
	animationEnabled: true,
	title:{
		text: "Daily Enrollment"
	},
	axisX: {
		valueFormatString: "DD MMM,YY"
	},
	axisY: {
		title: "Average",
		suffix: " "
	},
	legend:{
		cursor: "pointer",
		fontSize: 16,
		itemclick: toggleDataSeries
	},
	toolTip:{
		shared: true
	},
	data: [{
		name: "All",
		type: "spline",
		yValueFormatString: "#0.## °C",
		showInLegend: true,
		dataPoints: [
			{ x: new Date(2017,6,24), y: 31 },
			{ x: new Date(2017,6,25), y: 31 },
			{ x: new Date(2017,6,26), y: 29 },
			{ x: new Date(2017,6,27), y: 29 },
			{ x: new Date(2017,6,28), y: 31 },
			{ x: new Date(2017,6,29), y: 30 },
			{ x: new Date(2017,6,30), y: 29 }
		]
	},
	{
		name: "Active",
		type: "spline",
		yValueFormatString: "#0.## °C",
		showInLegend: true,
		dataPoints: [
			{ x: new Date(2017,6,24), y: 20 },
			{ x: new Date(2017,6,25), y: 20 },
			{ x: new Date(2017,6,26), y: 25 },
			{ x: new Date(2017,6,27), y: 25 },
			{ x: new Date(2017,6,28), y: 25 },
			{ x: new Date(2017,6,29), y: 25 },
			{ x: new Date(2017,6,30), y: 25 }
		]
	},
	{
		name: "MLABS",
		type: "spline",
		yValueFormatString: "#0.## °C",
		showInLegend: true,
		dataPoints: [
			{ x: new Date(2017,6,24), y: 22 },
			{ x: new Date(2017,6,25), y: 19 },
			{ x: new Date(2017,6,26), y: 23 },
			{ x: new Date(2017,6,27), y: 24 },
			{ x: new Date(2017,6,28), y: 24 },
			{ x: new Date(2017,6,29), y: 23 },
			{ x: new Date(2017,6,30), y: 23 }
		]
	}]
});
chart.render();
 
function toggleDataSeries(e){
	if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
		e.dataSeries.visible = false;
	}
	else{
		e.dataSeries.visible = true;
	}
	chart.render();
}

})
</script>
 
<div id="chartxContainer" style="height: 300px; width: 100%;"></div>
</td></tr>

<tr><td style="background-color:#666"> &nbsp;</td></tr>

<tr><td>
<script>
  window.addEventListener("load",function()
 { 
var chart = new CanvasJS.Chart("chartContainer4", {
	animationEnabled: true,
	title:{
		text: "Daily Collection",
		fontFamily: "arial black",
		fontColor: "#695A42"
	},
	axisX: {
		interval: 1,
		intervalType: "year"
	},
	axisY:{
		valueFormatString:"Kshs#0",
		gridColor: "#B6B1A8",
		tickColor: "#B6B1A8"
	},
	toolTip: {
		shared: true,
		content: toolTipContent
	},
	data: [
		{        
			type: "stackedColumn",
			showInLegend: true,
			name: "MTIBA",
			color: "#4261CA",
			dataPoints: [
				{ y: 6.82, x: new Date(2010,0) },
				{ y: 9.02, x: new Date(2011,0) },
				{ y: 11.80, x: new Date(2012,0) },
				{ y: 14.11, x: new Date(2013,0) },
				{ y: 15.96, x: new Date(2014,0) },
				{ y: 17.73, x: new Date(2015,0) },
				{ y: 2, x: new Date(2016,0) }
			]
		},
		{        
			type: "stackedColumn",
			showInLegend: true,
			name: "CASH",
			color: "#FFC000",
			dataPoints: [
				{ y: 7.28, x: new Date(2010,0) },
				{ y: 9.72, x: new Date(2011,0) },
				{ y: 13.30, x: new Date(2012,0) },
				{ y: 14.9, x: new Date(2013,0) },
				{ y: 18.10, x: new Date(2014,0) },
				{ y: 18.68, x: new Date(2015,0) },
				{ y: 3, x: new Date(2016,0) }
			]
		} 
		]
});
chart.render();

function toolTipContent(e) {
	var str = "";
	var total = 0;
	var str2, str3;
	for (var i = 0; i < e.entries.length; i++){
		var  str1 = "<span style= \"color:"+e.entries[i].dataSeries.color + "\"> "+e.entries[i].dataSeries.name+"</span>: Kshs<strong>"+e.entries[i].dataPoint.y+"</strong><br/>";
		total = e.entries[i].dataPoint.y + total;
		str = str.concat(str1);
	}
	str2 = "<span style = \"color:DodgerBlue;\"><strong>"+(e.entries[0].dataPoint.x).getFullYear()+"</strong></span><br/>";
	total = Math.round(total * 100) / 100;
	str3 = "<span style = \"color:Tomato\">Total:</span><strong> Kshs."+total+"</strong><br/>";
	return (str2.concat(str)).concat(str3);
}

})
</script>
 
<div id="chartContainer4" style="height: 300px; width: 100%;"></div>
</td></tr>

<tr><td style="background-color:#666"> &nbsp;</td></tr>


 <tr>
    <td>
    <script>
  window.addEventListener("load",function()
 { 

var chart = new CanvasJS.Chart("chartContainer3", {
	animationEnabled: true,
	title:{
		text: "Medical Conditions"
	},
	axisY :{
		valueFormatString: "#0,.",
		suffix: "k"
	},
	axisX: {
		title: "Months After Launch"
	},
	toolTip: {
		shared: true
	},
	data: [{        
		type: "stackedArea",
		showInLegend: true,
		toolTipContent: "<span style=\"color:#4F81BC\"><strong>{name}: </strong></span> {y}",
		name: "BOTH",
		dataPoints: [
		{ x: 1, y: 3000 },
		{ x: 2, y: 7000 },
		{ x: 3, y: 10000 },
		{ x: 4, y: 14000 },
		{ x: 5, y: 23000 },
		{ x: 6, y: 31000 },
		{ x: 7, y: 42000 },
		{ x: 8, y: 56000 },
		{ x: 9, y: 64000 },
		{ x: 10, y: 81000 },
		{ x: 11, y: 105000 }
		]
	},
	{        
		type: "stackedArea",  
		name: "DM",
		toolTipContent: "<span style=\"color:#C0504E\"><strong>{name}: </strong></span> {y}",
		showInLegend: true,
		dataPoints: [
		{ x: 4, y: 4000 },
		{ x: 5, y: 6000 },
		{ x: 6, y: 9000 },
		{ x: 7,y: 14000 },
		{ x: 8,y: 21000 },
		{ x: 9, y: 31000 },
		{ x: 10, y: 46000 },
		{ x: 11, y: 61000 }   
		]
	},
	{        
		type: "stackedArea",  
		name: "HTN",
		toolTipContent: "<span style=\"color:#C0504E\"><strong>{name}: </strong></span> {y}",
		showInLegend: true,
		dataPoints: [
		{ x: 4, y: 4000 },
		{ x: 5, y: 6000 },
		{ x: 6, y: 9000 },
		{ x: 7,y: 14000 },
		{ x: 8,y: 21000 },
		{ x: 9, y: 31000 },
		{ x: 10, y: 46000 },
		{ x: 11, y: 61000 }   
		]
	}]
});
chart.render();

})
</script>
 
<div id="chartContainer3" style="height: 300px; width: 100%;"></div></td></tr>
    
</table>

  
 
</div>
 
</div>
 

 

 
 
</div>
                      </div>
                      </div>
                      </div>
                      </div>
                       </div>           
            
            <? /**/ ?>
     
        <!-- /page content -->
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
   
    