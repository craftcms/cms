<?php

return [
	'clientaccount'                                                                                => false,
	'categories/(?P<groupHandle>{handle})/(?P<categoryId>\d+)(?:-{slug})?/(?P<localeId>\w+)'       => ['action' => 'categories/editCategory'],
	'categories/(?P<groupHandle>{handle})/new/(?P<localeId>\w+)'                                   => ['action' => 'categories/editCategory'],
	'entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)(?:-{slug})?/(?P<localeId>\w+)'           => ['action' => 'entries/editEntry'],
	'entries/(?P<sectionHandle>{handle})/new/(?P<localeId>\w+)'                                    => ['action' => 'entries/editEntry'],
	'globals/(?P<localeId>\w+)/(?P<globalSetHandle>{handle})'                                      => ['action' => 'globals/editContent'],
	'users/new'                                                                                    => ['action' => 'users/editUser'],
	'users/(?P<userId>\d+)'                                                                        => ['action' => 'users/editUser'],
	'settings/users'                                                                               => 'settings/users/groups/_index',
	'settings/users/groups/new'                                                                    => 'settings/users/groups/_edit',
	'settings/users/groups/(?P<groupId>\d+)'                                                       => 'settings/users/groups/_edit',
];
