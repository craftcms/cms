<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\CacheCollectors;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\ExpirableElementInterface;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTimeInterface;
use Illuminate\Container\Attributes\Scoped;
use RuntimeException;

#[Scoped]
class DependencyCollector implements CacheCollectorInterface
{
    /**
     * @var array<int, array{tags: array<string, bool>, duration: int|null}>
     */
    private array $buffers = [];

    /**
     * @var array<string, bool>|null
     */
    private ?array $tags = null;

    private ?int $duration = null;

    public static function key(): string
    {
        return 'dependencies';
    }

    public function begin(TemplateCacheContext $context): void
    {
        if ($this->isCollecting()) {
            $this->buffers[] = [
                'tags' => $this->tags,
                'duration' => $this->duration,
            ];
        }

        $this->tags = [];
        $this->duration = null;
    }

    /**
     * @return array{tags: list<string>, expiryDate: string|null}
     */
    public function end(TemplateCacheContext $context): array
    {
        [$dependency, $duration] = $this->stop();

        return [
            'tags' => $dependency->tags ?? [],
            'expiryDate' => $duration ? DateTimeHelper::toIso8601(now()->add($duration, 'seconds')) : null,
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
            $this->setExpiryDate(DateTimeHelper::toDateTime($cacheInfo['expiryDate']));
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
    }

    public function setExpiryDate(DateTimeInterface $expiryDate): void
    {
        if (! $this->isCollecting()) {
            return;
        }

        $duration = $expiryDate->getTimestamp() - now()->getTimestamp();

        if ($duration > 0 && ($this->duration === null || $duration < $this->duration)) {
            $this->duration = $duration;
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

        if (
            $element instanceof ExpirableElementInterface &&
            ($expiryDate = $element->getExpiryDate()) !== null
        ) {
            $this->setExpiryDate($expiryDate);
        }
    }

    /**
     * @return array{TagDependency|null, int|null}
     */
    public function stop(): array
    {
        if (! $this->isCollecting()) {
            throw new RuntimeException('Element cache invalidation tags are not currently being collected.');
        }

        $tags = array_keys($this->tags);
        $duration = $this->duration;

        if (! empty($this->buffers)) {
            $parent = array_pop($this->buffers);
            $this->tags = $parent['tags'];
            $this->duration = $parent['duration'];
            $this->collectTags($tags);

            if ($duration) {
                $this->setExpiryDate(now()->add($duration, 'seconds'));
            }
        } else {
            $this->tags = null;
            $this->duration = null;
        }

        if (empty($tags)) {
            return [null, $duration];
        }

        return [new TagDependency($tags), $duration];
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
