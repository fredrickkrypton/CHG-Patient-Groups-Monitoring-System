<style>
.space {
 margin-top:5px;
     
}
.table-sm {
	font-size:12px;
 	
}
.table-sm td, .table-sm th {
    padding: .2rem;
	font-size:12px;
}
.table-responsive {
     
    display: flow-root;
 
}
.table {
    width: 100%;
    margin-bottom: 3rem;
	background-color:#FFF;
	padding-left: 5px;
   
}
.font15 {
	background-color:#979590;
	font-size:16px !important;
	color:#FFF;
	margin:2px;	
}
	</style><?
/*
Copyright (C) Innovee Systems Erps.This program should NOT be distributed  WITHOUT ANY WARRANTY and LICENSE.
*/
//-----------------------------------------------------------------------------
//
//	Coder: Timothy Murunga
//	Email: murunga@innovee.co.ke
//	Change: 2021-04-01 12:38:33
 
 
//include('header.php');
$page="member_profile";
workout($_GET['D']);
$timestamp=date('Y-m-d H:i:s');

if($part=='') { caniacess($page."_view"); } 

if($_SESSION['stop']==1)
{  include('stop.php'); }
else 
{
 
 //---------------------------------------------------------------------------------------------------- 
 _title($title='Member Profile'); ?>
<? } 
$result = mysqli_query($conn,"SELECT * FROM member where logid='".$_GET['id']."'");
 
  while($r = mysqli_fetch_assoc($result))
    {?>
    
    <div class="table-responsive fixed-table-body">
<table width="100%" border="0" class="table table-responsive table-sm" style="margin-bottom: 0rem;">
   <tr>
     <td  valign="top"><table cellspacing="0" cellpadding="0" class="table table-hover table-responsive table-sm">
        
       <tr >
         <td colspan="3" class="font15">Client Profile</td>
         </tr>
       <tr>
         <td width="188"><strong>Client ID</strong></td>
         <td><? echo $r['logid']?></td>
         <td width="127">&nbsp;</td>
       </tr>
       <tr>
         <td><strong> Name</strong></td>
         <td><? echo $r['name']?></td>
         <td>&nbsp;</td>
       </tr>
       <tr>
         <td><strong>Gender</strong></td>
         <td><? echo $r['gender']?></td>
         <td>&nbsp;</td>
       </tr>
       <tr>
         <td><strong>Phone no</strong></td>
         <td><? echo $r['phone_no']?></td>
         <td>&nbsp;</td>
       </tr>
       <tr>
         <td><strong>National ID</strong></td>
         <td><? echo $r['nat_id']?></td>
         <td>&nbsp;</td>
       </tr>
     </table></td>
     <td width="50%" align="right" valign="top" class="table-center">
     <div align="center">
     <table cellspacing="0" cellpadding="0" class="table table-hover table-responsive table-sm">
        
       <tr>
          <td colspan="2" class="font15"> <div align="left">Current Month Status </div></td>
       </tr>
       <tr>
         <td width="153"><strong>This months Contribution</strong></td>
         <td width="152"><? $from=date('Y-m-01'); $to=date('Y-m-t');
		 $results = mysqli_query($conn,"SELECT COUNT(logid) AS deposits,SUM(amount) AS amount FROM mpesa WHERE memberid=".$_GET['id']."
		 and transactiondate>='$from' AND transactiondate<='$to' GROUP BY memberid");
  $ActiveLoans=0;
  while($rows = mysqli_fetch_assoc($results))
    {
 	$amount=$rows['amount'];
	$deposits=$rows['deposits'];
	 }
	 echo number_format($amount,2);?></td>
         </tr>
       <tr>
         <td><strong>Number of Deposits </strong></td>
         <td><?=$deposits?></td>
         </tr>
       <tr>
         <td><strong>Status</strong></td>
         <td><? if($r['active']=='Yes') { echo "Active"; } else { echo "InActive";  }?></td>
         </tr>
     </table></div></td>
   </tr>
   
    <tr><td colspan="2">&nbsp; </td></tr>
  <tr>
    <td>
    <div align="center">
    <table cellspacing="0" cellpadding="0" class="table table-hover table-responsive table-sm table-condensed">
   
   <tr>
     <td colspan="4"  class="font15"> <div align="left">Deposit History</div></td>
    </tr>
   <tr>
     <td colspan="4">&nbsp;</td>
    </tr>
   <tr>
     <td><strong>Transaction Date</strong></td>
     <td><strong>Reference</strong></td>
     <td><strong>Transaction Type</strong></td>
     <td><div align="right"><strong>Amount</strong></div></td>
     </tr>
 <? $mycount=0;
  
$result = mysqli_query($conn,"SELECT * FROM mpesa where memberid='".$r['logid']."'   order by transactiondate asc");
 
  while($rrow = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?>   <tr>
   <td><? echo $rrow['transactiondate']?></td>
   <td><? echo $rrow['mpesareceiptnumber']?></td>
   <td><? if($rrow['ctype']==0) { echo "Registration"; } else { echo "Contribution";  } ?></td>
   <td><div align="right"><? echo number_format($rrow['amount'],2); $grand+=$rrow['amount'];?></div></td>
   </tr>
   <? } ?>
   <tr>
   <td colspan="3" align="right"><strong>Total Deposits</strong></td>
   <td><div align="right"><? echo number_format($grand,2);?></div></td>
   </tr>
 </table></div></td>
    <td valign="top">
    <div align="center">
    <table cellspacing="0" cellpadding="0" class="table table-hover table-responsive table-sm table-condensed">
    
    <tr>
     <td colspan="5"  class="font15"><div align="left">Prescription History</div></td>
    </tr>
   <tr>
     <td><strong>Transaction Code</strong></td>
     <td><strong>Loan ID</strong></td>
     <td><strong>Client ID</strong></td>
     <td><strong>Payment date</strong></td>
     <td><strong>By /Time</strong></td>
   </tr>
   <? $mycount=0;
  
$result = mysqli_query($conn,$fg="SELECT trans_date,`medicine`.name,medicine.`molecule_type`,`unit_of_measure`.`name` as uom,quantity,`stock_transaction`.`created_by`,`stock_transaction`.`created_on`
FROM medicine,unit_of_measure,`stock_transaction`
WHERE stock_transaction.`medicine_id`=medicine.`logid` AND medicine.`unit_of_measure_id`=unit_of_measure.`logid` 
and recipient_id='".$r['logid']."' and trans_type='To_patient' order by trans_date desc");
  $fg;
  while($row = mysqli_fetch_assoc($result))
    { $mycount=$mycount+1; ?> 
    <tr>
     <td><? echo $row['trans_date']?></td>
     <td><? echo $row['name']?></td>
     <td><? echo $row['uom']?></td>
     <td><? echo ($row['quantity'])?></td>
     <td align="right"><? echo ($row['created_by']." - ".$row['created_on'])?></td>
   </tr>
   <? } ?>
 </table>
      </div></td>
  </tr>
</table>
 

 
 <? } ?>
    </div>
</div> 