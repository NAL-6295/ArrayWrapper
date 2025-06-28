<?php

declare(strict_types=1);

require_once __DIR__ . '/../ArrayWrapper.php';

use NAL_6295\Collections\ArrayWrapper;
use NAL_6295\Collections\JoinType;

/**
 * Simple test runner for ArrayWrapper modernized code
 */
class ArrayWrapperTest
{
	private int $testCount = 0;
	private int $passedTests = 0;

	private function assertEquals(mixed $expected, mixed $actual, string $testName): void
	{
		$this->testCount++;
		if ($expected === $actual) {
			$this->passedTests++;
			echo "✅ {$testName} - PASSED\n";
		} else {
			echo "❌ {$testName} - FAILED\n";
			echo "   Expected: " . var_export($expected, true) . "\n";
			echo "   Actual:   " . var_export($actual, true) . "\n";
		}
	}

	public function testWhere(): void
	{
		$target = ArrayWrapper::Wrap([1,2,3,4,5,6,7,8,9,10]);
		$actual = $target->where(fn($x) => $x > 5)->toVar();
		$expected = [6,7,8,9,10];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testWhere');
	}

	public function testSelect(): void
	{
		$target = ArrayWrapper::Wrap([1,2,3,4,5,6,7,8,9,10]);
		$actual = $target->select(fn($x) => $x * 2)->toVar();
		$expected = [2,4,6,8,10,12,14,16,18,20];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testSelect');
	}

	public function testWhereSelect(): void
	{
		$target = ArrayWrapper::Wrap([1,2,3,4,5,6,7,8,9,10]);
		$actual = $target
			->where(fn($x) => $x > 5)
			->select(fn($x) => $x * 2)
			->toVar();
		$expected = [12,14,16,18,20];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testWhereSelect');
	}

	public function testWhereSelectWhere(): void
	{
		$target = ArrayWrapper::Wrap([1,2,3,4,5,6,7,8,9,10]);
		$actual = $target
			->where(fn($x) => $x > 5)
			->select(fn($x) => $x * 2)
			->where(fn($x) => $x > 12)
			->toVar();
		$expected = [14,16,18,20];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testWhereSelectWhere');
	}

	public function testReduce(): void
	{
		$target = ArrayWrapper::Wrap([1,2,3,4,5,6,7,8,9,10]);
		$actual = $target
			->where(fn($x) => $x > 5)
			->select(fn($x) => $x * 2)
			->reduce(fn($x, $y) => $x + $y);
		$expected = 80;
		$this->assertEquals($expected, $actual, 'testReduce');
	}

	public function testJsonType(): void
	{
		$target = ArrayWrapper::Wrap([
			["key" => 1, "value" => 10],
			["key" => 2, "value" => 11],
			["key" => 3, "value" => 12],
			["key" => 4, "value" => 13],
			["key" => 5, "value" => 14]
		]);

		$actual = $target
			->where(fn($x) => $x["key"] > 2)
			->select(fn($x) => ["K" => $x["key"], "V" => $x["value"] * 2])
			->where(fn($x) => $x["K"] > 3)
			->toVar();
		$expected = [
			["K" => 4, "V" => 26],
			["K" => 5, "V" => 28]
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testJsonType');
	}

	public function testGroupBy(): void
	{
		$target = ArrayWrapper::Wrap([
			["id" => 2, "value" => 10],
			["id" => 2, "value" => 11],
			["id" => 3, "value" => 12],
			["id" => 3, "value" => 13],
			["id" => 5, "value" => 14]
		]);

		$actual = $target->groupBy(["id"])->toVar();
		$expected = [
			["keys" => ["id" => 2], "values" => [
				["id" => 2, "value" => 10],
				["id" => 2, "value" => 11]
			]],
			["keys" => ["id" => 3], "values" => [
				["id" => 3, "value" => 12],
				["id" => 3, "value" => 13]
			]],
			["keys" => ["id" => 5], "values" => [
				["id" => 5, "value" => 14]
			]]
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testGroupBy');
	}

	public function testGroupBySumAverage(): void
	{
		$target = ArrayWrapper::Wrap([
			["key" => 2, "value" => 10],
			["key" => 2, "value" => 11],
			["key" => 3, "value" => 12],
			["key" => 3, "value" => 13],
			["key" => 5, "value" => 14]
		]);

		$actual = $target
			->groupBy(["key"])
			->select(fn($x) => [
				"keys" => $x["keys"],
				"value" => ArrayWrapper::Wrap($x["values"])->sum("value"),
				"avg" => ArrayWrapper::Wrap($x["values"])->average("value")
			])
			->toVar();

		$expected = [
			["keys" => ["key" => 2], "value" => 21, "avg" => 10.5],
			["keys" => ["key" => 3], "value" => 25, "avg" => 12.5],
			["keys" => ["key" => 5], "value" => 14, "avg" => 14]
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testGroupBySumAverage');
	}

	public function testJoin(): void
	{
		$leftArray = [
			["key" => 2, "name" => "Nasal Hair Cutter"],
			["key" => 3, "name" => "scissors"],
			["key" => 5, "name" => "knife"]
		];

		$rightArray = [
			["id" => 1, "item_id" => 2, "value" => 10],
			["id" => 2, "item_id" => 2, "value" => 20],
			["id" => 3, "item_id" => 2, "value" => 30],
			["id" => 4, "item_id" => 3, "value" => 40],
			["id" => 5, "item_id" => 3, "value" => 50],
			["id" => 6, "item_id" => 5, "value" => 60],
			["id" => 7, "item_id" => 5, "value" => 70],
		];

		$target = ArrayWrapper::Wrap($leftArray);

		$actual = $target
			->join(
				$rightArray,
				["key"],
				["item_id"],
				fn($leftValue, $rightValue) => [
					"item_id" => $rightValue["item_id"],
					"name" => $leftValue["name"],
					"value" => $rightValue["value"]
				]
			)
			->toVar();

		$expected = [
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 10],
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 20],
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 30],
			["item_id" => 3, "name" => "scissors", "value" => 40],
			["item_id" => 3, "name" => "scissors", "value" => 50],
			["item_id" => 5, "name" => "knife", "value" => 60],
			["item_id" => 5, "name" => "knife", "value" => 70],
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testJoin');
	}

	public function testJoinCallableKey(): void
	{
		$leftArray = [
			["key" => 2, "name" => "Nasal Hair Cutter"],
			["key" => 3, "name" => "scissors"],
			["key" => 5, "name" => "knife"]
		];

		$rightArray = [
			["id" => 1, "item_id" => 2, "value" => 10],
			["id" => 2, "item_id" => 2, "value" => 20],
			["id" => 3, "item_id" => 2, "value" => 30],
			["id" => 4, "item_id" => 3, "value" => 40],
			["id" => 5, "item_id" => 3, "value" => 50],
			["id" => 6, "item_id" => 5, "value" => 60],
			["id" => 7, "item_id" => 5, "value" => 70],
		];

		$target = ArrayWrapper::Wrap($leftArray);

		$actual = $target
			->join(
				$rightArray,
				["key"],
				[fn($x) => $x["item_id"]],
				fn($leftValue, $rightValue) => [
					"item_id" => $rightValue["item_id"],
					"name" => $leftValue["name"],
					"value" => $rightValue["value"]
				]
			)
			->toVar();

		$expected = [
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 10],
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 20],
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 30],
			["item_id" => 3, "name" => "scissors", "value" => 40],
			["item_id" => 3, "name" => "scissors", "value" => 50],
			["item_id" => 5, "name" => "knife", "value" => 60],
			["item_id" => 5, "name" => "knife", "value" => 70],
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testJoinCallableKey');
	}

	public function testLeftJoin(): void
	{
		$leftArray = [
			["key" => 2, "name" => "Nasal Hair Cutter"],
			["key" => 3, "name" => "scissors"],
			["key" => 5, "name" => "knife"]
		];

		$rightArray = [
			["id" => 1, "item_id" => 2, "value" => 10],
			["id" => 2, "item_id" => 2, "value" => 20],
			["id" => 3, "item_id" => 2, "value" => 30],
			["id" => 6, "item_id" => 5, "value" => 60],
			["id" => 7, "item_id" => 5, "value" => 70],
		];

		$target = ArrayWrapper::Wrap($leftArray);

		$actual = $target
			->join(
				$rightArray,
				["key"],
				["item_id"],
				fn($leftValue, $rightValue) => [
					"item_id" => $leftValue["key"],
					"name" => $leftValue["name"],
					"value" => isset($rightValue) ? $rightValue["value"] : 0
				],
				JoinType::LEFT
			)
			->toVar();

		$expected = [
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 10],
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 20],
			["item_id" => 2, "name" => "Nasal Hair Cutter", "value" => 30],
			["item_id" => 3, "name" => "scissors", "value" => 0],
			["item_id" => 5, "name" => "knife", "value" => 60],
			["item_id" => 5, "name" => "knife", "value" => 70],
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testLeftJoin');
	}

	public function testOrderBy(): void
	{
		$target = ArrayWrapper::Wrap([
			["key" => 2, "value" => 10],
			["key" => 5, "value" => 11],
			["key" => 1, "value" => 12],
			["key" => 3, "value" => 13],
			["key" => 7, "value" => 14]
		]);

		$actual = $target
			->orderBy([["key" => "key", "desc" => false]])
			->toVar();

		$expected = [
			["key" => 1, "value" => 12],
			["key" => 2, "value" => 10],
			["key" => 3, "value" => 13],
			["key" => 5, "value" => 11],
			["key" => 7, "value" => 14]
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testOrderBy');
	}

	public function testOrderByDesc(): void
	{
		$target = ArrayWrapper::Wrap([
			["key" => 2, "value" => 10],
			["key" => 5, "value" => 11],
			["key" => 1, "value" => 12],
			["key" => 3, "value" => 13],
			["key" => 7, "value" => 14]
		]);

		$actual = $target
			->orderBy([["key" => "key", "desc" => true]])
			->toVar();

		$expected = [
			["key" => 7, "value" => 14],
			["key" => 5, "value" => 11],
			["key" => 3, "value" => 13],
			["key" => 2, "value" => 10],
			["key" => 1, "value" => 12],
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testOrderByDesc');
	}

	public function testOrderByMultiKey(): void
	{
		$target = ArrayWrapper::Wrap([
			["key" => 2, "key2" => 2, "value" => 10],
			["key" => 3, "key2" => 5, "value" => 11],
			["key" => 2, "key2" => 1, "value" => 12],
			["key" => 1, "key2" => 3, "value" => 13],
			["key" => 3, "key2" => 7, "value" => 14]
		]);

		$actual = $target
			->orderBy([
				["key" => "key", "desc" => true],
				["key" => "key2", "desc" => false]
			])
			->toVar();

		$expected = [
			["key" => 3, "key2" => 5, "value" => 11],
			["key" => 3, "key2" => 7, "value" => 14],
			["key" => 2, "key2" => 1, "value" => 12],
			["key" => 2, "key2" => 2, "value" => 10],
			["key" => 1, "key2" => 3, "value" => 13]
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testOrderByMultiKey');
	}

	public function testZip(): void
	{
		$leftArray = [
			["key" => 2, "name" => "Nasal Hair Cutter"],
			["key" => 3, "name" => "scissors"],
			["key" => 5, "name" => "knife"]
		];

		$rightArray = [
			["id" => 1, "item_id" => 2, "value" => 10],
			["id" => 2, "item_id" => 2, "value" => 20],
			["id" => 3, "item_id" => 2, "value" => 30],
			["id" => 4, "item_id" => 3, "value" => 40],
			["id" => 5, "item_id" => 3, "value" => 50],
			["id" => 6, "item_id" => 5, "value" => 60],
			["id" => 7, "item_id" => 5, "value" => 70],
		];

		$target = ArrayWrapper::Wrap($leftArray);

		$actual = $target
			->zip(
				$rightArray,
				fn($leftValue, $rightValue) => [
					"left" => $leftValue,
					"right" => $rightValue
				]
			)
			->toVar();

		$expected = [
			["left" => ["key" => 2, "name" => "Nasal Hair Cutter"], "right" => ["id" => 1, "item_id" => 2, "value" => 10]],
			["left" => ["key" => 3, "name" => "scissors"], "right" => ["id" => 2, "item_id" => 2, "value" => 20]],
			["left" => ["key" => 5, "name" => "knife"], "right" => ["id" => 3, "item_id" => 2, "value" => 30]],
		];
		$this->assertEquals(json_encode($expected), json_encode($actual), 'testZip');
	}

	public function runAllTests(): void
	{
		echo "\n=== ArrayWrapper Test Suite - PHP 8.x Modern Version ===\n\n";

		$this->testWhere();
		$this->testSelect();
		$this->testWhereSelect();
		$this->testWhereSelectWhere();
		$this->testReduce();
		$this->testJsonType();
		$this->testGroupBy();
		$this->testGroupBySumAverage();
		$this->testJoin();
		$this->testJoinCallableKey();
		$this->testLeftJoin();
		$this->testOrderBy();
		$this->testOrderByDesc();
		$this->testOrderByMultiKey();
		$this->testZip();

		echo "\n=== Test Results ===\n";
		echo "Tests Run: {$this->testCount}\n";
		echo "Tests Passed: {$this->passedTests}\n";
		echo "Tests Failed: " . ($this->testCount - $this->passedTests) . "\n";

		if ($this->passedTests === $this->testCount) {
			echo "\n🎉 ALL TESTS PASSED! The modernized PHP code works perfectly!\n";
		} else {
			echo "\n❌ Some tests failed. Please check the implementation.\n";
		}
	}
}

// Run tests
$testRunner = new ArrayWrapperTest();
$testRunner->runAllTests();
?>
