<?php
declare(strict_types=1);

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
	private const KEY = "key";
	private const DESC = "desc";
	private const GROUP_KEYS = "keys";
	private const GROUP_VALUES = "values";

	    private readonly array $source;
    private array $functions = [];

	public static function Wrap(array $source): self
	{
		return new self($source);
	}

	/**
	 * コンストラクタ
	 * @param array $source ラップしたい配列もしくは連想配列
	 */
	private function __construct(array $source)
	{
		$this->source = $source;
	}

	/**
	 * 配列同士のコンペア
	 * groupBy,orderByで利用
	 */
	private function compare(array $left, array $right, array $leftKeys, ?array $rightKeys = null): int
	{
		$rightKeys ??= $leftKeys;
		
		$getValue = function(array $target, string|callable $key): mixed {
			return is_string($key) ? $target[$key] : $key($target);
		};

		for ($i = 0; $i < count($leftKeys); $i++) {
			$leftValue = $getValue($left, $leftKeys[$i][self::KEY]);
			$rightValue = $getValue($right, $rightKeys[$i][self::KEY]);
			
			if ($leftValue > $rightValue) {
				return $leftKeys[$i][self::DESC] === false ? -1 : 1;
			} elseif ($leftValue < $rightValue) {
				return $leftKeys[$i][self::DESC] === true ? -1 : 1;
			}
		}
		return 0;
	}

	/**
	 * groupBy時に新しいgroupを作成する。
	 */
	private function addNewGroup(array $keyList, array $value): array
	{
		$groupKeys = [];
		foreach ($keyList as $groupKey) {
			$groupKeys[$groupKey[self::KEY]] = $value[$groupKey[self::KEY]];
		}
		return [self::GROUP_KEYS => $groupKeys, self::GROUP_VALUES => [$value]];
	}

	/**
	 * groupBy処理
	 */
	private function grouping(array &$groups, array $value, array $groupKeys): void
	{
		$arrayCount = count($groups);
		if ($arrayCount === 0) {
			$groups[] = $this->addNewGroup($groupKeys, $value);
			return;
		}

		$start = 0;
		$target = intval(floor($arrayCount / 2));
		
		while (true) {
			$arrayValue = $groups[$target];
			$comparison = $this->compare($arrayValue[self::GROUP_KEYS], $value, $groupKeys);
			
			if ($comparison === -1) {
				if ($target - $start > 1) {
					$target = $target - intval(floor(($target - $start) / 2));
				} elseif ($this->compare($groups[$start][self::GROUP_KEYS], $value, $groupKeys) === -1) {
					array_splice($groups, $start, 0, [$this->addNewGroup($groupKeys, $value)]);
					return;
				} else {
					array_splice($groups, $target, 0, [$this->addNewGroup($groupKeys, $value)]);
					return;
				}
			} elseif ($comparison === 0) {
				array_push($groups[$target][self::GROUP_VALUES], $value);
				return;
			} else {
				if ($arrayCount - $target > 1) {
					$start = $target;
					$target = $target + intval(floor(($arrayCount - $target) / 2));
				} elseif ($this->compare($groups[$arrayCount - 1][self::GROUP_KEYS], $value, $groupKeys) === -1) {
					array_splice($groups, $arrayCount - 1, 0, [$this->addNewGroup($groupKeys, $value)]);
					return;
				} else {
					array_push($groups, $this->addNewGroup($groupKeys, $value));
					return;
				}
			}
		}
	}

	    /**
     * 配列同士をjoinする処理
     * inner join,left joinに対応
     */
    private function joinArrays(array &$newArray, array $leftValue, array $joinInfo): void
	{
		$rightValues = $joinInfo["right"];
		$leftKey = $joinInfo["leftKey"];
		$rightKey = $joinInfo["rightKey"];
		$map = $joinInfo["map"];
		$joinType = $joinInfo["joinType"];

		$isNotFound = true;
		foreach ($rightValues as $rightValue) {
			if ($this->compare($leftValue, $rightValue, $leftKey, $rightKey) === 0) {
				array_push($newArray, $map($leftValue, $rightValue));
				$isNotFound = false;
			}
		}
		
		if ($isNotFound && $joinType === JoinType::LEFT) {
			array_push($newArray, $map($leftValue, null));
		}
	}

	    /**
     * OrderBy処理
     */
    private function orderByInternal(array &$newArray, array $value, array $orderKeys): void
	{
		$arrayCount = count($newArray);
		if ($arrayCount === 0) {
			array_push($newArray, $value);
			return;
		}

		$start = 0;
		$target = intval(floor($arrayCount / 2));
		
		while (true) {
			$arrayValue = $newArray[$target];
			$comparison = $this->compare($arrayValue, $value, $orderKeys);
			
			if ($comparison === -1) {
				if ($target - $start > 1) {
					$target = $target - intval(floor(($target - $start) / 2));
				} elseif ($this->compare($newArray[$start], $value, $orderKeys) === -1) {
					array_splice($newArray, $start, 0, [$value]);
					return;
				} else {
					array_splice($newArray, $target, 0, [$value]);
					return;
				}
			} elseif ($comparison === 0) {
				array_splice($newArray, $target + 1, 0, [$value]);
				return;
			} else {
				if ($arrayCount - $target > 1) {
					$start = $target;
					$target = $target + intval(floor(($arrayCount - $target) / 2));
				} elseif ($this->compare($newArray[$arrayCount - 1], $value, $orderKeys) === -1) {
					array_splice($newArray, $arrayCount - 1, 0, [$value]);
					return;
				} else {
					array_push($newArray, $value);
					return;
				}
			}
		}
	}

	/**
	 * 積み上げられた処理を行い、配列を返す
	 */
	public function toVar(): mixed
	{
		$reduceResult = 0;
		$isReduce = false;
		$groups = [];
		$newArray = [];
		
		        if (empty($this->functions)) {
            return $this->source;
        }

		foreach ($this->source as $value) {
			$isExcept = false;
			foreach ($this->functions as $function) {
				$operationType = OperationType::from($function[self::KEY]);
				
				if ($operationType === OperationType::WHERE) {
					if (!$function["value"]($value)) {
						$isExcept = true;
						break;
					}
				} elseif ($operationType === OperationType::SELECT) {
					$value = $function["value"]($value);
				} elseif ($operationType === OperationType::REDUCE) {
					$reduceResult = $function["value"]($reduceResult, $value);
					$isReduce = true;
				} elseif ($operationType === OperationType::GROUP_BY) {
					$this->grouping($groups, $value, $function["value"]);
					$isExcept = true;
				} elseif ($operationType === OperationType::JOIN) {
					$isExcept = true;
					$this->joinArrays($newArray, $value, $function["value"]);
				} elseif ($operationType === OperationType::ORDER_BY) {
					$isExcept = true;
					$this->orderByInternal($newArray, $value, $function["value"]);
				}
			}
			
			if (!$isExcept) {
				$newArray[] = $value;
			}
		}
		
		if ($isReduce) {
			array_pop($this->functions);
			return $reduceResult;
		}
		
		if (count($groups) !== 0) {
			return $groups;
		}

		return $newArray;
	}

	/**
	 * where処理の登録
	 * @param callable $predicate function(配列要素){return 要素が対象かどうかの処理}
	 */
	public function where(callable $predicate): self
	{
		$this->functions[] = [self::KEY => OperationType::WHERE->value, "value" => $predicate];
		return $this;
	}

	/**
	 * select処理の登録
	 * @param callable $mapper function(配列要素){return 加工した要素}
	 */
	public function select(callable $mapper): self
	{
		$this->functions[] = [self::KEY => OperationType::SELECT->value, "value" => $mapper];
		return $this;
	}

	/**
	 * groupBy処理の登録
	 * キー名を登録する必要がある。
	 * @param array $keys キー名の配列
	 */
	public function groupBy(array $keys): self
	{
		$groupKeys = [];
		foreach ($keys as $key) {
			$groupKeys[] = [self::KEY => $key, self::DESC => false];
		}

		$this->functions[] = [self::KEY => OperationType::GROUP_BY->value, "value" => $groupKeys];
		return self::Wrap($this->toVar());
	}

	/**
	 * reduce処理の登録と実行
	 * @param callable $reducer function(配列要素){return 加工した要素}
	 */
	public function reduce(callable $reducer): mixed
	{
		$this->functions[] = [self::KEY => OperationType::REDUCE->value, "value" => $reducer];
		return $this->toVar();
	}

	/**
	 * join処理の登録と実行
	 * @param array $right 結合する配列
	 * @param array $leftKey 元の配列の結合キー
	 * @param array $rightKey 結合する配列の結合キー
	 * @param callable $map 結合結果についてのマップ処理 function(元配列の要素、結合する配列の要素){return 結合する要素}
	 * @param JoinType $joinType ジョインの種類
	 */
	public function join(array $right, array $leftKey, array $rightKey, callable $map, JoinType $joinType = JoinType::INNER): self
	{
		if (count($leftKey) !== count($rightKey)) {
			throw new Exception("leftKey count different rightKey count.");
		}

		$leftKeys = array_map(fn($value) => [self::KEY => $value, self::DESC => false], $leftKey);
		$rightKeys = array_map(fn($value) => [self::KEY => $value, self::DESC => false], $rightKey);

		$this->functions[] = [
			self::KEY => OperationType::JOIN->value,
			"value" => [
				"right" => $right,
				"leftKey" => $leftKeys,
				"rightKey" => $rightKeys,
				"map" => $map,
				"joinType" => $joinType
			]
		];

		return self::Wrap($this->toVar());
	}

	/**
	 * orderBy処理の登録と実行
	 * @param array $orderKey ソート順を示す(self::KEY => "並び替えしたいキー",self::DESC => true or false(降順ならtrue))の配列
	 */
	public function orderBy(array $orderKey): self
	{
		$this->functions[] = [self::KEY => OperationType::ORDER_BY->value, "value" => $orderKey];
		return self::Wrap($this->toVar());
	}

	/**
	 * 配列の特定のキーの値を合計
	 * @param string $targetKeyName
	 */
	public function sum(string $targetKeyName): mixed
	{
		return $this->reduce(fn($x, $y) => $x + $y[$targetKeyName]);
	}

	/**
	 * 配列の特定のキーの値の算術平均(means)を出す
	 * @param string $targetKeyName
	 */
	public function average(string $targetKeyName): float
	{
		$value = $this->reduce(fn($x, $y) => $x + $y[$targetKeyName]);
		$count = count($this->source);
		return $value / $count;
	}

	/**
	 * LINQ.Zip相当の実行
	 * @param array $rightArray 一緒にループする配列
	 * @param callable $map 結合結果についてのマップ処理 function(元配列の要素、結合する配列の要素){return 結合する要素}
	 */
	public function zip(array $rightArray, callable $map): self
	{
		$leftArray = $this->toVar();
		$leftCount = count($leftArray);
		$rightCount = count($rightArray);
		$loopMaxCount = min($leftCount, $rightCount);

		$newArray = [];
		for ($i = 0; $i < $loopMaxCount; $i++) {
			$newArray[] = $map($leftArray[$i], $rightArray[$i]);
		}

		return self::Wrap($newArray);
	}
}
?>
