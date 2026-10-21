## GET /eletronico
Integrantes e papéis: Angelina (Driver) | Beatriz (Navigator)
Problema: Produtos cadastrados sem registro na tabela de estoque vinham com saldo nulo ou eram omitidos na listagem.
Nossa tentativa: Usamos INNER JOIN entre eletrônicos e estoque.
Prompt enviado à IA: "Por que o INNER JOIN pode ocultar produtos sem estoque? Dê apenas uma pista."
Sugestão da IA: Comparar INNER JOIN com LEFT JOIN e tratar valores NULL com COALESCE.
Decisão da dupla: Adaptar — usamos LEFT JOIN com COALESCE(es.quantidade_atual, 0) e calculamos estoque_baixo no laço foreach.
Teste realizado: GET http://localhost/GestaoEstoqueEletroMVC/public/eletronico (sem corpo).
Resultado obtido: HTTP 200 OK | JSON:
[
  {
    "id_eletronico": 1,
    "nome": "Notebook Vision 14",
    "numero_lote": "LOTE-NB-001",
    "preco": "3299.90",
    "estoque_minimo": 3,
    "quantidade_atual": 8,
    "estoque_baixo": false
  },
  {
    "id_eletronico": 2,
    "nome": "Smartphone Connect X",
    "numero_lote": "LOTE-SP-001",
    "preco": "1899.00",
    "estoque_minimo": 5,
    "quantidade_atual": 4,
    "estoque_baixo": true
  },
  {
    "id_eletronico": 3,
    "nome": "Cabo HDMI 2m",
    "numero_lote": "LOTE-AC-001",
    "preco": "39.90",
    "estoque_minimo": 10,
    "quantidade_atual": 25,
    "estoque_baixo": false
  }
]
Evidência: Captura no Thunder Client / Postman (CT01).
O que aprendemos: O LEFT JOIN garante que todos os produtos apareçam na listagem, mesmo quando ainda não têm estoque associado.

## 21/10/2026 — POST /eletronico
Integrantes e papéis: Angelina (Driver) | Beatriz (Navigator)
Problema: Ao cadastrar um produto novo com quantidade inicial, precisamos inserir na tabela eletronicos, na tabela estoque e criar uma movimentação de ENTRADA sem deixar dados pela metade caso ocorra erro.
Nossa tentativa: Fazer três INSERTs separados no Controller sem transação.
Prompt enviado à IA: "Como garantir que o cadastro do eletrônico e o estoque inicial sejam salvos de forma atômica?"
Sugestão da IA: Usar transação PDO (beginTransaction, commit e rollBack) e verificar antes se o número do lote já existe.
Decisão da dupla: Aceitar — colocamos a transação dentro do Model e o Controller valida se os campos obrigatórios foram enviados (devolvendo 422 se faltar algo e 409 se o lote já existir).
Teste realizado: POST http://localhost/GestaoEstoqueEletroMVC/public/eletronico com o corpo JSON:
{
  "nome": "Mouse Gamer USB",
  "descricao": "Mouse óptico 16000 DPI",
  "numero_lote": "LOTE-MS-001",
  "fabricante": "TechPlus",
  "categoria": "Periféricos",
  "preco": 120.00,
  "estoque_minimo": 5,
  "quantidade_inicial": 10
}
Resultado obtido: HTTP 201 Created | JSON:
{
  "message": "Eletronico cadastrado com sucesso.",
  "id_eletronico": 4
}
Evidência: Captura no Thunder Client / Postman (CT04).
O que aprendemos: Transações com beginTransaction() evitam inconsistência no banco caso uma das tabelas falhe na gravação.

## 21/10/2026 — PUT /eletronico
Integrantes e papéis: Angelina (Driver) | Beatriz (Navigator)
Problema: Atualizar os dados do produto sem permitir que o número de lote seja trocado por um lote que já pertence a outro eletrônico.
Nossa tentativa: UPDATE direto pelo ID sem verificar colisão de lote.
Prompt enviado à IA: "Como validar lote duplicado no UPDATE sem acusar erro no próprio eletrônico que está sendo editado?"
Sugestão da IA: Buscar o lote no banco adicionando a condição "AND id_eletronico != :id". Se encontrar outro, devolver 409; se o ID não existir, devolver 404.
Decisão da dupla: Aceitar — porque impede duplicação de lote sem travar a edição caso o produto mantenha o mesmo lote.
Teste realizado: PUT http://localhost/GestaoEstoqueEletroMVC/public/eletronico com o corpo JSON:
{
  "id": 1,
  "nome": "Notebook Vision 14 Pro",
  "descricao": "Notebook atualizado para escritório",
  "numero_lote": "LOTE-NB-001",
  "fabricante": "TechVision",
  "categoria": "Notebook",
  "preco": 3499.00,
  "estoque_minimo": 4
}
Resultado obtido: HTTP 200 OK | JSON:
{
  "message": "Eletronico atualizado com sucesso."
}
Evidência: Captura no Thunder Client / Postman (CT07).
O que aprendemos: Em atualizações, a regra de campo único deve sempre desconsiderar o próprio ID do registro alterado.

## 21/10/2026 — DELETE /eletronico?id=1
Integrantes e papéis: Angelina (Driver) | Beatriz (Navigator)
Problema: Desativar o produto sem apagar os dados do banco para preservar o histórico de estoque e movimentações (exclusão lógica).
Nossa tentativa: Usar DELETE no banco de dados, o que quebrava chaves estrangeiras.
Prompt enviado à IA: "Como fazer exclusão lógica de produto no MySQL com PDO?"
Sugestão da IA: Fazer UPDATE para "ativo = 0 WHERE id_eletronico = :id AND ativo = 1" e verificar rowCount().
Decisão da dupla: Aceitar — se rowCount() for 0 significa que o produto não existe ou já estava inativo (retorna 404), senão retorna 200.
Teste realizado: DELETE http://localhost/GestaoEstoqueEletroMVC/public/eletronico?id=3 (sem corpo).
Resultado obtido: HTTP 200 OK | JSON:
{
  "message": "Eletronico desativado com sucesso."
}
Evidência: Captura no Thunder Client / Postman (CT09).
O que aprendemos: Exclusão lógica mantém a integridade referencial com tabelas filhas sem perder registros históricos.

## 21/10/2026 — PUT /estoque
Integrantes e papéis: Angelina (Driver) | Beatriz (Navigator)
Problema: Movimentar entrada ou saída de itens sem deixar o saldo ficar negativo e garantindo que o estoque não seja alterado simultaneamente.
Nossa tentativa: Calcular o saldo direto no PHP e atualizar a tabela estoque sem transação.
Prompt enviado à IA: "Como usar SELECT ... FOR UPDATE dentro de transação no PDO para movimentar estoque?"
Sugestão da IA: Abrir transação, ler a linha com FOR UPDATE para travar concorrência, verificar saldo suficiente em SAIDA (422), atualizar estoque e gravar movimentação com id_usuario = 1.
Decisão da dupla: Aceitar — porque segue o pseudocódigo e garante que o histórico de movimentação seja gravado junto com o novo saldo.
Teste realizado: PUT http://localhost/GestaoEstoqueEletroMVC/public/estoque com o corpo JSON:
{
  "id_eletronico": 1,
  "tipo": "ENTRADA",
  "quantidade": 5,
  "observacao": "Recebimento de lote"
}
Resultado obtido: HTTP 200 OK | JSON:
{
  "message": "Estoque movimentado com sucesso.",
  "dados": {
    "id_eletronico": 1,
    "tipo": "ENTRADA",
    "quantidade": 5,
    "saldo_anterior": 8,
    "saldo_posterior": 13,
    "observacao": "Recebimento de lote"
  }
}
Evidência: Captura no Thunder Client / Postman (CT11).
O que aprendemos: SELECT ... FOR UPDATE impede que duas operações mexam no mesmo saldo ao mesmo tempo, evitando erros de concorrência.

## 21/10/2026 — GET /movimentacoes
Integrantes e papéis: Angelina (Driver) | Beatriz (Navigator)
Problema: Listar o histórico de movimentações trazendo os nomes legíveis do eletrônico e do operador em vez de apenas números de ID.
Nossa tentativa: Fazer SELECT direto na tabela movimentacoes sem JOIN.
Prompt enviado à IA: "Como fazer consulta com JOIN em eletronicos e usuarios e permitir filtro por ID?"
Sugestão da IA: Fazer JOIN com as tabelas eletronicos e usuarios, ordenar por data decrescente e adicionar WHERE se o parâmetro id for passado na URL.
Decisão da dupla: Aceitar — porque devolve um histórico legível para o operador e aceita filtro por produto específico.
Teste realizado: GET http://localhost/GestaoEstoqueEletroMVC/public/movimentacoes (sem corpo).
Resultado obtido: HTTP 200 OK | JSON:
[
  {
    "id_movimentacao": 4,
    "id_eletronico": 1,
    "eletronico_nome": "Notebook Vision 14 Pro",
    "id_usuario": 1,
    "usuario_nome": "Administrador",
    "tipo": "ENTRADA",
    "quantidade": 5,
    "saldo_anterior": 8,
    "saldo_posterior": 13,
    "observacao": "Recebimento de lote",
    "data_movimentacao": "2026-10-21 15:30:00"
  },
  {
    "id_movimentacao": 1,
    "id_eletronico": 1,
    "eletronico_nome": "Notebook Vision 14 Pro",
    "id_usuario": 1,
    "usuario_nome": "Administrador",
    "tipo": "ENTRADA",
    "quantidade": 8,
    "saldo_anterior": 0,
    "saldo_posterior": 8,
    "observacao": "Carga inicial",
    "data_movimentacao": "2026-10-21 14:19:20"
  }
]
Evidência: Captura no Thunder Client / Postman (CT15).
O que aprendemos: JOIN reúne dados relacionados de forma clara em uma única requisição.
