-- --------------------------------------------
-- @version 3.0.0
--
-- @author: Michael Mifsud <info@tropotek.com>
-- --------------------------------------------

ALTER TABLE user ADD role_id int DEFAULT 0 NOT NULL;
ALTER TABLE user
  MODIFY COLUMN role_id int NOT NULL DEFAULT 0 AFTER id;


-- --------------------------------------------------------
--
-- Table structure for table `user_role`
--
CREATE TABLE IF NOT EXISTS `user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(64) NOT NULL DEFAULT '',                 -- 'Name with only alpha chars and underscores [a-zA-Z_-]
  `type` varchar(64) NOT NULL DEFAULT '',                 -- [admin|client|staff|student, etc]The system role type to use for templates and homeUrls, etc
  `description` TEXT,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `static` TINYINT(1) NOT NULL DEFAULT 0,                 -- If record is static then no-one can delete or modify it
  `del` TINYINT(1) NOT NULL DEFAULT 0,
  `modified` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB;

INSERT INTO `user_role` (name, type, description, static, modified, created) VALUES
  ('admin', 'admin', 'System administrator role', 1, NOW(), NOW()),
  ('user', 'user', 'Site default user role', 1, NOW(), NOW())
;

UPDATE `user` a, `user_role` b
SET a.`role_id` = b.`id`
WHERE b.`type` = a.`role`;

ALTER TABLE user DROP COLUMN role;

-- --------------------------------------------------------
-- The role permission table
-- Table structure for table `user_permission`
--
CREATE TABLE IF NOT EXISTS `user_permission` (
  `role_id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY `role_id_name` (`role_id`, `name`)
) ENGINE=InnoDB;


INSERT INTO `user_permission` (`role_id`, `name`)
VALUES
   (1, 'type.admin'),
   (1, 'type.user'),
   (2, 'type.user')
;



-- TODO: remove the username, password fields from the user table at another date

-- --------------------------------------------------------
--
-- Table structure for table `user_auth`
--
# CREATE TABLE IF NOT EXISTS `user_auth` (
#   `username` varchar(128) NOT NULL PRIMARY KEY ,
#   `password` varchar(128) NOT NULL DEFAULT ''
# ) ENGINE=InnoDB;
#
# INSERT INTO `user_auth` (`username`, `password`)
#   (
#     SELECT a.username, a.password
#     FROM `user` a
#   );
# alter table user drop column username;
# alter table user drop column password;




-- TODO: this should b added to the site sql only
# TRUNCATE `user`;
# INSERT INTO `user` (`role_id`, `name`, `email`, `username`, `password`, `hash`, `modified`, `created`)
# VALUES
#   (1, 'Administrator', 'admin@example.com', 'admin', MD5('password'), MD5('1admin'), NOW() , NOW()),
#   (2, 'User', 'user@example.com', 'user', MD5('password'), MD5('2user'), NOW() , NOW())
# ;





