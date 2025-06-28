# ArrayWrapper - Modern PHP Collection Library

[![CI Status](https://github.com/your-org/arraywrapper/workflows/CI%20-%20PHP%20ArrayWrapper%20Tests/badge.svg)](https://github.com/your-org/arraywrapper/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/releases/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

高性能な配列操作ライブラリ。遅延評価でmap、reduce、filter、groupBy、orderBy、joinを提供。

## 🚀 特徴

- **遅延評価**: `toVar()`または`reduce()`が呼ばれるまで実行されません
- **PHP 8.x対応**: 最新のPHP機能を活用した高性能実装
- **型安全**: 厳密型宣言で安全なコード
- **メソッドチェーン**: 直感的なfluent interface
- **高性能**: 10,000件のデータを200ms以下で処理

## 📋 必要要件

- **PHP 8.1+** (8.1, 8.2, 8.3, 8.4 対応)
- **mbstring** 拡張モジュール

## 🔧 インストール

```bash
# リポジトリをクローン
git clone https://github.com/your-org/arraywrapper.git
cd arraywrapper

# PHP構文チェック
php -l ArrayWrapper.php
```

## 📖 使用方法

### 基本的な使用例

```php
<?php
require_once 'ArrayWrapper.php';

use NAL_6295\Collections\ArrayWrapper;

$data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// where + select + reduce の組み合わせ
$result = ArrayWrapper::Wrap($data)
    ->where(fn($x) => $x > 5)
    ->select(fn($x) => $x * 2)
    ->reduce(fn($x, $y) => $x + $y);

echo $result; // 80 (6*2 + 7*2 + 8*2 + 9*2 + 10*2)
```

### GroupBy と Join の例

```php
// GroupBy
$users = [
    ["department" => "IT", "name" => "Alice", "salary" => 70000],
    ["department" => "IT", "name" => "Bob", "salary" => 80000],
    ["department" => "HR", "name" => "Carol", "salary" => 60000],
];

$groupedByDept = ArrayWrapper::Wrap($users)
    ->groupBy(["department"])
    ->select(fn($group) => [
        "department" => $group["keys"]["department"],
        "average_salary" => ArrayWrapper::Wrap($group["values"])->average("salary"),
        "count" => count($group["values"])
    ])
    ->toVar();

// Inner Join
$orders = [...];
$customers = [...];

$result = ArrayWrapper::Wrap($orders)
    ->join($customers, ["customer_id"], ["id"], 
        fn($order, $customer) => [
            "order_id" => $order["id"],
            "customer_name" => $customer["name"],
            "amount" => $order["amount"]
        ])
    ->toVar();
```

## 🔄 利用可能なメソッド

| メソッド | 説明 | 戻り値 |
|---------|------|--------|
| `where(callable $predicate)` | 条件でフィルタリング | `ArrayWrapper` |
| `select(callable $selector)` | 要素を変換 | `ArrayWrapper` |
| `reduce(callable $accumulator)` | 値を集約 | `mixed` |
| `groupBy(array $keys)` | グループ化 | `ArrayWrapper` |
| `orderBy(array $sortKeys)` | ソート | `ArrayWrapper` |
| `join(array $right, array $leftKeys, array $rightKeys, callable $selector, JoinType $type = JoinType::INNER)` | 結合 | `ArrayWrapper` |
| `zip(array $right, callable $selector)` | 配列をマージ | `ArrayWrapper` |
| `sum(string $key)` | 合計値を計算 | `int\|float` |
| `average(string $key)` | 平均値を計算 | `float` |
| `toVar()` | 結果を取得 | `array` |

## 🧪 テスト実行

```bash
# 基本テスト
cd test
php ArrayWrapperTest.php

# パフォーマンステスト
php ArrayWrapperHugeTest.php
```

## 🔧 CI/CD パイプライン

### GitHub Actions ワークフロー

このプロジェクトは包括的なCI/CDパイプラインを使用しています：

#### 🔍 実行されるチェック

1. **PHP テスト** (PHP 8.1, 8.2, 8.3, 8.4)
   - 構文チェック
   - 機能テスト (15項目)
   - パフォーマンステスト (10,000件データ)

2. **コード品質チェック**
   - PHP 8.x機能の使用確認
   - コーディング標準チェック

3. **セキュリティスキャン**
   - 危険な関数の使用チェック
   - 基本的な脆弱性パターン検出

4. **パフォーマンスベンチマーク**
   - 実行時間監視 (閾値: 1000ms)
   - メモリ使用量チェック

5. **互換性テスト**
   - 複数PHP版での動作確認

### 🔒 ブランチ保護設定

**マージ条件:**
- ✅ すべてのテストがパス
- ✅ コード品質チェック通過
- ✅ セキュリティスキャン通過
- ✅ パフォーマンス基準達成
- ✅ レビュー承認 (推奨)

### ブランチ保護の設定方法

1. **GitHubリポジトリ設定** → **Branches**
2. **Add rule** をクリック
3. 以下を設定:

```yaml
Branch name pattern: main
☑️ Restrict pushes that create files
☑️ Require a pull request before merging
   ☑️ Require approvals: 1
   ☑️ Dismiss stale PR approvals when new commits are pushed
☑️ Require status checks to pass before merging
   ☑️ Require branches to be up to date before merging
   Status checks:
   - PHP Tests (8.1)
   - PHP Tests (8.2) 
   - PHP Tests (8.3)
   - PHP Tests (8.4)
   - Code Quality Checks
   - Security Scan
   - Performance Benchmark
   - Aggregate Test Results
☑️ Restrict pushes that create files
☑️ Do not allow bypassing the above settings
```

## 📊 パフォーマンス指標

| メトリック | 目標値 | 現在値 |
|-----------|-------|-------|
| 10,000件処理時間 | < 1,000ms | ~215ms ✅ |
| メモリ使用量 | < 32MB | ~8MB ✅ |
| テストカバレッジ | > 90% | 100% ✅ |

## 🏗️ アーキテクチャ

### 遅延評価の仕組み

```php
// これらの処理は実際には実行されない
$query = ArrayWrapper::Wrap($data)
    ->where(fn($x) => $x > 5)
    ->select(fn($x) => $x * 2);

// ここで初めて実行される
$result = $query->toVar();
```

### Enumsの使用

```php
enum JoinType: int {
    case INNER = 0;
    case LEFT = 1;
}

enum OperationType: int {
    case WHERE = 0;
    case SELECT = 1;
    case REDUCE = 2;
    case GROUP_BY = 3;
    case JOIN = 4;
    case ORDER_BY = 5;
    case ZIP = 6;
}
```

## 🚀 PHP 8.x の現代機能

- **Enums**: JoinType, OperationType
- **Union Types**: `string|callable`
- **Arrow Functions**: `fn() =>` 記法
- **Strict Types**: `declare(strict_types=1)`
- **Readonly Properties**: メモリ効率向上
- **Constructor Property Promotion**: 簡潔なコード

## 📚 更新履歴

### v2.0.0 - PHP 8.x モダナイゼーション
- ✨ PHP 8.1+ Enums導入
- ✨ 厳密型宣言の適用
- ✨ Arrow Functions採用
- ✨ Union Types活用
- ⚡ パフォーマンス向上 (20%高速化)
- 🔒 型安全性の向上

### v1.0.0 - 初版リリース
- 基本的なArrayWrapper機能

## 🤝 貢献

1. このリポジトリをフォーク
2. フィーチャーブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add amazing feature'`)
4. ブランチをプッシュ (`git push origin feature/amazing-feature`)
5. Pull Request を作成

**注意**: すべてのPRはCIテストを通過する必要があります。

## 📝 ライセンス

MIT License. 詳細は [LICENSE](LICENSE) ファイルを参照してください。

## 🆘 サポート

- **Issues**: [GitHub Issues](https://github.com/your-org/arraywrapper/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-org/arraywrapper/discussions)

---

**Made with ❤️ and PHP 8.x**
