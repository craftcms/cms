<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\helpers\Json;

/**
 * EditImage represents an Edit Image action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EditImage extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string The trigger label
     */
    public $label;

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function isDestructive()
    {
        return true;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->label === null) {
            $this->label = Craft::t('app', 'Edit Image');
        }
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel()
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::className());

        $js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		type: {$type},
		batch: false,
		_imageEditor: null,
		validateSelection: function(\$selectedItems)
		{
			var \$element = \$selectedItems.find('.element');

			return Garnish.hasAttr(\$element, 'data-image');
		},
		activate: function(\$selectedItems)
		{
			var \$element = \$selectedItems.find('.element:first'),
			    element = Craft.getElementInfo(\$element);

            var settings = {
                onSave: function () {
                    alert('Ok, saving ' + element.id);
                }
            };
            
            new Craft.AssetImageEditor(element.id, settings);
		}
	});
})();
EOT;

        Craft::$app->getView()->registerJs($js);
    }
}
