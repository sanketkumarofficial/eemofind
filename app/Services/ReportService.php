<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportService
{
    public function summary(string $table, array $filters = []): array
    {
        $query = DB::table($table);

        if (($filters['from'] ?? null) && ($filters['to'] ?? null)) {
            $query->whereBetween('created_at', [$filters['from'], $filters['to']]);
        }

        return [
            'table' => $table,
            'count' => (clone $query)->count(),
            'generated_at' => now()->toISOString(),
        ];
    }

    public function handle(array $payload = []): array
    {
        return $this->summary($payload['table'], $payload['filters'] ?? []);
    }
}
