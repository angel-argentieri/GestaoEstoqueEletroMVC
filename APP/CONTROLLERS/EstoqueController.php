<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Estoque;
use App\Views\JsonView;
use RuntimeException;
use Throwable;

class EstoqueController
{
    private Estoque $model;

    public function __construct()
    {
        $this->model = new Estoque();
    }

    public function movimentar(Request $request): void
    {
        $idRaw = $request->get('id_eletronico') ?? $request->get('id');
        $quantidadeRaw = $request->get('quantidade');

        if (!is_numeric($idRaw) || (int)$idRaw <= 0) {
            JsonView::error('ID do eletrônico inválido.', 422);
            return;
        }

        if (!is_numeric($quantidadeRaw) || (int)$quantidadeRaw <= 0) {
            JsonView::error('A quantidade deve ser um número inteiro maior que zero.', 422);
            return;
        }

        $tipo = strtoupper(trim((string)$request->get('tipo', '')));
        if ($tipo !== 'ENTRADA' && $tipo !== 'SAIDA') {
            JsonView::error('O tipo de movimentação deve ser ENTRADA ou SAIDA.', 422);
            return;
        }

        $idEletronico = (int)$idRaw;
        $quantidade = (int)$quantidadeRaw;
        $observacao = $request->get('observacao');
        $observacao = !empty($observacao) ? trim((string)$observacao) : null;

        try {
            $resultado = $this->model->movimentar($idEletronico, $tipo, $quantidade, $observacao);
            JsonView::render([
                'status' => 'sucesso',
                'mensagem' => 'Movimentação realizada com sucesso.',
                'dados' => $resultado
            ], 200);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'PRODUTO_NAO_ENCONTRADO') {
                JsonView::error('Eletrônico não encontrado ou inativo.', 404);
            } elseif ($e->getMessage() === 'SALDO_INSUFICIENTE') {
                JsonView::error('Saldo insuficiente para realizar a saída.', 422);
            } else {
                JsonView::error($e->getMessage(), 400);
            }
        } catch (Throwable $e) {
            JsonView::error('Erro interno ao movimentar estoque: ' . $e->getMessage(), 500);
        }
    }

    public function historico(Request $request): void
    {
        $idRaw = $request->getQuery('id') ?? $request->getQuery('id_eletronico');
        $idEletronico = null;

        if ($idRaw !== null) {
            if (!is_numeric($idRaw) || (int)$idRaw <= 0) {
                JsonView::error('ID inválido para filtro.', 422);
                return;
            }
            $idEletronico = (int)$idRaw;
        }

        try {
            $dados = $this->model->listarMovimentacoes($idEletronico);
            JsonView::render($dados, 200);
        } catch (Throwable $e) {
            JsonView::error('Erro ao consultar histórico de movimentações: ' . $e->getMessage(), 500);
        }
    }
}
