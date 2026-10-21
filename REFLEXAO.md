# Diário de Aprendizagem e Decisões — REFLEXAO.md

## 21/10/2026 — Fundação (Database, JsonView e Roteador)
Integrantes e papéis: Driver: Aluno 1 | Navigator: Aluno 2
Problema: Conectar no MySQL com PDO e estruturar o roteamento no index.php passando a conexão para os Controllers, além de devolver respostas em JSON.
Nossa tentativa: Criamos uma classe de conexão estática cheia de configurações complexas e namespaces que dificultavam o require manual.
Prompt enviado à IA: "Como fazer uma classe Database simples com PDO igual aos projetos anteriores e passar a conexão pelo construtor do Controller?"
Sugestão da IA: Criar a classe Database com `getConnection()` instanciando o PDO com utf8mb4 e passar a variável `$db` na criação dos Controllers no index.php, usando uma classe BaseView/JsonView com `sendResponse($data, $code)`.
Decisão da dupla: Aceitar — porque segue o padrão didático que já aprendemos e utilizamos em sala de aula (como no projeto DEVVO).
Teste realizado: GET /rotaInexistente no Thunder Client / Postman.
Resultado obtido: HTTP 404 | JSON `{"error":"Rota nao encontrada."}`.
Evidência: Postman CT02 | commit fundacao-projeto
O que aprendemos: A injeção da conexão `$db` via construtor simplifica o código e facilita o controle do ciclo de vida do PDO.

---

## 21/10/2026 — GET /eletronico
Integrantes e papéis: Driver: Aluno 1 | Navigator: Aluno 2
Problema: Produtos sem estoque apareciam com saldo nulo.
Nossa tentativa: Usamos INNER JOIN entre eletrônico e estoque.
Prompt enviado à IA: "Por que o INNER JOIN pode ocultar produtos sem estoque? Dê apenas uma pista."
Sugestão da IA: Comparar INNER JOIN com LEFT JOIN e tratar valores NULL.
Decisão da dupla: Adaptar para LEFT JOIN e saldo 0, após conferir os campos reais.
Teste realizado: GET /eletronico no Postman.
Resultado obtido: HTTP 200 | JSON com array dos produtos contendo saldo e `estoque_baixo` calculado.
Evidência: Postman CT01 | commit rota-get-eletronico
O que aprendemos: LEFT JOIN mantém produtos sem registro de estoque na listagem.

---

## 21/10/2026 — POST /eletronico
Integrantes e papéis: Driver: Aluno 2 | Navigator: Aluno 1
Problema: Ao cadastrar um eletrônico com quantidade inicial, precisamos inserir na tabela eletronicos, na tabela estoque e criar a primeira movimentação de ENTRADA.
Nossa tentativa: Fazer os INSERTs um após o outro sem transação.
Prompt enviado à IA: "Como garantir que se o cadastro falhar no estoque ele não deixe o eletrônico salvo pela metade?"
Sugestão da IA: Utilizar `$this->db->beginTransaction()`, `$this->db->commit()` e `$this->db->rollBack()`, além de verificar o lote antes com `buscarPorLote()`.
Decisão da dupla: Aceitar — porque impede dados órfãos e trata erro 409 caso o lote já exista.
Teste realizado: POST /eletronico com JSON de um novo produto e estoque inicial de 5 unidades.
Resultado obtido: HTTP 201 | JSON `{"message":"Eletronico cadastrado com sucesso.","id_eletronico":4}`.
Evidência: Postman CT04 | commit rota-post-eletronico
O que aprendemos: Transações garantem atomicidade em operações que tocam em mais de uma tabela.

---

## 21/10/2026 — PUT /eletronico
Integrantes e papéis: Driver: Aluno 1 | Navigator: Aluno 2
Problema: Atualizar todos os dados do produto sem permitir que o número de lote seja alterado para um que já pertence a outro produto.
Nossa tentativa: UPDATE direto pelo ID sem checar se o produto existia e sem conferir duplicidade de lote em outros IDs.
Prompt enviado à IA: "Como verificar se o número do lote já existe em outro produto no UPDATE?"
Sugestão da IA: Fazer uma consulta SELECT com `WHERE numero_lote = :lote AND id_eletronico != :id`. Se retornar linha, devolver 409; se o produto não existir no banco, devolver 404.
Decisão da dupla: Aceitar — porque valida a regra de lote único sem acusar conflito caso o lote pertença ao próprio produto que está sendo editado.
Teste realizado: PUT /eletronico enviando o JSON com dados atualizados e teste com ID inexistente 999.
Resultado obtido: HTTP 200 | JSON `{"message":"Eletronico atualizado com sucesso."}` e HTTP 404 para ID inexistente.
Evidência: Postman CT07 e CT08 | commit rota-put-eletronico
O que aprendemos: A validação de campos únicos na edição precisa ignorar o registro atual.

---

## 21/10/2026 — DELETE /eletronico?id=1
Integrantes e papéis: Driver: Aluno 2 | Navigator: Aluno 1
Problema: Desativar o produto sem apagar nada das tabelas de estoque e movimentações (exclusão lógica).
Nossa tentativa: Pensamos em rodar um DELETE FROM eletronicos, mas o banco bloqueava por causa das chaves estrangeiras.
Prompt enviado à IA: "Como implementar a exclusão lógica mantendo o histórico de estoque intacto?"
Sugestão da IA: Fazer um UPDATE na coluna `ativo = 0 WHERE id_eletronico = :id AND ativo = 1` e checar `rowCount() > 0`.
Decisão da dupla: Aceitar — porque atende à regra de negócio de não destruir o histórico e devolve 404 se o produto já estava inativo ou não existe.
Teste realizado: DELETE /eletronico?id=3 no Postman.
Resultado obtido: HTTP 200 | JSON `{"message":"Eletronico desativado com sucesso."}`. Segunda chamada retornou HTTP 404.
Evidência: Postman CT09 e CT10 | commit rota-delete-eletronico
O que aprendemos: Exclusão lógica com UPDATE preserva integridade referencial e auditoria.

---

## 21/10/2026 — PUT /estoque
Integrantes e papéis: Driver: Aluno 1 | Navigator: Aluno 2
Problema: Dar entrada ou saída de mercadoria, impedindo que o saldo final fique menor que zero e gravando a linha no histórico de movimentações.
Nossa tentativa: Fazer a conta do saldo direto no Controller e dar UPDATE no estoque.
Prompt enviado à IA: "Como fazer a leitura do saldo com trava no MySQL para movimentação segura de estoque?"
Sugestão da IA: Usar `SELECT ... FOR UPDATE` dentro de transação, calcular o novo saldo, checar se não é negativo (retornando erro 422 de saldo insuficiente) e inserir na tabela `movimentacoes`.
Decisão da dupla: Aceitar — porque cumpre o pseudocódigo da Fase 2 e utiliza o usuário padrão `id_usuario = 1` na tabela de movimentações.
Teste realizado: PUT /estoque com tipo ENTRADA de 3 unidades, depois SAIDA de 2 unidades e tentativa de SAIDA de 500 unidades.
Resultado obtido: HTTP 200 nas duas primeiras operações com novo saldo calculado e HTTP 422 `{"error":"Saldo insuficiente para realizar a saida."}` na terceira.
Evidência: Postman CT11, CT12 e CT13 | commit rota-put-estoque
O que aprendemos: `SELECT ... FOR UPDATE` bloqueia concorrência e transações garantem que a tabela de estoque e a de movimentações sempre fiquem sincronizadas.

---

## 21/10/2026 — GET /movimentacoes
Integrantes e papéis: Driver: Aluno 2 | Navigator: Aluno 1
Problema: Listar o histórico de movimentações trazendo os nomes do produto e do usuário em vez de apenas os IDs, permitindo filtrar por um produto específico.
Nossa tentativa: Fazer um SELECT simples na tabela movimentacoes sem JOIN.
Prompt enviado à IA: "Como trazer os nomes legíveis do eletrônico e do operador no histórico e aceitar filtro opcional de ID?"
Sugestão da IA: Montar a query com `JOIN eletronicos` e `JOIN usuarios`, adicionando `WHERE id_eletronico = :id` caso o parâmetro venha no `$_GET['id']`.
Decisão da dupla: Aceitar — porque deixa o histórico claro e legível para quem consulta a API.
Teste realizado: GET /movimentacoes e GET /movimentacoes?id=1 no Postman.
Resultado obtido: HTTP 200 | JSON com a lista completa e ordenada pela data mais recente. Com o filtro, trouxe apenas as movimentações do produto 1.
Evidência: Postman CT15 e CT16 | commit rota-get-movimentacoes
O que aprendemos: Usar JOINs para enriquecer retornos de auditoria sem duplicar dados no banco.
