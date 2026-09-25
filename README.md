# Carteira Digital

Aplicação Laravel para gerenciamento de saldo, depósitos, transferências e reversões financeiras com idempotência e consistência transacional.

## Requisitos

- Docker
- Docker Compose
- PHP 8.5
- Composer

## Subindo o ambiente com Sail

1. Copie as variáveis de ambiente:
   ```bash
   cp .env.example .env
   ```
2. Inicie os containers:
   ```bash
   ./vendor/bin/sail up -d
   ```
   ou, quando o projeto já estiver configurado via Compose:
   ```bash
   docker compose up -d
   ```
3. Rode as migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```
4. Execute a suíte de testes:
   ```bash
   ./vendor/bin/sail test
   ```

## Configuração de ambiente

A aplicação utiliza PostgreSQL em desenvolvimento e testes. O arquivo `.env` deve manter valores compatíveis com o Sail/Compose:

```env
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

Quando os testes são executados fora do container, o host pode ser `127.0.0.1` e a porta exposta pelo Compose deve estar acessível.

## Modelo financeiro

### Wallet
- representa a carteira de um usuário;
- possui saldo inteiro em centavos;
- cada usuário tem uma única carteira;
- a chave `wallet_transaction_key` é única e usada para identificar transferências.

### Transaction
- registra operações de depósito, transferência e reversão;
- guarda `amount` em centavos;
- possui `idempotency_key` exclusiva por usuário;
- mantém `reversal_of_id` para vincular reversões à transação original.

### LedgerEntry
- cada operação financeira cria um ou mais lançamentos contábeis;
- o saldo é atualizado apenas através dos repositórios de operação;
- os importes são armazenados como inteiros, sem float.

## Valores e centavos

Toda operação financeira usa centavos como unidade principal. O sistema trata `amount` como inteiro, com regra simples:

- `deposit`: positivo e maior que zero;
- `transfer`: positivo e maior que zero;
- `reversal`: gera lançamentos inversos aos valores originais.

## Idempotência

O projeto usa `idempotency_key` para garantir reenvio seguro da mesma operação. Regras importantes:

- a mesma chave por usuário identifica a mesma operação;
- chave reutilizada com mesmo valor e mesmos parâmetros retorna a transação original;
- chave reutilizada com valor ou destino diferente é rejeitada com exceção;
- a validação é feita em nível de repositório com `where(user_id, idempotency_key)` e checagem de compatibilidade.

## Atomicidade, locks e concorrência

O projeto busca consistência financeira com práticas de banco:

- `DB::transaction()` envolve cada operação financeira;
- `lockForUpdate()` bloqueia carteiras e transações relevantes para evitar race conditions;
- transferências com carteiras distintas ordenam os locks por `id` para reduzir deadlocks;
- ACID é aplicado ao nível da transação de banco com campos monetários inteiros.

## Endpoints principais

- `POST /wallet/deposit` — realiza depósito na carteira autenticada.
- `POST /wallet/transfer` — transfere valor de uma carteira para outra.
- `POST /wallet/transactions/{transaction}/reverse` — reverte uma transação anterior.

Todos os endpoints financeiros exigem autenticação e proteção por `throttle`.

## Decisões arquiteturais

- a regra de negócio fica nos services;
- controllers apenas validam entrada e delegam a operação;
- `FormRequest` valida entradas e bloqueia campos proibidos como `source_wallet_id`;
- `Wallet`, `Transaction` e `LedgerEntry` têm `fillable` restritivo;
- operações nunca usam float para moeda.

## Limitações conhecidas

- o projeto assume saldo em centavos e não trata conversão de moeda;
- a reversão só aplica em transações não reversas e já não revertidas;
- logs registram metadados financeiros, mas não dados sensíveis do usuário;
- a aplicação depende de PostgreSQL real para testes de concorrência e locks.

## Observabilidade

Operaçõess financeiras registram eventos relevantes com campos como:

- `user_id`
- `transaction_id`
- `reversal_of_id`
- `idempotency_key`
- `operation_type`
- `amount`
- `wallet_id` / `wallet_ids`

Esses eventos ajudam a rastrear depósitos, transferências, reversões e conflitos de idempotência sem expor dados sensíveis.
