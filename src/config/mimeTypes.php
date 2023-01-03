<?php

$mimeTypes = require Craft::getAlias('@yii/helpers/mimeTypes.php');

return array_merge($mimeTypes, [
    'markdown' => 'text/markdown',
    'md' => 'text/markdown',
    'vtt' => 'text/vtt',
    'woff2' => 'application/font-woff2',
    'yaml' => 'application/x-yaml',
    'yml' => 'application/x-yaml',
]);
