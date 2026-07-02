<?
_title($title='repayments list');

$page="repaymentlist";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
  
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
?>
  <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>

 <link rel="stylesheet" type="text/css" media="all" href="daterangepicker.css" />

<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>-->

<script src="vendors/jquery/dist/jquery.min.js"></script>
      
    
          
<form method='POST' action='' class='rounded' name='modeoftravel' style='min-width:70%'  enctype='multipart/form-data'>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
  <div class="x_title"> <table width="100%" border="0">
  <tr> 
    <td>  <h2><?=ucwords($title);?></h2> </td>
    <td width="15%"> 
 
 <div class="input-group date">
<input type="text" class="form-control form-control-sm manda" value="<? echo $_POST['start_date']?>" name="start_date" id="start_date" > <span class="input-group-addon">
                 <span class="fa fa-calendar"></span>
                   </span>
</div>
</td>
<td width="15%"> 
<div class="input-group date">
<input type="text" class="form-control form-control-sm manda" value="<? echo $_POST['end_date']?>" name="end_date" id="end_date" > <span class="input-group-addon">
                 <span class="fa fa-calendar"></span>
                   </span>
</div>
     
 </td>
  </tr>
</table> 

                  
	<div class="clearfix"></div>
  </div>
<div class="x_content" id="datepicker1">
<table id="example" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered  table-sm"><thead>
<tr>
  <th>#</th>
  <th>Date</th>
<th>Client ID</th>
    <th>Full Names</th>
    <th>ID</th>
    <th>Phone</th>
    <th>Payment Reference</th>
    <th>Repaid Amount</th>
    </tr>
</thead>
<tfoot>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$from = dateto_mysql($_POST['start_date']);
$to =dateto_mysql($_POST['end_date']);
$search=$_POST['search'];
$result = mysqli_query($conn,$fg="SELECT * FROM repayments  where date>='$from' and date<='$to' and (phone like '%$search%' OR ref like '%$search%' OR nationalid like '%$search%') order by date");   $fg;
 }
else {
$result = mysqli_query($conn,"SELECT * FROM repayments   order by date limit 10 ");
}
 
 
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 
    <tr>
     <td><? echo $mycount?></td>
     <td><? echo dateto_normal($row['date'])?></td>
     <td><a href="index.php?id=<? echo $row['userid']?>/clientsheet"><? echo $row['userid']?></a></td>
     <td> <?
	$nationalid=$phone='';
	$results = mysqli_query($conn,"SELECT CONCAT(firstname,' ',lastname) AS fullnames,nationalid,phone FROM users where logid='".$row['userid']."'");
   while($rows = mysqli_fetch_assoc($results))
    { echo $rows['fullnames'];} ?></td>
     <td><? echo $row['nationalid']?></td>
     <td><? echo $row['phone']?></td>
     <td><? echo ($row['ref'])?></td>
     <td align="right"><? echo number_format($row['amount']); $grand+=$row['amount'];?></td>
     </tr>
   <? } ?>
<tr>
  <td align="right" style="color:white"><? echo $mycount+1; ?></td>
  <td align="right">&nbsp;</td>
  <td align="right">&nbsp;</td>
  <td align="right">&nbsp;</td>
  <td align="right">&nbsp;</td>
  <td align="right">&nbsp;</td>
  <td align="right">&nbsp;</td>
  <td align="right"><? echo number_format($grand)?></td>
  </tr>


</tbody>
</table>
</div>
 
</form>
 <script>
$(document).ready(function() {
	
	$('#start_date').daterangepicker({
         locale: {
      format: 'DD-MM-YYYY'
    },
	singleDatePicker: true,
		   

        });
		
		
		$('#end_date').daterangepicker({
         locale: {
      format: 'DD-MM-YYYY'
    },
	singleDatePicker: true,
		   

        });
		
   
   

} );

$(document).ready(function() {
    $('#example').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    } );
} );
</script>
<style>
.btn-group .btn {
    margin-bottom: 0;
    margin-right: 0;
    font-size: 12px;
}
</style>

<? } ?>