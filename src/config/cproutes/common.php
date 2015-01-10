<?php

return [
	'categories'                                                           => ['action' => 'categories/categoryIndex'],
	'categories/(?P<groupHandle>{handle})'                                 => ['action' => 'categories/categoryIndex'],
	'categories/(?P<groupHandle>{handle})/new'                             => ['action' => 'categories/editCategory'],
	'categories/(?P<groupHandle>{handle})/(?P<categoryId>\d+)(?:-{slug})?' => ['action' => 'categories/editCategory'],

	'dashboard/settings/new'                                               => 'dashboard/settings/_widgetsettings',
	'dashboard/settings/(?P<widgetId>\d+)'                                 => 'dashboard/settings/_widgetsettings',

	'entries/(?P<sectionHandle>{handle})'                                  => 'entries',
	'entries/(?P<sectionHandle>{handle})/new'                              => ['action' => 'entries/editEntry'],
	'entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)(?:-{slug})?'     => ['action' => 'entries/editEntry'],

	'globals/(?P<globalSetHandle>{handle})'                                => ['action' => 'globals/editContent'],

	'updates/go/(?P<handle>[^/]*)'                                         => 'updates/_go',

	'settings'                                                             => ['action' => 'systemSettings/settingsIndex'],
	'settings/assets'                                                      => ['action' => 'assetSources/sourceIndex'],
	'settings/assets/sources/new'                                          => ['action' => 'assetSources/editSource'],
	'settings/assets/sources/(?P<sourceId>\d+)'                            => ['action' => 'assetSources/editSource'],
	'settings/assets/transforms'                                           => ['action' => 'assetTransforms/transformIndex'],
	'settings/assets/transforms/new'                                       => ['action' => 'assetTransforms/editTransform'],
	'settings/assets/transforms/(?P<handle>{handle})'                      => ['action' => 'assetTransforms/editTransform'],
	'settings/categories'                                                  => ['action' => 'categories/groupIndex'],
	'settings/categories/new'                                              => ['action' => 'categories/editCategoryGroup'],
	'settings/categories/(?P<groupId>\d+)'                                 => ['action' => 'categories/editCategoryGroup'],
	'settings/fields/(?P<groupId>\d+)'                                     => 'settings/fields',
	'settings/fields/new'                                                  => 'settings/fields/_edit',
	'settings/fields/edit/(?P<fieldId>\d+)'                                => 'settings/fields/_edit',
	'settings/general'                                                     => ['action' => 'systemSettings/generalSettings'],
	'settings/globals/new'                                                 => ['action' => 'systemSettings/editGlobalSet'],
	'settings/globals/(?P<globalSetId>\d+)'                                => ['action' => 'systemSettings/editGlobalSet'],
	'settings/plugins/(?P<pluginClass>{handle})'                           => 'settings/plugins/_settings',
	'settings/sections'                                                    => ['action' => 'sections/index'],
	'settings/sections/new'                                                => ['action' => 'sections/editSection'],
	'settings/sections/(?P<sectionId>\d+)'                                 => ['action' => 'sections/editSection'],
	'settings/sections/(?P<sectionId>\d+)/entrytypes'                      => ['action' => 'sections/entryTypesIndex'],
	'settings/sections/(?P<sectionId>\d+)/entrytypes/new'                  => ['action' => 'sections/editEntryType'],
	'settings/sections/(?P<sectionId>\d+)/entrytypes/(?P<entryTypeId>\d+)' => ['action' => 'sections/editEntryType'],
	'settings/tags'                                                        => ['action' => 'tags/index'],
	'settings/tags/new'                                                    => ['action' => 'tags/editTagGroup'],
	'settings/tags/(?P<tagGroupId>\d+)'                                    => ['action' => 'tags/editTagGroup'],

	'utils/serverinfo'                                                     => ['action' => 'utils/serverInfo'],
	'utils/phpinfo'                                                        => ['action' => 'utils/phpInfo'],
	'utils/logs(/(?P<currentLogFileName>[A-Za-z0-9\.]+))?'                 => ['action' => 'utils/logs'],
	'utils/deprecationerrors'                                              => ['action' => 'utils/deprecationErrors'],

	'myaccount'                                                            => ['action' => 'users/editUser', 'params' => ['account' => 'current']],

	'settings/routes' => [
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
