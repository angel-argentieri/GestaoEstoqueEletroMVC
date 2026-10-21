<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

class Estoque
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function movimentar(int $idEletronico, string $tipo, int $quantidade, ?string $observacao = null): array
    {
        $this->db->beginTransaction();

        try {
            $sqlVerifica = "SELECT e.id_eletronico, e.ativo, COALESCE(es.quantidade_atual, 0) AS quantidade_atual
                            FROM eletronicos e
                            LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico
                            WHERE e.id_eletronico = :id
                            FOR UPDATE";

            $stmtVerifica = $this->db->prepare($sqlVerifica);
            $stmtVerifica->bindValue(':id', $idEletronico, PDO::PARAM_INT);
            $stmtVerifica->execute();

            $produto = $stmtVerifica->fetch(PDO::FETCH_ASSOC);

            if (!$produto || (int) $produto['ativo'] !== 1) {
                $this->db->rollBack();
                throw new RuntimeException("PRODUTO_NAO_ENCONTRADO");
            }

            $saldoAnterior = (int) $produto['quantidade_atual'];

            if ($tipo === 'ENTRADA') {
                $saldoPosterior = $saldoAnterior + $quantidade;
            } else {
                $saldoPosterior = $saldoAnterior - $quantidade;
            }

            if ($saldoPosterior < 0) {
                $this->db->rollBack();
                throw new RuntimeException("SALDO_INSUFICIENTE");
            }

            $sqlEstoque = "INSERT INTO estoque (id_eletronico, quantidade_atual)
                           VALUES (:id, :novo_saldo)
                           ON DUPLICATE KEY UPDATE quantidade_atual = :novo_saldo_up";
            $stmtEstoque = $this->db->prepare($sqlEstoque);
            $stmtEstoque->execute([
                ':id' => $idEletronico,
                ':novo_saldo' => $saldoPosterior,
                ':novo_saldo_up' => $saldoPosterior
            ]);

            $sqlMov = "INSERT INTO movimentacoes (
                id_eletronico, id_usuario, tipo, quantidade,
                saldo_anterior, saldo_posterior, observacao
            ) VALUES (
                :id_eletronico, :id_usuario, :tipo, :quantidade,
                :saldo_anterior, :saldo_posterior, :observacao
            )";

            $stmtMov = $this->db->prepare($sqlMov);
            $stmtMov->execute([
                ':id_eletronico' => $idEletronico,
                ':id_usuario' => 1,
                ':tipo' => $tipo,
                ':quantidade' => $quantidade,
                ':saldo_anterior' => $saldoAnterior,
                ':saldo_posterior' => $saldoPosterior,
                ':observacao' => $observacao
            ]);

            $this->db->commit();

            return [
                'id_eletronico' => $idEletronico,
                'tipo' => $tipo,
                'quantidade' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'observacao' => $observacao
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function listarMovimentacoes(?int $idEletronico = null): array
    {
        $sql = "SELECT 
                    m.id_movimentacao,
                    m.id_eletronico,
                    e.nome AS eletronico_nome,
                    m.id_usuario,
                    u.nome AS usuario_nome,
                    m.tipo,
                    m.quantidade,
                    m.saldo_anterior,
                    m.saldo_posterior,
                    m.observacao,
                    m.data_movimentacao
                FROM movimentacoes m
                JOIN eletronicos e ON m.id_eletronico = e.id_eletronico
                JOIN usuarios u ON m.id_usuario = u.id_usuario";

        if ($idEletronico !== null) {
            $sql .= " WHERE m.id_eletronico = :id";
        }

        $sql .= " ORDER BY m.data_movimentacao DESC, m.id_movimentacao DESC";

        $stmt = $this->db->prepare($sql);
        if ($idEletronico !== null) {
            $stmt->bindValue(':id', $idEletronico, PDO::PARAM_INT);
        }
        $stmt->execute();

        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($itens as &$item) {
            $item['id_movimentacao'] = (int) $item['id_movimentacao'];
            $item['id_eletronico'] = (int) $item['id_eletronico'];
            $item['id_usuario'] = (int) $item['id_usuario'];
            $item['quantidade'] = (int) $item['quantidade'];
            $item['saldo_anterior'] = (int) $item['saldo_anterior'];
            $item['saldo_posterior'] = (int) $item['saldo_posterior'];
        }

        return $itens;
    }
}
