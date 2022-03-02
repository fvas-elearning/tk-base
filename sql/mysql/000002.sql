-- --------------------------------------------
-- @version 3.0.0
--
-- @author: Michael Mifsud <info@tropotek.com>
-- --------------------------------------------



ALTER TABLE user ADD session_id varchar(128) DEFAULT '' NULL;
ALTER TABLE user ADD image varchar(255) DEFAULT '' NULL;
ALTER TABLE user ADD phone varchar(32) DEFAULT '' NULL;
ALTER TABLE user ADD uid varchar(128) DEFAULT '' NULL;

ALTER TABLE user
  MODIFY COLUMN uid varchar(128) DEFAULT '' AFTER role_id,
  MODIFY COLUMN phone varchar(32) DEFAULT '' AFTER password,
  MODIFY COLUMN image varchar(255) DEFAULT '' AFTER phone,
  MODIFY COLUMN session_id varchar(128) DEFAULT '' AFTER active,
  MODIFY COLUMN email varchar(255) NOT NULL DEFAULT '' AFTER password,
  MODIFY COLUMN name varchar(255) NOT NULL DEFAULT '' AFTER password,
  MODIFY COLUMN last_login timestamp NULL AFTER active;



