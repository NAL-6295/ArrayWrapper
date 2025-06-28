<?php

declare(strict_types=1);

require_once __DIR__ . '/../ArrayWrapper.php';

use NAL_6295\Collections\ArrayWrapper;

/**
 * Large dataset performance test for ArrayWrapper modernized code
 */
class ArrayWrapperHugeTest
{
	private array $targetSource = [];
	private array $expected = [];

	public function setUp(): void
	{
		$this->targetSource = [];
		$this->expected = [];
		$count = 10000;
		
		for ($i = 0; $i < $count; $i++) {
			$value = ["key" => intval(floor($i / 10)), "key2" => $i, "value" => $i * $i];
			if ($i % 2 == 0) {
				array_push($this->targetSource, $value);
			} else {
				array_splice($this->targetSource, 0, 0, [$value]);
			}
		}
		
		for ($i = 0; $i < $count; $i++) {
			array_push($this->expected, ["key" => intval(floor($i / 10)), "key2" => $i, "value" => $i * $i]);
		}
	}

	public function testOrderByHugeData(): void
	{
		echo "Testing orderBy with large dataset (10,000 items)...\n";
		$start = microtime(true);
		
		        $target = ArrayWrapper::Wrap($this->targetSource);

		$actual = $target
			->orderBy([
				["key" => "key", "desc" => false],
				["key" => "key2", "desc" => false]
			])
			->toVar();

		$end = microtime(true);
		$executionTime = ($end - $start) * 1000; // Convert to milliseconds

		if (json_encode($this->expected) === json_encode($actual)) {
			echo "✅ testOrderByHugeData - PASSED\n";
			echo "   Execution time: " . number_format($executionTime, 2) . " ms\n";
			echo "   Items processed: " . count($actual) . "\n";
		} else {
			echo "❌ testOrderByHugeData - FAILED\n";
			echo "   Expected " . count($this->expected) . " items, got " . count($actual) . " items\n";
		}
	}

	public function runTest(): void
	{
		echo "\n=== ArrayWrapper Large Dataset Test - PHP 8.x Modern Version ===\n\n";
		
		echo "Setting up test data...\n";
		$this->setUp();
		
		$this->testOrderByHugeData();
		
		echo "\n=== Performance Test Complete ===\n";
	}
}

// Run performance test
$testRunner = new ArrayWrapperHugeTest();
$testRunner->runTest();
?>
