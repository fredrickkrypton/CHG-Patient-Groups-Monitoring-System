<? include("server.php");?>
<!DOCTYPE html>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  
    <!-- Bootstrap -->
  <? echo '
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
	
   <script>
  function js_validate(obj,fieldtype) {
var  element = document.getElementById(obj.id).value;

 var errors='';
 if(fieldtype=='numeric')
{
	  var numbers = /^[0-9.]*$/; 
	  if(element<1)
	  {
		  element=1;
	  }
      if(element.match(numbers))  
      {  
        return true;  
      }  
       else  if(element!='')
      {  
       alert('This Field must have numeric characters only.');
		  document.getElementById(obj.id).select();
		  window.setTimeout(function ()
		  {  document.getElementById(obj.id).focus(); 
		  document.getElementById(obj.id).value='';
		  document.getElementById(obj.id).select(); 
		  $('#div_' + obj.id)[0].scrollIntoView( true );
		   },0); return false;
					
					 
     var errors=1;
      
      } 
    element='';   
}
if(fieldtype=='numeric')
{
	  var numbers = /^[0-9.]*$/; 
	  if(element<1)
	  {
		  element=1;
	  }
      if(element.match(numbers))  
      {  
        return true;  
      }  
       else  if(element!='')
      {  
       alert('This Field must have numeric characters only');
		  document.getElementById(obj.id).select();
		  window.setTimeout(function ()
		  {  document.getElementById(obj.id).focus(); 
		  document.getElementById(obj.id).select(); 
		  document.getElementById(obj.id).value='';
		  $('#div_' + obj.id)[0].scrollIntoView( true );
		   },0); return false;
					
					 
     var errors=1;
      
      } 
    element='';   
}
if(fieldtype=='phoneno' && errors=='')
{
	     
    
      var phoneno =  /^\+(?:[0-9] ?){6,14}[0-9]$/;   
      if(element.match(phoneno))  
      {  
        return true;  
      }  
     
      else  if(element!='')
      {  
        alert('Error Phone Number format +(Country Code) and Number');  
		   document.getElementById(obj.id).select();
		  window.setTimeout(function ()
		  {  document.getElementById(obj.id).focus(); 
		  document.getElementById(obj.id).select(); 
		  $('#div_' + obj.id)[0].scrollIntoView( true );
		   },0); return false;
					
					 
     var errors=1;
      
      } 
       
      
}
 if(fieldtype=='alphabets' && errors=='')
{
	    
      var letters = /^[a-zA-Z\s]*$/;  
      if(element.match(letters))  
      {  
       return true;  
      }  
      else  if(element!='')
      {   
      alert('This Field must have alphabet characters only');  
 		  document.getElementById(obj.id).select();
		  window.setTimeout(function ()
		  {  document.getElementById(obj.id).focus(); 
		  document.getElementById(obj.id).select(); 
		  $('#div_' + obj.id)[0].scrollIntoView( true );
		   },0); return false ;
					 
     var errors=1;
      
      } 
}
  
   if(fieldtype=='email' && errors=='')
{
	   
     
      var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;  
      if(element.match(mailformat))  
      {  
        return true;  
      }  
      else  if(element!='')
      {  
      alert("You have entered an invalid email address!");  
        document.getElementById(obj.id).select();
		  window.setTimeout(function ()
		  {  document.getElementById(obj.id).focus(); 
		  document.getElementById(obj.id).select(); 
		  $('#div_' + obj.id)[0].scrollIntoView( true );
		   },0); return false;
					
		  var errors=1;
	  }
       
}
 
}
 
   </script>
 
    <!-- Ion.RangeSlider -->
  
 