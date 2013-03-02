ArrayWrapper for PHP
======================
PHPで一連の配列操作をメソッドチェーンで行うためのラッパクラスです。
where,select,reduce,orderBy,groupBy,join(inner join相当)ができるようになっています。
思いつきで書いたプロジェクトなので、1クラスのみで構成されていて若干微妙です。

使い方
-----
ArrayWrapperクラスのインスタンスを生成する時に、操作したい配列を与えることで、ラッピングされます。

```PHP
 $arrayVariable = array(1,2,3,4,5,6,7,8,9,10);
 $query = new ArrayWrapper($arrayVariable);
 
```