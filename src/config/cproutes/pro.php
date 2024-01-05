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
    'graphiql' => 'graphql/graphiql',
    'graphql' => 'graphql/cp-index',
    'graphql/schemas' => 'graphql/view-schemas',
    'graphql/schemas/new' => 'graphql/edit-schema',
    'graphql/schemas/<schemaId:\d+>' => 'graphql/edit-schema',
    'graphql/schemas/public' => 'graphql/edit-public-schema',
    'graphql/tokens' => 'graphql/view-tokens',
    'graphql/tokens/new' => 'graphql/edit-token',
    'graphql/tokens/<tokenId:\d+>' => 'graphql/edit-token',
];
