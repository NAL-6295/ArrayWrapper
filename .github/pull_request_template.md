## 📋 Pull Request Checklist

### ✅ **必須チェック項目**

- [ ] すべてのテストがローカルで実行され、パスしている
- [ ] コード変更に関連する新しいテストを追加済み
- [ ] PHP構文エラーがない (`php -l` でチェック済み)
- [ ] 変更内容の説明が適切に記載されている
- [ ] Breaking changes がある場合は明記している

### 🔧 **実行したテスト**

- [ ] `php test/ArrayWrapperTest.php` - 基本機能テスト
- [ ] `php test/ArrayWrapperHugeTest.php` - パフォーマンステスト
- [ ] PHP構文チェック: `find . -name "*.php" -exec php -l {} \;`

### 📝 **変更内容**

#### 🎯 **変更の種類** (該当するものにチェック)
- [ ] 🐛 Bug fix (バグ修正)
- [ ] ✨ New feature (新機能)
- [ ] 💥 Breaking change (非互換性変更)
- [ ] 📚 Documentation (ドキュメント更新)
- [ ] 🔧 Code refactoring (リファクタリング)
- [ ] ⚡ Performance improvement (パフォーマンス改善)
- [ ] 🧪 Tests (テスト追加・修正)

#### 📖 **変更の詳細**
<!-- 何を変更したか、なぜ変更したかを記載 -->

#### 🔗 **関連Issue**
<!-- Closes #123 のように記載 -->

### 🧪 **テスト環境**

- **PHP Version**: <!-- 8.1, 8.2, 8.3, 8.4 -->
- **OS**: <!-- Ubuntu, macOS, Windows -->
- **テスト実行結果**: 
  ```
  # ここにテスト実行結果を貼り付け
  ```

### 📊 **パフォーマンスチェック**

パフォーマンステストを実行した場合:
- **実行時間**: <!-- 例: 215ms -->
- **データ件数**: <!-- 例: 10,000件 -->
- **メモリ使用量**: <!-- 測定した場合 -->

### 🔒 **セキュリティチェック**

- [ ] 外部入力の適切な検証・サニタイゼーション
- [ ] SQL Injection対策 (該当する場合)
- [ ] XSS対策 (該当する場合)
- [ ] 危険な関数の使用確認 (`eval`, `system` など)

### 📚 **ドキュメント更新**

- [ ] README.mdの更新 (新機能の場合)
- [ ] コード内コメントの更新
- [ ]使用例の追加・修正

### 🔗 **互換性**

- [ ] PHP 8.1+ 対応確認
- [ ] 既存APIの互換性維持
- [ ] Deprecated機能の使用回避

### 🚀 **CI/CD Status**

以下のチェックがGitHub Actionsで自動実行されます:

- **PHP Tests** (8.1, 8.2, 8.3, 8.4)
- **Code Quality Checks**
- **Security Scan**
- **Performance Benchmark**
- **Compatibility Check**

❗ **注意**: すべてのCIチェックがパスするまでマージはできません。

### 📝 **追加情報**

<!-- その他の情報、レビュー時の注意点など -->

---

### 🔍 **Reviewer向けチェックポイント**

- [ ] コードの可読性と保守性
- [ ] テストカバレッジの確認
- [ ] パフォーマンスへの影響
- [ ] セキュリティ要件の満足
- [ ] ドキュメントの適切性

**🎉 Thank you for contributing to ArrayWrapper!**