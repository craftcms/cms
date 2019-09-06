<?php

return [
    'users/new' => 'users/edit-user',
    'users/<userId:\d+>' => 'users/edit-user',
    'settings/users' => ['template' => 'settings/users/groups/_index'],
    'settings/users/groups/new' => ['template' => 'settings/users/groups/_edit'],
    'settings/users/groups/<groupId:\d+>' => ['template' => 'settings/users/groups/_edit'],
    'graphql' => 'graphql/graphiql',
    'graphql/schemas' => 'graphql/view-schemas',
    'graphql/schemas/new' => 'graphql/edit-schema',
    'graphql/schemas/<schemaId:\d+>' => 'graphql/edit-schema',
];
