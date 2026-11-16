<?php

namespace App\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginatedDTO extends DataTransferObject
{
    /**
     * @param  array<int, DataTransferObject>  $items
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly array $items,
        public readonly array $meta,
        public readonly array $extra = [],
    ) {}

    /**
     * @param  callable(mixed): DataTransferObject  $mapper
     * @param  array<string, mixed>  $extra
     */
    public static function fromPaginator(LengthAwarePaginator $paginator, callable $mapper, array $extra = []): self
    {
        $items = array_map($mapper, $paginator->items());

        return new self(
            items: array_values($items),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            extra: $extra,
        );
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(fn (DataTransferObject $item) => $item->toArray(), $this->items),
            'meta' => $this->meta,
        ] + $this->extra;
    }
}
