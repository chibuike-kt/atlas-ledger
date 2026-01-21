-- System account that represents "outside world" funding source for deposits
INSERT INTO ledger_accounts (id, type, ref_id, name, currency)
SELECT UNHEX(REPLACE(UUID(),'-','')), 'system', NULL, 'funding', 'NGN'
WHERE NOT EXISTS (
  SELECT 1 FROM ledger_accounts WHERE type='system' AND name='funding' AND currency='NGN'
);

-- Ensure it has a balance row
INSERT INTO account_balances (account_id, balance_minor)
SELECT a.id, 0
FROM ledger_accounts a
LEFT JOIN account_balances b ON b.account_id = a.id
WHERE a.type='system' AND a.name='funding' AND a.currency='NGN'
  AND b.account_id IS NULL;
