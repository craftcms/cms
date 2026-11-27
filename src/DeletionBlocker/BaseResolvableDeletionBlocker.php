<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\DeletionBlocker\Contracts\ResolvableDeletionBlockerInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\DeletionBlockers\Contracts\DeletionBlockerInterface;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

/**
 * A base for resolvable deletion blockers.
 *
 * The control panel lists the blocker’s items, and offers one button that resolves it.
 *
 * @since 6.0.0
 */
abstract class BaseResolvableDeletionBlocker extends Component implements ResolvableDeletionBlockerInterface
{
    /**
     * Whether the modal opens with the blocker’s details expanded.
     */
    public bool $expandDetails = true;

    /**
     * Action buttons to show before the one that resolves the blocker, such as one that moves the elements
     * somewhere else. See {@see DeletionBlockerInterface::getActions()} for the keys each one can have.
     *
     * @var list<array<string, mixed>>
     */
    public array $additionalActions = [];

    /**
     * @var list<array{label: string, count: int|null, description: string}>
     */
    private array $items;

    /**
     * Returns the items that the deletion will affect.
     *
     * @return list<array{label: string, count: int|null, description: string}>
     */
    abstract protected function defineItems(): array;

    /**
     * Returns the message the control panel shows once the user resolves the blocker.
     */
    abstract protected function getResolvedMessage(): string;

    public function getItems(): array
    {
        return $this->items ??= $this->defineItems();
    }

    public function isActive(): bool
    {
        return $this->getItems() !== [];
    }

    public function getDetails(): ?string
    {
        return (string) Html::ul(array_map(
            fn (array $item) => Html::tag('strong', Html::encode($item['label'])).' – '.Html::encode($item['description']),
            $this->getItems(),
        ), encode: false);
    }

    /** @return list<array<string, mixed>> */
    public function getActions(): array
    {
        return [
            ...$this->additionalActions,
            [
                'icon' => $this->isDestructive() ? 'trash' : 'xmark',
                'label' => $this->getResolutionLabel(),
                'destructive' => $this->isDestructive(),
                'callback' => Html::jsWithVars(fn ($message) => "resolve($message);", [
                    $this->getResolvedMessage(),
                ]),
            ],
        ];
    }

    /**
     * Returns whether the blocker’s action is destructive, such as moving elements to the trash.
     */
    protected function isDestructive(): bool
    {
        return false;
    }

    /**
     * Returns a sentence that says how many elements have a value, such as “42 entries have a value.”
     *
     * @param  class-string<ElementInterface>|null  $elementType
     */
    protected static function valueCountDescription(?string $elementType, ?int $count): string
    {
        if ($count === null) {
            return t('Values couldn’t be counted.');
        }

        return t('{count, number} {count, plural, =1{{type} has} other{{pluralType} have}} a value.', [
            'count' => $count,
            'type' => $elementType ? $elementType::lowerDisplayName() : t('element'),
            'pluralType' => $elementType ? $elementType::pluralLowerDisplayName() : t('elements'),
        ]);
    }
}
