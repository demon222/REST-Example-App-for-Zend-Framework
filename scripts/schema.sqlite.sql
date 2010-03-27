CREATE TABLE user (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL,
    name VARCHAR(50),
    primary_email_id INTEGER,
    pic VARCAHR(2083)
);

CREATE TABLE email (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    email VARCHAR(254) NOT NULL
);
CREATE INDEX user_id ON user (id);

CREATE TABLE community (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(100) NOT NULL,
    pic VARCHAR(2083)
);
CREATE INDEX community_id ON community (id);

CREATE TABLE discussion (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    community_id INTEGER NOT NULL,
    title VARCHAR(100) NOT NULL,
    comment TEXT
);
CREATE INDEX discussion_id ON discussion (id);

CREATE TABLE entry (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    discussion_id INTEGER NOT NULL,
    comment TEXT,
    creator_user_id INTEGER NOT NULL,
    modified DATETIME NOT NULL
);
CREATE INDEX entry_id ON entry (id);

CREATE TABLE resource_role (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    resource VARCHAR(30) NOT NULL,
    resource_id VARCHAR(20),
    role VARCHAR(20) NOT NULL
);
CREATE INDEX resource_role_id ON resource_role (id);

CREATE TABLE permission (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    resource VARCHAR(30) NOT NULL,
    resource_id VARCHAR(20),
    role VARCHAR(20) NOT NULL,
    privilege VARCHAR(10) NOT NULL,
    permission VARCHAR(10) NOT NULL
);
CREATE INDEX permission_id ON permission (id);
