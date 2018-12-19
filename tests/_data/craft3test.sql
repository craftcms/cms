-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2018 at 10:12 PM
-- Server version: 10.1.28-MariaDB
-- PHP Version: 7.1.10


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `craft3test`
--

-- --------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `craft_assetindexdata`;
DROP TABLE IF EXISTS `craft_assets`;
DROP TABLE IF EXISTS `craft_assettransformindex`;
DROP TABLE IF EXISTS `craft_assettransforms`;
DROP TABLE IF EXISTS `craft_categories`;
DROP TABLE IF EXISTS `craft_categorygroups`;
DROP TABLE IF EXISTS `craft_categorygroups_sites`;
DROP TABLE IF EXISTS `craft_content`;
DROP TABLE IF EXISTS `craft_craftidtokens`;
DROP TABLE IF EXISTS `craft_deprecationerrors`;
DROP TABLE IF EXISTS `craft_elementindexsettings`;
DROP TABLE IF EXISTS `craft_elements`;
DROP TABLE IF EXISTS `craft_elements_sites`;
DROP TABLE IF EXISTS `craft_entries`;
DROP TABLE IF EXISTS `craft_entrydrafts`;
DROP TABLE IF EXISTS `craft_entrytypes`;
DROP TABLE IF EXISTS `craft_entryversions`;
DROP TABLE IF EXISTS `craft_fieldgroups`;
DROP TABLE IF EXISTS `craft_fieldlayoutfields`;
DROP TABLE IF EXISTS `craft_fieldlayouts`;
DROP TABLE IF EXISTS `craft_fieldlayouttabs`;
DROP TABLE IF EXISTS `craft_fields`;
DROP TABLE IF EXISTS `craft_globalsets`;
DROP TABLE IF EXISTS `craft_info`;
DROP TABLE IF EXISTS `craft_matrixblocks`;
DROP TABLE IF EXISTS `craft_matrixblocktypes`;
DROP TABLE IF EXISTS `craft_migrations`;
DROP TABLE IF EXISTS `craft_plugins`;
DROP TABLE IF EXISTS `craft_queue`;
DROP TABLE IF EXISTS `craft_relations`;
DROP TABLE IF EXISTS `craft_resourcepaths`;
DROP TABLE IF EXISTS `craft_routes`;
DROP TABLE IF EXISTS `craft_searchindex`;
DROP TABLE IF EXISTS `craft_sections`;
DROP TABLE IF EXISTS `craft_sections_sites`;
DROP TABLE IF EXISTS `craft_sessions`;
DROP TABLE IF EXISTS `craft_shunnedmessages`;
DROP TABLE IF EXISTS `craft_sitegroups`;
DROP TABLE IF EXISTS `craft_sites`;
DROP TABLE IF EXISTS `craft_structureelements`;
DROP TABLE IF EXISTS `craft_structures`;
DROP TABLE IF EXISTS `craft_systemmessages`;
DROP TABLE IF EXISTS `craft_systemsettings`;
DROP TABLE IF EXISTS `craft_taggroups`;
DROP TABLE IF EXISTS `craft_tags`;
DROP TABLE IF EXISTS `craft_templatecacheelements`;
DROP TABLE IF EXISTS `craft_templatecachequeries`;
DROP TABLE IF EXISTS `craft_templatecaches`;
DROP TABLE IF EXISTS `craft_tokens`;
DROP TABLE IF EXISTS `craft_usergroups`;
DROP TABLE IF EXISTS `craft_usergroups_users`;
DROP TABLE IF EXISTS `craft_userpermissions`;
DROP TABLE IF EXISTS `craft_userpermissions_usergroups`;
DROP TABLE IF EXISTS `craft_userpermissions_users`;
DROP TABLE IF EXISTS `craft_userpreferences`;
DROP TABLE IF EXISTS `craft_users`;
DROP TABLE IF EXISTS `craft_volumefolders`;
DROP TABLE IF EXISTS `craft_volumes`;
DROP TABLE IF EXISTS `craft_widgets`;

SET FOREIGN_KEY_CHECKS = 1;



--
-- Table structure for table `craft_assetindexdata`
--

CREATE TABLE `craft_assetindexdata` (
  `id` int(11) NOT NULL,
  `sessionId` varchar(36) NOT NULL DEFAULT '',
  `volumeId` int(11) NOT NULL,
  `uri` text,
  `size` bigint(20) UNSIGNED DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `recordId` int(11) DEFAULT NULL,
  `inProgress` tinyint(1) DEFAULT '0',
  `completed` tinyint(1) DEFAULT '0',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_assets`
--

CREATE TABLE `craft_assets` (
  `id` int(11) NOT NULL,
  `volumeId` int(11) DEFAULT NULL,
  `folderId` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `kind` varchar(50) NOT NULL DEFAULT 'unknown',
  `width` int(11) UNSIGNED DEFAULT NULL,
  `height` int(11) UNSIGNED DEFAULT NULL,
  `size` bigint(20) UNSIGNED DEFAULT NULL,
  `focalPoint` varchar(13) DEFAULT NULL,
  `dateModified` datetime DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_assettransformindex`
--

CREATE TABLE `craft_assettransformindex` (
  `id` int(11) NOT NULL,
  `assetId` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `format` varchar(255) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `volumeId` int(11) DEFAULT NULL,
  `fileExists` tinyint(1) NOT NULL DEFAULT '0',
  `inProgress` tinyint(1) NOT NULL DEFAULT '0',
  `dateIndexed` datetime DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_assettransforms`
--

CREATE TABLE `craft_assettransforms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `mode` enum('stretch','fit','crop') NOT NULL DEFAULT 'crop',
  `position` enum('top-left','top-center','top-right','center-left','center-center','center-right','bottom-left','bottom-center','bottom-right') NOT NULL DEFAULT 'center-center',
  `width` int(11) UNSIGNED DEFAULT NULL,
  `height` int(11) UNSIGNED DEFAULT NULL,
  `format` varchar(255) DEFAULT NULL,
  `quality` int(11) DEFAULT NULL,
  `interlace` enum('none','line','plane','partition') NOT NULL DEFAULT 'none',
  `dimensionChangeTime` datetime DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_categories`
--

CREATE TABLE `craft_categories` (
  `id` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_categorygroups`
--

CREATE TABLE `craft_categorygroups` (
  `id` int(11) NOT NULL,
  `structureId` int(11) NOT NULL,
  `fieldLayoutId` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_categorygroups_sites`
--

CREATE TABLE `craft_categorygroups_sites` (
  `id` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `siteId` int(11) NOT NULL,
  `hasUrls` tinyint(1) NOT NULL DEFAULT '1',
  `uriFormat` text,
  `template` varchar(500) DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_content`
--

CREATE TABLE `craft_content` (
  `id` int(11) NOT NULL,
  `elementId` int(11) NOT NULL,
  `siteId` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_craftidtokens`
--

CREATE TABLE `craft_craftidtokens` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `accessToken` text NOT NULL,
  `expiryDate` datetime DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_deprecationerrors`
--

CREATE TABLE `craft_deprecationerrors` (
  `id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `fingerprint` varchar(255) NOT NULL,
  `lastOccurrence` datetime NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` smallint(6) UNSIGNED DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `traces` text,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_deprecationerrors`
--

INSERT INTO `craft_deprecationerrors` (`id`, `key`, `fingerprint`, `lastOccurrence`, `file`, `line`, `message`, `traces`, `dateCreated`, `dateUpdated`, `uid`) VALUES
(1, 'validation.key', 'C:\\xampp\\htdocs\\cmstest\\src\\services\\Config.php:175', '2018-10-08 19:37:48', 'C:\\xampp\\htdocs\\cmstest\\src\\services\\Config.php', 175, 'The auto-generated validation key stored at C:\\xampp\\htdocs\\cmstest\\tests\\_craft\\storage\\runtime\\validation.key has been deprecated. Copy its value to the “securityKey” config setting in config/general.php.', '[{\"objectClass\":\"craft\\\\services\\\\Deprecator\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\services\\\\Config.php\",\"line\":127,\"class\":\"craft\\\\services\\\\Deprecator\",\"method\":\"log\",\"args\":\"\\\"validation.key\\\", \\\"The auto-generated validation key stored at C:\\\\xampp\\\\htdocs\\\\cmst...\\\"\"},{\"objectClass\":\"craft\\\\services\\\\Config\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\services\\\\Config.php\",\"line\":175,\"class\":\"craft\\\\services\\\\Config\",\"method\":\"getConfigSettings\",\"args\":\"\\\"general\\\"\"},{\"objectClass\":\"craft\\\\services\\\\Config\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\helpers\\\\App.php\",\"line\":485,\"class\":\"craft\\\\services\\\\Config\",\"method\":\"getGeneral\",\"args\":null},{\"objectClass\":null,\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\config\\\\app.web.php\",\"line\":11,\"class\":\"craft\\\\helpers\\\\App\",\"method\":\"webRequestConfig\",\"args\":null},{\"objectClass\":\"Codeception\\\\Lib\\\\Connector\\\\Yii2\",\"file\":null,\"line\":null,\"class\":\"Codeception\\\\Lib\\\\Connector\\\\Yii2\",\"method\":\"{closure}\",\"args\":null},{\"objectClass\":null,\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\di\\\\Container.php\",\"line\":503,\"class\":null,\"method\":\"call_user_func_array\",\"args\":\"Closure, []\"},{\"objectClass\":\"yii\\\\di\\\\Container\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\BaseYii.php\",\"line\":351,\"class\":\"yii\\\\di\\\\Container\",\"method\":\"invoke\",\"args\":\"Closure, []\"},{\"objectClass\":null,\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\di\\\\ServiceLocator.php\",\"line\":137,\"class\":\"yii\\\\BaseYii\",\"method\":\"createObject\",\"args\":\"Closure\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\base\\\\Module.php\",\"line\":742,\"class\":\"yii\\\\di\\\\ServiceLocator\",\"method\":\"get\",\"args\":\"\\\"request\\\", true\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\web\\\\Application.php\",\"line\":333,\"class\":\"yii\\\\base\\\\Module\",\"method\":\"get\",\"args\":\"\\\"request\\\", true\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\web\\\\Application.php\",\"line\":160,\"class\":\"craft\\\\web\\\\Application\",\"method\":\"get\",\"args\":\"\\\"request\\\"\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\helpers\\\\App.php\",\"line\":371,\"class\":\"yii\\\\web\\\\Application\",\"method\":\"getRequest\",\"args\":null},{\"objectClass\":null,\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\config\\\\app.php\",\"line\":214,\"class\":\"craft\\\\helpers\\\\App\",\"method\":\"logConfig\",\"args\":null},{\"objectClass\":\"Codeception\\\\Lib\\\\Connector\\\\Yii2\",\"file\":null,\"line\":null,\"class\":\"Codeception\\\\Lib\\\\Connector\\\\Yii2\",\"method\":\"{closure}\",\"args\":null},{\"objectClass\":null,\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\di\\\\Container.php\",\"line\":503,\"class\":null,\"method\":\"call_user_func_array\",\"args\":\"Closure, []\"},{\"objectClass\":\"yii\\\\di\\\\Container\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\BaseYii.php\",\"line\":351,\"class\":\"yii\\\\di\\\\Container\",\"method\":\"invoke\",\"args\":\"Closure, []\"},{\"objectClass\":null,\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\di\\\\ServiceLocator.php\",\"line\":137,\"class\":\"yii\\\\BaseYii\",\"method\":\"createObject\",\"args\":\"Closure\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\base\\\\Module.php\",\"line\":742,\"class\":\"yii\\\\di\\\\ServiceLocator\",\"method\":\"get\",\"args\":\"\\\"log\\\", true\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\web\\\\Application.php\",\"line\":333,\"class\":\"yii\\\\base\\\\Module\",\"method\":\"get\",\"args\":\"\\\"log\\\", true\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\base\\\\Application.php\",\"line\":508,\"class\":\"craft\\\\web\\\\Application\",\"method\":\"get\",\"args\":\"\\\"log\\\"\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\base\\\\ApplicationTrait.php\",\"line\":1133,\"class\":\"yii\\\\base\\\\Application\",\"method\":\"getLog\",\"args\":null},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\web\\\\Application.php\",\"line\":106,\"class\":\"craft\\\\web\\\\Application\",\"method\":\"_preInit\",\"args\":null},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\base\\\\BaseObject.php\",\"line\":109,\"class\":\"craft\\\\web\\\\Application\",\"method\":\"init\",\"args\":null},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\base\\\\Application.php\",\"line\":206,\"class\":\"yii\\\\base\\\\BaseObject\",\"method\":\"__construct\",\"args\":\"[\\\"components\\\" => [\\\"config\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Config\\\", \\\"configDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\tests\\\\_craft\\\\config\\\", \\\"appDefaultsDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest/src/config/defaults\\\"], \\\"api\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Api\\\"], \\\"assets\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Assets\\\"], \\\"assetIndexer\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\AssetIndexer\\\"], ...], \\\"id\\\" => \\\"craft-test\\\", \\\"name\\\" => \\\"Craft CMS\\\", \\\"version\\\" => \\\"3.0.25\\\", ...]\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\src\\\\web\\\\Application.php\",\"line\":97,\"class\":\"yii\\\\base\\\\Application\",\"method\":\"__construct\",\"args\":\"[\\\"components\\\" => [\\\"config\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Config\\\", \\\"configDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\tests\\\\_craft\\\\config\\\", \\\"appDefaultsDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest/src/config/defaults\\\"], \\\"api\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Api\\\"], \\\"assets\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Assets\\\"], \\\"assetIndexer\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\AssetIndexer\\\"], ...], \\\"id\\\" => \\\"craft-test\\\", \\\"name\\\" => \\\"Craft CMS\\\", \\\"version\\\" => \\\"3.0.25\\\", ...]\"},{\"objectClass\":\"craft\\\\web\\\\Application\",\"file\":null,\"line\":null,\"class\":\"craft\\\\web\\\\Application\",\"method\":\"__construct\",\"args\":\"[\\\"components\\\" => [\\\"config\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Config\\\", \\\"configDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\tests\\\\_craft\\\\config\\\", \\\"appDefaultsDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest/src/config/defaults\\\"], \\\"api\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Api\\\"], \\\"assets\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Assets\\\"], \\\"assetIndexer\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\AssetIndexer\\\"], ...], \\\"id\\\" => \\\"craft-test\\\", \\\"name\\\" => \\\"Craft CMS\\\", \\\"version\\\" => \\\"3.0.25\\\", ...]\"},{\"objectClass\":\"ReflectionClass\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\di\\\\Container.php\",\"line\":383,\"class\":\"ReflectionClass\",\"method\":\"newInstanceArgs\",\"args\":\"[[\\\"components\\\" => [\\\"config\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Config\\\", \\\"configDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\tests\\\\_craft\\\\config\\\", \\\"appDefaultsDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest/src/config/defaults\\\"], \\\"api\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Api\\\"], \\\"assets\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Assets\\\"], \\\"assetIndexer\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\AssetIndexer\\\"], ...], \\\"id\\\" => \\\"craft-test\\\", \\\"name\\\" => \\\"Craft CMS\\\", \\\"version\\\" => \\\"3.0.25\\\", ...]]\"},{\"objectClass\":\"yii\\\\di\\\\Container\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\di\\\\Container.php\",\"line\":156,\"class\":\"yii\\\\di\\\\Container\",\"method\":\"build\",\"args\":\"\\\"craft\\\\web\\\\Application\\\", [], [\\\"components\\\" => [\\\"config\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Config\\\", \\\"configDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\tests\\\\_craft\\\\config\\\", \\\"appDefaultsDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest/src/config/defaults\\\"], \\\"api\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Api\\\"], \\\"assets\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Assets\\\"], \\\"assetIndexer\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\AssetIndexer\\\"], ...], \\\"id\\\" => \\\"craft-test\\\", \\\"name\\\" => \\\"Craft CMS\\\", \\\"version\\\" => \\\"3.0.25\\\", ...]\"},{\"objectClass\":\"yii\\\\di\\\\Container\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\yiisoft\\\\yii2\\\\BaseYii.php\",\"line\":349,\"class\":\"yii\\\\di\\\\Container\",\"method\":\"get\",\"args\":\"\\\"craft\\\\web\\\\Application\\\", [], [\\\"components\\\" => [\\\"config\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Config\\\", \\\"configDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\tests\\\\_craft\\\\config\\\", \\\"appDefaultsDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest/src/config/defaults\\\"], \\\"api\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Api\\\"], \\\"assets\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Assets\\\"], \\\"assetIndexer\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\AssetIndexer\\\"], ...], \\\"id\\\" => \\\"craft-test\\\", \\\"name\\\" => \\\"Craft CMS\\\", \\\"version\\\" => \\\"3.0.25\\\", ...]\"},{\"objectClass\":null,\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\Lib\\\\Connector\\\\Yii2.php\",\"line\":253,\"class\":\"yii\\\\BaseYii\",\"method\":\"createObject\",\"args\":\"[\\\"components\\\" => [\\\"config\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Config\\\", \\\"configDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\tests\\\\_craft\\\\config\\\", \\\"appDefaultsDir\\\" => \\\"C:\\\\xampp\\\\htdocs\\\\cmstest/src/config/defaults\\\"], \\\"api\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Api\\\"], \\\"assets\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\Assets\\\"], \\\"assetIndexer\\\" => [\\\"class\\\" => \\\"craft\\\\services\\\\AssetIndexer\\\"], ...], \\\"id\\\" => \\\"craft-test\\\", \\\"name\\\" => \\\"Craft CMS\\\", \\\"version\\\" => \\\"3.0.25\\\", ...]\"},{\"objectClass\":\"Codeception\\\\Lib\\\\Connector\\\\Yii2\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\Module\\\\Yii2.php\",\"line\":300,\"class\":\"Codeception\\\\Lib\\\\Connector\\\\Yii2\",\"method\":\"startApp\",\"args\":null},{\"objectClass\":\"Codeception\\\\Module\\\\Yii2\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\Subscriber\\\\Module.php\",\"line\":56,\"class\":\"Codeception\\\\Module\\\\Yii2\",\"method\":\"_before\",\"args\":\"craftunit\\\\helpers\\\\UrlHelperTest\"},{\"objectClass\":\"Codeception\\\\Subscriber\\\\Module\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\symfony\\\\event-dispatcher\\\\EventDispatcher.php\",\"line\":212,\"class\":\"Codeception\\\\Subscriber\\\\Module\",\"method\":\"before\",\"args\":\"Codeception\\\\Event\\\\TestEvent, \\\"test.before\\\", Symfony\\\\Component\\\\EventDispatcher\\\\EventDispatcher\"},{\"objectClass\":\"Symfony\\\\Component\\\\EventDispatcher\\\\EventDispatcher\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\symfony\\\\event-dispatcher\\\\EventDispatcher.php\",\"line\":44,\"class\":\"Symfony\\\\Component\\\\EventDispatcher\\\\EventDispatcher\",\"method\":\"doDispatch\",\"args\":\"[[Codeception\\\\Subscriber\\\\PrepareTest, \\\"prepare\\\"], [Codeception\\\\Subscriber\\\\Module, \\\"before\\\"]], \\\"test.before\\\", Codeception\\\\Event\\\\TestEvent\"},{\"objectClass\":\"Symfony\\\\Component\\\\EventDispatcher\\\\EventDispatcher\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\phpunit-wrapper\\\\src\\\\Listener.php\",\"line\":133,\"class\":\"Symfony\\\\Component\\\\EventDispatcher\\\\EventDispatcher\",\"method\":\"dispatch\",\"args\":\"\\\"test.before\\\", Codeception\\\\Event\\\\TestEvent\"},{\"objectClass\":\"Codeception\\\\PHPUnit\\\\Listener\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\phpunit-wrapper\\\\src\\\\Listener.php\",\"line\":102,\"class\":\"Codeception\\\\PHPUnit\\\\Listener\",\"method\":\"fire\",\"args\":\"\\\"test.before\\\", Codeception\\\\Event\\\\TestEvent\"},{\"objectClass\":\"Codeception\\\\PHPUnit\\\\Listener\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\phpunit\\\\phpunit\\\\src\\\\Framework\\\\TestResult.php\",\"line\":395,\"class\":\"Codeception\\\\PHPUnit\\\\Listener\",\"method\":\"startTest\",\"args\":\"craftunit\\\\helpers\\\\UrlHelperTest\"},{\"objectClass\":\"PHPUnit\\\\Framework\\\\TestResult\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\phpunit\\\\phpunit\\\\src\\\\Framework\\\\TestResult.php\",\"line\":603,\"class\":\"PHPUnit\\\\Framework\\\\TestResult\",\"method\":\"startTest\",\"args\":\"craftunit\\\\helpers\\\\UrlHelperTest\"},{\"objectClass\":\"PHPUnit\\\\Framework\\\\TestResult\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\phpunit\\\\phpunit\\\\src\\\\Framework\\\\TestCase.php\",\"line\":798,\"class\":\"PHPUnit\\\\Framework\\\\TestResult\",\"method\":\"run\",\"args\":\"craftunit\\\\helpers\\\\UrlHelperTest\"},{\"objectClass\":\"craftunit\\\\helpers\\\\UrlHelperTest\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\phpunit\\\\phpunit\\\\src\\\\Framework\\\\TestSuite.php\",\"line\":750,\"class\":\"PHPUnit\\\\Framework\\\\TestCase\",\"method\":\"run\",\"args\":\"PHPUnit\\\\Framework\\\\TestResult\"},{\"objectClass\":\"Codeception\\\\Suite\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\phpunit-wrapper\\\\src\\\\Runner.php\",\"line\":117,\"class\":\"PHPUnit\\\\Framework\\\\TestSuite\",\"method\":\"run\",\"args\":\"PHPUnit\\\\Framework\\\\TestResult\"},{\"objectClass\":\"Codeception\\\\PHPUnit\\\\Runner\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\SuiteManager.php\",\"line\":157,\"class\":\"Codeception\\\\PHPUnit\\\\Runner\",\"method\":\"doEnhancedRun\",\"args\":\"Codeception\\\\Suite, PHPUnit\\\\Framework\\\\TestResult, [\\\"silent\\\" => false, \\\"debug\\\" => false, \\\"steps\\\" => false, \\\"html\\\" => false, ...]\"},{\"objectClass\":\"Codeception\\\\SuiteManager\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\Codecept.php\",\"line\":191,\"class\":\"Codeception\\\\SuiteManager\",\"method\":\"run\",\"args\":\"Codeception\\\\PHPUnit\\\\Runner, PHPUnit\\\\Framework\\\\TestResult, [\\\"silent\\\" => false, \\\"debug\\\" => false, \\\"steps\\\" => false, \\\"html\\\" => false, ...]\"},{\"objectClass\":\"Codeception\\\\Codecept\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\Codecept.php\",\"line\":158,\"class\":\"Codeception\\\\Codecept\",\"method\":\"runSuite\",\"args\":\"[\\\"env\\\" => [], \\\"class_name\\\" => \\\"UnitTester\\\", \\\"modules\\\" => [\\\"enabled\\\" => [\\\"Yii2\\\", \\\"Asserts\\\", \\\"\\\\Helper\\\\Unit\\\"], \\\"config\\\" => [\\\"Yii2\\\" => [\\\"configFile\\\" => \\\"tests/_craft/config/test.php\\\", \\\"entryUrl\\\" => \\\"https://test.craftcms.dev/\\\", \\\"entryScript\\\" => \\\"index.php\\\"]], \\\"depends\\\" => []], \\\"bootstrap\\\" => \\\"_bootstrap.php\\\", ...], \\\"unit\\\", null\"},{\"objectClass\":\"Codeception\\\\Codecept\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\Command\\\\Run.php\",\"line\":492,\"class\":\"Codeception\\\\Codecept\",\"method\":\"run\",\"args\":\"\\\"unit\\\"\"},{\"objectClass\":\"Codeception\\\\Command\\\\Run\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\Command\\\\Run.php\",\"line\":387,\"class\":\"Codeception\\\\Command\\\\Run\",\"method\":\"runSuites\",\"args\":\"[\\\"acceptance\\\" => \\\"acceptance\\\", \\\"functional\\\" => \\\"functional\\\", \\\"unit\\\" => \\\"unit\\\"], []\"},{\"objectClass\":\"Codeception\\\\Command\\\\Run\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\symfony\\\\console\\\\Command\\\\Command.php\",\"line\":255,\"class\":\"Codeception\\\\Command\\\\Run\",\"method\":\"execute\",\"args\":\"Symfony\\\\Component\\\\Console\\\\Input\\\\ArgvInput, Symfony\\\\Component\\\\Console\\\\Output\\\\ConsoleOutput\"},{\"objectClass\":\"Codeception\\\\Command\\\\Run\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":888,\"class\":\"Symfony\\\\Component\\\\Console\\\\Command\\\\Command\",\"method\":\"run\",\"args\":\"Symfony\\\\Component\\\\Console\\\\Input\\\\ArgvInput, Symfony\\\\Component\\\\Console\\\\Output\\\\ConsoleOutput\"},{\"objectClass\":\"Codeception\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":264,\"class\":\"Symfony\\\\Component\\\\Console\\\\Application\",\"method\":\"doRunCommand\",\"args\":\"Codeception\\\\Command\\\\Run, Symfony\\\\Component\\\\Console\\\\Input\\\\ArgvInput, Symfony\\\\Component\\\\Console\\\\Output\\\\ConsoleOutput\"},{\"objectClass\":\"Codeception\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":145,\"class\":\"Symfony\\\\Component\\\\Console\\\\Application\",\"method\":\"doRun\",\"args\":\"Symfony\\\\Component\\\\Console\\\\Input\\\\ArgvInput, Symfony\\\\Component\\\\Console\\\\Output\\\\ConsoleOutput\"},{\"objectClass\":\"Codeception\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\src\\\\Codeception\\\\Application.php\",\"line\":108,\"class\":\"Symfony\\\\Component\\\\Console\\\\Application\",\"method\":\"run\",\"args\":\"Symfony\\\\Component\\\\Console\\\\Input\\\\ArgvInput, Symfony\\\\Component\\\\Console\\\\Output\\\\ConsoleOutput\"},{\"objectClass\":\"Codeception\\\\Application\",\"file\":\"C:\\\\xampp\\\\htdocs\\\\cmstest\\\\vendor\\\\codeception\\\\codeception\\\\codecept\",\"line\":43,\"class\":\"Codeception\\\\Application\",\"method\":\"run\",\"args\":null}]', '2018-10-08 19:37:48', '2018-10-08 19:37:48', '93618233-89d5-4158-a887-1388c0dc22c6');

-- --------------------------------------------------------

--
-- Table structure for table `craft_elementindexsettings`
--

CREATE TABLE `craft_elementindexsettings` (
  `id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `settings` text,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_elements`
--

CREATE TABLE `craft_elements` (
  `id` int(11) NOT NULL,
  `fieldLayoutId` int(11) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_elements`
--

INSERT INTO `craft_elements` (`id`, `fieldLayoutId`, `type`, `enabled`, `archived`, `dateCreated`, `dateUpdated`, `uid`) VALUES
(1, NULL, 'craft\\elements\\User', 1, 0, '2018-10-08 18:33:23', '2018-10-08 18:33:23', '55178910-089c-4392-b160-adf1e9d2e2c8');

-- --------------------------------------------------------

--
-- Table structure for table `craft_elements_sites`
--

CREATE TABLE `craft_elements_sites` (
  `id` int(11) NOT NULL,
  `elementId` int(11) NOT NULL,
  `siteId` int(11) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `uri` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_entries`
--

CREATE TABLE `craft_entries` (
  `id` int(11) NOT NULL,
  `sectionId` int(11) NOT NULL,
  `typeId` int(11) NOT NULL,
  `authorId` int(11) DEFAULT NULL,
  `postDate` datetime DEFAULT NULL,
  `expiryDate` datetime DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_entrydrafts`
--

CREATE TABLE `craft_entrydrafts` (
  `id` int(11) NOT NULL,
  `entryId` int(11) NOT NULL,
  `sectionId` int(11) NOT NULL,
  `creatorId` int(11) NOT NULL,
  `siteId` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `notes` text,
  `data` mediumtext NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_entrytypes`
--

CREATE TABLE `craft_entrytypes` (
  `id` int(11) NOT NULL,
  `sectionId` int(11) NOT NULL,
  `fieldLayoutId` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `hasTitleField` tinyint(1) NOT NULL DEFAULT '1',
  `titleLabel` varchar(255) DEFAULT 'Title',
  `titleFormat` varchar(255) DEFAULT NULL,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_entryversions`
--

CREATE TABLE `craft_entryversions` (
  `id` int(11) NOT NULL,
  `entryId` int(11) NOT NULL,
  `sectionId` int(11) NOT NULL,
  `creatorId` int(11) DEFAULT NULL,
  `siteId` int(11) NOT NULL,
  `num` smallint(6) UNSIGNED NOT NULL,
  `notes` text,
  `data` mediumtext NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_fieldgroups`
--

CREATE TABLE `craft_fieldgroups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_fieldgroups`
--

INSERT INTO `craft_fieldgroups` (`id`, `name`, `dateCreated`, `dateUpdated`, `uid`) VALUES
(1, 'Common', '2018-10-08 18:33:23', '2018-10-08 18:33:23', '34ebdcec-4482-432a-9dd4-a22f82701c1d');

-- --------------------------------------------------------

--
-- Table structure for table `craft_fieldlayoutfields`
--

CREATE TABLE `craft_fieldlayoutfields` (
  `id` int(11) NOT NULL,
  `layoutId` int(11) NOT NULL,
  `tabId` int(11) NOT NULL,
  `fieldId` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_fieldlayouts`
--

CREATE TABLE `craft_fieldlayouts` (
  `id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_fieldlayouttabs`
--

CREATE TABLE `craft_fieldlayouttabs` (
  `id` int(11) NOT NULL,
  `layoutId` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_fields`
--

CREATE TABLE `craft_fields` (
  `id` int(11) NOT NULL,
  `groupId` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(64) NOT NULL,
  `context` varchar(255) NOT NULL DEFAULT 'global',
  `instructions` text,
  `translationMethod` varchar(255) NOT NULL DEFAULT 'none',
  `translationKeyFormat` text,
  `type` varchar(255) NOT NULL,
  `settings` text,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_globalsets`
--

CREATE TABLE `craft_globalsets` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `fieldLayoutId` int(11) DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_info`
--

CREATE TABLE `craft_info` (
  `id` int(11) NOT NULL,
  `version` varchar(50) NOT NULL,
  `schemaVersion` varchar(15) NOT NULL,
  `edition` tinyint(3) UNSIGNED NOT NULL,
  `timezone` varchar(30) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `on` tinyint(1) NOT NULL DEFAULT '0',
  `maintenance` tinyint(1) NOT NULL DEFAULT '0',
  `fieldVersion` char(12) NOT NULL DEFAULT '000000000000',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_info`
--

INSERT INTO `craft_info` (`id`, `version`, `schemaVersion`, `edition`, `timezone`, `name`, `on`, `maintenance`, `fieldVersion`, `dateCreated`, `dateUpdated`, `uid`) VALUES
(1, '3.0.25', '3.0.93', 0, 'America/Los_Angeles', 'test', 1, 0, 'DUgwRQQ2TU8w', '2018-10-08 18:33:23', '2018-10-08 18:34:00', '5f621b89-c3e3-4805-8dea-5a1d1963811e');

-- --------------------------------------------------------

--
-- Table structure for table `craft_matrixblocks`
--

CREATE TABLE `craft_matrixblocks` (
  `id` int(11) NOT NULL,
  `ownerId` int(11) NOT NULL,
  `ownerSiteId` int(11) DEFAULT NULL,
  `fieldId` int(11) NOT NULL,
  `typeId` int(11) NOT NULL,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_matrixblocktypes`
--

CREATE TABLE `craft_matrixblocktypes` (
  `id` int(11) NOT NULL,
  `fieldId` int(11) NOT NULL,
  `fieldLayoutId` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_migrations`
--

CREATE TABLE `craft_migrations` (
  `id` int(11) NOT NULL,
  `pluginId` int(11) DEFAULT NULL,
  `type` enum('app','plugin','content') NOT NULL DEFAULT 'app',
  `name` varchar(255) NOT NULL,
  `applyTime` datetime NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_migrations`
--

INSERT INTO `craft_migrations` (`id`, `pluginId`, `type`, `name`, `applyTime`, `dateCreated`, `dateUpdated`, `uid`) VALUES
(1, NULL, 'app', 'Install', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '220b8745-eef1-4fc2-875a-5989fca3638f'),
(2, NULL, 'app', 'm150403_183908_migrations_table_changes', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'b652b2ad-9869-49e7-90f3-3a79a9b02250'),
(3, NULL, 'app', 'm150403_184247_plugins_table_changes', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'a7160f97-c98f-4c05-9508-e773c68a09b2'),
(4, NULL, 'app', 'm150403_184533_field_version', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'c266b60f-ab3b-4c3a-84d3-60c51dcc111e'),
(5, NULL, 'app', 'm150403_184729_type_columns', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '82223cb4-2ed6-4cc4-9063-0c844c9803a8'),
(6, NULL, 'app', 'm150403_185142_volumes', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '188e068f-ec97-4d76-8955-63f139461e3c'),
(7, NULL, 'app', 'm150428_231346_userpreferences', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '8fbc5312-ed2f-4cd5-9da4-9ae538049825'),
(8, NULL, 'app', 'm150519_150900_fieldversion_conversion', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'aa58dbcf-cc95-40f4-bf0d-cd6193d24135'),
(9, NULL, 'app', 'm150617_213829_update_email_settings', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'ecff6dd9-f026-48ef-a4d8-e99a6c18b074'),
(10, NULL, 'app', 'm150721_124739_templatecachequeries', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'd13711e4-0d83-465a-943a-d23521b87758'),
(11, NULL, 'app', 'm150724_140822_adjust_quality_settings', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '6a17e434-d0ec-49ad-a666-14069281d9d3'),
(12, NULL, 'app', 'm150815_133521_last_login_attempt_ip', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'c515eb95-6c6a-44cf-beee-cfc1d1899078'),
(13, NULL, 'app', 'm151002_095935_volume_cache_settings', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '3fbb7e78-472c-45a3-9714-9cf66d9ace3b'),
(14, NULL, 'app', 'm151005_142750_volume_s3_storage_settings', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'c959b01b-2d1c-48a1-b566-68d10772f5a2'),
(15, NULL, 'app', 'm151016_133600_delete_asset_thumbnails', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2d1459c2-f518-48aa-acdf-144b35c7ec81'),
(16, NULL, 'app', 'm151209_000000_move_logo', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '220cf50d-f8c0-4f5f-bcff-bb3bf6891772'),
(17, NULL, 'app', 'm151211_000000_rename_fileId_to_assetId', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'd95795dd-413d-404c-b5dc-8bdebd3f1ab2'),
(18, NULL, 'app', 'm151215_000000_rename_asset_permissions', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '7a419656-c1eb-4175-9123-17d2d9fda6ce'),
(19, NULL, 'app', 'm160707_000001_rename_richtext_assetsource_setting', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'd4c473a6-b7c1-4740-a2c7-9b4381817102'),
(20, NULL, 'app', 'm160708_185142_volume_hasUrls_setting', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '1bbce4f5-a7c7-4174-a9c9-129e64f71d1c'),
(21, NULL, 'app', 'm160714_000000_increase_max_asset_filesize', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '6198e103-efb8-401d-8ed2-8ffef16c1ce4'),
(22, NULL, 'app', 'm160727_194637_column_cleanup', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '497ed39c-15bb-47ac-9982-992c23d34d6e'),
(23, NULL, 'app', 'm160804_110002_userphotos_to_assets', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '67057e97-e569-4f9d-a578-84027fd5e8b8'),
(24, NULL, 'app', 'm160807_144858_sites', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '4086dd8f-5efb-4fa2-be6e-3de8d7c883b6'),
(25, NULL, 'app', 'm160829_000000_pending_user_content_cleanup', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'dc71ad66-22a1-4781-837f-00de927d59c4'),
(26, NULL, 'app', 'm160830_000000_asset_index_uri_increase', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2736f6d5-dc22-4c2b-9f38-9aac8bcc5184'),
(27, NULL, 'app', 'm160912_230520_require_entry_type_id', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '5ab0b151-2028-4316-b012-02a723e43b15'),
(28, NULL, 'app', 'm160913_134730_require_matrix_block_type_id', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '36da316b-8568-46af-8601-e0af5294f898'),
(29, NULL, 'app', 'm160920_174553_matrixblocks_owner_site_id_nullable', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'f0d8afe0-78c8-496b-ad54-c22be31d55da'),
(30, NULL, 'app', 'm160920_231045_usergroup_handle_title_unique', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '19748b71-7e88-4f54-8e9f-32385183d8fe'),
(31, NULL, 'app', 'm160925_113941_route_uri_parts', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '4d4240a5-5524-4b0e-bdf3-d3f9d62ca6af'),
(32, NULL, 'app', 'm161006_205918_schemaVersion_not_null', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'eadb373f-2a44-4dc8-aeac-c136b10d3410'),
(33, NULL, 'app', 'm161007_130653_update_email_settings', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '09a2d379-bf9d-46e4-bbe7-70de89ebeb09'),
(34, NULL, 'app', 'm161013_175052_newParentId', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '22e376b1-d466-4881-87c4-459be7661909'),
(35, NULL, 'app', 'm161021_102916_fix_recent_entries_widgets', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2038c3c0-af08-42e9-95a5-cdb22aa40a5b'),
(36, NULL, 'app', 'm161021_182140_rename_get_help_widget', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '562b985a-f1a7-4423-8ede-91443ed3bf6d'),
(37, NULL, 'app', 'm161025_000000_fix_char_columns', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '0229bbdd-815d-4e2f-8b50-0f21ba963010'),
(38, NULL, 'app', 'm161029_124145_email_message_languages', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '54995d90-d510-4c0f-99a7-f9fecbab6755'),
(39, NULL, 'app', 'm161108_000000_new_version_format', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '73beaa3c-1408-4bf6-9934-d347a21a0baf'),
(40, NULL, 'app', 'm161109_000000_index_shuffle', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '6e8635cb-6f99-4d03-8073-c2ea09d98a3b'),
(41, NULL, 'app', 'm161122_185500_no_craft_app', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '561abe9a-8783-4e06-903f-edfce7967a20'),
(42, NULL, 'app', 'm161125_150752_clear_urlmanager_cache', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'f8417848-dbca-4a9d-925c-70c951a6daf5'),
(43, NULL, 'app', 'm161220_000000_volumes_hasurl_notnull', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'c3b9a709-e45a-4ded-9682-ecdf4e9749bd'),
(44, NULL, 'app', 'm170114_161144_udates_permission', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '6c28ff12-a995-47a6-bcac-35d3803bf061'),
(45, NULL, 'app', 'm170120_000000_schema_cleanup', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '49fcdde3-6a1f-4eb5-bf87-ef19287ace33'),
(46, NULL, 'app', 'm170126_000000_assets_focal_point', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '743d0499-daba-469e-ac59-014b69381833'),
(47, NULL, 'app', 'm170206_142126_system_name', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'c8a0749a-a6b5-41a4-afe6-4e0f0fbe6f7f'),
(48, NULL, 'app', 'm170217_044740_category_branch_limits', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'ecac4112-44e8-47cb-a9a6-19edcab96569'),
(49, NULL, 'app', 'm170217_120224_asset_indexing_columns', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'b093d78a-69c1-4814-a85a-c751340ffe01'),
(50, NULL, 'app', 'm170223_224012_plain_text_settings', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '6a463678-38ae-4d89-94cf-2ac8b7252921'),
(51, NULL, 'app', 'm170227_120814_focal_point_percentage', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'c566528d-d3d7-4b3d-9f10-ccdf7dd37bb7'),
(52, NULL, 'app', 'm170228_171113_system_messages', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '5926201b-ca82-4dc5-b1a5-09b6349aa4ef'),
(53, NULL, 'app', 'm170303_140500_asset_field_source_settings', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'bbefc804-d2fa-4d41-afec-6b692c67caa4'),
(54, NULL, 'app', 'm170306_150500_asset_temporary_uploads', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '5297fdb7-f29e-48d4-a529-6d4f94c8d619'),
(55, NULL, 'app', 'm170414_162429_rich_text_config_setting', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'e916cd5a-c7d0-46ef-9022-d98577788eda'),
(56, NULL, 'app', 'm170523_190652_element_field_layout_ids', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'b026f281-226b-4489-95dd-f8f4fcacc97c'),
(57, NULL, 'app', 'm170612_000000_route_index_shuffle', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '86802629-5694-4db3-ba5d-85430844cf81'),
(58, NULL, 'app', 'm170621_195237_format_plugin_handles', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'dbbf96e7-f9bd-4f57-8192-333bf3d5753e'),
(59, NULL, 'app', 'm170630_161028_deprecation_changes', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'c4466620-6147-47ac-b067-75c591301af1'),
(60, NULL, 'app', 'm170703_181539_plugins_table_tweaks', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'ee24d490-20ff-4c1d-a19a-f270b887b2c5'),
(61, NULL, 'app', 'm170704_134916_sites_tables', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '208c842e-24d2-431e-9b8e-c11f09837cb6'),
(62, NULL, 'app', 'm170706_183216_rename_sequences', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '34d74d72-a301-4678-81e4-f91ba399e8ab'),
(63, NULL, 'app', 'm170707_094758_delete_compiled_traits', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '1b5079af-6cc5-44b9-a8ab-0a5639f77c4c'),
(64, NULL, 'app', 'm170731_190138_drop_asset_packagist', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'b3b27aee-40bd-4d29-810a-e35a7d0090b4'),
(65, NULL, 'app', 'm170810_201318_create_queue_table', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '8b90e5dc-9422-4c17-b13c-b5fe984e05d5'),
(66, NULL, 'app', 'm170816_133741_delete_compiled_behaviors', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '5e6cc5e9-043f-45ba-b848-620911d9a858'),
(67, NULL, 'app', 'm170821_180624_deprecation_line_nullable', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '428df900-52d9-4b50-8843-15d61ed5ac91'),
(68, NULL, 'app', 'm170903_192801_longblob_for_queue_jobs', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '59533c14-ac72-4db2-8d6c-da7e08c94e4f'),
(69, NULL, 'app', 'm170914_204621_asset_cache_shuffle', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '47178f9e-68fe-4343-b3b1-56335befe49c'),
(70, NULL, 'app', 'm171011_214115_site_groups', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'e0089417-e409-4a00-9fe4-5c0e1314f3ad'),
(71, NULL, 'app', 'm171012_151440_primary_site', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'da518572-ce3b-44c4-902c-faf5f2f631a8'),
(72, NULL, 'app', 'm171013_142500_transform_interlace', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '7acb224f-272a-4de1-bbf3-dc4a5628de86'),
(73, NULL, 'app', 'm171016_092553_drop_position_select', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'f9080b9d-f032-44a6-a65a-477ed07e9d47'),
(74, NULL, 'app', 'm171016_221244_less_strict_translation_method', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '6ca080ab-d6d5-44c0-ad56-558bbea3bc18'),
(75, NULL, 'app', 'm171107_000000_assign_group_permissions', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '93186944-4cf4-4154-b2b8-033601a4c0f7'),
(76, NULL, 'app', 'm171117_000001_templatecache_index_tune', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '5b0f5020-2507-4b41-92a8-6b9558176730'),
(77, NULL, 'app', 'm171126_105927_disabled_plugins', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '6044650f-be6e-481e-9090-5a5295a1f85a'),
(78, NULL, 'app', 'm171130_214407_craftidtokens_table', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'fef468b2-bd5b-4893-a64a-4730a0779d1f'),
(79, NULL, 'app', 'm171202_004225_update_email_settings', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '35c02093-f7f5-43a0-a653-37ad83c9f9ee'),
(80, NULL, 'app', 'm171204_000001_templatecache_index_tune_deux', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '770c2f19-fe83-4e87-b120-8ed1c77ba58e'),
(81, NULL, 'app', 'm171205_130908_remove_craftidtokens_refreshtoken_column', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '0da171b1-683a-40dc-81a8-55370e52880e'),
(82, NULL, 'app', 'm171218_143135_longtext_query_column', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '6e6b03cb-6be5-4b57-ada2-a6b223145435'),
(83, NULL, 'app', 'm171231_055546_environment_variables_to_aliases', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '8f9bbce3-da0c-4f72-8e5c-44eb1f095544'),
(84, NULL, 'app', 'm180113_153740_drop_users_archived_column', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '59251c2c-0a57-4833-bbda-3eccac19f3d9'),
(85, NULL, 'app', 'm180122_213433_propagate_entries_setting', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'e09617cf-b07a-43f2-aea1-8a4960a0c0ae'),
(86, NULL, 'app', 'm180124_230459_fix_propagate_entries_values', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '3d7f187e-4dbd-422d-a44f-9666b3bc27a0'),
(87, NULL, 'app', 'm180128_235202_set_tag_slugs', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '25388f9c-29ac-4f56-827f-23f7a2ad7976'),
(88, NULL, 'app', 'm180202_185551_fix_focal_points', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '5386e048-1292-4f67-8283-c06a46019507'),
(89, NULL, 'app', 'm180217_172123_tiny_ints', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '1a157150-6f97-4389-96a1-56d9647c421e'),
(90, NULL, 'app', 'm180321_233505_small_ints', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '054bcfc6-2120-4b7e-94a8-9ad102af669e'),
(91, NULL, 'app', 'm180328_115523_new_license_key_statuses', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '0860038f-93b0-499f-ba23-74d6276a3efb'),
(92, NULL, 'app', 'm180404_182320_edition_changes', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '7687b327-b5ed-4e79-938b-1b9ce31727a6'),
(93, NULL, 'app', 'm180411_102218_fix_db_routes', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '48e4b4d4-d0ec-40c9-ba56-b493f506a8b8'),
(94, NULL, 'app', 'm180416_205628_resourcepaths_table', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'cb889336-10a5-489f-830e-fe46e7f1d20f'),
(95, NULL, 'app', 'm180418_205713_widget_cleanup', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '5c8d7e7a-b397-4f4a-8d55-e513d47eb3f5'),
(96, NULL, 'app', 'm180824_193422_case_sensitivity_fixes', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'd63ffc00-90fa-4cec-85a5-5751ff528793'),
(97, NULL, 'app', 'm180901_151639_fix_matrixcontent_tables', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2018-10-08 18:33:25', '2ff66813-0195-4302-8f25-1db584166223');

-- --------------------------------------------------------

--
-- Table structure for table `craft_plugins`
--

CREATE TABLE `craft_plugins` (
  `id` int(11) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `version` varchar(255) NOT NULL,
  `schemaVersion` varchar(255) NOT NULL,
  `licenseKey` char(24) DEFAULT NULL,
  `licenseKeyStatus` enum('valid','invalid','mismatched','astray','unknown') NOT NULL DEFAULT 'unknown',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `settings` text,
  `installDate` datetime NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_queue`
--

CREATE TABLE `craft_queue` (
  `id` int(11) NOT NULL,
  `job` longblob NOT NULL,
  `description` text,
  `timePushed` int(11) NOT NULL,
  `ttr` int(11) NOT NULL,
  `delay` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) UNSIGNED NOT NULL DEFAULT '1024',
  `dateReserved` datetime DEFAULT NULL,
  `timeUpdated` int(11) DEFAULT NULL,
  `progress` smallint(6) NOT NULL DEFAULT '0',
  `attempt` int(11) DEFAULT NULL,
  `fail` tinyint(1) DEFAULT '0',
  `dateFailed` datetime DEFAULT NULL,
  `error` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_relations`
--

CREATE TABLE `craft_relations` (
  `id` int(11) NOT NULL,
  `fieldId` int(11) NOT NULL,
  `sourceId` int(11) NOT NULL,
  `sourceSiteId` int(11) DEFAULT NULL,
  `targetId` int(11) NOT NULL,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_resourcepaths`
--

CREATE TABLE `craft_resourcepaths` (
  `hash` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_routes`
--

CREATE TABLE `craft_routes` (
  `id` int(11) NOT NULL,
  `siteId` int(11) DEFAULT NULL,
  `uriParts` varchar(255) NOT NULL,
  `uriPattern` varchar(255) NOT NULL,
  `template` varchar(500) NOT NULL,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_searchindex`
--

CREATE TABLE `craft_searchindex` (
  `elementId` int(11) NOT NULL,
  `attribute` varchar(25) NOT NULL,
  `fieldId` int(11) NOT NULL,
  `siteId` int(11) NOT NULL,
  `keywords` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_searchindex`
--

INSERT INTO `craft_searchindex` (`elementId`, `attribute`, `fieldId`, `siteId`, `keywords`) VALUES
(1, 'username', 0, 1, ' admin '),
(1, 'firstname', 0, 1, ''),
(1, 'lastname', 0, 1, ''),
(1, 'fullname', 0, 1, ''),
(1, 'email', 0, 1, ' t t com '),
(1, 'slug', 0, 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `craft_sections`
--

CREATE TABLE `craft_sections` (
  `id` int(11) NOT NULL,
  `structureId` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `type` enum('single','channel','structure') NOT NULL DEFAULT 'channel',
  `enableVersioning` tinyint(1) NOT NULL DEFAULT '0',
  `propagateEntries` tinyint(1) NOT NULL DEFAULT '1',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_sections_sites`
--

CREATE TABLE `craft_sections_sites` (
  `id` int(11) NOT NULL,
  `sectionId` int(11) NOT NULL,
  `siteId` int(11) NOT NULL,
  `hasUrls` tinyint(1) NOT NULL DEFAULT '1',
  `uriFormat` text,
  `template` varchar(500) DEFAULT NULL,
  `enabledByDefault` tinyint(1) NOT NULL DEFAULT '1',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_sessions`
--

CREATE TABLE `craft_sessions` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `token` char(100) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_shunnedmessages`
--

CREATE TABLE `craft_shunnedmessages` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `expiryDate` datetime DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_sitegroups`
--

CREATE TABLE `craft_sitegroups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_sitegroups`
--

INSERT INTO `craft_sitegroups` (`id`, `name`, `dateCreated`, `dateUpdated`, `uid`) VALUES
(1, 'test', '2018-10-08 18:33:23', '2018-10-08 18:33:23', '1d4492d9-e731-479f-93a8-10b489a83bb1');

-- --------------------------------------------------------

--
-- Table structure for table `craft_sites`
--

CREATE TABLE `craft_sites` (
  `id` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `primary` tinyint(1) NOT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `language` varchar(12) NOT NULL,
  `hasUrls` tinyint(1) NOT NULL DEFAULT '0',
  `baseUrl` varchar(255) DEFAULT NULL,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_sites`
--

INSERT INTO `craft_sites` (`id`, `groupId`, `primary`, `name`, `handle`, `language`, `hasUrls`, `baseUrl`, `sortOrder`, `dateCreated`, `dateUpdated`, `uid`) VALUES
(1, 1, 1, 'test', 'default', 'en-US', 1, '@web', 1, '2018-10-08 00:00:00', '2018-10-08 00:00:00', '6853c99a-ff7b-4bbf-bfee-eb858aed508b');

-- --------------------------------------------------------

--
-- Table structure for table `craft_structureelements`
--

CREATE TABLE `craft_structureelements` (
  `id` int(11) NOT NULL,
  `structureId` int(11) NOT NULL,
  `elementId` int(11) DEFAULT NULL,
  `root` int(11) UNSIGNED DEFAULT NULL,
  `lft` int(11) UNSIGNED NOT NULL,
  `rgt` int(11) UNSIGNED NOT NULL,
  `level` smallint(6) UNSIGNED NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_structures`
--

CREATE TABLE `craft_structures` (
  `id` int(11) NOT NULL,
  `maxLevels` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_systemmessages`
--

CREATE TABLE `craft_systemmessages` (
  `id` int(11) NOT NULL,
  `language` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_systemsettings`
--

CREATE TABLE `craft_systemsettings` (
  `id` int(11) NOT NULL,
  `category` varchar(15) NOT NULL,
  `settings` text,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_systemsettings`
--

INSERT INTO `craft_systemsettings` (`id`, `category`, `settings`, `dateCreated`, `dateUpdated`, `uid`) VALUES
(1, 'email', '{\"fromEmail\":\"t@t.com\",\"fromName\":\"test\",\"transportType\":\"craft\\\\mail\\\\transportadapters\\\\Sendmail\"}', '2018-10-08 18:33:25', '2018-10-08 18:33:25', 'f193764b-325e-400e-acaf-dd76a33e9b18');

-- --------------------------------------------------------

--
-- Table structure for table `craft_taggroups`
--

CREATE TABLE `craft_taggroups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `fieldLayoutId` int(11) DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_tags`
--

CREATE TABLE `craft_tags` (
  `id` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_templatecacheelements`
--

CREATE TABLE `craft_templatecacheelements` (
  `cacheId` int(11) NOT NULL,
  `elementId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_templatecachequeries`
--

CREATE TABLE `craft_templatecachequeries` (
  `id` int(11) NOT NULL,
  `cacheId` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `query` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_templatecaches`
--

CREATE TABLE `craft_templatecaches` (
  `id` int(11) NOT NULL,
  `siteId` int(11) NOT NULL,
  `cacheKey` varchar(255) NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `expiryDate` datetime NOT NULL,
  `body` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_tokens`
--

CREATE TABLE `craft_tokens` (
  `id` int(11) NOT NULL,
  `token` char(32) NOT NULL,
  `route` text,
  `usageLimit` tinyint(3) UNSIGNED DEFAULT NULL,
  `usageCount` tinyint(3) UNSIGNED DEFAULT NULL,
  `expiryDate` datetime NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_usergroups`
--

CREATE TABLE `craft_usergroups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_usergroups_users`
--

CREATE TABLE `craft_usergroups_users` (
  `id` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_userpermissions`
--

CREATE TABLE `craft_userpermissions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_userpermissions_usergroups`
--

CREATE TABLE `craft_userpermissions_usergroups` (
  `id` int(11) NOT NULL,
  `permissionId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_userpermissions_users`
--

CREATE TABLE `craft_userpermissions_users` (
  `id` int(11) NOT NULL,
  `permissionId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_userpreferences`
--

CREATE TABLE `craft_userpreferences` (
  `userId` int(11) NOT NULL,
  `preferences` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `craft_userpreferences`
--

INSERT INTO `craft_userpreferences` (`userId`, `preferences`) VALUES
(1, '{\"language\":\"en-US\"}');

-- --------------------------------------------------------

--
-- Table structure for table `craft_users`
--

CREATE TABLE `craft_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `photoId` int(11) DEFAULT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `suspended` tinyint(1) NOT NULL DEFAULT '0',
  `pending` tinyint(1) NOT NULL DEFAULT '0',
  `lastLoginDate` datetime DEFAULT NULL,
  `lastLoginAttemptIp` varchar(45) DEFAULT NULL,
  `invalidLoginWindowStart` datetime DEFAULT NULL,
  `invalidLoginCount` tinyint(3) UNSIGNED DEFAULT NULL,
  `lastInvalidLoginDate` datetime DEFAULT NULL,
  `lockoutDate` datetime DEFAULT NULL,
  `hasDashboard` tinyint(1) NOT NULL DEFAULT '0',
  `verificationCode` varchar(255) DEFAULT NULL,
  `verificationCodeIssuedDate` datetime DEFAULT NULL,
  `unverifiedEmail` varchar(255) DEFAULT NULL,
  `passwordResetRequired` tinyint(1) NOT NULL DEFAULT '0',
  `lastPasswordChangeDate` datetime DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_volumefolders`
--

CREATE TABLE `craft_volumefolders` (
  `id` int(11) NOT NULL,
  `parentId` int(11) DEFAULT NULL,
  `volumeId` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_volumes`
--

CREATE TABLE `craft_volumes` (
  `id` int(11) NOT NULL,
  `fieldLayoutId` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `hasUrls` tinyint(1) NOT NULL DEFAULT '1',
  `url` varchar(255) DEFAULT NULL,
  `settings` text,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `craft_widgets`
--

CREATE TABLE `craft_widgets` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `sortOrder` smallint(6) UNSIGNED DEFAULT NULL,
  `colspan` tinyint(1) NOT NULL DEFAULT '0',
  `settings` text,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `dateCreated` datetime NOT NULL,
  `dateUpdated` datetime NOT NULL,
  `uid` char(36) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `craft_assetindexdata`
--
ALTER TABLE `craft_assetindexdata`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_assetindexdata_sessionId_volumeId_idx` (`sessionId`,`volumeId`),
  ADD KEY `craft_assetindexdata_volumeId_idx` (`volumeId`);

--
-- Indexes for table `craft_assets`
--
ALTER TABLE `craft_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_assets_filename_folderId_unq_idx` (`filename`,`folderId`),
  ADD KEY `craft_assets_folderId_idx` (`folderId`),
  ADD KEY `craft_assets_volumeId_idx` (`volumeId`);

--
-- Indexes for table `craft_assettransformindex`
--
ALTER TABLE `craft_assettransformindex`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_assettransformindex_volumeId_assetId_location_idx` (`volumeId`,`assetId`,`location`);

--
-- Indexes for table `craft_assettransforms`
--
ALTER TABLE `craft_assettransforms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_assettransforms_name_unq_idx` (`name`),
  ADD UNIQUE KEY `craft_assettransforms_handle_unq_idx` (`handle`);

--
-- Indexes for table `craft_categories`
--
ALTER TABLE `craft_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_categories_groupId_idx` (`groupId`);

--
-- Indexes for table `craft_categorygroups`
--
ALTER TABLE `craft_categorygroups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_categorygroups_name_unq_idx` (`name`),
  ADD UNIQUE KEY `craft_categorygroups_handle_unq_idx` (`handle`),
  ADD KEY `craft_categorygroups_structureId_idx` (`structureId`),
  ADD KEY `craft_categorygroups_fieldLayoutId_idx` (`fieldLayoutId`);

--
-- Indexes for table `craft_categorygroups_sites`
--
ALTER TABLE `craft_categorygroups_sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_categorygroups_sites_groupId_siteId_unq_idx` (`groupId`,`siteId`),
  ADD KEY `craft_categorygroups_sites_siteId_idx` (`siteId`);

--
-- Indexes for table `craft_content`
--
ALTER TABLE `craft_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_content_elementId_siteId_unq_idx` (`elementId`,`siteId`),
  ADD KEY `craft_content_siteId_idx` (`siteId`),
  ADD KEY `craft_content_title_idx` (`title`);

--
-- Indexes for table `craft_craftidtokens`
--
ALTER TABLE `craft_craftidtokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_craftidtokens_userId_fk` (`userId`);

--
-- Indexes for table `craft_deprecationerrors`
--
ALTER TABLE `craft_deprecationerrors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_deprecationerrors_key_fingerprint_unq_idx` (`key`,`fingerprint`);

--
-- Indexes for table `craft_elementindexsettings`
--
ALTER TABLE `craft_elementindexsettings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_elementindexsettings_type_unq_idx` (`type`);

--
-- Indexes for table `craft_elements`
--
ALTER TABLE `craft_elements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_elements_fieldLayoutId_idx` (`fieldLayoutId`),
  ADD KEY `craft_elements_type_idx` (`type`),
  ADD KEY `craft_elements_enabled_idx` (`enabled`),
  ADD KEY `craft_elements_archived_dateCreated_idx` (`archived`,`dateCreated`);

--
-- Indexes for table `craft_elements_sites`
--
ALTER TABLE `craft_elements_sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_elements_sites_elementId_siteId_unq_idx` (`elementId`,`siteId`),
  ADD KEY `craft_elements_sites_siteId_idx` (`siteId`),
  ADD KEY `craft_elements_sites_slug_siteId_idx` (`slug`,`siteId`),
  ADD KEY `craft_elements_sites_enabled_idx` (`enabled`),
  ADD KEY `craft_elements_sites_uri_siteId_idx` (`uri`,`siteId`);

--
-- Indexes for table `craft_entries`
--
ALTER TABLE `craft_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_entries_postDate_idx` (`postDate`),
  ADD KEY `craft_entries_expiryDate_idx` (`expiryDate`),
  ADD KEY `craft_entries_authorId_idx` (`authorId`),
  ADD KEY `craft_entries_sectionId_idx` (`sectionId`),
  ADD KEY `craft_entries_typeId_idx` (`typeId`);

--
-- Indexes for table `craft_entrydrafts`
--
ALTER TABLE `craft_entrydrafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_entrydrafts_sectionId_idx` (`sectionId`),
  ADD KEY `craft_entrydrafts_entryId_siteId_idx` (`entryId`,`siteId`),
  ADD KEY `craft_entrydrafts_siteId_idx` (`siteId`),
  ADD KEY `craft_entrydrafts_creatorId_idx` (`creatorId`);

--
-- Indexes for table `craft_entrytypes`
--
ALTER TABLE `craft_entrytypes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_entrytypes_name_sectionId_unq_idx` (`name`,`sectionId`),
  ADD UNIQUE KEY `craft_entrytypes_handle_sectionId_unq_idx` (`handle`,`sectionId`),
  ADD KEY `craft_entrytypes_sectionId_idx` (`sectionId`),
  ADD KEY `craft_entrytypes_fieldLayoutId_idx` (`fieldLayoutId`);

--
-- Indexes for table `craft_entryversions`
--
ALTER TABLE `craft_entryversions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_entryversions_sectionId_idx` (`sectionId`),
  ADD KEY `craft_entryversions_entryId_siteId_idx` (`entryId`,`siteId`),
  ADD KEY `craft_entryversions_siteId_idx` (`siteId`),
  ADD KEY `craft_entryversions_creatorId_idx` (`creatorId`);

--
-- Indexes for table `craft_fieldgroups`
--
ALTER TABLE `craft_fieldgroups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_fieldgroups_name_unq_idx` (`name`);

--
-- Indexes for table `craft_fieldlayoutfields`
--
ALTER TABLE `craft_fieldlayoutfields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_fieldlayoutfields_layoutId_fieldId_unq_idx` (`layoutId`,`fieldId`),
  ADD KEY `craft_fieldlayoutfields_sortOrder_idx` (`sortOrder`),
  ADD KEY `craft_fieldlayoutfields_tabId_idx` (`tabId`),
  ADD KEY `craft_fieldlayoutfields_fieldId_idx` (`fieldId`);

--
-- Indexes for table `craft_fieldlayouts`
--
ALTER TABLE `craft_fieldlayouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_fieldlayouts_type_idx` (`type`);

--
-- Indexes for table `craft_fieldlayouttabs`
--
ALTER TABLE `craft_fieldlayouttabs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_fieldlayouttabs_sortOrder_idx` (`sortOrder`),
  ADD KEY `craft_fieldlayouttabs_layoutId_idx` (`layoutId`);

--
-- Indexes for table `craft_fields`
--
ALTER TABLE `craft_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_fields_handle_context_unq_idx` (`handle`,`context`),
  ADD KEY `craft_fields_groupId_idx` (`groupId`),
  ADD KEY `craft_fields_context_idx` (`context`);

--
-- Indexes for table `craft_globalsets`
--
ALTER TABLE `craft_globalsets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_globalsets_name_unq_idx` (`name`),
  ADD UNIQUE KEY `craft_globalsets_handle_unq_idx` (`handle`),
  ADD KEY `craft_globalsets_fieldLayoutId_idx` (`fieldLayoutId`);

--
-- Indexes for table `craft_info`
--
ALTER TABLE `craft_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `craft_matrixblocks`
--
ALTER TABLE `craft_matrixblocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_matrixblocks_ownerId_idx` (`ownerId`),
  ADD KEY `craft_matrixblocks_fieldId_idx` (`fieldId`),
  ADD KEY `craft_matrixblocks_typeId_idx` (`typeId`),
  ADD KEY `craft_matrixblocks_sortOrder_idx` (`sortOrder`),
  ADD KEY `craft_matrixblocks_ownerSiteId_idx` (`ownerSiteId`);

--
-- Indexes for table `craft_matrixblocktypes`
--
ALTER TABLE `craft_matrixblocktypes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_matrixblocktypes_name_fieldId_unq_idx` (`name`,`fieldId`),
  ADD UNIQUE KEY `craft_matrixblocktypes_handle_fieldId_unq_idx` (`handle`,`fieldId`),
  ADD KEY `craft_matrixblocktypes_fieldId_idx` (`fieldId`),
  ADD KEY `craft_matrixblocktypes_fieldLayoutId_idx` (`fieldLayoutId`);

--
-- Indexes for table `craft_migrations`
--
ALTER TABLE `craft_migrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_migrations_pluginId_idx` (`pluginId`),
  ADD KEY `craft_migrations_type_pluginId_idx` (`type`,`pluginId`);

--
-- Indexes for table `craft_plugins`
--
ALTER TABLE `craft_plugins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_plugins_handle_unq_idx` (`handle`),
  ADD KEY `craft_plugins_enabled_idx` (`enabled`);

--
-- Indexes for table `craft_queue`
--
ALTER TABLE `craft_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_queue_fail_timeUpdated_timePushed_idx` (`fail`,`timeUpdated`,`timePushed`),
  ADD KEY `craft_queue_fail_timeUpdated_delay_idx` (`fail`,`timeUpdated`,`delay`);

--
-- Indexes for table `craft_relations`
--
ALTER TABLE `craft_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_relations_fieldId_sourceId_sourceSiteId_targetId_unq_idx` (`fieldId`,`sourceId`,`sourceSiteId`,`targetId`),
  ADD KEY `craft_relations_sourceId_idx` (`sourceId`),
  ADD KEY `craft_relations_targetId_idx` (`targetId`),
  ADD KEY `craft_relations_sourceSiteId_idx` (`sourceSiteId`);

--
-- Indexes for table `craft_resourcepaths`
--
ALTER TABLE `craft_resourcepaths`
  ADD PRIMARY KEY (`hash`);

--
-- Indexes for table `craft_routes`
--
ALTER TABLE `craft_routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_routes_uriPattern_idx` (`uriPattern`),
  ADD KEY `craft_routes_siteId_idx` (`siteId`);

--
-- Indexes for table `craft_searchindex`
--
ALTER TABLE `craft_searchindex`
  ADD PRIMARY KEY (`elementId`,`attribute`,`fieldId`,`siteId`);
ALTER TABLE `craft_searchindex` ADD FULLTEXT KEY `craft_searchindex_keywords_idx` (`keywords`);

--
-- Indexes for table `craft_sections`
--
ALTER TABLE `craft_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_sections_handle_unq_idx` (`handle`),
  ADD UNIQUE KEY `craft_sections_name_unq_idx` (`name`),
  ADD KEY `craft_sections_structureId_idx` (`structureId`);

--
-- Indexes for table `craft_sections_sites`
--
ALTER TABLE `craft_sections_sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_sections_sites_sectionId_siteId_unq_idx` (`sectionId`,`siteId`),
  ADD KEY `craft_sections_sites_siteId_idx` (`siteId`);

--
-- Indexes for table `craft_sessions`
--
ALTER TABLE `craft_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_sessions_uid_idx` (`uid`),
  ADD KEY `craft_sessions_token_idx` (`token`),
  ADD KEY `craft_sessions_dateUpdated_idx` (`dateUpdated`),
  ADD KEY `craft_sessions_userId_idx` (`userId`);

--
-- Indexes for table `craft_shunnedmessages`
--
ALTER TABLE `craft_shunnedmessages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_shunnedmessages_userId_message_unq_idx` (`userId`,`message`);

--
-- Indexes for table `craft_sitegroups`
--
ALTER TABLE `craft_sitegroups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_sitegroups_name_unq_idx` (`name`);

--
-- Indexes for table `craft_sites`
--
ALTER TABLE `craft_sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_sites_handle_unq_idx` (`handle`),
  ADD KEY `craft_sites_sortOrder_idx` (`sortOrder`),
  ADD KEY `craft_sites_groupId_fk` (`groupId`);

--
-- Indexes for table `craft_structureelements`
--
ALTER TABLE `craft_structureelements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_structureelements_structureId_elementId_unq_idx` (`structureId`,`elementId`),
  ADD KEY `craft_structureelements_root_idx` (`root`),
  ADD KEY `craft_structureelements_lft_idx` (`lft`),
  ADD KEY `craft_structureelements_rgt_idx` (`rgt`),
  ADD KEY `craft_structureelements_level_idx` (`level`),
  ADD KEY `craft_structureelements_elementId_idx` (`elementId`);

--
-- Indexes for table `craft_structures`
--
ALTER TABLE `craft_structures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `craft_systemmessages`
--
ALTER TABLE `craft_systemmessages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_systemmessages_key_language_unq_idx` (`key`,`language`),
  ADD KEY `craft_systemmessages_language_idx` (`language`);

--
-- Indexes for table `craft_systemsettings`
--
ALTER TABLE `craft_systemsettings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_systemsettings_category_unq_idx` (`category`);

--
-- Indexes for table `craft_taggroups`
--
ALTER TABLE `craft_taggroups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_taggroups_name_unq_idx` (`name`),
  ADD UNIQUE KEY `craft_taggroups_handle_unq_idx` (`handle`),
  ADD KEY `craft_taggroups_fieldLayoutId_fk` (`fieldLayoutId`);

--
-- Indexes for table `craft_tags`
--
ALTER TABLE `craft_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_tags_groupId_idx` (`groupId`);

--
-- Indexes for table `craft_templatecacheelements`
--
ALTER TABLE `craft_templatecacheelements`
  ADD KEY `craft_templatecacheelements_cacheId_idx` (`cacheId`),
  ADD KEY `craft_templatecacheelements_elementId_idx` (`elementId`);

--
-- Indexes for table `craft_templatecachequeries`
--
ALTER TABLE `craft_templatecachequeries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_templatecachequeries_cacheId_idx` (`cacheId`),
  ADD KEY `craft_templatecachequeries_type_idx` (`type`);

--
-- Indexes for table `craft_templatecaches`
--
ALTER TABLE `craft_templatecaches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `craft_templatecaches_cacheKey_siteId_expiryDate_path_idx` (`cacheKey`,`siteId`,`expiryDate`,`path`),
  ADD KEY `craft_templatecaches_cacheKey_siteId_expiryDate_idx` (`cacheKey`,`siteId`,`expiryDate`),
  ADD KEY `craft_templatecaches_siteId_idx` (`siteId`);

--
-- Indexes for table `craft_tokens`
--
ALTER TABLE `craft_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_tokens_token_unq_idx` (`token`),
  ADD KEY `craft_tokens_expiryDate_idx` (`expiryDate`);

--
-- Indexes for table `craft_usergroups`
--
ALTER TABLE `craft_usergroups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_usergroups_handle_unq_idx` (`handle`),
  ADD UNIQUE KEY `craft_usergroups_name_unq_idx` (`name`);

--
-- Indexes for table `craft_usergroups_users`
--
ALTER TABLE `craft_usergroups_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_usergroups_users_groupId_userId_unq_idx` (`groupId`,`userId`),
  ADD KEY `craft_usergroups_users_userId_idx` (`userId`);

--
-- Indexes for table `craft_userpermissions`
--
ALTER TABLE `craft_userpermissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_userpermissions_name_unq_idx` (`name`);

--
-- Indexes for table `craft_userpermissions_usergroups`
--
ALTER TABLE `craft_userpermissions_usergroups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_userpermissions_usergroups_permissionId_groupId_unq_idx` (`permissionId`,`groupId`),
  ADD KEY `craft_userpermissions_usergroups_groupId_idx` (`groupId`);

--
-- Indexes for table `craft_userpermissions_users`
--
ALTER TABLE `craft_userpermissions_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `craft_userpermissions_users_permissionId_userId_unq_idx` (`permissionId`,`userId`),
  ADD KEY `craft_userpermissions_users_userId_idx` (`userId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
