<?php

class m110903_061157_utf8_users_to_many_groups extends CDbMigration
{
	public function safeUp()
	{
		$dbName = Blocks::app()->configRepo->getDatabaseName();
		$tablePrefix = Blocks::app()->configRepo->getDatabaseTablePrefix();
		$userName = Blocks::app()->configRepo->getDatabaseAuthName();

		$this->execute(
		'
		SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
		SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
		SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'TRADITIONAL\';

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_PluginSettings` DROP FOREIGN KEY `PluginSettings_Plugins_FK` ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_PluginComponents` DROP FOREIGN KEY `PluginComponents_Plugins_FK` ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_ContentPages` CHANGE COLUMN `Slug` `Slug` VARCHAR(250) NOT NULL  , CHANGE COLUMN `FullUri` `FullUri` VARCHAR(1000) NOT NULL  , CHANGE COLUMN `Status` `Status` ENUM(\'online\', \'offline\', \'archived\') NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  , DROP FOREIGN KEY `ContentPages_ContentPages_FK` ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_ContentPages`
		  ADD CONSTRAINT `ContentPages_ContentPages_FK`
		  FOREIGN KEY (`ParentId` )
		  REFERENCES `'.$dbName.'`.`'.$tablePrefix.'_ContentPages` (`Id` )
		  ON DELETE NO ACTION
		  ON UPDATE NO ACTION;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_ContentSections` CHANGE COLUMN `Handle` `Handle` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Label` `Label` VARCHAR(500) NOT NULL  , CHANGE COLUMN `PageUrlPrefix` `PageUrlPrefix` VARCHAR(250) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_Sites` CHANGE COLUMN `Handle` `Handle` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Label` `Label` VARCHAR(500) NOT NULL  , CHANGE COLUMN `Url` `Url` VARCHAR(250) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_ContentRevisions` CHANGE COLUMN `LanguageCode` `LanguageCode` CHAR(2) NOT NULL  , CHANGE COLUMN `Note` `Note` TEXT NULL DEFAULT NULL  , CHANGE COLUMN `Title` `Title` VARCHAR(250) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_Users` CHANGE COLUMN `Email` `Email` VARCHAR(200) NOT NULL  , CHANGE COLUMN `FirstName` `FirstName` VARCHAR(100) NOT NULL  , CHANGE COLUMN `LastName` `LastName` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_ContentDrafts` CHANGE COLUMN `Note` `Note` TEXT NOT NULL  , CHANGE COLUMN `Status` `Status` ENUM(\'InProgress\', \'PendingApproval\') NOT NULL DEFAULT \'InProgress\'  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  , DROP FOREIGN KEY `ContentDrafts_ContentPages_FK` ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_ContentDrafts`
		  ADD CONSTRAINT `ContentDrafts_ContentPages_FK`
		  FOREIGN KEY (`PageId` )
		  REFERENCES `'.$dbName.'`.`'.$tablePrefix.'_ContentPages` (`Id` )
		  ON DELETE NO ACTION
		  ON UPDATE NO ACTION;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_UserGroups` CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_UserGroupPermissions` CHANGE COLUMN `Key` `Key` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_Assets` CHANGE COLUMN `Path` `Path` VARCHAR(500) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_UploadFolders` CHANGE COLUMN `RelativePath` `RelativePath` VARCHAR(500) NULL DEFAULT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_AssetBlockSettings` CHANGE COLUMN `Key` `Key` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Value` `Value` TEXT NULL DEFAULT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_Plugins` CHANGE COLUMN `Name` `Name` VARCHAR(50) NOT NULL  , CHANGE COLUMN `Version` `Version` VARCHAR(15) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_PluginSettings` CHANGE COLUMN `PluginName` `PluginName` VARCHAR(50) NOT NULL  , CHANGE COLUMN `Key` `Key` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Value` `Value` TEXT NULL DEFAULT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ,
		  ADD CONSTRAINT `PluginSettings_Plugins_FK`
		  FOREIGN KEY (`PluginName` )
		  REFERENCES `'.$dbName.'`.`'.$tablePrefix.'_Plugins` (`Name` )
		  ON DELETE NO ACTION
		  ON UPDATE NO ACTION;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_PluginComponents` CHANGE COLUMN `PluginName` `PluginName` VARCHAR(50) NOT NULL  , CHANGE COLUMN `Name` `Name` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Type` `Type` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ,
		  ADD CONSTRAINT `PluginComponents_Plugins_FK`
		  FOREIGN KEY (`PluginName` )
		  REFERENCES `'.$dbName.'`.`'.$tablePrefix.'_Plugins` (`Name` )
		  ON DELETE NO ACTION
		  ON UPDATE NO ACTION;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_ContentBlocks` CHANGE COLUMN `Handle` `Handle` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Label` `Label` VARCHAR(500) NOT NULL  , CHANGE COLUMN `Type` `Type` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Instructions` `Instructions` TEXT NULL DEFAULT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_ContentBlockSettings` CHANGE COLUMN `Key` `Key` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Value` `Value` TEXT NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_SiteBlocks` CHANGE COLUMN `Handle` `Handle` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Label` `Label` VARCHAR(500) NOT NULL  , CHANGE COLUMN `Type` `Type` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Instructions` `Instructions` TEXT NULL DEFAULT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_SiteBlockSettings` CHANGE COLUMN `Key` `Key` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Value` `Value` TEXT NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_UserBlocks` CHANGE COLUMN `Type` `Type` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Handle` `Handle` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Label` `Label` VARCHAR(500) NOT NULL  , CHANGE COLUMN `Instructions` `Instructions` TEXT NULL DEFAULT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_UserBlockSettings` CHANGE COLUMN `Key` `Key` VARCHAR(100) NOT NULL  , CHANGE COLUMN `Value` `Value` TEXT NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;

		ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_AssetBlocks` CHANGE COLUMN `Type` `Type` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Handle` `Handle` VARCHAR(150) NOT NULL  , CHANGE COLUMN `Label` `Label` VARCHAR(500) NOT NULL  , CHANGE COLUMN `Uid` `Uid` VARCHAR(36) NULL DEFAULT \'\'  ;


		DELIMITER $$

		USE `'.$dbName.'`$$
		DROP TRIGGER IF EXISTS `'.$dbName.'`.`AuditInfoInsert_ContentRevisions` $$


		DELIMITER ;


		DELIMITER $$

		USE `'.$dbName.'`$$


		CREATE
		DEFINER=`'.$userName.'`@`127.0.0.1`
		TRIGGER `'.$dbName.'`.`AuditInfoInsert_ContentRevisions`
		BEFORE INSERT ON `'.$dbName.'`.`'.$tablePrefix.'_ContentRevisions`
		FOR EACH ROW
		SET NEW.DateCreated = UNIX_TIMESTAMP(),
			  NEW.Uid = UUID();$$


		DELIMITER ;


		DELIMITER $$

		USE `'.$dbName.'`$$
		DROP TRIGGER IF EXISTS `'.$dbName.'`.`AuditInfoInsert_Groups` $$

		DELIMITER ;

		DELIMITER $$

		USE `'.$dbName.'`$$


		CREATE
		DEFINER=`'.$userName.'`@`127.0.0.1`
		TRIGGER `'.$dbName.'`.`AuditInfoInsert_Groups`
		BEFORE INSERT ON `'.$dbName.'`.`'.$tablePrefix.'_Groups`
		FOR EACH ROW
		SET NEW.DateCreated = UNIX_TIMESTAMP(),
			NEW.DateUpdated = UNIX_TIMESTAMP(),
			NEW.Uid = UUID()$$


		DELIMITER ;


		DELIMITER $$

		USE `'.$dbName.'`$$
		DROP TRIGGER IF EXISTS `'.$dbName.'`.`AuditInfoUpdate_Groups` $$

		DELIMITER ;

		DELIMITER $$

		USE `'.$dbName.'`$$


		CREATE
		DEFINER=`'.$userName.'`@`127.0.0.1`
		TRIGGER `'.$dbName.'`.`AuditInfoUpdate_Groups`
		BEFORE UPDATE ON `'.$dbName.'`.`'.$tablePrefix.'_Groups`
		FOR EACH ROW
		SET NEW.DateUpdated = UNIX_TIMESTAMP(),
			NEW.DateCreated = OLD.DateCreated$$


		DELIMITER ;


		SET SQL_MODE=@OLD_SQL_MODE;
		SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
		SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;'
		);
	}

	public function safeDown()
	{
		echo "m110903_061157_utf8_users_to_many_groups does not support migration down.\n";
		return false;
	}
}
