# Atacado Prime · Loja B2B

MVP de comércio atacadista da Loja B2B com API Laravel + frontend Vue 3. Cada empresa cliente
acessa o catálogo com a sua tabela de preços, faixas de atacado, promoções, limite de
crédito e condições de pagamento. O administrador gerencia catálogo, clientes, preços,
promoções, condições de pagamento e pedidos.

## Stack

- **Backend:** PHP 8.4, Laravel 13, Sanctum, MySQL 8, PHPUnit/Pest
- **Frontend:** Vue 3, TypeScript, Vite, Pinia, Vue Router, Tailwind CSS 4, Lucide
- **Infra:** Docker Compose (MySQL + API + Vite)

## Como rodar

### Docker (tudo incluso)

```bash
docker compose up --build
```

- Loja: http://localhost:5173
- API: http://localhost:8001/api
- MySQL: localhost:3306 (`loja_b2b` / `loja` / `loja`)

Na primeira subida o backend instala dependências, gera a chave, aguarda o MySQL,
executa as migrations e importa os JSONs de `data/`.

### Local

Pré-requisitos: PHP 8.4+, Composer, Node 20+, MySQL 8.

```bash
# MySQL
docker compose up -d mysql

# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001

# Frontend (outro terminal)
cd frontend
npm install
npm run dev
```

Vite faz proxy de `/api` para `http://localhost:8001` (configurável em
`VITE_PROXY_TARGET`).

## Contas de demonstração

| Perfil | E-mail | Senha | Observação |
| --- | --- | --- | --- |
| Administrador | `admin@loja.test` | `admin123` | Painel `/admin` |
| Bom Preço | `compras@bompreco.com.br` | `senha123` | Tabela `PADRAO` |
| Atacadão Central | `compras@atacadocentral.com.br` | `senha123` | Tabela `ATACADISTA` |
| Empório São Jorge | `compras@emporiosaojorge.com.br` | `senha123` | Cliente bloqueado |

## Regras de negócio

- O preço unitário vem da tabela de preço do cliente (`PADRAO` como fallback).
- Ao atingir `wholesale_min_quantity`, aplica-se o `wholesale_price` da tabela.
- Promoções ativas aplicam desconto percentual sobre o preço vigente.
- O checkout valida cliente bloqueado, pedido mínimo da condição, crédito disponível
  e estoque (com lock transacional).
- Ao criar um pedido: baixa estoque e incrementa `credit_used`.
- Ao cancelar um pedido: devolve estoque e libera o crédito utilizado.
- Condições de pagamento podem ter acréscimo percentual e valor mínimo.

## Estrutura

```text
backend/
  app/
    DTO/            # contratos de saída da API
    Enums/          # UserRole, OrderStatus
    Http/           # controllers, requests, middleware
    Models/
    Repositories/   # contratos + implementações Eloquent
    Services/       # regras de negócio (Pricing, Cart, Order, ...)
  database/seeders/CommerceSeeder.php
  routes/api.php
frontend/
  src/
    components/     # UI reutilizável
    layouts/        # loja e painel admin
    services/       # clientes HTTP por domínio
    stores/         # auth, cart, toast (Pinia)
    views/store/    # catálogo, produto, carrinho, checkout, pedidos
    views/admin/    # dashboard e CRUDs
data/               # JSONs originais do desafio
docker-compose.yml
```

## Endpoints principais

Públicos: `GET /api/products`, `GET /api/products/{id}`,
`GET /api/catalog/featured`, `GET /api/catalog/filters`,
`GET /api/payment-conditions`, `POST /api/auth/login`.

Cliente autenticado: `POST /api/cart/preview`, `GET/POST /api/orders`,
`GET /api/orders/{id}`, `GET /api/auth/me`, `POST /api/auth/logout`.

Admin (`/api/admin/*`): dashboard, produtos, clientes, tabelas de preço,
promoções, condições de pagamento e pedidos (incluindo alteração de status).

## Scripts úteis

```bash
# Backend
php artisan migrate:fresh --seed
php artisan test

# Frontend
npm run build      # type-check + build de produção
npm run dev
```
