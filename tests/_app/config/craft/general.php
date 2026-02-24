<?php

use craft\config\GeneralConfig;

return GeneralConfig::create()
    ->aliases(['@webroot' => dirname(__DIR__, 2).'/public']);
