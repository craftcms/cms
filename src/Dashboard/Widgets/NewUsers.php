<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\elements\User;
use craft\web\assets\newusers\NewUsersAsset;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Json;
use Override;

use function CraftCms\Cms\t;

final class NewUsers extends Widget
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('New {type}', [
            'type' => User::pluralDisplayName(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function isSelectable(): bool
    {
        // This widget is only available for Craft Pro
        return Edition::get()->value >= Edition::Pro->value;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
    #[Override]
    public function getTitle(): ?string
    {
        if (! $groupId = $this->userGroupId) {
            return parent::getTitle();
        }

        if ($userGroup = UserGroups::getGroupById($groupId)) {
            return sprintf(
                '%s – %s',
                parent::getTitle(),
                t($userGroup->name, category: 'site'),
            );
        }

        return parent::getTitle();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getBodyHtml(): ?string
    {
        if (Edition::get()->value < Edition::Pro->value) {
            return null;
        }

        $options = $this->getSettings();
        $options['orientation'] = I18N::getLocale()->getOrientation();

        $view = Craft::$app->getView();
        $view->registerAssetBundle(NewUsersAsset::class);
        $view->registerJs('new Craft.NewUsersWidget('.$this->id.', '.Json::encode($options).');');

        return '';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/NewUsers/settings.twig',
            [
                'widget' => $this,
            ]);
    }
}
