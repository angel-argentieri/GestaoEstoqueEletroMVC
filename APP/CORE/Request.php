<?php
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

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir);

        if ($scriptDir !== '/' && !empty($scriptDir) && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = preg_replace('#^/GestaoEstoqueEletroMVC(/public)?#i', '', $uri);
        $uri = '/' . trim($uri, '/');
        $this->path = $uri === '/' ? '/' : rtrim($uri, '/');

        $this->queryParams = $_GET;

        $this->body = [];
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $this->body = $json;
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
