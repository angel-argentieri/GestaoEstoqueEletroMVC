<?php
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

    public function listarTodosComEstoque(): array
    {
        $sql = "SELECT e.*, COALESCE(es.quantidade_atual, 0) AS quantidade_atual
                FROM eletronicos e
                LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico
                WHERE e.ativo = 1
                ORDER BY e.nome ASC";

        $stmt = $this->db->query($sql);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($itens as &$item) {
            $item['id_eletronico'] = (int) $item['id_eletronico'];
            $item['preco'] = (float) $item['preco'];
            $item['estoque_minimo'] = (int) $item['estoque_minimo'];
            $item['ativo'] = (int) $item['ativo'];
            $item['quantidade_atual'] = (int) $item['quantidade_atual'];
            $item['estoque_baixo'] = ($item['quantidade_atual'] <= $item['estoque_minimo']);
        }

        return $itens;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM eletronicos WHERE id_eletronico = :id AND ativo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    public function buscarPorLote(string $numeroLote, ?int $ignorarId = null): ?array
    {
        $sql = "SELECT id_eletronico, numero_lote FROM eletronicos WHERE numero_lote = :lote";
        if ($ignorarId !== null) {
            $sql .= " AND id_eletronico != :id";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lote', $numeroLote, PDO::PARAM_STR);
        if ($ignorarId !== null) {
            $stmt->bindValue(':id', $ignorarId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    public function cadastrarComEstoque(array $dados, int $quantidadeInicial): int
    {
        $this->db->beginTransaction();

        try {
            $sqlEletronico = "INSERT INTO eletronicos (
                nome, descricao, numero_lote, fabricante, categoria,
                tensao, dimensoes, resolucao, capacidade, conectividade,
                preco, processador, memoria, camera, sistema_operacional,
                cor, estoque_minimo, ativo
            ) VALUES (
                :nome, :descricao, :numero_lote, :fabricante, :categoria,
                :tensao, :dimensoes, :resolucao, :capacidade, :conectividade,
                :preco, :processador, :memoria, :camera, :sistema_operacional,
                :cor, :estoque_minimo, 1
            )";

            $stmtEletronico = $this->db->prepare($sqlEletronico);
            $stmtEletronico->execute([
                ':nome' => $dados['nome'],
                ':descricao' => $dados['descricao'],
                ':numero_lote' => $dados['numero_lote'],
                ':fabricante' => $dados['fabricante'],
                ':categoria' => $dados['categoria'],
                ':tensao' => $dados['tensao'] ?? null,
                ':dimensoes' => $dados['dimensoes'] ?? null,
                ':resolucao' => $dados['resolucao'] ?? null,
                ':capacidade' => $dados['capacidade'] ?? null,
                ':conectividade' => $dados['conectividade'] ?? null,
                ':preco' => $dados['preco'],
                ':processador' => $dados['processador'] ?? null,
                ':memoria' => $dados['memoria'] ?? null,
                ':camera' => $dados['camera'] ?? null,
                ':sistema_operacional' => $dados['sistema_operacional'] ?? null,
                ':cor' => $dados['cor'] ?? null,
                ':estoque_minimo' => $dados['estoque_minimo'] ?? 5
            ]);

            $idEletronico = (int) $this->db->lastInsertId();

            $sqlEstoque = "INSERT INTO estoque (id_eletronico, quantidade_atual) VALUES (:id, :quantidade)";
            $stmtEstoque = $this->db->prepare($sqlEstoque);
            $stmtEstoque->execute([
                ':id' => $idEletronico,
                ':quantidade' => $quantidadeInicial
            ]);

            if ($quantidadeInicial > 0) {
                $sqlMov = "INSERT INTO movimentacoes (
                    id_eletronico, id_usuario, tipo, quantidade,
                    saldo_anterior, saldo_posterior, observacao
                ) VALUES (
                    :id_eletronico, :id_usuario, 'ENTRADA', :quantidade,
                    0, :saldo_posterior, :observacao
                )";
                $stmtMov = $this->db->prepare($sqlMov);
                $stmtMov->execute([
                    ':id_eletronico' => $idEletronico,
                    ':id_usuario' => 1,
                    ':quantidade' => $quantidadeInicial,
                    ':saldo_posterior' => $quantidadeInicial,
                    ':observacao' => 'Carga inicial'
                ]);
            }

            $this->db->commit();
            return $idEletronico;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = "UPDATE eletronicos SET
            nome = :nome,
            descricao = :descricao,
            numero_lote = :numero_lote,
            fabricante = :fabricante,
            categoria = :categoria,
            tensao = :tensao,
            dimensoes = :dimensoes,
            resolucao = :resolucao,
            capacidade = :capacidade,
            conectividade = :conectividade,
            preco = :preco,
            processador = :processador,
            memoria = :memoria,
            camera = :camera,
            sistema_operacional = :sistema_operacional,
            cor = :cor,
            estoque_minimo = :estoque_minimo
        WHERE id_eletronico = :id AND ativo = 1";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':numero_lote' => $dados['numero_lote'],
            ':fabricante' => $dados['fabricante'],
            ':categoria' => $dados['categoria'],
            ':tensao' => $dados['tensao'] ?? null,
            ':dimensoes' => $dados['dimensoes'] ?? null,
            ':resolucao' => $dados['resolucao'] ?? null,
            ':capacidade' => $dados['capacidade'] ?? null,
            ':conectividade' => $dados['conectividade'] ?? null,
            ':preco' => $dados['preco'],
            ':processador' => $dados['processador'] ?? null,
            ':memoria' => $dados['memoria'] ?? null,
            ':camera' => $dados['camera'] ?? null,
            ':sistema_operacional' => $dados['sistema_operacional'] ?? null,
            ':cor' => $dados['cor'] ?? null,
            ':estoque_minimo' => $dados['estoque_minimo'] ?? 5
        ]);
    }

    public function desativar(int $id): bool
    {
        $sql = "UPDATE eletronicos SET ativo = 0 WHERE id_eletronico = :id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
