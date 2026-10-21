<?php
/**
 * Conexão com o Banco de Dados via PDO
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Retorna a instância única de PDO (Singleton)
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $configPath = dirname(__DIR__, 2) . '/CONFIG/config.php';
            if (!file_exists($configPath)) {
                $configPath = dirname(__DIR__, 2) . '/config/config.php';
            }
            $config = require $configPath;
            $db = $config['db'];

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['dbname'],
                $db['charset']
            );

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Tenta fallback sem senha caso o MySQL local não utilize senha
                if (!empty($db['pass'])) {
                    try {
                        $fallbackInstance = new PDO($dsn, $db['user'], '', [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES   => false,
                        ]);
                        self::$instance = $fallbackInstance;
                        return self::$instance;
                    } catch (PDOException $fallbackEx) {
                        // mantém erro original
                    }
                }

                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'   => 'erro',
                    'mensagem' => 'Falha na conexão com o banco de dados.',
                    'detalhes' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        return self::$instance;
    }
}
