<?php

namespace NAL_6295\Collections;

use Exception;
//配列をラッピングしてmap,reduce,filter,group処理を行います。
//group,reduce,toVarでラッピングが外れます。
class ArrayWrapper 
{

	private $_source = null;
	private $_functions = null;

	const FILTER = 0;
	const MAP = 1;
	const REDUCE = 2;
	const GROUP_BY = 3;
	const JOIN = 4;
	const ORDER_BY = 5;

	function __construct($source){
		if(!is_array($source)){
			throw new Exception("$source is not array.");
		}
		$this->_source = $source;
	}

	private function compare($left,$right,$keys)
	{
		foreach($keys as $key){
			if($left[$key["key"]] > $right[$key["key"]]){
				if($key["desc"] == false){
					return -1;
				}
				return 1;
			}elseif($left[$key["key"]] < $right[$key["key"]]){
				if($key["desc"] == true){
					return -1;
					break;
				}
				return 1;
			}
		}
		return 0;
	}


	private function _addNewGroup($keyList,$value){
		foreach($keyList as $groupKey){
			$groupKeys[$groupKey["key"]] = $value[$groupKey["key"]];
		}
		return array("keys" => $groupKeys,"values" => array($value));
	}	
	
	private function _grouping(&$groups,$value,$groupKeys){
		$arrayCount = count($groups);
		if($arrayCount == 0){
			$groups[] = $this->_addNewGroup($groupKeys,$value);		
			return;
		}

		$start = 0;

		$target = floor($arrayCount / 2);
		while(true)
		{
			$arrayValue = $groups[$target];
			switch (self::compare($arrayValue["keys"],$value,$groupKeys)) 
			{
				case -1:
					if($target - $start > 1)
					{
						$target = $target - floor(($target - $start) / 2);
					}
					else if(self::compare($groups[$start]["keys"],$value,$groupKeys) == -1)
					{
						array_splice($groups,$start,0,array($this->_addNewGroup($groupKeys,$value)));
						return;
					}
					else
					{
						array_splice($groups,$target,0,array($this->_addNewGroup($groupKeys,$value)));
						return;							
					}
					break;
				case 0;	
					array_push($groups[$target]["values"],$value);
					return;
					break;
				default:
					if($arrayCount - $target > 1)
					{
						$start = $target;
						$target = $target + floor(($arrayCount - $target) /2); 
					}
					else if(self::compare($groups[$arrayCount -1]["keys"],$value,$groupKeys) == -1)
					{
						array_splice($groups,$arrayCount -1,0,array($this->_addNewGroup($groupKeys,$value)));
						return;
					}
					else
					{
						array_push($groups,$this->_addNewGroup($groupKeys,$value));
						return;							
					}
					break;
			}			
		}

	}


	private function _join(&$newArray,$leftValue,$joinInfo){
		$rightValues = $joinInfo["right"];
		$leftKey = $joinInfo["leftKey"];
		$rightKey = $joinInfo["rightKey"];
		$map = $joinInfo["map"];
		foreach ($rightValues as $rightValue) {
			$isSame = true;
			for($i = 0;$i < count($leftKey);$i++){
				if($leftValue[$leftKey[$i]] != $rightValue[$rightKey[$i]] ){
					$isSame = false;
					break;
				}
			}
			if($isSame){
				array_push($newArray,$map($leftValue,$rightValue));
			}
		}
	}




	private function _orderBy(&$newArray,$value,$orderKeys)
	{

		$arrayCount = count($newArray);
		if($arrayCount == 0)
		{
			array_push($newArray, $value);
			return;
		}

		$start = 0;

		$target = floor($arrayCount / 2);
		while(true)
		{
			$arrayValue = $newArray[$target];
			switch (self::compare($arrayValue,$value,$orderKeys)) 
			{
				case -1:
					if($target - $start > 1)
					{
						$target = $target - floor(($target - $start) / 2);
					}
					else if(self::compare($newArray[$start],$value,$orderKeys) == -1)
					{
						array_splice($newArray,$start,0,array($value));
						return;
					}
					else
					{
						array_splice($newArray,$target,0,array($value));
						return;							
					}
					break;
				case 0;	
					array_splice($newArray,$target+1,0,array($value));
					return;
					break;
				default:
					if($arrayCount - $target > 1)
					{
						$start = $target;
						$target = $target + floor(($arrayCount - $target) /2); 
					}
					else if(self::compare($newArray[$arrayCount -1],$value,$orderKeys) == -1)
					{
						array_splice($newArray,$arrayCount -1,0,array($value));
						return;
					}
					else
					{
						array_push($newArray,$value);
						return;							
					}
					break;
			}			
		}

	}

	public function toVar(){
		
		$reduceResult = 0;
		$isReduce = false;
		$groups = array();
		$newArray = array();
		if($this->_functions == null){
			return $this->_source;
		}

		foreach($this->_source as $value){
			$isExcept = false;
			foreach($this->_functions as $function){
				if($function["key"] == arrayWrapper::FILTER){
					if(!$function["value"]($value)){
						$isExcept = true;
						break;
					}
				}else if($function["key"] == arrayWrapper::MAP){
					$value = $function["value"]($value);
				}else if($function["key"] == arrayWrapper::REDUCE){
					$reduceResult = $function["value"]($reduceResult,$value);
					$isReduce = true;
				}else if($function["key"] == arrayWrapper::GROUP_BY){
					$this->_grouping($groups,$value,$function["value"]);
				}else if($function["key"] == ArrayWrapper::JOIN){
					$isExcept = true;
					$this->_join($newArray,$value,$function["value"]);
				}else if($function["key"] == ArrayWrapper::ORDER_BY){
					$isExcept = true;
					$this->_orderBy($newArray,$value,$function["value"]);
				}
			}
			if(!$isExcept){
				$newArray[] = $value;
			}
		}	
		if($isReduce){
			return $reduceResult;
		}
		if(count($groups) != 0){
			return $groups;
		}

		return $newArray;
	}
	
	public function filter($predicate){
		if(!is_callable($predicate)){
			throw new Exception("$predicate is not function.");
		}
		$this->_functions[] = array("key" => arrayWrapper::FILTER,"value" => $predicate);
		return $this;
	}

	public function map($mapper){
		if(!is_callable($mapper)){
			throw new Exception("$mapper is not function.");
		}
		$this->_functions[] = array("key" => arrayWrapper::MAP,"value" => $mapper);
		return $this;
	}

	public function groupBy($keys){
		if(!is_array($keys)){
			throw new Exception("$keys is not array.");
		}

		foreach($keys as $key)
		{
			$groupKeys[] = array("key" => $key,"desc" => false);
		}


		$this->_functions[] = array("key" => arrayWrapper::GROUP_BY,"value" => $groupKeys);
		return new ArrayWrapper($this->toVar());
	}

	public function reduce($reducer){
		if(!is_callable($reducer)){
			throw new Exception("$reducer is not function.");
		}
		$this->_functions[] = array("key" => arrayWrapper::REDUCE,"value" => $reducer);
		return $this->toVar();
	}

	public function join($right,$leftKey,$rightKey,$map){
#region "事前条件"
		if(!is_array($right)){
			throw new Exception("$right is not array");
		}

		if(!is_array($leftKey)){
			throw new Exception("$leftKey is not array");
		}

		if(!is_array($rightKey)){
			throw new Exception("$rightKey is not array");
		}

		if(!count($leftKey) == count($rightKey)){
			throw new Exception("$leftKey count diferrent $rightKey count.");
		}

		if(!is_callable($map)){
			throw new Exception("$map is not function");
		}
#end region
		$this->_functions[] = array("key" => ArrayWrapper::JOIN,
									"value" => array(
												"right" => $right ,
												"leftKey" => $leftKey,
												"rightKey" => $rightKey,
												"map"	=> $map));

		return new ArrayWrapper($this->toVar());
	}

	 public function orderBy($orderKey)
	{
		$this->_functions[] = array("key" => ArrayWrapper::ORDER_BY,
									"value" => $orderKey);
		return new ArrayWrapper($this->toVar());
	}
}
?>
