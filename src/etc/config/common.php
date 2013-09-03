<?php

Yii::setPathOfAlias('app', CRAFT_APP_PATH);
Yii::setPathOfAlias('plugins', CRAFT_PLUGINS_PATH);

// Load the deafult configs
$generalConfig = require_once(CRAFT_APP_PATH.'etc/config/defaults/general.php');
$dbConfig = require_once(CRAFT_APP_PATH.'etc/config/defaults/db.php');

/**
 * Merges a base config array with a custom config array,
 * taking environment-specific configs into account.
 *
 * @param array &$baseConfig
 * @param array $customConfig
 */
function mergeConfigs(&$baseConfig, $customConfig)
{
	// Is this a multi-environment config?
	if (array_key_exists('*', $customConfig))
	{
		foreach ($customConfig as $env => $envConfig)
		{
			if ($env == '*' || strpos(CRAFT_ENVIRONMENT, $env) !== false)
			{
				$baseConfig = array_merge($baseConfig, $envConfig);
			}
		}
	}
	else
	{
		$baseConfig = array_merge($baseConfig, $customConfig);
	}
}

// Does craft/config/general.php exist? (It used to be called blocks.php so maybe not.)
if (file_exists(CRAFT_CONFIG_PATH.'general.php'))
{
	if (is_array($customGeneralConfig = @include(CRAFT_CONFIG_PATH.'general.php')))
	{
		mergeConfigs($generalConfig, $customGeneralConfig);
	}
}
else if (file_exists(CRAFT_CONFIG_PATH.'blocks.php'))
{
	// Originally blocks.php defined a $blocksConfig variable, and then later returned an array directly.
	if (is_array($customGeneralConfig = require_once(CRAFT_CONFIG_PATH.'blocks.php')))
	{
		mergeConfigs($generalConfig, $customGeneralConfig);
	}
	else if (isset($blocksConfig))
	{
		$generalConfig = array_merge($generalConfig, $blocksConfig);
		unset($blocksConfig);
	}
}

// Originally db.php defined a $dbConfig variable.
if (is_array($customDbConfig = require_once(CRAFT_CONFIG_PATH.'db.php')))
{
	mergeConfigs($dbConfig, $customDbConfig);
}

if ($generalConfig['devMode'] == true)
{
	error_reporting(E_ALL & ~E_STRICT);
	ini_set('display_errors', 1);
}
else
{
	error_reporting(0);
	ini_set('display_errors', 0);
}

ini_set('log_errors', 1);
ini_set('error_log', CRAFT_STORAGE_PATH.'runtime/logs/phperrors.log');

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

$configArray = array(

	// autoloading model and component classes
	'import' => array(
		'application.framework.cli.commands.*',
		'application.framework.console.*',
		'application.framework.logging.CLogger',
	),

	'componentAliases' => array(
/* COMPONENT ALIASES */
	),

	'components' => array(

		'db' => array(
			'connectionString'  => strtolower('mysql:host='.$dbConfig['server'].';dbname=').$dbConfig['database'].strtolower(';port='.$dbConfig['port'].';'),
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => $tablePrefix,
			'driverMap'         => array('mysql' => 'Craft\MysqlSchema'),
			'class'             => 'Craft\DbConnection',
		),

		'config' => array(
			'class'         => 'Craft\ConfigService',
			'generalConfig' => $generalConfig,
			'dbConfig'      => $dbConfig,
		),

		'i18n' => array(
			'class' => 'Craft\LocalizationService',
		),

		'formatter' => array(
			'class' => 'CFormatter'
		),
	),

	'params' => array(
		'adminEmail'            => 'admin@website.com',
	)
);

// -------------------------------------------
//  CP routes
// -------------------------------------------

$cpRoutes['dashboard/settings/new']               = 'dashboard/settings/_widgetsettings';
$cpRoutes['dashboard/settings/(?P<widgetId>\d+)'] = 'dashboard/settings/_widgetsettings';

$cpRoutes['entries/(?P<sectionHandle>{handle})/new']              = array('action' => 'entries/editEntry');
$cpRoutes['entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)'] = array('action' => 'entries/editEntry');

$cpRoutes['globals/(?P<globalSetHandle>{handle})'] = 'globals';

$cpRoutes['updates/go/(?P<handle>[^/]*)'] = 'updates/_go';

$cpRoutes['settings']                                                             = array('action' => 'systemSettings/settingsIndex');
$cpRoutes['settings/assets']                                                      = array('action' => 'assetSources/sourceIndex');
$cpRoutes['settings/assets/sources/new']                                          = array('action' => 'assetSources/editSource');
$cpRoutes['settings/assets/sources/(?P<sourceId>\d+)']                            = array('action' => 'assetSources/editSource');
$cpRoutes['settings/assets/transforms']                                           = array('action' => 'assetTransforms/transformIndex');
$cpRoutes['settings/assets/transforms/new']                                       = array('action' => 'assetTransforms/editTransform');
$cpRoutes['settings/assets/transforms/(?P<handle>{handle})']                      = array('action' => 'assetTransforms/editTransform');
$cpRoutes['settings/fields/(?P<groupId>\d+)']                                     = 'settings/fields';
$cpRoutes['settings/fields/new']                                                  = 'settings/fields/_edit';
$cpRoutes['settings/fields/edit/(?P<fieldId>\d+)']                                = 'settings/fields/_edit';
$cpRoutes['settings/general']                                                     = array('action' => 'systemSettings/generalSettings');
$cpRoutes['settings/globals/new']                                                 = array('action' => 'systemSettings/editGlobalSet');
$cpRoutes['settings/globals/(?P<globalSetId>\d+)']                                = array('action' => 'systemSettings/editGlobalSet');
$cpRoutes['settings/plugins/(?P<pluginClass>{handle})']                           = 'settings/plugins/_settings';
$cpRoutes['settings/sections']                                                    = array('action' => 'sections/index');
$cpRoutes['settings/sections/new']                                                = array('action' => 'sections/editSection');
$cpRoutes['settings/sections/(?P<sectionId>\d+)']                                 = array('action' => 'sections/editSection');
$cpRoutes['settings/sections/(?P<sectionId>\d+)/entrytypes']                      = array('action' => 'sections/entryTypesIndex');
$cpRoutes['settings/sections/(?P<sectionId>\d+)/entrytypes/new']                  = array('action' => 'sections/editEntryType');
$cpRoutes['settings/sections/(?P<sectionId>\d+)/entrytypes/(?P<entryTypeId>\d+)'] = array('action' => 'sections/editEntryType');
$cpRoutes['settings/tags']                                                        = array('action' => 'tags/index');
$cpRoutes['settings/tags/new']                                                    = array('action' => 'tags/editTagSet');
$cpRoutes['settings/tags/(?P<tagSetId>\d+)']                                      = array('action' => 'tags/editTagSet');

$cpRoutes['settings/packages'] = array(
	'params' => array(
		'variables' => array(
			'stripeApiKey' => '@@@stripePublishableKey@@@'
		)
	)
);

$cpRoutes['settings/routes'] = array(
	'params' => array(
		'variables' => array(
			'tokens' => array(
				'year'   => '\d{4}',
				'month'  => '(?:0[1-9]|1[012])',
				'day'    => '(?:0[1-9]|[12][0-9]|3[01])',
				'number' => '\d+',
				'page'   => '\d+',
				'tag'    => '[^\/]+',
				'*'      => '[^\/]+',
			)
		)
	)
);

$cpRoutes['myaccount'] = 'users/_edit/account';

// Lanugage package routes
$cpRoutes['pkgRoutes']['Localize']['entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)/(?P<localeId>\w+)'] = array('action' => 'entries/editEntry');
$cpRoutes['pkgRoutes']['Localize']['entries/(?P<sectionHandle>{handle})/new/(?P<localeId>\w+)']              = array('action' => 'entries/editEntry');
$cpRoutes['pkgRoutes']['Localize']['globals/(?P<localeId>\w+)/(?P<globalSetHandle>{handle})']                = 'globals';

// Publish Pro package routes
$cpRoutes['pkgRoutes']['PublishPro']['entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)/drafts/(?P<draftId>\d+)']     = array('action' => 'entries/editEntry');
$cpRoutes['pkgRoutes']['PublishPro']['entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)/versions/(?P<versionId>\d+)'] = array('action' => 'entries/editEntry');

// Users package routes
$cpRoutes['pkgRoutes']['Users']['myaccount/profile']             = 'users/_edit/profile';
$cpRoutes['pkgRoutes']['Users']['myaccount/info']                = 'users/_edit/info';
$cpRoutes['pkgRoutes']['Users']['myaccount/admin']               = 'users/_edit/admin';

$cpRoutes['pkgRoutes']['Users']['users/new']                     = 'users/_edit/account';
$cpRoutes['pkgRoutes']['Users']['users/(?P<userId>\d+)']         = 'users/_edit/account';
$cpRoutes['pkgRoutes']['Users']['users/(?P<userId>\d+)/profile'] = 'users/_edit/profile';
$cpRoutes['pkgRoutes']['Users']['users/(?P<userId>\d+)/admin']   = 'users/_edit/admin';
$cpRoutes['pkgRoutes']['Users']['users/(?P<userId>\d+)/info']    = 'users/_edit/info';

$cpRoutes['pkgRoutes']['Users']['settings/users']                         = 'settings/users/groups';
$cpRoutes['pkgRoutes']['Users']['settings/users/groups/new']              = 'settings/users/groups/_settings';
$cpRoutes['pkgRoutes']['Users']['settings/users/groups/(?P<groupId>\d+)'] = 'settings/users/groups/_settings';

// -------------------------------------------
//  Component config
// -------------------------------------------

$components['users']['class']                = 'Craft\UsersService';
$components['assets']['class']               = 'Craft\AssetsService';
$components['assetTransforms']['class']      = 'Craft\AssetTransformsService';
$components['assetIndexing']['class']        = 'Craft\AssetIndexingService';
$components['assetSources']['class']         = 'Craft\AssetSourcesService';
$components['content']['class']              = 'Craft\ContentService';
$components['dashboard']['class']            = 'Craft\DashboardService';
$components['email']['class']                = 'Craft\EmailService';
$components['elements']['class']             = 'Craft\ElementsService';
$components['entries']['class']              = 'Craft\EntriesService';
$components['et']['class']                   = 'Craft\EtService';
$components['feeds']['class']                = 'Craft\FeedsService';
$components['fields']['class']               = 'Craft\FieldsService';
$components['fieldTypes']['class']           = 'Craft\FieldTypesService';
$components['globals']['class']              = 'Craft\GlobalsService';
$components['install']['class']              = 'Craft\InstallService';
$components['images']['class']               = 'Craft\ImagesService';
$components['migrations']['class']           = 'Craft\MigrationsService';
$components['path']['class']                 = 'Craft\PathService';
$components['relations']['class']            = 'Craft\RelationsService';

$components['sections'] = array(
	'class' => 'Craft\SectionsService',
	'typeLimits' => array(
		'single'  => 5,
		'channel' => 1
	)
);

$components['resources']['class']            = 'Craft\ResourcesService';
$components['resources']['dateParam']        = 'd';

$components['routes']['class']               = 'Craft\RoutesService';
$components['search']['class']               = 'Craft\SearchService';
$components['security']['class']             = 'Craft\SecurityService';
$components['systemSettings']['class']       = 'Craft\SystemSettingsService';
$components['tags']['class']                 = 'Craft\TagsService';
$components['templates']['class']            = 'Craft\TemplatesService';
$components['updates']['class']              = 'Craft\UpdatesService';

$components['components'] = array(
	'class' => 'Craft\ComponentsService',
	'types' => array(
		'assetSource' => array('subfolder' => 'assetsourcetypes', 'suffix' => 'AssetSourceType', 'instanceof' => 'BaseAssetSourceType'),
		'element'     => array('subfolder' => 'elementtypes',     'suffix' => 'ElementType',     'instanceof' => 'IElementType'),
		'field'       => array('subfolder' => 'fieldtypes',       'suffix' => 'FieldType',       'instanceof' => 'IFieldType'),
		'tool'        => array('subfolder' => 'tools',            'suffix' => 'Tool',            'instanceof' => 'ITool'),
		'widget'      => array('subfolder' => 'widgets',          'suffix' => 'Widget',          'instanceof' => 'IWidget'),
	)
);

$components['plugins'] = array(
	'class' => 'Craft\PluginsService',
	'componentTypes' => array(
		'controller'  => array('subfolder' => 'controllers',      'suffix' => 'Controller',      'instanceof' => 'BaseController'),
		'field'       => array('subfolder' => 'fieldtypes',       'suffix' => 'FieldType',       'instanceof' => 'IFieldType'),
		'helper'      => array('subfolder' => 'helpers',          'suffix' => 'Helper'),
		'model'       => array('subfolder' => 'models',           'suffix' => 'Model',           'instanceof' => 'BaseModel'),
		'record'      => array('subfolder' => 'records',          'suffix' => 'Record',          'instanceof' => 'BaseRecord'),
		'service'     => array('subfolder' => 'services',         'suffix' => 'Service',         'instanceof' => 'BaseApplicationComponent'),
		'variable'    => array('subfolder' => 'variables',        'suffix' => 'Variable'),
		'validator'   => array('subfolder' => 'validators',       'suffix' => 'Validator'),
		'widget'      => array('subfolder' => 'widgets',          'suffix' => 'Widget',          'instanceof' => 'IWidget'),
	)
);

// Plugins: This is for experimental use only.
// The Element Type API is likely to change before this config setting is removed.
if (!empty($generalConfig['enablePluginElementTypes']))
{
	$components['plugins']['componentTypes']['element'] = array('subfolder' => 'elementtypes', 'suffix' => 'ElementType', 'instanceof' => 'IElementType');
}

// Publish Pro package components
$components['pkgComponents']['PublishPro']['entryRevisions']['class'] = 'Craft\EntryRevisionsService';


// Users package components
$components['pkgComponents']['Users']['userGroups']['class']      = 'Craft\UserGroupsService';
$components['pkgComponents']['Users']['userPermissions']['class'] = 'Craft\UserPermissionsService';

// Rebrand package components
$components['pkgComponents']['Rebrand']['emailMessages']['class'] = 'Craft\EmailMessagesService';

$components['file']['class'] = 'Craft\File';
$components['messages']['class'] = 'Craft\PhpMessageSource';
$components['request']['class'] = 'Craft\HttpRequestService';
$components['request']['enableCookieValidation'] = true;
$components['viewRenderer']['class'] = 'Craft\TemplateProcessor';
$components['statePersister']['class'] = 'Craft\StatePersister';

$components['urlManager']['class'] = 'Craft\UrlManager';
$components['urlManager']['cpRoutes'] = $cpRoutes;
$components['urlManager']['pathParam'] = 'p';

$components['errorHandler']['class'] = 'Craft\ErrorHandler';

$components['fileCache']['class'] = 'Craft\FileCache';

$components['log']['class'] = 'Craft\LogRouter';
$components['log']['routes'] = array(
	array(
		'class'  => 'Craft\FileLogRoute',
	),
	array(
		'class'         => 'Craft\WebLogRoute',
		'filter'        => 'CLogFilter',
		'showInFireBug' => true,
	),
	array(
		'class'         => 'Craft\ProfileLogRoute',
		'showInFireBug' => true,
	),
);

$components['httpSession']['autoStart']   = true;
$components['httpSession']['cookieMode']  = 'only';
$components['httpSession']['class']       = 'Craft\HttpSessionService';
$components['httpSession']['sessionName'] = 'CraftSessionId';

$components['userSession']['class'] = 'Craft\UserSessionService';
$components['userSession']['allowAutoLogin']  = true;
$components['userSession']['autoRenewCookie'] = true;

$configArray['components'] = array_merge($configArray['components'], $components);

return $configArray;
