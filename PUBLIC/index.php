<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/APP/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);

    for ($i = 0; $i < count($parts) - 1; $i++) {
        $parts[$i] = strtoupper($parts[$i]);
    }

    $file = $baseDir . implode('/', $parts) . '.php';

    if (file_exists($file)) {
        require_once $file;
    } else {
        $fileAlt = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($fileAlt)) {
            require_once $fileAlt;
        }
    }
});

use App\Controllers\EletronicoController;
use App\Controllers\EstoqueController;
use App\Core\Request;
use App\Views\JsonView;

try {
    $request = new Request();
    $method = $request->getMethod();
    $path = $request->getPath();

    $rota = "{$method} {$path}";

    switch ($rota) {
        case 'GET /eletronico':
            (new EletronicoController())->listar();
            break;

        case 'POST /eletronico':
            (new EletronicoController())->cadastrar($request);
            break;

        case 'PUT /eletronico':
            (new EletronicoController())->atualizar($request);
            break;

        case 'DELETE /eletronico':
            (new EletronicoController())->desativar($request);
            break;

        case 'PUT /estoque':
            (new EstoqueController())->movimentar($request);
            break;

        case 'GET /movimentacoes':
            (new EstoqueController())->historico($request);
            break;

        default:
            JsonView::error('Rota não encontrada.', 404, [
                'metodo' => $method,
                'rota' => $path
            ]);
            break;
    }
} catch (\Throwable $e) {
    JsonView::error('Erro interno na execução da requisição.', 500, [
        'mensagem' => $e->getMessage()
    ]);
}
