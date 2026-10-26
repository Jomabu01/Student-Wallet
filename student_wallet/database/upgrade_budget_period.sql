USE wallet_db;

ALTER TABLE Budget
    ADD COLUMN period_type VARCHAR(10) NOT NULL DEFAULT 'monthly',
    ADD COLUMN period_start DATE NULL;

UPDATE Budget
SET period_start = STR_TO_DATE(
    CONCAT(year, '-', LPAD(CAST(month AS UNSIGNED), 2, '0'), '-01'),
    '%Y-%m-%d'
);

ALTER TABLE Budget
    MODIFY COLUMN period_start DATE NOT NULL;
