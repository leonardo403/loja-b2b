<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['active', 'category']);
            $table->index(['active', 'brand']);
            $table->index(['active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['active', 'category']);
            $table->dropIndex(['active', 'brand']);
            $table->dropIndex(['active', 'name']);
        });
    }
};
