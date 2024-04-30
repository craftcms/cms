<?php

return [
    'myaccount/permissions' => 'users/permissions',
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => 'user-settings/edit-group',
    'settings/users/groups/<groupId:\d+>' => 'user-settings/edit-group',
];
