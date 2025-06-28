<?php
require_once 'ArrayWrapper.php';

use NAL_6295\Collections\ArrayWrapper;

echo "Debug Test for ArrayWrapper\n";
echo "===========================\n\n";

// Simple test data
$testData = array(
    array("key" => 2, "key2" => 3, "value" => 9),
    array("key" => 1, "key2" => 1, "value" => 1),
    array("key" => 1, "key2" => 2, "value" => 4),
    array("key" => 0, "key2" => 0, "value" => 0),
);

echo "Original data:\n";
print_r($testData);

// Test OrderBy
echo "\nTesting OrderBy...\n";
$target = ArrayWrapper::Wrap($testData);
$result = $target->orderBy(array(
    array("key" => "key", "desc" => false),
    array("key" => "key2", "desc" => false)
))->toVar();

echo "OrderBy result:\n";
print_r($result);

// Test GroupBy
echo "\nTesting GroupBy...\n";
$target2 = ArrayWrapper::Wrap($testData);
$groupResult = $target2->groupBy(array("key"))->toVar();

echo "GroupBy result:\n";
print_r($groupResult);

// Test WHERE
echo "\nTesting WHERE...\n";
$target3 = ArrayWrapper::Wrap($testData);
$whereResult = $target3->where(function($item) { 
    echo "Checking item with key: " . $item['key'] . "\n";
    return $item['key'] < 2; 
})->toVar();

echo "WHERE result (key < 2):\n";
print_r($whereResult);

// Test combined
echo "\nTesting combined WHERE + SELECT...\n";
$target4 = ArrayWrapper::Wrap($testData);
$combinedResult = $target4
    ->where(function($item) { return $item['key'] < 2; })
    ->select(function($item) { return array('key' => $item['key'], 'doubled_value' => $item['value'] * 2); })
    ->toVar();

echo "Combined result:\n";
print_r($combinedResult);
?>