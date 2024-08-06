<?php

return [
    'users' => 'users/index',
    'users/new' => 'users/edit-user',
    'users/<userId:\d+>' => 'users/edit-user',
    'users/<source:{slug}>' => 'users/index',
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => ['template' => 'settings/users/groups/_edit'],
    'settings/users/groups/<groupId:\d+>' => ['template' => 'settings/users/groups/_edit'],
];
