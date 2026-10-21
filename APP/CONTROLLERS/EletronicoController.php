<?php
/**
 * Controller de Eletrônicos
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Eletronico;
use App\Views\JsonView;
use Throwable;

class EletronicoController
{
    private Eletronico $model;

    public function __construct()
    {
        $this->model = new Eletronico();
    }

    /**
     * GET /eletronico
     * Lista todos os eletrônicos ativos com saldo e indicador de estoque baixo
     */
    public function listar(): void
    {
        try {
            $dados = $this->model->listarTodosComEstoque();
            JsonView::render([
                'status' => 'sucesso',
                'total'  => count($dados),
                'dados'  => $dados
            ], 200);
        } catch (Throwable $e) {
            JsonView::error('Erro ao consultar eletrônicos: ' . $e->getMessage(), 500);
        }
    }
}
