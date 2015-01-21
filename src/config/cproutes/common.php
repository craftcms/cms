<?php

return [
	'categories'                                                            => 'categories/categoryIndex',
	'categories/<groupHandle:{handle}>'                                     => 'categories/categoryIndex',
	'categories/<groupHandle:{handle}>/new'                                 => 'categories/editCategory',
	'categories/<groupHandle:{handle}>/<categoryId:\d+><slug:(?:-{slug})?>' => 'categories/editCategory',

	'dashboard/settings/new'                                                => ['template' => 'dashboard/settings/_widgetsettings'],
	'dashboard/settings/<widgetId:\d+>'                                     => ['route' => 'templates/render', 'defaults' => ['template' => 'dashboard/settings/_widgetsettings']],

	'entries/<sectionHandle:{handle}>'                                      => ['template' => 'entries'],
	'entries/<sectionHandle:{handle}>/new'                                  => 'entries/editEntry',
	'entries/<sectionHandle:{handle}>/<entryId:\d+><slug:(?:-{slug})?>'     => 'entries/editEntry',

	'globals/<globalSetHandle:{handle}>'                                    => 'globals/editContent',

	'updates/go/<handle:[^/]*>'                                             => ['template' => 'updates/_go'],

	'settings'                                                              => 'systemSettings/settingsIndex',
	'settings/assets'                                                       => 'assetSources/sourceIndex',
	'settings/assets/sources/new'                                           => 'assetSources/editSource',
	'settings/assets/sources/<sourceId:\d+>'                                => 'assetSources/editSource',
	'settings/assets/transforms'                                            => 'assetTransforms/transformIndex',
	'settings/assets/transforms/new'                                        => 'assetTransforms/editTransform',
	'settings/assets/transforms/<handle:{handle}>'                          => 'assetTransforms/editTransform',
	'settings/categories'                                                   => 'categories/groupIndex',
	'settings/categories/new'                                               => 'categories/editCategoryGroup',
	'settings/categories/<groupId:\d+>'                                     => 'categories/editCategoryGroup',
	'settings/fields/<groupId:\d+>'                                         => ['template' => 'settings/fields'],
	'settings/fields/new'                                                   => ['template' => 'settings/fields/_edit'],
	'settings/fields/edit/<fieldId:\d+>'                                    => ['template' => 'settings/fields/_edit'],
	'settings/general'                                                      => 'systemSettings/generalSettings',
	'settings/globals/new'                                                  => 'systemSettings/editGlobalSet',
	'settings/globals/<globalSetId:\d+>'                                    => 'systemSettings/editGlobalSet',
	'settings/plugins/<pluginClass:{handle}>'                               => ['template' => 'settings/plugins/_settings'],
	'settings/sections'                                                     => 'sections/index',
	'settings/sections/new'                                                 => 'sections/editSection',
	'settings/sections/<sectionId:\d+>'                                     => 'sections/editSection',
	'settings/sections/<sectionId:\d+>/entrytypes'                          => 'sections/entryTypesIndex',
	'settings/sections/<sectionId:\d+>/entrytypes/new'                      => 'sections/editEntryType',
	'settings/sections/<sectionId:\d+>/entrytypes/<entryTypeId:\d+>'        => 'sections/editEntryType',
	'settings/tags'                                                         => 'tags/index',
	'settings/tags/new'                                                     => 'tags/editTagGroup',
	'settings/tags/<tagGroupId:\d+>'                                        => 'tags/editTagGroup',

	'utils/serverinfo'                                                      => 'utils/serverInfo',
	'utils/phpinfo'                                                         => 'utils/phpInfo',
	'utils/logs(/<currentLogFileName:[A-Za-z0-9\.]+>?'                      => 'utils/logs',
	'utils/deprecationerrors'                                               => 'utils/deprecationErrors',

	'myaccount'                                                             => ['route' => 'users/editUser', 'defaults' => ['acount' => 'current']],

	'settings/routes' => [
		'template' => 'settings/routes',
		'params' => [
			'variables' => [
				'tokens' => [
					'year'   => '\d{4}',
					'month'  => '(?:0?[1-9]|1[012])',
					'day'    => '(?:0?[1-9]|[12][0-9]|3[01])',
					'number' => '\d+',
					'page'   => '\d+',
					'slug'   => '[^\/]+',
					'tag'    => '[^\/]+',
					'*'      => '[^\/]+',
				]
			]
		]
	],
];
