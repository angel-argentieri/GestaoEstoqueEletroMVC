# Diário de Aprendizagem e Decisões — REFLEXAO.md

## 21/10/2026 — Fundação da Aplicação (Configuração, Request, Database e JsonView)
Integrantes e papéis: Driver: Aluno 1 | Navigator: Aluno 2
Problema: Como estruturar a base MVC sem autenticação, garantindo conexão PDO reutilizável, captura da rota correta no XAMPP e saída em JSON com códigos HTTP padronizados.
Nossa tentativa: Criamos a pasta Core com Database.php abrindo conexão direta e um script inicial no index.php. A URL vinha com o prefixo da pasta do projeto e quebrava o roteamento.
Prompt enviado à IA: "Como normalizar a URL no Request.php para remover o prefixo da pasta do XAMPP e como estruturar a classe JsonView?"
Sugestão da IA: Tratar `$_SERVER['REQUEST_URI']` com `parse_url` e regex para limpar `/GestaoEstoqueEletroMVC/public`, além de criar métodos estáticos em `JsonView` para `http_response_code` e `json_encode`.
Decisão da dupla: Aceitar a sugestão integralmente, pois centraliza o tratamento de requisição e resposta sem poluir os controllers.
Teste realizado: Acesso a uma rota inexistente `GET /rota-teste` via Postman.
Resultado obtido: HTTP 404 | JSON `{"status":"erro","mensagem":"Rota não encontrada.","detalhes":{"metodo":"GET","rota":"/rota-teste"}}`
Evidência: Postman CT03 | commit fundacao-mvc
O que aprendemos: Como o front controller canaliza todas as requisições pelo index.php via .htaccess e a importância de padronizar a emissão de cabeçalhos JSON.

---

## 21/10/2026 — GET /eletronico
Integrantes e papéis: Driver: Aluno 1 | Navigator: Aluno 2
Problema: Produtos cadastrados sem movimentação ou sem linha de estoque poderiam não aparecer na listagem ou retornar saldo nulo, além de ser necessário calcular se o estoque está baixo.
Nossa tentativa: Havíamos pensado em fazer um INNER JOIN simples entre a tabela eletronicos e estoque, mas ficamos com dúvida se ocultaria itens sem estoque.
Prompt enviado à IA: "Por que o INNER JOIN pode ocultar produtos sem estoque e como calcular o estoque_baixo diretamente no PHP?"
Sugestão da IA: Usar LEFT JOIN com `COALESCE(es.quantidade_atual, 0)` no SQL e, no Model, percorrer com foreach definindo `estoque_baixo = (quantidade_atual <= estoque_minimo)`.
Decisão da dupla: Adaptar a sugestão para manter tipagem de inteiros e floats nos campos numéricos, garantindo retorno limpo para o front/Postman.
Teste realizado: Requisição `GET http://localhost/GestaoEstoqueEletroMVC/public/eletronico`.
Resultado obtido: HTTP 200 | JSON contendo a lista dos 3 eletrônicos iniciais com a propriedade `estoque_baixo` booleana calculada corretamente.
Evidência: Postman CT01 e CT02 | commit rota-get-eletronico
O que aprendemos: O `LEFT JOIN` preserva todos os registros da tabela da esquerda (eletrônicos) mesmo quando a tabela relacionada ainda não possui registros correspondentes.

---

## 21/10/2026 — POST /eletronico
Integrantes e papéis: Driver: Aluno 2 | Navigator: Aluno 1
Problema: O cadastro exige inserir dados cadastrais na tabela `eletronicos`, inicializar a tabela `estoque` e, caso a quantidade inicial seja maior que zero, registrar a primeira movimentação de ENTRADA, tudo de forma atômica para evitar dados órfãos se algo falhar.
Nossa tentativa: Fazer três `INSERT` separados no controller sem transação.
Prompt enviado à IA: "Como garantir que o cadastro do eletrônico e a criação do estoque inicial aconteçam juntos ou sejam cancelados se houver erro?"
Sugestão da IA: Utilizar transação PDO (`beginTransaction`, `commit` e `rollBack`), capturar o `lastInsertId` do eletrônico e tratar chave duplicada de `numero_lote` com HTTP 409.
Decisão da dupla: Aceitar a abordagem com transação dentro do Model e deixar as validações de campos obrigatórios (nome, lote, fabricante etc.) e tipagem no Controller devolvendo HTTP 422 em falha de validação.
Teste realizado: Envio de POST com JSON de um novo produto (Mouse Gamer) com estoque inicial de 10 unidades; em seguida, tentativa de envio do mesmo lote.
Resultado obtido: Primeiro teste: HTTP 201 | JSON `{"status":"sucesso","mensagem":"Eletrônico cadastrado com sucesso.","id_eletronico":4}`. Segundo teste: HTTP 409 | JSON `{"status":"erro","mensagem":"Número de lote já cadastrado no sistema."}`.
Evidência: Postman CT04 e CT05 | commit rota-post-eletronico
O que aprendemos: Transações com PDO são essenciais quando duas ou mais tabelas dependem da mesma operação de negócio, garantindo a integridade dos dados caso ocorra uma falha intermediária.

---

## 21/10/2026 — PUT /eletronico
Integrantes e papéis: Driver: Aluno 1 | Navigator: Aluno 2
Problema: Atualizar todos os dados cadastrais de um eletrônico garantindo que o produto exista e esteja ativo, e impedir que o `numero_lote` seja alterado para um lote que já pertença a outro produto.
Nossa tentativa: Fizemos um UPDATE direto passando o ID recebido, sem verificar antes se o produto existia e sem checar colisão de lote em outros registros.
Prompt enviado à IA: "Como validar colisão de lote único no PUT sem acusar erro quando o lote enviado for o mesmo que o próprio produto já usa?"
Sugestão da IA: Criar um método no Model que busque pelo número de lote com a cláusula adicional `AND id_eletronico != :id`. Se achar outro produto, retornar 409; se o produto não existir, retornar 404.
Decisão da dupla: Aceitar e implementar no Controller a busca prévia com `buscarPorId` (para 404) e `buscarPorLote` ignorando o próprio ID (para 409).
Teste realizado: Atualização de nome e preço do eletrônico id 1 (`PUT /eletronico` com corpo JSON) e tentativa com ID inexistente (999).
Resultado obtido: Produto 1: HTTP 200 | JSON `{"status":"sucesso","mensagem":"Eletrônico atualizado com sucesso."}`. ID 999: HTTP 404 | JSON `{"status":"erro","mensagem":"Eletrônico não encontrado."}`.
Evidência: Postman CT06 e CT07 | commit rota-put-eletronico
O que aprendemos: Em operações de edição, a validação de unicidade precisa desconsiderar o próprio ID do registro que está sendo alterado.

---

## 21/10/2026 — DELETE /eletronico
Integrantes e papéis: Driver: Aluno 2 | Navigator: Aluno 1
Problema: Desativar o produto sem apagar seus dados físicos, preservando integridade referencial com tabelas de estoque e movimentações (exclusão lógica).
Nossa tentativa: Estávamos pensando em rodar um comando `DELETE FROM eletronicos WHERE id = ?`, mas isso violaria chaves estrangeiras com histórico.
Prompt enviado à IA: "Qual a melhor forma de implementar exclusão lógica no MySQL com PDO e saber se o registro realmente existia e foi desativado?"
Sugestão da IA: Executar `UPDATE eletronicos SET ativo = 0 WHERE id_eletronico = :id AND ativo = 1` e verificar se `rowCount() > 0`. Se for 0, o registro não existia ou já estava inativo (HTTP 404).
Decisão da dupla: Aceitar integralmente. É muito mais simples e seguro, pois evita que consultas futuras tragam itens inativos sem quebrar o histórico de movimentações anteriores.
Teste realizado: `DELETE /eletronico?id=3` e em seguida uma segunda chamada repetida com o mesmo ID.
Resultado obtido: Primeira chamada: HTTP 200 | JSON `{"status":"sucesso","mensagem":"Eletrônico desativado com sucesso."}`. Segunda chamada: HTTP 404 | JSON `{"status":"erro","mensagem":"Eletrônico não encontrado ou já desativado."}`.
Evidência: Postman CT08 e CT09 | commit rota-delete-eletronico
O que aprendemos: Exclusão lógica com `rowCount()` permite saber instantaneamente se a alteração ocorreu sem precisar de duas consultas ao banco.

---

## 21/10/2026 — PUT /estoque
Integrantes e papéis: Driver: Aluno 1 | Navigator: Aluno 2
Problema: Registrar movimentações de entrada e saída no estoque com cálculo do novo saldo, impedindo saldo negativo em saídas e garantindo bloqueio de concorrência.
Nossa tentativa: Ler o saldo com SELECT, fazer a soma/subtração no PHP e dar UPDATE sem trava de linha e sem transação.
Prompt enviado à IA: "Como usar SELECT ... FOR UPDATE dentro de transação no PDO para evitar que duas requisições simultâneas deixem o saldo inconsistente?"
Sugestão da IA: Abrir transação com `beginTransaction()`, executar a busca do saldo atual com a cláusula `FOR UPDATE`, calcular o saldo posterior, validar se não fica negativo (retornando 422 em caso de saldo insuficiente), atualizar a tabela `estoque`, gravar o registro na tabela `movimentacoes` e confirmar com `commit()`.
Decisão da dupla: Aceitar e utilizar o `id_usuario = 1` fixo para atender a chave estrangeira da tabela movimentações sem necessidade de login.
Teste realizado: 
1. Entrada de 5 unidades no eletrônico 1.
2. Saída de 2 unidades no eletrônico 1.
3. Tentativa de saída de 500 unidades no eletrônico 1 (saldo insuficiente).
Resultado obtido: 
1. HTTP 200 | saldo atualizado de 8 para 13.
2. HTTP 200 | saldo atualizado de 13 para 11.
3. HTTP 422 | JSON `{"status":"erro","mensagem":"Saldo insuficiente para realizar a saída."}`.
Evidência: Postman CT10, CT11, CT12 e CT13 | commit rota-put-estoque
O que aprendemos: `SELECT ... FOR UPDATE` bloqueia a linha no banco de dados até o término da transação, garantindo consistência estrita em concorrência.

---

## 21/10/2026 — GET /movimentacoes
Integrantes e papéis: Driver: Aluno 2 | Navigator: Aluno 1
Problema: Listar todo o histórico de movimentações com os nomes do produto e do usuário responsável, permitindo filtro opcional pelo ID do produto.
Nossa tentativa: Fazer consulta simples na tabela `movimentacoes`, mas o resultado trazia apenas os IDs numéricos (`id_eletronico` e `id_usuario`), sem nomes legíveis.
Prompt enviado à IA: "Como montar a query do histórico unindo os nomes do produto e do usuário e adicionando filtro opcional por ID?"
Sugestão da IA: Fazer `JOIN eletronicos` e `JOIN usuarios`, ordenando por `data_movimentacao DESC`. No PHP, verificar se o parâmetro `id` foi enviado na query string para concatenar `WHERE m.id_eletronico = :id`.
Decisão da dupla: Adaptar validando no Controller se o ID enviado na query é um inteiro positivo antes de passar ao Model; se for inválido, devolver HTTP 422.
Teste realizado: `GET /movimentacoes` sem parâmetros e `GET /movimentacoes?id=1`.
Resultado obtido: HTTP 200 | Lista completa com tipo, quantidade, saldo anterior, posterior, nome do produto e nome do operador. No teste com id=1, filtrou apenas o produto correspondente.
Evidência: Postman CT15 e CT16 | commit rota-get-movimentacoes
O que aprendemos: Como estruturar consultas parametrizadas opcionais e enriquecer os dados de auditoria com JOINs sem quebrar a separação de camadas.
