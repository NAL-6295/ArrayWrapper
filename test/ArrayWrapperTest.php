<?php
require_once 'PHPUnit/Autoload.php';
require_once 'ArrayWrapper.php';

use NAL_6295\Collections\ArrayWrapper;

class ArrayWrapperTest extends PHPUnit_Framework_TestCase
{
	public function testWhere()
	{	
		$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	
		$actual = $target->where(function($x){return $x > 5;})->toVar();
		$expected = array(6,7,8,9,10);
		$this->assertEquals(json_encode($expected),json_encode($actual));
	}
	public function testSelect()
	{	
		$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	
		$actual = $target->select(function($x){return $x *2;})->toVar();
		$expected = array(2,4,6,8,10,12,14,16,18,20);
		$this->assertEquals(json_encode($expected),json_encode($actual));
	}
	public function testWhereSelect()
	{	
		$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	
		$actual = $target
				->where(function($x){return $x > 5;})
				->select(function($x){return $x *2;})->toVar();
		$expected = array(12,14,16,18,20);
		$this->assertEquals(json_encode($expected),json_encode($actual));
	}
	public function testWhereSelectWhere()
	{	
		$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	
		$actual = $target
				->where(function($x){return $x > 5;})
				->select(function($x){return $x *2;})
				->where(function($x){return $x > 12;})
				->toVar();
		$expected = array(14,16,18,20);
		$this->assertEquals(json_encode($expected),json_encode($actual));
	}
	public function testReduce()
	{	
		$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	
		$actual = $target
				->where(function($x){return $x > 5;})
				->select(function($x){return $x *2;})
				->reduce(function($x,$y){return $x + $y;});
		$expected = 80;
		$this->assertEquals($expected,$actual);
	}	
	public function testJsonType()
	{
		$target = ArrayWrapper::Wrap(
				array(
					array("key" => 1,"value" => 10),
					array("key" => 2,"value" => 11),
					array("key" => 3,"value" => 12),
					array("key" => 4,"value" => 13),
					array("key" => 5,"value" => 14)	
				)
			);

		$actual = $target
				->where(function($x){return $x["key"] > 2;})
				->select(function($x){return array("K" => $x["key"],"V" => $x["value"] * 2);})
				->where(function($x){return $x["K"] > 3;})
				->toVar();
		$expected = array(
				array("K" => 4,"V" => 26),
				array("K" => 5,"V" => 28)
				);
		$this->assertEquals(json_encode($expected),json_encode($actual));
		
	}
	public function testGroupBy()
	{
		$target = ArrayWrapper::Wrap(
				array(
					array("id" => 2,"value" => 10),
					array("id" => 2,"value" => 11),
					array("id" => 3,"value" => 12),
					array("id" => 3,"value" => 13),
					array("id" => 5,"value" => 14)	
				)
			);

		$actual = $target
				->groupBy(array("id"))
				->toVar();
		$expected = array(
				array("keys" => array("id" => 2),"values" => array(
					array("id" => 2,"value" => 10),
					array("id" => 2,"value" => 11)
					)),
				array("keys" => array("id" => 3),"values" => array(
					array("id" => 3,"value" => 12),
					array("id" => 3,"value" => 13),
					)),
				array("keys" => array("id" => 5),"values" => array(
					array("id" => 5,"value" => 14)	
					))
				);					
		$this->assertEquals(json_encode($expected),json_encode($actual));
		


	}

	public function testGroupBySumAverage()
	{
		$target = ArrayWrapper::Wrap(
				array(
					array("key" => 2,"value" => 10),
					array("key" => 2,"value" => 11),
					array("key" => 3,"value" => 12),
					array("key" => 3,"value" => 13),
					array("key" => 5,"value" => 14)	
				)
			);
		$actual = $target
				->groupBy(array("key"))
				->select
				(
					function($x)
					{
						$target = new ArrayWrapper($x["values"]);
						return array("keys" => $x["keys"],
									"value" => $target->sum("value"),
									"avg" => $target->average("value"));
				   }
				)
				->toVar();
		$expected = array(
				array("keys" => array("key" => 2),"value" => 21 ,"avg" => 10.5),
				array("keys" => array("key" => 3),"value" => 25 ,"avg" => 12.5),
				array("keys" => array("key" => 5),"value" => 14 ,"avg" => 14)
				);			

		$this->assertEquals(json_encode($expected),json_encode($actual));
	}

	public function testJoin()
	{
		$leftArray = array(
					array("key" => 2,"name" => "Nasal Hair Cutter"),
					array("key" => 3,"name" => "scissors"),
					array("key" => 5,"name" => "knife")	
				);

		$rightArray = array(
				array("id" => 1,"item_id" => 2,"value" => 10),
				array("id" => 2,"item_id" => 2,"value" => 20),
				array("id" => 3,"item_id" => 2,"value" => 30),
				array("id" => 4,"item_id" => 3,"value" => 40),
				array("id" => 5,"item_id" => 3,"value" => 50),
				array("id" => 6,"item_id" => 5,"value" => 60),
				array("id" => 7,"item_id" => 5,"value" => 70),
			);

		$target = ArrayWrapper::Wrap($leftArray);

		$actual = $target
				->join($rightArray,
						array("key"),
						array("item_id"),
						function ($leftValue,$rightValue)
						{

							return 
								array("item_id" => $rightValue["item_id"],
										 "name" => $leftValue["name"],
										 "value" => $rightValue["value"]);
						})
				->toVar();

		$expected = array(
					array("item_id" => 2,"name" => "Nasal Hair Cutter","value" => 10),
					array("item_id" => 2,"name" => "Nasal Hair Cutter","value" => 20),
					array("item_id" => 2,"name" => "Nasal Hair Cutter","value" => 30),
					array("item_id" => 3,"name" => "scissors","value" => 40),
					array("item_id" => 3,"name" => "scissors","value" => 50),
					array("item_id" => 5,"name" => "knife","value" => 60),
					array("item_id" => 5,"name" => "knife","value" => 70),
				);					
		$this->assertEquals(json_encode($expected),json_encode($actual));
		
	}

	public function testOrderBy()
	{
		$target = ArrayWrapper::Wrap(
				array(
					array("key" => 2,"value" => 10),
					array("key" => 5,"value" => 11),
					array("key" => 1,"value" => 12),
					array("key" => 3,"value" => 13),
					array("key" => 7,"value" => 14)	
				)
			);

		$actual = $target
				->orderBy(array(array("key" => "key","desc" => false)))
				->toVar();
		$expected = array(
					array("key" => 1,"value" => 12),
					array("key" => 2,"value" => 10),
					array("key" => 3,"value" => 13),
					array("key" => 5,"value" => 11),
					array("key" => 7,"value" => 14)	
				);					
		$this->assertEquals(json_encode($expected),json_encode($actual));
		


	}

	public function testOrderByDesc()
	{
		$target = ArrayWrapper::Wrap(
				array(
					array("key" => 2,"value" => 10),
					array("key" => 5,"value" => 11),
					array("key" => 1,"value" => 12),
					array("key" => 3,"value" => 13),
					array("key" => 7,"value" => 14)	
				)
			);

		$actual = $target
				->orderBy(array(array("key" => "key","desc" => true)))
				->toVar();
		$expected = array(
					array("key" => 7,"value" => 14),
					array("key" => 5,"value" => 11),
					array("key" => 3,"value" => 13),
					array("key" => 2,"value" => 10),
					array("key" => 1,"value" => 12),
				);					
		$this->assertEquals(json_encode($expected),json_encode($actual));
		


	}

	public function testOrderByMultiKey()
	{

		$target = ArrayWrapper::Wrap(
				array(
					array("key" => 2, "key2" => 2,"value" => 10),
					array("key" => 3,"key2" => 5,"value" => 11),
					array("key" => 2,"key2" => 1,"value" => 12),
					array("key" => 1,"key2" => 3,"value" => 13),
					array("key" => 3,"key2" => 7,"value" => 14)	
				)
			);

		$actual = $target
				->orderBy(array(
						array("key" => "key","desc" => true),
						array("key" => "key2","desc" => false)
						))
				->toVar();
		$expected = array(
					array("key" => 3,"key2" => 5,"value" => 11),
					array("key" => 3,"key2" => 7,"value" => 14),
					array("key" => 2,"key2" => 1,"value" => 12),
					array("key" => 2, "key2" => 2,"value" => 10),
					array("key" => 1,"key2" => 3,"value" => 13)
				);					
		$this->assertEquals(json_encode($expected),json_encode($actual));

	}

	public function testZip()
	{
		$leftArray = array(
					array("key" => 2,"name" => "Nasal Hair Cutter"),
					array("key" => 3,"name" => "scissors"),
					array("key" => 5,"name" => "knife")	
				);

		$rightArray = array(
				array("id" => 1,"item_id" => 2,"value" => 10),
				array("id" => 2,"item_id" => 2,"value" => 20),
				array("id" => 3,"item_id" => 2,"value" => 30),
				array("id" => 4,"item_id" => 3,"value" => 40),
				array("id" => 5,"item_id" => 3,"value" => 50),
				array("id" => 6,"item_id" => 5,"value" => 60),
				array("id" => 7,"item_id" => 5,"value" => 70),
			);

		$target = ArrayWrapper::Wrap($leftArray);

		$actual = $target
				->zip($rightArray,
						function ($leftValue,$rightValue)
						{

							return 
								array("left" => $leftValue,"right" => $rightValue);
						})
				->toVar();

		$expected = array(
					array("left" => array("key" => 2,"name" => "Nasal Hair Cutter"),"right" => array("id" => 1,"item_id" => 2,"value" => 10)),
					array("left" => array("key" => 3,"name" => "scissors"),"right" => array("id" => 2,"item_id" => 2,"value" => 20)),
					array("left" => array("key" => 5,"name" => "knife")	,"right" => array("id" => 3,"item_id" => 2,"value" => 30)),
				);					
		$this->assertEquals(json_encode($expected),json_encode($actual));
		
	}

}
?>
