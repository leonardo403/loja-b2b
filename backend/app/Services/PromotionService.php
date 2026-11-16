<?php

namespace App\Services;

use App\DTO\PaginatedDTO;
use App\DTO\PromotionDTO;
use App\Exceptions\BusinessException;
use App\Models\Promotion;
use App\Repositories\Contracts\PromotionRepositoryInterface;

class PromotionService
{
    public function __construct(
        private readonly PromotionRepositoryInterface $promotions,
    ) {}

    public function list(array $filters, int $perPage): PaginatedDTO
    {
        return PaginatedDTO::fromPaginator(
            $this->promotions->paginate($filters, $perPage),
            fn (Promotion $promotion) => PromotionDTO::fromModel($promotion),
        );
    }

    public function find(int $id): PromotionDTO
    {
        $promotion = $this->promotions->find($id);

        if ($promotion === null) {
            throw new BusinessException('Promoção não encontrada.');
        }

        return PromotionDTO::fromModel($promotion);
    }

    public function create(array $data): PromotionDTO
    {
        return PromotionDTO::fromModel($this->promotions->create($data));
    }

    public function update(Promotion $promotion, array $data): PromotionDTO
    {
        return PromotionDTO::fromModel($this->promotions->update($promotion, $data));
    }

    public function delete(Promotion $promotion): void
    {
        $this->promotions->delete($promotion);
    }
}
