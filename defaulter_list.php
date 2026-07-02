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
$page="defaulter_list";
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
 
function mycontribution($date,$memberid)
{ global $conn;
$qresult = mysqli_query($conn,$vf="SELECT sum(amount) as amount FROM mpesa where memberid='$memberid' and transactiondate like '$date%'");   $vf;
  while($drow = mysqli_fetch_assoc($qresult))
    {
	$amount=$drow['amount'];	
	}
	return $amount;
	
}
 //---------------------------------------------------------------------------------------------------- 
 _title($title='Defaulter List'); ?> <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>

 <link rel="stylesheet" type="text/css" media="all" href="daterangepicker.css" />

<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>-->

<script src="vendors/jquery/dist/jquery.min.js"></script>
      
    
          
<form method='POST' action='' class='rounded' name='modeoftravel' style='min-width:70%'  enctype='multipart/form-data'>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel"><h2><?=ucwords($title);?></h2>
  <div class="x_title"><table width="100%" border="0">
  <tr> 
    <td width="70%">   </td>
    <td width="15%"><select class="form-control manda" id="group_id" name="group_id" style="margin-top:-13px;">
<? if($_POST['group_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group where logid='".$_POST['group_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $logid=$drow['logid']?>"><? echo $drow['name']?></option><? }
 } ?>

 <?
if($_SESSION['userrole']==4)
{
	$group_id=" where logid='".$_SESSION['groupid']."' ";
}
else {
	$group_id=" ";
	?>
     <option value="">All Groups</option>
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
<td><input type="submit" name="tbn_search" id="tbn_search" value="View" class="btn btn-secondary btn-sm"/></td>
  </tr>
</table>

                  
					<div class="clearfix"></div>
  </div>
<div class="x_content" id="datepicker1">
<table id="example" style="width: 100%;" role="grid" aria-describedby="datatable_info" class="table table-striped table-bordered  table-sm table-responsive"><thead>
<tr>
<th> Names</th>
<th colspan="2">Group Leader</th>
</tr>
</thead>
<tfoot>
</tfoot>
<tbody>
<? $mycount=0;
 if(isset($_POST['tbn_search']))
{
$from = dateto_mysql($_POST['start_date']). "  23:59:59";
$to =dateto_mysql($_POST['end_date']). " 23:59:59";
$search=$_POST['search'];
if($_POST['group_id'])
{
$groupid=" and logid=".$_POST['group_id'];	
}
 
 $result = mysqli_query($conn,"SELECT * FROM provider_group where logid>0 $groupid");
}
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 
    
     				  
<tr>
<td style="white-space:nowrap"><strong><? echo $row['name']?></strong></td>
<td colspan="2" style="white-space:nowrap"><? 
$querya = "SELECT `name` FROM `member` WHERE group_id='".$row['logid']."' AND isleader=1";
$querya = @mysqli_query($conn, $querya);
while($srow = @mysqli_fetch_assoc($querya))
    {  echo $srow['name'].',';  }


?></td>
</tr>

<tr>
<td colspan="3"> <table width="100%" border="0">
    <tr>
      <td><strong>Member No</strong></td>
      <td><strong>Name</strong></td>
      <td><strong>Phone</strong></td>
      <td width="10%" align="right"><strong>Contributed</strong></td>
      <td width="10%" align="right"><strong>Due</strong></td>
      </tr>
<?
 $Mquery = @mysqli_query($conn,"SELECT logid,`name`,mnumber,phone_no,activation_dt FROM `member` WHERE group_id='".$row['logid']."'");
while($mrow = @mysqli_fetch_assoc($Mquery))
    {    
	
//$date1 = date('Y-m',strtotime($mrow['activation_dt']));
  $date1=$date2 = date('Y-m',strtotime($from));
$months = 0;
$mymnths=$contributed=0;
for($i = $date1; $i <= $date2; $i++){
   
	$mymnths+=500;
	
	$contributed+=mycontribution($i,$mrow['logid']);
	
}

 if(($mymnths-$contributed)>=500)
{ 
  
		?>
        <tr style="background-color:#FFF">
      <td>&nbsp;<?=$mrow['mnumber']?></td>
      <td>&nbsp;<?=$mrow['name']?></td>
      <td>&nbsp;<?=$mrow['phone_no']?></td>
      <td  align="right"><?
	 
echo number_format($contributed,2);
	  ?></td>
      <td  align="right"><?
	 
echo number_format(500,2);
$ya500+=500;
 
	  ?></td>
        </tr>
    <? }} ?>
    
    <tr>
    <td colspan="3" align="right"><strong>Total</strong></td>
    <td  align="right">&nbsp;</td>
    <td  align="right"><strong>
      <?
	 
echo number_format($ya500,2);
 $grandt+=$ya500;
 $ya500=0;
	  ?>
    </strong></td>
    </tr>
  </table></td>


</tr>

<? } ?>
<tr>
  <td style="color:white">z</td>
  <td  align="right"><strong>Grand Total</strong></td>
  <td  width="10%"  align="right"><strong>
    <?
	 
echo number_format($grandt,2);
 
	  ?>
  </strong></td>
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