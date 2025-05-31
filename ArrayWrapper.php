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
	public function __construct($source){
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
				return $leftKeys[$i][self::DESC] ? -1 : 1;
			}elseif($leftValue < $rightValue){
				return $leftKeys[$i][self::DESC] ? 1 : -1;
			}
		}
		return 0;
	}

	/**
	/**
	* groupBy処理 - populates a temporary map with grouped items.
	* The map's keys are generated from the item's values for the specified group keys.
	*
	* @param array &$tempGroupsMap Associative array to store groups, passed by reference.
	* @param mixed $valueToGroup The item to be grouped.
	* @param array $groupKeyDefs Definitions of keys to group by.
	**/
	private function _grouping(&$tempGroupsMap, $valueToGroup, $groupKeyDefs){
		$groupValArray = [];
		$actualKeyValues = []; // Stores the actual values for the keys that form this group

		$getValue = function($target, $keyDef){
			$keyName = $keyDef[self::KEY];
			if(is_string($keyName)){
				return $target[$keyName];
			}
			if(is_callable($keyName)){
				return $keyName($target);
			}
			return null; // Should not happen with current usage
		};

		foreach($groupKeyDefs as $keyDef){
			$val = $getValue($valueToGroup, $keyDef);
			$groupValArray[] = (string)$val; // Cast to string for consistent key generation
			$actualKeyValues[$keyDef[self::KEY]] = $val;
		}
		$mapKey = implode("::", $groupValArray); // Create a unique string key for the map

		if(!isset($tempGroupsMap[$mapKey])){
			$tempGroupsMap[$mapKey] = array(
				self::GROUP_KEYS => $actualKeyValues,
				self::GROUP_VALUES => array()
			);
		}
		$tempGroupsMap[$mapKey][self::GROUP_VALUES][] = $valueToGroup;
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
	private function _orderBy($arrayToSort, $orderKeys)
	{
		// usort sorts the array in place, but it's clearer to return it.
		// The compare function needs $orderKeys, so we use a closure.
		usort($arrayToSort, function($a, $b) use ($orderKeys) {
			return $this->compare($a, $b, $orderKeys);
		});
		return $arrayToSort;
	}

	/**
	* 積み上げられた処理を行い、配列を返す
	*
	**/
	public function toVar(){
		
		$reduceResult = 0;
		$isReduce = false;
		$newArray = array(); // Used for SELECT/WHERE results, or JOIN results
		if($this->_functions == null){
			return $this->_source;
		}

		$orderByKeys = null;
		$hasOrderBy = false;
		$groupByKeysDefs = null; // Store GroupBy key definitions
		$hasGroupBy = false;
		$tempGroupsMap = []; // Temporary map for grouping

		// First pass: identify operations and extract ORDER_BY/GROUP_BY details
		$activeFunctions = array(); // Functions to apply in the loop (excluding delayed ORDER_BY)
		foreach($this->_functions as $funcDetails) {
			if ($funcDetails[self::KEY] == OperationType::ORDER_BY) {
				$hasOrderBy = true;
				$orderByKeys = $funcDetails["value"];
			} else if ($funcDetails[self::KEY] == OperationType::GROUP_BY) {
				$hasGroupBy = true;
				$groupByKeysDefs = $funcDetails["value"];
				// GROUP_BY operation itself is handled iteratively by _grouping using $tempGroupsMap
				$activeFunctions[] = $funcDetails;
			} else {
				$activeFunctions[] = $funcDetails;
			}
		}

		foreach($this->_source as $value){
			$currentValue = $value;
			$isExcept = false;

			foreach($activeFunctions as $function){
				if($function[self::KEY] == OperationType::WHERE){
					if(!$function["value"]($currentValue)){
						$isExcept = true;
						break;
					}
				}else if($function[self::KEY] == OperationType::SELECT){
					$currentValue = $function["value"]($currentValue);
				}else if($function[self::KEY] == OperationType::REDUCE){
					$reduceResult = $function["value"]($reduceResult,$currentValue);
					$isReduce = true;
					// If reduce is active, it's a terminal operation for this item's path.
					// The item won't be added to $newArray or $tempGroupsMap for this iteration.
					$isExcept = true;
					break;
				}else if($function[self::KEY] == OperationType::GROUP_BY){
					// $groupByKeysDefs would have been set if GROUP_BY is in _functions
					$this->_grouping($tempGroupsMap, $currentValue, $groupByKeysDefs);
					$isExcept = true;
					break;
				}else if($function[self::KEY] == OperationType::JOIN){
					$this->_join($newArray,$currentValue,$function["value"]);
					$isExcept = true;
					break;
				}
			}

			if(!$isExcept){
				$newArray[] = $currentValue;
			}
		}	

		// Handle terminal operations results
		if($isReduce){
			$finalFunctions = [];
			foreach($this->_functions as $func) { if ($func[self::KEY] != OperationType::REDUCE) $finalFunctions[] = $func; }
			$this->_functions = $finalFunctions;
			return $reduceResult;
		}

		$resultArray;
		if($hasGroupBy) {
			$resultArray = array_values($tempGroupsMap);
		} else {
			// If not grouping, $newArray contains results from WHERE/SELECT or JOIN.
			// JOIN populates $newArray directly. WHERE/SELECT append to $newArray.
			$resultArray = $newArray;
		}

		if($hasOrderBy && $orderByKeys){
			$resultArray = $this->_orderBy($resultArray, $orderByKeys);
		}

		// Clean up processed terminal-like operations (ORDER_BY, GROUP_BY) from the main _functions queue
		// This makes the ArrayWrapper stateful and might need review for immutability patterns.
		$finalFunctions = [];
		foreach($this->_functions as $func) {
			if ($func[self::KEY] != OperationType::ORDER_BY && $func[self::KEY] != OperationType::GROUP_BY) {
				$finalFunctions[] = $func;
			}
		}
		$this->_functions = $finalFunctions;

		return $resultArray;
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
