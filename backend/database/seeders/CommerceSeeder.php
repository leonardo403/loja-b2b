<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommerceSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $priceTableIds = [];

    public function run(): void
    {
        if (DB::table('products')->exists() && ! env('SEED_FORCE', false)) {
            $this->command?->info('Dados já existentes: seed do catálogo ignorado.');

            return;
        }

        $dataPath = (string) config('commerce.data_path');

        $this->ensureDataFiles($dataPath);

        DB::transaction(function () use ($dataPath) {
            $this->seedPriceTables($dataPath);
            $this->seedProducts($dataPath);
            $this->seedStocks($dataPath);
            $this->seedPriceTableItems($dataPath);
            $this->seedPromotions($dataPath);
            $this->seedPaymentConditions($dataPath);
            $this->seedCustomers($dataPath);
            $this->seedUsers();
        });

        $this->command?->info('Catálogo B2B importado com sucesso.');
    }

    private function ensureDataFiles(string $dataPath): void
    {
        foreach (['produtos', 'estoque', 'tabelas_preco', 'promocoes', 'condicoes_pagamento', 'clientes'] as $file) {
            if (! is_file("{$dataPath}/{$file}.json")) {
                throw new \RuntimeException("Arquivo de dados não encontrado: {$dataPath}/{$file}.json");
            }
        }
    }

    private function read(string $dataPath, string $file): array
    {
        return json_decode(file_get_contents("{$dataPath}/{$file}.json"), true, 512, JSON_THROW_ON_ERROR);
    }

    private function seedPriceTables(string $dataPath): void
    {
        foreach ($this->read($dataPath, 'tabelas_preco') as $table) {
            DB::table('price_tables')->updateOrInsert(
                ['code' => $table['codigo']],
                [
                    'description' => $table['descricao'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $this->priceTableIds[$table['codigo']] = (int) DB::table('price_tables')
                ->where('code', $table['codigo'])
                ->value('id');
        }
    }

    private function seedProducts(string $dataPath): void
    {
        $rows = array_map(fn (array $product) => [
            'id' => $product['id'],
            'sku' => $product['sku'],
            'ean' => $product['ean'] ?? null,
            'name' => $product['nome'],
            'description' => $product['descricao'] ?? null,
            'brand' => $product['marca'] ?? null,
            'category' => $product['categoria'] ?? null,
            'active' => (bool) ($product['ativo'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->read($dataPath, 'produtos'));

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('products')->upsert(
                $chunk,
                ['id'],
                ['sku', 'ean', 'name', 'description', 'brand', 'category', 'active', 'updated_at'],
            );
        }
    }

    private function seedStocks(string $dataPath): void
    {
        $rows = array_map(fn (array $stock) => [
            'product_id' => $stock['produto_id'],
            'quantity' => $stock['quantidade'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->read($dataPath, 'estoque'));

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('stocks')->upsert($chunk, ['product_id'], ['quantity', 'updated_at']);
        }
    }

    private function seedPriceTableItems(string $dataPath): void
    {
        $rows = [];

        foreach ($this->read($dataPath, 'tabelas_preco') as $table) {
            foreach ($table['itens'] as $item) {
                $rows[] = [
                    'price_table_id' => $this->priceTableIds[$table['codigo']],
                    'product_id' => $item['produto_id'],
                    'price' => $item['preco'],
                    'wholesale_price' => $item['preco_atacado'] ?? null,
                    'wholesale_min_quantity' => $item['qtd_minima_atacado'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('price_table_items')->upsert(
                $chunk,
                ['price_table_id', 'product_id'],
                ['price', 'wholesale_price', 'wholesale_min_quantity', 'updated_at'],
            );
        }
    }

    private function seedPromotions(string $dataPath): void
    {
        $rows = array_map(fn (array $promotion) => [
            'id' => $promotion['id'],
            'product_id' => $promotion['produto_id'],
            'discount_percentage' => $promotion['percentual_desconto'],
            'starts_at' => $promotion['inicio'],
            'ends_at' => $promotion['fim'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->read($dataPath, 'promocoes'));

        DB::table('promotions')->upsert(
            $rows,
            ['id'],
            ['product_id', 'discount_percentage', 'starts_at', 'ends_at', 'updated_at'],
        );
    }

    private function seedPaymentConditions(string $dataPath): void
    {
        $rows = array_map(fn (array $condition) => [
            'code' => $condition['codigo'],
            'description' => $condition['descricao'],
            'minimum_value' => $condition['valor_minimo'],
            'surcharge_percentage' => $condition['acrescimo_percentual'],
            'is_term' => (bool) $condition['a_prazo'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->read($dataPath, 'condicoes_pagamento'));

        DB::table('payment_conditions')->upsert(
            $rows,
            ['code'],
            ['description', 'minimum_value', 'surcharge_percentage', 'is_term', 'updated_at'],
        );
    }

    private function seedCustomers(string $dataPath): void
    {
        foreach ($this->read($dataPath, 'clientes') as $customer) {
            DB::table('customers')->updateOrInsert(
                ['cnpj' => $customer['cnpj']],
                [
                    'legal_name' => $customer['razao_social'],
                    'trade_name' => $customer['nome_fantasia'],
                    'city' => $customer['cidade'],
                    'state' => $customer['uf'],
                    'price_table_id' => $this->priceTableIds[$customer['tabela_preco']],
                    'credit_limit' => $customer['limite_credito'],
                    'credit_used' => $customer['limite_utilizado'],
                    'blocked' => (bool) $customer['bloqueado'],
                    'block_reason' => $customer['motivo_bloqueio'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function seedUsers(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@loja.test'],
            [
                'name' => 'Administrador',
                'password' => config('commerce.seed_admin_password'),
                'role' => UserRole::Admin,
                'customer_id' => null,
            ],
        );

        $accounts = [
            '12.345.678/0001-90' => 'compras@bompreco.com.br',
            '98.765.432/0001-10' => 'compras@atacadocentral.com.br',
            '11.222.333/0001-44' => 'compras@emporiosaojorge.com.br',
        ];

        foreach ($accounts as $cnpj => $email) {
            $customer = Customer::query()->where('cnpj', $cnpj)->first();

            if ($customer === null) {
                continue;
            }

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $customer->trade_name,
                    'password' => config('commerce.seed_customer_password'),
                    'role' => UserRole::Customer,
                    'customer_id' => $customer->id,
                ],
            );
        }
    }
}
