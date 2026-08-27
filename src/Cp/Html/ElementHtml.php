<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Component\Contracts\Actionable;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\Colorable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Describable;
use CraftCms\Cms\Component\Contracts\Grippable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Component\Contracts\Indicative;
use CraftCms\Cms\Component\Contracts\Statusable;
use CraftCms\Cms\Component\Contracts\Thumbable;
use CraftCms\Cms\Cp\Components\ActionMenu;
use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\Icon;
use CraftCms\Cms\Cp\Enums\ButtonVariant;
use CraftCms\Cms\Cp\Events\ElementCardHtmlResolving;
use CraftCms\Cms\Cp\Events\ElementChipHtmlResolving;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

#[Singleton]
readonly class ElementHtml
{
    public const string CHIP_SIZE_SMALL = 'small';

    public const string CHIP_SIZE_LARGE = 'large';

    public function __construct(
        private StatusHtml $statusHtml,
        private ContentHtml $contentHtml,
    ) {}

    /** @param array<string, mixed> $config */
    public function chipHtml(Chippable $component, array $config = []): string
    {
        $config += [
            'attributes' => [],
            'autoReload' => true,
            'class' => null,
            'hyperlink' => false,
            'id' => sprintf('chip-%s', mt_rand()),
            'inputName' => null,
            'inputValue' => null,
            'labelHtml' => null,
            'overrides' => [],
            'selectable' => false,
            'showActionMenu' => false,
            'showDescription' => false,
            'showHandle' => false,
            'showIndicators' => false,
            'showLabel' => true,
            'showStatus' => true,
            'showThumb' => true,
            'size' => self::CHIP_SIZE_SMALL,
            'sortable' => false,
        ];

        $config['showActionMenu'] = $config['showActionMenu'] && $component instanceof Actionable;
        $config['showHandle'] = $config['showHandle'] && $component instanceof Grippable;
        $config['showStatus'] = $config['showStatus'] && $component instanceof Statusable;
        $config['showThumb'] = $config['showThumb'] && ($component instanceof Thumbable || $component instanceof Iconic);
        $config['showIndicators'] = $config['showIndicators'] && $component instanceof Indicative;
        $config['showDescription'] = $config['showDescription'] && $component instanceof Describable;

        $color = $component instanceof Colorable ? $component->getColor() : null;

        $attributes = Arr::merge([
            'id' => $config['id'],
            'size' => $config['size'],
            'class' => [
                'cp-color-'.($color->value ?? 'white'),
                $config['size'],
                ...Html::explodeClass($config['class']),
            ],
            'show-indicators' => $config['showIndicators'],
            'show-thumb' => $config['showThumb'],
            'show-status' => $config['showStatus'],
            'selectable' => $config['selectable'],
            'appearance' => $config['appearance'] ?? null,
            'data' => array_filter([
                'type' => $component::class,
                'id' => $component->getId(),
                'label' => $component->getUiLabel(),
                'description' => $component instanceof Describable ? $component->getDescription() : null,
                'handle' => $component instanceof Grippable ? $component->getHandle() : null,
                'settings' => $config['autoReload'] ? [
                    'selectable' => $config['selectable'],
                    'id' => InputNamespace::namespaceId($config['id']),
                    'hyperlink' => $config['hyperlink'],
                    'showLabel' => $config['showLabel'],
                    'showHandle' => $config['showHandle'],
                    'showStatus' => $config['showStatus'],
                    'showThumb' => $config['showThumb'],
                    'showDescription' => $config['showDescription'],
                    'overrides' => $config['overrides'],
                    'size' => $config['size'],
                    'ui' => 'chip',
                ] : false,
            ]),
        ], $config['attributes']);

        $html = Html::beginTag('craft-chip', $attributes);

        if ($config['showStatus']) {
            /** @var Chippable&Statusable $component */
            $html .= Html::tag('div', $this->statusHtml->componentStatusIndicatorHtml($component) ?? '', [
                'slot' => 'status',
            ]);
        }

        if ($config['showThumb']) {
            $html .= Html::beginTag('div', ['slot' => 'thumbnail']);
            if ($component instanceof Thumbable) {
                $thumbSize = $config['size'] === self::CHIP_SIZE_SMALL ? 30 : 120;
                $html .= $component->getThumbHtml($thumbSize) ?? '';
            } else {
                /** @var Chippable&Iconic $component */
                $icon = $component->getIcon();
                if ($icon || $icon === '0') {
                    $html .= Icon::make()->name($icon)->slot('icon');
                }
            }
            $html .= Html::endTag('div');
        }

        if ($config['selectable']) {
            $html .= Html::beginTag('div', ['slot' => 'prefix']);
            $html .= $this->componentCheckboxHtml(sprintf('%s-label', $config['id']));
            $html .= Html::endTag('div');
        }

        if (isset($config['labelHtml'])) {
            $html .= $config['labelHtml'];
        } elseif ($config['showLabel']) {
            $labelHtml = Html::encode($component->getUiLabel());
            if ($config['hyperlink'] && $component instanceof CpEditable) {
                $url = $component->getCpEditUrl();
                if ($url) {
                    $labelHtml = Html::a($labelHtml, $url);
                }
            }

            if ($config['showDescription']) {
                /** @var Chippable&Describable $component */
                $description = $component->getDescription();
                if ($description) {
                    $labelHtml .= Html::tag('craft-info-icon',
                        $this->contentHtml->parseMarkdown(Html::encode($description)));
                }
            }

            $labelHtml = Html::tag('div', $labelHtml);

            if ($config['showHandle']) {
                /** @var Chippable&Grippable $component */
                $handle = $component->getHandle();
                if ($handle) {
                    $labelHtml .= Html::tag('div', Html::encode($handle), [
                        'class' => ['cp-code'],
                    ]);
                }
            }
            if ($config['showIndicators']) {
                /** @var Chippable&Indicative $component */
                $indicators = $component->getIndicators();
                if (! empty($indicators)) {
                    $labelHtml .= Html::beginTag('div', ['class' => 'indicators']).
                        implode('', array_map(function (array $indicator) {
                            $color = $indicator['iconColor'] ?? null;
                            if ($color instanceof Color) {
                                $color = $color->value;
                            }

                            return Html::tag('div', Icons::svg($indicator['icon']), [
                                'class' => array_filter(['cp-icon', 'puny', $color]),
                                'title' => $indicator['label'],
                                'aria' => ['label' => $indicator['label']],
                            ]);
                        }, $indicators)).
                        Html::endTag('div');
                }
            }
            $html .= Html::tag('div', $labelHtml, [
                'id' => sprintf('%s-label', $config['id']),
            ]);
        }

        $html .= Html::beginTag('div', ['slot' => 'suffix']);
        if ($config['showActionMenu']) {
            /** @var Chippable&Actionable $component */
            $html .= $this->componentActionMenu($component);
        }
        if ($config['sortable']) {
            $html .= Button::make()
                ->icon('move')
                ->attributes([
                    'class' => ['chromeless', 'small', 'move-btn'],
                    'title' => t('Reorder'),
                    'aria' => [
                        'label' => t('Reorder'),
                    ],
                    'role' => 'none',
                    'tabindex' => '-1',
                ]);
        }
        $html .= Html::endTag('div'); // slot=suffix

        if ($config['inputName'] !== null) {
            $inputValue = $config['inputValue'] ?? $component->getId();
            $html .= Html::hiddenInput($config['inputName'], (string) $inputValue);
        } // .element

        return $html.Html::endTag('craft-chip');
    }

    /**
     * Renders an element’s chip HTML.
     *
     * The following config settings can be passed to `$config`:
     *
     * - `attributes` – Any custom HTML attributes that should be set on the chip
     * - `autoReload` – Whether the chip should auto-reload itself when it’s saved
     * - `class` – Class name(s) that should be added to the container element
     * - `context` – The context the chip is going to be shown in (`index`, `field`, etc.)
     * - `hyperlink` – Whether the chip label should be hyperlinked to the element’s URL
     * - `returnUrl` – The `returnUrl` param that should be added to the hyperlink URL
     * - `id` – The chip’s `id` attribute
     * - `inputName` – The `name` attribute that should be set on a hidden input, if set
     * - `inputValue` – The `value` attribute that should be set on the hidden input, if `inputName` is set. Defaults to [[\craft\base\Identifiable::getId()`]].
     * - `labelHtml` – The label HTML, if it should be different from [[Chippable::getUiLabel()]]
     * - `overrides` – Any config overrides that should persist when the chip is re-rendered
     * - `selectable` – Whether the chip should include a checkbox input
     * - `showActionMenu` – Whether the chip should include an action menu
     * - `showDescription` – Whether the chip should include the element’s description
     * - `showDraftName` – Whether to show the draft name beside the label if the element is a draft of a published element
     * - `showHandle` – Whether the element’s handle should be show (only applies if the element implements [[Grippable]])
     * - `showIndicators` – Whether the element’s indicators should be shown (only applies if the element implements [[Indicative]])
     * - `showLabel` – Whether the element’s label should be shown
     * - `showProvisionalDraftLabel` – Whether an “Edited” badge should be added to the label if the element is a provisional draft
     * - `showStatus` – Whether the element’s status should be shown
     * - `showThumb` – Whether the element’s thumbnail should be shown
     * - `size` – The size of the chip (`small` or `large`)
     * - `sortable` – Whether the chip should include a drag handle
     */
    /** @param array<string, mixed> $config */
    public function elementChipHtml(ElementInterface $element, array $config = []): string
    {
        $config += [
            'attributes' => [],
            'autoReload' => true,
            'context' => 'index',
            'returnUrl' => null,
            'id' => sprintf('chip-%s', mt_rand()),
            'inputName' => null,
            'selectable' => false,
            'showActionMenu' => false,
            'showDraftName' => true,
            'showLabel' => true,
            'showProvisionalDraftLabel' => null,
            'showStatus' => true,
            'showThumb' => true,
            'size' => self::CHIP_SIZE_SMALL,
            'sortable' => false,
        ];

        $config['attributes'] = Arr::merge(
            $this->baseElementAttributes($element, $config),
            [
                'data' => array_filter([
                    'settings' => $config['autoReload'] ? [
                        'context' => $config['context'],
                        'returnUrl' => $config['returnUrl'],
                        'showDraftName' => $config['showDraftName'],
                        'showProvisionalDraftLabel' => $config['showProvisionalDraftLabel'],
                    ] : false,
                ]),
            ],
            $config['attributes'],
        );

        $config['showStatus'] = $config['showStatus'] && ($element->getIsDraft() || $element->showStatusIndicator());

        if ($config['showLabel']) {
            $config['labelHtml'] = $this->elementLabelHtml(
                $element,
                $config,
                $config['attributes'],
                fn () => $element->getChipLabelHtml(),
            );
        }

        if (
            ($config['showProvisionalDraftLabel'] ?? $config['showLabel']) &&
            ($element->isProvisionalDraft || $element->hasProvisionalChanges)
        ) {
            $config['labelHtml'] = ($config['labelHtml'] ?? '').$this->statusHtml->editedStatusLabelHtml();
        }

        if ($config['inputName'] !== null && $element->isProvisionalDraft) {
            $config['inputValue'] = $element->getCanonicalId();
        }

        $html = $this->chipHtml($element, $config);

        event($event = new ElementChipHtmlResolving($element, $config['context'], $html));

        return $event->html;
    }

    /**
     * Renders an element’s card HTML.
     *
     * The following config settings can be passed to `$config`:
     *
     * - `attributes` – Any custom HTML attributes that should be set on the card
     * - `autoReload` – Whether the card should auto-reload itself when it’s saved
     * - `context` – The context the card is going to be shown in (`index`, `field`, etc.)
     * - `hyperlink` – Whether the card label should be hyperlinked to the element’s URL
     * - `returnUrl` – The `returnUrl` param that should be added to the hyperlink URL
     * - `id` – The card’s `id` attribute
     * - `inputName` – The `name` attribute that should be set on the hidden input, if `context` is set to `field`
     * - `selectable` – Whether the card should include a checkbox input
     * - `showActionMenu` – Whether the card should include an action menu
     * - `showNestedActions` – Whether the action menu should include
     *   behavior-less Duplicate/Delete items (marked
     *   `data-duplicate-action`/`data-delete-action`) for the hosting
     *   controller to wire up. A server-side signal — set by the render
     *   paths that know the card belongs to a nested-element context
     *   ({@see NestedElementManager::getCardsHtml()},
     *   `app/render-elements`), never by client-supplied configs — with each
     *   item gated on the current user's permissions. May also be the owner
     *   context as an array (`ownerElementType`/`ownerId`/`ownerSiteId`/
     *   `attribute` — {@see NestedElementManager::getCardsData()}), in which
     *   case the Duplicate and Delete items carry self-contained
     *   `elements/duplicate`/`nested-elements/delete` actions instead of
     *   relying on a hosting manager
     * - `showEditButton` – Whether the card should include an edit button
     * - `sortable` – Whether the card should include a drag handle
     */
    /** @param array<string, mixed> $config */
    public function elementCardHtml(ElementInterface $element, array $config = []): string
    {
        $config = $this->normalizeCardConfig($element, $config);

        $attributes = $this->cardAttributes($element, $config);

        $html = Html::beginTag('craft-card', $attributes).
            $this->elementCardHeaderHtml($element, $config).
            $this->elementCardContentHtml($element, $config).
            $this->elementCardFooterHtml($element, $config).
            Html::endTag('craft-card'); // .card

        event($event = new ElementCardHtmlResolving($element, $config['context'], $html));

        return $event->html;
    }

    /**
     * Renders the header (titlebar) portion of an element’s card.
     *
     * Accepts the same `$config` settings as {@see elementCardHtml()}.
     */
    /** @param array<string, mixed> $config */
    public function elementCardHeaderHtml(ElementInterface $element, array $config = []): string
    {
        $config = $this->normalizeCardConfig($element, $config);

        [$showEditButton, $editAction] = $this->cardEditButtonConfig($element, $config);

        return Html::beginTag('div', ['class' => 'card-titlebar']).
            $this->elementCardLabelHtml($element, $config).
            Html::beginTag('div', ['class' => 'card-actions-container']).
            Html::beginTag('div', ['class' => 'card-actions']).
            ($config['selectable'] ? $this->componentCheckboxHtml(sprintf('%s-label', $config['id'])) : '').
            $this->cardActionsHtml($element, $config, $showEditButton, $editAction).
            Html::endTag('div'). // .card-actions
            Html::endTag('div'). // .card-actions-container
            Html::endTag('div'); // .card-titlebar
    }

    /**
     * Renders the label portion of an element’s card header: its icon, card
     * title, and modified-status badge. Suited to a card component's `label`
     * slot. Accepts the same `$config` settings as {@see elementCardHtml()}.
     *
     * @param  array<string, mixed>  $config
     */
    public function elementCardLabelHtml(ElementInterface $element, array $config = []): string
    {
        $icon = $element instanceof Iconic ? $element->getIcon() : null;
        $title = $element->getCardTitle();

        return Html::beginTag('div', [
            'class' => ['flex', 'flex-nowrap', 'gap-1', 'grow-1', 'items-center'],
        ]).
            ($icon ? Html::tag('craft-icon', '', ['name' => $icon]) : '').
            ($title ? Html::tag('div', Html::encode($title), ['class' => 'card-titlebar-label']) : '').
            Html::endTag('div'). // .flex
            $this->cardModifiedStatusHtml($element);
    }

    /**
     * Renders the actions portion of an element’s card header — the edit
     * button, action menu, and (when sortable) drag handle — as a plain
     * fragment, suited to a card component's `actions` slot. Accepts the
     * same `$config` settings as {@see elementCardHtml()}.
     *
     * @param  array<string, mixed>  $config
     */
    public function elementCardActionsHtml(ElementInterface $element, array $config = []): string
    {
        $config = $this->normalizeCardConfig($element, $config);

        [$showEditButton, $editAction] = $this->cardEditButtonConfig($element, $config);

        return $this->cardActionsHtml($element, $config, $showEditButton, $editAction);
    }

    /**
     * Resolves whether the card gets an edit button and, when it does, the
     * `craft:edit-element` event action it should run — handled by the
     * slideout module's window listener, which opens an element editor
     * slideout (or the element's edit page on ctrl-click).
     *
     * @param  array<string, mixed>  $config
     * @return array{0: bool, 1: array<string, mixed>|null} `[showEditButton, editAction]`
     */
    private function cardEditButtonConfig(ElementInterface $element, array $config): array
    {
        $showEditButton = $config['showEditButton'] && Gate::check('view', $element);

        if (! $showEditButton) {
            return [false, null];
        }

        return [true, [
            'type' => 'event',
            'name' => 'craft:edit-element',
            'detail' => [
                'elementType' => $element::class,
                'settings' => [
                    'elementId' => $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id,
                    'draftId' => $element->isProvisionalDraft ? null : $element->draftId,
                    'revisionId' => $element->revisionId,
                    'siteId' => $element->siteId,
                    'ownerId' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
                ],
                'cpEditUrl' => $element->getCpEditUrl(),
            ],
        ]];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>|null  $editAction
     */
    private function cardActionsHtml(ElementInterface $element, array $config, bool $showEditButton, ?array $editAction): string
    {
        return ($showEditButton ? Button::make()
            ->icon('edit')
            ->variant(ButtonVariant::Plain)
            ->size('small')
            ->action($editAction)
            ->attributes([
                'class' => ['edit-btn'],
                'aria' => [
                    'label' => mb_ucfirst(t('Edit {type}', [
                        'type' => $element::lowerDisplayName(),
                    ])),
                ],
            ]) : '').
            ($config['showActionMenu'] ? $this->componentActionMenu($element, ! $showEditButton, $this->nestedCardActionItems($element, $config)) : '').
            ($config['sortable'] ? Button::make()
                ->icon('move')
                ->attributes([
                    'class' => ['chromeless', 'small', 'move-btn'],
                    'title' => t('Reorder'),
                    'aria' => [
                        'label' => t('Reorder'),
                    ],
                    'role' => 'none',
                    'tabindex' => '-1',
                ]) : '');
    }

    /**
     * Renders the content (main content + thumbnail) portion of an element’s card.
     *
     * Accepts the same `$config` settings as {@see elementCardHtml()}.
     */
    /** @param array<string, mixed> $config */
    public function elementCardContentHtml(ElementInterface $element, array $config = []): string
    {
        $config = $this->normalizeCardConfig($element, $config);

        $attributes = $this->cardAttributes($element, $config);

        $headingContent = $this->elementLabelHtml($element, $config, $attributes, fn () => Html::encode($element->getUiLabel()));
        $bodyContent = $element->getCardBodyHtml() ?? '';

        $labels = array_filter([
            ($config['showStatus'] ?? false) && $config['showStatus'] && $element->showStatusIndicator() ? $this->statusHtml->componentStatusLabelHtml($element) : null,
            $element->isProvisionalDraft || $element->hasProvisionalChanges ? $this->statusHtml->editedStatusLabelHtml() : null,
        ]);

        if (! empty($labels)) {
            $bodyContent .= Html::ul()
                ->items(...array_map(fn ($label) => Html::li($label)->encode(false), $labels))
                ->class('flex gap-xs')
                ->render();
        }

        $contentHtml =
            Html::beginTag('div', ['class' => 'card-content']).
            ($headingContent !== '' ? Html::tag('div', $headingContent, ['class' => 'card-heading']) : '').
            ($bodyContent !== '' ? Html::tag('div', $bodyContent, ['class' => 'card-body']) : '').
            Html::endTag('div'); // .card-content

        // Consumers that render the thumbnail themselves (e.g. a card
        // component's `thumbnail` slot, via `elementCardThumbHtml()`) can
        // opt out of the inline thumb.
        if (! $config['withThumb']) {
            return Html::beginTag('div', ['class' => 'card-main']).$contentHtml.Html::endTag('div');
        }

        $thumbHtml = $this->elementCardThumbHtml($element);
        $thumbAlignment = $this->elementCardThumbAlignment($element);

        $html = Html::beginTag('div', ['class' => 'card-main']);

        if ($thumbAlignment === 'start') {
            $html .= $thumbHtml.$contentHtml;
        } else {
            $html .= $contentHtml.$thumbHtml;
        } // .card-main

        return $html.Html::endTag('div');
    }

    /**
     * Renders an element card’s thumbnail, suited to a card component's
     * `thumbnail` slot (pair with {@see elementCardThumbAlignment()}).
     */
    public function elementCardThumbHtml(ElementInterface $element): string
    {
        return $element->getThumbHtml(120) ?? '';
    }

    /**
     * The card thumbnail alignment configured by the element’s field layout.
     *
     * @return 'start'|'end'
     */
    public function elementCardThumbAlignment(ElementInterface $element): string
    {
        $alignment = $element->getFieldLayout()?->getCardThumbAlignment();

        return $alignment === 'start' ? 'start' : 'end';
    }

    /**
     * Renders the footer portion of an element’s card.
     *
     * In the `field` context this carries the hidden input holding the element’s
     * ID; otherwise it’s empty. Accepts the same `$config` settings as
     * {@see elementCardHtml()}.
     */
    /** @param array<string, mixed> $config */
    public function elementCardFooterHtml(ElementInterface $element, array $config = []): string
    {
        $config = $this->normalizeCardConfig($element, $config);

        if ($config['context'] === 'field' && $config['inputName'] !== null) {
            $inputValue = $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id;

            return (string) Html::hiddenInput($config['inputName'], (string) $inputValue);
        }

        return '';
    }

    /**
     * Returns the HTML attributes for the outer `.card` element as a structured
     * (normalized) tag-attributes array.
     *
     * These match exactly what the wrapper produced by {@see elementCardHtml()}
     * uses, so they can be applied to a card rendered from the individual part
     * methods. Accepts the same `$config` settings as {@see elementCardHtml()}.
     */
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function elementCardAttributes(ElementInterface $element, array $config = []): array
    {
        return $this->cardAttributes($element, $this->normalizeCardConfig($element, $config));
    }

    /**
     * Normalizes the config passed to the card rendering methods.
     *
     * Applying defaults here (rather than in each part method) keeps the
     * generated `id` stable when {@see elementCardHtml()} composes the parts, so
     * the same already-normalized `$config` is threaded through each.
     */
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeCardConfig(ElementInterface $element, array $config): array
    {
        $config += [
            'attributes' => [],
            'autoReload' => true,
            'context' => 'index',
            'hyperlink' => false,
            'returnUrl' => null,
            'id' => sprintf('card-%s', mt_rand()),
            'inputName' => null,
            'selectable' => false,
            'showActionMenu' => false,
            'showNestedActions' => false,
            'showEditButton' => true,
            'withThumb' => true,
            'sortable' => false,
        ];

        if ($element->getIsRevision()) {
            $config['showActionMenu'] = false;
            $config['selectable'] = false;
        }

        return $config;
    }

    /**
     * Builds the server-rendered nested-context items for a card's action
     * menu.
     *
     * `showNestedActions` requests Move forward/backward (when `sortable`),
     * Duplicate, and Delete items — marked with `data-move-forward-action`,
     * `data-move-backward-action`, `data-duplicate-action`, and
     * `data-delete-action` — for the hosting controller (e.g. the nested
     * element manager) to wire up; the items carry no behavior of their own.
     * The permission-bound items only render when the current user passes
     * the corresponding gate (mirroring the card's
     * `data-duplicatable`/`data-deletable` attributes), so client-supplied
     * render configs (`app/render-elements`) can request them but never
     * grant them.
     *
     * @param  array<string, mixed>  $config
     * @return list<array<string, mixed>>
     */
    private function nestedCardActionItems(ElementInterface $element, array $config): array
    {
        if (! $config['showNestedActions'] || ! Gate::check('view', $element)) {
            return [];
        }

        $items = [];

        if ($config['sortable']) {
            $showInGrid = $config['showInGrid'] ?? false;
            $ltr = I18N::getLocale()->getOrientation() === 'ltr';

            $items[] = [
                'icon' => $showInGrid ? ($ltr ? 'arrow-left' : 'arrow-right') : 'arrow-up',
                'label' => $showInGrid ? t('Move forward') : t('Move up'),
                'attributes' => [
                    'data' => ['move-forward-action' => true],
                ],
            ];
            $items[] = [
                'icon' => $showInGrid ? ($ltr ? 'arrow-right' : 'arrow-left') : 'arrow-down',
                'label' => $showInGrid ? t('Move backward') : t('Move down'),
                'attributes' => [
                    'data' => ['move-backward-action' => true],
                ],
            ];
        }

        if (Gate::check('duplicate', $element)) {
            $item = [
                'icon' => 'clone',
                'label' => t('Duplicate'),
                'attributes' => [
                    'data' => ['duplicate-action' => true],
                ],
            ];

            // With a known owner context (`showNestedActions` as an array —
            // the data path, where no `Craft.NestedElementManager` wires the
            // markers), the item carries a self-contained duplicate action,
            // mirroring the legacy manager's `duplicateElement()` request.
            if (is_array($config['showNestedActions'])) {
                $item['action'] = 'elements/duplicate';
                $item['params'] = [
                    'elementType' => $element::class,
                    'elementId' => $element->id,
                    'ownerId' => $config['showNestedActions']['ownerId'],
                    'siteId' => $config['showNestedActions']['ownerSiteId'],
                ];
            }

            $items[] = $item;
        }

        if (Gate::check('delete', $element)) {
            $item = [
                'icon' => 'trash',
                'label' => mb_ucfirst(t('Delete {type}', [
                    'type' => $element::lowerDisplayName(),
                ])),
                'destructive' => true,
                'attributes' => [
                    'data' => ['delete-action' => true],
                ],
            ];

            // With a known owner context (`showNestedActions` as an array —
            // the data path, where no `Craft.NestedElementManager` wires the
            // markers), the item carries a self-contained delete action.
            if (is_array($config['showNestedActions'])) {
                $item['action'] = 'nested-elements/delete';
                $item['params'] = $config['showNestedActions'] + ['elementId' => $element->id];
                $item['confirm'] = t('Are you sure you want to delete the selected {type}?', [
                    'type' => $element::lowerDisplayName(),
                ]);
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Builds the HTML attributes for the outer `.card` element.
     */
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function cardAttributes(ElementInterface $element, array $config): array
    {
        $color = $element instanceof Colorable ? $element->getColor() : null;

        $classes = ['card'];
        if ($element->errors()->isNotEmpty()) {
            $classes[] = 'error';
        }

        $thumb = $element->getThumbHtml(120);
        $thumbAlignment = $element->getFieldLayout()?->getCardThumbAlignment() ?? 'end';

        if ($thumb) {
            if ($thumbAlignment) {
                $classes[] = 'thumb-'.$thumbAlignment;
            }
        }

        return Arr::merge(
            $this->baseElementAttributes($element, $config),
            [
                'class' => $classes,
                'style' => array_filter([
                    '--custom-border-color' => $color?->cssVar(200),
                    '--custom-titlebar-bg-color' => $color?->cssVar(100),
                    '--custom-bg-color' => $color?->cssVar(50),
                    '--custom-text-color' => $color?->cssVar(900),
                    '--custom-sel-titlebar-bg-color' => $color?->cssVar(900),
                    '--custom-sel-bg-color' => $color?->cssVar(800),
                ]),
                'data' => array_filter([
                    'color' => $color?->value ?? 'white',
                    'settings' => $config['autoReload'] ? [
                        'hyperlink' => $config['hyperlink'],
                        'returnUrl' => $config['returnUrl'],
                        'selectable' => $config['selectable'],
                        'context' => $config['context'],
                        'id' => InputNamespace::namespaceId($config['id']),
                        'ui' => 'card',
                    ] : false,
                ]),
            ],
            $config['attributes'],
        );
    }

    /**
     * Renders the “modified” status badge for a nested element that will replace
     * its canonical counterpart when the owner is saved, or an empty string.
     */
    private function cardModifiedStatusHtml(ElementInterface $element): string
    {
        if (
            ! (
                $element instanceof NestedElementInterface &&
                $element->getOwnerId() !== null &&
                $element->getOwnerId() === $element->getPrimaryOwnerId() &&
                ! $element->getIsDraft() &&
                ! $element->getIsRevision() &&
                $element->getOwner()->getIsDerivative()
            )
        ) {
            return '';
        }

        if ($element->getIsCanonical()) {
            // this element was created for the owner
            $statusLabel = t('This is a new {type}.', [
                'type' => $element::lowerDisplayName(),
            ]);
        } else {
            // this element is a derivative of another element owned by the canonical owner
            $statusLabel = t('This {type} has been edited.', [
                'type' => $element::lowerDisplayName(),
            ]);
        }

        return Html::beginTag('div', [
            'class' => ['status-badge', AttributeStatus::Modified->value],
            'title' => $statusLabel,
        ]).
            Html::tag('span', $statusLabel, [
                'class' => 'visually-hidden',
            ]).
            Html::endTag('div');
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function baseElementAttributes(ElementInterface $element, array $config): array
    {
        $user = currentUser();
        $editable = $user && $user->can('view', $element);

        return Arr::merge(
            Html::normalizeTagAttributes($element->getHtmlAttributes($config['context'])),
            [
                'id' => $config['id'],
                'class' => array_filter([
                    'element',
                    $config['context'] === 'field' ? 'removable' : null,
                    ($config['context'] === 'field' && $element->errors()->isNotEmpty()) ? 'error' : null,
                ]),
                'data' => array_filter([
                    'type' => $element::class,
                    'id' => $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id,
                    'draft-id' => $element->isProvisionalDraft ? null : $element->draftId,
                    'revision-id' => $element->revisionId,
                    'field-id' => $element instanceof NestedElementInterface ? $element->getField()?->id : null,
                    'primary-owner-id' => $element instanceof NestedElementInterface ? $element->getPrimaryOwnerId() : null,
                    'owner-id' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
                    'owner-is-canonical' => $this->elementOwnerIsCanonical($element),
                    'site-id' => $element->siteId,
                    'is-unpublished-draft' => $element->getIsUnpublishedDraft(),
                    'status' => $element->getStatus(),
                    'label' => $element->getUiLabel(),
                    'url' => $element->getUrl(),
                    'cp-url' => $editable ? $element->getCpEditUrl() : null,
                    'level' => $element->level,
                    'trashed' => $element->trashed,
                    'editable' => $editable,
                    'savable' => $editable && $this->contextIsAdministrative($config['context']) && Gate::check('save', $element),
                    'duplicatable' => $editable && $this->contextIsAdministrative($config['context']) && Gate::check('duplicate', $element),
                    'duplicatable-as-draft' => $editable && $this->contextIsAdministrative($config['context']) && Gate::check('duplicateAsDraft', $element),
                    'copyable' => $editable && $this->contextIsAdministrative($config['context']) && Gate::check('copy', $element),
                    'deletable' => $editable && $this->contextIsAdministrative($config['context']) && Gate::check('delete', $element),
                    'deletable-for-site' => (
                        $editable &&
                        $this->contextIsAdministrative($config['context']) &&
                        ElementHelper::isMultiSite($element) &&
                        Gate::check('deleteForSite', $element)
                    ),
                ]),
            ],
        );
    }

    private function elementOwnerIsCanonical(ElementInterface $element): bool
    {
        // figure out if the element has any non-canonical owners
        $ownerIsCanonical = false;

        do {
            $owner = null;
            try {
                $owner = $element instanceof NestedElementInterface ? $element->getPrimaryOwner() : null;
            } catch (RuntimeException) {
            }
            if (! $owner) {
                break;
            }
            $ownerIsCanonical = $owner->getIsCanonical();
            if (! $ownerIsCanonical) {
                break;
            }
            $element = $owner;
        } while (true);

        return $ownerIsCanonical;
    }

    private function componentCheckboxHtml(string $labelId): string
    {
        return Html::tag('div', attributes: [
            'class' => 'checkbox',
            'title' => t('Select'),
            'role' => 'checkbox',
            'tabindex' => '0',
            'aria' => [
                'checked' => 'false',
                'labelledby' => $labelId,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $attributes
     */
    private function elementLabelHtml(ElementInterface $element, array $config, array $attributes, callable $uiLabel): string
    {
        $content = implode('', array_map(
            fn (string $segment) => Html::tag('span', Html::encode($segment), ['class' => 'segment']),
            $element->getUiLabelPath()
        )).
            $uiLabel();

        // show the draft name?
        if (($config['showDraftName'] ?? true) && $element->getIsDraft() && ! $element->isProvisionalDraft && ! $element->getIsUnpublishedDraft()) {
            /** @var ElementInterface $element */
            $content .= Html::tag(
                'span',
                $element->draftName ? Html::encode($element->draftName) : t('Draft'),
                ['class' => 'context-label'],
            );
        }

        // the inner span is needed for `text-overflow: ellipsis` (e.g. within breadcrumbs)
        if ($content !== '') {
            if (
                ($config['hyperlink'] ?? false) &&
                ! $element->trashed &&
                $config['context'] !== 'modal' &&
                ($url = $attributes['data']['cp-url'] ?? null)
            ) {
                $returnUrl = $config['returnUrl'] ?? null;
                if ($returnUrl) {
                    if (str_contains((string) $returnUrl, '{')) {
                        $returnUrl = Crypt::encrypt($returnUrl);
                    }

                    $url = Url::urlWithParams($url, [
                        'returnUrl' => $returnUrl,
                    ]);
                }

                $content = Html::tag('a', Html::tag('span', $content), [
                    'class' => ['label-link'],
                    'href' => $url,
                ]);
            } else {
                $content = Html::tag('span', $content, ['class' => 'label-link']);
            }
        }

        if ($config['context'] === 'field' && $element->errors()->isNotEmpty()) {
            $content .= Html::tag('span', '', [
                'data' => ['icon' => 'triangle-exclamation'],
                'aria' => ['label' => t('Error')],
                'role' => 'img',
            ]);
        }

        if ($content === '') {
            return '';
        }

        return Html::tag('craft-truncate', $content, [
            'id' => sprintf('%s-label', $config['id']),
            'class' => 'label',
        ]);
    }

    /**
     * Renders an `Actionable` component's action menu.
     *
     * The whole thing runs inside one `InputNamespace::namespaceInputs()`
     * closure: items are collected *and* rendered under the same namespace, so
     * that any JS an item registers against its own `id` (via
     * `HtmlStack::jsWithVars()` + `InputNamespace::namespaceId()`) still finds
     * the element. `ActionMenu` deliberately doesn't namespace on its own, so
     * IDs aren't doubled up.
     *
     * @param  list<array<string, mixed>>  $extraItems
     */
    private function componentActionMenu(Actionable $component, bool $withEdit = true, array $extraItems = []): string
    {
        return InputNamespace::namespaceInputs(
            function () use ($component, $withEdit, $extraItems): string {
                $actionMenuItems = array_filter(
                    $component->getActionMenuItems(),
                    fn (array $item) => $item['showInChips'] ?? ! ($item['destructive'] ?? false)
                );

                foreach ($actionMenuItems as $i => &$item) {
                    if (str_starts_with($item['id'] ?? '', 'action-edit-')) {
                        if (! $withEdit) {
                            unset($actionMenuItems[$i]);
                        } else {
                            $item['attributes']['data']['edit-action'] = true;
                        }
                    } elseif (str_starts_with($item['id'] ?? '', 'action-copy-')) {
                        $item['attributes']['data']['copy-action'] = true;
                    }
                }
                unset($item);

                return ActionMenu::make()
                    ->menuItems([...$actionMenuItems, ...$extraItems])
                    ->toHtml();
            },
            sprintf('action-menu-%s', mt_rand()),
        );
    }

    private function contextIsAdministrative(string $context): bool
    {
        return in_array($context, ['index', 'embedded-index', 'field']);
    }
}
