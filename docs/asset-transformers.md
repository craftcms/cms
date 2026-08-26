# Asset Transformers

Asset Transformers turn an Asset and an Image Transform definition into a transform result. A transform result can be an image generated
by Craft, a URL produced by an external image service, or another representation supplied by a plugin.

The system separates two concepts:

- An **Asset Transformer** is a project-configured profile. It has a name, handle, driver, and driver-specific settings.
- An **Asset Transform driver** is the PHP implementation that validates its supported parameters and produces transform results.

Several transformers can use the same driver with different settings. For example, two transformers could use one remote
image-service driver but target different accounts or delivery domains.

Transformers are stored in Project Config under `assetTransformers`. Manage them from **Settings → Assets → Asset
Transformers**. Driver settings are defined by the driver and rendered through the Control Panel Form system.

An additional transformer cannot be deleted while it is the configured default or is assigned to a volume. Renaming its
handle updates direct default and volume references. If a plugin that provides a driver is unavailable, Craft retains the
transformer and its settings in Project Config, marks the driver as unavailable, and prevents the transformer from being
saved with that missing driver.

## Transformer selection

Craft selects one transformer for every transform request, in this order:

1. The `transformer` handle in the transform definition.
2. The transformer assigned to the Asset’s volume.
3. The [`defaultAssetTransformer`](../src/Config/GeneralConfig.php) general config setting, which defaults to `craft`.

An explicit transformer can be combined with an inline transform:

```php
$result = $asset->transform([
    'transformer' => 'remote-images',
    'width' => 1200,
    'format' => 'webp',
]);

$url = $result->url;
```

The same override is available through `getUrl()` and Twig:

```twig
<img src="{{ asset.getUrl({
    transformer: 'remote-images',
    width: 1200,
    format: 'webp',
}) }}" alt="">
```

GraphQL transform arguments also accept `transformer`:

```graphql
query {
  asset(id: 42) {
    url(transformer: "remote-images", width: 1200, format: "webp")
  }
}
```

The `transformer` key selects the profile and is not passed to the driver as a parameter.

## Transform definitions

Asset Transformers accept the same transform definition forms as Assets:

- an inline parameter array;
- a named Image Transform handle;
- an `ImageTransform` data object; or
- an array with a `transform` key that names a base transform and overrides selected parameters.

```php
$result = $asset->transform([
    'transform' => 'card',
    'transformer' => 'remote-images',
    'width' => 800,
]);
```

Craft normalizes the definition, merges overrides, selects the transformer, and validates the parameters before invoking
the driver. The core parameters are:

| Parameter | Accepted value |
| --- | --- |
| `fill` | String |
| `format` | An `ImageTransformFormat` value |
| `height` | Integer greater than zero |
| `interlace` | An `ImageTransformInterlace` value |
| `mode` | An `ImageTransformMode` value |
| `position` | An `ImageTransformPosition` value |
| `quality` | Integer from 1 through 100 |
| `upscale` | Boolean |
| `width` | Integer greater than zero |

A driver can declare further parameters. Unknown parameters are removed, invalid values cause an
`InvalidAssetTransformException`, and the driver receives parameters sorted by handle.

Named Image Transforms store custom parameter values against the transformer UUID. This keeps values for transformers that
use different parameter sets separate, even if the transformers share a driver.

## Immediate and deferred generation

`Asset::transform()` and `Asset::getUrl()` accept an optional `immediately` argument:

```php
$result = $asset->transform(['width' => 800], immediately: true);
```

When it is omitted, Craft uses the `generateTransformsBeforePageLoad` general config setting. The resolved boolean is
available as `AssetTransformRequest::$immediately`.

The meaning is part of the driver contract:

- `true` asks the driver to ensure that the transform result is available before returning.
- `false` allows the driver to return a URL whose first request generates or warms the transform result.

A remote service that always transforms on demand can return the same URL in both cases, but should warm or verify it
when `immediately` is `true` if the service supports that behavior.

## The Craft transformer

Craft always provides a reserved transformer with the `craft` handle and driver. Its name, handle, and driver cannot be
changed, and it cannot be deleted. Its settings remain editable:

- **Output Filesystem** selects where generated transforms are stored. Leave it empty to use the source Asset’s
  filesystem.
- **Output Subpath** places generated transforms below a subpath on the selected output filesystem.

Both settings accept environment-variable aliases. The built-in driver parses those aliases when resolving its output
filesystem.

The Craft driver uses Craft’s local image transformer. It rejects source formats that Craft cannot manipulate, maintains
the transform index, supports deferred generation, and preloads transform-index data for Asset queries.

## Implementing a custom driver

A custom driver implements `AssetTransformDriver`:

```php
<?php

declare(strict_types=1);

namespace Acme\RemoteImages;

use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\File;
use Illuminate\Http\Client\Factory;

use function CraftCms\Cms\t;

class RemoteImageDriver implements AssetTransformDriver
{
    public function __construct(
        private readonly Factory $http,
    ) {}

    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition(
            name: t('Remote Images', category: 'remote-images'),
            parameterRules: [
                'devicePixelRatio' => ['numeric', 'between:1,4'],
            ],
            settingsFields: [
                Field::make(
                    t('Endpoint', category: 'remote-images'),
                    Text::make('endpoint'),
                )->required(),
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
        $endpoint = Env::parse($request->transformer->settings['endpoint'] ?? null);
        $sourceUrl = $request->asset->getUrl();

        if (! is_string($endpoint) || $endpoint === '' || $sourceUrl === null) {
            throw new AssetTransformFailedException('The remote image request could not be created.');
        }

        $format = $request->parameters['format'] ?? $request->asset->getExtension();
        $query = http_build_query([
            'source' => $sourceUrl,
            ...$request->parameters,
        ]);
        $url = sprintf('%s/render?%s', rtrim($endpoint, '/'), $query);

        if ($request->immediately) {
            $this->http->head($url)->throw();
        }

        return new AssetTransformResult(
            url: $url,
            mimeType: File::getMimeTypeByExtension("transform.{$format}") ?? 'application/octet-stream',
        );
    }
}
```

This example assumes the source Asset has a public URL and the service supports warming with a `HEAD` request. A
production driver should use the service’s authenticated source-upload or signing flow when source Assets can be private.

### The driver definition

`AssetTransformDriverDefinition` describes the driver’s Control Panel and validation surface:

- `name` is the human-readable driver name.
- `parameterRules` maps custom parameter handles to Laravel validation rules.
- `settingsFields` contains `Field` nodes for configuring each transformer profile.
- `parameterFields` maps parameter handles to `Field` nodes shown in named Image Transform forms.

Core parameters are already available to every driver and should not be redeclared. A custom parameter that uses a core
handle must use exactly the core validation rules; conflicting rules cause an `InvalidAssetTransformException`.

Each settings field must use a single-segment Control path. The Control Panel only persists submitted settings declared
by the driver. The driver must still validate the resolved setting values at its runtime boundary, particularly URLs,
credentials, and environment-variable aliases.

Transformer settings are stored in Project Config. Store credentials as environment-variable aliases rather than literal
secrets, and resolve them inside the driver with `Env::parse()`.

Each parameter field must:

- have the same array key and Control path as a declared custom parameter; and
- use a Control whose submitted value satisfies the parameter’s validation rules.

Parameter fields are optional. Omit one when the parameter is intended only for inline PHP or Twig use. Custom parameters
are not added to the GraphQL transform argument schema automatically.

### The transform request

`AssetTransformRequest` contains:

- `asset`: the source `Asset` element;
- `transformer`: the selected transformer profile, including its driver settings;
- `parameters`: normalized and validated core and custom parameters; and
- `immediately`: the resolved generation policy.

Do not read the transformer handle back out of the original definition. Craft has already selected the transformer and
removed the routing key.

### The transform result

`AssetTransformResult` requires a URL and MIME type. It can also report the transform result’s filename, width,
height, and byte size:

```php
return new AssetTransformResult(
    url: $result->url,
    mimeType: $result->mimeType,
    filename: $result->filename,
    width: $result->width,
    height: $result->height,
    size: $result->size,
);
```

Return metadata for the generated transform result, not the source Asset. Consumers such as GraphQL use these values when
resolving transformed Asset fields.

### Registering the driver

Register the driver from the plugin or application service provider before Asset Transformer settings are resolved:

```php
<?php

declare(strict_types=1);

namespace Acme\RemoteImages;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class RemoteImagesServiceProvider extends ServiceProvider
{
    public function boot(AssetTransformDrivers $drivers): void
    {
        $drivers->extend(
            'remote-images',
            fn (Container $container) => $container->make(RemoteImageDriver::class),
        );
    }
}
```

The registration handle identifies the driver implementation. After registering it, create an Asset Transformer in the
Control Panel and select **Remote Images** as its driver. The configured transformer handle is what volumes, transform
definitions, and `defaultAssetTransformer` reference.

Laravel’s driver manager resolves and caches a driver instance by registration handle. Keep request-specific state in
the `AssetTransformRequest`, not on the driver instance.

## Optional transform preloading

Implement `PreloadsAssetTransforms` when the backing service can prepare multiple requests more efficiently than
processing them individually:

```php
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;

class RemoteImageDriver implements AssetTransformDriver, PreloadsAssetTransforms
{
    // definition() and transform() ...

    public function preloadAssetTransforms(array $requests): void
    {
        $this->client->preload($requests);
    }
}
```

Asset queries using `withTransforms()` group requests by driver and call this method once per driver. Requests can still
belong to different transformer profiles, so use `AssetTransformRequest::$transformer` for profile-specific settings.
Preloading is an optimization and does not guarantee that generated files have materialized.

## Cache invalidation and transformer changes

Drivers that maintain their own transform index or remote cache can listen for:

- `AssetTransformsInvalidating` when all transform results for an Asset should be invalidated;
- `AssetTransformerUpdating` when a transformer’s driver or settings change; and
- `AssetTransformerDeleting` before a transformer profile is removed.

The update event contains both the old and new transformer profiles. Use their UUIDs as stable cache namespaces; transformer
handles can be renamed.

## Failure behavior

Fail explicitly when a request cannot be fulfilled:

- throw `NotSupportedException` when the source Asset type is outside the driver’s capabilities;
- throw `AssetTransformFailedException` when a supported transform fails; and
- allow unexpected exceptions to remain observable rather than returning a plausible but invalid result.

Craft uses `NotSupportedException` to fall back to file-kind icons in Control Panel thumbnail and preview contexts. Other
transform failures are reported to the caller.
