ArrayWrapper for PHP
======================
PHPで一連の配列操作をメソッドチェーンで行うためのラッパクラスです。
.NET FrameworkのLINQのメソッドチェーンをイメージした使い方ができるようにしています。
where,select,reduce,orderBy,groupBy,join(inner join,left join),sum,avgができるようになっています。

使い方
-----
ArrayWrapperクラスのインスタンスを生成する時に、操作したい配列を与えることで、ラッピングされます。

```php
 $arrayVariable = array(1,2,3,4,5,6,7,8,9,10);
 // new ArrayWrapperはできなくした。
 $wrapper = ArrayWrapper::Wrap($arrayVariable);

```

それぞれのメソッドの使い方を下に示していきます。

where
----

```php
	$target = ArrayWrapper::Wrap($array(1,2,3,4,5,6,7,8,9,10));

	$actual = $target
				->where(function($x){return $x > 5;})
				->toVar();
	
	$expected = array(6,7,8,9,10);
```

select
-----

```php
	$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	
	$actual = $target
				->select(function($x){return $x * 2;})
				->toVar();

	$expected = array(2,4,6,8,10,12,14,16,18,20);
```

where -> select
-----

```php
	$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	

	$actual = $target
			->where(function($x){return $x > 5;})
			->select(function($x){return $x *2;})->toVar();

	$expected = array(12,14,16,18,20);
```

where -> select -> where
-----

```php
	$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	

	$actual = $target
			->where(function($x){return $x > 5;})
			->select(function($x){return $x *2;})
			->where(function($x){return $x > 12;})
			->toVar();

	$expected = array(14,16,18,20);
```

select -> reduce
-----

```php
	$target = ArrayWrapper::Wrap(array(1,2,3,4,5,6,7,8,9,10));	

	$actual = $target
			->where(function($x){return $x > 5;})
			->select(function($x){return $x *2;})
			->reduce(function($x,$y){return $x + $y;});

	$expected = 80;
```

(Hash)where -> select -> where
-----

```php
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
```

(Hash)groupBy
-----

```php
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
```

(Hash)groupBy->sum,average
-----

```php
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
```


(Hash)join
-----

```php
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

	$target = nArrayWrapper::Wrap($leftArray);

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
```

(Hash)left join
-----

```php
		$leftArray = array(
					array("key" => 2,"name" => "Nasal Hair Cutter"),
					array("key" => 3,"name" => "scissors"),
					array("key" => 5,"name" => "knife")	
				);

		$rightArray = array(
				array("id" => 1,"item_id" => 2,"value" => 10),
				array("id" => 2,"item_id" => 2,"value" => 20),
				array("id" => 3,"item_id" => 2,"value" => 30),
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
								array("item_id" => $leftValue["key"],
										 "name" => $leftValue["name"],
										 "value" => isset($rightValue) ? $rightValue["value"]:0);
						},JoinType::LEFT)
				->toVar();

		$expected = array(
					array("item_id" => 2,"name" => "Nasal Hair Cutter","value" => 10),
					array("item_id" => 2,"name" => "Nasal Hair Cutter","value" => 20),
					array("item_id" => 2,"name" => "Nasal Hair Cutter","value" => 30),
					array("item_id" => 3,"name" => "scissors","value" => 0),
					array("item_id" => 5,"name" => "knife","value" => 60),
					array("item_id" => 5,"name" => "knife","value" => 70),
				);
```


(Hash)orderBy
-----

```php
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

```




(Hash)orderBy(composite keys)
-----

```php
	$target = ArrayWrapper::Wrap(
			array(
				array("key" => 2,"key2" => 2,"value" => 10),
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
```


