-- --------------------------------------------
-- @version 3.0.0
--
-- @author: Michael Mifsud <info@tropotek.com>
-- --------------------------------------------


CREATE TABLE IF NOT EXISTS `user`
(
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(128) NOT NULL DEFAULT '',
    `email` varchar(128) NOT NULL DEFAULT '',
    `username` varchar(64) NOT NULL DEFAULT '',
    `password` varchar(64) NOT NULL DEFAULT '',
    `role` varchar(32) NOT NULL DEFAULT '',   -- deprecated
    `notes` TEXT,
    `last_login` TIMESTAMP,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `hash` varchar(64) NOT NULL DEFAULT '',
    `del` TINYINT(1) NOT NULL DEFAULT 0,
    `modified` DATETIME NOT NULL,
    `created` DATETIME NOT NULL,
    KEY `username` (`username`),
    KEY `email` (`email`)
) ENGINE=InnoDB;






