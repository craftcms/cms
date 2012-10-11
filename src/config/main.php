<?php

$common = require_once(BLOCKS_APP_PATH.'config/common.php');

Yii::setPathOfAlias('app', BLOCKS_APP_PATH);
Yii::setPathOfAlias('plugins', BLOCKS_PLUGINS_PATH);

$packages = explode(',', BLOCKS_PACKAGES);

// -------------------------------------------
//  CP routes
// -------------------------------------------

$handle = '[a-zA-Z][a-zA-Z0-9_]*';

$cpRoutes['content']                                                          = 'content/entries/index';
$cpRoutes['content\/pages']                                                   = 'content/pages/index';
$cpRoutes['content\/pages\/(?P<pageId>\d+)']                                  = 'content/pages/_edit';
$cpRoutes['content\/globals']                                                 = 'content/globals/index';
$cpRoutes['content\/(?P<sectionHandle>'.$handle.')\/new']                     = 'content/entries/_edit';
$cpRoutes['content\/(?P<sectionHandle>'.$handle.')\/(?P<entryId>\d+)']        = 'content/entries/_edit';
$cpRoutes['content\/(?P<filter>'.$handle.')']                                 = 'content/entries/index';
$cpRoutes['content\/(?P<entryId>\d+)']                                        = 'content/_entry';

$cpRoutes['dashboard\/settings\/new']                                         = 'dashboard/settings/_widgetsettings';
$cpRoutes['dashboard\/settings\/(?P<widgetId>\d+)']                           = 'dashboard/settings/_widgetsettings';

$cpRoutes['update\/(?P<handle>[^\/]*)']                                       = 'update';

$cpRoutes['plugins\/(?P<pluginClass>[A-Za-z]\w*)']                            = 'plugins/_settings';

$cpRoutes['settings\/assets']                                                 = 'settings/assets/sources';
$cpRoutes['settings\/assets\/sources\/new']                                   = 'settings/assets/sources/_settings';
$cpRoutes['settings\/assets\/sources\/(?P<sourceId>\d+)']                     = 'settings/assets/sources/_settings';
$cpRoutes['settings\/assets\/blocks\/new']                                    = 'settings/assets/blocks/_settings';
$cpRoutes['settings\/assets\/blocks\/(?P<blockId>\d+)']                       = 'settings/assets/blocks/_settings';
$cpRoutes['settings\/globals\/new']                                           = 'settings/globals/_settings';
$cpRoutes['settings\/globals\/(?P<blockId>\d+)']                              = 'settings/globals/_settings';
$cpRoutes['settings\/pages\/new']                                             = 'settings/pages/_edit/settings';
$cpRoutes['settings\/pages\/(?P<pageId>\d+)']                                 = 'settings/pages/_edit/settings';
$cpRoutes['settings\/pages\/(?P<pageId>\d+)\/blocks']                         = 'settings/pages/_edit/blocks/index';
$cpRoutes['settings\/pages\/(?P<pageId>\d+)\/blocks\/new']                    = 'settings/pages/_edit/blocks/settings';
$cpRoutes['settings\/pages\/(?P<pageId>\d+)\/blocks\/(?P<blockId>\d+)']       = 'settings/pages/_edit/blocks/settings';

$cpRoutes['myaccount']                                                        = 'users/_edit/account';

if (in_array('publishpro', $packages))
{
	$cpRoutes['content\/(?P<entryId>\d+)\/draft(?P<draftNum>\d+)']                = 'content/_entry';

	$cpRoutes['settings\/sections\/new']                                          = 'settings/sections/_edit/settings';
	$cpRoutes['settings\/sections\/(?P<sectionId>\d+)']                           = 'settings/sections/_edit/settings';

	$cpRoutes['settings\/sections\/(?P<sectionId>\d+)\/blocks']                   = 'settings/sections/_edit/blocks';
	$cpRoutes['settings\/sections\/(?P<sectionId>\d+)\/blocks\/new']              = 'settings/sections/_edit/blocks/settings';
	$cpRoutes['settings\/sections\/(?P<sectionId>\d+)\/blocks\/(?P<blockId>\d+)'] = 'settings/sections/_edit/blocks/settings';
}
else
{
	$cpRoutes['settings\/blog\/blocks\/new']                                      = 'settings/sections/_edit/blocks/settings';
	$cpRoutes['settings\/blog\/blocks\/(?P<blockId>\d+)']                         = 'settings/sections/_edit/blocks/settings';
}

if (in_array('users', $packages))
{
	$cpRoutes['myaccount\/profile']                                               = 'users/_edit/profile';
	$cpRoutes['myaccount\/info']                                                  = 'users/_edit/info';
	$cpRoutes['myaccount\/admin']                                                 = 'users/_edit/admin';

	$cpRoutes['users\/new']                                                       = 'users/_edit/account';
	$cpRoutes['users\/(?P<filter>'.$handle.')']                                   = 'users';
	$cpRoutes['users\/(?P<userId>\d+)']                                           = 'users/_edit/account';
	$cpRoutes['users\/(?P<userId>\d+)\/profile']                                  = 'users/_edit/profile';
	$cpRoutes['users\/(?P<userId>\d+)\/admin']                                    = 'users/_edit/admin';
	$cpRoutes['users\/(?P<userId>\d+)\/info']                                     = 'users/_edit/info';

	$cpRoutes['settings\/users']                                                  = 'settings/users/groups';
	$cpRoutes['settings\/users\/groups\/new']                                     = 'settings/users/groups/_settings';
	$cpRoutes['settings\/users\/groups\/(?P<groupId>\d+)']                        = 'settings/users/groups/_settings';
	$cpRoutes['settings\/users\/blocks\/new']                                     = 'settings/users/blocks/_settings';
	$cpRoutes['settings\/users\/blocks\/(?P<blockId>\d+)']                        = 'settings/users/blocks/_settings';
}

// -------------------------------------------
//  Component config
// -------------------------------------------

$components['account']['class']           = 'Blocks\AccountService';
$components['assets']['class']            = 'Blocks\AssetsService';
$components['assetBlocks']['class']       = 'Blocks\AssetBlocksService';
$components['assetSources']['class']      = 'Blocks\AssetSourcesService';
$components['blockTypes']['class']        = 'Blocks\BlockTypesService';
$components['components']['class']        = 'Blocks\ComponentsService';
$components['dashboard']['class']         = 'Blocks\DashboardService';
$components['email']['class']             = 'Blocks\EmailService';
$components['entries']['class']           = 'Blocks\EntriesService';
$components['entryBlocks']['class']       = 'Blocks\EntryBlocksService';
$components['et']['class']                = 'Blocks\EtService';
$components['globalBlocks']['class']      = 'Blocks\GlobalBlocksService';
$components['installer']['class']         = 'Blocks\InstallService';
$components['links']['class']             = 'Blocks\LinksService';
$components['migrations']['class']        = 'Blocks\MigrationsService';
$components['pages']['class']             = 'Blocks\PagesService';
$components['pageBlocks']['class']        = 'Blocks\PageBlocksService';
$components['path']['class']              = 'Blocks\PathService';
$components['plugins']['class']           = 'Blocks\PluginsService';
$components['routes']['class']            = 'Blocks\RoutesService';
$components['security']['class']          = 'Blocks\SecurityService';
$components['systemSettings']['class']    = 'Blocks\SystemSettingsService';
$components['templates']['class']         = 'Blocks\TemplatesService';
$components['updates']['class']           = 'Blocks\UpdatesService';

if (in_array('publishpro', $packages))
{
	$components['sections']['class']          = 'Blocks\SectionsService';
	$components['sectionBlocks']['class']     = 'Blocks\SectionBlocksService';
}

if (in_array('users', $packages))
{
	$components['users']['class']             = 'Blocks\UsersService';
	$components['userProfiles']['class']      = 'Blocks\UserProfilesService';
	$components['userProfileBlocks']['class'] = 'Blocks\UserProfileBlocksService';
	$components['userGroups']['class']        = 'Blocks\UserGroupsService';
}

if (in_array('rebrand', $packages))
{
	$components['emailMessages']['class']     = 'Blocks\EmailMessagesService';
}

$components['file']['class'] = 'Blocks\File';
$components['messages']['class'] = 'Blocks\PhpMessageSource';
$components['request']['class'] = 'Blocks\HttpRequestService';
$components['request']['enableCookieValidation'] = true;
$components['viewRenderer']['class'] = 'Blocks\TemplateProcessor';
$components['statePersister']['class'] = 'Blocks\StatePersister';

$components['urlManager']['class'] = 'Blocks\UrlManager';
$components['urlManager']['cpRoutes'] = $cpRoutes;

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

$components['session']['autoStart'] = true;
$components['session']['cookieMode'] = 'only';
$components['session']['class'] = 'Blocks\HttpSessionService';
$components['session']['sessionName'] = 'BlocksSessionId';

$components['user']['class'] = 'Blocks\UserSessionService';
$components['user']['allowAutoLogin'] = true;
$components['user']['loginUrl'] = array('/login');
$components['user']['autoRenewCookie'] = true;


return CMap::mergeArray(
	$common,

	array(
		'basePath'    => BLOCKS_APP_PATH,
		'runtimePath' => BLOCKS_RUNTIME_PATH,
		'name'        => 'Blocks',

		// autoloading model and component classes
		'import' => array(
			'application.lib.*',
			'application.lib.PhpMailer.*',
			'application.lib.Requests.*',
			'application.lib.Requests.Auth.*',
			'application.lib.Requests.Response.*',
			'application.lib.Requests.Transport.*',
		),

		// application components
		'components' => $components,

		'params' => array(
			'blocksConfig'         => $blocksConfig,
			'requiredPhpVersion'   => '5.3.0',
			'requiredMysqlVersion' => ''
		),
	)
);
