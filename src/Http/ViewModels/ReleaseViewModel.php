<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Enums\Appearance;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Release\Data\ReleaseDetailData;
use CraftCms\Cms\Release\Data\ReleaseItemData;
use CraftCms\Cms\Release\Data\ReleaseReadiness;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Exceptions\ReleaseException;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Models\ReleaseItem;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\Markdown as MarkdownParser;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Controls\Markdown;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Table as UiTable;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ReleaseViewModel extends ViewModel
{
    public function __construct(
        private readonly Release $release,
        private readonly CraftUser $actor,
        private readonly Releases $releases,
        private readonly UiResolver $uiResolver,
    ) {}

    public function release(): ReleaseDetailData
    {
        $canManage = $this->actor->can('manage', $this->release);
        $canPublish = $this->canPublish();

        return new ReleaseDetailData(
            id: $this->release->id,
            name: $this->release->name,
            status: $this->release->status,
            targetDate: $this->release->targetDate,
            publishedAt: $this->release->publishedAt,
            lastError: $canPublish ? $this->release->lastError : null,
            canManage: $canManage,
            canSchedule: $canPublish,
            canPublish: $canPublish,
            canUnschedule: $this->release->status === ReleaseStatus::Scheduled
                && $this->actor->can('publishReleases'),
        );
    }

    public function readOnly(): bool
    {
        return ! $this->release->status->isMutable();
    }

    /**
     * The content toolbar's action menu.
     *
     * @return list<array{label: string, icon: string, variant: string, action: array{type: 'http', method: 'DELETE', url: string, confirm: string}}>
     */
    public function actionMenu(): array
    {
        if (! $this->editable()) {
            return [];
        }

        return [[
            'label' => t('Delete release'),
            'icon' => 'trash',
            'variant' => 'danger',
            'action' => [
                'type' => 'http',
                'method' => 'DELETE',
                'url' => route('craft.cp.releases.destroy', $this->release),
                'confirm' => t('Delete this release? Its unpublished drafts will be preserved.'),
            ],
        ]];
    }

    /** Where saving returns to, encrypted as the form's posted `redirect`. */
    public function redirectUrl(): ?string
    {
        return $this->editable() ? Crypt::encrypt(route('craft.cp.releases.index')) : null;
    }

    /**
     * The Save button's alternate actions.
     *
     * @return list<array{label: string, redirect: string, shortcut: bool, retainScroll: bool}>
     */
    public function formActions(): array
    {
        if (! $this->editable()) {
            return [];
        }

        return [[
            'label' => t('Save and continue editing'),
            'redirect' => Crypt::encrypt(route('craft.cp.releases.show', $this->release)),
            'shortcut' => true,
            'retainScroll' => true,
        ]];
    }

    private function editable(): bool
    {
        return ! $this->readOnly() && $this->actor->can('manage', $this->release);
    }

    /** @return list<ReleaseItemData> */
    private function items(): array
    {
        return $this->release->items()
            ->orderBy('id')
            ->get()
            ->map(fn (ReleaseItem $item): ReleaseItemData => $this->itemData($item))
            ->all();
    }

    public function readiness(): ?ReleaseReadiness
    {
        return once(fn (): ?ReleaseReadiness => $this->canPublish()
            ? $this->releases->checkReadiness($this->release, $this->actor)
            : null);
    }

    /** Why the release can't be published or scheduled yet, if it can't. */
    public function publishDisabledReason(): ?string
    {
        $readiness = $this->readiness();

        if ($readiness === null) {
            return $this->release->items()->exists() ? null : t('The release has no drafts.');
        }

        if ($readiness->ready) {
            return null;
        }

        if ($readiness->errors !== []) {
            return implode(' ', $readiness->errors);
        }

        return t('{count, plural, =1{# draft is blocking publication.} other{# drafts are blocking publication.}}', [
            'count' => count($readiness->itemErrors),
        ]);
    }

    public function ui(): UiPayload
    {
        $readiness = $this->readiness();
        $published = $this->release->status === ReleaseStatus::Published;
        $rows = array_map(function (ReleaseItemData $item) use ($readiness): array {
            $errors = $readiness?->itemErrors[$item->id] ?? [];
            $warnings = $readiness?->itemWarnings[$item->id] ?? [];
            [$label, $color] = match (true) {
                $item->publishedAt !== null => [t('Published {date}', ['date' => $this->formatDate($item->publishedAt)]), Color::Emerald],
                $errors !== [] => [t('Blocked'), Color::Red],
                $warnings !== [] => [t('Ready with warnings'), Color::Amber],
                default => [t('Ready'), Color::Emerald],
            };
            $issues = [...$errors, ...$warnings];

            return [
                'id' => $item->id,
                'title' => $item->chipHtml !== null
                    ? ['html' => $item->chipHtml]
                    : ['label' => $item->title],
                'status' => ['html' => Html::tag('craft-badge', Html::encode($label), ['fill' => $color->value])],
                'issues' => ['html' => $issues === [] ? '' : (string) Html::ul($issues, ['class' => 'grid gap-1'], encode: true)],
                'publishedAt' => $item->publishedAt === null ? '' : $this->formatDate($item->publishedAt),
                '_deletable' => $item->canDetach,
                '_deleteUrl' => $item->canDetach && $item->draftId !== null
                    ? route('craft.cp.releases.detach', [$this->release, $item->draftId])
                    : null,
                '_deleteLabel' => t('Remove {title}', ['title' => $item->title]),
                '_deleteConfirmMessage' => t('Remove “{title}” from this release? The draft will be preserved.', ['title' => $item->title]),
            ];
        }, $this->items());

        $columns = [
            ['key' => 'title', 'label' => t('Draft'), 'wrap' => true],
            ...match (true) {
                $published => [['key' => 'publishedAt', 'label' => t('Published')]],
                $readiness !== null => [
                    ['key' => 'status', 'label' => t('Status')],
                    ...($readiness->itemErrors !== [] || $readiness->itemWarnings !== []
                        ? [['key' => 'issues', 'label' => t('Issues'), 'width' => '2fr', 'wrap' => true]]
                        : []),
                ],
                default => [],
            },
        ];

        $table = UiTable::make('release-items')
            ->columns($columns)
            ->rows($rows)
            ->emptyMessage(t('No drafts have been added to this release.'));

        if (! $this->readOnly()) {
            $table->deletable();
        }

        return $this->uiResolver->resolve(Ui::make([$table]), new UiContext);
    }

    public function editUi(): ?UiPayload
    {
        if (! $this->actor->can('manage', $this->release) || $this->readOnly()) {
            return null;
        }

        return $this->uiResolver->resolve(Ui::make([
            Field::make(t('Name'), Text::make('name'))->required(),
            Field::make(t('Description'), Markdown::make('description')
                ->toolbarButtons(MarkdownField::DEFAULT_TOOLBAR_BUTTONS)),
            ReleaseDateGroup::dateTimeField('targetDate', t('Target Date'), fullWidth: true)
                ->instructions(t('This date is for planning only. Scheduling is a separate action.')),
        ]), new UiContext(values: [
            'name' => $this->release->name,
            'description' => $this->release->description,
            'targetDate' => ReleaseDateGroup::dateTimeValue($this->release->targetDate),
        ], mode: ControlMode::Editable));
    }

    public function metadataHtml(): ?string
    {
        $release = $this->release;
        $dates = app(ContentHtml::class)->metadataHtml([
            t('Scheduled for') => fn () => $release->status === ReleaseStatus::Scheduled && $release->publishedAt !== null
                ? Html::encode($this->formatDate($release->publishedAt))
                : false,
            t('Published') => fn () => $release->status === ReleaseStatus::Published && $release->publishedAt !== null
                ? Html::encode($this->formatDate($release->publishedAt))
                : false,
        ]);
        $description = $release->description === null
            ? ''
            : Html::tag('section', implode('', [
                Html::tag('h3', Html::encode(t('Description')), ['class' => 'font-bold text-xs mb-xs']),
                Html::tag('div', HtmlSanitizers::sanitize(MarkdownParser::parse($release->description)), ['class' => 'markdown']),
            ]), ['class' => $dates === '' ? null : 'mt-lg']);

        return $dates.$description ?: null;
    }

    private function formatDate(\DateTimeInterface $date): string
    {
        return (string) new \IntlDateFormatter(app()->getLocale(), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, Cms::timezone())
            ->format($date);
    }

    private function itemData(ReleaseItem $item): ReleaseItemData
    {
        $elements = $this->itemElements($item);
        $authorizedElements = array_values(array_filter(
            $elements,
            fn (ElementInterface $element): bool => $this->actor->can('view', $element),
        ));
        $viewableElement = $authorizedElements[0] ?? null;
        $element = $viewableElement ?? $elements[0] ?? null;

        return new ReleaseItemData(
            id: $item->id,
            restricted: $viewableElement === null,
            title: $element === null ? t('Not available') : ($element->title ?? t('Untitled')),
            draftId: $item->draftId,
            canonicalId: $item->canonicalId,
            chipHtml: $element === null ? null : $this->chipHtml($element, $viewableElement !== null),
            publishedAt: $item->publishedAt,
            canDetach: $element !== null
                && $item->draftId !== null
                && $this->actor->can('detach', [$this->release, $element]),
        );
    }

    private function chipHtml(ElementInterface $element, bool $hyperlink): string
    {
        // A chip can't be rendered once the live element is gone along with its
        // section, so the draft is labeled the same way, without one.
        if (! $element->getIsUnpublishedDraft() && $element->getCanonical(true)->trashed) {
            return Html::encode($element->title ?? t('Untitled')).
                Html::tag('span', Html::encode($element->draftName ?? t('Draft')), ['class' => 'context-label']);
        }

        return app(ElementHtml::class)->elementChipHtml($element, [
            'appearance' => Appearance::Plain->value,
            'hyperlink' => $hyperlink,
            'showStatus' => false,
        ]);
    }

    /** @return list<ElementInterface> */
    private function itemElements(ReleaseItem $item): array
    {
        $elementQuery = DB::table(Table::ELEMENTS);
        if ($item->draftId !== null) {
            $elementQuery->where('draftId', $item->draftId);
        } else {
            $elementQuery->where('id', $item->publishedElementId ?? $item->canonicalId);
        }

        $elementType = $elementQuery->value('type');
        if (! is_string($elementType) || ! is_a($elementType, ElementInterface::class, true)) {
            return [];
        }

        $query = $elementType::find()
            ->drafts(null)
            ->revisions(null)
            ->site('*')
            ->status(null);

        return $item->draftId !== null
            ? $query->draftId($item->draftId)->all()
            : $query->id($item->publishedElementId ?? $item->canonicalId)->all();
    }

    private function canPublish(): bool
    {
        try {
            return $this->actor->can('publish', $this->release);
        } catch (ReleaseException) {
            return false;
        }
    }
}
