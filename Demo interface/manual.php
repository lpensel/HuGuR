<?php

function return_man(){
	$output = '<h2>How to build a model</h2>
	<br>
	<!--<h3>Video Introduction</h3>
	 <iframe width="560" height="315" src="https://www.youtube.com/embed/6HJ_imKOq-U" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
	<br>-->
	<h3>1. About the model</h3>
	In the task of <b>permutation regression</b> you are given permutations of objects, for instance the order of questions in a test, and corresponding numerical values, such as the average points per question. With this data you want to build a model, which predicts the numerical target value of other permutations as precise as possible.<br>
	With this framework, you can build a linear model for permutation regression, based on simple understandable rules. Each rule describes an ordered subset of the permutatet items, which is fulfilled by a permutation if the items of the rule appear in the given order in the permutation. For instance the permutation [3,2,1,4] fulfills the rule (2,4) but not the rule (1,2,3). Additionally, each rule is associated with a numerical coefficient. The model calculates its prediction by summing the coefficients of all fulfilled rules.<br>
The color of a rule represents its sign, where green rules represent rules which lead to <it>good</it> targets, i.e. many points, and red rules represent rules which lead to <it>bad</it> targets, i.e. few points. The saturation of a rule corresponds to its importance, i.e. a highly saturated rule has a higher impact on the prediction.<br>
The quality of a model is measured with the mean absolute error (MAE), which is the average distance from the predicted target to the true target value for a tested set of permutations. The lower the MAE the better the performance of the model.<br>
<b>Important</b>: The shown MAE is calculated on a small validation set, which is distinct from the actual test set. Therefore, the final MAE of the model varies from the MAE during the building process. It might be advisable to submit a less complex model with a higher MAE, since it is less prone to be overfitted to the validation set.
 <br>
	<br>
	<h3>2. Set parameters</h3>
	<table><tr><td><img src="images/sc1.png" alt="Userinterface for generating the model" width="249" height="172"></td>
	<td>When starting a new model, you can select the number of rules and the learning rate.<br><br><br>
	<table><tr><td><b>Number of rules:</b></td>
	<td>During each step of the model building process, this many new rules are added to the model. The higher you choose this value, the more complex your model will get.
	</td></tr>
	<tr><td><b>Learning rate:</b></td>
	<td>This parameter controls the impact of each individual rule to the overall prediction of the model.
	</td></tr></table>
	</td></tr></table>
	<br>
	<h3>3. Building the model</h3>
	<table><tr><td><img src="images/sc2_small.png" alt="Userinterface for building the model" width="433" height="320"></td>
	<td>During the building process you can add rules, remove rules, simplify, reset to your best model and start over with new parameters.<br>
	Meanwhile, you can track your current performance and compare it to previous models on the provided history graph.<br><br>
	<table>
	<tr><td><b>Add rules:</b></td>
	<td>By clicking on an active rule, a rule which is not grey, you replace this rule with several new child rules. Those rules are more specific than the previous.
	</td></tr><tr><td><br></td></td></td></tr>
	<tr><td><b>Remove rules:</b></td>
	<td>By clicking on an inactive rule, a rule which is grey, you reactivate that rule and remove all its children from the model.
	</td></tr><tr><td><br></td></td></td></tr>
	<tr><td><b>Simplify:</b></td>
	<td>The rules with the highest absolute weights are selected as the base for a new model.
	</td></tr><tr><td><br></td></td></td></tr>
	<tr><td><b>New model:</b></td>
	<td>You can build a new model with new parameters.
	</td></tr><tr><td><br></td></td></td></tr>
	<tr><td><b>Reset to best MAE:</b></td>
	<td>Reload the model with the smallest error you encountered so far.
	</td></tr><tr><td><br></td></td></td></tr>
	<tr><td><b>Reset to iteration:</b></td>
	<td>Reload the model of a specific iteration.
	</td></tr><tr><td><br></td></td></td></tr>
	<tr><td><b>Evaluate:</b></td>
	<td>End the model building process and evaluate your current model. <b>Important:</b> You cannot continue the model building process after this action!
	</td></tr>
	</table>
	</td></tr></table>';

	return $output;
}
?>