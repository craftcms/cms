<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\SystemMessage\Actions\RenderSystemMessageAction;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;

use function CraftCms\Cms\t;

beforeEach(function () {
    $this->systemMessages = app(SystemMessages::class);
});

it('enables immediate Craft transforms while rendering system messages', function () {
    $craftDriver = Mockery::mock(CraftAssetTransformDriver::class);
    $craftDriver->shouldReceive('withImmediateTransforms')
        ->once()
        ->andReturnUsing(fn (callable $callback) => $callback());
    app()->instance(CraftAssetTransformDriver::class, $craftDriver);
    $driver = new SystemMessageAssetTransformDriver;
    app(AssetTransformDrivers::class)->extend('system-message', fn () => $driver);
    app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
        'uid' => Str::uuid()->toString(),
        'name' => 'System message',
        'handle' => 'system-message',
        'driver' => 'system-message',
    ]), false);
    Cms::config()->defaultAssetTransformer('system-message');
    $asset = Asset::factory()->createElement();
    $this->systemMessages->register('asset-transform', fn () => new SystemMessage([
        'key' => 'asset-transform',
        'heading' => 'Asset transform',
        'subject' => 'Asset transform',
        'body' => '{{ asset.transform({ width: 320 }).url }}',
    ]));

    $message = app(RenderSystemMessageAction::class)->handle('asset-transform', ['asset' => $asset]);

    expect($message->textBody)->toContain('/system-message/320.webp')
        ->and($driver->requests)->not()->toBeEmpty();
});

it('resolves registered messages in the site locale', function () {
    $localeBeforeTest = app()->getLocale();
    $unsupportedLocale = 'zz-ZZ';
    app()->setLocale($unsupportedLocale);

    $resolvedLocale = null;
    app(SystemMessages::class)->register('modern', function () use (&$resolvedLocale) {
        $resolvedLocale = app()->getLocale();

        return new SystemMessage([
            'key' => 'modern',
            'heading' => 'Modern message',
            'subject' => 'Modern message',
            'body' => 'Modern message',
        ]);
    });

    try {
        expect($this->systemMessages->getAllDefaultMessages())->toHaveKey('modern')
            ->and($resolvedLocale)->toBe(Sites::getPrimarySite()->getLanguage())
            ->and(app()->getLocale())->toBe($unsupportedLocale);
    } finally {
        app()->setLocale($localeBeforeTest);
    }
});

it('caches defaults within a scope and resolves them again for the next locale scope', function () {
    $originalLocale = app()->getLocale();
    I18N::shouldReceive('getSiteLocaleIds')->andReturn(collect(['en-US', 'fr']));
    I18N::shouldReceive('translate')->andReturnUsing(fn (string $message) => $message);
    app(SystemMessages::class)->register('scoped', fn () => new SystemMessage([
        'key' => 'scoped',
        'heading' => app()->getLocale(),
        'subject' => app()->getLocale(),
        'body' => app()->getLocale(),
    ]));

    try {
        app()->setLocale('en-US');
        $systemMessages = app(SystemMessages::class);

        expect($systemMessages->getAllDefaultMessages()['scoped']->subject)->toBe('en-US');

        app()->setLocale('fr');

        expect($systemMessages->getAllDefaultMessages()['scoped']->subject)->toBe('en-US');

        app()->forgetScopedInstances();

        expect(app(SystemMessages::class)->getAllDefaultMessages()['scoped']->subject)->toBe('fr');
    } finally {
        app()->setLocale($originalLocale);
    }
});

it('can get messages including overrides', function () {
    $edition = Edition::get();

    Edition::set(Edition::Pro);

    expect($this->systemMessages->getAllMessages()->has('account_activation'))->toBeTrue();

    expect($this->systemMessages->getAllMessages()['account_activation']->subject)->toBe(t('account_activation_subject'));
    expect($this->systemMessages->getMessage('account_activation')->subject)->toBe(t('account_activation_subject'));

    $this->systemMessages->saveMessage(new SystemMessage([
        'key' => 'account_activation',
        'language' => 'en-US',
        'subject' => 'Overridden subject',
        'body' => '',
    ]));

    expect($this->systemMessages->getAllMessages()['account_activation']->subject)->toBe('Overridden subject');
    expect($this->systemMessages->getMessage('account_activation')->subject)->toBe('Overridden subject');

    Edition::set($edition);
});

class SystemMessageAssetTransformDriver implements AssetTransformDriver
{
    /** @var list<AssetTransformRequest> */
    public array $requests = [];

    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition('System message');
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        $this->requests[] = $request;

        return new AssetTransformResult(
            "/system-message/{$request->parameters['width']}.webp",
            'image/webp',
        );
    }
}
