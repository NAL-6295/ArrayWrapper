<?php
require_once 'ArrayWrapper.php';

use NAL_6295\Collections\ArrayWrapper;

echo "Performance Test for ArrayWrapper Optimizations\n";
echo "==============================================\n\n";

// Test data generation (same as the original test)
function generateTestData($count = 10000) {
    $targetSource = array();
    for ($i = 0; $i < $count; $i++) { 
        $value = array("key" => floor($i / 10), "key2" => $i, "value" => $i * $i);
        if($i % 2 == 0) {
            array_push($targetSource, $value);
        } else {
            array_splice($targetSource, 0, 0, array($value));
        }
    }
    return $targetSource;
}

// Generate expected result for verification
function generateExpectedResult($count = 10000) {
    $expected = array();
    for ($i = 0; $i < $count; $i++) { 
        array_push($expected, array("key" => floor($i / 10), "key2" => $i, "value" => $i * $i));
    }
    return $expected;
}

// Test OrderBy performance
function testOrderByPerformance($dataSize = 10000) {
    echo "Testing OrderBy with $dataSize records...\n";
    
    $testData = generateTestData($dataSize);
    $expected = generateExpectedResult($dataSize);
    
    // Measure performance
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    $target = ArrayWrapper::Wrap($testData);
    $actual = $target->orderBy(array(
        array("key" => "key", "desc" => false),
        array("key" => "key2", "desc" => false)
    ))->toVar();
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
    
    // Verify correctness
    $isCorrect = (json_encode($expected) === json_encode($actual));
    
    echo "  Execution time: " . number_format($executionTime, 2) . " ms\n";
    echo "  Memory used: " . number_format($memoryUsed, 2) . " MB\n";
    echo "  Result correct: " . ($isCorrect ? "YES" : "NO") . "\n\n";
    
    return array(
        'time' => $executionTime,
        'memory' => $memoryUsed,
        'correct' => $isCorrect
    );
}

// Test GroupBy performance
function testGroupByPerformance($dataSize = 1000) {
    echo "Testing GroupBy with $dataSize records...\n";
    
    $testData = generateTestData($dataSize);
    
    // Measure performance
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    $target = ArrayWrapper::Wrap($testData);
    $actual = $target->groupBy(array("key"))->toVar();
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
    
    echo "  Execution time: " . number_format($executionTime, 2) . " ms\n";
    echo "  Memory used: " . number_format($memoryUsed, 2) . " MB\n";
    echo "  Groups created: " . count($actual) . "\n\n";
    
    return array(
        'time' => $executionTime,
        'memory' => $memoryUsed,
        'groups' => count($actual)
    );
}

// Test combined operations
function testCombinedOperations($dataSize = 5000) {
    echo "Testing combined WHERE + SELECT + OrderBy with $dataSize records...\n";
    
    $testData = generateTestData($dataSize);
    
    // Measure performance
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    $target = ArrayWrapper::Wrap($testData);
    $actual = $target
        ->where(function($item) { return $item['key'] < 250; })
        ->select(function($item) { return array('key' => $item['key'], 'doubled_value' => $item['value'] * 2); })
        ->orderBy(array(array("key" => "key", "desc" => false)))
        ->toVar();
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
    
    echo "  Execution time: " . number_format($executionTime, 2) . " ms\n";
    echo "  Memory used: " . number_format($memoryUsed, 2) . " MB\n";
    echo "  Results: " . count($actual) . " records\n\n";
    
    return array(
        'time' => $executionTime,
        'memory' => $memoryUsed,
        'results' => count($actual)
    );
}

// Run performance tests
echo "Running performance tests...\n\n";

// Test with different data sizes
$orderBySizes = array(1000, 5000, 10000);
$groupBySizes = array(500, 1000, 2000);

echo "=== OrderBy Performance Tests ===\n";
foreach ($orderBySizes as $size) {
    testOrderByPerformance($size);
}

echo "=== GroupBy Performance Tests ===\n";
foreach ($groupBySizes as $size) {
    testGroupByPerformance($size);
}

echo "=== Combined Operations Test ===\n";
testCombinedOperations(5000);

echo "Performance testing completed!\n";
echo "\nOptimizations implemented:\n";
echo "- Replaced O(n²) binary search insertion with O(n log n) native PHP usort()\n";
echo "- Optimized compare function with inlined logic\n";
echo "- Implemented hash-based grouping for better performance\n";
echo "- Reduced redundant array operations (array_splice, array_push)\n";
echo "- Batch processing for OrderBy operations\n";
echo "- Memory optimizations with array shortcuts\n";
?>