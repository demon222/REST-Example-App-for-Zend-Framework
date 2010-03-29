/* public and view and post entries
Hard Coded:
Entry, default, get, allow
Entry, default, post, allow
*/

/* initial Community and Discussion */
INSERT INTO community (id, title) VALUES (420, 'Something To Talk About');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (456, 'Community', 420, 'member');
INSERT INTO discussion (id, community_id, title) VALUES (361, 420, 'Average Internet Discourse');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (456, 'Discussion', 361, 'member');

/* Dan posts */
INSERT INTO user (id, username, primary_email_id) VALUES (384, 'Dan', 384);
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (384, 'User', 384, 'owner');
INSERT INTO email (id, user_id, email) VALUES (384, 384, 'dvalentiate+REST_Dan@gmail.com');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (384, 'Email', 384, 'owner');
INSERT INTO entry (id, discussion_id, comment, creator_user_id, modified) VALUES (823, 361, "information wants to be free", 384, "2010-02-10 15:26:32");

/* Alex and Ed are members of all entries */
INSERT INTO user (id, username, primary_email_id) VALUES (456, 'Alex', 456);
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (456, 'User', 456, 'owner');
INSERT INTO email (id, user_id, email) VALUES (456, 456, 'dvalentiate+REST_Alex@gmail.com');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (456, 'Email', 456, 'owner');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (456, 'Entry', NULL, 'member');

INSERT INTO user (id, username, primary_email_id) VALUES (234, 'Ed', 234);
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (234, 'User', 234, 'owner');
INSERT INTO email (id, user_id, email) VALUES (234, 234, 'dvalentiate+REST_Ed@gmail.com');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (234, 'Email', 234, 'owner');
INSERT INTO email (id, user_id, email) VALUES (235, 234, 'dvalentiate+REST_Ed_Other@gmail.com');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (234, 'Email', '235', 'owner');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (234, 'Entry', NULL, 'member');

/* Alex posts private message, because he is a member he is also given ownership over his post */
INSERT INTO entry (id, discussion_id, comment, creator_user_id, modified) VALUES (123, 361, "the dead don't talk much", 456, "2010-02-10 18:14:02");
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (456, 'Entry', '123', 'owner');
/* WILL LOOK INTO USING SQL UNION TO ADD FROM PHP ALL/GENERAL PERMISSIONS TO LIST QUERY */
INSERT INTO permission (resource, resource_id, role, privilege, permission) VALUES ('Entry', '123', 'default', 'get', 'deny');
INSERT INTO permission (resource, resource_id, role, privilege, permission) VALUES ('Entry', '123', 'member', 'get', 'allow');

/* Alex selects Carl to be able to see his private entry, even though he is not a member */
INSERT INTO user (id, username) VALUES (789, 'Carl');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (789, 'User', '789', 'owner');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (789, 'Entry', '123', 'selected');
INSERT INTO permission (resource, resource_id, role, privilege, permission) VALUES ('Entry', '123', 'selected', 'get', 'allow');

/* Alex posts public message */
INSERT INTO entry (id, discussion_id, comment, creator_user_id, modified) VALUES (124, 361, "well of course! but why do you hide so much?", 456, "2010-02-10 18:20:56");
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (456, 'Entry', '124', 'owner');

/* Ed posts public message */
INSERT INTO entry (id, discussion_id, comment, creator_user_id, modified) VALUES (125, 361, "Haha, that is funny coming from you!", 234, "2010-02-11 11:40:05");
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (234, 'Entry', '125', 'owner');

/* Carl posts public message */
INSERT INTO entry (id, discussion_id, comment, creator_user_id, modified) VALUES (629, 361, "He's got a point there Alex", 789, "2010-02-11 13:04:23");


/* admins can do anything
Hard Coded:
Entry, admin, get, allow
Entry, admin, put, allow
Entry, admin, delete, allow
Entry, admin, post, allow
*/
/* Bob is admin*/
INSERT INTO user (id, username) VALUES (111, 'Bob');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (111, 'User', '111', 'owner');
INSERT INTO resource_role (user_id, resource, resource_id, role) VALUES (111, 'Entry', NULL, 'admin');
