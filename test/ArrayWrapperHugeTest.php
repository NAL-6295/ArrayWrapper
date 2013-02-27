<?php
require_once 'PHPUnit/Autoload.php';
require_once 'ArrayWrapper.php';

use NAL_6295\Collections\ArrayWrapper;

class ArrayWrapperHugeTest extends PHPUnit_Framework_TestCase
{

	var $targetSource = array();
	var $expected = array();
	public function setUp()
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

		$actual = $target
				->orderBy(array(
						array("key" => "key","desc" => false),
						array("key" => "key2","desc" => false)
						))
				->toVar();

		// $actual = $this->targetSource;

		// foreach ($actual as $row) {
		//     $key[]  = $row['key'];
		//     $key2[] = $row['key2'];
		// }
		// array_multisort($key, SORT_ASC, $key2, SORT_ASC, $actual);

		$this->assertEquals(json_encode($this->expected),json_encode($actual));

	}

}
?>
