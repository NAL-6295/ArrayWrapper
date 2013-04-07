<?php
namespace NAL_6295\Collections;

require_once 'OperationType.php';
require_once 'JoinType.php';

use Exception;

/**
 * ArrayWrapperクラス
 * 
 * 配列をラッピングしてmap,reduce,filter,groupBy,orderBy,joinを行う。
 * toVar及びreduceを呼ぶまでは実行されません。  
 * 例外として、groupBy,orderByのみ実行した結果をArrayWrapperで返します。
 */
class ArrayWrapper 
{

	private $_source = null;
	private $_functions = null;

	const KEY = "key";
	const DESC = "desc";
	const GROUP_KEYS = "keys";
	const GROUP_VALUES = "values";

	public static function Wrap($source){
		if(!is_array($source)){
			throw new Exception("$source is not array.");
		}
		return new ArrayWrapper($source);
	}

	/**
	*	コンストラクタ
	*	@param array $source ラップしたい配列もしくは連想配列
	**/
	private function __construct($source){
		if(!is_array($source)){
			throw new Exception("$source is not array.");
		}
		$this->_source = $source;
	}

	/**
	*   配列同士のコンペア
	*   groupBy,orderByで利用
	**/
	private function compare($left,$right,$leftKeys,$rightKeys = null)
	{
		if(!isset($rightKeys)){
			$rightKeys = $leftKeys;
		}
		else
		{
		}
		$getValue = function($target,$key){
			if(is_string($key)){
				return $target[$key];
			}

			if(is_callable($key)){
				return $key($target);
			}
		};
		for ($i=0; $i < count($leftKeys); $i++) { 			
			$leftValue = $getValue($left,$leftKeys[$i][self::KEY]);
			$rightValue = $getValue($right,$rightKeys[$i][self::KEY]);
			if($leftValue > $rightValue){
				if($leftKeys[$i][self::DESC] == false){
					return -1;
				}
				return 1;
			}elseif($leftValue < $rightValue){
				if($leftKeys[$i][self::DESC] == true){
					return -1;
					break;
				}
				return 1;
			}
		}
		return 0;
	}

	/**
	* groupBy時に新しいgroupを作成する。
	*
	**/
	private function _addNewGroup($keyList,$value){
		foreach($keyList as $groupKey){
			$groupKeys[$groupKey[self::KEY]] = $value[$groupKey[self::KEY]];
		}
		return array(self::GROUP_KEYS => $groupKeys,self::GROUP_VALUES => array($value));
	}	
	
	/**
	* groupBy処理
	*
	**/
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
			switch (self::compare($arrayValue[self::GROUP_KEYS],$value,$groupKeys)) 
			{
				case -1:
					if($target - $start > 1)
					{
						$target = $target - floor(($target - $start) / 2);
					}
					else if(self::compare($groups[$start][self::GROUP_KEYS],$value,$groupKeys) == -1)
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
					array_push($groups[$target][self::GROUP_VALUES],$value);
					return;
					break;
				default:
					if($arrayCount - $target > 1)
					{
						$start = $target;
						$target = $target + floor(($arrayCount - $target) /2); 
					}
					else if(self::compare($groups[$arrayCount -1][self::GROUP_KEYS],$value,$groupKeys) == -1)
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

	/**
	* 配列同士をjoinする処理
	*　inner join,left joinに対応
	**/
	private function _join(&$newArray,$leftValue,$joinInfo){
		$rightValues = $joinInfo["right"];
		$leftKey = $joinInfo["leftKey"];
		$rightKey = $joinInfo["rightKey"];
		$map = $joinInfo["map"];
		$joinType = $joinInfo["joinType"];

		$isNotFound = true;
		foreach ($rightValues as $rightValue) 
		{
			if(self::compare($leftValue,$rightValue,$leftKey,$rightKey) == 0)
			{
				array_push($newArray,$map($leftValue,$rightValue));
				$isNotFound = false;
			}
		}
		if($isNotFound && $joinType == JoinType::LEFT){
			array_push($newArray,$map($leftValue,null));			
		}
	}

	/**
	* OrderBy処理
	*
	**/
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

	/**
	* 積み上げられた処理を行い、配列を返す
	*
	**/
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
				if($function[self::KEY] == OperationType::WHERE){
					if(!$function["value"]($value)){
						$isExcept = true;
						break;
					}
				}else if($function[self::KEY] == OperationType::SELECT){
					$value = $function["value"]($value);
				}else if($function[self::KEY] == OperationType::REDUCE){
					$reduceResult = $function["value"]($reduceResult,$value);
					$isReduce = true;
				}else if($function[self::KEY] == OperationType::GROUP_BY){
					$this->_grouping($groups,$value,$function["value"]);
					$isExcept = true;
				}else if($function[self::KEY] == OperationType::JOIN){
					$isExcept = true;
					$this->_join($newArray,$value,$function["value"]);
				}else if($function[self::KEY] == OperationType::ORDER_BY){
					$isExcept = true;
					$this->_orderBy($newArray,$value,$function["value"]);
				}
			}
			if(!$isExcept){
				$newArray[] = $value;
			}
		}	
		if($isReduce){
			array_pop($this->_functions);
			return $reduceResult;
		}
		if(count($groups) != 0){
			return $groups;
		}

		return $newArray;
	}
	
	/**
	* where処理の登録
	*
	* @param lambda $predicate function(配列要素){return 要素が対象かどうかの処理}
	**/
	public function where($predicate){
		if(!is_callable($predicate)){
			throw new Exception("$predicate is not function.");
		}
		$this->_functions[] = array(self::KEY => OperationType::WHERE,"value" => $predicate);
		return $this;
	}

	/**
	* select処理の登録
	*
	* @param lambda $mapper function(配列要素){return 加工した要素}
	**/
	public function select($mapper){
		if(!is_callable($mapper)){
			throw new Exception("$mapper is not function.");
		}
		$this->_functions[] = array(self::KEY => OperationType::SELECT,"value" => $mapper);
		return $this;
	}

	/**
	* groupBy処理の登録
	* キー名を登録する必要がある。
	* 
	* @param array(string) $keys キー名の配列
	**/
	public function groupBy($keys){
		if(!is_array($keys)){
			throw new Exception("$keys is not array.");
		}

		foreach($keys as $key)
		{
			$groupKeys[] = array(self::KEY => $key,self::DESC => false);
		}


		$this->_functions[] = array(self::KEY => OperationType::GROUP_BY,"value" => $groupKeys);
		return ArrayWrapper::Wrap($this->toVar());
	}

	/**
	* reduce処理の登録と実行
	*
	* @param lambda $reducer function(配列要素){return 加工した要素}
	**/
	public function reduce($reducer){
		if(!is_callable($reducer)){
			throw new Exception("$reducer is not function.");
		}
		$this->_functions[] = array(self::KEY => OperationType::REDUCE,"value" => $reducer);
		return $this->toVar();
	}

	/**
	* join処理の登録と実行
	*
	* @param array $right 結合する配列
	* @param array $leftKey 元の配列の結合キー
	* @param array $rightKey 結合する配列の結合キー
	* @param lambda $map 結合結果についてのマップ処理 function(元配列の要素、結合する配列の要素){return 結合する要素}
	**/
	public function join($right,$leftKey,$rightKey,$map,$joinType = JoinType::INNER){
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

		if(!is_int($joinType) || !($joinType == JoinType::INNER || $joinType == JoinType::LEFT) ){
			throw new Exception("joinType is not JoinType");
		}


#end region

		$leftKeys = array();
		foreach ($leftKey as $value) {
			array_push($leftKeys, array(self::KEY => $value,self::DESC => "false"));
		}
		$rightKeys = array();
		foreach ($rightKey as  $value) {
			array_push($rightKeys, array(self::KEY => $value,self::DESC => "false"));
		}


		$this->_functions[] = array(self::KEY => OperationType::JOIN,
									"value" => array(
												"right" => $right ,
												"leftKey" => $leftKeys,
												"rightKey" => $rightKeys,
												"map"	=> $map,
												"joinType" => $joinType));

		return ArrayWrapper::Wrap($this->toVar());
	}

	/**
	*  orderBy処理の登録と実行
	*
	* @param array $orderKey ソート順を示す(self::KEY => "並び替えしたいキー",self::DESC => true or false(降順ならtrue))
	*						 の配列
	**/
	public function orderBy($orderKey)
	{
		$this->_functions[] = array(self::KEY => OperationType::ORDER_BY,
									"value" => $orderKey);
		return ArrayWrapper::Wrap($this->toVar());
	}

	/**
	*	配列の特定のキーの値を合計
	*
	*	@param string $targetKeyName
	**/
	public function sum($targetKeyName)
	{
		$sumFunc = function($x,$y)
				use($targetKeyName)
				{
					return $x + $y[$targetKeyName];
				};

		return $this->reduce($sumFunc);
	}

	/**
	*	配列の特定のキーの値の算術平均(means)を出す
	*
	*	@param string $targetKeyName
	**/
	public function average($targetKeyName)
	{
		$sumFunc = function($x,$y)
				use($targetKeyName)
				{
					return $x + $y[$targetKeyName];
				};

		$value = $this->reduce($sumFunc);
		$count = count($this->_source);
		return $value / $count;
	}

	/**
	* LINQ.Zip相当の実行
	*
	* @param array $rightArray 一緒にループする配列
	* @param lambda $map 結合結果についてのマップ処理 function(元配列の要素、結合する配列の要素){return 結合する要素}
	**/
	public function zip($rightArray,$map){
#region "事前条件"
		if(!is_array($rightArray)){
			throw new Exception("$rightArray is not array");
		}

		if(!is_callable($map)){
			throw new Exception("$map is not function");
		}
#end region
		
		$leftArray = $this->toVar();

		$leftCount = count($leftArray);
		$rightCount = count($rightArray);

		$loopMaxCount = $leftCount > $rightCount ? $rightCount : $leftCount;

		$newArray = array();
		for ($i=0; $i < $loopMaxCount; $i++) { 
			$newArray[]  = $map($leftArray[$i],$rightArray[$i]);

		}

		return ArrayWrapper::Wrap($newArray);
	}



}
?>
