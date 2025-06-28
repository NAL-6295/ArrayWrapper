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
	*   配列同士のコンペア（最適化版）
	*   groupBy,orderByで利用
	**/
	private function compare($left, $right, $leftKeys, $rightKeys = null)
	{
		if (!isset($rightKeys)) {
			$rightKeys = $leftKeys;
		}
		
		$leftKeysCount = count($leftKeys);
		
		for ($i = 0; $i < $leftKeysCount; $i++) { 
			$leftKeyInfo = $leftKeys[$i];
			$rightKeyInfo = $rightKeys[$i];
			
			// getValue logic inlined for performance
			$leftKey = $leftKeyInfo[self::KEY];
			$rightKey = $rightKeyInfo[self::KEY];
			
			if (is_string($leftKey)) {
				$leftValue = $left[$leftKey];
			} elseif (is_callable($leftKey)) {
				$leftValue = $leftKey($left);
			} else {
				$leftValue = $left[$leftKey];
			}
			
			if (is_string($rightKey)) {
				$rightValue = $right[$rightKey];
			} elseif (is_callable($rightKey)) {
				$rightValue = $rightKey($right);
			} else {
				$rightValue = $right[$rightKey];
			}
			
			if ($leftValue !== $rightValue) {
				if ($leftValue > $rightValue) {
					return $leftKeyInfo[self::DESC] ? -1 : 1;
				} else {
					return $leftKeyInfo[self::DESC] ? 1 : -1;
				}
			}
		}
		return 0;
	}

	/**
	* groupBy時に新しいgroupを作成する。
	*
	**/
	private function _addNewGroup($keyList, $value){
		$groupKeys = [];
		foreach($keyList as $groupKey){
			$groupKeys[$groupKey[self::KEY]] = $value[$groupKey[self::KEY]];
		}
		return [self::GROUP_KEYS => $groupKeys, self::GROUP_VALUES => [$value]];
	}	
	
	/**
	* groupBy処理（最適化版 - ハッシュマップベース）
	**/
	private function _groupingOptimized(&$groups, $value, $groupKeys){
		// グループキーのハッシュを生成
		$hashKey = '';
		foreach($groupKeys as $keyInfo) {
			$key = $keyInfo[self::KEY];
			if (is_string($key)) {
				$hashKey .= $value[$key] . '|';
			} elseif (is_callable($key)) {
				$hashKey .= $key($value) . '|';
			}
		}
		
		if (isset($groups[$hashKey])) {
			$groups[$hashKey][self::GROUP_VALUES][] = $value;
		} else {
			$groups[$hashKey] = $this->_addNewGroup($groupKeys, $value);
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
			if($this->compare($leftValue,$rightValue,$leftKey,$rightKey) == 0)
			{
				$newArray[] = $map($leftValue,$rightValue);
				$isNotFound = false;
			}
		}
		if($isNotFound && $joinType == JoinType::LEFT){
			$newArray[] = $map($leftValue,null);			
		}
	}

	/**
	* OrderBy処理（最適化版 - PHP native sort使用）
	**/
	private function _orderByOptimized(&$newArray, $orderKeys)
	{
		$self = $this;
		usort($newArray, function($a, $b) use ($orderKeys, $self) {
			return $self->compare($a, $b, $orderKeys);
		});
	}

	/**
	* 積み上げられた処理を行い、配列を返す
	*
	**/
	public function toVar(){
		
		$reduceResult = 0;
		$isReduce = false;
		$groups = [];
		$newArray = [];
		$isGroupBy = false;
		$isOrderBy = false;
		$orderKeys = null;
		
		if($this->_functions == null){
			return $this->_source;
		}

		// 先にOrderByやGroupByがあるかチェック
		foreach($this->_functions as $function){
			if($function[self::KEY] == OperationType::ORDER_BY){
				$isOrderBy = true;
				$orderKeys = $function["value"];
			} elseif($function[self::KEY] == OperationType::GROUP_BY){
				$isGroupBy = true;
			}
		}

		foreach($this->_source as $value){
			$isExcept = false;
			$currentValue = $value;
			
			foreach($this->_functions as $function){
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
				}else if($function[self::KEY] == OperationType::GROUP_BY){
					$this->_groupingOptimized($groups,$currentValue,$function["value"]);
					$isExcept = true;
				}else if($function[self::KEY] == OperationType::JOIN){
					$isExcept = true;
					$this->_join($newArray,$currentValue,$function["value"]);
				}else if($function[self::KEY] == OperationType::ORDER_BY){
					// OrderBy処理は後でまとめて行うのでここでは何もしない
					// $isExceptはfalseのままにして、要素をnewArrayに追加する
				}
			}
			if(!$isExcept){
				$newArray[] = $currentValue;
			}
		}
		
		// OrderBy処理を最後にまとめて実行（効率的）
		if($isOrderBy && !empty($newArray)){
			$this->_orderByOptimized($newArray, $orderKeys);
		}
		
		if($isReduce){
			return $reduceResult;
		}
		if($isGroupBy){
			// グループ結果をソートして返す（連想配列から通常配列に変換）
			return array_values($groups);
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

		$groupKeys = [];
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

		if(count($leftKey) != count($rightKey)){
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
			$leftKeys[] = array(self::KEY => $value,self::DESC => false);
		}
		$rightKeys = array();
		foreach ($rightKey as  $value) {
			$rightKeys[] = array(self::KEY => $value,self::DESC => false);
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
		// 既存のfunctionを使わずに直接ソース配列を使用
		$sourceArray = $this->_source;
		if (!is_array($sourceArray)) {
			throw new Exception("Source is not array for average calculation");
		}
		
		$count = count($sourceArray);
		if ($count == 0) return 0;
		
		$sum = 0;
		foreach($sourceArray as $item) {
			$sum += $item[$targetKeyName];
		}
		return $sum / $count;
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
