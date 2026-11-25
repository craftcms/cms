<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use yii\base\BaseObject;

/**
 * BaseResolvableDeletionBlocker defines a base implementation for resolvable deletion blockers.
 *
 * The control panel lists the blocker’s items, and offers one button that resolves it.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
abstract class BaseResolvableDeletionBlocker extends BaseObject implements ResolvableDeletionBlockerInterface
{
    /**
     * @var bool Whether the control panel should show the blocker’s details when it opens.
     */
    public bool $expandDetails = true;

    /**
     * @var array{label: string, count: int|null, description: string}[]
     */
    private array $items;

    /**
     * Returns the items that the deletion will affect.
     *
     * @return array{label: string, count: int|null, description: string}[]
     */
    abstract protected function defineItems(): array;

    /**
     * Returns the message shown once the blocker is resolved in the control panel.
     */
    abstract protected function getResolvedMessage(): string;

    public function getItems(): array
    {
        return $this->items ??= $this->defineItems();
    }

    public function isActive(): bool
    {
        return !empty($this->getItems());
    }

    public function getDetails(): ?string
    {
        return Html::ul(array_map(
            fn(array $item) => Html::tag('strong', Html::encode($item['label'])) . ' – ' . Html::encode($item['description']),
            $this->getItems(),
        ), ['encode' => false]);
    }

    public function getActions(): array
    {
        return [
            [
                'icon' => $this->isDestructive() ? 'trash' : 'xmark',
                'label' => $this->getResolutionLabel(),
                'destructive' => $this->isDestructive(),
                'callback' => Html::jsWithVars(fn($message) => "resolve($message);", [
                    $this->getResolvedMessage(),
                ]),
            ],
        ];
    }

    /**
     * Returns whether resolving the blocker agrees to something destructive, such as trashing elements.
     */
    protected function isDestructive(): bool
    {
        return false;
    }

    /**
     * Returns a sentence that says how many elements have a value, such as “42 entries have a value.”
     *
     * @param class-string<ElementInterface>|null $elementType
     */
    protected static function valueCountDescription(?string $elementType, ?int $count): string
    {
        if ($count === null) {
            return Craft::t('app', 'Values couldn’t be counted.');
        }

        return Craft::t('app', '{count, number} {count, plural, =1{{type} has} other{{pluralType} have}} a value.', [
            'count' => $count,
            'type' => $elementType ? $elementType::lowerDisplayName() : Craft::t('app', 'element'),
            'pluralType' => $elementType ? $elementType::pluralLowerDisplayName() : Craft::t('app', 'elements'),
        ]);
    }
}
