-- /*******************************************************
-- ** civicrm_twingle_profile
-- **
-- ** stores twingle profile data v1.4+
-- ********************************************************/

CREATE TABLE IF NOT EXISTS `civicrm_twingle_profile`(
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
     `name`                 varchar(255)        COMMENT 'configuration name, i.e. internal ID',
     `config`               text                COMMENT 'JSON encoded configuration',
     `last_access`          datetime            COMMENT 'timestamp of the last access (through the api)',
     `access_counter`       int unsigned        COMMENT 'number of accesses (through the api)',
     PRIMARY KEY (`id`),
     UNIQUE INDEX (`name`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

