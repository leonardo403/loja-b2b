<?php

namespace App\Services;

use App\DTO\OrderDTO;
use App\DTO\PaginatedDTO;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\StockRepositoryInterface;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderRepositoryInterface $orders,
        private readonly StockRepositoryInterface $stocks,
    ) {}

    public function create(
        Customer $customer,
        User $user,
        array $items,
        string $paymentCode,
        ?string $notes = null,
    ): OrderDTO {
        if ($customer->blocked) {
            throw new BusinessException($customer->block_reason
                ? "Seu cadastro está bloqueado: {$customer->block_reason}"
                : 'Seu cadastro está bloqueado para novos pedidos.');
        }

        $cart = $this->cart->preview($customer, $items, $paymentCode);

        if ($cart->itemCount === 0) {
            throw new BusinessException('Adicione produtos ao carrinho para finalizar o pedido.');
        }

        if ($cart->paymentCondition === null) {
            throw new BusinessException('Selecione uma condição de pagamento.');
        }

        if ($cart->subtotal < $cart->paymentCondition->minimumValue) {
            throw new BusinessException(sprintf(
                'A condição %s exige pedido mínimo de R$ %s.',
                $cart->paymentCondition->description,
                number_format($cart->paymentCondition->minimumValue, 2, ',', '.'),
            ));
        }

        if ($cart->total > $customer->availableCredit()) {
            throw new BusinessException(sprintf(
                'Limite de crédito insuficiente. Disponível: R$ %s.',
                number_format($customer->availableCredit(), 2, ',', '.'),
            ));
        }

        $order = DB::transaction(function () use ($customer, $user, $cart, $notes) {
            $lockedStocks = $this->stocks->lockByProductIds(
                array_map(fn ($item) => $item->productId, $cart->items)
            );

            foreach ($cart->items as $item) {
                $stock = $lockedStocks->get($item->productId);

                if ($stock === null || (int) $stock->quantity < $item->quantity) {
                    throw new BusinessException(
                        "Estoque insuficiente para \"{$item->name}\". Disponível: ".($stock?->quantity ?? 0).' un.'
                    );
                }
            }

            $order = $this->orders->create(
                [
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'payment_condition_id' => $cart->paymentCondition->id,
                    'status' => OrderStatus::Confirmed,
                    'subtotal' => $cart->subtotal,
                    'savings' => $cart->savings,
                    'surcharge' => $cart->surcharge,
                    'total' => $cart->total,
                    'notes' => $notes,
                ],
                array_map(fn ($item) => [
                    'product_id' => $item->productId,
                    'product_name' => $item->name,
                    'product_sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'list_price' => $item->listPrice,
                    'unit_price' => $item->unitPrice,
                    'discount_percentage' => $item->discountPercentage,
                    'wholesale_applied' => $item->wholesaleApplied,
                    'subtotal' => $item->lineSubtotal,
                ], $cart->items),
            );

            foreach ($cart->items as $item) {
                $this->stocks->decrement($item->productId, $item->quantity);
            }

            $customer->increment('credit_used', $cart->total);

            return $order;
        });

        Cache::forget(CacheKeys::ADMIN_DASHBOARD_STATS);

        return OrderDTO::fromModel($order);
    }

    public function listForCustomer(Customer $customer, int $perPage): PaginatedDTO
    {
        return PaginatedDTO::fromPaginator(
            $this->orders->paginateForCustomer($customer->id, $perPage),
            fn (Order $order) => OrderDTO::fromModel($order),
        );
    }

    public function findForCustomer(Customer $customer, int $orderId): OrderDTO
    {
        $order = $this->orders->findWithRelations($orderId);

        if ($order === null || $order->customer_id !== $customer->id) {
            throw new BusinessException('Pedido não encontrado.');
        }

        return OrderDTO::fromModel($order);
    }

    public function adminList(array $filters, int $perPage): PaginatedDTO
    {
        return PaginatedDTO::fromPaginator(
            $this->orders->paginateAll($filters, $perPage),
            fn (Order $order) => OrderDTO::fromModel($order),
        );
    }

    public function find(int $orderId): OrderDTO
    {
        $order = $this->orders->findWithRelations($orderId);

        if ($order === null) {
            throw new BusinessException('Pedido não encontrado.');
        }

        return OrderDTO::fromModel($order);
    }

    public function changeStatus(Order $order, OrderStatus $status): OrderDTO
    {
        DB::transaction(function () use ($order, $status) {
            $locked = Order::query()
                ->with(['items', 'customer'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($locked->status === OrderStatus::Canceled && $status !== OrderStatus::Canceled) {
                throw new BusinessException('Pedidos cancelados não podem ser reabertos.');
            }

            if ($status === OrderStatus::Canceled
                && $locked->status !== OrderStatus::Canceled
                && $locked->status->canBeCanceled()) {
                foreach ($locked->items as $item) {
                    $this->stocks->increment($item->product_id, $item->quantity);
                }

                $newUsed = max(0, (float) $locked->customer->credit_used - (float) $locked->total);
                $locked->customer->update(['credit_used' => $newUsed]);
            }

            $this->orders->updateStatus($locked, $status);
        });

        Cache::forget(CacheKeys::ADMIN_DASHBOARD_STATS);

        return $this->find($order->id);
    }
}
