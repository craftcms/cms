<?php

return [
    'users/new' => 'users/edit-user',
    'users/<userId:\d+>' => 'users/edit-user',
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => ['template' => 'settings/users/groups/_edit'],
    'settings/users/groups/<groupId:\d+>' => ['template' => 'settings/users/groups/_edit'],
    'settings/graphql/tokens' => ['template' => 'settings/graphql/tokens/_index'],
    'settings/graphql/tokens/new' => ['template' => 'settings/graphql/tokens/_edit'],
    'settings/graphql/tokens/<tokenId:\d+>' => ['template' => 'settings/graphql/tokens/_edit'],
];
