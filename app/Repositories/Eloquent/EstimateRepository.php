<?php

namespace App\Repositories\Eloquent;

use App\Models\Estimate;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EstimateRepository implements EstimateRepositoryInterface
{
    public function __construct(
        private Estimate $model
    ) {}

    /**
     * 見積もりを作成する
     */
    public function create(array $data): Estimate
    {
        return $this->model->create($data);
    }

    /**
     * IDで見積もりを取得する
     */
    public function findById(string $id): ?Estimate
    {
        return $this->model->find($id);
    }

    /**
     * 見積もり一覧を取得する
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * 見積もりを更新する
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
     * 見積もりを削除する
     */
    public function delete(string $id): bool
    {
        $estimate = $this->findById($id);
        if (!$estimate) {
            return false;
        }

        return $estimate->delete();
    }
}
