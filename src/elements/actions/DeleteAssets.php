<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
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
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $userSession = Craft::$app->getUser();
        $elementsService = Craft::$app->getElements();

        try {
            foreach ($query->all() as $asset) {
                /** @var Asset $asset */
                $volume = $asset->getVolume();
                if ($userSession->checkPermission('deleteFilesAndFoldersInVolume:' . $volume->uid)) {
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
