<?php

return [
    'assets/edit/<elementId:\d+><filename:(?:-[^\/]*)?>' => 'elements/edit',
    'entries/<section:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
    'content/<page:{slug}>/<section:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
];
