<?php

namespace Tests\Feature\Business;

use App\Exceptions\BusinessException;
use App\Models\PriceTable;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\PricingService;
use Database\Seeders\CommerceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesCommerceFixtures;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use CreatesCommerceFixtures;
    use RefreshDatabase;


    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-16 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_customer_price_table_takes_precedence_over_default_table(): void
    {
        $default = $this->defaultPriceTable();
        $special = PriceTable::factory()->create(['code' => 'ATACADISTA']);
        $product = $this->productWithPrice($default, price: ['price' => 10.00]);

        $this->priceItem($product, $special, ['price' => 12.00]);
        $product = $product->fresh(['stock', 'priceItems', 'promotions']);
        $customer = $this->customerFor($special);

        $price = app(PricingService::class)->priceForCustomer($product, $customer);

        $this->assertEqualsWithDelta(12.00, $price->unitPrice, 0.0001);
        $this->assertEqualsWithDelta(12.00, $price->listPrice, 0.0001);
        $this->assertFalse($price->wholesaleApplied);
    }

    /*public function test_falls_back_to_default_table_when_specific_table_has_no_item(): void
    {
        $this->seed(CommerceSeeder::class);

        $default = PriceTable::query()->where('code', 'PADRAO')->firstOrFail();
        $special = PriceTable::query()->where('code', 'ATACADISTA')->firstOrFail();
        $product = Product::query()
            ->whereHas('priceItems', fn ($query) => $query->where('price_table_id', $default->id))
            ->whereDoesntHave('priceItems', fn ($query) => $query->where('price_table_id', $special->id))
            ->with(['stock', 'priceItems', 'promotions'])
            ->firstOrFail();
        $expectedPrice = $product->priceItems->firstWhere('price_table_id', $default->id)->price;
        $customer = $this->customerFor($special);

        $price = app(PricingService::class)->priceForCustomer($product, $customer);

        $this->assertEqualsWithDelta((float) $expectedPrice, $price->listPrice, 0.0001);
    }*/

    public function test_applies_wholesale_price_only_at_minimum_quantity(): void
    {
        $table = $this->defaultPriceTable();
        $product = $this->productWithPrice($table, price: [
            'price' => 10.00,
            'wholesale_price' => 8.00,
            'wholesale_min_quantity' => 3,
        ]);
        $customer = $this->customerFor($table);
        $pricing = app(PricingService::class);

        $below = $pricing->priceForCustomer($product, $customer, 2);
        $atMinimum = $pricing->priceForCustomer($product, $customer, 3);

        $this->assertEqualsWithDelta(10.00, $below->unitPrice, 0.0001);
        $this->assertFalse($below->wholesaleApplied);
        $this->assertEqualsWithDelta(8.00, $atMinimum->unitPrice, 0.0001);
        $this->assertTrue($atMinimum->wholesaleApplied);
    }

    public function test_applies_active_promotion_percentage(): void
    {
        $table = $this->defaultPriceTable();
        $product = $this->productWithPrice($table, price: ['price' => 10.00]);

        Promotion::factory()->create([
            'product_id' => $product->id,
            'discount_percentage' => 10.00,
        ]);

        $product = $product->fresh(['stock', 'priceItems', 'promotions']);
        $customer = $this->customerFor($table);

        $price = app(PricingService::class)->priceForCustomer($product, $customer);

        $this->assertEqualsWithDelta(9.00, $price->unitPrice, 0.0001);
        $this->assertEqualsWithDelta(10.00, $price->discountPercentage, 0.0001);
        $this->assertTrue($price->onPromotion);
        $this->assertSame('2026-11-17', $price->promotionEndsAt);
    }

    public function test_ignores_expired_promotion(): void
    {
        $table = $this->defaultPriceTable();
        $product = $this->productWithPrice($table, price: ['price' => 10.00]);

        Promotion::factory()->expired()->create([
            'product_id' => $product->id,
            'discount_percentage' => 10.00,
        ]);

        $product = $product->fresh(['stock', 'priceItems', 'promotions']);
        $customer = $this->customerFor($table);

        $price = app(PricingService::class)->priceForCustomer($product, $customer);

        $this->assertEqualsWithDelta(10.00, $price->unitPrice, 0.0001);
        $this->assertFalse($price->onPromotion);
    }

    public function test_promotion_applies_on_top_of_wholesale_price(): void
    {
        $table = $this->defaultPriceTable();
        $product = $this->productWithPrice($table, price: [
            'price' => 10.00,
            'wholesale_price' => 8.00,
            'wholesale_min_quantity' => 2,
        ]);

        Promotion::factory()->create([
            'product_id' => $product->id,
            'discount_percentage' => 10.00,
        ]);

        $product = $product->fresh(['stock', 'priceItems', 'promotions']);
        $customer = $this->customerFor($table);

        $price = app(PricingService::class)->priceForCustomer($product, $customer, 2);

        $this->assertEqualsWithDelta(7.20, $price->unitPrice, 0.0001);
        $this->assertTrue($price->wholesaleApplied);
        $this->assertTrue($price->onPromotion);
    }

    public function test_throws_when_product_is_unavailable_in_all_price_tables(): void
    {
        $table = $this->defaultPriceTable();
        $product = Product::factory()->create();
        $customer = $this->customerFor($table);

        $this->expectException(BusinessException::class);

        app(PricingService::class)->priceForCustomer($product, $customer);
    }
}
