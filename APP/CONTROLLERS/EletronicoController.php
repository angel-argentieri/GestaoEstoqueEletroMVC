<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Eletronico;
use App\Views\JsonView;
use PDOException;
use Throwable;

class EletronicoController
{
    private Eletronico $model;

    public function __construct()
    {
        $this->model = new Eletronico();
    }

    public function listar(): void
    {
        try {
            $dados = $this->model->listarTodosComEstoque();
            JsonView::render($dados, 200);
        } catch (Throwable $e) {
            JsonView::error('Erro ao listar eletrônicos: ' . $e->getMessage(), 500);
        }
    }

    public function cadastrar(Request $request): void
    {
        $body = $request->getBody();

        $erros = [];
        $camposObrigatorios = ['nome', 'descricao', 'numero_lote', 'fabricante', 'categoria'];
        foreach ($camposObrigatorios as $campo) {
            if (empty(trim((string)($body[$campo] ?? '')))) {
                $erros[] = "O campo '{$campo}' é obrigatório.";
            }
        }

        if (!isset($body['preco'])) {
            $erros[] = "O campo 'preco' é obrigatório.";
            $preco = 0.0;
        } else {
            $precoRaw = str_replace(',', '.', (string)$body['preco']);
            if (!is_numeric($precoRaw) || (float)$precoRaw < 0) {
                $erros[] = "O campo 'preco' deve ser um número maior ou igual a zero.";
                $preco = 0.0;
            } else {
                $preco = (float)$precoRaw;
            }
        }

        $estoqueMinimo = 5;
        if (isset($body['estoque_minimo'])) {
            if (!is_numeric($body['estoque_minimo']) || (int)$body['estoque_minimo'] < 0) {
                $erros[] = "O campo 'estoque_minimo' deve ser um número inteiro maior ou igual a zero.";
            } else {
                $estoqueMinimo = (int)$body['estoque_minimo'];
            }
        }

        $quantidadeInicial = 0;
        if (isset($body['quantidade_inicial']) || isset($body['estoque_inicial'])) {
            $qtdRaw = $body['quantidade_inicial'] ?? $body['estoque_inicial'];
            if (!is_numeric($qtdRaw) || (int)$qtdRaw < 0) {
                $erros[] = "A quantidade inicial deve ser um número inteiro maior ou igual a zero.";
            } else {
                $quantidadeInicial = (int)$qtdRaw;
            }
        }

        if (!empty($erros)) {
            JsonView::error('Dados inválidos para cadastro.', 422, $erros);
            return;
        }

        $numeroLote = trim((string)$body['numero_lote']);
        if ($this->model->buscarPorLote($numeroLote) !== null) {
            JsonView::error('Número de lote já cadastrado no sistema.', 409);
            return;
        }

        $dados = [
            'nome' => trim((string)$body['nome']),
            'descricao' => trim((string)$body['descricao']),
            'numero_lote' => $numeroLote,
            'fabricante' => trim((string)$body['fabricante']),
            'categoria' => trim((string)$body['categoria']),
            'tensao' => !empty($body['tensao']) ? trim((string)$body['tensao']) : null,
            'dimensoes' => !empty($body['dimensoes']) ? trim((string)$body['dimensoes']) : null,
            'resolucao' => !empty($body['resolucao']) ? trim((string)$body['resolucao']) : null,
            'capacidade' => !empty($body['capacidade']) ? trim((string)$body['capacidade']) : null,
            'conectividade' => !empty($body['conectividade']) ? trim((string)$body['conectividade']) : null,
            'preco' => $preco,
            'processador' => !empty($body['processador']) ? trim((string)$body['processador']) : null,
            'memoria' => !empty($body['memoria']) ? trim((string)$body['memoria']) : null,
            'camera' => !empty($body['camera']) ? trim((string)$body['camera']) : null,
            'sistema_operacional' => !empty($body['sistema_operacional']) ? trim((string)$body['sistema_operacional']) : null,
            'cor' => !empty($body['cor']) ? trim((string)$body['cor']) : null,
            'estoque_minimo' => $estoqueMinimo
        ];

        try {
            $id = $this->model->cadastrarComEstoque($dados, $quantidadeInicial);
            JsonView::render([
                'status' => 'sucesso',
                'mensagem' => 'Eletrônico cadastrado com sucesso.',
                'id_eletronico' => $id
            ], 201);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || strpos($e->getMessage(), '1062') !== false) {
                JsonView::error('Número de lote já cadastrado.', 409);
            } else {
                JsonView::error('Erro ao cadastrar eletrônico: ' . $e->getMessage(), 500);
            }
        } catch (Throwable $e) {
            JsonView::error('Erro interno no servidor: ' . $e->getMessage(), 500);
        }
    }

    public function atualizar(Request $request): void
    {
        $idRaw = $request->get('id') ?? $request->get('id_eletronico');
        if (!is_numeric($idRaw) || (int)$idRaw <= 0) {
            JsonView::error('ID inválido.', 422);
            return;
        }

        $id = (int)$idRaw;
        $existente = $this->model->buscarPorId($id);
        if (!$existente) {
            JsonView::error('Eletrônico não encontrado.', 404);
            return;
        }

        $body = $request->getBody();

        $erros = [];
        $camposObrigatorios = ['nome', 'descricao', 'numero_lote', 'fabricante', 'categoria'];
        foreach ($camposObrigatorios as $campo) {
            if (empty(trim((string)($body[$campo] ?? '')))) {
                $erros[] = "O campo '{$campo}' é obrigatório.";
            }
        }

        if (!isset($body['preco'])) {
            $erros[] = "O campo 'preco' é obrigatório.";
            $preco = 0.0;
        } else {
            $precoRaw = str_replace(',', '.', (string)$body['preco']);
            if (!is_numeric($precoRaw) || (float)$precoRaw < 0) {
                $erros[] = "O campo 'preco' deve ser um número maior ou igual a zero.";
                $preco = 0.0;
            } else {
                $preco = (float)$precoRaw;
            }
        }

        $estoqueMinimo = 5;
        if (isset($body['estoque_minimo'])) {
            if (!is_numeric($body['estoque_minimo']) || (int)$body['estoque_minimo'] < 0) {
                $erros[] = "O campo 'estoque_minimo' deve ser um número inteiro maior ou igual a zero.";
            } else {
                $estoqueMinimo = (int)$body['estoque_minimo'];
            }
        }

        if (!empty($erros)) {
            JsonView::error('Dados inválidos para atualização.', 422, $erros);
            return;
        }

        $numeroLote = trim((string)$body['numero_lote']);
        $conflito = $this->model->buscarPorLote($numeroLote, $id);
        if ($conflito !== null) {
            JsonView::error('Número de lote já pertence a outro produto.', 409);
            return;
        }

        $dados = [
            'nome' => trim((string)$body['nome']),
            'descricao' => trim((string)$body['descricao']),
            'numero_lote' => $numeroLote,
            'fabricante' => trim((string)$body['fabricante']),
            'categoria' => trim((string)$body['categoria']),
            'tensao' => !empty($body['tensao']) ? trim((string)$body['tensao']) : null,
            'dimensoes' => !empty($body['dimensoes']) ? trim((string)$body['dimensoes']) : null,
            'resolucao' => !empty($body['resolucao']) ? trim((string)$body['resolucao']) : null,
            'capacidade' => !empty($body['capacidade']) ? trim((string)$body['capacidade']) : null,
            'conectividade' => !empty($body['conectividade']) ? trim((string)$body['conectividade']) : null,
            'preco' => $preco,
            'processador' => !empty($body['processador']) ? trim((string)$body['processador']) : null,
            'memoria' => !empty($body['memoria']) ? trim((string)$body['memoria']) : null,
            'camera' => !empty($body['camera']) ? trim((string)$body['camera']) : null,
            'sistema_operacional' => !empty($body['sistema_operacional']) ? trim((string)$body['sistema_operacional']) : null,
            'cor' => !empty($body['cor']) ? trim((string)$body['cor']) : null,
            'estoque_minimo' => $estoqueMinimo
        ];

        try {
            $this->model->atualizar($id, $dados);
            JsonView::render([
                'status' => 'sucesso',
                'mensagem' => 'Eletrônico atualizado com sucesso.'
            ], 200);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || strpos($e->getMessage(), '1062') !== false) {
                JsonView::error('Número de lote já pertence a outro produto.', 409);
            } else {
                JsonView::error('Erro ao atualizar eletrônico: ' . $e->getMessage(), 500);
            }
        } catch (Throwable $e) {
            JsonView::error('Erro interno no servidor: ' . $e->getMessage(), 500);
        }
    }

    public function desativar(Request $request): void
    {
        $idRaw = $request->get('id') ?? $request->get('id_eletronico');
        if (!is_numeric($idRaw) || (int)$idRaw <= 0) {
            JsonView::error('ID inválido.', 422);
            return;
        }

        $id = (int)$idRaw;

        try {
            $sucesso = $this->model->desativar($id);
            if (!$sucesso) {
                JsonView::error('Eletrônico não encontrado ou já desativado.', 404);
                return;
            }

            JsonView::render([
                'status' => 'sucesso',
                'mensagem' => 'Eletrônico desativado com sucesso.'
            ], 200);
        } catch (Throwable $e) {
            JsonView::error('Erro ao desativar eletrônico: ' . $e->getMessage(), 500);
        }
    }
}
