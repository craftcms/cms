<?php

return [
    'users/new' => 'users/edit-user',
    'users/<userId:\d+>' => 'users/edit-user',
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => ['template' => 'settings/users/groups/_edit'],
    'settings/users/groups/<groupId:\d+>' => ['template' => 'settings/users/groups/_edit'],
    'graphql' => 'gql/graphiql',
    'graphql/schemas' => 'gql/view-schemas',
    'graphql/schemas/new' => 'gql/edit-schema',
    'graphql/schemas/<schemaId:\d+>' => 'gql/edit-schema',
];
