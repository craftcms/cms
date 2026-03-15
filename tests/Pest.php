<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\Tests\UnitTestCase;

uses(TestCase::class)->in('Feature');
uses(UnitTestCase::class)->in('Unit');

beforeEach(function () {
    app()->forgetInstance(GeneralConfig::class);
});
