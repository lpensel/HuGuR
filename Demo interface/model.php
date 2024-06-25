<?php

//require_once "libraries/numphp/vendor/autoload.php";

//use SciPhp\NumPhp as np;

require "vars.php";

function test(){
	//echo "start";
	//$m = np::loadtxt('data/hil_survey_y_train.csv');
	//echo "sum";
	//$s = $m->sum();
	//$n = $m->size;
	//$mean = $s / $n;
	//return $s;
	$csv = array_map('str_getcsv', file('data/hil_survey_y_val.csv'));
	$a = $csv[0];
	if(count($a)) {
		$average = array_sum($a)/count($a);
	}
	return $average;
}

function vector_prod($a,$b){
	$output = [];
	foreach($a as $key=>$value){
		$output[$key] = $a[$key]*$b[$key];
	}
	return $output;
}

function vector_sub($a,$b){
	$output = [];
	foreach($a as $key=>$value){
		$output[$key] = $a[$key]-$b[$key];
	}
	return $output;
}

function vector_scalar($a,$b){
	function help_func($item){
		global $b;
		return $item * $b;
	}
	return array_map("help_func",$a);
}

function binary2set($b_list){
	$output = [];
	foreach($b_list as $idx => $value){
		if($value == 1){
			$output[] = $idx;
		}
	}
	return $output;
}

function get_selected($keys, $input){
	$output = [];
	foreach($keys as $key){
		$output[] = $input[$key];
	}
	if(count($output) == 0){
		$output = [0.0];
	}
	return $output;
}

function update_residuals($rule, $beta, $residuals){
	foreach($rule as $key){
		$residuals[$key] -= $beta;
	}
	return $residuals;
}

function update_prediction($rule, $beta, $prediction){
	foreach($rule as $key){
		$prediction[$key] += $beta;
	}
	return $prediction;
}

function base_res($y, $baseline){
	$output = [];
	foreach($y as $item){
		$output[] = $item - $baseline;
	}
	return $output;
}

function get_prediction($y, $res){
	$output = [];
	foreach($y as $index => $y_val){
		$output[] = $y_val - $res[$index];
	}
	return $output;
}


function build_new_model($n,$lr,$ds){
	global $DATA_RULES;
	global $DATA_ITEMS;
	global $DATA_FILES;
	//echo "<br><br><br><br>n: ".$n." lr: ".$lr."<br>";
	$y_file = $DATA_FILES[$ds - 1][0][1];
	$x_file = $DATA_FILES[$ds - 1][0][0];

	$csv = array_map('str_getcsv', file($y_file));
	
	$y_train = $csv[0];


	//base predict
	$base_predict = array_sum($y_train)/count($y_train);
	
	//residuals 0
	$residuals = base_res($y_train, $base_predict);

	$csv = array_map('str_getcsv', file($x_file));
	$baselines = $DATA_RULES[$ds - 1];
	
	$base_rules = array_map('binary2set',$csv);
	$taus = [];
	//get rules
	for($i = 0, $size = count($baselines); $i < $size; ++$i) {
		$cur_rule = $base_rules[$i];
		$cur_res = get_selected($cur_rule,$residuals);
		$grad = abs(array_sum($cur_res));
		//echo $i.':'.array_sum($cur_res).'    ';
		$taus[] = $grad;
	}
	arsort($taus);
	$selected_rules = [];
	$count = 0;
	foreach($taus as $key => $val){
		if($count < $n){
			$selected_rules[] = $key;
		}
		$count++;
	}
	//echo count($selected_rules);
	//echo "rules selected<br>";
	//calc betas
	$model_rules = [];
	$model_dna = [];
	$model_x_rules = [];
	$model_names = [];
	$model_betas = [];
	$model_active = [];
	$model_level = [];

	while(count($selected_rules) > 0){
		$cur_idx = array_shift($selected_rules);
		$cur_rule = $base_rules[$cur_idx];
		$cur_res = get_selected($cur_rule,$residuals);
		$grad = array_sum($cur_res);
		$cur_count = count($cur_rule);
		if($cur_count == 0){
			$cur_beta = 0.0;
		}
		else{
			$cur_beta = $lr * ($grad/$cur_count);
		}
		$model_rules[] = [$cur_idx];
		$model_dna[] = $baselines[$cur_idx];
		$model_x_rules[] = $cur_rule;
		$model_level[] = 0;
		$model_active[] = true;
		$model_betas[] = $cur_beta;
		$model_names[] = implode(", ", $baselines[$cur_idx]);

		$residuals = update_residuals($cur_rule, $cur_beta, $residuals);

		$taus = [];
		foreach($selected_rules as $key => $val){
			$cur_rule = $base_rules[$val];
			$cur_res = get_selected($cur_rule,$residuals);
			$grad = abs(array_sum($cur_res));
			$taus[$val] = $grad;
		}
		arsort($taus);
		$selected_rules = array_keys($taus);
		//echo count($selected_rules)."_".count($model_rules);
		//echo "rules selected<br>";
	}
	//echo "betas done<br>";
	
	$model = array("rules" => $model_rules, "x_rules" => $model_x_rules, "names" => $model_names, "betas" => $model_betas, "active" => $model_active, "level" => $model_level, "base_predict" => $base_predict, "dna" => $model_dna, "dataset" => $ds);
	return $model;
}

function validate_baseline($model){
	global $DATA_RULES;
	global $DATA_ITEMS;
	global $DATA_FILES;
	$y_file = $DATA_FILES[$model["dataset"] - 1][1][1];
	$csv = array_map('str_getcsv', file($y_file));
	
	$y_val = $csv[0];

	$base_predict = $model["base_predict"];
	
	//errors 0
	$y_errors = base_res($y_val, $base_predict);
	$mae = array_sum(array_map("abs",$y_errors))/count($y_errors);
	return $mae;

}

function validate_model($model){
	global $DATA_RULES;
	global $DATA_ITEMS;
	global $DATA_FILES;

	$y_file = $DATA_FILES[$model["dataset"] - 1][1][1];
	$x_file = $DATA_FILES[$model["dataset"] - 1][1][0];
	$csv = array_map('str_getcsv', file($y_file));

	$y_val = $csv[0];

	$base_predict = $model["base_predict"];
	
	//errors 0
	$y_errors = base_res($y_val, $base_predict);

	$csv = array_map('str_getcsv', file($x_file));
	
	$base_rules = array_map('binary2set',$csv);

	foreach($model["rules"] as $idx => $rules){
		if($model["active"][$idx]){
			$items = array_keys($y_errors);
			foreach($rules as $rule){
				$base_rule = $base_rules[$rule];
				$items = array_intersect($items, $base_rule);
			}
			$y_errors = update_residuals($items, $model["betas"][$idx], $y_errors);
		}
	}
	$mae = array_sum(array_map("abs",$y_errors))/count($y_errors);
	return $mae;

}

function evaluate_model($model){
	global $DATA_RULES;
	global $DATA_ITEMS;
	global $DATA_FILES;

	$y_file = $DATA_FILES[$model["dataset"] - 1][2][1];
	$x_file = $DATA_FILES[$model["dataset"] - 1][2][0];

	$csv = array_map('str_getcsv', file($y_file));
	$y_val = $csv[0];

	$base_predict = $model["base_predict"];
	
	//errors 0
	$y_errors = base_res($y_val, $base_predict);

	//$y_predict = [];
	//foreach($y_val as $item){
	//	$y_predict[] = $baseline;
	//}

	$csv = array_map('str_getcsv', file($x_file));
	$base_rules = array_map('binary2set',$csv);

	foreach($model["rules"] as $idx => $rules){
		if($model["active"][$idx]){
			$items = array_keys($y_errors);
			foreach($rules as $rule){
				$base_rule = $base_rules[$rule];
				$items = array_intersect($items, $base_rule);
			}
			$y_errors = update_residuals($items, $model["betas"][$idx], $y_errors);
			//$y_predict = update_prediction($items, $model["betas"][$idx], $y_predict);
		}
	}
	$y_predict = get_prediction($y_val, $y_errors);
	$mae = array_sum(array_map("abs",$y_errors))/count($y_errors);
	return [$mae, $y_predict];

}




function plot_model($model){
	$output = '<br><br><br>
	<h2>Model: </h2><small><form action="index.php?a=update_model" method="post">
	<div class="fix-width"><table class="table">';

	$mae = validate_model($model);
	$mae_base = validate_baseline($model);

	$abs_betas = array_map('abs',$model["betas"]);
	$max_beta = max($abs_betas);

	$out_table = [];
	$max_level = max($model["level"]);
	$table_length = count(array_filter($model["active"]));
	for($i = 0; $i <= $max_level; $i++){
		$out_table[$i] = [];
		for($j = 0; $j < $table_length; $j++){
			$out_table[$i][$j] = '<td></td>';
		}
	}

	$pos = 0;

	foreach($model["names"] as $key => $val){
		if($model["active"][$key]){
			$color = intval(255 - ((abs($model["betas"][$key])/$max_beta)*255));
			$color = max(0,min(255,$color));
			$color = dechex($color);
			if(strlen($color)<2){
				$color = "0".$color;
			}
			if($model["dataset"] <= 3){
				if($model["betas"][$key] < 0){
					$color_str = "#ff" . $color . $color;
				}
				else{
					$color_str = "#" . $color . $color . "ff";
				}
			}
			else{
				if($model["betas"][$key] > 0){
					$color_str = "#ff" . $color . $color;
				}
				else{
					$color_str = "#" . $color . $color . "ff";
				}
			}
		}
		else{
			$color_str = "#bbbbbb";
		}
		$out_table[$model["level"][$key]][$pos] = '<td>
		<button style="background-color:'.$color_str.'" name="rule" value="'.$key.'" />'.$val.'</button>
		</td>';
		if($model["active"][$key]){
			$pos += 1;
		}
	}

	foreach($out_table as $level_layer){
		$output .= '<tr>';
		foreach($level_layer as $entry){
			$output .= $entry;
		}
		$output .= '</tr>
		';
	}

	$output .= '</table></div></form></small>';

	$output .= '<br><br><h3>MAE: '.number_format($mae,4).'</h3>';
	//$output .= '<h4>Baseline: '.number_format($mae_base,4).'</h4>';
	$output .= '<br><br>';

	function add_one(int $n){
		return $n + 1;
	}


	$output .= '<canvas id="myChart" style="width:100%;max-width:600px;height:25%;max-height:200px"></canvas>
                        <script>
                        const ctx = document.getElementById("myChart");
                        const myChart = new Chart(ctx, {
                            type: "line",
                            data: {
                                labels: ['. implode(", ", array_map("strval",array_map("add_one",array_keys($_SESSION["history_mae"])))) .'],
                                datasets: [{
                                    label: "MAE history of the models",
                                    data: ['. implode(", ", array_map("strval",$_SESSION["history_mae"])) .'],
                                    fill: false,
                                    borderColor: "rgb(75, 192, 192)",
                                    yAxisID: "y",
                                    tension: 0.1
                                }]
                            },
                            options: {
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: "Model iteration"
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: "MAE"
                                        },
                                        position: "left"
                                    }
                                }
                            }
                        });
                        </script>
                        <br>
                        ';


	return $output;
}


function flatten_model($model,$idx){
	$model_rules = [];
	$model_dna = [];
	$model_x_rules = [];
	$model_names = [];
	$model_betas = [];
	$model_active = [];
	$model_level = [];
	$base_predict = $model["base_predict"];

	$deleting = false;
	$target_level = 0;

	foreach($model["level"] as $key => $cur_level){
		if($deleting){
			if($cur_level <= $target_level){
				$deleting = false;
			}
		}
		if(!$deleting){
			$model_rules[] = $model["rules"][$key];
			$model_dna[] = $model["dna"][$key];
			$model_x_rules[] = $model["x_rules"][$key];
			$model_names[] = $model["names"][$key];
			$model_betas[] = $model["betas"][$key];
			$model_level[] = $model["level"][$key];
			if($key == $idx){
				$model_active[] = true;
				$deleting = true;
				$target_level = $cur_level;
			}
			else{
				$model_active[] = $model["active"][$key];
			}
		}
	}

	$out = array("rules" => $model_rules, "x_rules" => $model_x_rules, "names" => $model_names, "betas" => $model_betas, "active" => $model_active, "level" => $model_level, "base_predict" => $base_predict, "dna" => $model_dna, "dataset" => $model["dataset"]);
	return $out;

}

function deepen_model($model, $idx, $n, $lr){
	global $DATA_RULES;
	global $DATA_ITEMS;
	global $DATA_FILES;

	$y_file = $DATA_FILES[$model["dataset"] - 1][0][1];
	$x_file = $DATA_FILES[$model["dataset"] - 1][0][0];

	$csv = array_map('str_getcsv', file($y_file));
	$y_train = $csv[0];

	$model["active"][$idx] = false;

	$base_predict = $model["base_predict"];
	$residuals = base_res($y_train, $base_predict);

	//residuals for model
	foreach($model["x_rules"] as $key => $cur_rule){
		if($model["active"][$key]){
			$residuals = update_residuals($cur_rule, $model["betas"][$key], $residuals);
		}
	}

	//get all children
	$base_dna = $model["dna"][$idx];

	$csv = array_map('str_getcsv', file($x_file));
	$baselines = $DATA_RULES[$model["dataset"] - 1];
	$baseitems = $DATA_ITEMS[$model["dataset"] - 1];

	
	$base_rules = array_map('binary2set',$csv);

	$child_dna = [];
	$child_rules = [];
	$child_x_rules = [];

	foreach($baseitems as $candidate){
		if(!in_array($candidate,$base_dna)){
			for($i = 0; $i < count($base_dna) + 1; $i++){
				$this_dna = [];
				$this_rules = $model["rules"][$idx];
				$this_x_rules = $model["x_rules"][$idx];
				foreach($base_dna as $key => $val){
					if($i == $key){
						$this_dna[] = $candidate;
						$cur_rule = [$candidat, $val];
						$rule_idx = array_search($cur_rule,$baselines);
						$this_rules[] = $rule_idx;
						$this_x_rules = array_intersect($this_x_rules, $base_rules[$rule_idx]);
						if($key > 0){
							$cur_rule = [$val, $candidate];
							$rule_idx = array_search($cur_rule,$baselines);
							$this_rules[] = $rule_idx;
							$this_x_rules = array_intersect($this_x_rules, $base_rules[$rule_idx]);
						}
					}
					$this_dna[] = $val;
				}
				if($i == count($base_dna)){
					$this_dna[] = $candidate;
					$cur_rule = [end($base_dna), $candidate];
					$rule_idx = array_search($cur_rule,$baselines);
					$this_rules[] = $rule_idx;
					$this_x_rules = array_intersect($this_x_rules, $base_rules[$rule_idx]);
				}
				$child_dna[] = $this_dna;
				$child_rules[] = $this_rules;
				$child_x_rules[] = $this_x_rules;
				//echo implode(", ", $this_dna);
				//echo "_";
			}
		}
	}


	$taus = [];
	//get rules
	foreach($child_x_rules as $key => $cur_rule) {
		$cur_res = get_selected($cur_rule,$residuals);
		$grad = abs(array_sum($cur_res));
		$taus[$key] = $grad;
	}
	arsort($taus);
	$selected_rules = [];
	$count = 0;
	foreach($taus as $key => $val){
		if($count < $n){
			$selected_rules[] = $key;
		}
		$count++;
	}
	//echo "rules selected<br>";
	//calc betas
	$update_rules = [];
	$update_dna = [];
	$update_x_rules = [];
	$update_names = [];
	$update_betas = [];

	while(count($selected_rules) > 0){
		$cur_idx = array_shift($selected_rules);
		$cur_rule = $child_x_rules[$cur_idx];
		$cur_res = get_selected($cur_rule,$residuals);
		$grad = array_sum($cur_res);
		$cur_count = count($cur_rule);
		if($cur_count == 0){
			$cur_beta = 0.0;
		}
		else{
			$cur_beta = $lr * ($grad/$cur_count);
		}
		$update_rules[] = $child_rules[$cur_idx];
		$update_dna[] = $child_dna[$cur_idx];
		$update_x_rules[] = $child_x_rules[$cur_idx];
		$update_betas[] = $cur_beta;
		$update_names[] = implode(", ", $child_dna[$cur_idx]);

		$residuals = update_residuals($cur_rule, $cur_beta, $residuals);

		$taus = [];
		foreach($selected_rules as $key => $val){
			$cur_rule = $child_x_rules[$val];
			$cur_res = get_selected($cur_rule,$residuals);
			$grad = abs(array_sum($cur_res));
			$taus[$val] = $grad;
		}
		arsort($taus);
		$selected_rules = array_keys($taus);
	}

	$model_rules = [];
	$model_dna = [];
	$model_x_rules = [];
	$model_names = [];
	$model_betas = [];
	$model_active = [];
	$model_level = [];
	$base_predict = $model["base_predict"];


	foreach($model["level"] as $key => $cur_level){
		$model_rules[] = $model["rules"][$key];
		$model_dna[] = $model["dna"][$key];
		$model_x_rules[] = $model["x_rules"][$key];
		$model_names[] = $model["names"][$key];
		$model_betas[] = $model["betas"][$key];
		$model_level[] = $model["level"][$key];
		$model_active[] = $model["active"][$key];
		if($key == $idx){
			foreach($update_names as $key2 => $val){
				$model_rules[] = $update_rules[$key2];
				$model_dna[] = $update_dna[$key2];
				$model_x_rules[] = $update_x_rules[$key2];
				$model_names[] = $update_names[$key2];
				$model_betas[] = $update_betas[$key2];
				$model_level[] = $cur_level + 1;
				$model_active[] = true;
			}
		}
	}

	$out = array("rules" => $model_rules, "x_rules" => $model_x_rules, "names" => $model_names, "betas" => $model_betas, "active" => $model_active, "level" => $model_level, "base_predict" => $base_predict, "dna" => $model_dna, "dataset" => $model["dataset"]);
	return $out;






}


function update_model($model, $idx, $n, $lr){
	if($model['active'][$idx]){
		$out = deepen_model($model, $idx, $n, $lr);
	}
	else{
		$out = flatten_model($model,$idx);
	}
	return $out;
}


function simplify_model($model, $n, $lr){
	global $DATA_RULES;
	global $DATA_ITEMS;
	global $DATA_FILES;

	$y_file = $DATA_FILES[$model["dataset"] - 1][0][1];

	


	$abs_betas = array_map('abs',$model["betas"]);
	arsort($abs_betas);
	$selected_rules = [];
	$count = 0;
	foreach($abs_betas as $key => $val){
		if($count < $n){
			$selected_rules[] = $key;
		}
		$count++;
	}

	$csv = array_map('str_getcsv', file($y_file));
	$y_train = $csv[0];

	$base_predict = $model["base_predict"];
	$residuals = base_res($y_train, $base_predict);


	$model_rules = [];
	$model_dna = [];
	$model_x_rules = [];
	$model_names = [];
	$model_betas = [];
	$model_active = [];
	$model_level = [];

	while(count($selected_rules) > 0){
		$taus = [];
		foreach($selected_rules as $key => $val){
			$cur_rule = $model["x_rules"][$val];
			$cur_res = get_selected($cur_rule,$residuals);
			$grad = abs(array_sum($cur_res));
			$taus[$val] = $grad;
		}
		arsort($taus);
		$selected_rules = array_keys($taus);

		$cur_idx = array_shift($selected_rules);
		$cur_rule = $model["x_rules"][$cur_idx];
		$cur_res = get_selected($cur_rule,$residuals);
		$grad = array_sum($cur_res);
		$cur_count = count($cur_rule);
		if($cur_count == 0){
			$cur_beta = 0.0;
		}
		else{
			$cur_beta = $lr * ($grad/$cur_count);
		}
		$model_rules[] = $model["rules"][$cur_idx];
		$model_dna[] = $model["dna"][$cur_idx];
		$model_x_rules[] = $model["x_rules"][$cur_idx];
		$model_level[] = 0;
		$model_active[] = true;
		$model_betas[] = $cur_beta;
		$model_names[] = $model["names"][$cur_idx];

		$residuals = update_residuals($cur_rule, $cur_beta, $residuals);

		
		//echo count($selected_rules)."_".count($model_rules);
		//echo "rules selected<br>";
	}
	//echo "betas done<br>";
	
	$output = array("rules" => $model_rules, "x_rules" => $model_x_rules, "names" => $model_names, "betas" => $model_betas, "active" => $model_active, "level" => $model_level, "base_predict" => $base_predict, "dna" => $model_dna, "dataset" => $model["dataset"]);
	return $output;




}


?>