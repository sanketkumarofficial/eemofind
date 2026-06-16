<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class EloquentRepository implements RepositoryInterface
{
    public function __construct(protected Model $model)
    {
    }

    public function paginate(array $filters = [], int $perPage = 25)
    {
        return $this->model->newQuery()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int|string $id)
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int|string $id, array $data)
    {
        $record = $this->find($id);
        $record->fill($data)->save();
        return $record;
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->find($id)->delete();
    }
}