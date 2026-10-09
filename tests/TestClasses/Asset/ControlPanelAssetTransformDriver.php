<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Asset;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Support\Str;
use Throwable;

class ControlPanelAssetTransformDriver implements AssetTransformDriver
{
    public array $requests = [];

    public function __construct(
        private readonly ?Throwable $failure = null,
    ) {}

    public function register(): void
    {
        $driver = $this;
        app(AssetTransformDrivers::class)->extend('test', fn () => $driver);
        app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
            'uid' => Str::uuid()->toString(),
            'name' => 'Test',
            'handle' => 'test',
            'driver' => 'test',
        ]), false);
    }

    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition('Control panel test');
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        $this->requests[] = $request;

        return new AssetTransformResult(
            url: sprintf('/transforms/%sx%s.webp', $request->parameters['width'], $request->parameters['height']),
            mimeType: 'image/webp',
        );
    }
}
