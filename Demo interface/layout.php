<?php

$username = $password = "";
$username_err = $password_err = "";


function generate_head($title){
	echo '<head>
  <title>'.$title.'</title>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.js" integrity="sha512-d6nObkPJgV791iTGuBoVC9Aa2iecqzJRE0Jiqvk85BhLHAPhWqkuBiQb1xz2jvuHNqHLYoN3ymPfpiB1o+Zgpw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="w3.css">
<link rel="stylesheet" href="w3-theme-black.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
html,body,h1,h2,h3,h4,h5,h6 {font-family: "Roboto", sans-serif;}
.w3-sidebar {
  z-index: 3;
  width: 250px;
  top: 43px;
  bottom: 0;
  height: inherit;
}
.fix-width {
  width: 100%;
  overflow-y: auto;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.table {
  width: 100%;
  margin-bottom: 0;
  max-width: none;
}
</style>


</head>
<body>

<!-- Navbar -->
<div class="w3-top">
  <div class="w3-bar w3-theme w3-top w3-left-align w3-large">
    <a class="w3-bar-item w3-button w3-right w3-hide-large w3-hover-white w3-large w3-theme-l1" href="javascript:void(0)" onclick="w3_open()"><i class="fa fa-bars"></i></a>
    <a href="index.php" class="w3-bar-item w3-button w3-theme-l1">HuGuR Demo</a>
  </div>
</div>

<!--<center> 
<a class="nav" href=index.php><h1>HuGuR</h1></a>   
</center>-->
    ';
}


function generate_menue($role){ 
  if(isset($_SESSION["number_rules"])){
    $n = $_SESSION["number_rules"];
  }
  else{
    $n = random_int(4,10);
  }
  if(isset($_SESSION["learning_rate"])){
    $lr = $_SESSION["learning_rate"];
  }
  else{
    $lrs = [0.1,0.25,0.05,0.5,0.01];
    $lrn = random_int(0,4);
    $lr = $lrs[$lrn];
  }

	$output = '<!-- Sidebar -->
<nav class="w3-sidebar w3-bar-block w3-collapse w3-large w3-theme-l5 w3-animate-left" id="mySidebar">
  <a href="javascript:void(0)" onclick="w3_close()" class="w3-right w3-xlarge w3-padding-large w3-hover-black w3-hide-large" title="Close Menu">
    <i class="fa fa-remove"></i>
  </a>';

if($role == 1 || $role == 0 ) {
    $output .= '<h4 class="w3-bar-item"><b>Menu</b></h4>';
    $output .= '<form action="index.php?a=new_model" method="post"><table><tr>';
    $output .= '<td>Number of rules:</td><td><select id="number_rules" name="number_rules">';
    for($i=1;$i <= 20; $i++){
      if($i == $n){
        $output .= '<option value="'.$i.'" selected>'.$i.'</option>';
      }
      else{
        $output .= '<option value="'.$i.'">'.$i.'</option>';
      }
    }
    $output .= '</select></td></tr>';
    $output .= '<tr><td>Learning rate:</td>';
    $output .= '<td><input type="text" id="learning_rate" name="learning_rate" size="10" value="'.$lr.'"></td></tr>';
    //$output .= '<tr><td>Dataset:</td>
    //<td><select id="dataset" name="dataset">
    //<option value="1" selected>small</option>
    //<option value="2">medium</option>
    //<option value="3">big</option>
    //</select></td></tr>';
    $output .= '</table>
    <button name="gen-model" value="1" />Generate Model</button></form>';
  //<a class="w3-bar-item w3-button w3-hover-black" href="#">Link</a>
  //<a class="w3-bar-item w3-button w3-hover-black" href="#">Link</a>
  //<a class="w3-bar-item w3-button w3-hover-black" href="#">Link</a>
  //<a class="w3-bar-item w3-button w3-hover-black" href="#">Link</a>
    
}
if($role > 1){
  $output .= '<h4 class="w3-bar-item"><b>Menu</b></h4>';
  $output .= '<form action="index.php?a=reset-model" method="post"><table><tr>';
    $output .= '<td>Number of rules:</td><td><select id="number_rules" name="number_rules">';
    for($i=1;$i <= 20; $i++){
      if($i == $n){
        $output .= '<option value="'.$i.'" selected>'.$i.'</option>';
      }
      else{
        $output .= '<option value="'.$i.'">'.$i.'</option>';
      }
    }
    $output .= '</select></td></tr>';
    $output .= '<tr><td>Learning rate:</td>';
    $output .= '<td><input type="text" id="learning_rate" name="learning_rate" size="10" value="'.$lr.'"></td></tr>';
  $output .= '</table>
    <button name="gen-model" value="1" />NEW MODEL</button></form><br>';
  $output .= '<form action="index.php?a=reset-mae" method="post">
    <button name="reset-mae" value="1" />RESET to best MAE</button>
    </form>';
    $output .= '<form action="index.php?a=reset-iteration" method="post"><table><tr>
    <td><button name="reset-iteration" value="1" />RESET to iteration</button></td><td><select id="iteration-index" name="iteration-index">';
    $max_mae = min($_SESSION["history_mae"]);
    $max_idx = array_search($max_mae, $_SESSION["history_mae"]);
    for($i = 1; $i <= count($_SESSION["history_mae"]); $i++){
      if($i == $max_idx){
        $output .= '<option value="'.$i.'" selected>'.$i.'</option>';
      }
      else{
        $output .= '<option value="'.$i.'">'.$i.'</option>';
      }
    }
    $output .= '</select></td></tr></table></form>';
  $output .= '<form action="index.php?a=simplify" method="post">
    <button name="simplify" value="1" />SIMPLIFY</button>
    </form>';
  $output .= '<br><br><form action="index.php?a=evaluate" method="post">
    <button name="evaluate" value="1" />EVALUATE and FINISH</button>
    </form>';
}
if($role < 0){
  $output .= '<h4 class="w3-bar-item"><b>Menu</b></h4>';
}
  


$output .= '</nav>
<!-- Overlay effect when opening sidebar on small screens -->
<div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>

<!-- Main content: shift it to the right by 250 pixels when the sidebar is visible -->
<div class="w3-main" style="margin-left:250px">';

echo $output;
}

function generate_login($role){
	global $username, $username_err,$password_err;
	$output = '<!-- END MAIN -->
</div>

<script>
// Get the Sidebar
var mySidebar = document.getElementById("mySidebar");

// Get the DIV with overlay effect
var overlayBg = document.getElementById("myOverlay");

// Toggle between showing and hiding the sidebar, and add overlay effect
function w3_open() {
  if (mySidebar.style.display === "block") {
    mySidebar.style.display = "none";
    overlayBg.style.display = "none";
  } else {
    mySidebar.style.display = "block";
    overlayBg.style.display = "block";
  }
}

// Close the sidebar with the close button
function w3_close() {
  mySidebar.style.display = "none";
  overlayBg.style.display = "none";
}
</script>

</body>
</html>';

echo $output;
}

?>



