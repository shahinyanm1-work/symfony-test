<?php

declare(strict_types=1);

namespace App\DTO;

class AggregationResponseDTO
{
    /**
     * @param array<int, array{group: string, count: int}> $data
     */
    public function __construct(
        public readonly array $data,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalPages,
        public readonly int $totalItems
    ) {
    }

    public function toArray(): array
    {
        return [
            'meta' => [
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total_pages' => $this->totalPages,
                'total_items' => $this->totalItems,
            ],
            'data' => $this->data,
        ];
    }
}
