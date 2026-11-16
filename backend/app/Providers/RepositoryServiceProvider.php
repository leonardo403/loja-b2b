<?php

namespace App\Providers;

use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentConditionRepositoryInterface;
use App\Repositories\Contracts\PriceTableRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\PromotionRepositoryInterface;
use App\Repositories\Contracts\StockRepositoryInterface;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\PaymentConditionRepository;
use App\Repositories\Eloquent\PriceTableRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\PromotionRepository;
use App\Repositories\Eloquent\StockRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $bindings = [
            ProductRepositoryInterface::class => ProductRepository::class,
            CustomerRepositoryInterface::class => CustomerRepository::class,
            OrderRepositoryInterface::class => OrderRepository::class,
            PaymentConditionRepositoryInterface::class => PaymentConditionRepository::class,
            PriceTableRepositoryInterface::class => PriceTableRepository::class,
            PromotionRepositoryInterface::class => PromotionRepository::class,
            StockRepositoryInterface::class => StockRepository::class,
        ];

        foreach ($bindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}
