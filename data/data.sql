CREATE TABLE users (
   id INT PRIMARY KEY,
   username VARCHAR(255) NOT NULL,
   email VARCHAR(255) NOT NULL,
   is_confirmed BOOLEAN DEFAULT FALSE,
   is_checked BOOLEAN DEFAULT FALSE,
   is_valid BOOLEAN DEFAULT FALSE
);

CREATE TABLE subscriptions (
   id INT PRIMARY KEY,
   user_id INT,
   expired_at DATETIME,
   FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_subscriptions_expired_at ON subscriptions(expired_at);
CREATE INDEX idx_users_is_valid_is_checked ON users(is_valid, is_checked);
CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX idx_users_is_confirmed ON users(is_confirmed);

-- Generate 100 records for the users table
DELIMITER $$
CREATE PROCEDURE generate_users()
BEGIN
  DECLARE i INT DEFAULT 0;
  WHILE i < 100 DO
    INSERT INTO users (id, username, email, is_confirmed, is_checked, is_valid)
    VALUES (i, CONCAT('user', i), CONCAT('user', i, '@example.com'), RAND() < 0.15, RAND() < 0.8, RAND() < 0.8);
    SET i = i + 1;
END WHILE;
END$$
DELIMITER ;

CALL generate_users();

-- Generate 20 records for the subscriptions table
DELIMITER $$
CREATE PROCEDURE generate_subscriptions()
BEGIN
  DECLARE i INT DEFAULT 0;
  WHILE i < 20 DO
    INSERT INTO subscriptions (id, user_id, expired_at)
    VALUES (i, FLOOR(1 + RAND() * 99), TIMESTAMPADD(DAY, FLOOR(1 + RAND() * 30), NOW() + RAND()));
    SET i = i + 1;
END WHILE;
END$$
DELIMITER ;

CALL generate_subscriptions();