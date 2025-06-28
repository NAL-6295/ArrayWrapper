# 🚀 CI/CD セットアップガイド

## 📋 概要

このガイドでは、GitHub ActionsによるCI/CDパイプラインとブランチ保護を設定し、テストが通らない限りマージできないようにする手順を説明します。

## ⚡ クイックセットアップ (5分)

### 1. 📤 リポジトリにプッシュ

```bash
# GitHubリポジトリを作成後
git init
git add .
git commit -m "feat: Add modernized PHP ArrayWrapper with CI/CD"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/arraywrapper.git
git push -u origin main
```

### 2. 🔧 GitHub Actions有効化

1. GitHubリポジトリページに移動
2. **Actions** タブをクリック
3. "I understand my workflows, go ahead and enable them" をクリック

### 3. 🔒 ブランチ保護設定

1. **Settings** → **Branches** に移動
2. **Add rule** をクリック
3. 以下を設定:

```
Branch name pattern: main

☑️ Require a pull request before merging
   ☑️ Require approvals: 1
   ☑️ Dismiss stale PR approvals when new commits are pushed
   ☑️ Require review from code owners

☑️ Require status checks to pass before merging
   ☑️ Require branches to be up to date before merging
   
   Required status checks:
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

4. **Create** をクリック

## 🧪 CI/CD パイプライン詳細

### 実行されるジョブ

| ジョブ名 | 説明 | 実行時間 |
|---------|------|----------|
| **PHP Tests** | 4つのPHPバージョンでテスト実行 | ~2分 |
| **Code Quality** | コード品質とPHP 8.x機能チェック | ~30秒 |
| **Security Scan** | セキュリティ脆弱性チェック | ~30秒 |
| **Performance** | パフォーマンステスト (10k件) | ~1分 |
| **Compatibility** | バージョン間互換性チェック | ~2分 |

### トリガー

- `push` to `main`, `develop`
- `pull_request` to `main`, `develop`

## 🔄 Dependabot設定

自動的に依存関係の更新をチェックします:

- **GitHub Actions**: 毎週月曜日
- **Composer**: 毎週月曜日 (将来的にcomposer.json追加時)

## 📝 プルリクエスト ワークフロー

### 開発者の手順

1. **ブランチ作成**
   ```bash
   git checkout -b feature/new-feature
   ```

2. **コード変更**
   ```bash
   # 変更を実施
   vim ArrayWrapper.php
   ```

3. **ローカルテスト実行**
   ```bash
   cd test
   php ArrayWrapperTest.php
   php ArrayWrapperHugeTest.php
   ```

4. **コミット & プッシュ**
   ```bash
   git add .
   git commit -m "feat: Add new feature"
   git push origin feature/new-feature
   ```

5. **プルリクエスト作成**
   - GitHubでPRを作成
   - PRテンプレートに従って入力

### レビュー & マージ

1. **自動CI実行** - 全ジョブ完了まで待機
2. **レビュー** - コードレビューと承認
3. **マージ** - すべてのチェックがパスした場合のみ可能

## 🚫 マージブロック条件

以下の場合はマージが**ブロック**されます:

- ❌ テストが失敗
- ❌ PHP構文エラー
- ❌ パフォーマンス劣化 (>1000ms)
- ❌ セキュリティ問題検出
- ❌ レビュー未承認
- ❌ ブランチが最新でない

## 🎯 ステータスバッジ

README.mdに以下のバッジが表示されます:

```markdown
[![CI Status](https://github.com/YOUR_USERNAME/arraywrapper/workflows/CI%20-%20PHP%20ArrayWrapper%20Tests/badge.svg)](https://github.com/YOUR_USERNAME/arraywrapper/actions)
```

## 🔍 トラブルシューティング

### よくある問題

1. **"Required status checks"が表示されない**
   - 一度CIを実行してからブランチ保護を設定してください

2. **テストが失敗する**
   ```bash
   # ローカルで確認
   php -l ArrayWrapper.php
   cd test && php ArrayWrapperTest.php
   ```

3. **パフォーマンステストが失敗**
   ```bash
   # 実行時間をチェック
   cd test && time php ArrayWrapperHugeTest.php
   ```

### CI実行状況確認

- リポジトリ → **Actions** タブ
- 各ワークフロー実行の詳細ログを確認可能

## 📊 成功メトリクス

設定完了後の期待値:

- ✅ **CI実行時間**: 5-7分
- ✅ **成功率**: >95%
- ✅ **セキュリティスコア**: A+
- ✅ **コードカバレッジ**: 100%

## 🎉 設定完了確認

以下を確認してセットアップ完了:

1. ✅ GitHub ActionsでCIが実行される
2. ✅ ブランチ保護が有効化されている
3. ✅ mainブランチへの直接pushがブロックされる
4. ✅ PRでCIが自動実行される
5. ✅ すべてのテストがパスする

---

**🎯 これで世界クラスの品質保証システムが完成しました！**