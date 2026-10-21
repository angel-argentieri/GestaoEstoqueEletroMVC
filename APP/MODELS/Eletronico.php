<?php
class Eletronico {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listarTodos() {
        $stmt = $this->db->query("
            SELECT e.*, COALESCE(es.quantidade_atual, 0) AS quantidade_atual
            FROM eletronicos e
            LEFT JOIN estoque es ON e.id_eletronico = es.id_eletronico
            WHERE e.ativo = 1
            ORDER BY e.nome ASC
        ");
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($itens as &$item) {
            $item['quantidade_atual'] = (int) $item['quantidade_atual'];
            $item['estoque_minimo']   = (int) $item['estoque_minimo'];
            $item['estoque_baixo']    = ($item['quantidade_atual'] <= $item['estoque_minimo']);
        }

        return $itens;
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM eletronicos WHERE id_eletronico = :id AND ativo = 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorLote($numero_lote, $ignorarId = null) {
        $sql = "SELECT id_eletronico, numero_lote FROM eletronicos WHERE numero_lote = :lote";
        if ($ignorarId) {
            $sql .= " AND id_eletronico != :id";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lote', $numero_lote);
        if ($ignorarId) {
            $stmt->bindValue(':id', $ignorarId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cadastrar($dados, $quantidade_inicial = 0) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO eletronicos (
                    nome, descricao, numero_lote, fabricante, categoria,
                    tensao, dimensoes, resolucao, capacidade, conectividade,
                    preco, processador, memoria, camera, sistema_operacional,
                    cor, estoque_minimo, ativo
                ) VALUES (
                    :nome, :descricao, :numero_lote, :fabricante, :categoria,
                    :tensao, :dimensoes, :resolucao, :capacidade, :conectividade,
                    :preco, :processador, :memoria, :camera, :sistema_operacional,
                    :cor, :estoque_minimo, 1
                )
            ");

            $stmt->bindValue(':nome',                $dados['nome']);
            $stmt->bindValue(':descricao',           $dados['descricao']);
            $stmt->bindValue(':numero_lote',         $dados['numero_lote']);
            $stmt->bindValue(':fabricante',          $dados['fabricante']);
            $stmt->bindValue(':categoria',           $dados['categoria']);
            $stmt->bindValue(':tensao',              $dados['tensao'] ?? null);
            $stmt->bindValue(':dimensoes',           $dados['dimensoes'] ?? null);
            $stmt->bindValue(':resolucao',           $dados['resolucao'] ?? null);
            $stmt->bindValue(':capacidade',          $dados['capacidade'] ?? null);
            $stmt->bindValue(':conectividade',       $dados['conectividade'] ?? null);
            $stmt->bindValue(':preco',               $dados['preco']);
            $stmt->bindValue(':processador',         $dados['processador'] ?? null);
            $stmt->bindValue(':memoria',             $dados['memoria'] ?? null);
            $stmt->bindValue(':camera',              $dados['camera'] ?? null);
            $stmt->bindValue(':sistema_operacional', $dados['sistema_operacional'] ?? null);
            $stmt->bindValue(':cor',                 $dados['cor'] ?? null);
            $stmt->bindValue(':estoque_minimo',      $dados['estoque_minimo'] ?? 5, PDO::PARAM_INT);
            $stmt->execute();

            $id_eletronico = $this->db->lastInsertId();

            $stmtEstoque = $this->db->prepare("INSERT INTO estoque (id_eletronico, quantidade_atual) VALUES (:id, :qtd)");
            $stmtEstoque->bindValue(':id',  $id_eletronico, PDO::PARAM_INT);
            $stmtEstoque->bindValue(':qtd', $quantidade_inicial, PDO::PARAM_INT);
            $stmtEstoque->execute();

            if ($quantidade_inicial > 0) {
                $stmtMov = $this->db->prepare("
                    INSERT INTO movimentacoes (id_eletronico, id_usuario, tipo, quantidade, saldo_anterior, saldo_posterior, observacao)
                    VALUES (:id_eletronico, 1, 'ENTRADA', :qtd, 0, :qtd, 'Carga inicial')
                ");
                $stmtMov->bindValue(':id_eletronico', $id_eletronico, PDO::PARAM_INT);
                $stmtMov->bindValue(':qtd',           $quantidade_inicial, PDO::PARAM_INT);
                $stmtMov->execute();
            }

            $this->db->commit();
            return $id_eletronico;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function atualizar($id, $dados) {
        $stmt = $this->db->prepare("
            UPDATE eletronicos SET
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
            WHERE id_eletronico = :id AND ativo = 1
        ");

        $stmt->bindValue(':id',                  $id, PDO::PARAM_INT);
        $stmt->bindValue(':nome',                $dados['nome']);
        $stmt->bindValue(':descricao',           $dados['descricao']);
        $stmt->bindValue(':numero_lote',         $dados['numero_lote']);
        $stmt->bindValue(':fabricante',          $dados['fabricante']);
        $stmt->bindValue(':categoria',           $dados['categoria']);
        $stmt->bindValue(':tensao',              $dados['tensao'] ?? null);
        $stmt->bindValue(':dimensoes',           $dados['dimensoes'] ?? null);
        $stmt->bindValue(':resolucao',           $dados['resolucao'] ?? null);
        $stmt->bindValue(':capacidade',          $dados['capacidade'] ?? null);
        $stmt->bindValue(':conectividade',       $dados['conectividade'] ?? null);
        $stmt->bindValue(':preco',               $dados['preco']);
        $stmt->bindValue(':processador',         $dados['processador'] ?? null);
        $stmt->bindValue(':memoria',             $dados['memoria'] ?? null);
        $stmt->bindValue(':camera',              $dados['camera'] ?? null);
        $stmt->bindValue(':sistema_operacional', $dados['sistema_operacional'] ?? null);
        $stmt->bindValue(':cor',                 $dados['cor'] ?? null);
        $stmt->bindValue(':estoque_minimo',      $dados['estoque_minimo'] ?? 5, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function desativar($id) {
        $stmt = $this->db->prepare("UPDATE eletronicos SET ativo = 0 WHERE id_eletronico = :id AND ativo = 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
