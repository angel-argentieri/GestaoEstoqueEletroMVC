<?php
/**
 * Emissor de Respostas JSON
 */

declare(strict_types=1);

namespace App\Views;

class JsonView
{
    /**
     * Emite resposta JSON com status HTTP
     */
    public static function render(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Resposta padronizada de sucesso
     */
    public static function success(mixed $data = null, string $message = 'Sucesso', int $statusCode = 200): void
    {
        $response = [
            'status'   => 'sucesso',
            'mensagem' => $message,
        ];

        if ($data !== null) {
            $response['dados'] = $data;
        }

        self::render($response, $statusCode);
    }

    /**
     * Resposta padronizada de erro
     */
    public static function error(string $message, int $statusCode = 400, mixed $details = null): void
    {
        $response = [
            'status'   => 'erro',
            'mensagem' => $message,
        ];

        if ($details !== null) {
            $response['detalhes'] = $details;
        }

        self::render($response, $statusCode);
    }
}
