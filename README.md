# Gestão de Estoque de Eletrônicos (MVC em PHP) — Fase 3

API REST desenvolvida em PHP utilizando a arquitetura MVC (Model-View-Controller) e PDO com Prepared Statements, sem dependência de bibliotecas externas e focada na gestão de eletrônicos, controle de estoque e auditoria de movimentações.

---

## 📌 Escopo da Atividade

Conforme as diretrizes da **Fase 3 — Implementação assistida**:
- **NÃO** foram implementadas rotas de login, logout, sessão ou autenticação.
- O campo de auditoria `id_usuario` na tabela `movimentacoes` utiliza por convenção o usuário semeado `id_usuario = 1` (Administrador).
- Foram implementadas as **6 rotas obrigatórias** do contrato.

---

## 🛠️ Tecnologias e Requisitos

- **PHP 8.0+** com extensões `pdo` e `pdo_mysql` ativadas
- **Apache** (XAMPP) com módulo `mod_rewrite` ativado
- **MySQL 8.0+**
- **Postman** ou **Thunder Client** (VS Code)

---

## 🚀 Instalação e Configuração Local

1. **Clonar / Posicionar o Projeto:**
   Certifique-se de que a pasta do projeto esteja localizada no diretório do seu servidor local:
   `C:\xampp\htdocs\GestaoEstoqueEletroMVC`

2. **Banco de Dados:**
   - No MySQL Workbench ou phpMyAdmin, execute o script SQL `eletronico_db.sql` fornecido pelo professor para criar a base `gestao_estoque_eletro` e suas tabelas (`usuarios`, `eletronicos`, `estoque`, `movimentacoes`).

3. **Configuração da Conexão:**
   - O arquivo de configuração está em `CONFIG/config.php`:
     ```php
     return [
         'db' => [
             'host' => 'localhost',
             'port' => 3306,
             'dbname' => 'gestao_estoque_eletro',
             'user' => 'root',
             'pass' => '12345678', // altere caso o MySQL local não tenha senha
             'charset' => 'utf8mb4'
         ],
         'usuario_padrao_id' => 1
     ];
     ```

4. **URL Base da API:**
   ```
   http://localhost/GestaoEstoqueEletroMVC/public
   ```

---

## 📋 As 6 Rotas Obrigatórias

| Ordem | Método | Endpoint | Funcionalidade | Status Sucesso |
|---|---|---|---|---|
| 1 | `GET` | `/eletronico` | Listar produtos ativos com saldo e flag `estoque_baixo` | `200 OK` |
| 2 | `POST` | `/eletronico` | Cadastrar eletrônico com criação de estoque inicial | `201 Created` |
| 3 | `PUT` | `/eletronico` | Atualizar dados cadastrais de um produto ativo | `200 OK` |
| 4 | `DELETE` | `/eletronico?id=1` | Desativação lógica (`ativo = 0`) sem apagar dados | `200 OK` |
| 5 | `PUT` | `/estoque` | Movimentação de estoque (`ENTRADA` ou `SAIDA`) | `200 OK` |
| 6 | `GET` | `/movimentacoes` | Consultar histórico (geral ou filtrado por `?id=1`) | `200 OK` |

---

## 🧪 Como Testar via Postman / Thunder Client

Na raiz deste repositório encontra-se o arquivo de coleção pronto:
📁 **`GestaoEstoqueEletroMVC_postman_collection.json`**

1. No Postman, clique em **Import** e selecione o arquivo `GestaoEstoqueEletroMVC_postman_collection.json`.
2. A variável de ambiente `baseUrl` já vem configurada como `http://localhost/GestaoEstoqueEletroMVC/public`.
3. A coleção contempla os casos de teste **CT01 a CT16**:
   - **CT01:** `GET /eletronico` (Listar ativos e saldos)
   - **CT02:** `GET /rotaInexistente` (Retorno 404 padronizado)
   - **CT03:** `OPTIONS /eletronico` (Preflight CORS 204)
   - **CT04:** `POST /eletronico` (Cadastro com sucesso 201)
   - **CT05:** `POST /eletronico` (Conflito de lote 409)
   - **CT06:** `POST /eletronico` (Validação de campos obrigatórios 422)
   - **CT07:** `PUT /eletronico` (Atualização com sucesso 200)
   - **CT08:** `PUT /eletronico` (ID inexistente 404)
   - **CT09:** `DELETE /eletronico?id=3` (Exclusão lógica 200)
   - **CT10:** `DELETE /eletronico?id=9999` (ID inexistente ou já inativo 404)
   - **CT11:** `PUT /estoque` (Movimentação ENTRADA 200)
   - **CT12:** `PUT /estoque` (Movimentação SAIDA 200)
   - **CT13:** `PUT /estoque` (Saída com saldo insuficiente 422)
   - **CT14:** `PUT /estoque` (Tipo inválido 422)
   - **CT15:** `GET /movimentacoes` (Histórico completo 200)
   - **CT16:** `GET /movimentacoes?id=1` (Histórico filtrado por produto 200)

---

## 📖 Diário de Decisões e Aprendizagem

Consulte o arquivo [`REFLEXAO.md`](./REFLEXAO.md) na raiz do projeto para o detalhamento de cada rota, problemas enfrentados, decisões tomadas pela dupla e resultados obtidos.
