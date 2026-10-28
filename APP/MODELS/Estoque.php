<?php
class Estoque {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function movimentar($id_eletronico, $tipo, $quantidade, $observacao = null) {
        try {
            $this->db->beginTransaction();

            $stmtVerifica = $this->db->prepare("
                SELECT e.id_eletronico, e.ativo, COALESCE(es.quantidade_atual, 0) AS quantidade_atual
                FROM eletronicos e
                LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico
                WHERE e.id_eletronico = :id
                FOR UPDATE
            ");
            $stmtVerifica->bindValue(':id', $id_eletronico, PDO::PARAM_INT);
            $stmtVerifica->execute();

            $produto = $stmtVerifica->fetch(PDO::FETCH_ASSOC);

            if (!$produto || (int)$produto['ativo'] !== 1) {
                $this->db->rollBack();
                return 'NAO_ENCONTRADO';
            }

            $saldo_anterior = (int)$produto['quantidade_atual'];

            if ($tipo === 'ENTRADA') {
                $saldo_posterior = $saldo_anterior + $quantidade;
            } else {
                $saldo_posterior = $saldo_anterior - $quantidade;
            }

            if ($saldo_posterior < 0) {
                $this->db->rollBack();
                return 'SALDO_INSUFICIENTE';
            }

            $stmtEstoque = $this->db->prepare("
                INSERT INTO estoque (id_eletronico, quantidade_atual)
                VALUES (:id, :novo_saldo)
                ON DUPLICATE KEY UPDATE quantidade_atual = :novo_saldo_up
            ");
            $stmtEstoque->bindValue(':id',            $id_eletronico, PDO::PARAM_INT);
            $stmtEstoque->bindValue(':novo_saldo',    $saldo_posterior, PDO::PARAM_INT);
            $stmtEstoque->bindValue(':novo_saldo_up', $saldo_posterior, PDO::PARAM_INT);
            $stmtEstoque->execute();

            $stmtMov = $this->db->prepare("
                INSERT INTO movimentacoes (
                    id_eletronico, id_usuario, tipo, quantidade,
                    saldo_anterior, saldo_posterior, observacao
                ) VALUES (
                    :id_eletronico, :id_usuario, :tipo, :quantidade,
                    :saldo_anterior, :saldo_posterior, :observacao
                )
            ");
            $stmtMov->bindValue(':id_eletronico',   $id_eletronico, PDO::PARAM_INT);
            $stmtMov->bindValue(':id_usuario',       1, PDO::PARAM_INT);
            $stmtMov->bindValue(':tipo',             $tipo);
            $stmtMov->bindValue(':quantidade',       $quantidade, PDO::PARAM_INT);
            $stmtMov->bindValue(':saldo_anterior',   $saldo_anterior, PDO::PARAM_INT);
            $stmtMov->bindValue(':saldo_posterior',  $saldo_posterior, PDO::PARAM_INT);
            $stmtMov->bindValue(':observacao',       $observacao);
            $stmtMov->execute();

            $this->db->commit();

            return [
                'id_eletronico'   => $id_eletronico,
                'tipo'            => $tipo,
                'quantidade'      => $quantidade,
                'saldo_anterior'  => $saldo_anterior,
                'saldo_posterior' => $saldo_posterior,
                'observacao'      => $observacao
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function buscarTodas($id_eletronico = null) {
        $sql = "
            SELECT 
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
            JOIN usuarios u ON m.id_usuario = u.id_usuario
        ";

        if ($id_eletronico) {
            $sql .= " WHERE m.id_eletronico = :id";
        }

        $sql .= " ORDER BY m.data_movimentacao DESC, m.id_movimentacao DESC";

        $stmt = $this->db->prepare($sql);
        if ($id_eletronico) {
            $stmt->bindValue(':id', $id_eletronico, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
