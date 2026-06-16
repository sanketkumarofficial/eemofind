<?php

namespace App\Repositories\Contracts;

interface RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 25);
    public function find(int|string $id);
    public function create(array $data);
    public function update(int|string $id, array $data);
    public function delete(int|string $id): bool;
}