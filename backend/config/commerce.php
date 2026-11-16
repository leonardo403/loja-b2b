<?php

return [
    'data_path' => env('DATA_PATH', base_path('../data')),
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 20),
    'seed_admin_password' => env('SEED_ADMIN_PASSWORD', 'admin123'),
    'seed_customer_password' => env('SEED_CUSTOMER_PASSWORD', 'senha123'),
];
