CREATE TABLE user (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL,
    name VARCHAR(50) NOT NULL
);
CREATE INDEX user_id ON user (id);

CREATE TABLE resource_role (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    resource VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL
);
CREATE INDEX resource_role_id ON resource_role (id);

CREATE TABLE permission (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    resource VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL,
    privilege VARCHAR(10) NOT NULL,
    permission VARCHAR(10) NOT NULL
);
CREATE INDEX permission_id ON permission (id);

CREATE TABLE entry (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    comment TEXT NULL,
    creator_user_id INTEGER NOT NULL,
    created DATETIME NOT NULL
);
CREATE INDEX entry_id ON entry (id);
