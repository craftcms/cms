<?php

use CraftCms\Cms\Support\Str;

return [
    'assets/edit/<elementId:\d+><filename:(?:-[^\/]*)?>' => 'elements/edit',
    'edit/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/redirect',
    'edit/<elementUid:' . Str::uuidPattern() . '>' => 'elements/redirect',
    'revisions/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/revisions',
    'entries/<section:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
    'entries/<section:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>/revisions' => 'elements/revisions',

    'content/<page:{slug}>/<section:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
    'content/<page:{slug}>/<section:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>/revisions' => 'elements/revisions',

    'graphiql' => 'graphql/graphiql',
    'graphql' => 'graphql/cp-index',
    'graphql/schemas' => 'graphql/view-schemas',
    'graphql/schemas/new' => 'graphql/edit-schema',
    'graphql/schemas/<schemaId:\d+>' => 'graphql/edit-schema',
    'graphql/schemas/public' => 'graphql/edit-public-schema',
    'graphql/tokens' => 'graphql/view-tokens',
    'graphql/tokens/new' => 'graphql/edit-token',
    'graphql/tokens/<tokenId:\d+>' => 'graphql/edit-token',
    'myaccount' => 'users/profile',
    'myaccount/addresses' => 'users/addresses',
    'myaccount/preferences' => 'users/preferences',
    'myaccount/password' => 'users/password',
    'myaccount/passkeys' => 'users/passkeys',
    'settings/email' => 'system-settings/edit-email-settings',
    'settings/fields/new' => 'fields/edit-field',
    'settings/fields/edit/<fieldId:\d+>' => 'fields/edit-field',
    'settings/general' => 'system-settings/general-settings',
    'settings/plugins/<handle>' => 'plugins/edit-plugin-settings',
    'settings/users' => ['template' => 'settings/users/fields'],
    'preview/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/preview',
];
