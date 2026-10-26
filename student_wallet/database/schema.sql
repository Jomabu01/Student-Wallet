

CREATE DATABASE IF NOT EXISTS wallet_db;
USE wallet_db;


CREATE TABLE Users (
    user_id INT NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(30) NOT NULL,
    PRIMARY KEY (user_id)
);

CREATE TABLE Wallet (
    wallet_id INT NOT NULL AUTO_INCREMENT,
    current_balance DECIMAL(10,2) NOT NULL,
    created_date DATETIME NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (wallet_id),
    CONSTRAINT fk_wallet_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE Category (
    category_id INT NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(255) NOT NULL,
    category_icon VARCHAR(255) NOT NULL,
    PRIMARY KEY (category_id)
);

CREATE TABLE Income (
    income_id INT NOT NULL AUTO_INCREMENT,
    amount DECIMAL(10,2) NOT NULL,
    source VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    income_date DATETIME NOT NULL,
    wallet_id INT NOT NULL,
    PRIMARY KEY (income_id),
    CONSTRAINT fk_income_wallet
        FOREIGN KEY (wallet_id)
        REFERENCES Wallet(wallet_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE Expenses (
    expense_id INT NOT NULL AUTO_INCREMENT,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    expense_date DATETIME NOT NULL,
    wallet_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (expense_id),
    CONSTRAINT fk_expenses_wallet
        FOREIGN KEY (wallet_id)
        REFERENCES Wallet(wallet_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_expenses_category
        FOREIGN KEY (category_id)
        REFERENCES Category(category_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE Budget (
    budget_id INT NOT NULL AUTO_INCREMENT,
    budget_limit DECIMAL(10,2) NOT NULL,
    month DECIMAL(10,2) NOT NULL,
    year YEAR NOT NULL,
    period_type VARCHAR(10) NOT NULL DEFAULT 'monthly',
    period_start DATE NOT NULL,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (budget_id),
    CONSTRAINT fk_budget_category
        FOREIGN KEY (category_id)
        REFERENCES Category(category_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_budget_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


INSERT INTO Category (category_name, category_icon) VALUES
('Food', 'food.png'),
('Transportation', 'transport.png'),
('School Supplies', 'school.png'),
('Mobile Data', 'data.png'),
('Leisure', 'leisure.png');
