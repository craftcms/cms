<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/config/defaults/general.php
 */
$config = [
    'devMode' => true,
    'cpTrigger' => 'adminustriggerus',
    'siteUrl' => 'https://test.craftcms.test/index.php',
    'slugWordSeparator' => '--',
    'allowUppercaseInSlug' => true,
    'securityKey' => getenv('SECURITY_KEY')
];


$testConfig = \craft\test\Craft::$testConfig;
$config['useProjectConfigFile'] = !empty($testConfig['projectConfig']);

return $config;
