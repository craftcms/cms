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
use craft\helpers\Json;
use yii\base\Exception;

/**
 * CopyReferenceTag represents a Copy Reference Tag element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CopyReferenceTag extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The element type associated with this action
     */
    public $elementType;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Copy reference tag');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);
        $prompt = Json::encode(Craft::t('app', '{ctrl}C to copy.'));
        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;

        if (($refHandle = $elementType::refHandle()) === null) {
            throw new Exception("Element type \"{$elementType}\" doesn't have a reference handle.");
        }

        $refHandleJs = Json::encode($refHandle);

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        activate: function(\$selectedItems)
        {
            var message = Craft.t('app', {$prompt}, {
                ctrl: (navigator.appVersion.indexOf('Mac') !== -1 ? '⌘' : 'Ctrl-')
            });

            prompt(message, '{'+{$refHandleJs}+':'+\$selectedItems.find('.element').data('id')+'}');
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }
}
