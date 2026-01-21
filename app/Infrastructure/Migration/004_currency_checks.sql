-- Add NGN-only CHECK constraints safely (idempotent)
-- MySQL stores checks in INFORMATION_SCHEMA.TABLE_CONSTRAINTS
-- constraint_type = 'CHECK'

SET @schema := DATABASE();

-- wallets
SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = @schema
    AND table_name = 'wallets'
    AND constraint_name = 'chk_wallet_currency_ngn'
    AND constraint_type = 'CHECK'
);
SET @sql := IF(@exists = 0,
  "ALTER TABLE wallets ADD CONSTRAINT chk_wallet_currency_ngn CHECK (currency = 'NGN')",
  "SELECT 'skip chk_wallet_currency_ngn (wallets)'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ledger_accounts
SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = @schema
    AND table_name = 'ledger_accounts'
    AND constraint_name = 'chk_account_currency_ngn'
    AND constraint_type = 'CHECK'
);
SET @sql := IF(@exists = 0,
  "ALTER TABLE ledger_accounts ADD CONSTRAINT chk_account_currency_ngn CHECK (currency = 'NGN')",
  "SELECT 'skip chk_account_currency_ngn (ledger_accounts)'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ledger_journals
SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = @schema
    AND table_name = 'ledger_journals'
    AND constraint_name = 'chk_journal_currency_ngn'
    AND constraint_type = 'CHECK'
);
SET @sql := IF(@exists = 0,
  "ALTER TABLE ledger_journals ADD CONSTRAINT chk_journal_currency_ngn CHECK (currency = 'NGN')",
  "SELECT 'skip chk_journal_currency_ngn (ledger_journals)'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
