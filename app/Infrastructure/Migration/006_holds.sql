-- extend enum to support wallet_hold
ALTER TABLE ledger_accounts
  MODIFY COLUMN type ENUM('wallet','wallet_hold','system') NOT NULL;

CREATE TABLE IF NOT EXISTS wallet_holds (
  id              BINARY(16) PRIMARY KEY,
  wallet_id        BINARY(16) NOT NULL,
  amount_minor     BIGINT NOT NULL,
  status           ENUM('active','released','captured','expired') NOT NULL DEFAULT 'active',
  reason           VARCHAR(64) NOT NULL,     -- e.g. withdrawal, dispute, authorization
  idempotency_key  VARBINARY(80) NOT NULL,
  external_ref     VARCHAR(128) NULL,
  expires_at       TIMESTAMP(6) NULL,
  created_at       TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at       TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  UNIQUE KEY uq_hold_idem (idempotency_key),
  KEY ix_hold_wallet_status (wallet_id, status),
  CONSTRAINT fk_hold_wallet FOREIGN KEY (wallet_id) REFERENCES wallets(id)
) ENGINE=InnoDB;
