<?php

return [
	'clientaccount'                                                                            => false,
	'categories/<groupHandle:{handle}>/<categoryId:\d+><slug:(?:-{slug})?>/<localeId:[\w\-]+>' => 'categories/edit-category',
	'categories/<groupHandle:{handle}>/new/<localeId:[\w\-]+>'                                 => 'categories/edit-category',
	'entries/<sectionHandle:{handle}>/<entryId:\d+><slug:(?:-{slug})?>/<localeId:[\w\-]+>'     => 'entries/edit-entry',
	'entries/<sectionHandle:{handle}>/new/<localeId:[\w\-]+>'                                  => 'entries/edit-entry',
	'globals/<localeId:[\w\-]+>/<globalSetHandle:{handle}>'                                    => 'globals/edit-content',
	'users/new'                                                                                => 'users/edit-user',
	'users/<userId:\d+>'                                                                       => 'users/edit-user',
	'settings/users'                                                                           => ['template' => 'settings/users/groups/_index'],
	'settings/users/groups/new'                                                                => ['template' => 'settings/users/groups/_edit'],
	'settings/users/groups/<groupId:\d+>'                                                      => ['template' => 'settings/users/groups/_edit'],
];
