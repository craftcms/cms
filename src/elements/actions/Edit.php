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
 * Edit represents an Edit element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Edit extends ElementAction
{
    /**
     * @var string|null The trigger label
     */
    public ?string $label = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!isset($this->label)) {
            $this->label = Craft::t('app', 'Edit');
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
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: \$selectedItems => Garnish.hasAttr(\$selectedItems.find('.element'), 'data-savable'),
        activate: \$selectedItems => {
            const \$element = \$selectedItems.find('.element:first');
            Craft.createElementEditor(\$element.data('type'), \$element);
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
