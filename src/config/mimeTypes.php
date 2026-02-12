<?php

$mimeTypes = require Craft::getAlias('@yii/helpers/mimeTypes.php');

return array_merge($mimeTypes, [
    'avif' => 'image/avif',
    'heic' => 'image/heic',
    'heif' => 'image/heic',
    'hevc' => 'video/mp4',
    'markdown' => 'text/markdown',
    'md' => 'text/markdown',
    'vtt' => 'text/vtt',
    'woff2' => 'application/font-woff2',
    'yaml' => 'application/x-yaml',
    'yml' => 'application/x-yaml',
]);
