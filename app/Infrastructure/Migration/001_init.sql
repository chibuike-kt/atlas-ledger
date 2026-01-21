-- MySQL 8.0+ / InnoDB
CREATE TABLE IF NOT EXISTS wallets (
  id            BINARY(16) PRIMARY KEY,
  owner_type    VARCHAR(32) NOT NULL,
  owner_id      VARCHAR(64) NOT NULL,
  currency      CHAR(3)     NOT NULL,
  status        ENUM('active','frozen','closed') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at    TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  UNIQUE KEY uq_wallet_owner_currency (owner_type, owner_id, currency)
) ENGINE=InnoDB;

-- Ledger "accounts": each wallet maps to one account; system accounts exist too.
CREATE TABLE IF NOT EXISTS ledger_accounts (
  id            BINARY(16) PRIMARY KEY,
  type          ENUM('wallet','system') NOT NULL,
  ref_id        BINARY(16) NULL, -- wallet id if type=wallet
  name          VARCHAR(100) NULL, -- for system accounts (e.g., 'fees', 'suspense')
  currency      CHAR(3) NOT NULL,
  created_at    TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  UNIQUE KEY uq_account_wallet (type, ref_id),
  UNIQUE KEY uq_account_system (type, name, currency)
) ENGINE=InnoDB;

-- Transfers are represented as journal entries (one per business event).
CREATE TABLE IF NOT EXISTS ledger_journals (
  id              BINARY(16) PRIMARY KEY,
  idempotency_key VARBINARY(80) NOT NULL,
  reason          VARCHAR(64) NOT NULL, -- e.g. transfer, deposit, withdrawal, fee
  external_ref    VARCHAR(128) NULL,    -- optional reference from upstream
  currency        CHAR(3) NOT NULL,
  created_at      TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  UNIQUE KEY uq_journal_idem (idempotency_key)
) ENGINE=InnoDB;

-- Each journal has >=2 lines. Use signed BIGINT of "minor units" (kobo/cents).
CREATE TABLE IF NOT EXISTS ledger_lines (
  id            BINARY(16) PRIMARY KEY,
  journal_id    BINARY(16) NOT NULL,
  account_id    BINARY(16) NOT NULL,
  amount_minor  BIGINT NOT NULL, -- debit/credit as +/-
  created_at    TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  CONSTRAINT fk_lines_journal FOREIGN KEY (journal_id) REFERENCES ledger_journals(id),
  CONSTRAINT fk_lines_account FOREIGN KEY (account_id) REFERENCES ledger_accounts(id),
  KEY ix_lines_journal (journal_id),
  KEY ix_lines_account (account_id)
) ENGINE=InnoDB;

-- Fast balance snapshot (derived from ledger_lines). Updated inside same transaction.
CREATE TABLE IF NOT EXISTS account_balances (
  account_id     BINARY(16) PRIMARY KEY,
  balance_minor  BIGINT NOT NULL DEFAULT 0,
  updated_at     TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  CONSTRAINT fk_bal_account FOREIGN KEY (account_id) REFERENCES ledger_accounts(id)
) ENGINE=InnoDB;

-- Idempotency storage (request-level)
CREATE TABLE IF NOT EXISTS idempotency_keys (
  idem_key       VARBINARY(80) PRIMARY KEY,
  request_hash   BINARY(32) NOT NULL, -- SHA-256
  journal_id     BINARY(16) NULL,
  status         ENUM('started','completed','failed') NOT NULL,
  created_at     TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at     TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  KEY ix_idem_status (status)
) ENGINE=InnoDB;

-- Append-only audit log (who/what/when). Keep it simple for now.
CREATE TABLE IF NOT EXISTS audit_log (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  actor         VARCHAR(64) NULL, -- user/admin/service
  action        VARCHAR(64) NOT NULL,
  entity_type   VARCHAR(32) NOT NULL,
  entity_id     BINARY(16) NULL,
  meta_json     JSON NULL,
  created_at    TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  KEY ix_audit_entity (entity_type, entity_id),
  KEY ix_audit_action (action)
) ENGINE=InnoDB;


