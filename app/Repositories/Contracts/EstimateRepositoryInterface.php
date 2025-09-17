<?php

namespace App\Repositories\Contracts;

use App\Models\Estimate;
use Illuminate\Database\Eloquent\Collection;

interface EstimateRepositoryInterface
{
    /**
     * 見積もりを作成する
     */
    public function create(array $data): Estimate;

    /**
     * IDで見積もりを取得する
     */
    public function findById(string $id): ?Estimate;

    /**
     * 見積もり一覧を取得する
     */
    public function getAll(): Collection;

    /**
     * 見積もりを更新する
     */
    public function update(string $id, array $data): bool;

    /**
     * 見積もりを削除する
     */
    public function delete(string $id): bool;
}
