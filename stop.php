<? _title("Access"); /*$now = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
setcookie("back", $now);
if (isset($_COOKIE['back'])) 
{
echo "<a href='".$_COOKIE['back']."' target='_blank'>GO BACK</a>";
}*/ ?>
 


<div class="col-md-12">
          <div class="col-middle">
            <div class="text-center text-center">
              <h1 class="error-number text-danger">403</h1>
              <h2>Access denied</h2>
              <p>Full authentication is required to access this resource. <p>
              <a href="#" onClick="history.go(-1)" class="link"><i>Click here to go back</i></a>
              </p>
               
              </div>
            </div>
          </div>
 