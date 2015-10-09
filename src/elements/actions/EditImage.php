<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
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

			return (\$element.hasClass('hasthumb'));
		},
		activate: function(\$selectedItems)
		{
			this._imageEditor = new Craft.AssetImageEditor(\$selectedItems.find('.element').data('id'));
		}
	});
})();
EOT;

        Craft::$app->getView()->registerJs($js);
    }
}
