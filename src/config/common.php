<?php

Yii::setPathOfAlias('app', BLOCKS_APP_PATH);
Yii::setPathOfAlias('plugins', BLOCKS_PLUGINS_PATH);

// Load the configs
$generalConfig = require_once(BLOCKS_APP_PATH.'config/defaults/general.php');
$dbConfig = require_once(BLOCKS_APP_PATH.'config/defaults/db.php');

if (file_exists(BLOCKS_CONFIG_PATH.'general.php'))
{
	if (is_array($_generalConfig = require_once(BLOCKS_CONFIG_PATH.'general.php')))
	{
		$generalConfig = array_merge($generalConfig, $_generalConfig);
	}
}
else if (file_exists(BLOCKS_CONFIG_PATH.'blocks.php'))
{
	if (is_array($_generalConfig = require_once(BLOCKS_CONFIG_PATH.'blocks.php')))
	{
		$generalConfig = array_merge($generalConfig, $_generalConfig);
	}
	else if (isset($blocksConfig))
	{
		$generalConfig = array_merge($generalConfig, $blocksConfig);
		unset($blocksConfig);
	}
}

if (is_array($_dbConfig = require_once(BLOCKS_CONFIG_PATH.'db.php')))
{
	$dbConfig = array_merge($dbConfig, $_dbConfig);
}

if ($generalConfig['devMode'] == true)
{
	defined('YII_DEBUG') || define('YII_DEBUG', true);
	error_reporting(E_ALL & ~E_STRICT);
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
	ini_set('error_log', BLOCKS_STORAGE_PATH.'runtime/logs/phperrors.log');
}
else
{
	error_reporting(0);
	ini_set('display_errors', 0);
}

// Table prefixes cannot be longer than 5 characters
$tablePrefix = rtrim($dbConfig['tablePrefix'], '_');
if ($tablePrefix)
{
	if (strlen($tablePrefix) > 5)
	{
		$tablePrefix = substr($tablePrefix, 0, 5);
	}

	$tablePrefix .= '_';
}

$packages = explode(',', BLOCKS_PACKAGES);

$configArray = array(

	// autoloading model and component classes
	'import' => array(
		'application.framework.cli.commands.*',
		'application.framework.console.*',
		'application.framework.logging.CLogger',
	),

	'componentAliases' => array(
/* COMPONENT ALIASES */
/* REBRAND COMPONENT ALIASES */
/* PUBLISHPRO COMPONENT ALIASES */
/* CLOUD COMPONENT ALIASES */
/* LANGUAGE COMPONENT ALIASES */
/* USERS COMPONENT ALIASES */
		),

	'components' => array(

		'db' => array(
			'connectionString'  => strtolower('mysql:host='.$dbConfig['server'].';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';'),
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => $tablePrefix,
			'driverMap'         => array('mysql' => 'Blocks\MysqlSchema'),
			'class'             => 'Blocks\DbConnection',
			'pdoClass'          => 'Blocks\PDO',
		),

		'config' => array(
			'class' => 'Blocks\ConfigService',
		),

		'i18n' => array(
			'class' => 'Blocks\LocalizationService',
		),

		'formatter' => array(
			'class' => '\CFormatter'
		),
	),

	'params' => array(
		'adminEmail'            => 'admin@website.com',
		'dbConfig'              => $dbConfig,
		'generalConfig'         => $generalConfig,
	)
);

// -------------------------------------------
//  CP routes
// -------------------------------------------

$cpRoutes['content']                                                          = 'content/entries/index';

$cpRoutes['content\/singletons']                                              = 'content/singletons';
$cpRoutes['content\/singletons\/(?P<singletonId>\d+)']                        = 'content/singletons/_edit';

$cpRoutes['content\/globals']                                                 = 'content/globals';
$cpRoutes['content\/(?P<sectionHandle>{handle})\/new']                        = 'content/entries/_edit';
$cpRoutes['content\/(?P<sectionHandle>{handle})\/(?P<entryId>\d+)']           = 'content/entries/_edit';
$cpRoutes['content\/(?P<filter>{handle})']                                    = 'content/entries/index';

$cpRoutes['dashboard\/settings\/new']                                         = 'dashboard/settings/_widgetsettings';
$cpRoutes['dashboard\/settings\/(?P<widgetId>\d+)']                           = 'dashboard/settings/_widgetsettings';

$cpRoutes['updates\/go\/(?P<handle>[^\/]*)']                                  = 'updates/_go';

$cpRoutes['settings\/assets']                                                 = 'settings/assets/sources';
$cpRoutes['settings\/assets\/sources\/new']                                   = 'settings/assets/sources/_settings';
$cpRoutes['settings\/assets\/sources\/(?P<sourceId>\d+)']                     = 'settings/assets/sources/_settings';
$cpRoutes['settings\/assets\/transformations\/new']                           = 'settings/assets/transformations/_settings';
$cpRoutes['settings\/assets\/transformations\/(?P<handle>{handle})']          = 'settings/assets/transformations/_settings';
$cpRoutes['settings\/fields\/(?P<groupId>\d+)']                               = 'settings/fields';
$cpRoutes['settings\/fields\/new']                                            = 'settings/fields/_edit';
$cpRoutes['settings\/fields\/edit\/(?P<fieldId>\d+)']                         = 'settings/fields/_edit';
$cpRoutes['settings\/plugins\/(?P<pluginClass>{handle})']                     = 'settings/plugins/_settings';
$cpRoutes['settings\/sections\/new']                                          = 'settings/sections/_edit';
$cpRoutes['settings\/sections\/(?P<sectionId>\d+)']                           = 'settings/sections/_edit';
$cpRoutes['settings\/singletons\/new']                                        = 'settings/singletons/_edit';
$cpRoutes['settings\/singletons\/(?P<singletonId>\d+)']                       = 'settings/singletons/_edit';

$cpRoutes['myaccount']                                                        = 'users/_edit/account';

// Lanugage package routes
$cpRoutes['pkgRoutes']['Language']['content\/(?P<sectionHandle>{handle})\/(?P<entryId>\d+)\/(?P<localeId>\w+)'] = 'content/entries/_edit';
$cpRoutes['pkgRoutes']['Language']['content\/(?P<sectionHandle>{handle})\/new\/(?P<localeId>\w+)']              = 'content/entries/_edit';

// Publish Pro package routes
$cpRoutes['pkgRoutes']['PublishPro']['content\/(?P<sectionHandle>{handle})\/(?P<entryId>\d+)\/drafts\/(?P<draftId>\d+)']     = 'content/entries/_edit';
$cpRoutes['pkgRoutes']['PublishPro']['content\/(?P<sectionHandle>{handle})\/(?P<entryId>\d+)\/versions\/(?P<versionId>\d+)'] = 'content/entries/_edit';

// Users package routes
$cpRoutes['pkgRoutes']['Users']['myaccount\/profile']                        = 'users/_edit/profile';
$cpRoutes['pkgRoutes']['Users']['myaccount\/info']                           = 'users/_edit/info';
$cpRoutes['pkgRoutes']['Users']['myaccount\/admin']                          = 'users/_edit/admin';
$cpRoutes['pkgRoutes']['Users']['users\/new']                                = 'users/_edit/account';
$cpRoutes['pkgRoutes']['Users']['users\/(?P<filter>{handle})']               = 'users';
$cpRoutes['pkgRoutes']['Users']['users\/(?P<userId>\d+)']                    = 'users/_edit/account';
$cpRoutes['pkgRoutes']['Users']['users\/(?P<userId>\d+)\/profile']           = 'users/_edit/profile';
$cpRoutes['pkgRoutes']['Users']['users\/(?P<userId>\d+)\/admin']             = 'users/_edit/admin';
$cpRoutes['pkgRoutes']['Users']['users\/(?P<userId>\d+)\/info']              = 'users/_edit/info';
$cpRoutes['pkgRoutes']['Users']['settings\/users']                           = 'settings/users/groups';
$cpRoutes['pkgRoutes']['Users']['settings\/users\/groups\/new']              = 'settings/users/groups/_settings';
$cpRoutes['pkgRoutes']['Users']['settings\/users\/groups\/(?P<groupId>\d+)'] = 'settings/users/groups/_settings';

// -------------------------------------------
//  Component config
// -------------------------------------------

$components['users']['class']                = 'Blocks\UsersService';
$components['assets']['class']               = 'Blocks\AssetsService';
$components['assetTransformations']['class'] = 'Blocks\AssetTransformationsService';
$components['assetIndexing']['class']        = 'Blocks\AssetIndexingService';
$components['assetSources']['class']         = 'Blocks\AssetSourcesService';

$components['dashboard']['class']            = 'Blocks\DashboardService';
$components['email']['class']                = 'Blocks\EmailService';
$components['entries']['class']              = 'Blocks\EntriesService';
$components['et']['class']                   = 'Blocks\EtService';
$components['feeds']['class']                = 'Blocks\FeedsService';
$components['fields']['class']               = 'Blocks\FieldsService';
$components['fieldTypes']['class']           = 'Blocks\FieldTypesService';
$components['globals']['class']              = 'Blocks\GlobalsService';
$components['install']['class']              = 'Blocks\InstallService';
$components['images']['class']               = 'Blocks\ImagesService';
$components['links']['class']                = 'Blocks\LinksService';
$components['migrations']['class']           = 'Blocks\MigrationsService';
$components['path']['class']                 = 'Blocks\PathService';
$components['plugins']['class']              = 'Blocks\PluginsService';
$components['sections']['class']             = 'Blocks\SectionsService';
$components['singletons']['class']           = 'Blocks\SingletonsService';

$components['resources']['class']            = 'Blocks\ResourcesService';
$components['resources']['dateParam']        = 'd';

$components['routes']['class']               = 'Blocks\RoutesService';
$components['security']['class']             = 'Blocks\SecurityService';
$components['systemSettings']['class']       = 'Blocks\SystemSettingsService';
$components['templates']['class']            = 'Blocks\TemplatesService';
$components['updates']['class']              = 'Blocks\UpdatesService';

$components['components'] = array(
	'class' => 'Blocks\ComponentsService',
	'types' => array(
		'assetSource' => array('subfolder' => 'assetsourcetypes', 'suffix' => 'AssetSourceType', 'baseClass' => 'BaseAssetSourceType'),
		'entry'       => array('subfolder' => 'entrytypes', 'suffix' => 'EntryType', 'baseClass' => 'BaseEntryType'),
		'field'       => array('subfolder' => 'fieldtypes', 'suffix' => 'FieldType', 'baseClass' => 'BaseFieldType'),
		'widget'      => array('subfolder' => 'widgets', 'suffix' => 'Widget', 'baseClass' => 'BaseWidget'),
	)
);

// Publish Pro package components
$components['pkgComponents']['PublishPro']['entryRevisions']['class'] = 'Blocks\EntryRevisionsService';


// Users package components
$components['pkgComponents']['Users']['userGroups']['class']      = 'Blocks\UserGroupsService';
$components['pkgComponents']['Users']['userPermissions']['class'] = 'Blocks\UserPermissionsService';

// Rebrand package components
$components['pkgComponents']['Rebrand']['emailMessages']['class'] = 'Blocks\EmailMessagesService';

$components['file']['class'] = 'Blocks\File';
$components['messages']['class'] = 'Blocks\PhpMessageSource';
$components['request']['class'] = 'Blocks\HttpRequestService';
$components['request']['enableCookieValidation'] = true;
$components['viewRenderer']['class'] = 'Blocks\TemplateProcessor';
$components['statePersister']['class'] = 'Blocks\StatePersister';

$components['urlManager']['class'] = 'Blocks\UrlManager';
$components['urlManager']['cpRoutes'] = $cpRoutes;
$components['urlManager']['pathParam'] = 'p';

$components['assetManager']['basePath'] = dirname(__FILE__).'/../assets';
$components['assetManager']['baseUrl'] = '../../blocks/app/assets';

$components['errorHandler']['class'] = 'Blocks\ErrorHandler';

$components['fileCache']['class'] = 'CFileCache';

$components['log']['class'] = 'Blocks\LogRouter';
$components['log']['routes'] = array(
	array(
		'class'  => 'Blocks\FileLogRoute',
	),
	array(
		'class'         => 'Blocks\WebLogRoute',
		'filter'        => 'CLogFilter',
		'showInFireBug' => true,
	),
	array(
		'class'         => 'Blocks\ProfileLogRoute',
		'showInFireBug' => true,
	),
);

$components['httpSession']['autoStart']   = true;
$components['httpSession']['cookieMode']  = 'only';
$components['httpSession']['class']       = 'Blocks\HttpSessionService';
$components['httpSession']['sessionName'] = 'BlocksSessionId';

$components['userSession']['class'] = 'Blocks\UserSessionService';
$components['userSession']['allowAutoLogin']  = true;
$components['userSession']['loginUrl']        = $generalConfig['loginPath'];
$components['userSession']['autoRenewCookie'] = true;

$configArray['components'] = array_merge($configArray['components'], $components);

return $configArray;
