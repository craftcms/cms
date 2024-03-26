<?php

return [
    'users/new' => 'users/create',
    'users/<userId:\d+>' => 'users/profile',
    'users/<userId:\d+>/addresses' => 'users/addresses',
    'users/<source:{slug}>' => ['template' => 'users'],
];
