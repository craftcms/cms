<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use Craft;
use craft\base\NestedElementInterface;
use craft\controllers\ElementsController;
use craft\events\DefineAltActionsEvent;
use craft\events\DefineAttributeHtmlEvent;
use craft\events\DefineHtmlEvent;
use craft\events\DefineMenuItemsEvent;
use craft\events\DefineMetadataEvent;
use craft\events\RegisterElementHtmlAttributesEvent;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Translation\Formatter;
use Illuminate\Support\Facades\Auth;
use Stringable;
use yii\web\Response;

use function CraftCms\Cms\t;

/**
 * Provides Control Panel UI functionality for elements.
 *
 * This trait handles methods related to rendering elements in the Control Panel,
 * including edit URLs, sidebar HTML, metadata, action menus, and attribute rendering.
 *
 *
 * @internal
 */
trait HasControlPanelUI
{
    /**
     * @event DefineHtmlEvent The event that is triggered when defining additional buttons that should be shown at the top of the element's edit page.
     *
     * @see getAdditionalButtons()
     * @since 4.0.0
     */
    public const EVENT_DEFINE_ADDITIONAL_BUTTONS = 'defineAdditionalButtons';

    /**
     * @event DefineAltActionsEvent The event that is triggered when defining alternative form actions for the element.
     *
     * @see getAltActions()
     * @since 5.6.0
     */
    public const EVENT_DEFINE_ALT_ACTIONS = 'defineAltActions';

    /**
     * @event DefineMenuItemsEvent The event that is triggered when defining action menu items..
     *
     * @see getActionMenuItems()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining the HTML for the editor sidebar.
     *
     * @see getSidebarHtml()
     * @since 3.7.0
     */
    public const EVENT_DEFINE_SIDEBAR_HTML = 'defineSidebarHtml';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining the HTML for meta fields within the editor sidebar.
     *
     * @see metaFieldsHtml()
     * @since 3.7.0
     */
    public const EVENT_DEFINE_META_FIELDS_HTML = 'defineMetaFieldsHtml';

    /**
     * @event DefineMetadataEvent The event that is triggered when defining the element's metadata info.
     *
     * @see getMetadata()
     * @since 3.7.0
     */
    public const EVENT_DEFINE_METADATA = 'defineMetadata';

    /**
     * @event RegisterElementHtmlAttributesEvent The event that is triggered when registering the HTML attributes that should be included in the element's DOM representation in the control panel.
     */
    public const EVENT_REGISTER_HTML_ATTRIBUTES = 'registerHtmlAttributes';

    /**
     * @event DefineAttributeHtmlEvent The event that is triggered when defining an attribute's HTML for table and card views.
     *
     * @see getAttributeHtml()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ATTRIBUTE_HTML = 'defineAttributeHtml';

    /**
     * @event DefineAttributeHtmlEvent The event that is triggered when defining an attribute's inline input HTML.
     *
     * @see getInlineAttributeInputHtml()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML = 'defineInlineAttributeInputHtml';

    /**
     * Performs any action after the element's editor is fully ready.
     */
    public function prepareEditScreen(Response|CpScreenResponse $response, string $containerId): void {}

    /**
     * {@inheritdoc}
     */
    public function getCpEditUrl(): ?string
    {
        if (! $this->id) {
            return null;
        }

        $url = $this->cpEditUrl();

        return $url ? ElementHelper::addElementEditorUrlParams($url, $this) : null;
    }

    /**
     * Returns the element's edit URL in the control panel.
     *
     * @since 3.7.0
     */
    protected function cpEditUrl(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPostEditUrl(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalButtons(): string|Stringable
    {
        if (! $this->hasEventHandlers(self::EVENT_DEFINE_ADDITIONAL_BUTTONS)) {
            return '';
        }

        $event = new DefineHtmlEvent;
        $this->trigger(self::EVENT_DEFINE_ADDITIONAL_BUTTONS, $event);

        return $event->html;
    }

    /**
     * {@inheritdoc}
     */
    public function getAltActions(): array
    {
        $isUnpublishedDraft = $this->getIsUnpublishedDraft();
        $elementsService = Craft::$app->getElements();
        $canSaveCanonical = $elementsService->canSaveCanonical($this);

        $altActions = [
            [
                'label' => $isUnpublishedDraft && $canSaveCanonical
                    ? t('Create and continue editing')
                    : t('Save and continue editing'),
                'redirect' => '{cpEditUrl}',
                'shortcut' => true,
                'retainScroll' => true,
                'eventData' => ['autosave' => false],
            ],
        ];

        if ($this->getIsCanonical() || $this->isProvisionalDraft) {
            $newElement = $this->createAnother();
            if ($newElement && $elementsService->canSave($newElement)) {
                $altActions[] = [
                    'label' => $isUnpublishedDraft && $canSaveCanonical
                        ? t('Create and add another')
                        : t('Save and add another'),
                    'shortcut' => true,
                    'shift' => true,
                    'eventData' => ['autosave' => false],
                    'params' => ['addAnother' => 1],
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

            if (! $this->getIsRevision() && $elementsService->canDuplicateAsDraft($this)) {
                $altActions[] = [
                    'label' => t('Save as a new {type}', [
                        'type' => static::lowerDisplayName(),
                    ]),
                    'action' => 'elements/duplicate',
                    'redirect' => '{cpEditUrl}',
                    'params' => [
                        'asUnpublishedDraft' => true,
                        'deleteProvisionalDraft' => true,
                    ],
                ];
            }
        }

        // Fire a 'defineAltActions' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ALT_ACTIONS)) {
            $event = new DefineAltActionsEvent([
                'altActions' => $altActions,
            ]);
            $this->trigger(self::EVENT_DEFINE_ALT_ACTIONS, $event);

            return $event->altActions;
        }

        return $altActions;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionMenuItems(): array
    {
        $items = [
            ...$this->safeActionMenuItems(),
            ...array_map(fn (array $item) => $item + ['destructive' => true], $this->destructiveActionMenuItems()),
        ];

        // Fire a 'defineActionMenuItems' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
            $event = new DefineMenuItemsEvent(['items' => $items]);
            $this->trigger(self::EVENT_DEFINE_ACTION_MENU_ITEMS, $event);

            return $event->items;
        }

        return $items;
    }

    /**
     * Returns action menu items for the element's edit screens.
     *
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
     *
     * @see getActionMenuItems()
     * @see Cp::disclosureMenu()
     * @since 5.0.0
     */
    protected function safeActionMenuItems(): array
    {
        $items = [];
        $elementsService = Craft::$app->getElements();
        $view = Craft::$app->getView();

        // Validate
        if (
            ! $this->getIsRevision() &&
            ! Craft::$app->getRequest()->getHeaders()->has('X-Craft-Container-Id') &&
            Craft::$app->controller instanceof ElementsController &&
            Craft::$app->controller->element === $this
        ) {
            $validateId = sprintf('action-validate-%s', mt_rand());
            $items[] = [
                'id' => $validateId,
                'icon' => 'circle-check',
                'label' => t('Validate {type}', [
                    'type' => static::lowerDisplayName(),
                ]),
            ];

            $view->registerJsWithVars(fn ($id) => <<<JS
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
                $view->namespaceInputId($validateId),
            ]);
        }

        // View
        $url = $this->getUrl();
        if ($url) {
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

        if ($elementsService->canView($this)) {
            // Edit
            $editId = sprintf('action-edit-%s', mt_rand());
            $items[] = [
                'id' => $editId,
                'icon' => 'edit',
                'label' => mb_ucfirst(t('Edit {type}', [
                    'type' => static::lowerDisplayName(),
                ])),
            ];

            $view->registerJsWithVars(fn ($id, $elementType, $settings) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.createElementEditor($elementType, $settings)
});
JS, [
                $view->namespaceInputId($editId),
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
            if (! $this->getIsRevision() && $elementsService->canCopy($this)) {
                $copyId = sprintf('action-copy-%s', mt_rand());
                $items[] = [
                    'id' => $copyId,
                    'color' => Color::Fuchsia,
                    'icon' => 'clone-dashed',
                    'label' => mb_ucfirst(t('Copy {type}', [
                        'type' => static::lowerDisplayName(),
                    ])),
                ];

                $view = Craft::$app->getView();
                $view->registerJsWithVars(fn ($id, $elementInfo) => <<<JS
(() => {
  $('#' + $id).on('activate', () => {
    Craft.cp.copyElements([$elementInfo])
  });
})();
JS, [
                    $view->namespaceInputId($copyId),
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
     * Returns destructive action menu items for the element's edit screens.
     *
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
     *
     * `'destructive' => true` will be automatically added to all returned items.
     *
     * @see getActionMenuItems()
     * @see Cp::disclosureMenu()
     * @since 5.0.0
     */
    protected function destructiveActionMenuItems(): array
    {
        $items = [];

        $elementsService = Craft::$app->getElements();
        $user = Auth::user();

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

        $canDeleteDraft = $isDraft && ! $this->isProvisionalDraft && $elementsService->canDelete($this, $user);
        $canDeleteCanonical = $elementsService->canDelete($canonical, $user);
        $canDeleteCanonicalForSite = $elementsService->canDeleteForSite($canonical, $user);
        $canDeleteForSite = (
            ElementHelper::isMultiSite($this) &&
            (($isCurrent && $canDeleteCanonicalForSite) || ($canDeleteDraft && $isNewSite)) &&
            $elementsService->canDeleteForSite($this, $user)
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
                $items[] = [
                    'icon' => 'trash',
                    'label' => mb_ucfirst(t('Delete {type}', [
                        'type' => $isUnpublishedDraft ? t('draft') : static::lowerDisplayName(),
                    ])),
                    'action' => $isUnpublishedDraft ? 'elements/delete-draft' : 'elements/delete',
                    'params' => [
                        'elementId' => $this->getCanonicalId(),
                        'siteId' => $this->siteId,
                    ],
                    'redirect' => "$redirectUrl#",
                    'confirm' => t('Are you sure you want to delete this {type}?', [
                        'type' => $isUnpublishedDraft ? t('draft') : static::lowerDisplayName(),
                    ]),
                    'destructive' => true,
                ];
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

    /**
     * {@inheritdoc}
     */
    public function getHtmlAttributes(string $context): array
    {
        $htmlAttributes = Arr::merge($this->htmlAttributes($context), [
            'data' => [
                'disallow-status' => ! $this->showStatusField(),
            ],
        ]);

        // Fire a 'registerHtmlAttributes' event
        if ($this->hasEventHandlers(self::EVENT_REGISTER_HTML_ATTRIBUTES)) {
            $event = new RegisterElementHtmlAttributesEvent(['htmlAttributes' => $htmlAttributes]);
            $this->trigger(self::EVENT_REGISTER_HTML_ATTRIBUTES, $event);

            return $event->htmlAttributes;
        }

        return $htmlAttributes;
    }

    /**
     * Returns any attributes that should be included in the element's chips and cards.
     *
     * @param  string  $context  The context that the element is being rendered in ('index', 'modal', 'field', or 'settings'.)
     *
     * @see getHtmlAttributes()
     */
    protected function htmlAttributes(string $context): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeHtml(string $attribute): string|Stringable
    {
        // Fire a 'defineAttributeHtml' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ATTRIBUTE_HTML)) {
            $event = new DefineAttributeHtmlEvent([
                'attribute' => $attribute,
            ]);
            $this->trigger(self::EVENT_DEFINE_ATTRIBUTE_HTML, $event);
            if (isset($event->html)) {
                return $event->html;
            }
        }

        return $this->attributeHtml($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function getInlineAttributeInputHtml(string $attribute): string|Stringable
    {
        // Fire a 'defineInlineAttributeInputHtml' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML)) {
            $event = new DefineAttributeHtmlEvent(['attribute' => $attribute]);
            $this->trigger(self::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML, $event);
            if (isset($event->html)) {
                return $event->html;
            }
        }

        return $this->inlineAttributeInputHtml($attribute);
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
     * @since 5.0.0
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
     * @since 5.0.0
     */
    protected function inlineAttributeInputHtml(string $attribute): string|Stringable
    {
        return app(ElementAttributeRenderer::class)->renderInlineInput($this, $attribute);
    }

    /**
     * {@inheritdoc}
     */
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

        if (! $this->hasEventHandlers(self::EVENT_DEFINE_SIDEBAR_HTML)) {
            return $html;
        }

        $event = new DefineHtmlEvent(['html' => $html]);
        $this->trigger(self::EVENT_DEFINE_SIDEBAR_HTML, $event);

        return $event->html;
    }

    /**
     * Returns the HTML for any meta fields that should be shown within the editor sidebar.
     *
     * @param  bool  $static  Whether the fields should be static (non-interactive)
     *
     * @since 3.7.0
     */
    protected function metaFieldsHtml(bool $static): string|Stringable
    {
        if (! $this->hasEventHandlers(self::EVENT_DEFINE_META_FIELDS_HTML)) {
            return '';
        }

        $event = new DefineHtmlEvent(['static' => $static]);
        $this->trigger(self::EVENT_DEFINE_META_FIELDS_HTML, $event);

        return $event->html;
    }

    /**
     * Returns the HTML for the element's Slug field.
     *
     * @param  bool  $static  Whether the fields should be static (non-interactive)
     *
     * @since 3.7.0
     */
    protected function slugFieldHtml(bool $static): string|Stringable
    {
        $slug = isset($this->slug) && ! ElementHelper::isTempSlug($this->slug) ? $this->slug : null;

        return Cp::textFieldHtml([
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
     *  If set to `false`, the element's status can't be updated via edit forms, the Set Status action, or `resave/*` commands.
     *
     * @since 4.5.0
     */
    protected function showStatusField(): bool
    {
        return true;
    }

    /**
     * Returns the status field HTML for the sidebar.
     *
     * @since 4.0.0
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

            $statusField = Cp::lightswitchFieldHtml([
                'fieldClass' => "enabled-for-site-$this->siteId-field",
                'label' => t($this->getSite()->getName(), category: 'site'),
                'headingSuffix' => $expandStatusBtn,
                'name' => "enabledForSite[$this->siteId]",
                'on' => $this->enabled && $this->getEnabledForSite(),
                'status' => $this->getAttributeStatus('enabled'),
            ]);
        } else {
            $statusField = Cp::lightswitchFieldHtml([
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
     *
     * @since 4.0.0
     */
    protected function notesFieldHtml(): string|Stringable
    {
        // todo: this should accept a $static arg
        return Cp::textareaFieldHtml([
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
     *
     * @since 3.7.0
     */
    protected function hasFieldLayout(): bool
    {
        $fieldLayout = $this->getFieldLayout();

        return $fieldLayout && ! empty($fieldLayout->getTabs());
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        $metadata = $this->metadata();

        // Fire a 'defineMetadata' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_METADATA)) {
            $event = new DefineMetadataEvent(['metadata' => $metadata]);
            $this->trigger(self::EVENT_DEFINE_METADATA, $event);
            $metadata = $event->metadata;
        }

        $formatter = I18N::getFormatter();

        return array_merge([
            t('ID') => fn () => $this->id ?? false,
            t('Status') => function () {
                if (! static::hasStatuses()) {
                    return false;
                }
                if ($this->getIsDraft() && ! $this->isProvisionalDraft) {
                    $icon = Html::tag('span', '', [
                        'data' => ['icon' => 'draft'],
                        'aria' => ['hidden' => 'true'],
                    ]);
                    $label = t('Draft');
                } else {
                    $status = $this->getStatus();
                    $statusDef = static::statuses()[$status] ?? null;
                    $color = $statusDef['color'] ?? $status;
                    if ($color instanceof Color) {
                        $color = $color->value;
                    }
                    $icon = Html::tag('span', '', ['class' => ['status', $color]]);
                    $label = $statusDef['label'] ?? $statusDef ?? ucfirst($status);
                }

                return $icon.Html::tag('span', $label);
            },
        ], $metadata, [
            t('Created at') => $this->dateCreated && ! $this->getIsUnpublishedDraft()
                ? $formatter->asDatetime($this->dateCreated, Formatter::FORMAT_WIDTH_SHORT)
                : false,
            t('Updated at') => $this->dateUpdated && ! $this->getIsUnpublishedDraft()
                ? $formatter->asDatetime($this->dateUpdated, Formatter::FORMAT_WIDTH_SHORT)
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
     * @return array The data, with keys representing the labels. The values can either be strings or callables.
     *               If a value is `false`, it will be omitted.
     *
     * @since 3.7.0
     */
    protected function metadata(): array
    {
        return [];
    }
}
