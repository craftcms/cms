<?php

return [
    'myaccount/permissions' => 'users/permissions',
    'users/new' => 'users/create',
    'users/<userId:\d+>' => 'users/profile',
    'users/<userId:\d+>/addresses' => 'users/addresses',
    'users/<userId:\d+>/permissions' => 'users/permissions',
    'users/<source:{slug}>' => ['template' => 'users'],
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => ['template' => 'settings/users/groups/_edit'],
    'settings/users/groups/<groupId:\d+>' => ['template' => 'settings/users/groups/_edit'],
];
