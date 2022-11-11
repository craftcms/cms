<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\Json;
use craft\web\assets\newusers\NewUsersAsset;

/**
 * NewUsers represents a New Users dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class NewUsers extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'New Users');
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        // This widget is only available for Craft Pro
        return (Craft::$app->getEdition() === Craft::Pro);
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@appicons/users.svg');
    }

    /**
     * @var int|null The ID of the user group
     */
    public ?int $userGroupId = null;

    /**
     * @var string|null The date range
     */
    public ?string $dateRange = null;

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        if ($groupId = $this->userGroupId) {
            $userGroup = Craft::$app->getUserGroups()->getGroupById($groupId);

            if ($userGroup) {
                return Craft::t('app', 'New Users') . ' – ' . Craft::t('app', $userGroup->name);
            }
        }

        return parent::getTitle();
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return null;
        }

        $options = $this->getSettings();
        $options['orientation'] = Craft::$app->getLocale()->getOrientation();

        $view = Craft::$app->getView();
        $view->registerAssetBundle(NewUsersAsset::class);
        $view->registerJs('new Craft.NewUsersWidget(' . $this->id . ', ' . Json::encode($options) . ');');

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/NewUsers/settings.twig',
            [
                'widget' => $this,
            ]);
    }
}
