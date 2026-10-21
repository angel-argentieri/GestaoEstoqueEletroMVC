<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../APP/CORE/Database.php';
require_once '../APP/CONTROLLERS/EletronicoController.php';
require_once '../APP/CONTROLLERS/EstoqueController.php';
require_once '../APP/VIEWS/JsonView.php';

$database = new Database();
$db = $database->getConnection();

$eletronicoController = new EletronicoController($db);
$estoqueController    = new EstoqueController($db);
$view                 = new JsonView();

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri  = preg_replace('#^/GestaoEstoqueEletroMVC(/public)?#i', '', $uri);
$rota = '/' . trim($uri, '/');

switch ("$method $rota") {
    case 'GET /eletronico':
        $eletronicoController->listar();
        break;

    case 'POST /eletronico':
        $eletronicoController->cadastrar();
        break;

    case 'PUT /eletronico':
        $eletronicoController->atualizar();
        break;

    case 'DELETE /eletronico':
        $eletronicoController->desativar();
        break;

    case 'PUT /estoque':
        $estoqueController->movimentar();
        break;

    case 'GET /movimentacoes':
        $estoqueController->getMovimentacoes();
        break;

    default:
        $view->sendResponse(['error' => 'Rota nao encontrada.'], 404);
        break;
}
?>
