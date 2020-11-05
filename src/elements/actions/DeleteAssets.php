<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use yii\base\Exception;

/**
 * DeleteAssets represents a Delete Assets element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DeleteAssets extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Delete');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return Craft::t('app', 'Are you sure you want to delete the selected assets?');
    }

    /**
     * @inheritdoc
     * @since 3.5.15
     */
    public function getTriggerHtml()
    {
        // Only enable for deletable elements, per getIsDeletable()
        $type = Json::encode(static::class);
        $js = <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: {$type},
        validateSelection: function(\$selectedItems)
        {
            for (let i = 0; i < \$selectedItems.length; i++) {
                if (!Garnish.hasAttr(\$selectedItems.eq(i).find('.element'), 'data-deletable')) {
                    return false;
                }
            }
            return true;
        },
    });
})();
JS;
        Craft::$app->getView()->registerJs($js);
        return null;
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        try {
            foreach ($query->all() as $asset) {
                if ($asset->getIsDeletable()) {
                    $elementsService->deleteElement($asset);
                }
            }
        } catch (Exception $exception) {
            $this->setMessage($exception->getMessage());
            return false;
        }

        $this->setMessage(Craft::t('app', 'Assets deleted.'));

        return true;
    }
}
