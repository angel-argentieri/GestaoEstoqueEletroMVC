<?php
/**
 * Abstração da Requisição HTTP
 */

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $queryParams;
    private array $body;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Tratamento do caminho da URI
        $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedPath = parse_url($rawUri, PHP_URL_PATH) ?: '/';

        // Remove prefixos conhecidos de diretórios comuns no XAMPP
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir);

        if ($scriptDir !== '/' && !empty($scriptDir) && strpos($parsedPath, $scriptDir) === 0) {
            $parsedPath = substr($parsedPath, strlen($scriptDir));
        }

        // Remove também se a chamada vier sem /public mas direcionada
        $parsedPath = preg_replace('#^/GestaoEstoqueEletroMVC(/public)?#i', '', $parsedPath);

        // Garante barra inicial e remove barra final se não for a raiz
        $parsedPath = '/' . trim($parsedPath, '/');
        $this->path = $parsedPath === '/' ? '/' : rtrim($parsedPath, '/');

        // Parâmetros de Query String
        $this->queryParams = $_GET;

        // Corpo da requisição (JSON ou POST formulário)
        $this->body = [];
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            if (is_array($jsonData)) {
                $this->body = $jsonData;
            }
        }

        if (empty($this->body) && !empty($_POST)) {
            $this->body = $_POST;
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }
        return $this->queryParams[$key] ?? $default;
    }
}
