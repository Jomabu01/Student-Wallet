USE wallet_db;

ALTER TABLE Users
    MODIFY phone_number VARCHAR(30) NOT NULL;
