<?php

return [
    'users/new' => 'users/edit-user',
    'users/<userId:\d+>' => 'users/edit-user',
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => ['template' => 'settings/users/groups/_edit'],
    'settings/users/groups/<groupId:\d+>' => ['template' => 'settings/users/groups/_edit'],
    'settings/graphql/tokens' => 'gql/view-tokens',
    'settings/graphql/tokens/new' => 'gql/edit-token',
    'settings/graphql/tokens/<tokenId:\d+>' => 'gql/edit-token',
];
