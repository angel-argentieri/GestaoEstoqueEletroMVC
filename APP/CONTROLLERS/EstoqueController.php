<?php
require_once '../APP/MODELS/Estoque.php';
require_once '../APP/VIEWS/JsonView.php';

class EstoqueController {
    private $model;
    private $view;

    public function __construct($db) {
        $this->model = new Estoque($db);
        $this->view  = new JsonView();
    }

    public function movimentar() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        $id_eletronico = $data['id_eletronico'] ?? ($data['id'] ?? null);
        $tipo          = isset($data['tipo']) ? strtoupper(trim($data['tipo'])) : null;
        $quantidade    = $data['quantidade'] ?? null;
        $observacao    = $data['observacao'] ?? null;

        if (!$id_eletronico || !is_numeric($id_eletronico) || (int)$id_eletronico <= 0) {
            $this->view->sendResponse(['error' => 'ID do eletronico invalido.'], 422);
            return;
        }

        if (!$quantidade || !is_numeric($quantidade) || (int)$quantidade <= 0) {
            $this->view->sendResponse(['error' => 'A quantidade deve ser um numero inteiro maior que zero.'], 422);
            return;
        }

        if ($tipo !== 'ENTRADA' && $tipo !== 'SAIDA') {
            $this->view->sendResponse(['error' => 'Tipo de movimentacao deve ser ENTRADA ou SAIDA.'], 422);
            return;
        }

        $resultado = $this->model->movimentar((int)$id_eletronico, $tipo, (int)$quantidade, $observacao);

        if ($resultado === 'NAO_ENCONTRADO') {
            $this->view->sendResponse(['error' => 'Eletronico nao encontrado ou inativo.'], 404);
            return;
        }

        if ($resultado === 'SALDO_INSUFICIENTE') {
            $this->view->sendResponse(['error' => 'Saldo insuficiente para realizar a saida.'], 422);
            return;
        }

        if (!$resultado) {
            $this->view->sendResponse(['error' => 'Erro ao movimentar estoque.'], 500);
            return;
        }

        $this->view->sendResponse([
            'message' => 'Estoque movimentado com sucesso.',
            'dados'   => $resultado
        ]);
    }

    public function getMovimentacoes() {
        $id = $_GET['id'] ?? ($_GET['id_eletronico'] ?? null);

        if ($id !== null && (!is_numeric($id) || (int)$id <= 0)) {
            $this->view->sendResponse(['error' => 'ID invalido.'], 422);
            return;
        }

        $dados = $this->model->buscarTodas($id ? (int)$id : null);
        $this->view->sendResponse($dados);
    }
}
?>
