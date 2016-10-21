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
 * Edit represents an Edit element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Edit extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string The trigger label
     */
    public $label;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->label === null) {
            $this->label = Craft::t('app', 'Edit');
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
        $type = Json::encode(static::class);

        $js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		type: {$type},
		batch: false,
		validateSelection: function(\$selectedItems)
		{
			return Garnish.hasAttr(\$selectedItems.find('.element'), 'data-editable');
		},
		activate: function(\$selectedItems)
		{
			var \$element = \$selectedItems.find('.element:first');

			if (Craft.elementIndex.viewMode == 'table') {
				new Craft.ElementEditor(\$element, {
					params: {
						includeTableAttributesForSource: Craft.elementIndex.sourceKey
					},
					onSaveElement: $.proxy(function(response) {
						if (response.tableAttributes) {
							Craft.elementIndex.view._updateTableAttributes(\$element, response.tableAttributes);
						}
					}, this)
				});
			} else {
				new Craft.ElementEditor(\$element);
			}
		}
	});
})();
EOT;

        Craft::$app->getView()->registerJs($js);
    }
}
