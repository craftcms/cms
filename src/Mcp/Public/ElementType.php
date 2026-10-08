<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Public;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;

/**
 * @since 6.0.0
 */
readonly class ElementType
{
    /** @param class-string<ElementInterface> $class */
    public function __construct(
        public string $name,
        public string $class,
    ) {}

    public function query(): ElementQueryInterface
    {
        return $this->class::find();
    }

    public function publicStatus(): string
    {
        return match (true) {
            $this->is(Entry::class) => Entry::STATUS_LIVE,
            $this->is(User::class) => User::STATUS_ACTIVE,
            default => Element::STATUS_ENABLED,
        };
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [
            'type' => $this->name,
            'class' => $this->class,
            'refHandle' => $this->class::refHandle(),
            'name' => $this->class::displayName(),
            'pluralName' => $this->class::pluralDisplayName(),
            'pluralLowerName' => $this->class::pluralLowerDisplayName(),
        ];
    }

    /** @param class-string<ElementInterface> $class */
    public function is(string $class): bool
    {
        return is_a($this->class, $class, true);
    }

    public function isScopeControlled(): bool
    {
        return $this->is(Entry::class) || $this->is(Asset::class);
    }
}
