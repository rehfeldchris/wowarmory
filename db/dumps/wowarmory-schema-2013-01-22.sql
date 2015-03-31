-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               5.5.29-log - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL version:             7.0.0.4053
-- Date/time:                    2013-02-15 07:23:55
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;

-- Dumping database structure for wowarmory
CREATE DATABASE IF NOT EXISTS `wowarmory` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_bin */;
USE `wowarmory`;


-- Dumping structure for view wowarmory.accountwide_achievements
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `accountwide_achievements` (
	`id` INT(10) NOT NULL DEFAULT '0',
	`title` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`account_wide` TINYINT(255) NOT NULL,
	`faction_id` TINYINT(255) NOT NULL,
	`last_update` DATETIME NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for view wowarmory.accountwide_character_achievements
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `accountwide_character_achievements` (
	`character_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`achievement_id` INT(10) NOT NULL DEFAULT '0',
	`achievement_completed_ts` BIGINT(20) NOT NULL,
	`achievement_title` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`achievement_faction_id` TINYINT(255) NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for view wowarmory.accountwide_denormalized_character_achievements
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `accountwide_denormalized_character_achievements` (
	`id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL COMMENT 'this has a binary collation so that accented characters are considered different, like wow does. unfortunately its also case sensitive, unlike wow. see searchable_name' COLLATE 'utf8_bin',
	`level` INT(10) UNSIGNED NOT NULL,
	`gender` CHAR(6) NOT NULL DEFAULT 'Male' COLLATE 'utf8_bin',
	`last_update` DATETIME NOT NULL,
	`race_id` INT(10) UNSIGNED NOT NULL,
	`race_name` VARCHAR(45) NOT NULL COLLATE 'utf8_bin',
	`class_id` INT(10) UNSIGNED NOT NULL,
	`class_name` VARCHAR(20) NOT NULL COLLATE 'utf8_bin',
	`realm_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`region` CHAR(2) NOT NULL COLLATE 'utf8_bin',
	`realm_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`battlegroup_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`battlegroup_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`guild_id` INT(10) UNSIGNED NULL DEFAULT '0',
	`guild_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'game isnt case sensitive for uniqueness, but accented characters shouldnt compare equal to non accented' COLLATE 'utf8_bin',
	`character_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`achievement_id` INT(10) NOT NULL DEFAULT '0',
	`achievement_title` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`achievement_completed_ts` BIGINT(20) NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for table wowarmory.achievements
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` int(10) NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `account_wide` tinyint(255) NOT NULL,
  `faction_id` tinyint(255) NOT NULL,
  `last_update` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `account_wide` (`account_wide`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for procedure wowarmory.add_character_achievement
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_character_achievement`(IN `character_name_in` VARCHAR(512), IN `realm_name_in` VARCHAR(512), IN `region_in` CHAR(50), IN `achievement_id_in` INT, IN `achievement_unix_timestamp_miliseconds_in` BIGINT)
BEGIN
	declare character_id int;
	
	select c.id
	  into character_id
	  from denormalized_characters c
	 where c.name = character_name_in
	   and c.realm_name = realm_name_in
	   and c.region = region_in;
      
	if character_id is not null then
	   
		insert
      ignore
		  into character_achievements
		   set character_achievements.character_id = character_id
			  , character_achievements.achievement_id = achievement_id_in
			  , character_achievements.achievement_completed_ts = achievement_unix_timestamp_miliseconds_in;
	    
	end if;

END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.add_or_update_arenateam
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_or_update_arenateam`(IN `team_name_in` VARCHAR(255), IN `realm_name_in` VARCHAR(255), IN `teamsize_in` INT, IN `region_in` CHAR(2))
BEGIN
    declare realm_id int;
    
    select r.id
      into realm_id
      from realms r
     where r.name = realm_name_in
       and r.region = region_in;
       
    insert
      into arenateams
           (name, realm_id, teamsize, last_update)
    values (team_name_in, realm_id, teamsize_in, now())
	     on
 duplicate
       key
	 update last_update = now();
    
END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.add_or_update_battlegroup
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_or_update_battlegroup`(IN `name_in` VARCHAR(255), IN `region_in` CHAR(50))
BEGIN
	 insert
	   into battlegroups
		     (name, region, last_verified)
	 values (name_in, region_in, now())
	     on
 duplicate
       key
    update battlegroups.last_verified = now();
END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.add_or_update_character
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_or_update_character`(IN `name_in` varchar(255), IN `realm_name_in` varchar(255), IN `region_name_in` varchar(255), IN `guild_name_in` varchar(255), IN `level_in` int, IN `gender_name_in` char(6), IN `class_id_in` varchar(255), IN `race_id_in` varchar(255), IN `last_modified_api_timestamp_in` BIGINT)
    SQL SECURITY INVOKER
BEGIN
    DECLARE realm_id int;
    DECLARE guild_id int DEFAULT NULL;
    
         
    --  get realm_id
    select r.id
      into realm_id
      from realms r
     where r.name = realm_name_in
       and r.region = region_name_in;
     

     --  add guild, might not exist
     call add_or_update_guild(guild_name_in, realm_name_in, region_name_in);
          
     -- get guild_id
     select g.id 
       into guild_id
       from guilds g
      where g.name = guild_name_in
        and g.realm_id = realm_id;

     
     --  add the character
     insert
       into characters
        set characters.name = name_in
          , characters.realm_id = realm_id
          , characters.level = level_in
          , characters.race_id = race_id_in
          , characters.class_id = class_id_in
          , characters.guild_id = guild_id
          , characters.last_update = now()
          , characters.searchable_name = name_in
          , characters.last_modified_api_ts = last_modified_api_timestamp_in
         on
  duplicate
        key
     update 
            characters.level = level_in
          , characters.race_id = race_id_in
          , characters.class_id = class_id_in
          , characters.guild_id = guild_id
          , characters.last_modified_api_ts = last_modified_api_timestamp_in
          , characters.last_update = now();
END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.add_or_update_character_arenateam
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_or_update_character_arenateam`(IN `character_name_in` VARCHAR(255), IN `realm_name_in` VARCHAR(255), IN `region_in` CHAR(50), IN `arenateam_name_in` VARCHAR(512), IN `match_size_in` TINYINT)
BEGIN
	declare character_id int;
	declare arenateam_id int;
	
	select c.id
	  into character_id
	  from denormalized_characters c
	 where c.name = character_name_in
	   and c.realm_name = realm_name_in
	   and c.region = region_in;
	   
	select a.id
	  into arenateam_id
	  from denormalized_arenateams a
	 where a.name = arenateam_name_in
	   and a.realm_name = realm_name_in
	   and a.region = region_in;
	   
	if character_id is not null then
	   
		insert
		  into character_arenateams
		   set character_arenateams.character_id = character_id
			  , character_arenateams.match_size = match_size_in
			  , character_arenateams.arenateam_id = arenateam_id
			on
	 duplicate
	       key
	    update character_arenateams.arenateam_id = arenateam_id;
	    
	end if;
END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.add_or_update_guild
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_or_update_guild`(IN `guild_name_in` varchar(255), IN `realm_name_in` varchar(255), IN `region_in` varchar(255))
BEGIN
    declare realm_id int;
    
    IF guild_name_in IS NOT NULL and guild_name_in <> '' THEN
    
	    select realms.id
	      into realm_id
	      from realms
	     where realms.name = realm_name_in
	       and realms.region = region_in;
	       
	       
	    insert
	      into guilds
	       set guilds.name = guild_name_in
	         , guilds.realm_id = realm_id
	         , guilds.last_update = now()
	         on
	  duplicate
	        key
	     update guilds.last_update = now();
     
     END IF;
      
END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.add_or_update_realm
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_or_update_realm`(IN `name_in` VARCHAR(255), IN `battlegroup_name_in` VARCHAR(255), IN `region_in` CHAR(2), IN `play_type_in` CHAR(10))
BEGIN
    declare battlegroup_id int;
    
    insert
	 ignore
	   into battlegroups
	        (name, region)
	 values (battlegroup_name_in, region_in);
	 
	 select b.id
	   into battlegroup_id
	   from battlegroups b
	  where b.name = battlegroup_name_in
	    and b.region = region_in;
	    
	 insert
	   into realms
		     (name, region, play_type, battlegroup_id)
	 values (name_in, region_in, play_type_in, battlegroup_id)
	     on
 duplicate
       key
    update realms.play_type = play_type
         , realms.battlegroup_id = battlegroup_id;
    
END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.add_url_to_crawl_queue
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_url_to_crawl_queue`(IN `url_in` VARCHAR(512), IN `additional_data_in` BLOB, IN `type_in` VARCHAR(255))
BEGIN
	insert
	ignore
	  into urls_to_fetch
	   set url = url_in
	     , created_at = now()
	     , additional_data = additional_data_in
	     , type = type_in;
END//
DELIMITER ;


-- Dumping structure for function wowarmory.apiurl
DELIMITER //
CREATE DEFINER=`root`@`localhost` FUNCTION `apiurl`(`type` CHAR(50), `name` VARCHAR(512), `realm` VARCHAR(512), `region` CHAR(50)) RETURNS varchar(4096) CHARSET utf8 COLLATE utf8_bin
    DETERMINISTIC
BEGIN
    if type = 'character' then
      return concat('http://', region, '.battle.net/api/wow/', type, '/', urlencode(realm), '/', urlencode(name), '?fields=guild,pvp,achievements,titles');
    end if;
    if type = 'guild' then
      return concat('http://', region, '.battle.net/api/wow/', type, '/', urlencode(realm), '/', urlencode(name), '?fields=members');
    end if;
     if type = 'arena' then
      return concat('http://', region, '.battle.net/api/wow/', type, '/', urlencode(realm), '/', urlencode(name), '?fields=members');
    end if;
END//
DELIMITER ;


-- Dumping structure for table wowarmory.arenateams
CREATE TABLE IF NOT EXISTS `arenateams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `realm_id` int(10) unsigned NOT NULL,
  `teamsize` tinyint(3) unsigned NOT NULL,
  `last_update` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_realm_id_match_size_UNIQUE` (`name`,`realm_id`,`teamsize`),
  KEY `fk_realms_id5` (`realm_id`),
  CONSTRAINT `fk_realms_id5` FOREIGN KEY (`realm_id`) REFERENCES `realms` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.battlegroups
CREATE TABLE IF NOT EXISTS `battlegroups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `region` char(2) COLLATE utf8_bin NOT NULL,
  `last_verified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`,`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.characters
CREATE TABLE IF NOT EXISTS `characters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL COMMENT 'this has a binary collation so that accented characters are considered different, like wow does. unfortunately its also case sensitive, unlike wow. see searchable_name',
  `realm_id` int(10) unsigned NOT NULL,
  `level` int(10) unsigned NOT NULL,
  `race_id` int(10) unsigned NOT NULL,
  `guild_id` int(10) unsigned DEFAULT NULL,
  `gender` char(6) COLLATE utf8_bin NOT NULL DEFAULT 'Male',
  `class_id` int(10) unsigned NOT NULL,
  `last_update` datetime NOT NULL,
  `searchable_name` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT 'uses case insensitve collation, and some accented characters should be equal. good for searching, but cant establish proper uniqueness this way, so we maintain the name column',
  `last_modified_api_ts` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_realm` (`name`,`realm_id`),
  KEY `fk_realm_id` (`realm_id`),
  KEY `searchable_name` (`searchable_name`),
  KEY `fk_guild_id` (`guild_id`),
  KEY `fk_classes_id` (`class_id`),
  KEY `fk_races_id` (`race_id`),
  CONSTRAINT `fk_classes_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_guild_id` FOREIGN KEY (`guild_id`) REFERENCES `guilds` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_races_id` FOREIGN KEY (`race_id`) REFERENCES `races` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_realm_id` FOREIGN KEY (`realm_id`) REFERENCES `realms` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.characters_to_crawl
CREATE TABLE IF NOT EXISTS `characters_to_crawl` (
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `realm` varchar(45) COLLATE utf8_bin NOT NULL,
  `region` char(2) COLLATE utf8_bin NOT NULL,
  `created_at` datetime NOT NULL,
  `last_attempt` datetime DEFAULT NULL,
  `html` mediumtext COLLATE utf8_bin,
  PRIMARY KEY (`name`,`realm`,`region`),
  KEY `idx_last_attempt` (`last_attempt`),
  KEY `idx_html_null` (`html`(1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='obsolete. we use urls_to_fetch + new scripts';

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.character_achievements
CREATE TABLE IF NOT EXISTS `character_achievements` (
  `character_id` int(10) unsigned NOT NULL DEFAULT '0',
  `achievement_id` int(10) NOT NULL DEFAULT '0',
  `achievement_completed_ts` bigint(20) NOT NULL,
  PRIMARY KEY (`character_id`,`achievement_id`),
  UNIQUE KEY `achievement_completed_achievement_id_character_id` (`achievement_completed_ts`,`achievement_id`,`character_id`),
  KEY `fk_achievement_id` (`achievement_id`),
  CONSTRAINT `fk_achievement_id` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`),
  CONSTRAINT `fk_character_id` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.character_arenateams
CREATE TABLE IF NOT EXISTS `character_arenateams` (
  `character_id` int(10) unsigned NOT NULL,
  `match_size` tinyint(10) unsigned NOT NULL,
  `arenateam_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`character_id`,`match_size`,`arenateam_id`),
  KEY `FK_character_arenateams_arenateams` (`arenateam_id`),
  CONSTRAINT `FK_character_arenateams_arenateams` FOREIGN KEY (`arenateam_id`) REFERENCES `arenateams` (`id`),
  CONSTRAINT `FK_character_arenateams_characters` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.character_crawl_history
CREATE TABLE IF NOT EXISTS `character_crawl_history` (
  `character_id` int(10) unsigned NOT NULL,
  `put_into_queue` datetime NOT NULL,
  PRIMARY KEY (`character_id`,`put_into_queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.character_titles
CREATE TABLE IF NOT EXISTS `character_titles` (
  `character_id` int(10) unsigned NOT NULL,
  `title_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`character_id`,`title_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.classes
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(20) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`,`name`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for view wowarmory.common
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `common` (
	`cnt` BIGINT(21) NOT NULL DEFAULT '0',
	`group_concat(name order by name)` TEXT NULL DEFAULT NULL COLLATE 'utf8_bin',
	`achievement_completed_ts` BIGINT(20) NOT NULL,
	`achievement_title` VARCHAR(255) NOT NULL COLLATE 'utf8_bin'
) ENGINE=MyISAM;


-- Dumping structure for view wowarmory.denormalized_arenateams
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `denormalized_arenateams` (
	`id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`teamsize` TINYINT(3) UNSIGNED NOT NULL,
	`realm_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`realm_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`region` CHAR(2) NOT NULL COLLATE 'utf8_bin',
	`locale` VARCHAR(25) NULL DEFAULT NULL COLLATE 'utf8_bin',
	`play_type` CHAR(10) NOT NULL COLLATE 'utf8_bin',
	`battlegroup_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`battlegroup_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin'
) ENGINE=MyISAM;


-- Dumping structure for view wowarmory.denormalized_characters
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `denormalized_characters` (
	`id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL COMMENT 'this has a binary collation so that accented characters are considered different, like wow does. unfortunately its also case sensitive, unlike wow. see searchable_name' COLLATE 'utf8_bin',
	`level` INT(10) UNSIGNED NOT NULL,
	`gender` CHAR(6) NOT NULL DEFAULT 'Male' COLLATE 'utf8_bin',
	`last_update` DATETIME NOT NULL,
	`last_modified_api_ts` BIGINT(20) NULL DEFAULT NULL,
	`race_id` INT(10) UNSIGNED NOT NULL,
	`race_name` VARCHAR(45) NOT NULL COLLATE 'utf8_bin',
	`class_id` INT(10) UNSIGNED NOT NULL,
	`class_name` VARCHAR(20) NOT NULL COLLATE 'utf8_bin',
	`realm_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`region` CHAR(2) NOT NULL COLLATE 'utf8_bin',
	`realm_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`battlegroup_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`battlegroup_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`guild_id` INT(10) UNSIGNED NULL DEFAULT '0',
	`guild_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'game isnt case sensitive for uniqueness, but accented characters shouldnt compare equal to non accented' COLLATE 'utf8_bin'
) ENGINE=MyISAM;


-- Dumping structure for view wowarmory.denormalized_guilds
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `denormalized_guilds` (
	`id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL COMMENT 'game isnt case sensitive for uniqueness, but accented characters shouldnt compare equal to non accented' COLLATE 'utf8_bin',
	`last_update` DATETIME NULL DEFAULT NULL,
	`realm_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`realm_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`realm_region` CHAR(2) NOT NULL COLLATE 'utf8_bin',
	`battlegroup_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`battlegroup_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin'
) ENGINE=MyISAM;


-- Dumping structure for view wowarmory.denormalized_races
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `denormalized_races` (
	`id` INT(10) UNSIGNED NOT NULL,
	`name` VARCHAR(45) NOT NULL COLLATE 'utf8_bin',
	`faction_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`faction_name` VARCHAR(45) NOT NULL COLLATE 'utf8_bin'
) ENGINE=MyISAM;


-- Dumping structure for view wowarmory.denormalized_realms
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `denormalized_realms` (
	`id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`region` CHAR(2) NOT NULL COLLATE 'utf8_bin',
	`locale` VARCHAR(25) NULL DEFAULT NULL COLLATE 'utf8_bin',
	`play_type` CHAR(10) NOT NULL COLLATE 'utf8_bin',
	`battlegroup_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`battlegroup_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin'
) ENGINE=MyISAM;


-- Dumping structure for table wowarmory.factions
CREATE TABLE IF NOT EXISTS `factions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for procedure wowarmory.get_rand
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rand`(out the_number float)
begin
select rand() into the_number;
end//
DELIMITER ;


-- Dumping structure for table wowarmory.guilds
CREATE TABLE IF NOT EXISTS `guilds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL COMMENT 'game isnt case sensitive for uniqueness, but accented characters shouldnt compare equal to non accented',
  `realm_id` int(10) unsigned NOT NULL,
  `last_update` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_realm_id_unique` (`name`,`realm_id`),
  KEY `idx_last_update` (`last_update`),
  KEY `fk_realm_id2` (`realm_id`),
  CONSTRAINT `fk_realm_id2` FOREIGN KEY (`realm_id`) REFERENCES `realms` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.guilds_to_crawl
CREATE TABLE IF NOT EXISTS `guilds_to_crawl` (
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `realm` varchar(255) COLLATE utf8_bin NOT NULL,
  `region` char(2) COLLATE utf8_bin NOT NULL,
  `created_at` datetime NOT NULL,
  `last_attempt` datetime DEFAULT NULL,
  `html` mediumtext COLLATE utf8_bin,
  PRIMARY KEY (`name`,`realm`,`region`),
  KEY `idx_last_attempt` (`last_attempt`),
  KEY `idx_html_null` (`html`(1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='obsolete. we use urls_to_fetch + new scripts';

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.guild_crawl_history
CREATE TABLE IF NOT EXISTS `guild_crawl_history` (
  `guild_id` int(10) unsigned NOT NULL,
  `put_into_queue` datetime NOT NULL,
  PRIMARY KEY (`guild_id`,`put_into_queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for view wowarmory.me
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `me` (
	`id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL COMMENT 'this has a binary collation so that accented characters are considered different, like wow does. unfortunately its also case sensitive, unlike wow. see searchable_name' COLLATE 'utf8_bin',
	`level` INT(10) UNSIGNED NOT NULL,
	`gender` CHAR(6) NOT NULL DEFAULT 'Male' COLLATE 'utf8_bin',
	`last_update` DATETIME NOT NULL,
	`race_id` INT(10) UNSIGNED NOT NULL,
	`race_name` VARCHAR(45) NOT NULL COLLATE 'utf8_bin',
	`class_id` INT(10) UNSIGNED NOT NULL,
	`class_name` VARCHAR(20) NOT NULL COLLATE 'utf8_bin',
	`realm_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`region` CHAR(2) NOT NULL COLLATE 'utf8_bin',
	`realm_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`battlegroup_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`battlegroup_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`guild_id` INT(10) UNSIGNED NULL DEFAULT '0',
	`guild_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'game isnt case sensitive for uniqueness, but accented characters shouldnt compare equal to non accented' COLLATE 'utf8_bin',
	`character_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`achievement_id` INT(10) NOT NULL DEFAULT '0',
	`achievement_title` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`achievement_completed_ts` BIGINT(20) NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for procedure wowarmory.p
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `p`()
BEGIN
    select 55;
  END//
DELIMITER ;


-- Dumping structure for table wowarmory.races
CREATE TABLE IF NOT EXISTS `races` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(45) COLLATE utf8_bin NOT NULL,
  `faction_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_factions_id` (`faction_id`),
  CONSTRAINT `fk_factions_id` FOREIGN KEY (`faction_id`) REFERENCES `factions` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for table wowarmory.realms
CREATE TABLE IF NOT EXISTS `realms` (
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `battlegroup_id` int(10) unsigned NOT NULL,
  `region` char(2) COLLATE utf8_bin NOT NULL,
  `locale` varchar(25) COLLATE utf8_bin DEFAULT NULL,
  `play_type` char(10) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_region_UNIQUE` (`name`,`region`),
  KEY `fk_battlegroups_id` (`battlegroup_id`),
  CONSTRAINT `fk_battlegroups_id` FOREIGN KEY (`battlegroup_id`) REFERENCES `battlegroups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for procedure wowarmory.realmstats
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `realmstats`(IN `realm_name_in` VARCHAR(512))
BEGIN
   declare realm_id int;
   
   select id
     into realm_id
     from realms
    where name = realm_name_in
      and region = 'us';
    
   select realm_name_in realm
        , (select count(*) from characters c where c.realm_id = realm_id) num_characters
        , (select count(*) from guilds c where c.realm_id = realm_id) num_guilds
        , (select count(*) from arenateams c where c.realm_id = realm_id) num_arenateams
        
   
   ;
END//
DELIMITER ;


-- Dumping structure for table wowarmory.reprocessed_url_ids
CREATE TABLE IF NOT EXISTS `reprocessed_url_ids` (
  `url_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`url_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for procedure wowarmory.set_character_guild
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `set_character_guild`(IN `character_name_in` VARCHAR(255), IN `realm_name_in` VARCHAR(255), IN `region_in` VARCHAR(255), IN `guild_name_in` VARCHAR(255))
BEGIN
	declare character_id int;
	declare guild_id int;
	
	select c.id
	  into character_id
	  from denormalized_characters c
	 where c.name = character_name_in
	   and c.realm_name = realm_name_in
	   and c.region = region_in;
	   
	select g.id
	  into guild_id
	  from denormalized_guilds g
	 where g.name = guild_name_in
	   and g.realm_name = realm_name_in
	   and g.realm_region = region_in;
	   
    update characters c
       set c.guild_id = guild_id
     where c.id = character_id;

END//
DELIMITER ;


-- Dumping structure for table wowarmory.titles
CREATE TABLE IF NOT EXISTS `titles` (
  `id` int(10) NOT NULL,
  `name` int(10) NOT NULL,
  `account_wide` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='unused for now, i think titles always come from an achievement anyways, so this offers no new info';

-- Data exporting was unselected.


-- Dumping structure for procedure wowarmory.unguild_current_guild_members
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `unguild_current_guild_members`(IN `guild_name_in` VARCHAR(512), IN `realm_name_in` VARCHAR(255), IN `region_in` VARCHAR(50))
BEGIN
	update characters
	   set guild_id = NULL
	 where guild_id = ( select g.id
								 from denormalized_guilds g
							   where g.name = guild_name_in
						        and g.realm_name = realm_name_in
						        and g.realm_region = region_in );
END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.unteam_current_arenateam_members
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `unteam_current_arenateam_members`(IN `team_name_in` VARCHAR(512), IN `realm_name_in` VARCHAR(512), IN `region_in` CHAR(50))
BEGIN
    delete
      from character_arenateams
     where arenateam_id = ( select a.id
                              from denormalized_arenateams a
                             where a.name = team_name_in
                               and a.realm_name = realm_name_in
                               and a.region = region_in );
END//
DELIMITER ;


-- Dumping structure for function wowarmory.urlencode
DELIMITER //
CREATE DEFINER=`root`@`localhost` FUNCTION `urlencode`(`s` VARCHAR(4096)) RETURNS varchar(4096) CHARSET utf8 COLLATE utf8_bin
    DETERMINISTIC
BEGIN
       DECLARE c VARCHAR(4096) DEFAULT '';
       DECLARE pointer INT DEFAULT 1;
       DECLARE s2 VARCHAR(4096) DEFAULT '';

       IF ISNULL(s) THEN
           RETURN NULL;
       ELSE
       SET s2 = '';
       WHILE pointer <= length(s) DO
          SET c = MID(s,pointer,1);
          IF c = ' ' THEN
             SET c = '%20';
          ELSEIF NOT (ASCII(c) BETWEEN 48 AND 57 OR 
                ASCII(c) BETWEEN 65 AND 90 OR 
                ASCII(c) BETWEEN 97 AND 122) THEN
             SET c = concat("%",LPAD(CONV(ASCII(c),10,16),2,0));
          END IF;
          SET s2 = CONCAT(s2,c);
          SET pointer = pointer + 1;
       END while;
       END IF;
       RETURN s2;
END//
DELIMITER ;


-- Dumping structure for procedure wowarmory.urlstats
DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `urlstats`()
BEGIN
   select
      (select count(*) from urls_to_fetch where http_response_code is null) null_code
   ,  (select count(*) from urls_to_fetch where http_response_code is not null and processed = 0) notnull_code_unproc
   ,  (select count(*) from urls_to_fetch where http_response_code = 200 and processed = 0) code_200_unproc
   
   ;
END//
DELIMITER ;


-- Dumping structure for table wowarmory.urls_to_fetch
CREATE TABLE IF NOT EXISTS `urls_to_fetch` (
  `url` varchar(512) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_attempt` datetime NOT NULL,
  `additional_data` blob,
  `url_payload` longblob,
  `http_response_code` int(11) DEFAULT NULL,
  `type` char(10) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `processed` tinyint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `http_response_code_type_processed` (`http_response_code`,`type`,`processed`),
  KEY `last_attempt_created_at` (`last_attempt`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.


-- Dumping structure for trigger wowarmory.name_maintenence
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,TRADITIONAL,NO_AUTO_CREATE_USER';
DELIMITER //
CREATE TRIGGER `name_maintenence` BEFORE INSERT ON `characters` FOR EACH ROW BEGIN
    SET NEW.searchable_name = NEW.name;
END//
DELIMITER ;
SET SQL_MODE=@OLD_SQL_MODE;


-- Dumping structure for view wowarmory.accountwide_achievements
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `accountwide_achievements`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `accountwide_achievements` AS select * from achievements where account_wide = 1 ;


-- Dumping structure for view wowarmory.accountwide_character_achievements
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `accountwide_character_achievements`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `accountwide_character_achievements` AS select ca.character_id
, ca.achievement_id
,ca.achievement_completed_ts
,awa.title achievement_title
,awa.faction_id achievement_faction_id

  from character_achievements ca
inner
   join accountwide_achievements awa
     on ca.achievement_id = awa.id ;


-- Dumping structure for view wowarmory.accountwide_denormalized_character_achievements
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `accountwide_denormalized_character_achievements`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `accountwide_denormalized_character_achievements` AS select c.*
     , c.id character_id
     , a.achievement_id
     , a.achievement_title
     , a.achievement_completed_ts
  from accountwide_character_achievements a
 inner
  join denormalized_characters c
    on c.id = a.character_id ;


-- Dumping structure for view wowarmory.common
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `common`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `common` AS select 
count(*) cnt
,group_concat(name order by name)
,achievement_completed_ts
, achievement_title
from accountwide_denormalized_character_achievements

group by
achievement_completed_ts, achievement_title
having cnt > 5
order by cnt desc ;


-- Dumping structure for view wowarmory.denormalized_arenateams
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `denormalized_arenateams`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `denormalized_arenateams` AS select `t`.`id` AS `id`,`t`.`name` AS `name`,`t`.`teamsize` AS `teamsize`,`wowarmory`.`realms`.`id` AS `realm_id`,`wowarmory`.`realms`.`name` AS `realm_name`,`wowarmory`.`realms`.`region` AS `region`,`wowarmory`.`realms`.`locale` AS `locale`,`wowarmory`.`realms`.`play_type` AS `play_type`,`wowarmory`.`battlegroups`.`id` AS `battlegroup_id`,`wowarmory`.`battlegroups`.`name` AS `battlegroup_name` from ((`wowarmory`.`arenateams` `t` join `wowarmory`.`realms` on((`t`.`realm_id` = `wowarmory`.`realms`.`id`))) join `wowarmory`.`battlegroups` on((`wowarmory`.`realms`.`battlegroup_id` = `wowarmory`.`battlegroups`.`id`))) ;


-- Dumping structure for view wowarmory.denormalized_characters
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `denormalized_characters`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `denormalized_characters` AS SELECT `c`.`id` AS `id`
,`c`.`name` AS `name`
,`c`.`level` AS `level`
,`c`.`gender` AS `gender`
,`c`.`last_update` AS `last_update`
,`c`.`last_modified_api_ts` AS `last_modified_api_ts`
,`wowarmory`.`races`.`id` AS `race_id`
,`wowarmory`.`races`.`name` AS `race_name`
,`wowarmory`.`classes`.`id` AS `class_id`
,`wowarmory`.`classes`.`name` AS `class_name`
,`wowarmory`.`realms`.`id` AS `realm_id`
, `wowarmory`.`realms`.`region` AS `region`
, `wowarmory`.`realms`.`name` AS `realm_name`
,`wowarmory`.`battlegroups`.`id` AS `battlegroup_id`
,`wowarmory`.`battlegroups`.`name` AS `battlegroup_name`
,`wowarmory`.`guilds`.`id` AS `guild_id`
,`wowarmory`.`guilds`.`name` AS `guild_name`

FROM (((((`wowarmory`.`characters` `c`
JOIN `wowarmory`.`races` ON((`c`.`race_id` = `wowarmory`.`races`.`id`)))
JOIN `wowarmory`.`classes` ON((`c`.`class_id` = `wowarmory`.`classes`.`id`)))
JOIN `wowarmory`.`realms` ON((`c`.`realm_id` = `wowarmory`.`realms`.`id`)))
JOIN `wowarmory`.`battlegroups` ON((`wowarmory`.`realms`.`battlegroup_id` = `wowarmory`.`battlegroups`.`id`)))
LEFT JOIN `wowarmory`.`guilds` ON((`c`.`guild_id` = `wowarmory`.`guilds`.`id`))) ;


-- Dumping structure for view wowarmory.denormalized_guilds
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `denormalized_guilds`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `denormalized_guilds` AS select `g`.`id` AS `id`,`g`.`name` AS `name`,`g`.`last_update` AS `last_update`,`wowarmory`.`realms`.`id` AS `realm_id`,`wowarmory`.`realms`.`name` AS `realm_name`,`wowarmory`.`realms`.`region` AS `realm_region`,`wowarmory`.`battlegroups`.`id` AS `battlegroup_id`,`wowarmory`.`battlegroups`.`name` AS `battlegroup_name` from ((`wowarmory`.`guilds` `g` join `wowarmory`.`realms` on((`g`.`realm_id` = `wowarmory`.`realms`.`id`))) join `wowarmory`.`battlegroups` on((`wowarmory`.`realms`.`battlegroup_id` = `wowarmory`.`battlegroups`.`id`))) ;


-- Dumping structure for view wowarmory.denormalized_races
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `denormalized_races`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `denormalized_races` AS select r.id
     , r.name
     , factions.id faction_id
     , factions.name faction_name
  from races r
inner
  join factions
    on r.faction_id = factions.id ;


-- Dumping structure for view wowarmory.denormalized_realms
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `denormalized_realms`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `denormalized_realms` AS select r.id
     , r.name
     , r.region
     , r.locale
     , r.play_type
     , battlegroups.id battlegroup_id
     , battlegroups.name battlegroup_name
  from realms r
inner
  join battlegroups
    on r.battlegroup_id = battlegroups.id ;


-- Dumping structure for view wowarmory.me
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `me`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `me` AS select *
from accountwide_denormalized_character_achievements
where name in ('Huntarrd', 'Clamcrusher', 'Steamfart', 'Cheesytot', 'Creebag', 'Superburrito')
and realm_name = 'Arthas'
and region = 'us' ;
/*!40014 SET FOREIGN_KEY_CHECKS=1 */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
