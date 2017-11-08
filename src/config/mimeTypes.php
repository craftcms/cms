<?php

$mimeTypes = require Craft::getAlias('@yii/helpers/mimeTypes.php');
$mimeTypes['woff2'] = 'application/font-woff2';

return $mimeTypes;
