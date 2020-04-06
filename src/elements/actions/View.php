<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

/**
 * View represents a View element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class View extends ElementAction
{
    /**
     * @var string|null The trigger label
     */
    public $label;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->label === null) {
            $this->label = Craft::t('app', 'View');
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
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            var \$element = \$selectedItems.find('.element');

            return (
                \$element.data('url') &&
                (\$element.data('status') === 'enabled' || \$element.data('status') === 'live')
            );
        },
        activate: function(\$selectedItems)
        {
            window.open(\$selectedItems.find('.element').data('url'));
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }
}
