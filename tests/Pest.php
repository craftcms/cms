<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\Tests\UnitTestCase;
use Illuminate\Http\Request;

uses(TestCase::class)->in('Feature');
uses(UnitTestCase::class)->in('Unit');

beforeEach(function () {
    app()->forgetInstance(GeneralConfig::class);
});

function swapUrlRequest(string $uri, string $method = 'GET', array $parameters = []): void
{
    app()->instance('request', Request::create($uri, $method, $parameters));
    app()->forgetScopedInstances();
}

function buildExpectedUrl(string $url, string $scheme): string
{
    $siteUrl = "$scheme://localhost/";
    $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
    $cpUrl = $cpTrigger === '' ? rtrim($siteUrl, '/') : rtrim($siteUrl, '/')."/$cpTrigger";

    return str_replace(['{siteUrl}', '{cpUrl}'], [$siteUrl, $cpUrl], $url);
}
