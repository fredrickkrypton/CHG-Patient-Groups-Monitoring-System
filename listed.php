 <? include('server.php');
 $page="activeloan";
 $conn=$_SESSION['conn'];
$timestamp=date('Y-m-d H:i:s');

 caniacess($page."_view");
  
if($_SESSION['stop']==1)
{  ?><div align="center"><? include('stop.php');?></div><? }
else 
{ 
  echo '
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- jQuery custom content scroller -->
    <link href="vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css" rel="stylesheet"/>

<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <!-- bootstrap-datetimepicker -->
    <link href="vendors/bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css" rel="stylesheet">
	
	  <link href="vendors/normalize-css/normalize.css" rel="stylesheet">
    <link href="vendors/ion.rangeSlider/css/ion.rangeSlider.css" rel="stylesheet">
    <link href="vendors/ion.rangeSlider/css/ion.rangeSlider.skinFlat.css" rel="stylesheet">
    <!-- Bootstrap Colorpicker -->
    <link href="vendors/mjolnic-bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css" rel="stylesheet">
     
	 <!-- bootstrap-wysiwyg -->
	<link href="vendors/google-code-prettify/bin/prettify.min.css" rel="stylesheet">
	
	 <!-- PNotify -->
    <link href="vendors/pnotify/dist/pnotify.css" rel="stylesheet">
    <link href="vendors/pnotify/dist/pnotify.buttons.css" rel="stylesheet">
    <link href="vendors/pnotify/dist/pnotify.nonblock.css" rel="stylesheet">
	
	 
	
    <!-- Custom Theme Style -->
	 <link href="vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    
	 	<link href="build/css/custom.min.css" rel="stylesheet">
	<link href="ccss.css" rel="stylesheet">
	';?>
	<script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>

 <link rel="stylesheet" type="text/css" media="all" href="daterangepicker.css" />
<style>
body {
    color: black;
    background: white !important;
    font-family: "Helvetica Neue", Roboto, Arial, "Droid Sans", sans-serif;
    font-size: 13px;
    font-weight: 400;
    line-height: 1.471;
}
div.dataTables_wrapper div.dataTables_info {
    padding-top: 8px;
    white-space: nowrap;
}
.dataTables_info {
    width: 60%;
    float: left;
}
div.dataTables_wrapper div.dataTables_paginate {
    margin: 0;
    white-space: nowrap;
    text-align: right;
}
</style>
<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>-->

<script src="vendors/jquery/dist/jquery.min.js"></script>

<table id="example" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered  table-sm table-responsive"><thead>
<tr>
<th>Active / Repaid status</th>
    <th>Client ID</th>
    <th>Full Names</th>
    <th>ID No.</th>
    <th>Phone No.</th>
    <th>Loan ID</th>
    <th>Loan Type</th>
    <th>Manual Loan Status</th>
    <th>Principal</th>
    <th>Date requested</th>
    <th> tenor</th>
    <th>Maturity date</th>
    <th>Due </th>
    <th>Paid</th>
    <th>Last Payament date</th>
    <th>Balalance</th>
    </tr>
</thead>
<tfoot>
</tfoot>
<tbody>
<? $mycount=0;
 $ligids= rtrim($_GET['id'], ',');
 if($ligids)
 {
$result = mysqli_query($conn,"SELECT * FROM loans where logid in ($ligids)   order by trans_date ");
 
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 				  
<tr>
<td><? if($row['repaid']=="No") { echo "Active"; } else { echo "Repaid"; } ?></td>
<td style="white-space:nowrap"><? echo $row['userid']?></td>
<td ><a href="index.php?id=<? echo $row['userid']?>/clientsheet"><?
	$nationalid=$phone='';
	$results = mysqli_query($conn,"SELECT CONCAT(firstname,' ',lastname) AS fullnames,nationalid,phone FROM users where logid='".$row['userid']."'");
   while($rows = mysqli_fetch_assoc($results))
    { echo $rows['fullnames']; $nationalid=$rows['nationalid']; $phone=$rows['phone'];  } ?></a></td>
<td ><? echo $nationalid?></td>
<td ><? echo $phone?></td>
<td><? echo $row['logid']?></td>
<td>
  <? if($row['facilitype']==1) { echo "NHIF"; } else { echo "Treatment"; } ?>
</td>
<td style="white-space:nowrap"><? echo $row['manualstatus']?></td>
<td align="right"><? echo number_format($row['loanamount']); $grandprinc+=$row['loanamount'];?></td>
<td><? echo dateto_normal($row['trans_date'])?></td>
<td   align="center" class="table table-striped table-bordered dataTable table-sm"><? echo $row['repayperiod']?></td>
<td   align="center" class="table table-striped table-bordered dataTable table-sm"><? echo dateto_normal($row['due_date'])?></td>
<td   align="right" class="table table-striped table-bordered dataTable table-sm"><? echo number_format($row['dueamount']);  $granddueamount+=$row['dueamount'];?></td>
<td   align="center" class="table table-striped table-bordered dataTable table-sm">
<? $paid=$dated='';
$resultsz = mysqli_query($conn,"SELECT sum(amount) as paid,max(date) as dated FROM repayments where loanid='".$row['logid']."'");
   while($rows = mysqli_fetch_assoc($resultsz))
    { echo number_format($paid=$rows['paid'],2); $dated=$rows['dated'];  } ?></td>
<td   align="center" class="table table-striped table-bordered dataTable table-sm"><? if($dated) { echo dateto_normal($dated); }?></td>
<td   align="center" class="table table-striped table-bordered dataTable table-sm"><? echo number_format($row['dueamount']-$paid);  ?></td>
</tr>
<? }
 }?>


</tbody>
</table>
<script>
$(document).ready(function() {
    $('#example').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    } );
} );
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
<? } ?>