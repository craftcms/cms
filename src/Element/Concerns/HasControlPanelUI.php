<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\ElementActionMenuItemsResolving;
use CraftCms\Cms\Element\Events\ElementAdditionalButtonsResolving;
use CraftCms\Cms\Element\Events\ElementAltActionsResolving;
use CraftCms\Cms\Element\Events\ElementAttributeHtmlResolving;
use CraftCms\Cms\Element\Events\ElementHtmlAttributesResolving;
use CraftCms\Cms\Element\Events\ElementInlineAttributeInputHtmlResolving;
use CraftCms\Cms\Element\Events\ElementMetadataResolving;
use CraftCms\Cms\Element\Events\ElementMetaFieldsHtmlResolving;
use CraftCms\Cms\Element\Events\ElementSidebarHtmlResolving;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\ElementEditViewModel;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Formatter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Stringable;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * Provides Control Panel UI functionality for elements.
 *
 * This trait handles methods related to rendering elements in the Control Panel,
 * including edit URLs, sidebar HTML, metadata, action menus, and attribute rendering.
 *
 * @property string|null $ref The reference string to this element
 * @property array<string,mixed> $htmlAttributes Any attributes that should be included in the element's DOM representation in the control panel
 *
 * @internal
 */
trait HasControlPanelUI
{
    /**
     * @see getUiLabel()
     * @see setUiLabel()
     */
    private ?string $_uiLabel = null;

    /**
     * @var string[]
     *
     * @see getUiLabelPath()
     * @see setUiLabelPath()
     */
    private array $_uiLabelPath = [];

    /**
     * The view model that builds this element type's edit screen payload, or
     * `null` for a type whose editor hasn't been ported off the legacy screen.
     *
     * The edit controllers construct it directly — they know their own element
     * type. This is for the shared `elements/*` actions, which don't: autosave
     * rebuilds the screen payload so the client can adopt the state the save
     * left the element in, and has only the element to go on.
     *
     * @return class-string<ElementEditViewModel>|null
     */
    public static function editViewModelClass(): ?string
    {
        return null;
    }

    /**
     * Performs any action after the element's editor is fully ready.
     */
    public function prepareEditScreen(Response|CpScreenResponse $response, string $containerId): void {}

    public function getAdditionalButtons(): string|Stringable
    {
        event($event = new ElementAdditionalButtonsResolving($this));

        return $event->html;
    }

    public function getAltActions(): array
    {
        $isUnpublishedDraft = $this->getIsUnpublishedDraft();
        $canSaveCanonical = Gate::check('saveCanonical', $this);

        $returnUrl = request()->query('returnUrl');
        $redirectParams = array_filter([
            'returnUrl' => $returnUrl,
        ]);

        $altActions = [
            [
                'label' => $isUnpublishedDraft && $canSaveCanonical
                    ? t('Create and continue editing')
                    : t('Save and continue editing'),
                'redirect' => '{cpEditUrl}',
                'params' => array_filter([
                    'redirectParams' => ! empty($redirectParams) ? Json::encode($redirectParams) : null,
                ]),
                'shortcut' => true,
                'retainScroll' => true,
                'eventData' => ['autosave' => false],
            ],
        ];

        if ($this->getIsCanonical() || $this->isProvisionalDraft) {
            $newElement = $this->createAnother();
            if ($newElement && Gate::check('save', $newElement)) {
                $altActions[] = [
                    'label' => $isUnpublishedDraft && $canSaveCanonical
                        ? t('Create and add another')
                        : t('Save and add another'),
                    'shortcut' => true,
                    'shift' => true,
                    'eventData' => ['autosave' => false],
                    'params' => [
                        'addAnother' => 1,
                        'returnUrl' => $returnUrl,
                    ],
                ];
            }

            if ($canSaveCanonical && $isUnpublishedDraft) {
                $altActions[] = [
                    'label' => mb_ucfirst(t('Save {type}', [
                        'type' => t('draft'),
                    ])),
                    'action' => 'elements/save-draft',
                    'redirect' => sprintf('%s#', ElementHelper::postEditUrl($this)),
                    'eventData' => ['autosave' => false],
                ];
            }

            if (! $this->getIsRevision() && Gate::check('duplicateAsDraft', $this)) {
                $altActions[] = [
                    'label' => t('Save as a new {type}', [
                        'type' => static::lowerDisplayName(),
                    ]),
                    'action' => 'elements/duplicate',
                    'redirect' => '{cpEditUrl}',
                    'params' => [
                        'asUnpublishedDraft' => true,
                        'deleteProvisionalDraft' => true,
                        'redirectParams' => ! empty($redirectParams) ? Json::encode($redirectParams) : null,
                    ],
                ];
            }
        }

        if ($this->getIsDraft() && ! $this->getIsUnpublishedDraft() && ! $this->isProvisionalDraft) {
            $altActions[] = [
                'label' => t('Save as a new {type}', [
                    'type' => t('draft'),
                ]),
                'action' => 'elements/duplicate',
            ];
        }

        event($event = new ElementAltActionsResolving($this, $altActions));

        return $event->altActions;
    }

    public function getActionMenuItems(): array
    {
        $items = [
            ...$this->safeActionMenuItems(),
            ...array_map(fn (array $item) => $item + ['destructive' => true], $this->destructiveActionMenuItems()),
        ];

        event($event = new ElementActionMenuItemsResolving($this, $items));

        return $event->items;
    }

    /**
     * Returns action menu items for the element's edit screens.
     *
     * See [[\CraftCms\Cms\Cp\Html\MenuHtml::disclosureMenu()]] for documentation on supported item properties.
     *
     * @see getActionMenuItems()
     * @see MenuHtml::disclosureMenu()
     */
    /** @return array<array-key,array<string,mixed>> */
    protected function safeActionMenuItems(): array
    {
        $items = [];

        // Validate
        if (
            ! $this->getIsRevision() &&
            ! request()->headers->has('X-Craft-Container-Id') &&
            app(ElementRequest::class)->element === $this
        ) {
            $validateId = sprintf('action-validate-%s', mt_rand());
            $items[] = [
                'id' => $validateId,
                'icon' => 'circle-check',
                'label' => t('Validate {type}', [
                    'type' => static::lowerDisplayName(),
                ]),
            ];

            HtmlStack::jsWithVars(fn ($id) => <<<JS
(() => {
  const btn = $('#' + $id);
  btn.on('activate', () => {
    const elementId = btn.closest('.menu').data('disclosureMenu').\$trigger
      .closest('form').data('elementEditor').settings.elementId;
    const form = Craft.createForm()
      .addClass('hidden')
      .append(Craft.getCsrfInput())
      .appendTo(Garnish.\$bod);

    Craft.submitForm(form, {
      action: 'elements/validate',
      retainScroll: true,
      params: {
        elementId,
      },
    });
  });
})();
JS, [
                InputNamespace::namespaceId($validateId),
            ]);
        }

        // View
        if ($url = $this->getUrl()) {
            $viewId = sprintf('action-view-%s', mt_rand());
            $items[] = [
                'id' => $viewId,
                'icon' => 'share',
                'label' => t('View in a new tab'),
                'url' => $url,
                'attributes' => [
                    'target' => '_blank',
                    'data' => [
                        'view' => true,
                    ],
                ],
            ];
        }

        if (Gate::check('view', $this)) {
            // Edit
            $editId = sprintf('action-edit-%s', mt_rand());
            $items[] = [
                'id' => $editId,
                'icon' => 'edit',
                'label' => mb_ucfirst(t('Edit {type}', [
                    'type' => static::lowerDisplayName(),
                ])),
            ];

            HtmlStack::jsWithVars(fn ($id, $elementType, $settings) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.createElementEditor($elementType, $settings)
});
JS, [
                InputNamespace::namespaceId($editId),
                static::class,
                [
                    'elementId' => $this->isProvisionalDraft ? $this->getCanonicalId() : $this->id,
                    'draftId' => $this->isProvisionalDraft ? null : $this->draftId,
                    'revisionId' => $this->revisionId,
                    'siteId' => $this->siteId,
                    'ownerId' => $this instanceof NestedElementInterface ? $this->getOwnerId() : null,
                ],
            ]);

            // Copy
            if (! $this->getIsRevision() && Gate::check('copy', $this)) {
                $copyId = sprintf('action-copy-%s', mt_rand());
                $items[] = [
                    'id' => $copyId,
                    'color' => Color::Fuchsia,
                    'icon' => self::actionMenuIcon('clone-dashed'),
                    'label' => mb_ucfirst(t('Copy {type}', [
                        'type' => static::lowerDisplayName(),
                    ])),
                ];

                HtmlStack::jsWithVars(fn ($id, $elementInfo) => <<<JS
(() => {
  $('#' + $id).on('activate', () => {
    Craft.cp.copyElements([$elementInfo])
  });
})();
JS, [
                    InputNamespace::namespaceId($copyId),
                    [
                        'type' => static::class,
                        'id' => $this->isProvisionalDraft ? $this->getCanonicalId() : $this->id,
                        'draftId' => $this->isProvisionalDraft ? null : $this->draftId,
                        'revisionId' => $this->revisionId,
                        'fieldId' => $this instanceof NestedElementInterface ? $this->getField()?->id : null,
                        'ownerId' => $this instanceof NestedElementInterface ? $this->getOwnerId() : null,
                        'siteId' => $this->siteId,
                    ],
                ]);
            }
        }

        return $items;
    }

    /**
     * The element's action menu as behavior descriptors, for renderers that
     * dispatch actions themselves rather than executing registered JavaScript.
     *
     * This is the counterpart to {@see getActionMenuItems()}: same actions, but
     * each item names *what* it does (`behavior`) instead of pairing markup with
     * an inline handler. The Inertia editor renders these; the legacy editor and
     * slideouts keep using the HTML pairing.
     *
     * Element types extend this the way they extend the HTML items.
     *
     * @return list<array<string, mixed>>
     */
    public function actionMenuDescriptors(): array
    {
        $items = [];
        $isDraft = $this->getIsDraft();
        $isUnpublishedDraft = $this->getIsUnpublishedDraft();
        $isCurrent = $this->getIsCanonical() || $this->isProvisionalDraft;
        $canonical = $this->getCanonical(true);
        $redirectUrl = ElementHelper::postEditUrl($this);
        $sourceId = $this->isProvisionalDraft ? $this->getCanonicalId() : $this->id;

        if (! $this->getIsRevision()) {
            $items[] = [
                'label' => t('Validate {type}', ['type' => static::lowerDisplayName()]),
                'icon' => 'circle-check',
                'behavior' => [
                    'type' => 'submit',
                    'actionUrl' => Url::actionUrl('elements/validate'),
                    'params' => ['elementId' => $this->id],
                ],
            ];
        }

        if ($url = $this->getUrl()) {
            $items[] = [
                'label' => t('View in a new tab'),
                'icon' => 'share',
                'behavior' => ['type' => 'link', 'href' => $url, 'newTab' => true],
            ];
        }

        if (! $this->getIsRevision() && Gate::check('copy', $this)) {
            $items[] = [
                'label' => mb_ucfirst(t('Copy {type}', ['type' => static::lowerDisplayName()])),
                'icon' => self::actionMenuIcon('clone-dashed'),
                'color' => Color::Fuchsia->value,
                'behavior' => [
                    'type' => 'copy',
                    'elements' => [[
                        'type' => static::class,
                        'id' => $sourceId,
                        'draftId' => $this->isProvisionalDraft ? null : $this->draftId,
                        'revisionId' => $this->revisionId,
                        'siteId' => $this->siteId,
                    ]],
                ],
            ];
        }

        $items = [...$items, ...$this->extraActionMenuDescriptors()];

        // Destructive items sort last and are flagged so the menu can style them.
        $canDeleteForSite = (
            ElementHelper::isMultiSite($this) &&
            $isCurrent &&
            Gate::check('deleteForSite', $canonical) &&
            Gate::check('deleteForSite', $this)
        );

        if ($isCurrent && $canDeleteForSite) {
            $type = $isUnpublishedDraft ? t('draft') : static::lowerDisplayName();

            $items[] = [
                'label' => mb_ucfirst(t('Delete {type} for this site', ['type' => $type])),
                'icon' => 'remove',
                'destructive' => true,
                'behavior' => [
                    'type' => 'submit',
                    'actionUrl' => Url::actionUrl('elements/delete-for-site'),
                    'params' => [
                        'elementId' => $this->getCanonicalId(),
                        'siteId' => $this->siteId,
                    ],
                    'redirect' => Crypt::encrypt("$redirectUrl#"),
                    'confirm' => t('Are you sure you want to delete the {type} for this site?', ['type' => $type]),
                ],
            ];
        }

        if ($isCurrent && Gate::check('delete', $canonical)) {
            $type = $isUnpublishedDraft ? t('draft') : static::lowerDisplayName();

            $items[] = [
                'label' => mb_ucfirst(t('Delete {type}', ['type' => $type])),
                'icon' => 'trash',
                'destructive' => true,
                // Deletion runs through the deletion-blockers flow rather than a
                // plain confirm, so relations and references can be reassigned.
                'behavior' => [
                    'type' => 'delete',
                    'elementType' => static::class,
                    'elementId' => $this->id,
                    'siteId' => $this->siteId,
                    'confirm' => t('Are you sure you want to delete this {type}?', [
                        'type' => $isDraft ? t('draft') : static::lowerDisplayName(),
                    ]),
                    'redirect' => Url::cpUrl($redirectUrl),
                ],
            ];
        }

        return $items;
    }

    /**
     * Qualifies an icon with its family when it isn't in the default set.
     *
     * The action menu's icon renderer resolves a bare name against the default
     * family, so custom icons have to arrive prefixed or they 404.
     */
    private static function actionMenuIcon(string $icon): string
    {
        $family = Icons::resolveIconFamily($icon);

        return $family === 'solid' ? $icon : "$family/$icon";
    }

    /**
     * Element-type additions to {@see actionMenuDescriptors()}, inserted before
     * the destructive items.
     *
     * @return list<array<string, mixed>>
     */
    protected function extraActionMenuDescriptors(): array
    {
        return [];
    }

    /**
     * Returns destructive action menu items for the element's edit screens.
     *
     * See [[\CraftCms\Cms\Cp\Html\MenuHtml::disclosureMenu()]] for documentation on supported item properties.
     *
     * `'destructive' => true` will be automatically added to all returned items.
     *
     * @see getActionMenuItems()
     * @see MenuHtml::disclosureMenu()
     */
    /** @return array<array-key,array<string,mixed>> */
    protected function destructiveActionMenuItems(): array
    {
        $items = [];

        $isCanonical = $this->getIsCanonical();
        $isDraft = $this->getIsDraft();
        $isUnpublishedDraft = $this->getIsUnpublishedDraft();
        $isCurrent = $isCanonical || $this->isProvisionalDraft;
        $canonical = $this->getCanonical(true);
        $redirectUrl = ElementHelper::postEditUrl($this);

        $isNewSite = match (true) {
            $isUnpublishedDraft => true,
            $isDraft => ! static::find()
                ->id($this->getCanonicalId())
                ->siteId($this->siteId)
                ->status(null)
                ->exists(),
            default => false,
        };

        $canDeleteDraft = $isDraft && ! $this->isProvisionalDraft && Gate::check('delete', $this);
        $canDeleteCanonical = Gate::check('delete', $canonical);
        $canDeleteCanonicalForSite = Gate::check('deleteForSite', $canonical);
        $canDeleteForSite = (
            ElementHelper::isMultiSite($this) &&
            (($isCurrent && $canDeleteCanonicalForSite) || ($canDeleteDraft && $isNewSite)) &&
            Gate::check('deleteForSite', $this)
        );

        if ($isCurrent) {
            if ($canDeleteForSite) {
                $items[] = [
                    'icon' => 'remove',
                    'label' => mb_ucfirst(t('Delete {type} for this site', [
                        'type' => $isUnpublishedDraft ? t('draft') : static::lowerDisplayName(),
                    ])),
                    'action' => 'elements/delete-for-site',
                    'params' => [
                        'elementId' => $this->getCanonicalId(),
                        'siteId' => $this->siteId,
                    ],
                    'redirect' => "$redirectUrl#",
                    'confirm' => t('Are you sure you want to delete the {type} for this site?', [
                        'type' => $isUnpublishedDraft ? t('draft') : static::lowerDisplayName(),
                    ]),
                    'destructive' => true,
                ];
            }

            if ($canDeleteCanonical) {
                $deleteId = sprintf('action-delete-%s', mt_rand());

                $items[] = [
                    'id' => $deleteId,
                    'icon' => 'trash',
                    'label' => mb_ucfirst(t('Delete {type}', [
                        'type' => $isUnpublishedDraft ? t('draft') : static::lowerDisplayName(),
                    ])),
                ];

                HtmlStack::jsWithVars(fn (
                    $id,
                    $elementType,
                    $elementId,
                    $siteId,
                    $ownerId,
                    $confirmationMessage,
                    $redirect,
                ) => <<<JS
$('#' + $id).on('activate', async () => {
  new Craft.ElementDeletionManager($elementType, [$elementId], {
    siteId: $siteId,
    ownerId: $ownerId,
    confirmationMessage: $confirmationMessage,
    onSuccess: () => {
      document.location.href = $redirect;
    },
  })
});
JS,
                    [
                        InputNamespace::namespaceId($deleteId),
                        static::class,
                        $this->id,
                        $this->siteId,
                        $this instanceof NestedElementInterface ? $this->getOwnerId() : null,
                        t('Are you sure you want to delete this {type}?', [
                            'type' => $isDraft ? t('draft') : static::lowerDisplayName(),
                        ]),
                        "$redirectUrl#",
                    ]);
            }
        } elseif ($isDraft && $canDeleteDraft) {
            if ($canDeleteForSite) {
                $items[] = [
                    'icon' => 'remove',
                    'label' => mb_ucfirst(t('Delete {type} for this site', [
                        'type' => t('draft'),
                    ])),
                    'action' => 'elements/delete-for-site',
                    'params' => [
                        'elementId' => $this->getCanonicalId(),
                        'siteId' => $this->siteId,
                        'draftId' => $this->draftId,
                    ],
                    'redirect' => "$redirectUrl#",
                    'confirm' => t('Are you sure you want to delete the {type} for this site?', [
                        'type' => static::lowerDisplayName(),
                    ]),
                    'destructive' => true,
                ];
            }

            $items[] = [
                'icon' => 'trash',
                'label' => mb_ucfirst(t('Delete {type}', [
                    'type' => t('draft'),
                ])),
                'action' => 'elements/delete-draft',
                'params' => [
                    'elementId' => $this->getCanonicalId(),
                    'siteId' => $this->siteId,
                    'draftId' => $this->draftId,
                ],
                'redirect' => $canonical->getCpEditUrl(),
                'confirm' => t('Are you sure you want to delete this {type}?', [
                    'type' => t('draft'),
                ]),
                'destructive' => true,
            ];
        }

        return $items;
    }

    /** @return array<string,mixed> */
    public function getHtmlAttributes(string $context): array
    {
        $htmlAttributes = Arr::merge($this->htmlAttributes($context), [
            'data' => [
                'disallow-status' => ! $this->showStatusField(),
            ],
        ]);

        event($event = new ElementHtmlAttributesResolving($this, $context, $htmlAttributes));

        return $event->htmlAttributes;
    }

    /**
     * Returns any attributes that should be included in the element's chips and cards.
     *
     * @param  string  $context  The context that the element is being rendered in ('index', 'modal', 'field', or 'settings'.)
     *
     * @see getHtmlAttributes()
     */
    /** @return array<string,mixed> */
    protected function htmlAttributes(string $context): array
    {
        return [];
    }

    public function getAttributeHtml(string $attribute): string|Stringable
    {
        event($event = new ElementAttributeHtmlResolving($this, $attribute));

        return $event->html ?? $this->attributeHtml($attribute);
    }

    public function getInlineAttributeInputHtml(string $attribute): string|Stringable
    {
        event($event = new ElementInlineAttributeInputHtmlResolving($this, $attribute));

        return $event->html ?? $this->inlineAttributeInputHtml($attribute);
    }

    /**
     * Returns the HTML that should be shown for a given attribute in table and card views.
     *
     * For example, if your elements have an `email` attribute that you want to wrap in a `mailto:` link, your
     * `attributeHtml()` method could do this:
     *
     * ```php
     * return match ($attribute) {
     *     'email' => $this->email ? Html::mailto(Html::encode($this->email)) : '',
     *     default => parent::attributeHtml($attribute),
     * };
     * ```
     *
     * ::: warning
     * All untrusted text should be passed through [[Html::encode()]] to prevent XSS attacks.
     * :::
     *
     * By default, the following will be returned:
     *
     * - If the attribute name is `link` or `uri`, it will be linked to the front-end URL.
     * - If the attribute is a custom field handle, it will pass the responsibility off to the field type.
     * - If the attribute value is a [[DateTime]] object, the date will be formatted with a localized date format.
     * - For anything else, it will output the attribute value as a string.
     *
     * @param  string  $attribute  The attribute name.
     * @return string The HTML that should be shown for a given attribute in table and card views.
     *
     * @see getAttributeHtml()
     */
    protected function attributeHtml(string $attribute): string|Stringable
    {
        return app(ElementAttributeRenderer::class)->render($this, $attribute);
    }

    /**
     * Returns the HTML that should be shown for a given attribute's inline input.
     *
     * @param  string  $attribute  The attribute name.
     * @return string The HTML that should be shown for a given attribute's inline input.
     *
     * @see getInlineAttributeInputHtml()
     */
    protected function inlineAttributeInputHtml(string $attribute): string|Stringable
    {
        return app(ElementAttributeRenderer::class)->renderInlineInput($this, $attribute);
    }

    public function getSidebarHtml(bool $static): string|Stringable
    {
        $components = [];

        $metaFieldsHtml = trim($this->metaFieldsHtml($static));
        if ($metaFieldsHtml !== '') {
            $components[] = Html::tag('div', $metaFieldsHtml, ['class' => 'meta'])
                .Html::tag('h2', t('Metadata'), ['class' => 'visually-hidden']);
        }

        if (! $static && static::hasStatuses() && $this->showStatusField()) {
            $components[] = $this->statusFieldHtml();
        }

        if ($this->hasRevisions() && ! $this->getIsRevision()) {
            $components[] = $this->notesFieldHtml();
        }

        $html = implode("\n", $components);

        event($event = new ElementSidebarHtmlResolving($this, $static, $html));

        return $event->html;
    }

    public function sidebarForm(FormContext $context = new FormContext): ?Form
    {
        $static = $context->mode === ControlMode::ReadOnly || $context->mode === ControlMode::Disabled;

        $nodes = $this->metaFieldsNodes($static);

        if (! $static && static::hasStatuses() && $this->showStatusField()) {
            $nodes = [...$nodes, ...$this->statusNodes()];
        }

        if ($this->hasRevisions() && ! $this->getIsRevision()) {
            $nodes[] = Field::make(t('Notes about your changes'))
                ->control(
                    Textarea::make('notes')
                        ->rows(1)
                        ->value($this->getIsDraft() ? $this->draftNotes : $this->revisionNotes),
                );
        }

        return $nodes === [] ? null : Form::make($nodes);
    }

    /**
     * The status Node(s) for the sidebar Form.
     *
     * On a single-site install (or an element supported by one site) this is a
     * lone `enabled` switch. Otherwise it mirrors the legacy editor: a global
     * "Enabled for all sites" switch plus a per-site switch for every editable
     * site the element propagates to, the latter collapsed behind a group. The
     * global switch is indeterminate when the sites disagree, and the client
     * keeps the two in sync.
     *
     * @return list<Node>
     */
    private function statusNodes(): array
    {
        $siteIds = $this->editableStatusSiteIds();
        $additionalSiteIds = $this->additionalStatusSiteIds();

        // One editable site in total (or a non-localized element) keeps the
        // plain global switch the single-site editor has always shown.
        if (count($siteIds) + count($additionalSiteIds) < 2) {
            return [
                Field::make(t('Status'))
                    ->control(
                        Lightswitch::make('enabled')
                            ->value((bool) $this->enabled)
                            ->onLabel(t('Enabled'))
                            ->offLabel(t('Disabled')),
                    ),
            ];
        }

        // Sites the element hasn't propagated to yet are absent from the status
        // map; the legacy editor defaults those to enabled, so match it.
        $siteStatuses = ElementHelper::siteStatusesForElement($this, true);
        $statuses = [];

        foreach ($siteIds as $siteId) {
            $statuses[$siteId] = (bool) ($siteStatuses[$siteId] ?? true);
        }

        // Supported sites the element doesn't propagate to are offered here too,
        // switched off. The legacy editor hides these behind an "Add a site…"
        // select; turning one on has the same effect — the element is saved for
        // that site — without the dynamic field building.
        foreach ($additionalSiteIds as $siteId) {
            $statuses[$siteId] ??= false;
        }

        $values = array_values($statuses);
        $allEnabled = ! in_array(false, $values, true);
        $allDisabled = ! in_array(true, $values, true);

        $siteFields = [];

        foreach ($statuses as $siteId => $enabled) {
            $site = Sites::getSiteById($siteId);

            if ($site === null) {
                continue;
            }

            $siteFields[] = Field::make(t($site->getName(), category: 'site'))
                ->control(
                    Lightswitch::make(['enabledForSite', (string) $siteId])
                        ->value($enabled),
                );
        }

        return [
            Field::make(t('Enabled for all sites'))
                ->control(
                    Lightswitch::make('enabled')
                        ->value($allEnabled)
                        ->indeterminate(! $allEnabled && ! $allDisabled)
                        ->onLabel(t('Enabled'))
                        ->offLabel(t('Disabled')),
                ),
            Group::make('site-statuses', $siteFields)
                ->label(t('Update status for individual sites'))
                ->collapsible(),
        ];
    }

    /**
     * Supported sites the element does *not* propagate to, limited to editable
     * ones — the sites it could be added to.
     *
     * @return list<int>
     */
    private function additionalStatusSiteIds(): array
    {
        if (! static::isLocalized()) {
            return [];
        }

        return array_values(array_intersect(
            array_column(
                array_filter(
                    ElementHelper::supportedSitesForElement($this, true),
                    fn (array $site): bool => ! $site['propagate'],
                ),
                'siteId',
            ),
            Sites::getEditableSiteIds()->all(),
        ));
    }

    /**
     * The sites whose statuses the current user can edit from this screen —
     * the element's propagating supported sites, limited to editable ones.
     *
     * @return list<int>
     */
    private function editableStatusSiteIds(): array
    {
        if (! static::isLocalized()) {
            return [];
        }

        return array_values(array_intersect(
            array_column(
                array_filter(
                    ElementHelper::supportedSitesForElement($this, true),
                    fn (array $site): bool => $site['propagate'],
                ),
                'siteId',
            ),
            Sites::getEditableSiteIds()->all(),
        ));
    }

    /**
     * Returns the editor sidebar's element-type-specific meta fields as Form
     * Nodes. The Form-system counterpart to {@see metaFieldsHtml()}; element
     * types override this alongside their HTML implementation until the legacy
     * editor is retired.
     *
     * @return list<Node>
     */
    protected function metaFieldsNodes(bool $static): array
    {
        return [];
    }

    /**
     * Returns the element's validation errors keyed the way the editor's Forms
     * address them.
     *
     * A Form matches errors to Controls by path, so an attribute validated
     * under one name but posted under another — an asset's `newLocation` versus
     * its `newFilename` field — needs remapping here, or its messages never
     * reach the field that produced them.
     *
     * @return array<string, list<string>>
     */
    public function formErrors(): array
    {
        return $this->errors()->getMessages();
    }

    /**
     * Returns the HTML for any meta fields that should be shown within the editor sidebar.
     *
     * @param  bool  $static  Whether the fields should be static (non-interactive)
     */
    protected function metaFieldsHtml(bool $static): string|Stringable
    {
        event($event = new ElementMetaFieldsHtmlResolving($this, $static));

        return $event->html;
    }

    /**
     * Returns the HTML for the element's Slug field.
     *
     * @param  bool  $static  Whether the fields should be static (non-interactive)
     */
    protected function slugFieldHtml(bool $static): string|Stringable
    {
        $slug = ! ElementHelper::isTempSlug($this->slug) ? $this->slug : null;

        return FormFields::textFieldHtml([
            'status' => $this->getAttributeStatus('slug'),
            'label' => t('Slug'),
            'siteId' => $this->siteId,
            'translatable' => $this->getIsSlugTranslatable(),
            'translationDescription' => $this->getSlugTranslationDescription(),
            'id' => 'slug',
            'name' => 'slug',
            'autocorrect' => false,
            'autocapitalize' => false,
            'value' => $slug,
            'disabled' => $static,
            'errors' => array_merge($this->errors()->get('slug'), $this->errors()->get('uri')),
        ]);
    }

    /**
     * Returns whether the Status field should be shown for this element.
     *
     * If set to `false`, the element's status can't be updated via edit forms, the Set Status action, or `resave/*` commands.
     */
    protected function showStatusField(): bool
    {
        return true;
    }

    /**
     * Returns the status field HTML for the sidebar.
     */
    protected function statusFieldHtml(): string|Stringable
    {
        $supportedSites = ElementHelper::supportedSitesForElement($this, true);
        $allEditableSiteIds = Sites::getEditableSiteIds()->all();
        $propSites = array_values(array_filter($supportedSites, fn ($site) => $site['propagate']));
        $propSiteIds = array_column($propSites, 'siteId');
        $propEditableSiteIds = array_intersect($propSiteIds, $allEditableSiteIds);
        $addlEditableSites = array_values(array_filter(
            $supportedSites,
            fn ($site) => ! $site['propagate'] && in_array($site['siteId'], $allEditableSiteIds)
        ));

        if (count($supportedSites) > 1) {
            $expandStatusBtn = (count($propEditableSiteIds) > 1 || $addlEditableSites)
                ? Html::button('', [
                    'class' => ['expand-status-btn', 'btn'],
                    'data' => ['icon' => 'ellipsis'],
                    'title' => t('Update status for individual sites'),
                    'aria' => [
                        'expanded' => 'false',
                        'label' => t('Update status for individual sites'),
                    ],
                ])
                : '';

            $statusField = FormFields::lightswitchFieldHtml([
                'fieldClass' => "enabled-for-site-$this->siteId-field",
                'label' => Html::encode(t($this->getSite()->getName(), category: 'site')),
                'headingSuffix' => $expandStatusBtn,
                'name' => "enabledForSite[$this->siteId]",
                'on' => $this->enabled && $this->getEnabledForSite(),
                'status' => $this->getAttributeStatus('enabled'),
            ]);
        } else {
            $statusField = FormFields::lightswitchFieldHtml([
                'id' => 'enabled',
                'label' => t('Enabled'),
                'name' => 'enabled',
                'on' => $this->enabled,
                'disabled' => $this->getIsRevision(),
                'status' => $this->getAttributeStatus('enabled'),
            ]);
        }

        return Html::beginTag('fieldset')
            .Html::tag('legend', t('Status'), ['class' => 'h6'])
            .Html::tag('div', $statusField, ['class' => 'meta'])
            .Html::endTag('fieldset');
    }

    /**
     * Returns the notes field HTML for the sidebar.
     */
    protected function notesFieldHtml(): string|Stringable
    {
        // todo: this should accept a $static arg
        return FormFields::textareaFieldHtml([
            'label' => t('Notes about your changes'),
            'labelClass' => 'h6',
            'class' => ['nicetext', 'notes'],
            'name' => 'notes',
            'value' => $this->getIsDraft() ? $this->draftNotes : $this->revisionNotes,
            'rows' => 1,
            'inputAttributes' => [
                'aria' => [
                    'label' => t('Notes about your changes'),
                ],
            ],
        ]);
    }

    /**
     * Returns whether the element has a field layout with at least one tab.
     *
     * @return bool Returns whether the element has a field layout with at least one tab.
     */
    protected function hasFieldLayout(): bool
    {
        $fieldLayout = $this->getFieldLayout();

        return $fieldLayout && ! empty($fieldLayout->getTabs());
    }

    /** @return array<string,mixed> */
    public function getMetadata(): array
    {
        $metadata = $this->metadata();

        event($event = new ElementMetadataResolving($this, $metadata));
        $metadata = $event->metadata;

        $formatter = I18N::getFormatter();

        return array_merge([
            t('ID') => fn () => $this->id ?? false,
            t('Status') => fn() => app(StatusHtml::class)->componentStatusLabelHtml($this),
        ], $metadata, [
            t('Created at') => $this->dateCreated && ! $this->getIsUnpublishedDraft()
                ? $formatter->asDatetime($this->dateCreated, Formatter::FORMAT_WIDTH_SHORT, true)
                : false,
            t('Updated at') => $this->dateUpdated && ! $this->getIsUnpublishedDraft()
                ? $formatter->asDatetime($this->dateUpdated, Formatter::FORMAT_WIDTH_SHORT, true)
                : false,
            t('Notes') => function () {
                if ($this->getIsRevision()) {
                    $revision = $this;
                } elseif ($this->getIsCanonical() || $this->isProvisionalDraft) {
                    $element = $this->getCanonical(true);
                    $revision = $element->getCurrentRevision();
                }
                if (! isset($revision)) {
                    return false;
                }

                return Html::encode($revision->revisionNotes);
            },
        ]);
    }

    /**
     * Returns element metadata that should be shown within the editor sidebar.
     *
     * @return array<string,mixed> The data, with keys representing the labels. The values can either be strings or callables.
     *                             If a value is `false`, it will be omitted.
     */
    protected function metadata(): array
    {
        return [];
    }

    /**
     * @see crumbs()
     */
    public function getCrumbs(): array
    {
        if (! $this instanceof NestedElementInterface) {
            return $this->crumbs();
        }

        if ($owner = $this->getOwner()) {
            return [
                ...$owner->getCrumbs(),
                [
                    'html' => app(ElementHtml::class)->elementChipHtml($owner, [
                        'appearance' => 'plain',
                        'showDraftName' => false,
                        'class' => 'chromeless',
                        'hyperlink' => true,
                    ]),
                ],
            ];
        }

        return $this->crumbs();
    }

    /**
     * Returns the breadcrumbs that lead up to the element.
     *
     * @see getCrumbs()
     */
    /** @return array<array-key,mixed> */
    protected function crumbs(): array
    {
        return [];
    }

    public function getUiLabel(): string
    {
        return $this->_uiLabel ?? $this->uiLabel() ?? (string) $this;
    }

    public function setUiLabel(?string $label): void
    {
        $this->_uiLabel = $label;
    }

    public function getUiLabelPath(): array
    {
        return $this->_uiLabelPath;
    }

    public function setUiLabelPath(array $path): void
    {
        $this->_uiLabelPath = $path;
    }

    /**
     * Returns what the element should be called within the control panel.
     */
    protected function uiLabel(): ?string
    {
        return null;
    }

    public function getChipLabelHtml(): string|Stringable
    {
        return Html::encode($this->getUiLabel());
    }

    public function showStatusIndicator(): bool
    {
        return static::hasStatuses();
    }

    public function getCardTitle(): ?string
    {
        return null;
    }

    public function getCardBodyHtml(): ?string
    {
        $this->viewMode = 'cards';
        $html = '';
        $cardElements = $this->getFieldLayout()?->getCardBodyElements($this) ?? [];

        foreach ($cardElements as $item) {
            $html .= Html::tag('div', $item, [
                'class' => 'card-attribute-preview',
            ]);
        }

        return $html;
    }

    public function getRef(): ?string
    {
        return null;
    }
}
