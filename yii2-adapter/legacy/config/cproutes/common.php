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

    'preview/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/preview',
];
