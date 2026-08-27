<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Shared\Enums\Color;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Selects an ordered list of element IDs through Craft's element selector.
 */
class ElementSelect extends Control
{
    #[\Override]
    protected mixed $value = [];

    /** @var class-string<ElementInterface>|null */
    private ?string $elementType = null;

    /** @var list<string>|null */
    private ?array $sources = null;

    /** @var array<string, mixed> */
    private array $criteria = [];

    private ?string $selectionLabel = null;

    private ?int $limit = null;

    private bool $showSiteMenu = false;

    /**
     * The view modes a relation field can be set to, mirroring
     * {@see BaseRelationField::supportedViewModes()}. The
     * field passes its own setting straight through.
     */
    public const string VIEW_MODE_LIST = 'list';

    public const string VIEW_MODE_LIST_INLINE = 'list-inline';

    public const string VIEW_MODE_THUMBS = 'thumbs';

    public const string VIEW_MODE_CARDS = 'cards';

    public const string VIEW_MODE_CARDS_GRID = 'cards-grid';

    /** Thumbnails are rendered at the size the element index uses. */
    private const int THUMB_SIZE = 120;

    private string $viewMode = self::VIEW_MODE_LIST;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $editable = $attributes['name'] !== null;

        return FormFields::elementSelectHtml([
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'elements' => self::elements($control->props['elementType'], $value),
            'elementType' => $control->props['elementType'],
            'sources' => $control->props['sources'],
            'criteria' => $control->props['criteria'],
            'selectionLabel' => $control->props['selectionLabel'],
            'limit' => $control->props['limit'],
            'showSiteMenu' => $control->props['showSiteMenu'],
            'allowAdd' => $editable,
            'allowRemove' => $editable,
            'sortable' => false,
            'disabled' => ! $editable,
            'useCustomElement' => true,
            'customElement' => $control->props['customElement'],
        ]);
    }

    public function component(): string
    {
        return 'craft:element-select';
    }

    /** @param class-string<ElementInterface> $elementType */
    public function elementType(string $elementType): static
    {
        if (! is_a($elementType, ElementInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Element type [%s] must implement %s.',
                $elementType,
                ElementInterface::class,
            ));
        }

        $this->elementType = $elementType;

        return $this;
    }

    /** @param list<string>|null $sources */
    public function sources(?array $sources): static
    {
        $this->sources = $sources;

        return $this;
    }

    /** @param array<string, mixed> $criteria */
    public function criteria(array $criteria): static
    {
        $this->criteria = $criteria;

        return $this;
    }

    public function selectionLabel(string $selectionLabel): static
    {
        $this->selectionLabel = $selectionLabel;

        return $this;
    }

    public function limit(?int $limit): static
    {
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('Element selection limits must be at least 1.');
        }

        $this->limit = $limit;

        return $this;
    }

    public function showSiteMenu(bool $showSiteMenu = true): static
    {
        $this->showSiteMenu = $showSiteMenu;

        return $this;
    }

    public function viewMode(string $viewMode): static
    {
        if (! in_array($viewMode, self::viewModes(), true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown element select view mode [%s].',
                $viewMode,
            ));
        }

        $this->viewMode = $viewMode;

        return $this;
    }

    /** @return list<string> */
    public static function viewModes(): array
    {
        return [
            self::VIEW_MODE_LIST,
            self::VIEW_MODE_LIST_INLINE,
            self::VIEW_MODE_THUMBS,
            self::VIEW_MODE_CARDS,
            self::VIEW_MODE_CARDS_GRID,
        ];
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        if ($this->elementType === null) {
            throw new InvalidArgumentException('ElementSelect Controls require an element type.');
        }

        $elements = self::elements($this->elementType, $value);

        return [
            'elementType' => $this->elementType,
            'customElement' => self::customElement($this->elementType),
            'elements' => array_map(fn (ElementInterface $element): array => self::elementPayload($element) + self::viewPayload($element, $this->viewMode), $elements),
            'elementDisplayName' => $this->elementType::lowerDisplayName(),
            'sources' => $this->sources,
            'criteria' => $this->criteria,
            'selectionLabel' => $this->selectionLabel ?? t('Choose'),
            'limit' => $this->limit,
            'showSiteMenu' => $this->showSiteMenu,
            'viewMode' => $this->viewMode,
        ];
    }

    /**
     * The extra per-element data a view mode needs on top of
     * {@see elementPayload()}.
     *
     * Only the active mode's parts are rendered — a list field shouldn't pay to
     * build card markup it will never show.
     *
     * @return array<string, mixed>
     */
    private static function viewPayload(ElementInterface $element, string $viewMode): array
    {
        if (in_array($viewMode, [self::VIEW_MODE_CARDS, self::VIEW_MODE_CARDS_GRID], true)) {
            return self::cardPayload($element);
        }

        if ($viewMode === self::VIEW_MODE_THUMBS) {
            return ['thumbHtml' => $element->getThumbHtml(self::THUMB_SIZE)];
        }

        if (in_array($viewMode, [self::VIEW_MODE_LIST, self::VIEW_MODE_LIST_INLINE], true)) {
            return ['thumbHtml' => $element->getThumbHtml(30)];
        }

        return [];
    }

    /**
     * Card parts in the shape the `ElementCards` component consumes, matching
     * what the element index builds for its own card view.
     *
     * @return array<string, mixed>
     */
    private static function cardPayload(ElementInterface $element): array
    {
        $elementHtml = app(ElementHtml::class);

        // A per-element `id` ties the parts together while staying unique per
        // card. Selection and sorting are the field's job, not the card's.
        $cardConfig = [
            'id' => sprintf('card-%s', mt_rand()),
            'context' => 'field',
            'hyperlink' => $element->getCpEditUrl() !== null,
            'showEditButton' => false,
            'autoReload' => false,
            'selectable' => false,
            'sortable' => false,
            'withThumb' => false,
        ];

        return [
            'cardAttributes' => $elementHtml->elementCardAttributes($element, $cardConfig),
            'cardHeaderHtml' => $elementHtml->elementCardHeaderHtml($element, $cardConfig),
            'cardContentHtml' => $elementHtml->elementCardContentHtml($element, $cardConfig),
            'cardFooterHtml' => $elementHtml->elementCardFooterHtml($element, $cardConfig),
            'cardThumbHtml' => $elementHtml->elementCardThumbHtml($element),
            'thumbAlignment' => $elementHtml->elementCardThumbAlignment($element),
        ];
    }

    /**
     * What a chip needs to draw itself and offer its own actions.
     *
     * The chip's menu mirrors the element's own — View in a new tab, Edit,
     * Copy — which Craft 5 gets from `Element::safeActionMenuItems()`. Those
     * are permission- and state-dependent, so the decisions are made here and
     * sent as flags rather than re-derived on the client.
     *
     * @return array<string, mixed>
     */
    private static function elementPayload(ElementInterface $element): array
    {
        return [
            'id' => $element->getId(),
            'label' => $element->getUiLabel(),
            'siteId' => $element->siteId,
            // Only a routable element can be viewed on the front end.
            'url' => $element->getUrl(),
            'canEdit' => Gate::check('view', $element),
            // A revision is a snapshot; there's nothing to copy from it.
            'canCopy' => ! $element->getIsRevision() && Gate::check('copy', $element),
            'draftId' => $element->draftId,
            'revisionId' => $element->revisionId,
            'status' => self::statusPayload($element),
            'actions' => $element->actionMenuDescriptors(ElementActionContext::Field),
        ];
    }

    /**
     * The chip's status dot, resolved the way an element index chip resolves
     * it — see `Cp\Html\ElementHtml::chipHtml()`, which shows the indicator
     * only for a draft or an element whose type opts in, and colors it from
     * the status definition falling back to {@see Color::tryFromStatus()}.
     *
     * Resolved here rather than on the client because the status definitions,
     * their labels and their colors are all element-type concerns.
     *
     * @return array{fill: string, label: string, draft: bool}|null
     */
    private static function statusPayload(ElementInterface $element): ?array
    {
        if (! $element->getIsDraft() && ! $element->showStatusIndicator()) {
            return null;
        }

        $status = $element->getIsDraft() ? 'draft' : $element->getStatus();

        if (! is_string($status) || $status === '') {
            return null;
        }

        if ($status === 'draft') {
            return ['fill' => Color::Gray->value, 'label' => t('Draft'), 'draft' => true];
        }

        $definition = $element::statuses()[$status] ?? [];
        $definition = is_string($definition) ? ['label' => $definition] : $definition;

        $color = $definition['color'] ?? Color::tryFromStatus($status) ?? Color::Gray;

        return [
            'fill' => $color instanceof Color ? $color->value : (string) $color,
            'label' => (string) ($definition['label'] ?? ucfirst($status)),
            'draft' => false,
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return list<ElementInterface>
     */
    private static function elements(string $elementType, mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        return $elementType::find()->id(array_values($value))->fixedOrder()->all();
    }

    /** @param class-string<ElementInterface> $elementType */
    private static function customElement(string $elementType): string
    {
        if (is_a($elementType, Asset::class, true)) {
            return 'craft-asset-select-input';
        }

        if (is_a($elementType, Entry::class, true)) {
            return 'craft-entry-select-input';
        }

        return 'craft-element-select-input';
    }
}
