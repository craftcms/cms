<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Element\Events\ElementEditorContentResolving;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\EditsElement;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesElement;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Locale;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class EditElementController
{
    use EditsElement;
    use ElementCrumbs;
    use SavesElement;

    private ?ElementInterface $element = null;

    public function __construct(
        protected readonly ElementRequest $request,
        private readonly Elements $elements,
        private readonly Sites $sites,
    ) {}

    public function setElement(ElementInterface $element): self
    {
        $this->element = $element;

        return $this;
    }

    public function __invoke(): Response|CpScreenResponse
    {
        $strictSite = $this->request->acceptsJson();
        $elementId = $this->request->route('id') ?? $this->request->integer('elementId');

        /**
         * @var Element|Response|null $element
         */
        $element = $this->element ?? $this->request->element([
            'id' => $elementId,
        ], checkForProvisionalDraft: true, strictSite: $strictSite);

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element) {
            abort(400, 'No element was identified by the request.');
        }

        // If this is an outdated draft, merge in the latest canonical changes
        $mergeCanonicalChanges = (
            $element::trackChanges() &&
            $element->getIsDraft() &&
            ! $element->getIsUnpublishedDraft() &&
            ElementHelper::isOutdated($element)
        );

        if ($mergeCanonicalChanges) {
            $this->elements->mergeCanonicalChanges($element);
        }

        $this->applyParamsToElement($element);

        // Prevalidate?
        if ($this->request->boolean('prevalidate') && $element->enabled && $element->getEnabledForSite()) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
            $element->validate();
        }

        // Figure out what we're dealing with here
        $isCanonical = $element->getIsCanonical();
        $isDraft = $element->getIsDraft();
        $isUnpublishedDraft = $element->getIsUnpublishedDraft();
        $isRevision = $element->getIsRevision();
        $isCurrent = $isCanonical || $element->isProvisionalDraft;
        $canonical = $element->getCanonical(true);

        // Site info
        $supportedSites = ElementHelper::supportedSitesForElement($element, true);
        $allEditableSiteIds = $this->sites->getEditableSiteIds()->all();
        $propSites = array_values(array_filter($supportedSites, fn ($site) => $site['propagate']));
        $propSiteIds = array_column($propSites, 'siteId');
        $propEditableSiteIds = array_intersect($propSiteIds, $allEditableSiteIds);
        $addlEditableSites = array_values(array_filter($supportedSites, fn ($site) => ! $site['propagate'] && in_array($site['siteId'], $allEditableSiteIds)));
        $canEditMultipleSites = count($propEditableSiteIds) > 1 || $addlEditableSites;

        // Permissions
        $canSave = $this->canSave($element, $this->request->craftUser());
        $canSaveCanonical = Gate::check('saveCanonical', $element);
        $canCreateDrafts = Gate::check('createDrafts', $canonical);
        $canDuplicate = ! $isRevision && Gate::check('duplicateAsDraft', $element);

        // Preview targets
        $previewTargets = $element->id ? $element->getPreviewTargets() : [];
        $enablePreview = (
            ! empty($previewTargets) &&
            ! $this->request->isMobileBrowser(true) &&
            (
                ($isDraft && $canSave) ||
                ($isCurrent && $canCreateDrafts)
            )
        );

        if ($previewTargets) {
            match (true) {
                $isDraft && ! $element->isProvisionalDraft => SessionAuth::authorize("previewDraft:$element->draftId"),
                $isRevision => SessionAuth::authorize("previewRevision:$element->revisionId"),
                default => SessionAuth::authorize("previewElement:$canonical->id"),
            };
        }

        // Screen prep
        [$docTitle, $title] = $this->editElementTitles($element);
        $enabledForSite = $element->getEnabledForSite();
        $hasRoute = $element->getRoute() !== null;

        $redirectUrl = $this->request->query('returnUrl');
        if ($redirectUrl) {
            // only require the URL to be hashed if it contains Twig code
            try {
                $redirectUrl = Crypt::decrypt($redirectUrl);
            } catch (DecryptException) {
                if (str_contains($redirectUrl, '{')) {
                    abort(400, "Invalid returnUrl param: $redirectUrl");
                }
            }

            // swap the QS placeholder with a `?`
            // (see https://github.com/craftcms/cms/issues/18923)
            $redirectUrl = str_replace(':QS:', '?', $redirectUrl);
        } else {
            $redirectUrl = ElementHelper::postEditUrl($element);
        }

        // Site statuses
        if ($canEditMultipleSites) {
            $siteStatuses = ElementHelper::siteStatusesForElement($element, true);
        } else {
            $siteStatuses = [
                $element->siteId => $element->enabled,
            ];
        }

        $previewToken = $previewTargets ? Str::random(32, extendedChars: true) : null;

        $notice = match (true) {
            $element->isProvisionalDraft => $this->draftNotice(...),
            $isRevision => fn () => $this->revisionNotice($element::lowerDisplayName()),
            default => null,
        };

        $response = new CpScreenResponse()
            ->editUrl($element->getCpEditUrl())
            ->docTitle($docTitle)
            ->title($title)
            ->crumbs($this->crumbs($element))
            ->contextMenuItems(fn () => $this->contextMenuItems(
                element: $element,
                isUnpublishedDraft: $isUnpublishedDraft,
                canCreateDrafts: $canCreateDrafts,
            ))
            ->toolbarHtml(
                // if we're in a slideout, we don't want to add the .flex-grow to the header toolbar
                // as it'll mess with the width available for the tabs
                // see https://github.com/craftcms/cms/issues/17260
                ($this->isSlideout() ? '' : Html::tag('div', attributes: ['class' => 'cp:flex-grow'])).
                Html::tag('div', attributes: ['class' => 'activity-container']),
            )
            ->additionalButtonsHtml(fn () => $this->additionalButtons(
                element: $element,
                canonical: $canonical,
                isRevision: $isRevision,
                canSave: $canSave,
                canSaveCanonical: $canSaveCanonical,
                canCreateDrafts: $canCreateDrafts,
                canDuplicate: $canDuplicate,
                previewTargets: $previewTargets,
                enablePreview: $enablePreview,
                isCurrent: $isCurrent,
                isUnpublishedDraft: $isUnpublishedDraft,
                isDraft: $isDraft
            ))
            ->actionMenuItems(fn () => $this->actionMenuItems($element, $previewTargets))
            ->noticeHtml($notice)
            ->errorSummary(fn () => new ElementResponse()->errorSummary($element))
            ->prepareScreen(
                fn (CpScreenResponse $response, string $containerId) => $this->prepareEditor(
                    $element,
                    $isUnpublishedDraft,
                    $canSave,
                    $response,
                    $containerId,
                    fn (?string $form) => $this->editorContent($element, $canSave, $form),
                    fn () => $this->editorSidebar($element, $mergeCanonicalChanges, $canSave),
                    fn () => [
                        'additionalSites' => $addlEditableSites,
                        'canCreateDrafts' => $canCreateDrafts,
                        'canEditMultipleSites' => $canEditMultipleSites,
                        'canSave' => $canSave,
                        'canSaveCanonical' => $canSaveCanonical,
                        'elementId' => $element->id,
                        'canonicalId' => $canonical->id,
                        'draftId' => $element->draftId,
                        'draftName' => $isDraft ? $element->draftName : null,
                        'elementType' => $element::class,
                        'enablePreview' => $enablePreview,
                        'enabledForSite' => $element->enabled && $enabledForSite,
                        'hashedCpEditUrl' => Crypt::encrypt('{cpEditUrl}'),
                        'isLive' => $isCurrent && ! $element->getIsDraft() && $element->enabled && $enabledForSite && $hasRoute,
                        'isProvisionalDraft' => $element->isProvisionalDraft,
                        'isUnpublishedDraft' => $isUnpublishedDraft,
                        'previewTargets' => $previewTargets,
                        'previewToken' => $previewToken,
                        'hashedPreviewToken' => $previewToken ? Crypt::encrypt($previewToken) : null,
                        'previewParamValue' => $previewTargets ? Crypt::encrypt(Str::random(10)) : null,
                        'revisionId' => $element->revisionId,
                        'fieldId' => $element instanceof NestedElementInterface ? $element->getField()?->id : null,
                        'ownerId' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
                        'siteId' => $element->siteId,
                        'siteStatuses' => $siteStatuses,
                        'siteToken' => (! app()->isLive() || ! $element->getSite()->getEnabled()) ? Crypt::encrypt((string) $element->siteId) : null,
                        'updatedTimestamp' => $element->dateUpdated?->getTimestamp(),
                        'canonicalUpdatedTimestamp' => $canonical->dateUpdated?->getTimestamp(),
                        'isStatic' => $isRevision || ! $canSave,
                    ]
                )
            );

        if ($canSave) {
            match (true) {
                $isUnpublishedDraft => $response->when(
                    value: $canSaveCanonical,
                    callback: fn (CpScreenResponse $response) => $response
                        ->submitButtonLabel(mb_ucfirst(t('Create {type}', [
                            'type' => $element::lowerDisplayName(),
                        ])))
                        ->action('elements/apply-draft')
                        ->redirectUrl("$redirectUrl#"),
                    default: fn (CpScreenResponse $response) => $response
                        ->action('elements/save-draft')
                        ->redirectUrl("$redirectUrl#")
                ),
                $element->isProvisionalDraft => $response
                    ->action('elements/apply-draft')
                    ->redirectUrl("$redirectUrl#"),
                $isDraft => $response
                    ->submitButtonLabel(mb_ucfirst(t('Save {type}', [
                        'type' => t('draft'),
                    ])))
                    ->action('elements/save-draft')
                    ->redirectUrl('{cpEditUrl}'),
                default => $response
                    ->action('elements/save')
                    ->redirectUrl("$redirectUrl#")
            };

            $response
                ->saveShortcutRedirectUrl('{cpEditUrl}')
                ->altActions($element->getAltActions());
        }

        return $response;
    }

    private function draftNotice(): string
    {
        return
            Html::beginTag('div', [
                'class' => 'draft-notice',
            ]).
            Html::tag('div', '', [
                'class' => ['draft-icon'],
                'aria' => ['hidden' => 'true'],
                'data' => ['icon' => 'edit'],
            ]).
            Html::tag('p', t('Showing your unsaved changes.')).
            Html::button(t('Discard'), [
                'class' => ['discard-changes-btn', 'btn'],
            ]).
            Html::endTag('div');
    }

    private function revisionNotice(string $elementType): string
    {
        return
            Html::beginTag('div', [
                'class' => 'content-notice',
            ]).
            Html::tag('div', '', [
                'class' => ['content-notice-icon'],
                'aria' => ['hidden' => 'true'],
                'data' => ['icon' => 'lightbulb'],
            ]).
            Html::tag('p', t('You’re viewing a revision. None of the {type}’s fields are editable.', [
                'type' => $elementType,
            ])).
            Html::endTag('div');
    }

    /** @return list<array<string, mixed>> */
    private function contextMenuItems(
        ElementInterface $element,
        bool $isUnpublishedDraft,
        bool $canCreateDrafts,
    ): array {
        if ($element->isProvisionalDraft) {
            $element = $element->getCanonical(true);
        }

        if (! $element->id || $element->getIsUnpublishedDraft()) {
            return [];
        }

        if (! $isUnpublishedDraft) {
            $drafts = $element::find()
                ->draftOf($element)
                ->siteId($element->siteId)
                ->status(null)
                ->orderByDesc('dateUpdated')
                ->with(['draftCreator'])
                ->get()
                ->filter(fn (ElementInterface $draft) => $this->request->craftUser()->can('view', $draft))
                ->all();
        } else {
            $drafts = [];
        }

        $revisionsPageUrl = null;
        $hasMoreRevisions = false;

        if ($element->hasRevisions() && Cms::config()->maxRevisions !== 1) {
            $revisionsQuery = $element::find()
                ->revisionOf($element)
                ->siteId($element->siteId)
                ->status(null)
                ->offset(1)
                ->limit(Cms::config()->maxRevisions ? min(Cms::config()->maxRevisions - 1, 10) : 10)
                ->orderByDesc('dateCreated')
                ->with(['revisionCreator']);

            $revisions = (clone $revisionsQuery)->get();
            $revisionsPageUrl = $element->getCpRevisionsUrl();

            if ($revisionsPageUrl) {
                $hasMoreRevisions = ($revisionsQuery->getCountForPagination() - 1) > 0;
            }
        } else {
            $revisions = collect();
        }

        // if we're viewing a revision, make sure it's in the list
        if (
            $element->getIsRevision() &&
            $revisions->doesntContain(fn (ElementInterface $revision) => $revision->id === $element->id)
        ) {
            $revisions[] = $element;
        }

        if (empty($drafts) && empty($revisions) && ! $canCreateDrafts) {
            return [];
        }

        $formatter = I18N::getFormatter();

        $baseParams = $this->request->query();
        unset($baseParams['draftId'], $baseParams['revisionId'], $baseParams['siteId'], $baseParams['fresh']);

        $isDraft = $element->getIsDraft();
        $isRevision = $element->getIsRevision();
        $cpEditUrl = Url::cpUrl($element->getCpEditUrl(), [
            'draftId' => null,
            'revisionId' => null,
        ]);

        $revision = $element->getCurrentRevision();
        $creator = $revision?->getRevisionCreator();
        $timestamp = $formatter->asTimestamp($revision->dateCreated ?? $element->dateUpdated, Locale::LENGTH_SHORT, true);

        $items = [
            [
                'heading' => t('Context'),
                'headingTag' => 'h2',
                'headingAttributes' => ['class' => ['visually-hidden']],
                'listAttributes' => ['class' => ['revision-group-current']],
                'items' => [
                    [
                        'label' => t('Current'),
                        'description' => $creator
                            ? t('Saved {timestamp} by {creator}', [
                                'timestamp' => $timestamp,
                                'creator' => $creator->name,
                            ])
                            : t('Last saved {timestamp}', [
                                'timestamp' => $timestamp,
                            ]),
                        'url' => $cpEditUrl,
                        'selected' => ! $isDraft && ! $isRevision,
                    ],
                ],
            ],
        ];

        if (! empty($drafts)) {
            $items[] = [
                'heading' => t('Drafts'),
                'listAttributes' => ['class' => ['revision-group-drafts']],
                'items' => array_map(function ($draft) use ($element, $formatter, $cpEditUrl, $baseParams) {
                    /** @var ElementInterface $draft */
                    $creator = $draft->getDraftCreator();
                    $timestamp = $formatter->asTimestamp($draft->dateUpdated, Locale::LENGTH_SHORT, true);
                    $timestampWithDate = $formatter->asDatetime($draft->dateUpdated, Locale::LENGTH_SHORT, true);

                    return [
                        'label' => $draft->draftName,
                        'description' => $creator
                            ? Template::raw(t('Saved <time title="{timestampWithDate}">{timestamp}</time> by {creator}', [
                                'timestampWithDate' => $timestampWithDate,
                                'timestamp' => $timestamp,
                                'creator' => Html::encode($creator->name),
                            ]))
                            : Template::raw(t('Last saved <time title="{timestampWithDate}">{timestamp}</time>', [
                                'timestampWithDate' => $timestampWithDate,
                                'timestamp' => $timestamp,
                            ])),
                        'url' => Url::urlWithParams($cpEditUrl, array_merge($baseParams, [
                            'draftId' => $draft->draftId,
                        ])),
                        'selected' => $draft->id === $element->id,
                    ];
                }, $drafts),
            ];
        }

        if (! empty($revisions)) {
            $items[] = [
                'heading' => t('Recent Revisions'),
                'listAttributes' => ['class' => ['revision-group-revisions']],
                'items' => $revisions->map(function (ElementInterface $revision) use ($element, $formatter, $cpEditUrl, $baseParams) {
                    $creator = $revision->getRevisionCreator();
                    $timestamp = $formatter->asTimestamp($revision->dateCreated, Locale::LENGTH_SHORT, true);
                    $timestampWithDate = $formatter->asDatetime($revision->dateCreated, Locale::LENGTH_SHORT, true);

                    return [
                        'label' => $revision->getRevisionLabel(),
                        'description' => $creator
                            ? Template::raw(t('Saved <time title="{timestampWithDate}">{timestamp}</time> by {creator}', [
                                'timestampWithDate' => $timestampWithDate,
                                'timestamp' => $timestamp,
                                'creator' => Html::encode($creator->name),
                            ]))
                            : Template::raw(t('Saved <time title="{timestampWithDate}">{timestamp}</time>', [
                                'timestampWithDate' => $timestampWithDate,
                                'timestamp' => $timestamp,
                            ])),
                        'url' => Url::urlWithParams($cpEditUrl, array_merge($baseParams, [
                            'revisionId' => $revision->revisionId,
                        ])),
                        'selected' => $revision->id === $element->id,
                    ];
                })->all(),
            ];
        }

        if ($hasMoreRevisions && $revisionsPageUrl) {
            $items[] = ['type' => MenuItemType::HR];
            $items[] = [
                'label' => t('View all revisions'),
                'url' => $revisionsPageUrl,
                'attributes' => [
                    'class' => ['go'],
                ],
            ];
        }

        return $items;
    }

    private function isSlideout(): bool
    {
        return $this->request->hasHeader('X-Craft-Container-Id');
    }

    /** @param list<array<string, mixed>>|null $previewTargets */
    private function additionalButtons(
        ElementInterface $element,
        ElementInterface $canonical,
        bool $isRevision,
        bool $canSave,
        bool $canSaveCanonical,
        bool $canCreateDrafts,
        bool $canDuplicate,
        ?array $previewTargets,
        bool $enablePreview,
        bool $isCurrent,
        bool $isUnpublishedDraft,
        bool $isDraft,
    ): string {
        $components = [];

        // Preview (View will be added later by JS)
        if ($previewTargets) {
            $components[] =
                Html::beginTag('div', [
                    'class' => ['preview-btn-container', 'btngroup'],
                ]).
                ($enablePreview
                    ? Html::beginTag('button', [
                        'type' => 'button',
                        'class' => ['preview-btn', 'btn'],
                    ]).
                    Html::tag('span', t('Preview'), ['class' => 'label']).
                    Html::endTag('button')
                    : '').
                Html::endTag('div');
        }

        // Create a draft
        if ($isCurrent && ! $isUnpublishedDraft && $canCreateDrafts) {
            if ($canSave) {
                $components[] = Html::button(t('Create a draft'), [
                    'class' => ['btn', 'formsubmit'],
                    'data' => [
                        'action' => 'elements/save-draft',
                        'redirect' => Crypt::encrypt('{cpEditUrl}'),
                        'params' => ['dropProvisional' => 1],
                    ],
                ]);
            } else {
                $components[] = Html::beginForm().
                    Html::actionInput('elements/save-draft').
                    Html::redirectInput('{cpEditUrl}').
                    Html::hiddenInput('elementId', (string) $canonical->id).
                    Html::button(t('Create a draft'), [
                        'class' => ['btn', 'formsubmit'],
                    ]).
                    Html::endForm();
            }
        }

        if (! $canSave && $canDuplicate) {
            // save as a new is now available to people who can create drafts
            $components[] = Html::beginForm().
                Html::actionInput('elements/duplicate').
                Html::redirectInput('{cpEditUrl}').
                Html::hiddenInput('elementId', (string) $canonical->id).
                Html::hiddenInput('asUnpublishedDraft', '1').
                Html::button(t('Save as a new {type}', ['type' => $element::lowerDisplayName()]), [
                    'class' => ['btn', 'formsubmit'],
                ]).
                Html::endForm();
        }

        // Apply draft
        if ($isDraft && ! $isCurrent && $canSave && $canSaveCanonical) {
            $components[] = Html::button(t('Apply draft'), [
                'class' => ['btn', 'secondary', 'formsubmit', 'tooltip-draft-btn'],
                'data' => [
                    'action' => 'elements/apply-draft',
                    'redirect' => Crypt::encrypt('{cpEditUrl}'),
                ],
            ]);
        }

        // Revert content from this revision
        if ($isRevision && $canSaveCanonical && $element->hasRevisions()) {
            $returnUrl = $this->request->query('returnUrl');

            $components[] = Html::beginForm().
                Html::actionInput('elements/revert').
                Html::redirectInput('{cpEditUrl}').
                ($returnUrl
                    ? Html::hiddenInput('redirectParams', Json::encode([
                        'returnUrl' => $returnUrl,
                    ]))
                    : ''
                ).
                Html::hiddenInput('elementId', (string) $canonical->id).
                Html::hiddenInput('revisionId', (string) $element->revisionId).
                Html::button(t('Revert content from this revision'), [
                    'class' => ['btn', 'formsubmit', 'revision-draft-btn'],
                ]).
                Html::endForm();
        }

        $components[] = $element->getAdditionalButtons();

        return implode("\n", array_filter($components));
    }

    /**
     * @param  list<array<string, mixed>>  $previewTargets
     * @return list<array<string, mixed>>
     */
    private function actionMenuItems(ElementInterface $element, array $previewTargets): array
    {
        if (! $element->id) {
            return [];
        }

        $hideViewAction = ! empty($previewTargets) && ! $this->isSlideout();

        return array_filter(
            $element->getActionMenuItems(),
            function (array $item) use ($hideViewAction) {
                // filter out "Edit" item - no point showing edit action on the edit page,
                if (str_starts_with($item['id'] ?? '', 'action-edit-')) {
                    return false;
                }

                // and "View in a new tab" item, if we have at least one preview target, and it's not a slideout
                // as that action is already covered by the "View" button;
                // (https://github.com/craftcms/cms/issues/16556)
                if ($hideViewAction && str_starts_with($item['id'] ?? '', 'action-view-')) {
                    return false;
                }

                return true;
            },
        );
    }

    private function prepareEditor(
        ElementInterface $element,
        bool $isUnpublishedDraft,
        bool $canSave,
        CpScreenResponse $response,
        string $containerId,
        callable $contentFn,
        callable $sidebarFn,
        callable $jsSettingsFn,
    ): void {
        $fieldLayout = $element->getFieldLayout();
        $payload = null;
        $vueForm = $this->request->expectsJson();

        if ($fieldLayout !== null) {
            $payload = DeltaRegistry::withActive(true, fn () => app(FieldLayoutCompiler::class)->compile(
                $fieldLayout,
                $element,
                new FormContext(
                    namespace: $vueForm ? (InputNamespace::get() ?? []) : [],
                    errors: $element->errors()->getMessages(),
                    mode: $canSave ? ControlMode::Editable : ControlMode::ReadOnly,
                    refreshable: true,
                ),
            ));
        }

        $renderer = app(FormHtmlRenderer::class);
        $formContent = match (true) {
            $payload === null => null,
            $vueForm => Html::tag('craft-entry-field-layout-form', '', [
                'data' => ['payload' => Json::encode($payload)],
            ]),
            default => $renderer->render($payload),
        };
        $contentHtml = $contentFn($formContent);
        $sidebarHtml = $sidebarFn();

        if ($contentHtml === '' && $sidebarHtml !== '' && $this->request->acceptsJson()) {
            $contentHtml = Html::tag('div', $sidebarHtml, [
                'class' => 'details',
            ]);
            $sidebarHtml = '';
            $response->slideoutBodyClass = 'so-full-details';
        }

        if ($canSave) {
            $components = [];

            if ($element->id) {
                // don't use the canonical ID if this is a normal element that's keeping track of its canonical
                // e.g. nested Matrix entries that were duplicated for an owner's draft
                $id = $element->getIsDraft() || $element->getIsRevision() ? $element->getCanonicalId() : $element->id;
                $components[] = Html::hiddenInput('elementId', (string) $id);
            }

            if ($element->siteId) {
                $components[] = Html::hiddenInput('siteId', (string) $element->siteId);
            }

            if ($element->fieldLayoutId) {
                $components[] = Html::hiddenInput('fieldLayoutId', (string) $element->fieldLayoutId);
            }

            if ($isUnpublishedDraft && $this->request->boolean('fresh')) {
                $components[] = Html::hiddenInput('fresh', '1');
            }

            if ($this->request->boolean('updateSearchIndexImmediately')) {
                $components[] = Html::hiddenInput('updateSearchIndexImmediately', '1');
            }

            $components[] = $contentHtml;
            $contentHtml = implode("\n", $components);
        }

        $response->tabs($payload === null || $vueForm ? [] : $renderer->tabMenu($payload));
        $response->contentHtml($contentHtml);
        $response->metaSidebarHtml($sidebarHtml);

        $settings = $jsSettingsFn();

        if ($this->request->inertia()) {
            // The Vue slideout builds its own `Craft.ElementEditor`, but can't
            // receive settings the jQuery way: that script looks the container
            // up by id and runs before Vue has put the panel in the document.
            $response->screenData(['elementEditorSettings' => $settings]);
        } elseif ($this->isSlideout()) {
            HtmlStack::jsWithVars(fn ($settings) => <<<JS
$('#$containerId').data('elementEditorSettings', $settings)
JS, [
                $settings,
            ]);
        } else {
            HtmlStack::jsWithVars(fn ($settings) => <<<JS
new Craft.ElementEditor($('#$containerId'), $settings)
JS, [
                $settings,
            ]);
        }

        // Give the element a chance to do things here too
        $element->prepareEditScreen($response, $containerId);
    }

    private function editorContent(ElementInterface $element, bool $canSave, ?string $form): string
    {
        event($event = new ElementEditorContentResolving($element, $form ?? '', ! $canSave));

        return trim($event->html);
    }

    private function editorSidebar(ElementInterface $element, bool $mergedCanonicalChanges, bool $canSave): string
    {
        $components = [];

        if ($mergedCanonicalChanges) {
            $components[] =
                Html::beginTag('div', [
                    'class' => ['meta', 'warning'],
                ]).
                Html::tag('p', t('Recent changes to the Current revision have been merged into this draft.')).
                Html::endTag('div');
        }

        $components[] = $element->getSidebarHtml(! $canSave);

        if ($this->request->route()?->getActionName()) {
            $components[] = app(ContentHtml::class)->metadataHtml($element->getMetadata());
        }

        return trim(implode("\n", $components));
    }
}
