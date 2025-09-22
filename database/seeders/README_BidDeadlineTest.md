# 入札期限バッチテスト用シーダー

## 概要

`BidDeadlineTestSeeder` は入札期限バッチ処理の全パターンをテストするためのシーダーです。

## 作成されるテストデータ

### 1. 期限切れ（入札なし） - 3件
- **説明**: 入札期限が切れているが、入札がない見積もり
- **期待結果**: 見積もりがクローズされるが、営業権は付与されない
- **メールアドレス**: `expired_no_bids_1@test.com` ～ `expired_no_bids_3@test.com`

### 2. 期限切れ（上位3社明確） - 3件
- **説明**: 入札期限が切れており、上位3社が明確に決まる
- **期待結果**: 上位3社に営業権が付与される
- **メールアドレス**: `expired_clear_top3_1@test.com` ～ `expired_clear_top3_3@test.com`
- **入札パターン**: 5社が入札、金額は明確に異なる

### 3. 期限切れ（同順位あり） - 2件
- **説明**: 同順位により4社以上が営業権を獲得するパターン
- **期待結果**: 1位（1社）+ 2位（3社） = 計4社に営業権付与
- **メールアドレス**: `expired_with_ties_1@test.com` ～ `expired_with_ties_2@test.com`
- **入札パターン**: 
  - 1位: 80,000円-100,000円（1社）
  - 2位: 85,000円-110,000円（3社、同順位）
  - 5位以下: 90,000円以上（営業権対象外）

### 4. 期限切れ（1社のみ） - 2件
- **説明**: 入札権を持つ店舗のうち1社のみが入札
- **期待結果**: 1社のみに営業権付与
- **メールアドレス**: `expired_single_bid_1@test.com` ～ `expired_single_bid_2@test.com`

### 5. 期限切れ（2社のみ） - 2件
- **説明**: 入札権を持つ店舗のうち2社のみが入札
- **期待結果**: 2社に営業権付与
- **メールアドレス**: `expired_two_bids_1@test.com` ～ `expired_two_bids_2@test.com`

### 6. まだ期限前 - 3件
- **説明**: 入札期限がまだ来ていない見積もり
- **期待結果**: バッチ処理の対象外
- **メールアドレス**: `active_1@test.com` ～ `active_3@test.com`

### 7. 既に営業権付与済み（closed状態） - 2件
- **説明**: 既にクローズされている見積もり
- **期待結果**: バッチ処理の対象外
- **メールアドレス**: `closed_1@test.com` ～ `closed_2@test.com`

## 実行方法

### 1. マイグレーションとシーダーの実行

```bash
# データベースをリセットしてマイグレーション実行
cd src/api
php artisan migrate:fresh

# 全シーダー実行（テストデータも含む）
php artisan db:seed
```

### 2. テスト用シーダーのみ実行

```bash
# 特定のシーダーのみ実行
php artisan db:seed --class=BidDeadlineTestSeeder
```

### 3. バッチ処理の実行

```bash
# ドライランでテスト
php artisan bid:process-deadline --dry-run

# 実際のバッチ処理実行
php artisan bid:process-deadline
```

## 検証ポイント

### バッチ処理実行前の確認

```sql
-- 期限切れ見積もりの確認
SELECT id, name, email, bid_deadline, status 
FROM estimates 
WHERE bid_deadline <= NOW() AND status = 'published'
ORDER BY bid_deadline;

-- 入札状況の確認
SELECT e.id, e.name, COUNT(b.id) as bid_count
FROM estimates e
LEFT JOIN estimate_bid_rights ebr ON e.id = ebr.estimate_id
LEFT JOIN bids b ON ebr.id = b.estimate_bid_right_id
WHERE e.bid_deadline <= NOW() AND e.status = 'published'
GROUP BY e.id, e.name
ORDER BY e.bid_deadline;
```

### バッチ処理実行後の確認

```sql
-- 営業権付与状況の確認
SELECT 
    br.estimate_id,
    e.name,
    s.name as store_name,
    br.ranking,
    br.bid_amount_min,
    br.bid_amount_max,
    br.granted_at
FROM business_rights br
JOIN estimates e ON br.estimate_id = e.id
JOIN stores s ON br.store_id = s.id
ORDER BY br.estimate_id, br.ranking;

-- 見積もりステータスの確認
SELECT id, name, status, bid_deadline 
FROM estimates 
WHERE bid_deadline <= NOW()
ORDER BY bid_deadline;
```

## 期待される結果

- **期限切れ（入札なし）**: 3件がクローズされ、営業権は0件
- **期限切れ（上位3社明確）**: 3件がクローズされ、営業権は9件（3件×3社）
- **期限切れ（同順位あり）**: 2件がクローズされ、営業権は8件（2件×4社）
- **期限切れ（1社のみ）**: 2件がクローズされ、営業権は2件（2件×1社）
- **期限切れ（2社のみ）**: 2件がクローズされ、営業権は4件（2件×2社）

**合計**: 12件の見積もりがクローズされ、23件の営業権が付与される
