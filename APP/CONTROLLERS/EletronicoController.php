<?php
require_once '../APP/MODELS/Eletronico.php';
require_once '../APP/VIEWS/JsonView.php';

class EletronicoController {
    private $model;
    private $view;

    public function __construct($db) {
        $this->model = new Eletronico($db);
        $this->view  = new JsonView();
    }

    public function listar() {
        $itens = $this->model->listarTodos();
        $this->view->sendResponse($itens);
    }

    public function cadastrar() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        $nome        = $data['nome'] ?? null;
        $descricao   = $data['descricao'] ?? null;
        $numero_lote = $data['numero_lote'] ?? null;
        $fabricante  = $data['fabricante'] ?? null;
        $categoria   = $data['categoria'] ?? null;
        $preco       = $data['preco'] ?? null;

        if (!$nome || !$descricao || !$numero_lote || !$fabricante || !$categoria || $preco === null) {
            $this->view->sendResponse(['error' => 'Campos obrigatorios nao preenchidos.'], 422);
            return;
        }

        $preco = (float) str_replace(',', '.', (string) $preco);
        if ($preco < 0) {
            $this->view->sendResponse(['error' => 'Preco deve ser maior ou igual a zero.'], 422);
            return;
        }

        if ($this->model->buscarPorLote($numero_lote)) {
            $this->view->sendResponse(['error' => 'Numero de lote ja cadastrado.'], 409);
            return;
        }

        $estoque_minimo = isset($data['estoque_minimo']) ? (int)$data['estoque_minimo'] : 5;
        $quantidade_inicial = isset($data['quantidade_inicial']) ? (int)$data['quantidade_inicial'] : (isset($data['estoque_inicial']) ? (int)$data['estoque_inicial'] : 0);

        if ($estoque_minimo < 0 || $quantidade_inicial < 0) {
            $this->view->sendResponse(['error' => 'Valores de estoque nao podem ser negativos.'], 422);
            return;
        }

        $dados = [
            'nome'                => $nome,
            'descricao'           => $descricao,
            'numero_lote'         => $numero_lote,
            'fabricante'          => $fabricante,
            'categoria'           => $categoria,
            'tensao'              => $data['tensao'] ?? null,
            'dimensoes'           => $data['dimensoes'] ?? null,
            'resolucao'           => $data['resolucao'] ?? null,
            'capacidade'          => $data['capacidade'] ?? null,
            'conectividade'       => $data['conectividade'] ?? null,
            'preco'               => $preco,
            'processador'         => $data['processador'] ?? null,
            'memoria'             => $data['memoria'] ?? null,
            'camera'              => $data['camera'] ?? null,
            'sistema_operacional' => $data['sistema_operacional'] ?? null,
            'cor'                 => $data['cor'] ?? null,
            'estoque_minimo'      => $estoque_minimo
        ];

        $id = $this->model->cadastrar($dados, $quantidade_inicial);
        if (!$id) {
            $this->view->sendResponse([
                'error' => 'Erro ao cadastrar eletronico.',
                'detalhe' => $this->model->getErro()
            ], 500);
            return;
        }

        $this->view->sendResponse([
            'message'       => 'Eletronico cadastrado com sucesso.',
            'id_eletronico' => (int)$id
        ], 201);
    }

    public function atualizar() {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? ($_GET['id'] ?? null);

        if (!$id || !is_numeric($id)) {
            $this->view->sendResponse(['error' => 'ID invalido ou nao fornecido.'], 422);
            return;
        }

        $existente = $this->model->buscarPorId((int)$id);
        if (!$existente) {
            $this->view->sendResponse(['error' => 'Eletronico nao encontrado.'], 404);
            return;
        }

        $nome        = $data['nome'] ?? null;
        $descricao   = $data['descricao'] ?? null;
        $numero_lote = $data['numero_lote'] ?? null;
        $fabricante  = $data['fabricante'] ?? null;
        $categoria   = $data['categoria'] ?? null;
        $preco       = $data['preco'] ?? null;

        if (!$nome || !$descricao || !$numero_lote || !$fabricante || !$categoria || $preco === null) {
            $this->view->sendResponse(['error' => 'Campos obrigatorios nao preenchidos.'], 422);
            return;
        }

        $preco = (float) str_replace(',', '.', (string) $preco);
        if ($preco < 0) {
            $this->view->sendResponse(['error' => 'Preco deve ser maior ou igual a zero.'], 422);
            return;
        }

        $conflito = $this->model->buscarPorLote($numero_lote, (int)$id);
        if ($conflito) {
            $this->view->sendResponse(['error' => 'Numero de lote pertence a outro produto.'], 409);
            return;
        }

        $dados = [
            'nome'                => $nome,
            'descricao'           => $descricao,
            'numero_lote'         => $numero_lote,
            'fabricante'          => $fabricante,
            'categoria'           => $categoria,
            'tensao'              => $data['tensao'] ?? null,
            'dimensoes'           => $data['dimensoes'] ?? null,
            'resolucao'           => $data['resolucao'] ?? null,
            'capacidade'          => $data['capacidade'] ?? null,
            'conectividade'       => $data['conectividade'] ?? null,
            'preco'               => $preco,
            'processador'         => $data['processador'] ?? null,
            'memoria'             => $data['memoria'] ?? null,
            'camera'              => $data['camera'] ?? null,
            'sistema_operacional' => $data['sistema_operacional'] ?? null,
            'cor'                 => $data['cor'] ?? null,
            'estoque_minimo'      => isset($data['estoque_minimo']) ? (int)$data['estoque_minimo'] : 5
        ];

        $this->model->atualizar((int)$id, $dados);
        $this->view->sendResponse(['message' => 'Eletronico atualizado com sucesso.']);
    }

    public function desativar() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
        }

        if (!$id || !is_numeric($id)) {
            $this->view->sendResponse(['error' => 'ID invalido.'], 422);
            return;
        }

        $sucesso = $this->model->desativar((int)$id);
        if (!$sucesso) {
            $this->view->sendResponse(['error' => 'Eletronico nao encontrado ou ja inativo.'], 404);
            return;
        }

        $this->view->sendResponse(['message' => 'Eletronico desativado com sucesso.']);
    }
}
?>
