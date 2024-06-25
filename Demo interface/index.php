<?php
// Initialize the session
session_start();

//////////////////////////////////////////////////////////////////////////

//Variables
$title = "HuGuR Demo"; //page title
$min_role = 0; //reqired roles (0-None, 1-Guest, 2-Member, 3-Admin)

/////////////////////////////////////////////////////////////////////////

// Set user role
$role = 1;
if(isset($_SESSION["role"])){
    $role = $_SESSION["role"];
}
else{
    $_SESSION["role"] = $role;
}


//Include Layout
require "layout.php";

//Include Model and calculations
require "model.php";

require "manual.php";




//Get input type
if(isset($_GET["a"])){
    $input_type = $_GET["a"];
}

if($input_type == "new_model" && $role >= 1){
    if($_POST['gen-model'] == 1){
        $n = (int)$_POST['number_rules'];
        $lr = (float)$_POST['learning_rate'];
        $ds = 1;
        //$ds = (float)$_POST['dataset'];
        $_SESSION["number_rules"] = $n;
        $_SESSION["learning_rate"] = $lr;
        $_SESSION["dataset"] = $ds;
        $model = build_new_model($n,$lr,$ds);
        $_SESSION["model"] = $model;
        $role = 2;
        $_SESSION["role"] = 2;
        $_SESSION["history_mae"][] = validate_model($model);
        $_SESSION["history_model"][] = $model;
    }
}

if($input_type == "reset-model" && $role >= 2){
    if($_POST['gen-model'] == 1){
        $n = (int)$_POST['number_rules'];
        $lr = (float)$_POST['learning_rate'];
        $_SESSION["number_rules"] = $n;
        $_SESSION["learning_rate"] = $lr;
        $model = build_new_model($_SESSION["number_rules"],$_SESSION["learning_rate"],$_SESSION["dataset"]);
        $_SESSION["model"] = $model;
        $_SESSION["history_mae"][] = validate_model($model);
        $_SESSION["history_model"][] = $model;
    }
}

if($input_type == "evaluate" && $role >= 2){
    if($_POST['evaluate'] == 1){
        $model = $_SESSION["model"];
        $test_result = evaluate_model($model);
        $_SESSION["result"] = $test_result[0];
        $_SESSION["role"] = -1;
        $role = -1;
        //unset($_SESSION[$model]);
    }
}

if($input_type == "reset-mae" && $role >= 2){
    if($_POST['reset-mae'] == 1){
        $max_mae = min($_SESSION["history_mae"]);
        $max_idx = array_search($max_mae, $_SESSION["history_mae"]);
        $_SESSION["model"] = $_SESSION["history_model"][$max_idx];
        $_SESSION["history_mae"][] = $max_mae;
        $_SESSION["history_model"][] = $_SESSION["model"];
    }
}

if($input_type == "reset-iteration" && $role >= 2){
    if($_POST['reset-iteration'] == 1){
        $idx = (int)$_POST['iteration-index']-1;
        $_SESSION["model"] = $_SESSION["history_model"][$idx];
        $_SESSION["history_mae"][] = $_SESSION["history_mae"][$idx];
        $_SESSION["history_model"][] = $_SESSION["history_model"][$idx];
    }
}

if($input_type == "simplify" && $role >= 2){
    if($_POST['simplify'] == 1){
        $model = $_SESSION["model"];
        $model = simplify_model($model, $_SESSION["number_rules"], $_SESSION["learning_rate"]);
        $_SESSION["model"] = $model;
        $_SESSION["history_mae"][] = validate_model($model);
        $_SESSION["history_model"][] = $model;
    }
}

if($input_type == "update_model" && $role >= 2){
    $idx = (int)$_POST['rule'];
    $model = $_SESSION["model"];
    $model = update_model($model, $idx, $_SESSION["number_rules"], $_SESSION["learning_rate"]);
    $_SESSION["model"] = $model;
    $_SESSION["history_mae"][] = validate_model($model);
    $_SESSION["history_model"][] = $model;
}

if($input_type == "restart-session"){
    unset($_SESSION["number_rules"]);
    unset($_SESSION["learning_rate"]);
    session_destroy();
    session_start();
    $role = 1;
    $_SESSION["role"] = $role;
}


if($role < 0){
    if($_SESSION["dataset"] == 1){
        $b_mae = 0.2358;
        $o_mae = 0.2146;
        $g_mae = 0.2177;
    }
    elseif($_SESSION["dataset"] == 2){
        $b_mae = 0.2106;
        $o_mae = 0.1947;
        $g_mae = 0.1937;
    }
    elseif($_SESSION["dataset"] == 3){
        $b_mae = 0.2222;
        $o_mae = 0.1862;
        $g_mae = 0.1841;
    }
    elseif($_SESSION["dataset"] == 4){
        $b_mae = 0.0088;
        $o_mae = 0.0478;
        $g_mae = 0.0397;
    }
    elseif($_SESSION["dataset"] == 5){
        $b_mae = 0.6198;
        $o_mae = 0.5289;
        $g_mae = 0.2060;
    }
    elseif($_SESSION["dataset"] == 6){
        $b_mae = 0.0044;
        $o_mae = 0.0358;
        $g_mae = 0.0170;
    }

    $display = "Your";
    $page_content = '<br><br><br><br><br><center><table>
    <tr><td>'.$display.' MAE:</td><td>'.number_format($_SESSION["result"],4).'</td></tr>
    <tr><td>Baseline MAE:</td><td>'.$b_mae.'</td></tr>
    <tr><td>One-Hot MAE:</td><td>'.$o_mae.'</td></tr>
    <tr><td>Graph MAE:</td><td>'.$g_mae.'</td></tr>
    </table>';
    $page_content .= '<form action="index.php?a=restart-session" method="post">
        <button name="restart-session" value="1" />Start over again</button></form>';
}
else{
    if(isset($_SESSION["model"]) && $role > 1){
        $page_content = plot_model($_SESSION["model"]);
    }
    else{
        $page_content = '<center>';
        $page_content .= '<br><br>';
        $page_content .= return_man();
    }
}




?>

<?php
//Generate the HTML Head
generate_head($title);
?>


<?php
//Generate the Menue-Bar
generate_menue($role);
?>


<!--  Page Content  -->




<?php 
echo $page_content;
//echo $_SESSION["model"]["base_predict"];
//foreach($_SESSION["model"]["names"] as $idx => $name){
//    echo '<br>'.$name.' => '.$_SESSION["model"]["betas"][$idx].' ';
//}
?>


</center>








<?php
//Generate the Login/User Bar
generate_login($role);
?>




<!--

    

</html>



-->