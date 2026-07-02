<style>

body {

  height: 100%;
  font-family: 'Ubuntu', sans-serif;
  background: linear-gradient(270deg, #82c3fe, #2e74b4);
background-size: 400% 400%;

-webkit-animation: AnimationName 59s ease infinite;
-moz-animation: AnimationName 59s ease infinite;
animation: AnimationName 59s ease infinite;
  
  
}




@-webkit-keyframes AnimationName {
    0%{background-position:0% 50%}
    50%{background-position:100% 50%}
    100%{background-position:0% 50%}
}
@-moz-keyframes AnimationName {
    0%{background-position:0% 50%}
    50%{background-position:100% 50%}
    100%{background-position:0% 50%}
}
@keyframes AnimationName { 
    0%{background-position:0% 50%}
    50%{background-position:100% 50%}
    100%{background-position:0% 50%}
}

.smart {
	
  margin-left:auto;
  margin-right:auto;
  margin-top: 100px;

  border-color: #444;
	background-color: #333;



	width: 295px;
	height: 532px;
	border-radius: 40px;
	border: 2px solid #ddd;
	margin: 0 auto;
	padding: 60px 10px;
	
	position: relative;


}

#acenzione-button {
	background-color: #333;
	
	height: 20px;
	left: -5px;
	top: 59px;
	border-radius: 4px 0 0 4px;

	width: 3px;
	background: #333;
	position: absolute;
	
}


#volume-su {
	height: 40px;
	width: 3px;
	left: -7px;
	top: 110px;
	border-radius: 4px 0 0 4px;

	width: 5px;
	background: #333;
	position: absolute;
	
	
}

#volume-giu {
	height: 40px;
	width: 3px;
	left: -7px;
	top: 155px;
	border-radius: 4px 0 0 4px;

	width: 5px;
	background: #333;
	position: absolute;
	


}


#dettaglio {
	position: absolute;
	left: 0;
	top: 17px;
	width: 100%;
}


#fotocamera {
	background: #111;
	height: 7px;
	width: 7px;
	margin: auto;

	border-radius: 100px;
	display: block;
}

#sensore {
	background: #111;
	height: 7px;
	width: 7px;
	margin: 12px 100px;

	border-radius: 100px;
	display: block;
}

#microfono {
	background: #111;
	height: 7px;
	width: 90px;
	margin: -19px auto 0;
	border-radius: 100px;
}


#schermo {
	height: 522px;
	width: 295px;
	background-color: #fff;
	box-shadow: 0 0 0 1px    rgba(0,0,0,0.1)inset;
	overflow: hidden;
	position: relative;
	
}


#bottone {
	height: 40px;
	width: 40px;
	background: #111;
	margin: 17px auto;
	border-radius: 100px;
}



.frame-posizione{
	
	margin-left: 0px;
	margin-right: -1px;
	display: block;
}



#header {
	background: #555;
	box-shadow: 1px 2px 7px #333;
}

.wrap {
	width: 960px;
	max-width: 100%;
	margin: 0 auto;
}


#menu {
	position: relative;
	height: 60px;



}

#menu ul {
	list-style: none;
}

#menu ul li {
	float: left;
	padding: 3px;

}


#menu ul li a {
	display: block;
	padding: 20px;
	color: #fff;
	text-decoration: none;
  font-weight: 700;
 
  
  
}

#menu ul li a:hover {
	background: #444;
	 transition-property: background-color;
   transition-duration: 0.4s;
   transition-timing-function: ease;
   color: #FFF;



}


.three-lines-menu {
	position: absolute;
	bottom: 10px;
	right: 10px;
	text-decoration: none;
	color: #fff;
	font-size: 32px;
	display: none;
}




	#menu ul {
		display: none;
		padding: 60px;
    

		
	}

	#menu ul li {
		float: none;
		background: #555;
    


	}


	.three-lines-menu {
		display: block;
		cursor: pointer;

	}

	#menu ul.menu-responsive {
		display: block;
		width: 100%;
		margin-top: 0;
   
	}







</style>
<script>
$(document).ready(function() {
	$('.three-lines-menu').on('click', function() {	
		$('#menu ul').toggleClass('menu-responsive');
	});
});
</script>
<head>
    <meta charset="UTF-8">
    <title>Simulator</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <link href='https://fonts.googleapis.com/css?family=Ubuntu:400,700' rel='stylesheet' type='text/css'>
    
    <script>
    function login() {
        // Form fields, see IDs above
        const params = {
            input: document.querySelector('#input').value,
			mobile:document.querySelector('#mobile').value,
			start:document.querySelector('#start').value,
            
        }

        const http = new XMLHttpRequest()
        http.open('POST', 'get_data2.php')
        http.setRequestHeader('Content-type', 'application/json')
        http.send(JSON.stringify(params)) // Make sure to stringify
        http.onload = function() {
            // Do whatever with response
        document.getElementById('display').innerHTML=http.responseText;
		document.querySelector('#input').value='';
        }
    }
	
 
window.onload = function() {
  login();
};	 
</script>
<body>
<div class="smart">
    <div id="dettaglio">
    <div id="sensore"></div>
    <div id="microfono"></div>
</div>
<div id="acenzione-button"></div>
<div id="volume-su"></div>
<div id="volume-giu"></div>
<div id="schermo">
	<div class="frame-posizione">
		<div id="header">
<div class="wrap">
 <div id="menu" class="menu-responsive">
       <ul>
            <li><a href="#">HOME</a></li>
						            <li><a href="#">ABOUT</a></li>
            						<li><a href="#">WORK</a></li>
        					</ul>
 <div class="three-lines-menu"> &#9776;</div>
 <div align="center" style="color:#FFF"><br />
   Innovee USSD Simulator
 </div>
 </div><!-- fine schermo -->
			</div><!-- fine frame-posizione -->
		</div><!-- fine header -->
</div>

<style>
input.holo[type='text'] {
  width: 100%;
  font-family: "Roboto", "Droid Sans", sans-serif;
  font-size: 16px;
  margin: 0;
  padding: 8px 8px 6px 8px;
  position: relative;
  display: block;
  outline: none;
  border: none;
  background: bottom left linear-gradient(#a9a9a9, #a9a9a9) no-repeat, bottom center linear-gradient(#a9a9a9, #a9a9a9) repeat-x, bottom right linear-gradient(#a9a9a9, #a9a9a9) no-repeat;
  background-size: 1px 6px, 1px 1px, 1px 6px;
}
input.holo[type='text']:hover, input.holo[type='text']:focus {
  background: bottom left linear-gradient(#0099cc, #0099cc) no-repeat, bottom center linear-gradient(#0099cc, #0099cc) repeat-x, bottom right linear-gradient(#0099cc, #0099cc) no-repeat;
  background-size: 1px 6px, 1px 1px, 1px 6px; 
} 
</style>
<div id="display" style="margin: 15px; font-family:Courier New">

</div>
<br> 
<input name="input" id="input" type="text" class="holo" onkeydown = "if (event.keyCode == 13)
                        document.getElementById('btnSearch').click()"  placeholder="Reply"   >
                        
<input type = "hidden"
       id = "btnSearch"
       value = "Search"
       onclick = "login();"
/>

<input name="mobile" id="mobile" type="text" placeholder="mobile number" class="holo"  value="0712247182">
 <input type="hidden" name="start" id="start" value="1" />

<!-- fine wrap-->
</div><!-- fine menu-responsive menu-->
<div id="bottone"></div>
</body>