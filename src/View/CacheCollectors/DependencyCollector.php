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
     * @var array<int, array{tags: array<string, bool>, expiryTimestamp: int|null}>
     */
    private array $buffers = [];

    /**
     * @var array<string, bool>|null
     */
    private ?array $tags = null;

    private ?int $expiryTimestamp = null;

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
            ];
        }

        $this->tags = [];
        $this->expiryTimestamp = null;
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

    private function mergeExpiryTimestamp(int $expiryTimestamp): void
    {
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
            $this->collectTags($tags);

            if ($expiryTimestamp !== null) {
                $this->mergeExpiryTimestamp($expiryTimestamp);
            }
        } else {
            $this->tags = null;
            $this->expiryTimestamp = null;
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
