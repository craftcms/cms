<?php

return [
    'myaccount/permissions' => 'users/permissions',
    'users/<userId:\d+>/permissions' => 'users/permissions',
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => ['template' => 'settings/users/groups/_edit'],
    'settings/users/groups/<groupId:\d+>' => ['template' => 'settings/users/groups/_edit'],
];
