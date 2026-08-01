<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Cp\FormDefinitions\Elements\SelectInput;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\NewUsersAsset;
use Override;

use function CraftCms\Cms\t;

class NewUsers extends Widget
{
    #[Override]
    public static function displayName(): string
    {
        return t('New {type}', [
            'type' => User::pluralDisplayName(),
        ]);
    }

    #[Override]
    public static function isSelectable(): bool
    {
        // This widget is only available for Craft Pro
        return Edition::get()->value >= Edition::Pro->value;
    }

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

    #[Override]
    public function getBodyHtml(): ?string
    {
        if (Edition::get()->value < Edition::Pro->value) {
            return null;
        }

        $options = $this->getSettings();
        $options['orientation'] = I18N::getLocale()->getOrientation();

        app(InternalAssetRegistry::class)->register(NewUsersAsset::class);
        HtmlStack::js('new Craft.NewUsersWidget('.$this->id.', '.Json::encode($options).');');

        return '';
    }

    #[Override]
    public function getSettingsFormDefinition(bool $readOnly): FormDefinition
    {
        $elements = [
            SelectInput::make('dateRange')
                ->label(t('Date Range'))
                ->options([
                    ['label' => t('Last {num, number} {num, plural, =1{day} other{days}}', ['num' => 7]), 'value' => 'd7'],
                    ['label' => t('Last {num, number} {num, plural, =1{day} other{days}}', ['num' => 30]), 'value' => 'd30'],
                    ['label' => t('Last Week'), 'value' => 'lastweek'],
                    ['label' => t('Last Month'), 'value' => 'lastmonth'],
                ])
                ->readOnly($readOnly),
        ];

        $userGroups = UserGroups::getAllGroups();

        if ($userGroups->isNotEmpty()) {
            $elements[] = SelectInput::make('userGroupId')
                ->label(t('User Group'))
                ->options([
                    ['label' => t('All'), 'value' => null],
                    ...$userGroups->map(fn ($userGroup): array => [
                        'label' => t($userGroup->name, category: 'site'),
                        'value' => $userGroup->id,
                    ])->all(),
                ])
                ->readOnly($readOnly);
        }

        return FormDefinition::make($elements);
    }
}
