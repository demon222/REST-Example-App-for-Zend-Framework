
INSERT INTO user (id, username, name) VALUES (384, 'Dan', 'Dan');
INSERT INTO entry (comment, creator_user_id, created) VALUES ("information wants to be free", 384, DATETIME('NOW'));

INSERT INTO user (id, username, name) VALUES (456, 'Alex', 'Alex');
INSERT INTO entry (id, comment, creator_user_id, created) VALUES (123, "the dead don't talk much", 456, DATETIME('NOW'));

INSERT INTO resource_role (user_id, resource, role) VALUES (456, 'Entry=123', 'owner');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'get', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'put', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'delete', 'allow');

INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'default', 'get', 'deny');

INSERT INTO user (id, username, name) VALUES (111, 'Bob', 'Bob');
INSERT INTO resource_role (user_id, resource, role) VALUES (111, 'Entry', 'admin');

INSERT INTO user (id, username, name) VALUES (789, 'Carl', 'Carl');
INSERT INTO resource_role (user_id, resource, role) VALUES (789, 'Entry=123', 'selected');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'selected', 'get', 'allow');
