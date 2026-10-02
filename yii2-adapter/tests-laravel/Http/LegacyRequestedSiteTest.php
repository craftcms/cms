<?php

declare(strict_types=1);

use craft\web\Request;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Update\Updates;
use Illuminate\Http\Request as IlluminateRequest;

beforeEach(function() {
    $this->originalSiteEnvironment = getenv('CRAFT_SITE');
    $this->originalSiteServer = $_SERVER['CRAFT_SITE'] ?? null;
    $this->originalSiteEnv = $_ENV['CRAFT_SITE'] ?? null;
    $this->originalSiteUpper = $_SERVER['CRAFT_SITE_UPPER'] ?? null;

    unset($_SERVER['CRAFT_SITE'], $_ENV['CRAFT_SITE']);
    putenv('CRAFT_SITE');
    Sites::setCurrentSite(null);
});

afterEach(function() {
    putenv($this->originalSiteEnvironment === false ? 'CRAFT_SITE' : "CRAFT_SITE={$this->originalSiteEnvironment}");

    if ($this->originalSiteServer === null) {
        unset($_SERVER['CRAFT_SITE']);
    } else {
        $_SERVER['CRAFT_SITE'] = $this->originalSiteServer;
    }

    if ($this->originalSiteEnv === null) {
        unset($_ENV['CRAFT_SITE']);
    } else {
        $_ENV['CRAFT_SITE'] = $this->originalSiteEnv;
    }

    if ($this->originalSiteUpper === null) {
        unset($_SERVER['CRAFT_SITE_UPPER']);
    } else {
        $_SERVER['CRAFT_SITE_UPPER'] = $this->originalSiteUpper;
    }
});

it('initializes control panel requests with a missing requested site during installation or updates', function(bool $installed, bool $header) {
    Cms::setIsInstalled($installed);
    $this->mock(Updates::class)->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $httpRequest = IlluminateRequest::create('/' . Cms::config()->cpTrigger . '/install');
    if ($header) {
        $httpRequest->headers->set('X-Craft-Site', 'missing');
    } else {
        $_SERVER['CRAFT_SITE'] = 'missing';
    }
    app()->instance('request', $httpRequest);

    $request = new Request();

    expect($request->getIsCpRequest())->toBeTrue()
        ->and($request->getPathInfo())->toBe('install')
        ->and(Sites::getHasCurrentSite())->toBeFalse();
})->with([
    'installation' => false,
    'pending update' => true,
])->with([
    'environment' => false,
    'header' => true,
]);

it('resolves the site by URL when the requested site is missing during an update', function() {
    Cms::setIsInstalled();
    $this->mock(Updates::class)->shouldReceive('isCraftUpdatePending')->andReturn(true);
    $_SERVER['CRAFT_SITE'] = 'missing';

    Sites::partialMock()->shouldReceive('getSiteByHandle')->andReturnNull();
    Sites::shouldReceive('getAllSites')->andReturn(collect([
        new Site(['id' => 1, 'handle' => 'other', 'primary' => true, 'baseUrl' => 'https://other.example.test/']),
        new Site(['id' => 2, 'handle' => 'news', 'baseUrl' => 'https://site.example.test/news/']),
    ]));
    app()->instance('request', IlluminateRequest::create('https://site.example.test/news/article'));

    $request = new Request();

    expect(Sites::getCurrentSite()->id)->toBe(2)
        ->and($request->getIsSiteRequest())->toBeTrue()
        ->and($request->getPathInfo())->toBe('article');
});

it('rejects a missing requested site when Craft is installed and current', function() {
    Cms::setIsInstalled();
    $this->mock(Updates::class)->shouldReceive('isCraftUpdatePending')->andReturn(false);
    $_SERVER['CRAFT_SITE'] = 'missing';
    app()->instance('request', IlluminateRequest::create('/'));

    expect(fn() => new Request())->toThrow(InvalidArgumentException::class, 'Invalid site: missing');
});
