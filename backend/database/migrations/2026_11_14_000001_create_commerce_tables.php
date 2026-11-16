<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tables', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('ean', 20)->nullable()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('brand')->nullable()->index();
            $table->string('category')->nullable()->index();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('price_table_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->unsignedInteger('wholesale_min_quantity')->nullable();
            $table->timestamps();

            $table->unique(['price_table_id', 'product_id']);
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_percentage', 5, 2);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->timestamps();

            $table->index(['product_id', 'starts_at', 'ends_at']);
        });

        Schema::create('payment_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description');
            $table->decimal('minimum_value', 12, 2)->default(0);
            $table->decimal('surcharge_percentage', 5, 2)->default(0);
            $table->boolean('is_term')->default(false);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj', 20)->unique();
            $table->string('legal_name');
            $table->string('trade_name');
            $table->string('city');
            $table->string('state', 2);
            $table->foreignId('price_table_id')->constrained();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->decimal('credit_used', 14, 2)->default(0);
            $table->boolean('blocked')->default(false);
            $table->string('block_reason')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->index();
            $table->foreignId('customer_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_condition_id')->constrained();
            $table->string('status', 20)->default('confirmed')->index();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('savings', 14, 2)->default(0);
            $table->decimal('surcharge', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name');
            $table->string('product_sku');
            $table->unsignedInteger('quantity');
            $table->decimal('list_price', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->boolean('wholesale_applied')->default(false);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('customers');
        Schema::dropIfExists('payment_conditions');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('price_table_items');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('price_tables');
    }
};
