/*
 Navicat MySQL Data Transfer

 Source Server         : sydey
 Source Server Version : 50509
 Source Host           : 192.168.168.60
 Source Database       : blocks

 Target Server Version : 50509
 File Encoding         : utf-8

 Date: 12/21/2011 09:47:48 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `blx_assetblocks`
-- ----------------------------
DROP TABLE IF EXISTS `blx_assetblocks`;
CREATE TABLE `blx_assetblocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `type` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `handle` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `sort_order` int(11) unsigned NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `handle_unique` (`handle`) USING BTREE,
  KEY `assetblocks_assets_fk` (`asset_id`) USING BTREE,
  CONSTRAINT `assetblocks_assets_fk` FOREIGN KEY (`asset_id`) REFERENCES `blx_assets` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_assetblocks` BEFORE INSERT ON `blx_assetblocks` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_assetblocks` BEFORE UPDATE ON `blx_assetblocks` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_assetblocksettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_assetblocksettings`;
CREATE TABLE `blx_assetblocksettings` (
  `asset_block_id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`asset_block_id`,`key`),
  UNIQUE KEY `asset_block_id_key_unique` (`asset_block_id`,`key`) USING BTREE,
  KEY `asset_block_id_index` (`asset_block_id`) USING BTREE,
  CONSTRAINT `assetblocksettings_assetsblocks_fk` FOREIGN KEY (`asset_block_id`) REFERENCES `blx_assetblocks` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_assetblocksettings` BEFORE INSERT ON `blx_assetblocksettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_assetblocksettings` BEFORE UPDATE ON `blx_assetblocksettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_assets`
-- ----------------------------
DROP TABLE IF EXISTS `blx_assets`;
CREATE TABLE `blx_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `upload_folder_id` int(11) NOT NULL,
  `path` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  KEY `upload_folder_id_index` (`upload_folder_id`) USING BTREE,
  CONSTRAINT `assets_uploadfolders_fk` FOREIGN KEY (`upload_folder_id`) REFERENCES `blx_uploadfolders` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_assets` BEFORE INSERT ON `blx_assets` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_assets` BEFORE UPDATE ON `blx_assets` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_entries`
-- ----------------------------
DROP TABLE IF EXISTS `blx_entries`;
CREATE TABLE `blx_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `section_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `slug` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `full_uri` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_date` int(11) DEFAULT NULL,
  `expiry_date` int(11) DEFAULT NULL,
  `sort_order` int(11) unsigned DEFAULT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `archived` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `section_id_parent_id_sort_order_unique` (`section_id`,`parent_id`,`sort_order`) USING BTREE,
  KEY `entries_entries_fk` (`parent_id`) USING BTREE,
  KEY `entries_sections_fk` (`section_id`),
  KEY `entries_users_fk` (`author_id`) USING BTREE,
  CONSTRAINT `entries_users_fk` FOREIGN KEY (`author_id`) REFERENCES `blx_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `entries_entries_fk` FOREIGN KEY (`parent_id`) REFERENCES `blx_entries` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `entries_sections_fk` FOREIGN KEY (`section_id`) REFERENCES `blx_sections` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_entries` BEFORE INSERT ON `blx_entries` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_entries` BEFORE UPDATE ON `blx_entries` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_entryblocks`
-- ----------------------------
DROP TABLE IF EXISTS `blx_entryblocks`;
CREATE TABLE `blx_entryblocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_id` int(11) NOT NULL,
  `handle` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `instructions` text COLLATE utf8_unicode_ci,
  `required` tinyint(1) unsigned DEFAULT '0',
  `sort_order` int(11) unsigned NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `handle_section_unique` (`handle`,`section_id`),
  KEY `entryblocks_entrysections_fk` (`section_id`) USING BTREE,
  CONSTRAINT `entryblocks_entrysections_fk` FOREIGN KEY (`section_id`) REFERENCES `blx_sections` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_entryblocks` BEFORE INSERT ON `blx_entryblocks` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_entryblocks` BEFORE UPDATE ON `blx_entryblocks` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_entryblocksettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_entryblocksettings`;
CREATE TABLE `blx_entryblocksettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `key_unique` (`key`,`block_id`) USING BTREE,
  KEY `entryblocksettings_entryblocks_fk` (`block_id`) USING BTREE,
  CONSTRAINT `entryblocksettings_entryblocks_fk` FOREIGN KEY (`block_id`) REFERENCES `blx_entryblocks` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_entryblocksettings` BEFORE INSERT ON `blx_entryblocksettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_entryblocksettings` BEFORE UPDATE ON `blx_entryblocksettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_entrytitles`
-- ----------------------------
DROP TABLE IF EXISTS `blx_entrytitles`;
CREATE TABLE `blx_entrytitles` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_code` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`entry_id`,`language_code`),
  UNIQUE KEY `entry_id_language_code_unique` (`entry_id`,`language_code`) USING BTREE,
  KEY `entrytitles_entries_fk` (`entry_id`) USING BTREE,
  KEY `entrytitles_languages_fk` (`language_code`),
  CONSTRAINT `entrytitles_entries_fk` FOREIGN KEY (`entry_id`) REFERENCES `blx_entries` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_entrytitles` BEFORE INSERT ON `blx_entrytitles` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_entrytitles` BEFORE UPDATE ON `blx_entrytitles` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_entryversions`
-- ----------------------------
DROP TABLE IF EXISTS `blx_entryversions`;
CREATE TABLE `blx_entryversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `num` int(11) unsigned NOT NULL,
  `label` text COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `draft` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `entry_id_num_unique` (`entry_id`,`num`) USING BTREE,
  UNIQUE KEY `entry_id_active_unique` (`entry_id`,`active`) USING BTREE,
  KEY `entryversions_entry_fk` (`entry_id`) USING BTREE,
  CONSTRAINT `entryversions_entries_fk` FOREIGN KEY (`entry_id`) REFERENCES `blx_entries` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_entryversions` BEFORE INSERT ON `blx_entryversions` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_entryversions` BEFORE UPDATE ON `blx_entryversions` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_groups`
-- ----------------------------
DROP TABLE IF EXISTS `blx_groups`;
CREATE TABLE `blx_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_groups` BEFORE INSERT ON `blx_groups` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_groups` BEFORE UPDATE ON `blx_groups` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_info`
-- ----------------------------
DROP TABLE IF EXISTS `blx_info`;
CREATE TABLE `blx_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `edition` enum('Pro','Standard','Personal') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Standard',
  `version` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `build` int(11) unsigned NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_info` BEFORE INSERT ON `blx_info` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_info` BEFORE UPDATE ON `blx_info` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_languages`
-- ----------------------------
DROP TABLE IF EXISTS `blx_languages`;
CREATE TABLE `blx_languages` (
  `language_code` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`language_code`),
  UNIQUE KEY `language_code_unique` (`language_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_languages` BEFORE INSERT ON `blx_languages` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_languages` BEFORE UPDATE ON `blx_languages` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_licensekeys`
-- ----------------------------
DROP TABLE IF EXISTS `blx_licensekeys`;
CREATE TABLE `blx_licensekeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `key_unique` (`key`) USING BTREE,
  KEY `key` (`key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_licensekeys` BEFORE INSERT ON `blx_licensekeys` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_licensekeys` BEFORE UPDATE ON `blx_licensekeys` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_migrations`
-- ----------------------------
DROP TABLE IF EXISTS `blx_migrations`;
CREATE TABLE `blx_migrations` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `apply_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `blx_plugins`
-- ----------------------------
DROP TABLE IF EXISTS `blx_plugins`;
CREATE TABLE `blx_plugins` (
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`name`),
  UNIQUE KEY `name_unique` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_plugins` BEFORE INSERT ON `blx_plugins` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_plugins` BEFORE UPDATE ON `blx_plugins` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_pluginsettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_pluginsettings`;
CREATE TABLE `blx_pluginsettings` (
  `plugin_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`plugin_name`,`key`),
  UNIQUE KEY `plugin_name_key_unique` (`plugin_name`,`key`) USING BTREE,
  KEY `pluginsettings_plugins_fk` (`plugin_name`) USING BTREE,
  CONSTRAINT `pluginsettings_plugins_fk` FOREIGN KEY (`plugin_name`) REFERENCES `blx_plugins` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_pluginsettings` BEFORE INSERT ON `blx_pluginsettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_pluginsettings` BEFORE UPDATE ON `blx_pluginsettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_routes`
-- ----------------------------
DROP TABLE IF EXISTS `blx_routes`;
CREATE TABLE `blx_routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `route` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `template` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `sort_order` int(11) unsigned NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  KEY `routes_sites_fk` (`site_id`) USING BTREE,
  CONSTRAINT `routes_sites_fk` FOREIGN KEY (`site_id`) REFERENCES `blx_sites` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_routes` BEFORE INSERT ON `blx_routes` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_routes` BEFORE UPDATE ON `blx_routes` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_sections`
-- ----------------------------
DROP TABLE IF EXISTS `blx_sections`;
CREATE TABLE `blx_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `site_id` int(11) NOT NULL,
  `handle` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `url_format` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `max_entries` int(11) unsigned DEFAULT NULL,
  `template` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sortable` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `handle_unique` (`handle`) USING BTREE,
  KEY `sections_sections_fk` (`parent_id`) USING BTREE,
  KEY `sections_sites_fk` (`site_id`) USING BTREE,
  CONSTRAINT `sections_sites_fk` FOREIGN KEY (`site_id`) REFERENCES `blx_sites` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `sections_sections_fk` FOREIGN KEY (`parent_id`) REFERENCES `blx_sections` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_sections` BEFORE INSERT ON `blx_sections` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_sections` BEFORE UPDATE ON `blx_sections` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_siteblocks`
-- ----------------------------
DROP TABLE IF EXISTS `blx_siteblocks`;
CREATE TABLE `blx_siteblocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `handle` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `instructions` text COLLATE utf8_unicode_ci,
  `sort_order` int(11) unsigned NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `handle_unique` (`handle`) USING BTREE,
  KEY `siteblocks_site_fk` (`site_id`) USING BTREE,
  CONSTRAINT `siteblocks_site_fk` FOREIGN KEY (`site_id`) REFERENCES `blx_sites` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_siteblocks` BEFORE INSERT ON `blx_siteblocks` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_siteblocks` BEFORE UPDATE ON `blx_siteblocks` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_siteblocksettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_siteblocksettings`;
CREATE TABLE `blx_siteblocksettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `key_block_id_unique` (`key`,`block_id`) USING BTREE,
  KEY `siteblocksettings_siteblocks_fk` (`block_id`) USING BTREE,
  CONSTRAINT `siteblocksettings_siteblocks_fk` FOREIGN KEY (`block_id`) REFERENCES `blx_siteblocks` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_siteblocksettings` BEFORE INSERT ON `blx_siteblocksettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_siteblocksettings` BEFORE UPDATE ON `blx_siteblocksettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_sites`
-- ----------------------------
DROP TABLE IF EXISTS `blx_sites`;
CREATE TABLE `blx_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `handle` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `handle_unique` (`handle`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_sites` BEFORE INSERT ON `blx_sites` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_sites` BEFORE UPDATE ON `blx_sites` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_sitesettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_sitesettings`;
CREATE TABLE `blx_sitesettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `key_site_id_unique` (`key`,`site_id`) USING BTREE,
  KEY `sitesettings_sites_fk` (`site_id`) USING BTREE,
  CONSTRAINT `sitesettings_sites_fk` FOREIGN KEY (`site_id`) REFERENCES `blx_sites` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_sitesettings` BEFORE INSERT ON `blx_sitesettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_sitesettings` BEFORE UPDATE ON `blx_sitesettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_systemsettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_systemsettings`;
CREATE TABLE `blx_systemsettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `key_unique` (`key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_systemsettings` BEFORE INSERT ON `blx_systemsettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_systemsettings` BEFORE UPDATE ON `blx_systemsettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_uploadfolders`
-- ----------------------------
DROP TABLE IF EXISTS `blx_uploadfolders`;
CREATE TABLE `blx_uploadfolders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `relative_path` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `include_subfolders` tinyint(1) unsigned DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `site_name_unique` (`site_id`,`name`),
  KEY `site_id_index` (`site_id`) USING BTREE,
  CONSTRAINT `uploadfolders_sites_fk` FOREIGN KEY (`site_id`) REFERENCES `blx_sites` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_uploadfolders` BEFORE INSERT ON `blx_uploadfolders` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_uploadfolders` BEFORE UPDATE ON `blx_uploadfolders` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_userblocks`
-- ----------------------------
DROP TABLE IF EXISTS `blx_userblocks`;
CREATE TABLE `blx_userblocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `handle` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `instructions` text COLLATE utf8_unicode_ci,
  `sort_order` int(11) unsigned NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `handle_unique` (`handle`) USING BTREE,
  KEY `userblocks_users_fk` (`user_id`) USING BTREE,
  CONSTRAINT `userblocks_users_fk` FOREIGN KEY (`user_id`) REFERENCES `blx_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_userblocks` BEFORE INSERT ON `blx_userblocks` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_userblocks` BEFORE UPDATE ON `blx_userblocks` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_userblocksettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_userblocksettings`;
CREATE TABLE `blx_userblocksettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `key_block_id_unique` (`key`,`block_id`) USING BTREE,
  KEY `userblocksettings_userblocks_fk` (`block_id`) USING BTREE,
  CONSTRAINT `userblocksettings_userblocks_fk` FOREIGN KEY (`block_id`) REFERENCES `blx_userblocks` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_userblocksettings` BEFORE INSERT ON `blx_userblocksettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_userblocksettings` BEFORE UPDATE ON `blx_userblocksettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_usergrouppermissions`
-- ----------------------------
DROP TABLE IF EXISTS `blx_usergrouppermissions`;
CREATE TABLE `blx_usergrouppermissions` (
  `user_group_id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` tinyint(1) unsigned DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`user_group_id`,`key`),
  UNIQUE KEY `user_group_id_key_unique` (`key`,`user_group_id`) USING BTREE,
  KEY `usergrouppermissions_usergroups_fk` (`user_group_id`) USING BTREE,
  CONSTRAINT `usergrouppermissions_usergroups_fk` FOREIGN KEY (`user_group_id`) REFERENCES `blx_usergroups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_usergrouppermissions` BEFORE INSERT ON `blx_usergrouppermissions` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_usergrouppermissions` BEFORE UPDATE ON `blx_usergrouppermissions` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_usergroups`
-- ----------------------------
DROP TABLE IF EXISTS `blx_usergroups`;
CREATE TABLE `blx_usergroups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `group_id_user_id_site_id_unique` (`user_id`,`group_id`,`site_id`),
  KEY `usergroups_users_fk` (`user_id`) USING BTREE,
  KEY `usergroups_groups_fk` (`group_id`) USING BTREE,
  KEY `usergroups_sites_fk` (`site_id`),
  CONSTRAINT `usergroups_sites_fk` FOREIGN KEY (`site_id`) REFERENCES `blx_sites` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `usergroups_groups_fk` FOREIGN KEY (`group_id`) REFERENCES `blx_groups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `usergroups_users_fk` FOREIGN KEY (`user_id`) REFERENCES `blx_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_usergroups` BEFORE INSERT ON `blx_usergroups` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_usergroups` BEFORE UPDATE ON `blx_usergroups` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_users`
-- ----------------------------
DROP TABLE IF EXISTS `blx_users`;
CREATE TABLE `blx_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`),
  UNIQUE KEY `email_unique` (`email`),
  UNIQUE KEY `user_name_unique` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_users` BEFORE INSERT ON `blx_users` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_users` BEFORE UPDATE ON `blx_users` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_userwidgets`
-- ----------------------------
DROP TABLE IF EXISTS `blx_userwidgets`;
CREATE TABLE `blx_userwidgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `sort_order` int(11) unsigned NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  KEY `userwidgets_users_fk` (`user_id`) USING BTREE,
  CONSTRAINT `userwidgets_users_fk` FOREIGN KEY (`user_id`) REFERENCES `blx_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_userwidgets` BEFORE INSERT ON `blx_userwidgets` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_userwidgets` BEFORE UPDATE ON `blx_userwidgets` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_userwidgetsettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_userwidgetsettings`;
CREATE TABLE `blx_userwidgetsettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `widget_id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `widget_id_key_unique` (`widget_id`,`key`) USING BTREE,
  KEY `userwidgetsettings_userwidgets_fk` (`widget_id`) USING BTREE,
  CONSTRAINT `userwidgetsettings_userwidgets_fk` FOREIGN KEY (`widget_id`) REFERENCES `blx_userwidgets` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_userwidgetsettings` BEFORE INSERT ON `blx_userwidgetsettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_userwidgetsettings` BEFORE UPDATE ON `blx_userwidgetsettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
