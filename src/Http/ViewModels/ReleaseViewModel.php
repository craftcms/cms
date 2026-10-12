<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
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
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\Markdown as MarkdownParser;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Controls\DateTime;
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
            plannedAt: $this->release->plannedAt,
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

    /** @return list<array{label: string, variant: string, action: array{type: 'http', method: 'DELETE', url: string, confirm: string}}> */
    public function formAdditionalButtons(): array
    {
        if ($this->readOnly() || ! $this->actor->can('manage', $this->release)) {
            return [];
        }

        return [[
            'label' => t('Delete release'),
            'variant' => 'danger-plain',
            'action' => [
                'type' => 'http',
                'method' => 'DELETE',
                'url' => route('craft.cp.releases.destroy', $this->release),
                'confirm' => t('Delete this release? Its unpublished drafts will be preserved.'),
            ],
        ]];
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

    public function ui(): UiPayload
    {
        $readiness = $this->readiness();
        $rows = array_map(function (ReleaseItemData $item) use ($readiness): array {
            $messages = $readiness?->itemErrors[$item->id] ?? [];
            if ($messages === []) {
                $messages = $readiness?->itemWarnings[$item->id] ?? [];
            }

            $state = $readiness === null ? t('Not available') : t('Ready');
            if ($messages !== []) {
                $state = ['html' => (string) Html::ul($messages, ['class' => 'list-disc ps-5'], encode: true)];
            }

            if ($item->publishedAt !== null) {
                $date = new \IntlDateFormatter(app()->getLocale(), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, Cms::timezone());
                $state = t('Published {date}', ['date' => $date->format($item->publishedAt)]);
            }

            return [
                'id' => $item->id,
                'title' => ['label' => $item->title, 'url' => $item->url],
                'draftName' => $item->draftName,
                'sites' => implode(', ', $item->sites) ?: t('Not available'),
                'readiness' => $state,
                '_deletable' => $item->canDetach,
                '_deleteUrl' => $item->canDetach && $item->draftId !== null
                    ? route('craft.cp.releases.detach', [$this->release, $item->draftId])
                    : null,
                '_deleteLabel' => t('Remove {title}', ['title' => $item->title]),
                '_deleteConfirmMessage' => t('Remove “{title}” from this release? The draft will be preserved.', ['title' => $item->title]),
            ];
        }, $this->items());

        $table = UiTable::make('release-items')
            ->columns([
                ['key' => 'title', 'label' => t('Content')],
                ['key' => 'draftName', 'label' => t('Draft')],
                ['key' => 'sites', 'label' => t('Sites')],
                ['key' => 'readiness', 'label' => t('Readiness')],
            ])
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
            Field::make(t('Planned date'), DateTime::make('plannedAt')->showTime()->showTimeZone())
                ->instructions(t('This date is for planning only. Scheduling is a separate action.')),
        ]), new UiContext(values: [
            'name' => $this->release->name,
            'description' => $this->release->description,
            'plannedAt' => $this->dateValue($this->release->plannedAt),
        ], mode: ControlMode::Editable));
    }

    public function descriptionHtml(): ?string
    {
        return $this->release->description === null
            ? null
            : HtmlSanitizers::sanitize(MarkdownParser::parse($this->release->description));
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
            draftName: $element?->draftName,
            url: $viewableElement?->getCpEditUrl(),
            sites: array_map(
                fn (ElementInterface $siteElement): string => $siteElement->getSite()->getName(),
                $elements,
            ),
            publishedAt: $item->publishedAt,
            canDetach: $viewableElement !== null
                && $item->draftId !== null
                && $this->actor->can('detach', [$this->release, $viewableElement]),
        );
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

    /** @return array{date: string, time: string, timezone: string} */
    private function dateValue(?\DateTimeInterface $date): array
    {
        return [
            'date' => $date?->format('Y-m-d') ?? '',
            'time' => $date?->format('H:i') ?? '',
            'timezone' => $date?->getTimezone()->getName() ?? Cms::timezone(),
        ];
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
