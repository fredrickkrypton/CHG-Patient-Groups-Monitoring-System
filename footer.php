<script>
	function callme(){
//This promise will resolve when the network call succeeds
//Feel free to make a REST fetch using promises and assign it to networkPromise
var networkPromise = fetch('test.php');


//This promise will resolve when 2 seconds have passed
var timeOutPromise = new Promise(function(resolve, reject) {
  // 2 Second delay
  setTimeout(resolve,15000, 'Timeout Done');
});

Promise.all(
[networkPromise, timeOutPromise]).then(function(values) {
  console.log("Atleast 5 secs + TTL (Network/server)");
  //Repeat
  callme();
});
}
callme();
</script> <!-- footer content -->
        <footer>
          <div class="pull-right">
            Copyright &copy; <?=$Appname." ".$Appnameversion?> 
          </div>
          <div class="clearfix"></div>
        </footer>
        <!-- /footer content -->
      </div>
    </div>

    <!-- jQuery -->
<script src="vendors/jquery/dist/jquery.min.js"></script>
<script src="vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="vendors/fastclick/lib/fastclick.js"></script>
<script src="vendors/nprogress/nprogress.js"></script>
<script src="vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js"></script>
 
  



 
    <!-- bootstrap-daterangepicker -->
    <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- bootstrap-datetimepicker -->    
    <script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
 
    <!-- iCheck -->
    <script src="vendors/iCheck/icheck.min.js"></script>
   
    <script src="vendors/jszip/dist/jszip.min.js"></script>
    <script src="vendors/pdfmake/build/pdfmake.min.js"></script>
    <script src="vendors/pdfmake/build/vfs_fonts.js"></script>
       
    <script src="vendors/ion.rangeSlider/js/ion.rangeSlider.min.js"></script>
    <!-- Bootstrap Colorpicker -->
    <script src="vendors/mjolnic-bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js"></script>
    <!-- jquery.inputmask -->
    <script src="vendors/jquery.inputmask/dist/min/jquery.inputmask.bundle.min.js"></script>
    <!-- jQuery Knob -->
    <script src="vendors/jquery-knob/dist/jquery.knob.min.js"></script>
    
  <!-- bootstrap-wysiwyg -->
<script src="vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js"></script>
<script src="vendors/jquery.hotkeys/jquery.hotkeys.js"></script>
<script src="vendors/google-code-prettify/src/prettify.js"></script>

<!-- PNotify -->
<script src="vendors/pnotify/dist/pnotify.js"></script>
<script src="vendors/pnotify/dist/pnotify.buttons.js"></script>
<script src="vendors/pnotify/dist/pnotify.nonblock.js"></script>


 <script  type="text/javascript">
   $(function () {
                $('#myDatepicker').datetimepicker();
            });
    for(s=1;s<5;s++){
    $('#datepicker'+s).datetimepicker({
        format: 'DD/MM/YYYY'
    });
	}
     
	
</script>
  <!-- Datatables -->
    <script src="vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
    <script src="vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
    <script src="vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
    <script src="vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
    <script src="vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
    <script src="vendors/datatables.net-scroller/js/dataTables.scroller.min.js"></script>
    
    
    <? if($page=='index.php'||$page=='index.php?'||$page=='') { ?>
      
    <!-- Chart.js -->
    <script src="vendors/Chart.js/dist/Chart.min.js"></script>
    <!-- jQuery Sparklines -->
    <script src="vendors/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
    <!-- Flot -->
    <script src="vendors/Flot/jquery.flot.js"></script>
    <script src="vendors/Flot/jquery.flot.pie.js"></script>
    <script src="vendors/Flot/jquery.flot.time.js"></script>
    <script src="vendors/Flot/jquery.flot.stack.js"></script>
    <script src="vendors/Flot/jquery.flot.resize.js"></script>
    <!-- Flot plugins -->
    <script src="vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
    <script src="vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
    <script src="vendors/flot.curvedlines/curvedLines.js"></script>
   
     
     <? } ?>
      <!-- DateJS -->
    <script src="vendors/DateJS/build/date.js"></script>
     <!-- Custom Theme Scripts -->
<? for($v=1;$v<=12;$v++)
{ 
$number=0;
 $date=date('Y-'.str_pad($v, 2, '0', STR_PAD_LEFT));
 $dresultsx = @mysqli_query($conn,$fg="SELECT COUNT(logid) AS number  FROM loans WHERE approvedstatus='Approved' and trans_date like '$date%'");
 while ($row = @mysqli_fetch_array($dresultsx)) 
	 	{ $number=$row['number']; }
		if($number=='') { $number=0; }
  $chartdata1.='['.$v.','.($number).'],'; 
}
  $chartdata1="[".$chartdata1."]";
 
 //sparkline
 $start = date('Y-m-d', strtotime(date('Y-m-d'). ' - 1 week'));
 
  $dresultsx = @mysqli_query($conn,$fg="SELECT COUNT(logid) AS number  FROM loans WHERE trans_date>='$start' and approvedstatus='Approved' GROUP BY LEFT(trans_date , 10)"); 
 while ($row = @mysqli_fetch_array($dresultsx)) 
	 	{ 
		$sparklinedata.=$row['number'].","; 
		}
		
		
		
/// doughnut
$dresults = @mysqli_query($conn,"SELECT * FROM loans group by facilitype");
	 while ($row = @mysqli_fetch_array($dresults)) 
	 	{ 
		if($row['facilitype']==1)
		{ $reason="NHIF"; } else {  $reason="Afya Credit"; }
		$douhglabel.='"'.$reason.'",';
		$dresultsx = @mysqli_query($conn,"SELECT count(logid) AS av_sales FROM loans WHERE approvedstatus='Approved'  AND facilitype='".$row['facilitype']."'");
	 while ($drow = @mysqli_fetch_array($dresultsx)) 
	 	{ $doughvalues.=$drow['av_sales'].",";  }
		}
	 
 ?> 
<? include("build/js/custom.min.php");?>  
<!--<script src="build/js/custom.min.js"></script> -->
<?
if($dontsort==1)
{?><script>
 
  $('#datatable').dataTable( {
order: []
});
</script><? } ?>
