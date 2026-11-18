<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Elements\User;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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
        return self::isAvailable();
    }

    /**
     * The widget is only available for Craft Pro, and requires the `viewUsers` permission.
     */
    private static function isAvailable(): bool
    {
        return Edition::get()->value >= Edition::Pro->value
            && (currentUser()?->can('viewUsers') ?? false);
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
        return self::isAvailable() ? 'craft:widget-new-users' : null;
    }

    /** @return array{userGroupId: ?int, userGroupName: ?string, userGroupNameSingular: ?string, dateRange: string, dateRangeLabel: ?string} */
    public function props(): array
    {
        $dateRange = $this->dateRange ?? 'd7';
        $userGroup = $this->userGroupId ? UserGroups::getGroupById($this->userGroupId) : null;
        // Lowercased, as the total names it: “264 new members”. Group names
        // are usually plural, so they’re left as they are, and singularized
        // for a single new user.
        $userGroupName = $userGroup ? Str::lower(t($userGroup->name, category: 'site')) : null;

        return [
            'userGroupId' => $this->userGroupId,
            'userGroupName' => $userGroupName,
            'userGroupNameSingular' => $userGroupName !== null ? Str::singular($userGroupName) : null,
            'dateRange' => $dateRange,
            'dateRangeLabel' => $this->dateRangeOptions()[$dateRange] ?? null,
        ];
    }

    /**
     * The date ranges the widget can show, by value.
     *
     * @return array<string, string>
     */
    private function dateRangeOptions(): array
    {
        return [
            'd7' => t('Last {num, number} {num, plural, =1{day} other{days}}', ['num' => 7]),
            'd30' => t('Last {num, number} {num, plural, =1{day} other{days}}', ['num' => 30]),
            'lastweek' => t('Last Week'),
            'lastmonth' => t('Last Month'),
        ];
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        $ui = Ui::make([
            Field::make(t('Date Range'))
                ->control(Choice::make('dateRange')->value($this->dateRange)->options(
                    collect($this->dateRangeOptions())
                        ->map(fn (string $label, string $value): array => ['label' => $label, 'value' => $value])
                        ->values()
                        ->all(),
                )),
        ]);

        $userGroups = UserGroups::getAllGroups();

        return $ui->when($userGroups->isNotEmpty(), fn (Ui $ui) => $ui->add(
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
