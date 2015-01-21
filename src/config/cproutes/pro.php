<?php

return [
	'clientaccount'                                                                        => false,
	'categories/<groupHandle:{handle}>/<categoryId:\d+><slug:(?:-{slug})?>/<localeId:\w+>' => 'categories/editCategory',
	'categories/<groupHandle:{handle}>/new/<localeId:\w+>'                                 => 'categories/editCategory',
	'entries/<sectionHandle:{handle}>/<entryId:\d+><slug:(?:-{slug})?>/<localeId:\w+>'     => 'entries/editEntry',
	'entries/<sectionHandle:{handle}>/new/<localeId:\w+>'                                  => 'entries/editEntry',
	'globals/<localeId:\w+>/<globalSetHandle:{handle}>'                                    => 'globals/editContent',
	'users/new'                                                                            => 'users/editUser',
	'users/<userId:\d+>'                                                                   => 'users/editUser',
	'settings/users'                                                                       => ['template' => 'settings/users/groups/_index'],
	'settings/users/groups/new'                                                            => ['template' => 'settings/users/groups/_edit'],
	'settings/users/groups/<groupId:\d+>'                                                  => ['template' => 'settings/users/groups/_edit'],
];
