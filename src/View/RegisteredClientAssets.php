<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Container\Attributes\Scoped;

/**
 * Tracks which asset bundles and JS files the browser has already loaded, so
 * XHR-rendered HTML fragments don't re-ship (and re-execute) them.
 *
 * Mirrors Craft 5's `View::setRegisteredAssetBundles()` mechanism: on full CP
 * page renders, a body-end script records crc32 hashes of everything that was
 * registered onto `Craft.registeredAssetBundles`/`Craft.registeredJsFiles`;
 * the JS action clients echo those back as `X-Registered-Asset-Bundles` /
 * `X-Registered-Js-Files` request headers, and this service consumes them to
 * skip re-registration during the XHR render.
 */
#[Scoped]
class RegisteredClientAssets
{
    /** @var array<string, true> Bundle hashes the client reported as loaded. */
    private array $clientBundles;

    /** @var array<string, true> JS file hashes the client reported as loaded. */
    private array $clientJsFiles;

    /** @var array<string, true> Bundle hashes registered during this request. */
    private array $bundles = [];

    /** @var array<string, true> JS file hashes registered during this request. */
    private array $jsFiles = [];

    /** Hash count at the last sync-script emission. */
    private int $emittedCount = 0;

    public function __construct()
    {
        $request = request();

        $this->clientBundles = self::parseHeader((string) $request->header('X-Registered-Asset-Bundles', ''));
        $this->clientJsFiles = self::parseHeader((string) $request->header('X-Registered-Js-Files', ''));
    }

    /**
     * Whether the client already has the given asset bundle.
     */
    public function hasBundle(string $name): bool
    {
        return isset($this->clientBundles[self::hash($name)]);
    }

    /**
     * Records an asset bundle registered during this request.
     */
    public function trackBundle(string $name): void
    {
        $this->bundles[self::hash($name)] = true;
    }

    /**
     * Whether the client already has the given JS file.
     */
    public function hasJsFile(string $key): bool
    {
        return isset($this->clientJsFiles[self::hash($key)]);
    }

    /**
     * Records a JS file registered during this request.
     */
    public function trackJsFile(string $key): void
    {
        $this->jsFiles[self::hash($key)] = true;
    }

    /**
     * Registers the body-end script that records this request's asset hashes
     * on the `Craft` global, for the client to echo back on action requests.
     *
     * Only applies to full page renders — for XHR renders (which are the
     * requests that *send* the headers) the client already knows what it has.
     * Idempotent, so every page-assembly path can call it safely.
     */
    public function registerSyncJs(HtmlStack $htmlStack): void
    {
        if (request()->ajax()) {
            return;
        }

        // Pending legacy bundles are normally flushed (and thereby tracked)
        // when the first HtmlStack position is rendered — force it here so
        // call order relative to the drain methods doesn't matter.
        app(InternalAssetRegistry::class)->flush();

        // Only (re-)register when new hashes were tracked since the last
        // emission, so repeated drains don't re-emit an unchanged script.
        $count = count($this->bundles) + count($this->jsFiles);

        if ($count === $this->emittedCount) {
            return;
        }

        $js = $this->syncJs();

        if ($js === null) {
            return;
        }

        $this->emittedCount = $count;

        // A standalone tag (rather than the shared inline-JS blob) so an
        // unrelated script error on the page can't prevent the recording.
        $htmlStack->script($js, Position::BodyEnd, key: self::class);
    }

    /**
     * Returns the JS that records this request's asset hashes on the `Craft`
     * global, or `null` if nothing was registered.
     */
    public function syncJs(): ?string
    {
        if ($this->bundles === [] && $this->jsFiles === []) {
            return null;
        }

        $js = "window.Craft = window.Craft || {};\n";

        foreach (['registeredAssetBundles' => $this->bundles, 'registeredJsFiles' => $this->jsFiles] as $property => $hashes) {
            if ($hashes === []) {
                continue;
            }

            $encoded = implode(', ', array_map(
                fn (string $hash) => Json::encode($hash),
                array_keys($hashes),
            ));

            $js .= "(window.Craft.{$property} = window.Craft.{$property} || []).push({$encoded});\n";
        }

        return $js;
    }

    /**
     * @return array<string, true>
     */
    private static function parseHeader(string $header): array
    {
        $hashes = array_filter(array_map(trim(...), explode(',', $header)));

        return array_fill_keys($hashes, true);
    }

    /**
     * Hashes an asset identity for the client round-trip. Same format as the
     * legacy view's resource hashes, so both systems can share the client-side
     * arrays without colliding.
     */
    private static function hash(string $key): string
    {
        return sprintf('%x', crc32($key));
    }
}
