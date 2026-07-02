<?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-08-20 08:33:37
 
 
//include('header.php');
$page="group_statement";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); }
if($part=='1') { caniacess($page."_add"); }
if($part=='2') { caniacess($page."_edit"); }
if(isset($_POST['0_users_delete'])) { caniacess($page."_delete"); }
if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
//$page="0_users";
 
 //---------------------------------------------------------------------------------------------------- 
 _title($title='Group statement'); ?> <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>

 <link rel="stylesheet" type="text/css" media="all" href="daterangepicker.css" />

<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>-->

<script src="vendors/jquery/dist/jquery.min.js"></script>
      
    
          
<form method='POST' action='' class='rounded' name='modeoftravel' style='min-width:70%'  enctype='multipart/form-data'>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
  <div class="x_title"><table width="100%" border="0">
  <tr> 
    <td>  <h2><?=ucwords($title);?></h2> </td>
    <td width="15%"><select class="form-control manda" id="group_id" name="group_id" style="margin-top:-13px;">
<? if($_POST['group_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group where logid='".$_POST['group_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } ?>

 <?
if($_SESSION['userrole']==4)
{
	$group_id=" where logid='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" ";
	?>
     <option value="">Select Group</option>
     <?
}
$qresult = mysqli_query($conn,$fg="SELECT logid,name FROM provider_group $group_id ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select></td>
    <td width="15%"> 
 
 <div class="input-group datex">
   <input type="text" class="form-control form-control-sm manda" value="<? echo $_POST['start_date']?>" name="start_date" id="start_date" > <span class="input-group-addon">
                 <span class="fa fa-calendar">
                   
</div>
</td>
<td width="15%"> 
<div class="input-group date">
<input type="text" class="form-control form-control-sm manda" value="<? echo $_POST['end_date']?>" name="end_date" id="end_date" > <span class="input-group-addon">
                 <span class="fa fa-calendar">
                   
</div>
     
 </td>
  </tr>
</table>

                  
					<div class="clearfix"></div>
  </div>
<div class="x_content" id="datepicker1">
<table id="example" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered  table-sm table-responsive"><thead>
<tr>
<th> Names</th>
<th>Group Leader</th>
<th><div align="right">Registration</div></th>
<th><div align="right">Contribution</div></th>
</tr>
</thead>
<tfoot>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$from = dateto_mysql($_POST['start_date']). " 00:00:00";
$to =dateto_mysql($_POST['end_date']). " 23:59:59";
$search=$_POST['search'];
if($_POST['group_id'])
{
$groupid=" and groupid=".$_POST['group_id'];	
}
$result = mysqli_query($conn,$fg="SELECT groupid,`name`, 
SUM(CASE 
    WHEN ctype = '0'
    THEN amount 
    ELSE 0 
END) AS registration,
SUM(CASE 
    WHEN ctype = '1' 
    THEN amount 
    ELSE 0 
END) AS contribution 
FROM provider_group,mpesa 
WHERE provider_group.`logid`=mpesa.`groupid` AND transactiondate>='$from' AND transactiondate<='$to' $groupid GROUP BY  groupid");
//echo $fg;
 }
else {
 $result = mysqli_query($conn,"SELECT * FROM provider_group  where logid<0");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 
    
     				  
<tr>
<td style="white-space:nowrap"><? echo $row['name']?></td>
<td style="white-space:nowrap"> <? 
$querya = "SELECT `name` FROM `member` WHERE group_id='".$row['groupid']."' AND isleader=1";
$querya = @mysqli_query($conn, $querya);
while($srow = @mysqli_fetch_assoc($querya))
    {  echo $srow['name'];  }


?> </td>
<td style="white-space:nowrap"><div align="right"><? echo number_format($row['registration'],2); $grna+=$row['registration'];?></div></td>
<td style="white-space:nowrap"><div align="right"><? echo number_format($row['contribution'],2);$grnb+=$row['contribution'];?></div></td>
</tr>

<? } ?>
<tr>
  <td style="color:white">z</td>
  <td>Total</td>
  <td><div align="right"><? echo number_format($grna,2); ?></div></td>
  <td><div align="right"><? echo number_format($grnb,2); ?></div></td>
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

function closeloan(id)
{
 
const params = {
            loanid: id,
			repaid: document.getElementById('repay_repaid'+id).value,
			repaiddate:document.getElementById('repay_date'+id).value,
			repaidby:"<?=$_SESSION['userfullnames']?>"	 
            
        }

        const http = new XMLHttpRequest()
        http.open('POST', 'activeloan_update.php')
        http.setRequestHeader('Content-type', 'application/json')
        http.send(JSON.stringify(params)) // Make sure to stringify
        http.onload = function() {
        
		alert(http.responseText);
   window.location = 'index.php?/activeloan';
        }
 
}
</script>
<style>
.btn-group .btn {
    margin-bottom: 0;
    margin-right: 0;
    font-size: 12px;
}
</style>
<input name="txtcount" type="hidden" value="<? echo $mycount?>">
<? } ?>