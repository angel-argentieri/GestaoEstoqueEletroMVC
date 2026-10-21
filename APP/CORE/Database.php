<?php
class Database {
    private $host     = 'localhost';
    private $dbname   = 'gestao_estoque_eletro';
    private $username = 'root';
    private $password = '12345678';
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $error) {
            try {
                $this->pdo = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                    $this->username,
                    '',
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $ex) {
                http_response_code(500);
                echo json_encode([
                    'error'  => 'Falha na conexao com o banco de dados.',
                    'detail' => $error->getMessage()
                ]);
                exit;
            }
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}
?>
