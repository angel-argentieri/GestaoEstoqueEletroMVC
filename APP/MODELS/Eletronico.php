<?php
/**
 * Model de Eletrônicos
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Eletronico
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Lista todos os eletrônicos ativos com saldo de estoque e flag de estoque baixo
     */
    public function listarTodosComEstoque(): array
    {
        $sql = "
            SELECT 
                el.id_eletronico,
                el.nome,
                el.descricao,
                el.numero_lote,
                el.fabricante,
                el.categoria,
                el.tensao,
                el.dimensoes,
                el.resolucao,
                el.capacidade,
                el.conectividade,
                CAST(el.preco AS FLOAT) AS preco,
                el.processador,
                el.memoria,
                el.camera,
                el.sistema_operacional,
                el.cor,
                el.estoque_minimo,
                el.ativo,
                el.criado_em,
                el.atualizado_em,
                COALESCE(es.quantidade_atual, 0) AS quantidade_atual
            FROM eletronicos el
            LEFT JOIN estoque es ON el.id_eletronico = es.id_eletronico
            WHERE el.ativo = 1
            ORDER BY el.nome ASC
        ";

        $stmt = $this->db->query($sql);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Processa regra de negócio: estoque_baixo = (quantidade_atual <= estoque_minimo)
        return array_map(function ($item) {
            $quantidadeAtual = (int) $item['quantidade_atual'];
            $estoqueMinimo   = (int) $item['estoque_minimo'];

            $item['quantidade_atual'] = $quantidadeAtual;
            $item['estoque_minimo']   = $estoqueMinimo;
            $item['ativo']            = (bool) $item['ativo'];
            $item['estoque_baixo']    = ($quantidadeAtual <= $estoqueMinimo);

            return $item;
        }, $registros);
    }
}
