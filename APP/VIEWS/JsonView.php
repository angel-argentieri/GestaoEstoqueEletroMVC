<?php
declare(strict_types=1);

namespace App\Views;

class JsonView
{
    public static function render(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $mensagem = '', int $statusCode = 200): void
    {
        $payload = [
            'status' => 'sucesso',
            'mensagem' => $mensagem
        ];

        if ($data !== null) {
            $payload['dados'] = $data;
        }

        self::render($payload, $statusCode);
    }

    public static function error(string $mensagem, int $statusCode = 400, mixed $detalhes = null): void
    {
        $payload = [
            'status' => 'erro',
            'mensagem' => $mensagem
        ];

        if ($detalhes !== null) {
            $payload['detalhes'] = $detalhes;
        }

        self::render($payload, $statusCode);
    }
}
