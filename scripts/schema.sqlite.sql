
CREATE TABLE guestbook_entry (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(32) NOT NULL DEFAULT 'php',
    comment TEXT NULL,
    created DATETIME NOT NULL
);

CREATE INDEX "id" ON "guestbook_entry" ("id");
