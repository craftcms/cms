# Asset Transformers

A **transformer** turns an Asset and a transform definition into an `AssetTransformResult` with a URL and MIME type.
Transformers are set with the `assetTransformers` setting in `config/general.php`. Each transformer has a name, a
`driver` such as `craft` or `imgix`, and that driver's settings. Craft defines the `craft` transformer. Plugins register
other drivers.

```php
use CraftCms\Cms\Config\AssetTransformersConfig;
use CraftCms\Cms\Config\GeneralConfig;

return GeneralConfig::create()
    ->assetTransformers(AssetTransformersConfig::create()
        ->default('imgix')
        ->outputDisk('transforms')
        ->transformer('imgix', [
            'driver' => 'imgix',
            'domain' => env('IMGIX_DOMAIN'),
            'key' => env('IMGIX_KEY'),
        ])
    );
```

`AssetTransformersConfig` has these settings:

| Setting | Environment override | Meaning |
| --- | --- | --- |
| `default()` | `CRAFT_ASSET_TRANSFORMERS_DEFAULT` | The transformer for volumes that don't choose one. Without one, they use `craft`. |
| `outputDisk()` | `CRAFT_ASSET_TRANSFORMERS_OUTPUT_DISK` | The `craft` transformer's output disk. See [The `craft` driver](#the-craft-driver). |
| `generateTransformsBeforePageLoad()` | `CRAFT_ASSET_TRANSFORMERS_GENERATE_TRANSFORMS_BEFORE_PAGE_LOAD` | Whether the `craft` transformer generates transforms before returning their URLs. |
| `transformer($name, $config)` | | Defines another transformer. `$config` needs a `driver`. |

The `craft` transformer's settings have their own methods, so `transformer('craft', ...)` throws.

## Choosing a transformer

Each volume uses one transformer. Craft picks it in this order:

1. The volume's **Asset Transformer** setting, if it names a transformer. It accepts an environment variable, such as
   `$IMAGES_TRANSFORMER`. An unset variable counts as no setting.
2. The `default` in the `assetTransformers` setting.
3. The `craft` transformer.

Most sites use one transformer per environment, so they set `CRAFT_ASSET_TRANSFORMERS_DEFAULT` and leave the volume
setting empty. Set the volume setting when one volume needs a different transformer than the rest, such as a Cloud
volume next to an S3 volume.

A hosting package can set its own default, as Craft Cloud's does, by setting it only when it's `null`. That way it
doesn't override a project that chose one.

### Multiple transformers of the same type

Two transformers can use the same driver with different settings, such as two imgix sources. Name the second one and
choose it in the volumes that need it:

```php
->assetTransformers(AssetTransformersConfig::create()
    ->transformer('imgix', ['driver' => 'imgix', 'domain' => env('IMGIX_DOMAIN')])
    ->transformer('imgix-products', ['driver' => 'imgix', 'domain' => env('IMGIX_PRODUCTS_DOMAIN')])
)
```

Craft builds one driver instance per transformer.

Craft doesn't read keys in `config/filesystems.php`. 6.0.0-beta.1 read an `asset_transformer` key on disks. Craft
now reports a disk that still has it: with debug mode on, it throws an `InvalidAssetTransformException`, and otherwise
it logs an error. Move the value to the volume's setting or to the `assetTransformers` setting, then remove the key.

### Invalid configuration

Craft checks a transformer when it's first used, not at boot. If a volume or the `default` names a transformer that
isn't defined, or whose driver isn't registered:

- With debug mode on, Craft throws an `AssetTransformDriverNotFoundException`.
- Otherwise, Craft logs a warning once per process, and the volume uses the `craft` transformer.

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

Named Image Transforms store custom parameter values under the transformer's name, so every volume using that
transformer shares them. Two transformers with the same driver keep separate values.

## The `craft` driver

The `craft` driver generates transforms with Craft's own image processing. The `craft` transformer takes these
settings from `AssetTransformersConfig`. A second transformer with the `craft` driver takes the same names as keys.

| Setting | Default | Meaning |
| --- | --- | --- |
| `outputDisk` | The volume's disk | The disk to store generated transforms on, at its root rather than below the volume's subpath. To store them below a path, use a `scoped` disk. |
| `generateTransformsBeforePageLoad` | `false` | Generate each transform before returning its URL, instead of on its first request. |

`outputDisk` applies to every volume that uses the transformer, including volumes without public URLs. Their
transforms are served through Craft's action URL, but the files are only as private as the output disk. If the output
disk is publicly readable, point it at a private disk, or give private volumes a second `craft` transformer without an
output disk, as in [Different output disks per volume](#different-output-disks-per-volume).

Transforms have URLs when the volume does, and either they're stored on the volume's own disk or the output disk's
config in `config/filesystems.php` sets a `url`. A `scoped` disk uses the `url` of the disk it wraps, and Laravel ignores its own. Without a `url`,
Laravel disks still build URLs, such as `/storage/...` for local disks, and those don't serve the files. In that case
Craft serves each transform through an action URL instead.

A Craft 5 volume's Transform Filesystem and Transform Subpath become `outputDisk`, pointing at a `scoped` disk when
there was a subpath. Craft doesn't carry them over. The Yii2 Loose Ends utility lists volumes that had a
separate transform filesystem.

### Different output disks per volume

To give some volumes another output disk, add a second transformer that uses the `craft` driver, and choose it in those
volumes' Asset Transformer setting:

```php
->transformer('craft-private', ['driver' => 'craft', 'outputDisk' => null])
```

### Changing where transforms are stored

Craft doesn't record where each transform is stored. After pointing `outputDisk` at a different disk, or changing
where a disk stores files, such as a `scoped` disk's `prefix` or a bucket, clear the asset transform index with
`php artisan craft:clear-caches transform-indexes` or Utilities → Caches, so transforms are generated again in the new
location. Craft doesn't delete the old files, so clear them yourself. Changing
`generateTransformsBeforePageLoad` keeps existing transforms.

To generate transforms immediately inside a callback, whatever the setting, use
`AssetTransformers::withImmediateTransforms()`. GraphQL queries can do the same with the `immediately`
transform argument, such as `url @transform(handle: "hero", immediately: true)`.

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
    public function __construct(
        private readonly ?string $endpoint,
    ) {}

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
        $endpoint = $this->endpoint;
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

The `default` applies to volumes without public URLs too, including the temporary uploads volume. If your driver can't serve them, check
`$request->asset->getVolume()->sourceHasUrls()` and hand the request to the `craft` transformer with
`app(AssetTransformers::class)->transformer('craft')->transform($request)`.

### Definition

`AssetTransformDriverDefinition` declares the driver's parameters:

- `parameterRules` maps parameter names to Laravel validation rules. Declaring a core parameter replaces its core rules
  for this driver.
- `parameterFields` maps declared parameter names to the `Field` shown in named Image Transform forms. Each Control's
  path must match the parameter name. Fields are optional; omit one for parameters only set from PHP or Twig.

Values saved in these fields override the transform's core values when this driver runs. GraphQL's transform arguments
only include core parameters. Validate any undeclared parameters your driver uses.

### Immediate generation

`$request->immediately` is `true` when the caller wants the transform generated before its URL is returned, such as
within `AssetTransformers::withImmediateTransforms()` or for a GraphQL `immediately: true` argument, `false`
when it explicitly doesn't, and `null` when it has no preference. Drivers that generate transforms when their URL is
first requested can honor it; others can ignore it.

### Settings

A driver's settings, credentials included, are the config of each transformer that uses it. Its `extend()` callback
receives that config, `driver` key included, the way a `Storage::extend()` callback receives a disk's config:

```php
->transformer('remote-images', [
    'driver' => 'remote-images',
    'endpoint' => env('REMOTE_IMAGES_ENDPOINT'),
    'key' => env('REMOTE_IMAGES_KEY'),
])
```

Craft passes settings through unchanged, so validate them.

### Request and result

An `AssetTransformRequest` has:

- `asset`: the source Asset;
- `transformer`: the transformer, whose `name` is its name in the `assetTransformers` setting;
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
        fn (Container $container, array $config) => new RemoteImageDriver($config['endpoint'] ?? null),
    );
}
```

Volumes can only choose transformers that are defined in config, so registering a driver doesn't make it available
yet. Like a Flysystem adapter package that documents the disk to add to `config/filesystems.php`, document the
`transformer()` call users should add to `config/general.php`, as in [Settings](#settings). Don't add it to the
project's config from your plugin. Once it's defined, users choose `remote-images` in a volume's Asset Transformer
setting, or set `CRAFT_ASSET_TRANSFORMERS_DEFAULT=remote-images`.

`craft` is a reserved driver name. For a second set of settings, add a second transformer with the same driver, as in
[Multiple transformers of the same type](#multiple-transformers-of-the-same-type).

Craft reuses one driver instance per transformer, so don't keep request state on it.

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

Asset queries using `withTransforms()` call it once per transformer. A batch can include assets from several volumes. Preloading is a hint; the driver doesn't have to generate anything.

### Cache invalidation

Craft fires `AssetTransformsInvalidating` when an Asset's transforms are out of date, such as after its file changes.
Drivers with their own index or cache should listen for it.

Craft doesn't fire an event when settings change, since they're in a config file. If results depend on settings,
include them in your cache keys. The `craft` driver doesn't, so clear its index after changing where it stores transforms.

### Failures

- Throw `NotSupportedException` if the driver can't handle the source Asset's file type. The Control Panel shows a file
  icon instead.
- Throw `AssetTransformFailedException` if a supported transform fails.
- Let unexpected exceptions propagate.
