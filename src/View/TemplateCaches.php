<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\CacheCollectors\ResourceCollector;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\Cms\View\Events\RegisterTemplateCacheCollectors;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use DateTime;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function request;

#[Scoped]
class TemplateCaches
{
    private ?bool $enabled = null;

    private ?bool $enabledGlobally = null;

    private ?string $path = null;

    /**
     * @var list<CacheCollectorInterface>|null
     */
    private ?array $collectors = null;

    public function __construct(
        private readonly DependencyCollector $dependencyCollector,
    ) {}

    public function getTemplateCache(string $key, bool $global, bool $registerResources = false): ?string
    {
        if (! $this->isTemplateCachingEnabled($global)) {
            return null;
        }

        $context = $this->context($key, $global, $registerResources);
        $data = $this->normalizeCachedValue(DependencyCache::get($context->cacheKey));

        if ($data === null) {
            return null;
        }

        $this->applyCachedPayload($data, $context);

        return $data['body'];
    }

    public function startTemplateCache(bool $withResources = false, bool $global = false): void
    {
        if (! $this->isTemplateCachingEnabled($global)) {
            return;
        }

        $context = new TemplateCacheContext(
            cacheKey: '',
            global: $global,
            resources: $withResources,
        );

        foreach ($this->collectors() as $collector) {
            $collector->begin($context);
        }
    }

    public function endTemplateCache(string $key, bool $global, ?string $duration, mixed $expiration, string $body, bool $withResources = false): void
    {
        if (! $this->isTemplateCachingEnabled($global)) {
            return;
        }

        $context = $this->context($key, $global, $withResources);
        $cacheInfo = [
            'tags' => [],
            'expiryDate' => null,
        ];
        $collectorPayloads = [];

        foreach ($this->collectors() as $collector) {
            $payload = $collector->end($context);

            if ($collector instanceof DependencyCollector) {
                $cacheInfo = DependencyCollector::normalizeCacheInfo($payload);

                continue;
            }
            if ($payload === null) {
                continue;
            }
            if ($payload === []) {
                continue;
            }
            if ($payload === false) {
                continue;
            }

            $collectorPayloads[$collector::key()] = $payload;
        }

        $this->applyNonDependencyCollectorPayloads($collectorPayloads, $context);

        if (str_contains(stripslashes($body), 'assets/generate-transform')) {
            return;
        }

        $cacheInfo = $this->withTemplateTag($cacheInfo);
        $duration = $this->normalizeDuration($duration, $expiration);
        $maxDuration = $this->durationFromCacheInfo($cacheInfo);

        if ($maxDuration) {
            $duration = $duration ? min($duration, $maxDuration) : $maxDuration;
        }

        DependencyCache::put(
            $context->cacheKey,
            [
                'body' => $body,
                'cacheInfo' => $cacheInfo,
                'collectors' => $collectorPayloads,
            ],
            $duration,
            new TagDependency($cacheInfo['tags']),
        );
    }

    /**
     * @return list<CacheCollectorInterface>
     */
    private function collectors(): array
    {
        if (isset($this->collectors)) {
            return $this->collectors;
        }

        event($event = new RegisterTemplateCacheCollectors(Collection::make()));

        $this->collectors = collect([
            DependencyCollector::class,
            ResourceCollector::class,
        ])
            ->concat($event->types)
            ->map(function (string $type): CacheCollectorInterface {
                $collector = app($type);

                if (! $collector instanceof CacheCollectorInterface) {
                    throw new InvalidArgumentException(sprintf('Template cache collector [%s] must implement [%s].', $type, CacheCollectorInterface::class));
                }

                return $collector;
            })
            ->all();

        return $this->collectors;
    }

    /**
     * Applies everything that was stored with a cached fragment.
     *
     * `cacheInfo` is stored separately from collector payloads because it is also
     * used to build the fragment's tag dependency at write time.
     *
     * @param  array{body: string, cacheInfo: array{tags: list<string>, expiryDate: string|null}, collectors: array<string, mixed>}  $data
     */
    private function applyCachedPayload(array $data, TemplateCacheContext $context): void
    {
        $this->dependencyCollector->apply($data['cacheInfo'], $context);
        $this->applyNonDependencyCollectorPayloads($data['collectors'], $context);
    }

    private function applyNonDependencyCollectorPayloads(array $payloads, TemplateCacheContext $context): void
    {
        foreach ($this->collectors() as $collector) {
            if ($collector instanceof DependencyCollector) {
                continue;
            }

            if (! array_key_exists($collector::key(), $payloads)) {
                continue;
            }

            $collector->apply($payloads[$collector::key()], $context);
        }
    }

    private function isTemplateCachingEnabled(bool $global): bool
    {
        if ($this->enabled === null || $this->enabledGlobally === null) {
            if (! Cms::config()->enableTemplateCaching) {
                $this->enabled = false;
                $this->enabledGlobally = false;
            } elseif (request()->isPreview() || request()->getToken()) {
                $this->enabled = false;
                $this->enabledGlobally = false;
            } else {
                $this->enabled = ! app()->runningInConsole();
                $this->enabledGlobally = true;
            }
        }

        return $global ? $this->enabledGlobally : $this->enabled;
    }

    private function context(string $key, bool $global, bool $resources): TemplateCacheContext
    {
        return new TemplateCacheContext(
            cacheKey: $this->cacheKey($key, $global),
            global: $global,
            resources: $resources,
        );
    }

    private function cacheKey(string $key, bool $global): string
    {
        $cacheKey = sprintf('template::%s::%s', $key, Sites::getCurrentSite()->id);

        if (! $global) {
            $cacheKey .= '::'.$this->path();
        }

        return $cacheKey;
    }

    private function path(): string
    {
        if (isset($this->path)) {
            return $this->path;
        }

        if (app()->runningInConsole()) {
            throw new InvalidArgumentException('Not possible to determine the request path for console commands.');
        }

        $isCpRequest = request()->isCpRequest();
        $path = trim(request()->decodedPath(), '/');
        $path = $path === '/' ? '' : $path;

        if ($isCpRequest) {
            $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');

            if ($cpTrigger !== '' && $path === $cpTrigger) {
                $path = '';
            } elseif ($cpTrigger !== '' && str_starts_with($path.'/', $cpTrigger.'/')) {
                $path = ltrim(substr($path, strlen($cpTrigger)), '/');
            }
        }

        $pageNum = request()->pageNumber();
        $path = $this->stripPageNumberFromPath($path, $isCpRequest, $pageNum);

        $this->path = ($isCpRequest ? 'cp:' : 'site:').$path;

        if ($pageNum !== 1) {
            $pageTrigger = $isCpRequest ? 'p' : Cms::config()->getPageTrigger();
            $this->path .= sprintf('/%s%s', $pageTrigger, $pageNum);
        }

        return $this->path;
    }

    private function stripPageNumberFromPath(string $path, bool $isCpRequest, int $pageNum): string
    {
        if ($pageNum === 1) {
            return $path;
        }

        $pageTrigger = $isCpRequest ? 'p' : Cms::config()->getPageTrigger();

        if (str_starts_with($pageTrigger, '?') || $path === '') {
            return $path;
        }

        $pageTriggerPattern = preg_quote($pageTrigger, '/');

        if (preg_match("/^(?:(.*)\\/)?{$pageTriggerPattern}{$pageNum}$/", $path, $matches)) {
            return $matches[1] ?? '';
        }

        return $path;
    }

    /**
     * @return array{body: string, cacheInfo: array{tags: list<string>, expiryDate: string|null}, collectors: array<string, mixed>}|null
     */
    private function normalizeCachedValue(mixed $data): ?array
    {
        if (! is_array($data) || $data === []) {
            return null;
        }

        if (! array_key_exists('body', $data) && ! array_key_exists('cacheInfo', $data) && ! array_key_exists('collectors', $data)) {
            return null;
        }

        return [
            'body' => (string) ($data['body'] ?? ''),
            'cacheInfo' => DependencyCollector::normalizeCacheInfo($data['cacheInfo'] ?? []),
            'collectors' => is_array($data['collectors'] ?? null) ? $data['collectors'] : [],
        ];
    }

    /**
     * @param  array{tags: list<string>, expiryDate: string|null}  $cacheInfo
     * @return array{tags: list<string>, expiryDate: string|null}
     */
    private function withTemplateTag(array $cacheInfo): array
    {
        $cacheInfo['tags'] = array_values(array_unique([
            ...$cacheInfo['tags'],
            'template',
        ]));

        return $cacheInfo;
    }

    private function normalizeDuration(?string $duration, mixed $expiration): ?int
    {
        if ($duration !== null) {
            $expiration = new DateTime($duration);
        }

        if ($expiration !== null) {
            $duration = DateTimeHelper::toDateTime($expiration)->getTimestamp() - DateTimeHelper::currentTimeStamp();
        } else {
            $duration = null;
        }

        if ($duration !== null && $duration <= 0) {
            return null;
        }

        return $duration;
    }

    /**
     * @param  array{tags: list<string>, expiryDate: string|null}  $cacheInfo
     */
    private function durationFromCacheInfo(array $cacheInfo): ?int
    {
        if (! $cacheInfo['expiryDate']) {
            return null;
        }

        $duration = DateTimeHelper::toDateTime($cacheInfo['expiryDate'])->getTimestamp() - DateTimeHelper::currentTimeStamp();

        return $duration > 0 ? $duration : null;
    }
}
