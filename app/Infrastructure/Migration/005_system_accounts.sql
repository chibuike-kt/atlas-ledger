-- payout system account
INSERT INTO ledger_accounts (id, type, ref_id, name, currency)
SELECT UNHEX(REPLACE(UUID(),'-','')), 'system', NULL, 'payout', 'NGN'
WHERE NOT EXISTS (
  SELECT 1 FROM ledger_accounts WHERE type='system' AND name='payout' AND currency='NGN'
);

-- fees system account
INSERT INTO ledger_accounts (id, type, ref_id, name, currency)
SELECT UNHEX(REPLACE(UUID(),'-','')), 'system', NULL, 'fees', 'NGN'
WHERE NOT EXISTS (
  SELECT 1 FROM ledger_accounts WHERE type='system' AND name='fees' AND currency='NGN'
);

-- ensure balance rows exist
INSERT INTO account_balances (account_id, balance_minor)
SELECT a.id, 0
FROM ledger_accounts a
LEFT JOIN account_balances b ON b.account_id = a.id
WHERE a.type='system' AND a.name IN ('payout','fees') AND a.currency='NGN'
  AND b.account_id IS NULL;
