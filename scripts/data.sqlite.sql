
INSERT INTO user (id, username) VALUES (384, 'Dan');
INSERT INTO entry (comment, created) VALUES ("information wants to be free", DATETIME('NOW'));

INSERT INTO user (id, username) VALUES (456, 'Alex');
INSERT INTO entry (id, comment, created) VALUES (123, "the dead don't talk much", DATETIME('NOW'));

INSERT INTO role (user_id, resource, role) VALUES (456, 'Entry=123', 'owner');

INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'get', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'put', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'delete', 'allow');

INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'default', 'get', 'deny');

INSERT INTO user (id, username) VALUES (111, 'Bob');
INSERT INTO role (user_id, resource, role) VALUES (111, 'Entry', 'admin');

INSERT INTO user (id, username) VALUES (789, 'Carl');
INSERT INTO role (user_id, resource, role) VALUES (789, 'Entry=123', 'selected');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'selected', 'get', 'allow');
