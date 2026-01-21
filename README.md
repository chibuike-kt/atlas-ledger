# Atlas Ledger

Atlas Ledger is a secure wallet and double-entry ledger system built in plain PHP with MySQL. It is designed as a backend foundation for fintech money movement: atomic transactions, idempotency, reconciliation, and auditability.

This project is intended to demonstrate how production-grade ledger logic is implemented without relying on full-stack frameworks.

## Features

## Core Ledger

- Double-entry ledger model (journals and lines) that always balances to zero.
- Stored balances for fast read queries.
- Derived balance calculations from ledger lines for reconciliation.
- System accounts (for funding, payouts, and fees).

## Wallet Layer

- Wallet creation in NGN (kobo precision).
- Deposits into wallets (funding account to wallet account).
- Wallet-to-wallet transfers.
- Deterministic row locking to prevent race conditions under concurrency.
- Strict idempotency for money routes to prevent duplicates.

## Infrastructure Features

- API key authentication middleware.
- Idempotency key middleware enforceable via header.
- MySQL-backed rate limiting middleware (per actor, IP, and endpoint).
- Audit logging of significant events for traceability.
- Health endpoint that can operate independently of the database.
- Migration tracking with a `schema_migrations` table.

## Architecture

app/
Application/
UseCase/
Domain/
Shared/
Infrastructure/
Database/
Migration/
Interface/
Cli/
Http/
bootstrap/
config/
public/
routes/

markdown
Copy code

## Requirements

- PHP 8.2+
- MySQL 8+
- Composer

## Setup Instructions

### Install dependencies

```bash
composer install
Configure environment
Copy and edit the environment file:

bash
Copy code
copy .env.example .env
Modify the values to suit your environment (database credentials, API keys, etc).

Run migrations
bash
Copy code
php app/Interface/Cli/migrate.php
Start the server
bash
Copy code
php -S 127.0.0.1:8000 -t public
API Endpoints
Health Check
bash
Copy code
GET /health
Create Wallet
bash
Copy code
POST /wallets
Headers:
  Content-Type: application/json
  X-API-Key: <api_key>

Body:
{
  "owner_type": "user",
  "owner_id": "u1"
}
Deposit
yaml
Copy code
POST /deposits
Headers:
  Content-Type: application/json
  X-API-Key: <api_key>
  Idempotency-Key: <id_key>

Body:
{
  "to_wallet_id": "32_hex_chars_no_prefix",
  "amount_kobo": 200000,
  "external_ref": "bank_tx_001"
}
Transfer
yaml
Copy code
POST /transfers
Headers:
  Content-Type: application/json
  X-API-Key: <api_key>
  Idempotency-Key: <id_key>

Body:
{
  "from_wallet_id": "32_hex_chars_no_prefix",
  "to_wallet_id": "32_hex_chars_no_prefix",
  "amount_kobo": 50000
}
Check Balance
yaml
Copy code
POST /wallets/balance
Headers:
  Content-Type: application/json
  X-API-Key: <api_key>

Body:
{
  "wallet_id": "32_hex_chars_no_prefix",
  "include_derived": true
}
Reconciliation Queries
Unbalanced Journals
sql
Copy code
SELECT
  HEX(j.id) AS journal_id,
  SUM(l.amount_minor) AS sum_minor,
  COUNT(*) AS line_count
FROM ledger_journals j
JOIN ledger_lines l ON l.journal_id = j.id
GROUP BY j.id
HAVING SUM(l.amount_minor) <> 0 OR COUNT(*) < 2;
Stored vs Derived Balance Drift
sql
Copy code
SELECT
  HEX(a.id) AS account_id,
  b.balance_minor AS stored_balance,
  COALESCE(SUM(l.amount_minor), 0) AS derived_balance,
  (b.balance_minor - COALESCE(SUM(l.amount_minor), 0)) AS diff
FROM ledger_accounts a
JOIN account_balances b ON b.account_id = a.id
LEFT JOIN ledger_lines l ON l.account_id = a.id
GROUP BY a.id, b.balance_minor
HAVING diff <> 0;
Notes on Correctness
All money movement is double-entry and occurs in database transactions.

Balance rows are always locked before update to prevent race conditions.

Idempotency keys protect endpoints from duplicate processing.

Audit logs capture meaningful metadata for forensic analysis.

License
MIT