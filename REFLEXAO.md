## 21/10/2026 — GET /eletronico
Integrantes e papéis: Aluno 1 (Driver) | Aluno 2 (Navigator)
Problema: Produtos sem estoque apareciam com saldo nulo ou sumiam da listagem.
Nossa tentativa: Usamos INNER JOIN entre eletrônico e estoque.
Prompt enviado à IA: "Por que o INNER JOIN pode ocultar produtos sem estoque? Dê apenas uma pista."
Sugestão da IA: Comparar INNER JOIN com LEFT JOIN e tratar valores NULL.
Decisão da dupla: Adaptar — usamos LEFT JOIN com COALESCE(es.quantidade_atual, 0) para trazer saldo 0.
Teste realizado: GET {{baseUrl}}/eletronico no Postman.
Resultado obtido: HTTP 200 | JSON com a lista de produtos ativos e a flag estoque_baixo calculada.
Evidência: evidencias/CT01-listagem.png | commit a1b2c3d
O que aprendemos: LEFT JOIN mantém produtos sem registro de estoque na listagem.

## 21/10/2026 — POST /eletronico
Integrantes e papéis: Aluno 2 (Driver) | Aluno 1 (Navigator)
Problema: Cadastrar produto e estoque inicial juntos sem deixar dados incompletos se der erro.
Nossa tentativa: Fazer dois INSERTs separados sem transação.
Prompt enviado à IA: "Como garantir que o cadastro do eletrônico e o estoque inicial sejam salvos juntos?"
Sugestão da IA: Usar transação PDO com beginTransaction() e tratar erro de lote duplicado.
Decisão da dupla: Aceitar — porque evita cadastrar o eletrônico sem o estoque se houver falha.
Teste realizado: POST {{baseUrl}}/eletronico com JSON do produto no Postman.
Resultado obtido: HTTP 201 | JSON `{"message":"Eletronico cadastrado com sucesso.","id_eletronico":4}`.
Evidência: evidencias/CT04-cadastro.png | commit b2c3d4e
O que aprendemos: Transações com PDO garantem que várias tabelas sejam salvas com segurança.

## 21/10/2026 — PUT /eletronico
Integrantes e papéis: Aluno 1 (Driver) | Aluno 2 (Navigator)
Problema: Atualizar o produto sem deixar usar um número de lote que já é de outro eletrônico.
Nossa tentativa: UPDATE direto sem verificar se o lote já existia.
Prompt enviado à IA: "Como verificar lote repetido no UPDATE sem dar erro no próprio produto?"
Sugestão da IA: Consultar no banco se existe o lote com `id_eletronico != :id`.
Decisão da dupla: Aceitar — porque se o lote for de outro devolve 409, e se o ID não existir devolve 404.
Teste realizado: PUT {{baseUrl}}/eletronico com o ID e os novos dados no corpo JSON.
Resultado obtido: HTTP 200 | JSON `{"message":"Eletronico atualizado com sucesso."}`.
Evidência: evidencias/CT07-atualizacao.png | commit c3d4e5f
O que aprendemos: Na edição, a verificação de chave única precisa ignorar o próprio ID do registro.

## 21/10/2026 — DELETE /eletronico?id=1
Integrantes e papéis: Aluno 2 (Driver) | Aluno 1 (Navigator)
Problema: Desativar o produto sem apagar do banco para não quebrar o histórico.
Nossa tentativa: Pensamos em rodar DELETE no banco, mas dava erro de chave estrangeira.
Prompt enviado à IA: "Como fazer exclusão lógica de produto no MySQL com PDO?"
Sugestão da IA: Fazer UPDATE para `ativo = 0` e usar rowCount() para conferir se alterou.
Decisão da dupla: Aceitar — porque mantém o histórico de estoque e avisa 404 se não achar o produto.
Teste realizado: DELETE {{baseUrl}}/eletronico?id=3 no Postman.
Resultado obtido: HTTP 200 | JSON `{"message":"Eletronico desativado com sucesso."}`.
Evidência: evidencias/CT09-desativar.png | commit d4e5f6a
O que aprendemos: Exclusão lógica com UPDATE preserva os dados de auditoria e movimentações.

## 21/10/2026 — PUT /estoque
Integrantes e papéis: Aluno 1 (Driver) | Aluno 2 (Navigator)
Problema: Movimentar entrada e saída sem deixar o saldo ficar negativo.
Nossa tentativa: Atualizar a quantidade direto no estoque sem conferir o saldo anterior.
Prompt enviado à IA: "Como travar a linha no MySQL para calcular entrada e saída com segurança?"
Sugestão da IA: Usar SELECT com FOR UPDATE dentro de transação e validar novo saldo >= 0.
Decisão da dupla: Aceitar — porque se faltar saldo devolve 422 e não mexe no estoque.
Teste realizado: PUT {{baseUrl}}/estoque com tipo ENTRADA e depois SAIDA no Postman.
Resultado obtido: HTTP 200 | JSON com os saldos atualizados e registro gravado em movimentações.
Evidência: evidencias/CT11-estoque.png | commit e5f6a7b
O que aprendemos: SELECT ... FOR UPDATE evita que duas requisições alterem o saldo ao mesmo tempo.

## 21/10/2026 — GET /movimentacoes
Integrantes e papéis: Aluno 2 (Driver) | Aluno 1 (Navigator)
Problema: Mostrar o histórico com o nome do produto e do usuário em vez de apenas números.
Nossa tentativa: SELECT simples na tabela movimentações.
Prompt enviado à IA: "Como trazer o nome do produto e do usuário no histórico de movimentações?"
Sugestão da IA: Usar JOIN com as tabelas eletronicos e usuarios, ordenando por data decrescente.
Decisão da dupla: Aceitar — e permitir filtrar por um produto específico passando o id na query string.
Teste realizado: GET {{baseUrl}}/movimentacoes no Postman.
Resultado obtido: HTTP 200 | JSON com o histórico completo de entradas e saídas.
Evidência: evidencias/CT15-historico.png | commit f6a7b8c
O que aprendemos: JOIN reúne dados relacionados de forma limpa em uma única consulta.
