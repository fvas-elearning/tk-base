-- --------------------------------------------
-- @version 3.2.0
-- Time to update the user name field to name_first, name_last fields
--
-- @author: Michael Mifsud <info@tropotek.com>
-- --------------------------------------------

-- Create the new fields

alter table user modify password varchar(128) default '' not null after session_id;
alter table user add title varchar(16) default '' not null after username;
alter table user add credentials varchar(255) default '' not null after phone;
alter table user add position varchar(255) default '' not null after credentials;
