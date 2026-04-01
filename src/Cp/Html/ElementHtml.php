<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use Craft;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
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
use CraftCms\Cms\Cp\Events\DefineElementCardHtml;
use CraftCms\Cms\Cp\Events\DefineElementChipHtml;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Auth;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

#[Singleton]
readonly class ElementHtml
{
    public const string CHIP_SIZE_SMALL = 'small';

    public const string CHIP_SIZE_MEDIUM = 'medium';

    public const string CHIP_SIZE_LARGE = 'large';

    public function __construct(
        private StatusHtml $statusHtml,
        private MenuHtml $menuHtml,
        private ContentHtml $contentHtml,
    ) {}

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
                'cp-colorable',
                'cp-colorable--'.$color?->value ?? 'white',
                ...Html::explodeClass($config['class']),
            ],
            'data' => array_filter([
                'type' => $component::class,
                'id' => $component->getId(),
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

        $prefixHtml = '';
        if ($config['showThumb'] || $config['selectable'] || $config['showStatus']) {
            $prefixHtml = Html::beginTag('div', ['slot' => 'prefix']);
        }

        if ($config['showThumb']) {
            if ($component instanceof Thumbable) {
                $thumbSize = $config['size'] === self::CHIP_SIZE_SMALL ? 30 : 120;
                $prefixHtml .= $component->getThumbHtml($thumbSize) ?? '';
            } else {
                /** @var Chippable&Iconic $component */
                $icon = $component->getIcon();
                if ($icon || $icon === '0') {
                    $prefixHtml .= Html::tag('craft-icon', '', [
                        'name' => $icon,
                    ]);
                }
            }
        }

        if ($config['selectable']) {
            $prefixHtml .= $this->componentCheckboxHtml(sprintf('%s-label', $config['id']));
        }

        if ($config['showStatus']) {
            /** @var Chippable&Statusable $component */
            $prefixHtml .= $this->statusHtml->componentStatusIndicatorHtml($component) ?? '';
        }

        $prefixHtml .= Html::endTag('div');
        $html .= $prefixHtml;

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
                    $labelHtml .= Html::tag('span',
                        $this->contentHtml->parseMarkdown(Html::encode($description)),
                        ['class' => 'info']);
                }
            }

            $labelHtml = Html::tag('div', $labelHtml, ['class' => 'flex gap-1 items-center']);

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
                            $color = Color::tryFrom($indicator['iconColor']);

                            return Html::tag('craft-icon', '', [
                                'name' => $indicator['icon'],
                                'style' => $color ? ['color' => $color->cssVar(600)] : null,
                                'label' => $indicator['label'],
                            ]);
                        }, $indicators)).
                        Html::endTag('div');
                }
            }
            $html .= Html::tag('div', $labelHtml, [
                'id' => sprintf('%s-label', $config['id']),
                'class' => 'grid gap-1 justify-items-start',
            ]);
        }

        $html .= Html::beginTag('div', ['slot' => 'suffix']);
        if ($config['showActionMenu']) {
            /** @var Chippable&Actionable $component */
            $html .= $this->componentActionMenu($component);
        }
        if ($config['sortable']) {
            $html .= FormFields::buttonHtml([
                'class' => ['chromeless', 'small', 'move-btn'],
                'icon' => 'move',
                'attributes' => [
                    'title' => t('Reorder'),
                    'aria' => [
                        'label' => t('Reorder'),
                    ],
                    'role' => 'none',
                    'tabindex' => '-1',
                ],
            ]);
        }
        $html .= Html::endTag('div'); // slot=suffix

        if ($config['inputName'] !== null) {
            $inputValue = $config['inputValue'] ?? $component->getId();
            $html .= Html::hiddenInput($config['inputName'], (string) $inputValue);
        } // .element

        $html .= Html::endTag('craft-chip');

        return $html;
    }

    public function elementChipHtml(ElementInterface $element, array $config = []): string
    {
        $config += [
            'attributes' => [],
            'autoReload' => true,
            'context' => 'index',
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

        event($event = new DefineElementChipHtml($element, $config['context'], $html));

        return $event->html;
    }

    public function elementCardHtml(ElementInterface $element, array $config = []): string
    {
        $config += [
            'attributes' => [],
            'autoReload' => true,
            'context' => 'index',
            'hyperlink' => false,
            'id' => sprintf('card-%s', mt_rand()),
            'inputName' => null,
            'selectable' => false,
            'showActionMenu' => false,
            'showEditButton' => true,
            'sortable' => false,
        ];

        $showEditButton = $config['showEditButton'] && Craft::$app->getElements()->canView($element);

        if ($showEditButton) {
            $editId = sprintf('action-edit-%s', mt_rand());
            HtmlStack::jsWithVars(fn ($id, $elementType, $settings, $cpEditUrl) => <<<JS
$('#' + $id).on('activate', (ev) => {
  if ($cpEditUrl && Garnish.isCtrlKeyPressed(ev.originalEvent)) {
    window.open($cpEditUrl)
  } else {
    // focus on the button so that when the slideout is closed, it's returned to the button
    $(ev.currentTarget).focus();

    const settings = $settings;
    // if settings have draftId but the replaced card doesn't have the data-draft-id attribute anymore,
    // remove the draftId from the settings before creating element editor, so the correct element can be retrieved
    if (settings.draftId && !Garnish.hasAttr($(ev.currentTarget).parents('.card'), 'data-draft-id')) {
      delete settings.draftId;
    }
    Craft.createElementEditor($elementType, settings)
  }
});
JS, [
                InputNamespace::namespaceId($editId),
                $element::class,
                [
                    'elementId' => $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id,
                    'draftId' => $element->isProvisionalDraft ? null : $element->draftId,
                    'revisionId' => $element->revisionId,
                    'siteId' => $element->siteId,
                    'ownerId' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
                ],
                'cpEditUrl' => $element->getCpEditUrl(),
            ]);
        }

        if ($element->getIsRevision()) {
            $config['showActionMenu'] = false;
            $config['selectable'] = false;
        }

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

        $attributes = Arr::merge(
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
                    'settings' => $config['autoReload'] ? [
                        'hyperlink' => $config['hyperlink'],
                        'selectable' => $config['selectable'],
                        'context' => $config['context'],
                        'id' => InputNamespace::namespaceId($config['id']),
                        'ui' => 'card',
                    ] : false,
                ]),
            ],
            $config['attributes'],
        );

        $headingContent = $this->elementLabelHtml($element, $config, $attributes, fn () => Html::encode($element->getUiLabel()));
        $bodyContent = $element->getCardBodyHtml() ?? '';

        $labels = array_filter([
            $element->showStatusIndicator() ? $this->statusHtml->componentStatusLabelHtml($element) : null,
            $element->isProvisionalDraft || $element->hasProvisionalChanges ? $this->statusHtml->editedStatusLabelHtml() : null,
        ]);

        if (! empty($labels)) {
            $bodyContent .= Html::ul()
                ->items(...array_map(fn ($label) => Html::li($label)->encode(false), $labels))
                ->class('flex gap-xs')
                ->render();
        }

        // is this a nested element that will end up replacing its canonical
        // counterpart when the owner is saved?
        if (
            $element instanceof NestedElementInterface &&
            $element->getOwnerId() !== null &&
            $element->getOwnerId() === $element->getPrimaryOwnerId() &&
            ! $element->getIsDraft() &&
            ! $element->getIsRevision() &&
            $element->getOwner()->getIsDerivative()
        ) {
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

            $status = Html::beginTag('div', [
                'class' => ['status-badge', AttributeStatus::Modified->value],
                'title' => $statusLabel,
            ]).
                Html::tag('span', $statusLabel, [
                    'class' => 'visually-hidden',
                ]).
                Html::endTag('div');
        }

        $icon = $element instanceof Iconic ? $element->getIcon() : null;
        $title = $element->getCardTitle();

        $html = Html::beginTag('div', $attributes).
            Html::beginTag('div', ['class' => 'card-titlebar']).
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap', 'flex-gap-s'],
            ]).
            ($icon ? Html::tag('div', Icons::svg($icon), [
                'class' => array_filter([
                    'cp-icon',
                    'small',
                    $element instanceof Colorable ? $element->getColor()?->value : null,
                ]),
                'aria' => ['hidden' => true],
            ]) : '').
            ($title ? Html::tag('div', Html::encode($title), ['class' => 'card-titlebar-label']) : '').
            Html::endTag('div'). // .flex
            ($status ?? '').
            Html::beginTag('div', ['class' => 'card-actions-container']).
            Html::beginTag('div', ['class' => 'card-actions']).
            ($config['selectable'] ? $this->componentCheckboxHtml(sprintf('%s-label', $config['id'])) : '').
            ($showEditButton ? FormFields::buttonHtml([
                'class' => ['chromeless', 'small', 'edit-btn'],
                'icon' => 'edit',
                'attributes' => [
                    'id' => $editId,
                    'title' => mb_ucfirst(t('Edit {type}', [
                        'type' => $element::lowerDisplayName(),
                    ])),
                    'aria' => [
                        'label' => mb_ucfirst(t('Edit {type}', [
                            'type' => $element::lowerDisplayName(),
                        ])),
                    ],
                ],
            ]) : '').
            ($config['showActionMenu'] ? $this->componentActionMenu($element, ! $showEditButton) : '').
            ($config['sortable'] ? FormFields::buttonHtml([
                'class' => ['chromeless', 'small', 'move-btn'],
                'icon' => 'move',
                'attributes' => [
                    'title' => t('Reorder'),
                    'aria' => [
                        'label' => t('Reorder'),
                    ],
                    'role' => 'none',
                    'tabindex' => '-1',
                ],
            ]) : '').
            Html::endTag('div'). // .card-actions
            Html::endTag('div'). // .card-actions-container
            Html::endTag('div'). // .card-titlebar
            Html::beginTag('div', ['class' => 'card-main']);

        $contentHtml =
            Html::beginTag('div', ['class' => 'card-content']).
            ($headingContent !== '' ? Html::tag('div', $headingContent, ['class' => 'card-heading']) : '').
            ($bodyContent !== '' ? Html::tag('div', $bodyContent, ['class' => 'card-body']) : '').
            Html::endTag('div'); // .card-content

        $thumbHtml = $element->getThumbHtml(120);

        if ($thumbAlignment === 'start') {
            $html .= $thumbHtml.$contentHtml;
        } else {
            $html .= $contentHtml.$thumbHtml;
        }

        $html .=
            Html::endTag('div'); // .card-main

        if ($config['context'] === 'field' && $config['inputName'] !== null) {
            $inputValue = $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id;
            $html .= Html::hiddenInput($config['inputName'], (string) $inputValue);
        }

        $html .= Html::endTag('div'); // .card

        event($event = new DefineElementCardHtml($element, $config['context'], $html));

        return $event->html;
    }

    private function baseElementAttributes(ElementInterface $element, array $config): array
    {
        $elementsService = Craft::$app->getElements();
        $user = Auth::user();
        $editable = $user && $elementsService->canView($element, $user);

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
                    'savable' => $editable && $this->contextIsAdministrative($config['context']) && $elementsService->canSave($element, $user),
                    'duplicatable' => $editable && $this->contextIsAdministrative($config['context']) && $elementsService->canDuplicate($element, $user),
                    'duplicatable-as-draft' => $editable && $this->contextIsAdministrative($config['context']) && $elementsService->canDuplicateAsDraft($element, $user),
                    'copyable' => $editable && $this->contextIsAdministrative($config['context']) && $elementsService->canCopy($element, $user),
                    'deletable' => $editable && $this->contextIsAdministrative($config['context']) && $elementsService->canDelete($element, $user),
                    'deletable-for-site' => (
                        $editable &&
                        $this->contextIsAdministrative($config['context']) &&
                        ElementHelper::isMultiSite($element) &&
                        $elementsService->canDeleteForSite($element, $user)
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
            } catch (InvalidConfigException) {
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
            $content .= Html::tag('span', $element->draftName ?: t('Draft'), [
                'class' => 'context-label',
            ]);
        }

        // the inner span is needed for `text-overflow: ellipsis` (e.g. within breadcrumbs)
        if ($content !== '') {
            if (
                ($config['hyperlink'] ?? false) &&
                ! $element->trashed &&
                $config['context'] !== 'modal' &&
                ($url = $attributes['data']['cp-url'] ?? null)
            ) {
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

        return Html::tag('craft-element-label', $content, [
            'id' => sprintf('%s-label', $config['id']),
            'class' => 'label',
        ]);
    }

    private function componentActionMenu(Actionable $component, bool $withEdit = true): string
    {
        return InputNamespace::namespaceInputs(
            function () use ($component, $withEdit): string {
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

                return $this->menuHtml->disclosureMenu($actionMenuItems, [
                    'buttonHtml' => Html::tag('craft-icon', '', ['name' => 'ellipsis', 'label' => t('Actions')]),
                    'buttonAttributes' => [
                        'icon' => true,
                        'size' => 'small',
                        'appearance' => 'plain',
                    ],
                    'omitIfEmpty' => false,
                ]);
            },
            sprintf('action-menu-%s', mt_rand()),
        );
    }

    private function contextIsAdministrative(string $context): bool
    {
        return in_array($context, ['index', 'embedded-index', 'field']);
    }
}
