<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User;
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

    public function component(): ?string
    {
        return 'craft:widget-new-users';
    }

    /** @return array{userGroupId: ?int, dateRange: string}|null */
    public function props(): ?array
    {
        if (Edition::get()->value < Edition::Pro->value) {
            return null;
        }

        return ['userGroupId' => $this->userGroupId, 'dateRange' => $this->dateRange ?? 'd7'];
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $form = Form::make([
            Field::make(t('Date Range'))
                ->control(Choice::make('dateRange')->value($this->dateRange)->options([
                    ['label' => t('Last {num, number} {num, plural, =1{day} other{days}}', ['num' => 7]), 'value' => 'd7'],
                    ['label' => t('Last {num, number} {num, plural, =1{day} other{days}}', ['num' => 30]), 'value' => 'd30'],
                    ['label' => t('Last Week'), 'value' => 'lastweek'],
                    ['label' => t('Last Month'), 'value' => 'lastmonth'],
                ])),
        ]);

        $userGroups = UserGroups::getAllGroups();

        return $form->when($userGroups->isNotEmpty(), fn (Form $form) => $form->add(
            Field::make(t('User Group'))
                ->control(Choice::make('userGroupId')->value($this->userGroupId)->options([
                    ['label' => t('All'), 'value' => ''],
                    ...$userGroups->map(fn ($userGroup): array => [
                        'label' => t($userGroup->name, category: 'site'),
                        'value' => $userGroup->id,
                    ])->all(),
                ])),
        ));
    }
}
