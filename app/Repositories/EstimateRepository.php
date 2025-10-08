<?php

namespace App\Repositories;

use App\Models\Estimate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EstimateRepository
{
    /**
     * 見積もり一覧を取得（ページネーション付き）
     * 店舗用: 公開中かつメール・電話認証済みの見積もりのみ
     */
    public function getPaginatedEstimates(string $storeId, int $perPage = 20, int $page = 1, array $filters = []): LengthAwarePaginator
    {
        $query = Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category',
            'estimateBidRights.bid',
        ])
            ->selectRaw('estimates.*, 
                EXISTS(
                    SELECT 1 FROM estimate_bid_rights 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ? 
                    AND estimate_bid_rights.status = 1
                ) as has_bid_right,
                EXISTS(
                    SELECT 1 FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                ) as has_bid,
                (
                    SELECT bids.bid_amount_min FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                    LIMIT 1
                ) as bid_amount_min,
                (
                    SELECT bids.bid_amount_max FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                    LIMIT 1
                ) as bid_amount_max', [$storeId, $storeId, $storeId, $storeId])
            ->where('status', Estimate::STATUS_PUBLISHED) // 公開中の見積もりのみ
            ->where('email_verified', true) // メール認証済み
            ->where('phone_verified', true) // 電話認証済み
            ->where('bid_deadline', '>=', now()); // 入札期限切れではない
        // 引っ越し先都道府県でのフィルタリング（ハイフン区切り対応）
        if (!empty($filters['to_prefecture_code'])) {
            $prefectureCodes = explode('-', $filters['to_prefecture_code']);
            $prefectureCodes = array_map('intval', $prefectureCodes);
            $prefectureCodes = array_filter($prefectureCodes, function ($code) {
                return $code > 0; // 正の整数のみ
            });

            if (!empty($prefectureCodes)) {
                $query->whereHas('movingToAddress', function ($q) use ($prefectureCodes) {
                    $q->whereIn('prefecture_code', $prefectureCodes);
                });
            }
        }

        // 引っ越し元都道府県でのフィルタリング（ハイフン区切り対応）
        if (!empty($filters['from_prefecture_code'])) {
            $prefectureCodes = explode('-', $filters['from_prefecture_code']);
            $prefectureCodes = array_map('intval', $prefectureCodes);
            $prefectureCodes = array_filter($prefectureCodes, function ($code) {
                return $code > 0; // 正の整数のみ
            });

            if (!empty($prefectureCodes)) {
                $query->whereHas('movingFromAddress', function ($q) use ($prefectureCodes) {
                    $q->whereIn('prefecture_code', $prefectureCodes);
                });
            }
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * IDで見積もりを取得
     * 店舗用: 公開中かつメール・電話認証済みの見積もりのみ
     */
    // public function findById(string $id, ?string $storeId = null): ?Estimate
    // {
    //     $query = Estimate::with([
    //         'movingFromAddress',
    //         'movingToAddress',
    //         'luggageItems.luggage.category'
    //     ]);

    //     // 店舗IDが指定された場合は入札権の有無と入札の有無、入札金額を追加
    //     if ($storeId) {
    //         $query->selectRaw('estimates.*, 
    //             EXISTS(
    //                 SELECT 1 FROM estimate_bid_rights 
    //                 WHERE estimate_bid_rights.estimate_id = estimates.id 
    //                 AND estimate_bid_rights.store_id = ? 
    //                 AND estimate_bid_rights.status = 1
    //             ) as has_bid_right,
    //             EXISTS(
    //                 SELECT 1 FROM bids 
    //                 INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
    //                 WHERE estimate_bid_rights.estimate_id = estimates.id 
    //                 AND estimate_bid_rights.store_id = ?
    //             ) as has_bid,
    //             (
    //                 SELECT bids.bid_amount_min FROM bids 
    //                 INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
    //                 WHERE estimate_bid_rights.estimate_id = estimates.id 
    //                 AND estimate_bid_rights.store_id = ?
    //                 LIMIT 1
    //             ) as bid_amount_min,
    //             (
    //                 SELECT bids.bid_amount_max FROM bids 
    //                 INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
    //                 WHERE estimate_bid_rights.estimate_id = estimates.id 
    //                 AND estimate_bid_rights.store_id = ?
    //                 LIMIT 1
    //             ) as bid_amount_max', [$storeId, $storeId, $storeId, $storeId]);
    //     }
    //     // 現在の入札ランキングを取得
    //     $query->selectRaw('estimates.*, 
    //         (
    //             SELECT COUNT(*) FROM bids 
    //             INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
    //             WHERE estimate_bid_rights.estimate_id = estimates.id 
    //             AND estimate_bid_rights.store_id = ?
    //         ) as bid_ranking', [$storeId]);

    //     $estimate = $query
    //         ->where('id', $id)
    //         ->where('status', Estimate::STATUS_PUBLISHED) // 公開中の見積もりのみ
    //         ->where('email_verified', true) // メール認証済み
    //         ->where('phone_verified', true) // 電話認証済み
    //         ->first();

    //     return $estimate;
    // }
    /**
     * IDで見積もりを取得
     * 店舗用: 公開中かつメール・電話認証済みの見積もりのみ
     * - $storeId がある場合：has_bid_right / has_bid / bid_amount_min / bid_amount_max / bid_rank を列として付与
     * - 常に：見積もり内の全店舗のランキング一覧 bid_ranking_list を属性として付与
     *   （各店舗の「最良入札」（min→max→id）を対象に DENSE_RANK() で 1,2,2,3,3,... 形式）
     */
    public function findById(string $id, ?string $storeId = null): ?Estimate
    {
        $query = Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category',
        ]);

        if ($storeId) {
            // 店舗IDが指定された場合に、入札権の有無・入札有無・最良入札額・自店舗の順位を付与
            $query->selectRaw(
                "estimates.*,

            /* この店舗が入札権（有効）を持っているか */
            EXISTS(
              SELECT 1
              FROM estimate_bid_rights ebr
              WHERE ebr.estimate_id = estimates.id
                AND ebr.store_id    = ?
                AND ebr.status      = 1
            ) AS has_bid_right,

            /* この店舗が既に1件以上入札しているか */
            EXISTS(
              SELECT 1
              FROM bids b
              JOIN estimate_bid_rights ebr ON b.estimate_bid_right_id = ebr.id
              WHERE ebr.estimate_id = estimates.id
                AND ebr.store_id    = ?
            ) AS has_bid,

            /* この店舗の “最良（最安）入札” の下限金額（min→max→id の優先で1件） */
            (
              SELECT b1.bid_amount_min
              FROM bids b1
              JOIN estimate_bid_rights ebr1 ON b1.estimate_bid_right_id = ebr1.id
              WHERE ebr1.estimate_id = estimates.id
                AND ebr1.store_id    = ?
              ORDER BY b1.bid_amount_min ASC, b1.bid_amount_max ASC, b1.id ASC
              LIMIT 1
            ) AS bid_amount_min,

            /* 上記 “最良入札” の上限金額 */
            (
              SELECT b2.bid_amount_max
              FROM bids b2
              JOIN estimate_bid_rights ebr2 ON b2.estimate_bid_right_id = ebr2.id
              WHERE ebr2.estimate_id = estimates.id
                AND ebr2.store_id    = ?
              ORDER BY b2.bid_amount_min ASC, b2.bid_amount_max ASC, b2.id ASC
              LIMIT 1
            ) AS bid_amount_max,

            /* 自店舗の順位（見積り内の各店舗の“最良入札”集合に DENSE_RANK() 適用）
               - 各 store の最良入札を RN=1 に絞る
               - その集合で min→max 昇順に DENSE_RANK()
            */
            (
              SELECT ranked.bid_rank
              FROM (
                SELECT
                  ebr.store_id,
                  b.bid_amount_min,
                  b.bid_amount_max,
                  ROW_NUMBER() OVER (
                    PARTITION BY ebr.store_id
                    ORDER BY b.bid_amount_min ASC, b.bid_amount_max ASC, b.id ASC
                  ) AS rn,
                  DENSE_RANK() OVER (
                    ORDER BY b.bid_amount_min ASC, b.bid_amount_max ASC
                  ) AS bid_rank
                FROM estimate_bid_rights ebr
                JOIN bids b ON b.estimate_bid_right_id = ebr.id
                WHERE ebr.estimate_id = estimates.id
              ) ranked
              WHERE ranked.rn = 1
                AND ranked.store_id = ?
              LIMIT 1
            ) AS bid_rank",
                [$storeId, $storeId, $storeId, $storeId, $storeId]
            );
        }

        // 旧: COUNT(*) での“ランキングもどき”は削除（不正確なため）

        $estimate = $query
            ->where('id', $id)
            ->where('status', Estimate::STATUS_PUBLISHED) // 公開中のみ
            ->where('email_verified', true)               // メール認証済み
            ->where('phone_verified', true)               // 電話認証済み
            ->first();

        if (!$estimate) {
            return null;
        }

        /* ------------------------------------------------------------
     * 見積もり $id の “全店舗ランキング一覧” を作る
     *  1) 各 store の最良入札を ROW_NUMBER() で 1件に絞る（rn=1）
     *  2) その集合に DENSE_RANK() を適用（min→max 昇順）
     *  3) rank, store_id, min, max, tie_count を返す
     * ------------------------------------------------------------ */
        $bestPerStore = DB::table('estimate_bid_rights as ebr')
            ->join('bids as b', 'b.estimate_bid_right_id', '=', 'ebr.id')
            ->join('stores as st', 'st.id', '=', 'ebr.store_id') // ★ ここで stores を結合
            ->where('ebr.estimate_id', $id)
            ->selectRaw("
      ebr.store_id,
      st.name AS store_name,                                -- ★ 取得
      b.bid_amount_min,
      b.bid_amount_max,
      ROW_NUMBER() OVER (
        PARTITION BY ebr.store_id
        ORDER BY b.bid_amount_min ASC, b.bid_amount_max ASC, b.id ASC
      ) AS rn
    ");

        $ranked = DB::query()
            ->fromSub(function ($q) use ($bestPerStore) {
                $q->fromSub($bestPerStore, 's')
                    ->where('s.rn', 1)
                    ->selectRaw("
          s.store_id,
          s.store_name,
          s.bid_amount_min,
          s.bid_amount_max,
          DENSE_RANK() OVER (
            ORDER BY s.bid_amount_min ASC, s.bid_amount_max ASC
          ) AS bid_rank,
          COUNT(*) OVER (
            PARTITION BY s.bid_amount_min, s.bid_amount_max
          ) AS tie_count
        ");
            }, 'r')
            ->orderBy('r.bid_rank')
            ->orderBy('r.bid_amount_min')
            ->orderBy('r.bid_amount_max')
            ->orderBy('r.store_name')
            ->select([
                'r.bid_rank',
                'r.store_id',
                'r.store_name',            // ★ 忘れずに select する
                'r.bid_amount_min',
                'r.bid_amount_max',
                'r.tie_count',
            ])
            ->get();

        $estimate->setAttribute('bid_ranking_list', $ranked->map(fn($row) => [
            'rank'       => (int)$row->bid_rank,
            'store_id'   => $row->store_id,
            'store_name' => $row->store_name,        // ★ ここに入る
            'min'        => (int)$row->bid_amount_min,
            'max'        => (int)$row->bid_amount_max,
            'is_tie'     => ((int)$row->tie_count) > 1,
            'tie_count'  => (int)$row->tie_count,
        ]));

        return $estimate;
    }

    /**
     * 見積もりを作成
     */
    public function create(array $data): Estimate
    {
        return Estimate::create($data);
    }

    /**
     * 見積もりを更新
     */
    public function update(string $id, array $data): bool
    {
        $estimate = $this->findById($id);
        if (!$estimate) {
            return false;
        }

        return $estimate->update($data);
    }

    /**
     * 見積もりを削除
     */
    public function delete(string $id): bool
    {
        $estimate = $this->findById($id);
        if (!$estimate) {
            return false;
        }

        return $estimate->delete();
    }

    /**
     * 見積もり一覧を取得（全件）
     */
    public function getAll(): Collection
    {
        return Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category'
        ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * ステータス別に見積もり一覧を取得
     */
    public function getByStatus(string $status): Collection
    {
        return Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category'
        ])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 見積もり件数を取得
     */
    public function getCount(): int
    {
        return Estimate::count();
    }

    /**
     * ステータス別見積もり件数を取得
     */
    public function getCountByStatus(string $status): int
    {
        return Estimate::where('status', $status)->count();
    }

    /**
     * 見積もりが存在するかどうかを取得
     */
    public function isExists(string $id): bool
    {
        return Estimate::where('id', $id)->exists();
    }
}
