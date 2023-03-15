<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementInterface;

/**
 * NewChild represents a New Child element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class NewChild extends ElementAction
{
    /**
     * @var string|null The trigger label
     */
    public ?string $label = null;

    /**
     * @var int|null The maximum number of levels that the structure is allowed to have
     */
    public ?int $maxLevels = null;

    /**
     * @var string|null The URL that the user should be taken to after clicking on this element action
     */
    public ?string $newChildUrl = null;

    /**
     * @inheritdoc
     */
    public function setElementType(string $elementType): void
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface> $elementType */
        parent::setElementType($elementType);

        if (!isset($this->label)) {
            $this->label = Craft::t('app', 'Create a new child {type}', [
                'type' => $elementType::lowerDisplayName(),
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type, $maxLevels, $newChildUrl) => <<<JS
(() => {
    let trigger = new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: \$selectedItems => !$maxLevels || $maxLevels > \$selectedItems.find('.element').data('level'),
        activate: \$selectedItems => {
            const url = Craft.getUrl($newChildUrl, 'parentId=' + \$selectedItems.find('.element').data('id'));
            Craft.redirectTo(url);
        },
    });

    if (Craft.elementIndex.view.structureTableSort) {
        Craft.elementIndex.view.structureTableSort.on('positionChange', $.proxy(trigger, 'updateTrigger'));
    }
})();
JS, [static::class, $this->maxLevels, $this->newChildUrl]);

        return null;
    }
}
