<?php

return [
    'users/new' => 'users/edit-user',
    'users/<userId:\d+>' => 'users/edit-user',
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => ['template' => 'settings/users/groups/_edit'],
    'settings/users/groups/<groupId:\d+>' => ['template' => 'settings/users/groups/_edit'],
    'graphql/tokens' => 'gql/view-tokens',
    'graphql/tokens/new' => 'gql/edit-token',
    'graphql/tokens/<tokenId:\d+>' => 'gql/edit-token',
];
