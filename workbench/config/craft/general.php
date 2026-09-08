<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;

return GeneralConfig::create()
    ->maxUploadFileSize(200 * 1024 * 1024);
