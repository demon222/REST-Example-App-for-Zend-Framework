/* public and view and post entries
Hard Coded:
Entry, default, get, allow
Entry, default, post, allow
*/

/* Dan posts */
INSERT INTO user (id, username, name) VALUES (384, 'Dan', 'Dan');
INSERT INTO entry (comment, creator_user_id, modified) VALUES ("information wants to be free", 384, DATETIME('NOW'));

/* Alex and Ed are members of all entries */
INSERT INTO user (id, username, name) VALUES (456, 'Alex', 'Alex');
INSERT INTO resource_role (user_id, resource, role) VALUES (456, 'Entry', 'member');
INSERT INTO user (id, username, name) VALUES (234, 'Ed', 'Ed');
INSERT INTO resource_role (user_id, resource, role) VALUES (234, 'Entry', 'member');

/* Alex posts private message, because he is a member he is also given ownership over his post */
INSERT INTO entry (id, comment, creator_user_id, modified) VALUES (123, "the dead don't talk much", 456, DATETIME('NOW'));
INSERT INTO resource_role (user_id, resource, role) VALUES (456, 'Entry=123', 'owner');
/* WILL LOOK INTO USING SQL UNION TO ADD FROM PHP ALL/GENERAL PERMISSIONS TO LIST QUERY */
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'get', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'put', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'owner', 'delete', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'default', 'get', 'deny');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'member', 'get', 'allow');

/* Alex selects Carl to be able to see his private entry, even though he is not a member */
INSERT INTO user (id, username, name) VALUES (789, 'Carl', 'Carl');
INSERT INTO resource_role (user_id, resource, role) VALUES (789, 'Entry=123', 'selected');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=123', 'selected', 'get', 'allow');

/* Alex posts public message */
INSERT INTO entry (id, comment, creator_user_id, modified) VALUES (124,"well of course! but why do you hide so much?", 456, DATETIME('NOW'));
INSERT INTO resource_role (user_id, resource, role) VALUES (456, 'Entry=124', 'owner');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=124', 'owner', 'get', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=124', 'owner', 'put', 'allow');
INSERT INTO permission (resource, role, privilege, permission) VALUES ('Entry=124', 'owner', 'delete', 'allow');

/* admins can do anything
Hard Coded:
Entry, admin, get, allow
Entry, admin, put, allow
Entry, admin, delete, allow
Entry, admin, post, allow
*/
/* Bob is admin*/
INSERT INTO user (id, username, name) VALUES (111, 'Bob', 'Bob');
INSERT INTO resource_role (user_id, resource, role) VALUES (111, 'Entry', 'admin');

