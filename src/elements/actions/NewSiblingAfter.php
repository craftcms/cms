<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;

/**
 * NewSibling represents a “Create a new X after” element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.0
 */
class NewSiblingAfter extends ElementAction
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
        batch: false,
        activate: \$selectedItems => {
            Craft.redirectTo(Craft.getUrl($newSiblingUrl, 'after=' + \$selectedItems.find('.element').data('id')));
        },
    });
})();
JS, [static::class, $this->newSiblingUrl]);

        return null;
    }
}
