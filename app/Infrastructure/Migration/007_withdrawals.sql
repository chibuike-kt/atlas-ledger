CREATE TABLE IF NOT EXISTS withdrawals (
  id              BINARY(16) PRIMARY KEY,
  wallet_id        BINARY(16) NOT NULL,
  hold_id          BINARY(16) NULL,
  amount_minor     BIGINT NOT NULL,
  fee_minor        BIGINT NOT NULL DEFAULT 0,
  net_minor        BIGINT NOT NULL,
  status           ENUM('pending','processing','paid','failed','reversed') NOT NULL DEFAULT 'pending',
  idempotency_key  VARBINARY(80) NOT NULL,
  external_ref     VARCHAR(128) NULL, -- bank transfer ref, provider ref, etc
  created_at       TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at       TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  UNIQUE KEY uq_withdraw_idem (idempotency_key),
  KEY ix_withdraw_wallet_status (wallet_id, status),
  CONSTRAINT fk_withdraw_wallet FOREIGN KEY (wallet_id) REFERENCES wallets(id),
  CONSTRAINT fk_withdraw_hold FOREIGN KEY (hold_id) REFERENCES wallet_holds(id)
) ENGINE=InnoDB;
