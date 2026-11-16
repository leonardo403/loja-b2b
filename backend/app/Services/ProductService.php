<?php

namespace App\Services;

use App\DTO\PaginatedDTO;
use App\DTO\ProductDTO;
use App\Exceptions\BusinessException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\PriceTableRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly StockRepositoryInterface $stocks,
        private readonly PricingService $pricing,
        private readonly PriceTableRepositoryInterface $priceTables,
    ) {}

    public function list(array $filters, int $perPage, ?User $user): PaginatedDTO
    {
        $customer = $this->customerFor($user);
        $paginator = $this->products->paginate($filters, $perPage);

        return PaginatedDTO::fromPaginator(
            $paginator,
            fn (Product $product) => $this->toDto($product, $customer),
            [
                'filters' => [
                    'categories' => $this->products->categories(),
                    'brands' => $this->products->brands(),
                ],
            ],
        );
    }

    public function featured(int $limit, ?User $user): array
    {
        $customer = $this->customerFor($user);

        return $this->products->featured($limit)
            ->map(fn (Product $product) => $this->toDto($product, $customer)->toArray())
            ->all();
    }

    /**
     * @return array{categories: array<int, string>, brands: array<int, string>}
     */
    public function filters(): array
    {
        return [
            'categories' => $this->products->categories(),
            'brands' => $this->products->brands(),
        ];
    }

    public function detail(int $id, ?User $user): ProductDTO
    {
        $product = $this->products->find($id, true);

        if ($product === null) {
            throw new BusinessException('Produto não encontrado.');
        }

        return $this->toDto($product, $this->customerFor($user));
    }

    public function adminList(array $filters, int $perPage): PaginatedDTO
    {
        $paginator = $this->products->paginate($filters, $perPage, false);

        return PaginatedDTO::fromPaginator($paginator, fn (Product $product) => ProductDTO::fromModel($product));
    }

    public function create(array $data, int $stockQuantity): ProductDTO
    {
        $price = $data['price'];
        $wholesalePrice = $data['wholesale_price'] ?? null;
        $wholesaleMinQuantity = $data['wholesale_min_quantity'] ?? null;

        unset($data['price'], $data['wholesale_price'], $data['wholesale_min_quantity']);

        $product = DB::transaction(function () use (
            $data,
            $stockQuantity,
            $price,
            $wholesalePrice,
            $wholesaleMinQuantity,
        ) {
            $product = $this->products->create($data);
            $this->stocks->setQuantity($product->id, $stockQuantity);

            foreach ($this->priceTables->all() as $table) {
                $this->priceTables->upsertItem($table->id, $product->id, [
                    'price' => $price,
                    'wholesale_price' => $wholesalePrice,
                    'wholesale_min_quantity' => $wholesaleMinQuantity,
                ]);
            }

            return $product;
        });

        return $this->toDto($this->products->find($product->id), null);
    }

    public function update(Product $product, array $data, ?int $stockQuantity): ProductDTO
    {
        DB::transaction(function () use ($product, $data, $stockQuantity) {
            $this->products->update($product, $data);

            if ($stockQuantity !== null) {
                $this->stocks->setQuantity($product->id, $stockQuantity);
            }
        });

        return $this->toDto($this->products->find($product->id), null);
    }

    public function delete(Product $product): void
    {
        if ($product->orderItems()->exists()) {
            throw new BusinessException('Este produto possui pedidos vinculados e não pode ser excluído. Desative-o em vez disso.');
        }

        $this->products->delete($product);
    }

    private function customerFor(?User $user): ?Customer
    {
        if ($user === null || $user->customer_id === null) {
            return null;
        }

        return $user->customer;
    }

    private function toDto(Product $product, ?Customer $customer): ProductDTO
    {
        $table = $customer?->priceTable ?? $this->pricing->defaultTable();
        $quantity = 1;

        try {
            return ProductDTO::fromModel($product, $this->pricing->priceFor($product, $table, $quantity));
        } catch (BusinessException) {
            return ProductDTO::fromModel($product);
        }
    }
}
