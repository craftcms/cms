# Asset Transformers

A **transformer** turns an Asset and a transform definition into an `AssetTransformResult` with a URL and MIME type. Each
transformer is a **driver**, registered under a name such as `craft` or `remote-images`, plus optional inline settings.

## Choosing a transformer per disk

Set `asset_transformer` on a disk in `config/filesystems.php`. Use a driver name, or an array with a `driver` key and
inline settings, the same way a disk is written:

```php
'disks' => [
    // No key: the `craft` driver with its defaults.
    'local' => ['driver' => 'local', 'root' => storage_path('app/public')],

    's3' => ['driver' => 's3', /* ... */, 'asset_transformer' => 'remote-images'],

    'uploads' => ['driver' => 's3', /* ... */, 'asset_transformer' => [
        'driver' => 'craft',
        'output_disk' => 'transforms',
    ]],

    // A scoped disk without the key uses the key of the disk it wraps: `remote-images`.
    'blog' => ['driver' => 'scoped', 'disk' => 's3', 'prefix' => 'blog'],
],
```

Volumes on the same disk share its transformer. To give one volume a different transformer, point it at its own disk,
such as a `scoped` disk that wraps the shared one.

### Invalid configuration

Craft checks a transformer when it's first used, not at boot. If an `asset_transformer` value isn't a driver name or an
array with a `driver` key, or names a driver that isn't registered:

- With debug mode on, Craft throws an `InvalidAssetTransformException` or `AssetTransformDriverNotFoundException`.
- Otherwise, Craft logs a warning once per process, and the disk falls back to the `craft` driver with its defaults.

## Transform definitions

`Asset::transform()` accepts:

- an array of parameters;
- a named Image Transform handle;
- an `ImageTransform` object;
- an array with a `transform` key naming a base transform, plus parameters to override.

```php
$asset->transform(['transform' => 'card', 'width' => 800]);
```

Core parameters accept these values:

| Parameter | Accepted value |
| --- | --- |
| `fill` | String |
| `format` | An `ImageTransformFormat` value |
| `height` | Integer of at least 1 |
| `interlace` | An `ImageTransformInterlace` value |
| `mode` | An `ImageTransformMode` value |
| `position` | An `ImageTransformPosition` value |
| `quality` | Integer from 1 through 100 |
| `upscale` | Boolean |
| `width` | Integer of at least 1 |

A driver can declare more parameters or replace a core parameter's rules. A Cloudflare driver could, for example, let
`quality` also accept presets such as `high`. Craft validates core and declared parameters, passes others through
unchanged, and sorts parameters by name before calling the driver.

Named Image Transforms store a driver's custom parameter values under its driver name, so every disk using that driver
shares them.

## The `craft` driver

The `craft` driver generates transforms with Craft's own image processing. It accepts these inline settings:

| Setting | Default | Meaning |
| --- | --- | --- |
| `output_disk` | The source disk | The disk to store generated transforms on. To store them below a path, use a `scoped` disk. |
| `has_urls` | `false` | Whether `output_disk` serves public URLs. Without `output_disk`, the volume's setting applies. |
| `generate_transforms_before_page_load` | `false` | Generate each transform before returning its URL, instead of on its first request. |

```php
'transforms' => ['driver' => 'scoped', 'disk' => 's3', 'prefix' => '_transforms'],

'uploads' => [
    'driver' => 's3',
    // ...
    'asset_transformer' => [
        'driver' => 'craft',
        'output_disk' => 'transforms',
        'has_urls' => true,
    ],
],
```

A Craft 5 volume's Transform Filesystem and Transform Subpath become a `scoped` disk like this one.

Use `env()` for values that differ between environments.

Pointing `output_disk` at a different disk makes Craft generate new transforms there. Craft doesn't delete the old
files, so clear them yourself. Changing `has_urls` or `generate_transforms_before_page_load` keeps existing transforms.

Changing a disk's own config, such as a `scoped` disk's `prefix` or a bucket, doesn't. Clear the asset transform index
afterwards, with `php artisan craft:clear-caches transform-indexes` or Utilities → Caches, so transforms are generated
again in the new location.

To generate transforms immediately inside a callback, whatever the setting, use
`CraftAssetTransformDriver::withImmediateTransforms()`.

## Writing a driver

Implement `AssetTransformDriver`:

```php
<?php

declare(strict_types=1);

namespace Acme\RemoteImages;

use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Nodes\Field;

use function CraftCms\Cms\t;

class RemoteImageDriver implements AssetTransformDriver
{
    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition(
            parameterRules: [
                'devicePixelRatio' => ['numeric', 'between:1,4'],
            ],
            parameterFields: [
                'devicePixelRatio' => Field::make(
                    t('Device Pixel Ratio', category: 'remote-images'),
                    Number::make('devicePixelRatio')->min(1)->max(4),
                ),
            ],
        );
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        $endpoint = $request->transformer->settings['endpoint'] ?? config('services.remote_images.endpoint');
        $sourceUrl = $request->asset->getUrl();

        if (! is_string($endpoint) || $endpoint === '' || $sourceUrl === null) {
            throw new AssetTransformFailedException('The remote image request could not be created.');
        }

        $format = $request->parameters['format'] ?? $request->asset->getExtension();
        $query = http_build_query(['source' => $sourceUrl, ...$request->parameters]);

        return new AssetTransformResult(
            url: rtrim($endpoint, '/').'/render?'.$query,
            mimeType: File::getMimeTypeByExtension("transform.{$format}") ?? 'application/octet-stream',
        );
    }
}
```

This example needs a public source URL. For private Assets, upload the source or sign the request.

### Definition

`AssetTransformDriverDefinition` declares the driver's parameters:

- `parameterRules` maps parameter names to Laravel validation rules. Declaring a core parameter replaces its core rules
  for this driver.
- `parameterFields` maps declared parameter names to the `Field` shown in named Image Transform forms. Each Control's
  path must match the parameter name. Fields are optional; omit one for parameters only set from PHP or Twig.

Values saved in these fields override the transform's core values when this driver runs. GraphQL's transform arguments
only include core parameters. Validate any undeclared parameters your driver uses.

### Settings

A driver gets the disk's inline settings, credentials included, in `$request->transformer->settings`:

```php
'asset_transformer' => [
    'driver' => 'remote-images',
    'endpoint' => env('REMOTE_IMAGES_ENDPOINT'),
    'key' => env('REMOTE_IMAGES_KEY'),
],
```

Craft passes settings through unchanged, so validate them. A driver can fall back to shared config, such as
`config/services.php`, so disks on the same account don't repeat it. Let inline settings win, the way Laravel's mail
transports merge mailer settings over `services.php`.

### Request and result

An `AssetTransformRequest` has:

- `asset`: the source Asset;
- `transformer`: the driver name and inline settings;
- `parameters`: the validated parameters.

An `AssetTransformResult` needs a URL and MIME type. It can also give the output's `filename`, `width`, `height`, and
`size`. Describe the output, not the source Asset; GraphQL's transformed `url`, `mimeType`, `width`, and `height`
fields read it.

### Registering

Register the driver from a service provider:

```php
use CraftCms\Cms\Asset\AssetTransformers;
use Illuminate\Contracts\Container\Container;

public function boot(AssetTransformers $transformers): void
{
    $transformers->extend(
        'remote-images',
        fn (Container $container) => $container->make(RemoteImageDriver::class),
    );
}
```

Then set `'asset_transformer' => 'remote-images'` on a disk.

Craft reuses one driver instance per name, so don't keep request state on it.

### Preloading

Implement `PreloadsAssetTransforms` if the service can prepare transforms in batches:

```php
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;

class RemoteImageDriver implements AssetTransformDriver, PreloadsAssetTransforms
{
    public function preloadAssetTransforms(array $requests): void
    {
        $this->client->preload($requests);
    }
}
```

Asset queries using `withTransforms()` call it once per driver. A batch can include several disks, so read settings
from each request. Preloading is a hint; the driver doesn't have to generate anything.

### Cache invalidation

Craft fires `AssetTransformsInvalidating` when an Asset's transforms are out of date, such as after its file changes.
Drivers with their own index or cache should listen for it.

Craft doesn't fire an event when settings change, since they're in config files. If results depend on settings,
include them in your cache keys, the way the `craft` driver keys its index by output location.

### Failures

- Throw `NotSupportedException` if the driver can't handle the source Asset's file type. The Control Panel shows a file
  icon instead.
- Throw `AssetTransformFailedException` if a supported transform fails.
- Let unexpected exceptions propagate.
