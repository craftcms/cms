<?php

// Initially set it here.  WebApp->init() will check devMode and override appropriately.
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

ini_set('log_errors', 1);
ini_set('error_log', CRAFT_STORAGE_PATH.'runtime/logs/phperrors.log');

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
			'driverMap'         => array('mysql' => 'Craft\MysqlSchema'),
			'class'             => 'Craft\DbConnection',
		),

		'config' => array(
			'class'         => 'Craft\ConfigService',
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

$cpRoutes['categories/(?P<groupHandle>{handle})'] = 'categories';

$cpRoutes['dashboard/settings/new']               = 'dashboard/settings/_widgetsettings';
$cpRoutes['dashboard/settings/(?P<widgetId>\d+)'] = 'dashboard/settings/_widgetsettings';

$cpRoutes['entries/(?P<sectionHandle>{handle})']                  = 'entries';
$cpRoutes['entries/(?P<sectionHandle>{handle})/new']              = array('action' => 'entries/editEntry');
$cpRoutes['entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)'] = array('action' => 'entries/editEntry');

$cpRoutes['globals/(?P<globalSetHandle>{handle})']                = array('action' => 'globals/editContent');

$cpRoutes['updates/go/(?P<handle>[^/]*)'] = 'updates/_go';

$cpRoutes['settings']                                                             = array('action' => 'systemSettings/settingsIndex');
$cpRoutes['settings/assets']                                                      = array('action' => 'assetSources/sourceIndex');
$cpRoutes['settings/assets/sources/new']                                          = array('action' => 'assetSources/editSource');
$cpRoutes['settings/assets/sources/(?P<sourceId>\d+)']                            = array('action' => 'assetSources/editSource');
$cpRoutes['settings/assets/transforms']                                           = array('action' => 'assetTransforms/transformIndex');
$cpRoutes['settings/assets/transforms/new']                                       = array('action' => 'assetTransforms/editTransform');
$cpRoutes['settings/assets/transforms/(?P<handle>{handle})']                      = array('action' => 'assetTransforms/editTransform');
$cpRoutes['settings/categories']                                                  = array('action' => 'categories/groupIndex');
$cpRoutes['settings/categories/new']                                              = array('action' => 'categories/editCategoryGroup');
$cpRoutes['settings/categories/(?P<groupId>\d+)']                                 = array('action' => 'categories/editCategoryGroup');
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
$cpRoutes['settings/tags/new']                                                    = array('action' => 'tags/editTagGroup');
$cpRoutes['settings/tags/(?P<tagGroupId>\d+)']                                    = array('action' => 'tags/editTagGroup');

$cpRoutes['utils/serverinfo']                                                     = array('action' => 'utils/serverInfo');
$cpRoutes['utils/phpinfo']                                                        = array('action' => 'utils/phpInfo');
$cpRoutes['utils/logviewer(/(?P<currentLogFileName>[A-Za-z0-9\.]+))?']            = array('action' => 'utils/logs');

$cpRoutes['settings/routes'] = array(
	'params' => array(
		'variables' => array(
			'tokens' => array(
				'year'   => '\d{4}',
				'month'  => '(?:0?[1-9]|1[012])',
				'day'    => '(?:0?[1-9]|[12][0-9]|3[01])',
				'number' => '\d+',
				'page'   => '\d+',
				'tag'    => '[^\/]+',
				'*'      => '[^\/]+',
			)
		)
	)
);

$cpRoutes['myaccount'] = array('action' => 'users/editUser');

// Client routes
$cpRoutes['editionRoutes'][2]['entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)/(?P<localeId>\w+)']           = array('action' => 'entries/editEntry');
$cpRoutes['editionRoutes'][2]['entries/(?P<sectionHandle>{handle})/new/(?P<localeId>\w+)']                        = array('action' => 'entries/editEntry');
$cpRoutes['editionRoutes'][2]['globals/(?P<localeId>\w+)/(?P<globalSetHandle>{handle})']                          = array('action' => 'globals/editContent');
$cpRoutes['editionRoutes'][2]['entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)/drafts/(?P<draftId>\d+)']     = array('action' => 'entries/editEntry');
$cpRoutes['editionRoutes'][2]['entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)/versions/(?P<versionId>\d+)'] = array('action' => 'entries/editEntry');
$cpRoutes['editionRoutes'][2]['users/new']                                                                        = array('action' => 'users/editUser');
$cpRoutes['editionRoutes'][2]['users/(?P<userId>\d+)']                                                            = array('action' => 'users/editUser');
$cpRoutes['editionRoutes'][2]['settings/users']                                                                   = 'settings/users/groups';
$cpRoutes['editionRoutes'][2]['settings/users/groups/new']                                                        = 'settings/users/groups/_settings';
$cpRoutes['editionRoutes'][2]['settings/users/groups/(?P<groupId>\d+)']                                           = 'settings/users/groups/_settings';

// -------------------------------------------
//  Component config
// -------------------------------------------

$components['users']['class']                = 'Craft\UsersService';
$components['assets']['class']               = 'Craft\AssetsService';
$components['assetTransforms']['class']      = 'Craft\AssetTransformsService';
$components['assetIndexing']['class']        = 'Craft\AssetIndexingService';
$components['assetSources']['class']         = 'Craft\AssetSourcesService';
$components['categories']['class']           = 'Craft\CategoriesService';
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
$components['matrix']['class']               = 'Craft\MatrixService';
$components['migrations']['class']           = 'Craft\MigrationsService';
$components['path']['class']                 = 'Craft\PathService';
$components['relations']['class']            = 'Craft\RelationsService';
$components['cache']['class']                = 'Craft\CacheService';
$components['resources'] = array(
	'class'     => 'Craft\ResourcesService',
	'dateParam' => 'd',
);
$components['routes']['class']               = 'Craft\RoutesService';
$components['search']['class']               = 'Craft\SearchService';
$components['sections'] = array(
	'class' => 'Craft\SectionsService',
	'typeLimits' => array(
		'single'    => 5,
		'channel'   => 1,
		'structure' => 0
	)
);
$components['security']['class']             = 'Craft\SecurityService';
$components['structures']['class']           = 'Craft\StructuresService';
$components['systemSettings'] = array(
	'class' => 'Craft\SystemSettingsService',
	'defaults' => array(
		'users' => array(
			'requireEmailVerification' => true,
			'allowPublicRegistration' => false,
			'defaultGroup' => null,
		),
		'email' => array(
			'emailAddress' => null,
			'senderName' => null,
			'template' => null,
			'protocol' => null,
			'username' => null,
			'password' => null,
			'port' => 25,
			'host' => null,
			'timeout' => 30,
			'smtpKeepAlive' => false,
			'smtpAuth' => false,
			'smtpSecureTransportType' => 'none',
		)
	)
);
$components['tags']['class']                 = 'Craft\TagsService';
$components['tasks']['class']                = 'Craft\TasksService';
$components['templateCache']['class']        = 'Craft\TemplateCacheService';
$components['templates']['class']            = 'Craft\TemplatesService';
$components['updates']['class']              = 'Craft\UpdatesService';
$components['components'] = array(
	'class' => 'Craft\ComponentsService',
	'types' => array(
		'assetSource' => array('subfolder' => 'assetsourcetypes', 'suffix' => 'AssetSourceType', 'instanceof' => 'BaseAssetSourceType'),
		'element'     => array('subfolder' => 'elementtypes',     'suffix' => 'ElementType',     'instanceof' => 'IElementType'),
		'field'       => array('subfolder' => 'fieldtypes',       'suffix' => 'FieldType',       'instanceof' => 'IFieldType'),
		'tool'        => array('subfolder' => 'tools',            'suffix' => 'Tool',            'instanceof' => 'ITool'),
		'task'        => array('subfolder' => 'tasks',            'suffix' => 'Task',            'instanceof' => 'ITask'),
		'widget'      => array('subfolder' => 'widgets',          'suffix' => 'Widget',          'instanceof' => 'IWidget'),
	)
);
$components['plugins'] = array(
	'class' => 'Craft\PluginsService',
	'autoloadClasses' => array('Controller','Helper','Model','Record','Service','Variable','Validator'),
);

// Craft Client components
$components['editionComponents'][1]['emailMessages']['class']   = 'Craft\EmailMessagesService';

// Craft Pro components
$components['editionComponents'][2]['entryRevisions']['class']  = 'Craft\EntryRevisionsService';
$components['editionComponents'][2]['userGroups']['class']      = 'Craft\UserGroupsService';
$components['editionComponents'][2]['userPermissions']['class'] = 'Craft\UserPermissionsService';

$components['file']['class'] = 'Craft\File';
$components['messages']['class'] = 'Craft\PhpMessageSource';
$components['request']['class'] = 'Craft\HttpRequestService';
$components['request']['enableCookieValidation'] = true;
$components['viewRenderer']['class'] = 'Craft\TemplateProcessor';
$components['statePersister']['class'] = 'Craft\StatePersister';

$components['urlManager']['class'] = 'Craft\UrlManager';
$components['urlManager']['cpRoutes'] = $cpRoutes;
$components['urlManager']['pathParam'] = 'p';

$components['errorHandler'] = array(
	'class' => 'Craft\ErrorHandler',
	'errorAction' => 'templates/renderError'
);

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
