<?php

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\elements\User;
use craft\helpers\Json;
use craft\web\assets\newusers\NewUsersAsset;
use CraftCms\Cms\Edition;

/** @since 6.0.0 */
final class NewUsers extends Widget
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'New {type}', [
            'type' => User::pluralDisplayName(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function isSelectable(): bool
    {
        // This widget is only available for Craft Pro
        return Craft::$app->edition->value >= Edition::Pro->value;
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'user-group';
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
     * {@inheritdoc}
     */
    public function getTitle(): ?string
    {
        if ($groupId = $this->userGroupId) {
            $userGroup = Craft::$app->getUserGroups()->getGroupById($groupId);

            if ($userGroup) {
                return sprintf(
                    '%s – %s',
                    parent::getTitle(),
                    Craft::t('site', $userGroup->name)
                );
            }
        }

        return parent::getTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyHtml(): ?string
    {
        if (Craft::$app->edition->value < Edition::Pro->value) {
            return null;
        }

        $options = $this->getSettings();
        $options['orientation'] = Craft::$app->getLocale()->getOrientation();

        $view = Craft::$app->getView();
        $view->registerAssetBundle(NewUsersAsset::class);
        $view->registerJs('new Craft.NewUsersWidget('.$this->id.', '.Json::encode($options).');');

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/NewUsers/settings.twig',
            [
                'widget' => $this,
            ]);
    }
}
