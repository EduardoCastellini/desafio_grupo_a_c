Requisitos funcionais do sistema:
1 Cadastro de Usuario e autenticação.
  já estão implementados (front e back).

2 Uma carteira digital para cada usuário.
  Cada usuário deve ter uma carteira digital associada a sua conta, onde será possível visualizar o saldo disponível e o histórico de transações realizadas.
  A carteira digital deve ser criada automaticamente no momento do cadastro do usuário, com saldo inicial igual a zero.

3 Transferir um valor para outro usuario.
  Criar uma tela onde seja possivel que um usuário possa enviar um valor para outro usuário registrado no sistema.

  Validaçoes:
    * O usuário não pode enviar um valor maior que o saldo atual.

4 Receber um valor de outro usuario.
  Aparentemente, o recebimento de valores é automático, não sendo necessário criar uma tela para isso. O usuário deve apenas visualizar o saldo atualizado após receber um valor.

5 Criar tela para que o usuário possa depositar um valor em sua conta.
  O sistema deve permitir que o usuário deposite um valor em sua conta, aumentando seu saldo disponível.

6 Criar uma tela para que o usuário possa reverter uma transação realizada.
  O sistema deve permitir que o usuário reverta uma transação realizada, desde que tenha saldo suficiente para isso. 
  A reversão de uma transação deve atualizar o saldo do usuário em caso de reversão de um deposito, e também deve atualizar o saldo do usuário que recebeu o valor em caso de reversão de uma transferência.
  A reversão de uma transação deve ser registrada no histórico de transações do usuário, indicando que se trata de uma reversão.
  Não deve deletar a transação original, apenas criar uma nova transação de reversão.

Requisitos não funcionais do sistema:
1 As transações devem ser registradas no banco de dados de maneira Atômica, Consistente, Isolável e Durável. 

Modelo de dados:
- users:
  id int
  name string
  email string
  password string
  ...

- wallets:
  id int
  wallet_transaction_key string UNIQUE NOT NULL
  user_id int
  balance BIGINT
  created_at datetime
  updated_at datetime

- transactions:
  id int
  user_id int
  type string (deposit, transfer, reversal)
  amount BIGINT
  idempotency_key string
  reversal_of_id int (nullable)
  created_at datetime
  updated_at datetime

- ledger_entries
  id int
  transaction_id int
  wallet_id int
  amount BIGINT
  created_at datetime
  updated_at datetime


Rotas / Telas:
- /register (POST) - Tela de cadastro de usuário. (já implementada)
- /login (POST) - Tela de login de usuário. (já implementada)
- /wallet (GET) - Tela de visualização da carteira digital do usuário, mostrando saldo disponível e histórico de transações.
- /wallet/deposit (POST) - Tela para o usuário depositar um valor em sua conta.
- /wallet/transfer (POST) - Tela para o usuário transferir um valor para outro usuário registrado no sistema.
- /wallet/revert (POST) - Tela para o usuário reverter uma transação realizada, desde que tenha saldo suficiente para isso.

Cenarios de teste:
1. Cadastro de usuário:
   - Acessar a tela de cadastro de usuário.
   - Preencher os campos obrigatórios (nome, email, senha).
   - Clicar no botão de cadastro.
   - Verificar se o usuário foi cadastrado com sucesso e se a carteira digital foi criada automaticamente com saldo inicial igual a zero.
2. Login de usuário:
   - Acessar a tela de login de usuário.
   - Preencher os campos obrigatórios (email, senha).
   - Clicar no botão de login.
   - Verificar se o usuário foi autenticado com sucesso e se foi redirecionado para a tela de visualização da carteira digital.
3. Visualização da carteira digital:
   - Acessar a tela de visualização da carteira digital.
   - Verificar se o saldo disponível e o histórico de transações estão sendo exibidos corretamente.
4. Depósito de valor:
   - Acessar a tela de depósito de valor.
   - Preencher o campo de valor a ser depositado.
   - Clicar no botão de depósito.
   - Verificar se o valor foi depositado com sucesso e se o saldo disponível foi atualizado corretamente.
5. Transferência de valor:
   - Acessar a tela de transferência de valor.
   - Preencher os campos obrigatórios (email do destinatário, valor a ser transferido).
   - Clicar no botão de transferência.
   - Verificar se o valor foi transferido com sucesso e se o saldo disponível do remetente foi atualizado corretamente.
   - Verificar se o destinatário recebeu o valor transferido e se o saldo disponível do destinatário foi atualizado corretamente.
6. Reversão de transação:
   - Acessar a tela de reversão de transação.
   - Selecionar a transação a ser revertida.
   - Clicar no botão de reversão.
   - Verificar se a transação foi revertida com sucesso e se o saldo disponível do usuário foi atualizado corretamente.
   - Verificar se o saldo disponível do destinatário da transação revertida foi atualizado corretamente, caso a transação revertida tenha sido uma transferência. 


Arquitetura e tecnologias utilizadas:
A principio, será um monólito com Laravel e inertia.js para renderização do frontend, que será em React. 
O backend será dividido em camadas, com controllers, services e infra onde deve ficar os repositorios.
O banco de dados será relacional, utilizaremos o PostgreSQL.