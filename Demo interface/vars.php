<?php


$BASE_RULES = [
 ['0', '1'],
 ['0', '2'],
 ['0', '3'],
 ['0', '4'],
 ['1', '0'],
 ['1', '2'],
 ['1', '3'],
 ['1', '4'],
 ['2', '0'],
 ['2', '1'],
 ['2', '3'],
 ['2', '4'],
 ['3', '0'],
 ['3', '1'],
 ['3', '2'],
 ['3', '4'],
 ['4', '0'],
 ['4', '1'],
 ['4', '2'],
 ['4', '3']];

$BASE_ITEMS = ['0', '1', '2', '3', '4'];

$DATA_RULES = [$BASE_RULES];
$DATA_ITEMS = [$BASE_ITEMS];
$DATA_FILES = [[['data/hil_survey_base_train.csv','data/hil_survey_y_train.csv'],['data/hil_survey_base_val.csv','data/hil_survey_y_val.csv'],['data/hil_survey_base_test.csv','data/hil_survey_y_test.csv']]];

?>