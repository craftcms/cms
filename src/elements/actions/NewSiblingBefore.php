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
 * NewSibling represents a “Create a new X before” element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.0
 */
class NewSiblingBefore extends ElementAction
{
    /**
     * @var string|null The trigger label
     */
    public ?string $label = null;

    /**
     * @var string|null The URL that the user should be taken to after clicking on this element action
     */
    public ?string $newSiblingUrl = null;

    /**
     * @inheritdoc
     */
    public function setElementType(string $elementType): void
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface> $elementType */
        parent::setElementType($elementType);

        if (!isset($this->label)) {
            $this->label = Craft::t('app', 'Create a new {type} before', [
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
        Craft::$app->getView()->registerJsWithVars(fn($type, $newSiblingUrl) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        activate: \$selectedItems => {
            Craft.redirectTo(Craft.getUrl($newSiblingUrl, 'before=' + \$selectedItems.find('.element').data('id')));
        },
    });
})();
JS, [static::class, $this->newSiblingUrl]);

        return null;
    }
}
