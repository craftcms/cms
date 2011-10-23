/*
 Navicat MySQL Data Transfer

 Source Server         : sydey
 Source Server Version : 50509
 Source Host           : 192.168.168.60
 Source Database       : blocks

 Target Server Version : 50509
 File Encoding         : utf-8

 Date: 10/21/2011 09:40:06 AM
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
  `display_order` int(11) NOT NULL,
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
--  Table structure for `blx_contentblocks`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentblocks`;
CREATE TABLE `blx_contentblocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_id` int(11) NOT NULL,
  `handle` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `instructions` text COLLATE utf8_unicode_ci,
  `required` tinyint(1) DEFAULT '0',
  `display_order` int(11) NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `handle_unique` (`handle`) USING BTREE,
  KEY `contentblocks_contentsections_fk` (`section_id`) USING BTREE,
  CONSTRAINT `contentblocks_contentsections_fk` FOREIGN KEY (`section_id`) REFERENCES `blx_contentsections` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentblocks` BEFORE INSERT ON `blx_contentblocks` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentblocks` BEFORE UPDATE ON `blx_contentblocks` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_contentblocksettings`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentblocksettings`;
CREATE TABLE `blx_contentblocksettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `Key_unique` (`key`) USING BTREE,
  KEY `contentblocksettings_contentblocks_fk` (`block_id`) USING BTREE,
  CONSTRAINT `contentblocksettings_contentblocks_fk` FOREIGN KEY (`block_id`) REFERENCES `blx_contentblocks` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentblocksettings` BEFORE INSERT ON `blx_contentblocksettings` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentblocksettings` BEFORE UPDATE ON `blx_contentblocksettings` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_contentdraftdata`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentdraftdata`;
CREATE TABLE `blx_contentdraftdata` (
  `draft_id` int(11) NOT NULL,
  `block_id` int(11) NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`draft_id`,`block_id`),
  UNIQUE KEY `draft_id_block_id_unique` (`draft_id`,`block_id`) USING BTREE,
  KEY `contentdraftdata_contentdrafts_fk` (`draft_id`) USING BTREE,
  KEY `contentdraftdata_contentblocks_fk` (`block_id`) USING BTREE,
  CONSTRAINT `contentdraftdata_contentblocks_fk` FOREIGN KEY (`block_id`) REFERENCES `blx_contentblocks` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contentdraftdata_contentdrafts_fk` FOREIGN KEY (`draft_id`) REFERENCES `blx_contentdrafts` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentdraftdata` BEFORE INSERT ON `blx_contentdraftdata` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentdraftdata` BEFORE UPDATE ON `blx_contentdraftdata` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_contentdrafts`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentdrafts`;
CREATE TABLE `blx_contentdrafts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `label` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  KEY `page_id_index` (`page_id`) USING BTREE,
  KEY `author_id_index` (`author_id`) USING BTREE,
  CONSTRAINT `contentdrafts_contentpages_fk` FOREIGN KEY (`page_id`) REFERENCES `blx_contentpages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contentdrafts_users_fk` FOREIGN KEY (`author_id`) REFERENCES `blx_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentdrafts` BEFORE INSERT ON `blx_contentdrafts` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentdrafts` BEFORE UPDATE ON `blx_contentdrafts` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_contentpages`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentpages`;
CREATE TABLE `blx_contentpages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `section_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `slug` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `full_uri` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expiration_date` int(11) DEFAULT NULL,
  `page_order` int(11) NOT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  `post_date` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `section_id_page_order_unique` (`section_id`,`page_order`),
  KEY `section_id_index` (`section_id`) USING BTREE,
  KEY `author_id_index` (`author_id`) USING BTREE,
  KEY `contentpages_contentpages_fk` (`parent_id`) USING BTREE,
  CONSTRAINT `contentpages_contentpages_fk` FOREIGN KEY (`parent_id`) REFERENCES `blx_contentpages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contentpages_contentsections_fk` FOREIGN KEY (`section_id`) REFERENCES `blx_contentsections` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contentpages_users_fk` FOREIGN KEY (`author_id`) REFERENCES `blx_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentpages` BEFORE INSERT ON `blx_contentpages` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentpages` BEFORE UPDATE ON `blx_contentpages` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_contentpagetitles`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentpagetitles`;
CREATE TABLE `blx_contentpagetitles` (
  `page_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_code` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`page_id`,`language_code`),
  UNIQUE KEY `page_id_language_code_unique` (`page_id`,`language_code`) USING BTREE,
  KEY `contentpagetitles_contentpages_fk` (`page_id`) USING BTREE,
  CONSTRAINT `contentpagetitles_contentpages_fk` FOREIGN KEY (`page_id`) REFERENCES `blx_contentpages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentpagetitles` BEFORE INSERT ON `blx_contentpagetitles` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentpagetitles` BEFORE UPDATE ON `blx_contentpagetitles` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_contentsections`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentsections`;
CREATE TABLE `blx_contentsections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `site_id` int(11) NOT NULL,
  `handle` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `url_format` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `max_pages` int(11) DEFAULT NULL,
  `template` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `handle_unique` (`handle`) USING BTREE,
  KEY `parent_id_index` (`parent_id`) USING BTREE,
  KEY `site_id_index` (`site_id`) USING BTREE,
  CONSTRAINT `contentsections_contentsections_fk` FOREIGN KEY (`parent_id`) REFERENCES `blx_contentsections` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contentsections_sites_fk` FOREIGN KEY (`site_id`) REFERENCES `blx_sites` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentsections` BEFORE INSERT ON `blx_contentsections` FOR EACH ROW SET NEW.date_created = UTC_TIMESTAMP(),
	NEW.date_updated = UTC_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentsections` BEFORE UPDATE ON `blx_contentsections` FOR EACH ROW SET NEW.date_updated = UTC_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_contentversiondata`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentversiondata`;
CREATE TABLE `blx_contentversiondata` (
  `version_id` int(11) NOT NULL,
  `block_id` int(11) NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`version_id`,`block_id`),
  UNIQUE KEY `version_id_block_id_unique` (`version_id`,`block_id`) USING BTREE,
  KEY `contentversiondata_contentversions_fk` (`version_id`) USING BTREE,
  KEY `contentversiondata_contentblocks_fk` (`block_id`) USING BTREE,
  CONSTRAINT `contentversiondata_contentblocks_fk` FOREIGN KEY (`block_id`) REFERENCES `blx_contentblocks` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contentversiondata_contentversions_fk` FOREIGN KEY (`version_id`) REFERENCES `blx_contentversions` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentversiondata` BEFORE INSERT ON `blx_contentversiondata` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentversiondata` BEFORE UPDATE ON `blx_contentversiondata` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.date_created = OLD.date_created;
 ;;
delimiter ;

-- ----------------------------
--  Table structure for `blx_contentversions`
-- ----------------------------
DROP TABLE IF EXISTS `blx_contentversions`;
CREATE TABLE `blx_contentversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `num` int(11) NOT NULL,
  `label` text COLLATE utf8_unicode_ci NOT NULL,
  `is_live` tinyint(1) NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `page_id_num_unique` (`page_id`,`num`) USING BTREE,
  UNIQUE KEY `page_id_is_live_unique` (`page_id`,`is_live`) USING BTREE,
  KEY `page_id_index` (`page_id`) USING BTREE,
  CONSTRAINT `contentversions_contentpages_fk` FOREIGN KEY (`page_id`) REFERENCES `blx_contentpages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
delimiter ;;
CREATE TRIGGER `auditinfoinsert_contentversions` BEFORE INSERT ON `blx_contentversions` FOR EACH ROW SET NEW.date_created = UNIX_TIMESTAMP(),
	NEW.date_updated = UNIX_TIMESTAMP(),
	NEW.uid = UUID();
 ;;
delimiter ;
delimiter ;;
CREATE TRIGGER `auditinfoupdate_contentversions` BEFORE UPDATE ON `blx_contentversions` FOR EACH ROW SET NEW.date_updated = UNIX_TIMESTAMP(),
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
  `version` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `build_number` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `edition` enum('Personal','Standard','Pro') COLLATE utf8_unicode_ci NOT NULL,
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
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
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
  `display_order` int(11) NOT NULL,
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
  UNIQUE KEY `key_unique` (`key`) USING BTREE,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
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
  UNIQUE KEY `key_unique` (`key`) USING BTREE,
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
  `relative_path` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `include_subfolders` tinyint(1) DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
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
  `display_order` int(11) NOT NULL,
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
  UNIQUE KEY `key_unique` (`key`) USING BTREE,
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
  `value` tinyint(1) DEFAULT '0',
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
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`) USING BTREE,
  UNIQUE KEY `group_id_user_id_unique` (`user_id`,`group_id`) USING BTREE,
  KEY `usergroups_users_fk` (`user_id`) USING BTREE,
  KEY `usergroups_groups_fk` (`group_id`) USING BTREE,
  CONSTRAINT `usergroups_groups_fk` FOREIGN KEY (`group_id`) REFERENCES `blx_groups` (`id`),
  CONSTRAINT `usergroups_users_fk` FOREIGN KEY (`user_id`) REFERENCES `blx_users` (`id`)
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
  `last_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` int(11) DEFAULT NULL,
  `date_updated` int(11) DEFAULT NULL,
  `uid` varchar(36) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_unique` (`id`),
  UNIQUE KEY `email_unique` (`email`),
  UNIQUE KEY `user_name_unique` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
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

SET FOREIGN_KEY_CHECKS = 1;
