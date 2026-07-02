<? include("server.php");
 $data = json_decode(file_get_contents('php://input'), true);
$clientid= $data['clientid'];?>
<select class="form-control manda" id="loanid" name="loanid"  required='required' onchange="submit()">
<option value="">Select Loanid</option><?
$qresult = mysqli_query($conn,"SELECT * FROM loans where userid='$clientid' and disbursed='Disbursed' and repaid='No'");
  while($drow = mysqli_fetch_assoc($qresult))
    { ?><option value="<? echo $drow['logid']?>"><? echo $drow['logid']?></option>
<? } ?>
</select>