<?php

require __DIR__ . '/../vendor/autoload.php';

use MuchDataProcessor\MuchDataProcessor;

$input = new \MuchDataProcessor\InputCsv();
$output = new \MuchDataProcessor\OutputDb('localhost', 'root', 'secret', 'hellyeah', 'prefix_');


// note that if table exists and columns are same, it will append data
//$output->setTable('test_table');
// or
//length0 = size-1
$output->setTable('test_table', [
    ['name' => 'Field1', 'length0' => 99],
    ['name' => 'Field2', 'length0' => 49],
    ['name' => 'Field3', 'length0' => 39],
    ['name' => 'Field4', 'length0' => 79],
    ['name' => 'Field5', 'length0' => 39],
    ['name' => 'Field6', 'length0' => 119]
]);

$processor = new MuchDataProcessor(__DIR__.'/stock.csv', $input, $output);