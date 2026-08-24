# Asset Processors

Asset Processors turn an Asset and an Asset Transform definition into a rendition. A rendition can be an image generated
by Craft, a URL produced by an external image service, or another representation supplied by a plugin.

The system separates two concepts:

- An **Asset Processor** is a project-configured profile. It has a name, handle, driver, and driver-specific settings.
- An **Asset Processor driver** is the PHP implementation that validates its supported operations and produces rendition
  results.

Several processors can use the same driver with different settings. For example, two processors could use one remote
image-service driver but target different accounts or delivery domains.

Processors are stored in Project Config under `assetProcessors`. Manage them from **Settings → Assets → Asset
Processors**. Driver settings are defined by the driver and rendered through the Control Panel Form system.

An additional processor cannot be deleted while it is the configured default or is assigned to a volume. Renaming its
handle updates direct default and volume references. If a plugin that provides a driver is unavailable, Craft retains the
processor and its settings in Project Config, marks the driver as unavailable, and prevents the processor from being
saved with that missing driver.

## Processor selection

Craft selects one processor for every transform request, in this order:

1. The `processor` handle in the transform definition.
2. The processor assigned to the Asset’s volume.
3. The [`defaultAssetProcessor`](../src/Config/GeneralConfig.php) general config setting, which defaults to `craft`.

An explicit processor can be combined with an inline transform:

```php
$result = $asset->transform([
    'processor' => 'remote-images',
    'width' => 1200,
    'format' => 'webp',
]);

$url = $result->url;
```

The same override is available through `getUrl()` and Twig:

```twig
<img src="{{ asset.getUrl({
    processor: 'remote-images',
    width: 1200,
    format: 'webp',
}) }}" alt="">
```

GraphQL transform arguments also accept `processor`:

```graphql
query {
  asset(id: 42) {
    url(processor: "remote-images", width: 1200, format: "webp")
  }
}
```

The `processor` key selects the profile and is not passed to the driver as an operation.

## Transform definitions

Asset Processors accept the same transform definition forms as Assets:

- an inline operation array;
- a named Image Transform handle;
- an `ImageTransform` data object; or
- an array with a `transform` key that names a base transform and overrides selected operations.

```php
$result = $asset->transform([
    'transform' => 'card',
    'processor' => 'remote-images',
    'width' => 800,
]);
```

Craft normalizes the definition, merges overrides, selects the processor, and validates the operations before invoking
the driver. The core operations are:

| Operation | Accepted value |
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

A driver can declare further operations. Unknown operations are removed, invalid values cause an
`InvalidAssetTransformException`, and the driver receives operations sorted by handle.

Named Image Transforms store custom operation values against the processor UUID. This keeps settings for processors that
use different operation sets separate, even if the processors share a driver.

## Immediate and deferred generation

`Asset::transform()` and `Asset::getUrl()` accept an optional `immediately` argument:

```php
$result = $asset->transform(['width' => 800], immediately: true);
```

When it is omitted, Craft uses the `generateTransformsBeforePageLoad` general config setting. The resolved boolean is
available as `AssetTransformRequest::$immediately`.

The meaning is part of the driver contract:

- `true` asks the driver to ensure that the rendition is available before returning.
- `false` allows the driver to return a URL whose first request generates or warms the rendition.

A remote service that always transforms on demand can return the same URL in both cases, but should warm or verify it
when `immediately` is `true` if the service supports that behavior.

## The Craft processor

Craft always provides a reserved processor with the `craft` handle and driver. Its name, handle, and driver cannot be
changed, and it cannot be deleted. Its settings remain editable:

- **Output Filesystem** selects where generated renditions are stored. Leave it empty to use the source Asset’s
  filesystem.
- **Output Subpath** places renditions below a subpath on the selected output filesystem.

Both settings accept environment-variable aliases. The built-in driver parses those aliases when resolving its output
filesystem.

The Craft driver uses Craft’s local image transformer. It rejects source formats that Craft cannot manipulate, maintains
the transform index, supports deferred generation, and preloads transform-index data for Asset queries.

## Implementing a custom driver

A custom driver implements `AssetProcessorDriver`:

```php
<?php

declare(strict_types=1);

namespace Acme\RemoteImages;

use CraftCms\Cms\Asset\Contracts\AssetProcessorDriver;
use CraftCms\Cms\Asset\Data\AssetProcessorDriverDefinition;
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

class RemoteImageDriver implements AssetProcessorDriver
{
    public function __construct(
        private readonly Factory $http,
    ) {}

    public function definition(): AssetProcessorDriverDefinition
    {
        return new AssetProcessorDriverDefinition(
            name: t('Remote Images', category: 'remote-images'),
            operations: [
                'devicePixelRatio' => ['numeric', 'between:1,4'],
            ],
            settings: [
                Field::make(
                    t('Endpoint', category: 'remote-images'),
                    Text::make('endpoint'),
                )->required(),
            ],
            operationFields: [
                'devicePixelRatio' => Field::make(
                    t('Device Pixel Ratio', category: 'remote-images'),
                    Number::make('devicePixelRatio')->min(1)->max(4),
                ),
            ],
        );
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        $endpoint = Env::parse($request->processor->settings['endpoint'] ?? null);
        $sourceUrl = $request->asset->getUrl();

        if (! is_string($endpoint) || $endpoint === '' || $sourceUrl === null) {
            throw new AssetTransformFailedException('The remote image request could not be created.');
        }

        $format = $request->operations['format'] ?? $request->asset->getExtension();
        $query = http_build_query([
            'source' => $sourceUrl,
            ...$request->operations,
        ]);
        $url = sprintf('%s/render?%s', rtrim($endpoint, '/'), $query);

        if ($request->immediately) {
            $this->http->head($url)->throw();
        }

        return new AssetTransformResult(
            url: $url,
            mimeType: File::getMimeTypeByExtension("rendition.{$format}") ?? 'application/octet-stream',
        );
    }
}
```

This example assumes the source Asset has a public URL and the service supports warming with a `HEAD` request. A
production driver should use the service’s authenticated source-upload or signing flow when source Assets can be private.

### The driver definition

`AssetProcessorDriverDefinition` describes the driver’s Control Panel and validation surface:

- `name` is the human-readable driver name.
- `operations` maps custom operation handles to Laravel validation rules.
- `settings` contains `Field` nodes for configuring each processor profile.
- `operationFields` maps operation handles to `Field` nodes shown in named Image Transform forms.

Core operations are already available to every driver and should not be redeclared. A custom operation that uses a core
handle must use exactly the core validation rules; conflicting rules cause an `InvalidAssetTransformException`.

Each settings field must use a single-segment Control path. The Control Panel only persists submitted settings declared
by the driver. The driver must still validate the resolved setting values at its runtime boundary, particularly URLs,
credentials, and environment-variable aliases.

Processor settings are stored in Project Config. Store credentials as environment-variable aliases rather than literal
secrets, and resolve them inside the driver with `Env::parse()`.

Each operation field must:

- have the same array key and Control path as a declared custom operation; and
- use a Control whose submitted value satisfies the operation’s validation rules.

Operation fields are optional. Omit one when the operation is intended only for inline PHP or Twig use. Custom operations
are not added to the GraphQL transform argument schema automatically.

### The transform request

`AssetTransformRequest` contains:

- `asset`: the source `Asset` element;
- `processor`: the selected processor profile, including its driver settings;
- `operations`: normalized and validated core and custom operations; and
- `immediately`: the resolved generation policy.

Do not read the processor handle back out of the original definition. Craft has already selected the processor and
removed the routing key.

### The transform result

`AssetTransformResult` requires a rendition URL and MIME type. It can also report the rendition’s filename, width,
height, and byte size:

```php
return new AssetTransformResult(
    url: $rendition->url,
    mimeType: $rendition->mimeType,
    filename: $rendition->filename,
    width: $rendition->width,
    height: $rendition->height,
    size: $rendition->size,
);
```

Return metadata for the generated rendition, not the source Asset. Consumers such as GraphQL use these values when
resolving transformed Asset fields.

### Registering the driver

Register the driver from the plugin or application service provider before Asset Processor settings are resolved:

```php
<?php

declare(strict_types=1);

namespace Acme\RemoteImages;

use CraftCms\Cms\Asset\AssetProcessorDrivers;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class RemoteImagesServiceProvider extends ServiceProvider
{
    public function boot(AssetProcessorDrivers $drivers): void
    {
        $drivers->extend(
            'remote-images',
            fn (Container $container) => $container->make(RemoteImageDriver::class),
        );
    }
}
```

The registration handle identifies the driver implementation. After registering it, create an Asset Processor in the
Control Panel and select **Remote Images** as its driver. The configured processor handle is what volumes, transform
definitions, and `defaultAssetProcessor` reference.

Laravel’s driver manager resolves and caches a driver instance by registration handle. Keep request-specific state in
the `AssetTransformRequest`, not on the driver instance.

## Optional transform preloading

Implement `PreloadsAssetTransforms` when the backing service can prepare multiple requests more efficiently than
processing them individually:

```php
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;

class RemoteImageDriver implements AssetProcessorDriver, PreloadsAssetTransforms
{
    // definition() and transform() ...

    public function preloadAssetTransforms(array $requests): void
    {
        $this->client->preload($requests);
    }
}
```

Asset queries using `withTransforms()` group requests by driver and call this method once per driver. Requests can still
belong to different processor profiles, so use `AssetTransformRequest::$processor` for profile-specific settings.
Preloading is an optimization and does not guarantee that rendition files have materialized.

## Cache invalidation and processor changes

Drivers that maintain their own transform index or remote cache can listen for:

- `AssetTransformsInvalidating` when all renditions for an Asset should be invalidated;
- `AssetProcessorUpdating` when a processor’s driver or settings change; and
- `AssetProcessorDeleting` before a processor profile is removed.

The update event contains both the old and new processor profiles. Use their UUIDs as stable cache namespaces; processor
handles can be renamed.

## Failure behavior

Fail explicitly when a request cannot be fulfilled:

- throw `NotSupportedException` when the source Asset type is outside the driver’s capabilities;
- throw `AssetTransformFailedException` when a supported transform fails; and
- allow unexpected exceptions to remain observable rather than returning a plausible but invalid result.

Craft uses `NotSupportedException` to fall back to file-kind icons in Control Panel thumbnail and preview contexts. Other
transform failures are reported to the caller.
