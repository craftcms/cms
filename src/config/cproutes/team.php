<?php

return [
    'myaccount/permissions' => 'users/permissions',
    'settings/users' => 'user-settings/edit-group',
    'users' => 'users/index',
    'users/new' => 'users/create',
    'users/<userId:\d+>' => 'users/profile',
    'users/<userId:\d+>/addresses' => 'users/addresses',
    'users/<userId:\d+>/permissions' => 'users/permissions',
    'users/<source:{slug}>' => 'users/index',
];
