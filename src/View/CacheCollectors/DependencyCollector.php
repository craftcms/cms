<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\CacheCollectors;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\ExpirableElementInterface;
use CraftCms\Cms\HttpCache\ResponseTagger;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTimeInterface;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Date;
use RuntimeException;

/**
 * @since 6.0.0
 */
#[Scoped]
class DependencyCollector implements CacheCollectorInterface
{
    /**
     * @var array<int, array{tags: array<string, bool>, expiryTimestamp: int|null, collectsStatusChanges: bool}>
     */
    private array $buffers = [];

    /**
     * @var array<string, bool>|null
     */
    private ?array $tags = null;

    private ?int $expiryTimestamp = null;

    private bool $collectsStatusChanges = true;

    public static function key(): string
    {
        return 'dependencies';
    }

    public function begin(TemplateCacheContext $context): void
    {
        if ($this->isCollecting()) {
            $this->buffers[] = [
                'tags' => $this->tags,
                'expiryTimestamp' => $this->expiryTimestamp,
                'collectsStatusChanges' => $this->collectsStatusChanges,
            ];
        }

        $this->tags = [];
        $this->expiryTimestamp = null;
        $this->collectsStatusChanges = true;
    }

    /**
     * @return array{tags: list<string>, expiryDate: string|null}
     */
    public function end(TemplateCacheContext $context): array
    {
        [$dependency, $expiryTimestamp] = $this->finish();

        return [
            'tags' => $dependency->tags ?? [],
            'expiryDate' => $expiryTimestamp !== null ? DateTimeHelper::toIso8601(now()->setTimestamp($expiryTimestamp)) : null,
        ];
    }

    public function apply(mixed $payload, TemplateCacheContext $context): void
    {
        if (! $this->isCollecting()) {
            return;
        }

        $cacheInfo = self::normalizeCacheInfo($payload);

        if (! empty($cacheInfo['tags'])) {
            $this->collectTags($cacheInfo['tags']);
        }

        if ($cacheInfo['expiryDate']) {
            $this->mergeExpiryTimestamp(DateTimeHelper::toDateTime($cacheInfo['expiryDate'])->getTimestamp());
        }
    }

    public function isCollecting(): bool
    {
        return isset($this->tags);
    }

    /** @param list<string> $tags */
    public function collectTags(array $tags): void
    {
        if (! $this->isCollecting()) {
            return;
        }

        foreach ($tags as $tag) {
            $this->tags[$tag] = true;
        }

        // So cache drivers anywhere in the request can read the response's element tags as they're collected.
        $this->responseTagger->addTags($tags);
    }

    public function setExpiryDate(DateTimeInterface $expiryDate): void
    {
        if (! $this->isCollecting()) {
            return;
        }

        $expiryTimestamp = $expiryDate->getTimestamp();

        if ($expiryTimestamp > now()->getTimestamp()) {
            $this->mergeExpiryTimestamp($expiryTimestamp);
        }
    }

    /**
     * Registers the time an element’s status changes in a way that changes the result being cached, such as when an
     * entry goes live.
     *
     * Entry queries treat “now” as the end of the current minute, so their statuses change at the start of a date’s
     * minute. A change that’s already happened means the result is stale, so it isn’t cached at all.
     *
     * @internal
     */
    public function setStatusChangeDate(DateTimeInterface $date): void
    {
        if ($this->isCollecting()) {
            $this->mergeExpiryTimestamp(max(now()->getTimestamp(), Date::instance($date)->startOfMinute()->getTimestamp()));
        }
    }

    /**
     * Registers an element’s expiry date as a status change, if it’s expirable and hasn’t expired yet.
     *
     * For element types that compare to the exact time rather than the minute, the cache expires up to 59 seconds
     * early.
     *
     * @internal
     */
    public function collectElementExpiry(ElementInterface $element): void
    {
        if (
            ! $this->isCollecting() ||
            ! $element instanceof ExpirableElementInterface ||
            ($expiryDate = $element->getExpiryDate()) === null
        ) {
            return;
        }

        // An element that has already expired was loaded on purpose, such as by a query for expired entries
        if ($expiryDate->getTimestamp() > now()->getTimestamp()) {
            $this->setStatusChangeDate($expiryDate);
        }
    }

    /**
     * Stops element queries from looking up when their results will change without a save, such as when a pending
     * entry goes live, for the cache being collected. Their tags are still collected.
     *
     * The lookups still run when an enclosing cache collects them, since it caches what this one collects too.
     *
     * @internal
     */
    public function skipStatusChanges(): void
    {
        $parent = end($this->buffers);
        $this->collectsStatusChanges = $parent !== false && $parent['collectsStatusChanges'];
    }

    /**
     * Returns whether element queries should look up when their results will change without a save.
     *
     * @internal
     */
    public function collectsStatusChanges(): bool
    {
        return $this->isCollecting() && $this->collectsStatusChanges;
    }

    /**
     * Runs a callback without collecting anything it registers, such as a query run only to work out cache info.
     *
     * @internal
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function withoutCollecting(Closure $callback): mixed
    {
        // Clear the buffers too, so caches the callback collects itself don’t merge into the ones being collected
        $state = [$this->tags, $this->expiryTimestamp, $this->collectsStatusChanges, $this->buffers];
        [$this->tags, $this->buffers] = [null, []];

        try {
            return $callback();
        } finally {
            [$this->tags, $this->expiryTimestamp, $this->collectsStatusChanges, $this->buffers] = $state;
        }
    }

    private function mergeExpiryTimestamp(int $expiryTimestamp): void
    {
        $this->responseTagger->setExpiryTimestamp($expiryTimestamp);

        if ($this->expiryTimestamp === null || $expiryTimestamp < $this->expiryTimestamp) {
            $this->expiryTimestamp = $expiryTimestamp;
        }
    }

    public function collectElement(ElementInterface $element): void
    {
        if (! $this->isCollecting()) {
            return;
        }

        $class = $element::class;

        $this->collectTags([
            'element',
            "element::{$class}",
            "element::{$element->id}",
        ]);

        $this->collectElementExpiry($element);
    }

    /**
     * @return array{TagDependency|null, int|null}
     */
    public function stop(): array
    {
        [$dependency, $expiryTimestamp] = $this->finish();

        return [$dependency, $expiryTimestamp !== null ? max(0, $expiryTimestamp - now()->getTimestamp()) : null];
    }

    /**
     * @return array{TagDependency|null, int|null}
     */
    private function finish(): array
    {
        if (! $this->isCollecting()) {
            throw new RuntimeException('Element cache invalidation tags are not currently being collected.');
        }

        $tags = array_keys($this->tags);
        $expiryTimestamp = $this->expiryTimestamp;

        if (! empty($this->buffers)) {
            $parent = array_pop($this->buffers);
            $this->tags = $parent['tags'];
            $this->expiryTimestamp = $parent['expiryTimestamp'];
            $this->collectsStatusChanges = $parent['collectsStatusChanges'];
            $this->collectTags($tags);

            if ($expiryTimestamp !== null) {
                $this->mergeExpiryTimestamp($expiryTimestamp);
            }
        } else {
            $this->tags = null;
            $this->expiryTimestamp = null;
            $this->collectsStatusChanges = true;
        }

        if (empty($tags)) {
            return [null, $expiryTimestamp];
        }

        return [new TagDependency($tags), $expiryTimestamp];
    }

    /**
     * @return array{tags: list<string>, expiryDate: string|null}
     */
    public static function normalizeCacheInfo(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [
                'tags' => [],
                'expiryDate' => null,
            ];
        }

        if (array_is_list($payload)) {
            return [
                'tags' => array_values(array_filter($payload, is_string(...))),
                'expiryDate' => null,
            ];
        }

        return [
            'tags' => array_values(array_filter($payload['tags'] ?? [], is_string(...))),
            'expiryDate' => is_string($payload['expiryDate'] ?? null) ? $payload['expiryDate'] : null,
        ];
    }
}
