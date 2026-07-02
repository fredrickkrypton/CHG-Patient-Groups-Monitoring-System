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
$page="member_statement";
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
 _title($title='Member Contribution Summary'); ?> <script src="vendors/moment/min/moment.min.js"></script>
    <script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>

 <link rel="stylesheet" type="text/css" media="all" href="daterangepicker.css" />

<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>-->

<script src="vendors/jquery/dist/jquery.min.js"></script>
      
<style>
@media print {
  body {
    visibility: hidden;
  }
  #section-to-print {
    visibility: visible;
    position: absolute;
    left: 0;
    top: 0;
  }
}
</style>
          
<form method='POST' action='' class='rounded' name='modeoftravel' style='min-width:70%'  enctype='multipart/form-data'>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel">
  <div class="x_title"> <h2><?=ucwords($title);?></h2> <br /><table width="100%" border="0">
  <tr> 
    <td width="25%"> </td>
    <td align="right" width="15%"><select class="form-control manda" id="group_id" name="group_id" style="margin-top:-13px;" onchange="submit()" required>
      <? if($_POST['group_id'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM provider_group where logid='".$_POST['group_id']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?>
      <option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
      <? }
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
    { ?>
      <option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
      <? } ?>
    </select></td>
    <td width="15%">
<select class="form-control manda" id="member" name="member" style="margin-top:-13px;" required>
<? if($_POST['member'])
{ $qresult = mysqli_query($conn,"SELECT logid,name FROM member where logid='".$_POST['member']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option><? }
 } ?>

  
     <option value="">Select Member</option>
     <?
 
$qresult = mysqli_query($conn,$fg="SELECT logid,name FROM member where  group_id='".$_POST['group_id']."' ORDER BY name");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['name']?></option>
<? } ?>
</select>


</td>
    <!--<td width="15%"> 
 
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
     
 </td>-->
<td><input type="submit" name="button" id="button" value="View" class="btn btn-secondary btn-sm"/></td>
  </tr>
</table>

                  
					<div class="clearfix"></div>
  </div>
<div class="x_content" id="datepicker1">
<div align="center" id="printableArea">
<table   width="100%" style="width:100%"  align="center"   class="table table-striped table-bordered  table-sm table-responsive"><thead>
<tr>
  <th>Name</th>
  <th style="background-color:#FFF">&nbsp;
  <?
  $qresult = mysqli_query($conn,"SELECT member.name,member.phone_no,member.nat_id,member.activation_dt,provider_group.name AS groupname
FROM member,provider_group WHERE member.group_id=provider_group.logid
AND member.logid='".$_POST['member']."'");
  while($drow = mysqli_fetch_assoc($qresult))
    {
	echo strtoupper($drow['name']);
	$phone=($drow['phone_no']);
	$groupname=strtoupper($drow['groupname']);
	$actdate=($drow['activation_dt']);	
	}
	?>
    </th>
</tr>
<tr>
  <th>Phone</th>
  <th style="background-color:#FFF"><?=$phone?></th>
</tr>
<tr>
  <th>Group</th>
  <th style="background-color:#FFF"><?=$groupname?></th>
</tr>
<tr>
  <th>Activation Date</th>
  <th style="background-color:#FFF"><?=$actdate?></th>
</tr>
</thead>
<tr>
  <td colspan="2"><table width="100%" border="0">
      <tr>
        <td><strong>Transaction DAte</strong></td>
        <td><strong>Reference</strong></td>
        <td><strong>Amount</strong></td>
      </tr>
    <? 
  $qresultA = mysqli_query($conn,$df="SELECT * FROM mpesa where memberid='".$_POST['member']."'");
    $df;
  while($drw = mysqli_fetch_assoc($qresultA))
    {?>  <tr>
        <td>&nbsp;<?=$drw['transactiondate']?></td>
        <td><?=$drw['mpesareceiptnumber']?></td>
        <td align="right"><?=number_format($drw['amount'],2);$total+=$drw['amount'];?></td>
      </tr>
    <? } ?>
    <tr>
    <td colspan="2" align="right"><strong>Total</strong></td>
    
    <td  align="right"><strong>
      <?=number_format($total,2);?>
    </strong></td>
    
    </tr>  
    </table></td>
  </tr>

<tfoot>
</tfoot>
<tbody>


</tbody>
</table>
</div>
</div>
 <span onclick="printDiv('printableArea')">print</span>
</form>
 <script>
 function printDiv(divName) {
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = printContents;

     window.print();

     document.body.innerHTML = originalContents;
}
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