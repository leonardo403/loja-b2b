<?php

namespace App\Services;

use App\DTO\CartDTO;
use App\DTO\CartItemDTO;
use App\DTO\PaymentConditionDTO;
use App\Exceptions\BusinessException;
use App\Models\Customer;
use App\Repositories\Contracts\PaymentConditionRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;

class CartService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly PaymentConditionRepositoryInterface $paymentConditions,
        private readonly PricingService $pricing,
    ) {}

    public function preview(Customer $customer, array $items, ?string $paymentCode = null): CartDTO
    {
        $normalized = $this->normalizeItems($items);

        if ($normalized === []) {
            return new CartDTO([], 0, 0, 0, 0, 0, 0, 0, null);
        }

        $products = $this->products->findManyWithRelations(array_keys($normalized));
        $cartItems = [];
        $warnings = [];

        foreach ($normalized as $productId => $quantity) {
            $product = $products->get($productId);

            if ($product === null || ! $product->active) {
                throw new BusinessException('Um dos produtos do carrinho não está mais disponível.');
            }

            $stockQuantity = (int) ($product->stock?->quantity ?? 0);

            if ($quantity > $stockQuantity) {
                $warnings[] = "Estoque insuficiente para \"{$product->name}\". Disponível: {$stockQuantity} un.";
            }

            $price = $this->pricing->priceForCustomer($product, $customer, $quantity);

            $cartItems[] = new CartItemDTO(
                productId: $product->id,
                sku: $product->sku,
                name: $product->name,
                brand: $product->brand,
                category: $product->category,
                quantity: $quantity,
                stockQuantity: $stockQuantity,
                listPrice: $price->listPrice,
                unitPrice: $price->unitPrice,
                discountPercentage: $price->discountPercentage,
                wholesaleApplied: $price->wholesaleApplied,
                lineSubtotal: round($price->unitPrice * $quantity, 2),
                lineListTotal: round($price->listPrice * $quantity, 2),
            );
        }

        $subtotal = round(array_sum(array_map(fn (CartItemDTO $item) => $item->lineSubtotal, $cartItems)), 2);
        $listTotal = round(array_sum(array_map(fn (CartItemDTO $item) => $item->lineListTotal, $cartItems)), 2);
        $savings = round($listTotal - $subtotal, 2);

        $condition = null;

        if ($paymentCode !== null && $paymentCode !== '') {
            $condition = $this->paymentConditions->findByCode($paymentCode);

            if ($condition === null) {
                throw new BusinessException('Condição de pagamento inválida.');
            }
        }

        $surchargePercentage = $condition ? (float) $condition->surcharge_percentage : 0.0;
        $surcharge = round($subtotal * $surchargePercentage / 100, 2);
        $total = round($subtotal + $surcharge, 2);

        if ($condition !== null && $subtotal < (float) $condition->minimum_value) {
            $minimum = number_format((float) $condition->minimum_value, 2, ',', '.');
            $warnings[] = "A condição {$condition->description} exige pedido mínimo de R$ {$minimum}.";
        }

        if ($customer->blocked) {
            $warnings[] = 'Seu cadastro está bloqueado. Entre em contato com o comercial para liberar novos pedidos.';
        }

        if ($total > $customer->availableCredit()) {
            $available = number_format($customer->availableCredit(), 2, ',', '.');
            $warnings[] = "Pedido acima do limite de crédito disponível (R$ {$available}).";
        }

        return new CartDTO(
            items: $cartItems,
            itemCount: count($cartItems),
            totalUnits: array_sum(array_map(fn (CartItemDTO $item) => $item->quantity, $cartItems)),
            subtotal: $subtotal,
            savings: $savings,
            surchargePercentage: $surchargePercentage,
            surcharge: $surcharge,
            total: $total,
            paymentCondition: $condition ? PaymentConditionDTO::fromModel($condition) : null,
            warnings: $warnings,
        );
    }

    /**
     * @return array<int, int>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw new BusinessException('Itens do carrinho inválidos.');
            }

            $normalized[$productId] = ($normalized[$productId] ?? 0) + $quantity;
        }

        return $normalized;
    }
}
