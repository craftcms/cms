# Asset Transformers

Craft 6 includes an asset transformer registry that lets plugins add new ways to transform assets.

Craft registers its built-in image transformer with the `craft` handle. Plugins can register additional transformer handles for remote image services, video processors, PDF preview generators, text renderers, or any other asset transformation workflow.

The registry is available through `CraftCms\Cms\Asset\AssetTransforms` or the `CraftCms\Cms\Support\Facades\AssetTransforms` facade.

## How Transformers Are Selected

When an asset transform is requested, Craft resolves the transformer in this order:

1. the `transformer` value in the transform array
2. the transformer selected by the named image transform
3. the asset volume's default transformer
4. Craft's built-in `craft` transformer

If a configured transformer handle is missing, Craft logs a warning and falls back to `craft`.

```php
<?php

$url = $asset->getUrl([
    'transformer' => 'my-service',
    'width' => 1200,
    'height' => 800,
    'blur' => 12,
]);
```

Unknown transform options are preserved on the transform's `settings` array. Transformers can use the options they support and ignore the rest.

## Registering a Transformer

Register transformers by listening for `AssetTransformersResolving`.

```php
<?php

namespace App\Providers;

use App\Assets\MyServiceTransformer;
use CraftCms\Cms\Image\Events\AssetTransformersResolving;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(AssetTransformersResolving::class, function (AssetTransformersResolving $event) {
            $event->types[MyServiceTransformer::handle()] = MyServiceTransformer::class;
        });
    }
}
```

Handles should be stable, unique, and safe to store in project config.

## Implementing a Transformer

Transformers implement `CraftCms\Cms\Image\Contracts\AssetTransformerInterface`.

```php
<?php

namespace App\Assets;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Contracts\AssetTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use GraphQL\Type\Definition\Type;

class MyServiceTransformer implements AssetTransformerInterface
{
    public static function handle(): string
    {
        return 'my-service';
    }

    public static function displayName(): string
    {
        return 'My Service';
    }

    public static function gqlArguments(): array
    {
        return [
            'blur' => [
                'type' => Type::int(),
                'description' => 'Blur amount for the transformed asset.',
            ],
        ];
    }

    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        if (! str_starts_with((string) $asset->getMimeType(), 'image/')) {
            throw new NotSupportedException('Only images are supported.');
        }

        $settings = $imageTransform->settings;

        $sourceUrl = $asset->getUrl();

        if ($sourceUrl === null) {
            // Use $asset->getStream() or $asset->getCopyOfFile() and upload the
            // source somewhere the transformer can read from.
            throw new NotSupportedException('A public source URL is required.');
        }

        return 'https://img.example.test/'.http_build_query([
            'src' => $sourceUrl,
            'w' => $imageTransform->width,
            'h' => $imageTransform->height,
            'blur' => $settings['blur'] ?? null,
        ]);
    }

    public function invalidateAssetTransforms(Asset $asset): void
    {
        // Purge remote transforms, delete generated files, or clear internal caches.
    }

    public function getTransformString(ImageTransform $imageTransform, bool $ignoreHandle = false): string
    {
        $parts = [
            'w' => $imageTransform->width,
            'h' => $imageTransform->height,
            'format' => $imageTransform->format,
            'blur' => $imageTransform->settings['blur'] ?? null,
        ];

        return http_build_query(array_filter($parts, fn (mixed $value): bool => $value !== null));
    }

    public function getSettingsHtml(ImageTransform $imageTransform, bool $readOnly = false): ?string
    {
        return null;
    }
}
```

## Runtime Behavior

`getTransformUrl()` should return the final URL for the transformed asset.

Use `NotSupportedException` when the transformer cannot handle the asset or transform options. Craft catches that exception when rendering asset URLs, logs it, and returns `null`.

The `$immediately` argument tells the transformer whether Craft wants the transform generated before returning the URL. Transformers that delegate to remote services may ignore it when the returned URL is enough to trigger generation externally.

## Cache Keys

`getTransformString()` defines the transformer's canonical cache key. Include only options that affect the generated result.

This is what lets one transformer support an option like `blur` while another transformer can safely ignore it. Ignored options stay in `ImageTransform::$settings`, but they should not be included in the transform string unless they affect output.

## Settings UI

Named image transforms can store transformer-specific settings. Return CP HTML from `getSettingsHtml()` when your transformer needs fields on the named transform edit screen.

Fields should post under `settings`.

```php
public function getSettingsHtml(ImageTransform $imageTransform, bool $readOnly = false): ?string
{
    $value = htmlspecialchars((string) ($imageTransform->settings['blur'] ?? ''), ENT_QUOTES);
    $disabled = $readOnly ? ' disabled' : '';

    return <<<HTML
<div class="field">
    <div class="heading">
        <label for="settings-blur">Blur</label>
    </div>
    <div class="input ltr">
        <input type="number" id="settings-blur" name="settings[blur]" value="{$value}"{$disabled}>
    </div>
</div>
HTML;
}
```

Craft preserves unsupported settings when the runtime transformer differs from the transformer that originally supplied the settings UI.

## GraphQL Arguments

Transformers may add transform arguments for GraphQL by returning definitions from `gqlArguments()`.

```php
public static function gqlArguments(): array
{
    return [
        'blur' => [
            'type' => Type::int(),
            'description' => 'Blur amount for the transformed asset.',
        ],
    ];
}
```

Argument names must not collide with Craft's built-in transform arguments or arguments registered by another transformer. Craft throws an `ImageTransformException` if a collision is detected.

## Volume Defaults and Named Transforms

Volumes have a default transformer setting. Use that for environment-specific behavior, such as a cloud image service in staging and production but Craft's local transformer in development.

Named image transforms can optionally select a transformer. Leave the named transform set to the volume default when the transform should follow the asset's volume.

Per-call `transformer` values still take precedence:

```twig
{{ asset.url({
    transformer: 'my-service',
    transform: 'hero',
    blur: 8,
}) }}
```

## Filesystems With and Without URLs

Transformers should work with volumes whose transform filesystems have public URLs and volumes whose filesystems do not.

Craft's built-in `craft` transformer returns direct filesystem URLs when possible and signed Craft action URLs for private filesystems. Custom transformers should follow the same principle: return a usable URL regardless of whether the source or transform filesystem has public URLs.

If your transformer needs to read the original asset and `Asset::getUrl()` returns `null`, use `Asset::getStream()` or `Asset::getCopyOfFile()` instead of requiring a public source URL.

## Legacy Image Transformers

The legacy image transformer API remains available through the Yii adapter layer for backwards compatibility. Legacy transformers registered through `craft\services\ImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS` are bridged into the asset transformer registry by their class name.

New code should implement `AssetTransformerInterface` and register a stable handle instead of registering a legacy image transformer class.
