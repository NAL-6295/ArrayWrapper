<?php
require_once 'ArrayWrapper.php';

use NAL_6295\Collections\ArrayWrapper;
use PHPUnit\Framework\TestCase;

class ArrayWrapperHugeTest extends TestCase
{

	var $targetSource = array();
	var $expected = array();
	public function setUp(): void
	{
		$this->targetSource = array();
		$this->expected = array();
		$count = 10000;
		for ($i=0; $i < $count; $i++) { 
			$value = array("key" => floor($i / 10) ,"key2" => $i,"value" => $i * $i);
			if($i % 2 == 0)
			{
				array_push($this->targetSource ,$value);
			}
			else
			{
				array_splice($this->targetSource,0,0,array($value));				
			}
		}
		for ($i=0; $i < $count; $i++) { 
			array_push($this->expected,array("key" => floor($i / 10) ,"key2" => $i,"value" => $i * $i));
		}
						

	}

	public function testOrderByHugeData()
	{		

		$target = new ArrayWrapper($this->targetSource);

		$startTime = microtime(true);
		$actual = $target
				->orderBy(array(
						array("key" => "key","desc" => false),
						array("key" => "key2","desc" => false)
						))
				->toVar();
		$endTime = microtime(true);
		$executionTime = $endTime - $startTime;
		echo "Execution time for orderBy: " . $executionTime . " seconds\n";

		// $actual = $this->targetSource;

		// foreach ($actual as $row) {
		//     $key[]  = $row['key'];
		//     $key2[] = $row['key2'];
		// }
		// array_multisort($key, SORT_ASC, $key2, SORT_ASC, $actual);

		$this->assertEquals(json_encode($this->expected),json_encode($actual));

	}

	public function testGroupByHugeData()
	{
		$target = new ArrayWrapper($this->targetSource);
		$groupKeys = array("key"); // Group by the 'key' field

		$startTime = microtime(true);
		$groupedResult = $target->groupBy($groupKeys)->toVar();
		$endTime = microtime(true);

		$executionTime = $endTime - $startTime;
		echo "Execution time for groupBy: " . $executionTime . " seconds\n";

		// Assertions:
		// 1. Check the number of groups.
		//    $count = 10000; floor($i/10) gives 1000 unique keys (0-999)
		$this->assertEquals(1000, count($groupedResult));

		// 2. Check if one group has the expected structure and count of items.
		//    For example, group with key '0' (floor($i/10) == 0) should have 10 items.
		$firstGroup = null;
		foreach ($groupedResult as $group) {
			if (isset($group['keys']['key']) && $group['keys']['key'] == 0) {
				$firstGroup = $group;
				break;
			}
		}
		$this->assertNotNull($firstGroup, "Group with key '0' not found.");
		$this->assertEquals(0, $firstGroup['keys']['key']);
		$this->assertEquals(10, count($firstGroup['values']));
	}
}
?>
