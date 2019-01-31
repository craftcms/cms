<?php

$mimeTypes = require Craft::getAlias('@yii/helpers/mimeTypes.php');
$mimeTypes['markdown'] = 'text/markdown';
$mimeTypes['md'] = 'text/markdown';
$mimeTypes['woff2'] = 'application/font-woff2';
$mimeTypes['yaml'] = 'application/x-yaml';
$mimeTypes['yml'] = 'application/x-yaml';

return $mimeTypes;
