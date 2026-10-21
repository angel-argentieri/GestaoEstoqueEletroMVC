<?php
/**
 * Configuração Geral da Aplicação
 * Gestão de Estoque de Eletrônicos MVC
 */

declare(strict_types=1);

return [
    'db' => [
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'port'     => (int)(getenv('DB_PORT') ?: 3306),
        'dbname'   => getenv('DB_NAME') ?: 'gestao_estoque_eletro',
        'user'     => getenv('DB_USER') ?: 'root',
        'pass'     => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '12345678',
        'charset'  => 'utf8mb4',
    ],
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    ],
    'app' => [
        'default_usuario_id' => 1, // ID do Administrador para registro de movimentações sem autenticação
    ]
];
