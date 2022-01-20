DROP TABLE IF EXISTS user_website;
DROP TABLE IF EXISTS password;
DROP TABLE IF EXISTS website;
DROP TABLE IF EXISTS credit_card;
DROP TABLE IF EXISTS authentication;
DROP TABLE IF EXISTS "user";

CREATE TABLE "user"(
    id SERIAL NOT NULL PRIMARY KEY,
    firstName VARCHAR(255),
    lastName VARCHAR(255),
    email VARCHAR(255)
);

CREATE TABLE authentication(
    userId INT NOT NULL PRIMARY KEY ,
    password VARCHAR(255) NOT NULL,
    validator VARCHAR(255),
    dateTime TIMESTAMP DEFAULT now(),
    lastConnection TIMESTAMP DEFAULT NULL,
    lastActivity TIMESTAMP DEFAULT NULL,
    multiFactorMask INT DEFAULT 0,
    googleAuthSecret VARCHAR(255) DEFAULT NULL,
    locked BOOLEAN DEFAULT FALSE,
    FOREIGN KEY ( userId ) REFERENCES "user"(id) ON DELETE CASCADE
);

CREATE TABLE credit_card(
    id SERIAL NOT NULL PRIMARY KEY,
    userId INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    number VARCHAR(255),
    expirationMonth SMALLINT,
    expirationYear SMALLINT,
    cvc varchar(255),
    FOREIGN KEY (userId) REFERENCES "user"(id) ON DELETE CASCADE
);

CREATE TABLE website(
    id SERIAL NOT NULL PRIMARY KEY,
    url VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    isFavorite BOOLEAN DEFAULT FALSE,
    usernameFieldId VARCHAR(255),
    passwordFieldId VARCHAR(255)
);

CREATE TABLE password(
    id INT NOT NULL PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    validator VARCHAR(255),
    dateTime TIMESTAMP DEFAULT now(),
    FOREIGN KEY (id) REFERENCES website(id) ON DELETE CASCADE
);

CREATE TABLE user_website(
     userId INT NOT NULL,
     websiteId INT NOT NULL,
     PRIMARY KEY (userId, websiteId),
     FOREIGN KEY (userId) REFERENCES "user"(id) ON DELETE CASCADE,
     FOREIGN KEY (websiteId) REFERENCES website(id) ON DELETE CASCADE
);