<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldLayoutComponent;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\errors\InvalidTypeException;
use craft\errors\UnsupportedSiteException;
use craft\events\DefineElementEditorHtmlEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayoutForm;
use craft\services\Elements;
use craft\web\Controller;
use craft\web\CpScreenResponseBehavior;
use craft\web\View;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Elements controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementsController extends Controller
{
    /**
     * @event DefineElementEditorHtmlEvent The event that is triggered when rendering an element editor’s content.
     * @see _editorContent()
     */
    public const EVENT_DEFINE_EDITOR_CONTENT = 'defineEditorContent';

    /**
     * @var ElementInterface|null The element currently being managed.
     * @since 4.3.0
     */
    public ?ElementInterface $element = null;

    private array $_attributes;
    private ?string $_elementType = null;
    private ?int $_elementId = null;
    private ?string $_elementUid = null;
    private ?int $_draftId = null;
    private ?int $_revisionId = null;
    private ?int $_siteId = null;

    private ?bool $_enabled = null;
    /**
     * @var bool|bool[]|null
     */
    private array|bool|null $_enabledForSite = null;
    private ?string $_slug = null;
    private bool $_fresh;
    private ?string $_draftName = null;
    private ?string $_notes = null;
    private string $_fieldsLocation;
    private bool $_provisional;
    private bool $_dropProvisional;
    private bool $_addAnother;
    private array $_visibleLayoutElements;
    private ?string $_selectedTab = null;
    private bool $_prevalidate;
    private ?string $_context = null;
    private ?string $_thumbSize = null;
    private ?string $_viewMode = null;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->_attributes = $this->request->getBodyParams();

        // No funny business
        if (isset($this->_attributes['id']) || isset($this->_attributes['canonicalId'])) {
            throw new BadRequestHttpException('Changing an element’s ID is not allowed.');
        }

        $this->_elementType = $this->_param('elementType');
        $this->_elementId = $this->_param('elementId');
        $this->_elementUid = $this->_param('elementUid');
        $this->_draftId = $this->_param('draftId');
        $this->_revisionId = $this->_param('revisionId');
        $this->_siteId = $this->_param('siteId');
        $this->_enabled = $this->_param('enabled');
        $this->_enabledForSite = $this->_param('enabledForSite');
        $this->_slug = $this->_param('slug');
        $this->_fresh = (bool)$this->_param('fresh');
        $this->_draftName = $this->_param('draftName');
        $this->_notes = $this->_param('notes');
        $this->_fieldsLocation = $this->_param('fieldsLocation') ?? 'fields';
        $this->_provisional = (bool)$this->_param('provisional');
        $this->_dropProvisional = (bool)$this->_param('dropProvisional');
        $this->_addAnother = (bool)$this->_param('addAnother');
        $this->_visibleLayoutElements = $this->_param('visibleLayoutElements') ?? [];
        $this->_selectedTab = $this->_param('selectedTab');
        $this->_prevalidate = (bool)$this->_param('prevalidate');
        $this->_context = $this->_param('context');
        $this->_thumbSize = $this->_param('thumbSize');
        $this->_viewMode = $this->_param('viewMode');

        unset($this->_attributes['failMessage']);
        unset($this->_attributes['redirect']);
        unset($this->_attributes['successMessage']);
        unset($this->_attributes[$this->_fieldsLocation]);

        return true;
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function _param(string $name): mixed
    {
        return ArrayHelper::remove($this->_attributes, $name) ?? $this->request->getQueryParam($name);
    }

    /**
     * Redirects to an element’s edit page.
     *
     * @param int|null $elementId
     * @param string|null $elementUid
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 4.0.0
     */
    public function actionRedirect(?int $elementId = null, ?string $elementUid = null): Response
    {
        $element = $this->element = $this->_element($elementId, $elementUid);
        $url = $element->getCpEditUrl();

        if (!$url) {
            throw new ServerErrorHttpException('The element doesn’t have an edit page.');
        }

        return $this->redirect($url);
    }

    /**
     * Creates a new element and redirects to its edit page.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 4.0.0
     */
    public function actionCreate(): Response
    {
        if (!$this->_elementType) {
            throw new BadRequestHttpException('Request missing required body param.');
        }

        $this->_validateElementType($this->_elementType);

        /** @var ElementInterface $element */
        $element = $this->element = Craft::createObject($this->_elementType);
        if ($this->_siteId) {
            $element->siteId = $this->_siteId;
        }
        $element->setAttributes($this->_attributes);

        $user = static::currentUser();

        if (!Craft::$app->getElements()->canSave($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to create this element.');
        }

        if (!$element->slug) {
            $element->slug = ElementHelper::tempSlug();
        }

        // Save it
        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($element, $user->id, null, null, false)) {
            return $this->_asFailure($element, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        }

        // Redirect to its edit page
        $editUrl = $element->getCpEditUrl() ?? UrlHelper::actionUrl('elements/edit', [
            'draftId' => $element->draftId,
            'siteId' => $element->siteId,
        ]);

        $response = $this->_asSuccess(Craft::t('app', '{type} created.', [
            'type' => Craft::t('app', 'Draft'),
        ]), $element, array_filter([
            'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => '1',
            ]));
        }

        return $response;
    }

    /**
     * Returns an element edit screen.
     *
     * @param ElementInterface|null $element
     * @param int|null $elementId
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 4.0.0
     */
    public function actionEdit(?ElementInterface $element, ?int $elementId = null): Response
    {
        $this->requireCpRequest();

        $strictSite = $this->request->getAcceptsJson();

        if ($element === null) {
            /** @var Element|DraftBehavior|RevisionBehavior|Response|null $element */
            $element = $this->_element($elementId, null, true, $strictSite);

            if ($element instanceof Response) {
                return $element;
            }

            if (!$element) {
                throw new BadRequestHttpException('No element was identified by the request.');
            }

            // If this is an outdated draft, merge in the latest canonical changes
            $mergeCanonicalChanges = (
                $element::trackChanges() &&
                $element->getIsDraft() &&
                !$element->getIsUnpublishedDraft() &&
                ElementHelper::isOutdated($element)
            );
            if ($mergeCanonicalChanges) {
                Craft::$app->getElements()->mergeCanonicalChanges($element);
            }

            $this->_applyParamsToElement($element);

            // Prevalidate?
            if ($this->_prevalidate && $element->enabled && $element->getEnabledForSite()) {
                $element->setScenario(Element::SCENARIO_LIVE);
                $element->validate();
            }
        } else {
            $mergeCanonicalChanges = false;
        }

        $this->element = $element;

        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        // Figure out what we're dealing with here
        $isCanonical = $element->getIsCanonical();
        $isDraft = $element->getIsDraft();
        $isUnpublishedDraft = $element->getIsUnpublishedDraft();
        $isRevision = $element->getIsRevision();
        $isCurrent = $isCanonical || $element->isProvisionalDraft;
        $canonical = $element->getCanonical(true);

        // Site info
        $supportedSites = ElementHelper::supportedSitesForElement($element, true);
        $allEditableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
        $propSites = array_values(array_filter($supportedSites, fn($site) => $site['propagate']));
        $propSiteIds = array_column($propSites, 'siteId');
        $propEditableSiteIds = array_intersect($propSiteIds, $allEditableSiteIds);
        $isMultiSiteElement = count($supportedSites) > 1;
        $addlEditableSites = array_values(array_filter($supportedSites, fn($site) => !$site['propagate'] && in_array($site['siteId'], $allEditableSiteIds)));
        $canEditMultipleSites = count($propEditableSiteIds) > 1 || $addlEditableSites;

        // Is this a new site that isn’t supported by the canonical element yet?
        if ($isUnpublishedDraft) {
            $isNewSite = true;
        } elseif ($isDraft) {
            $isNewSite = !$element::find()
                ->id($element->getCanonicalId())
                ->siteId($element->siteId)
                ->status(null)
                ->exists();
        } else {
            $isNewSite = false;
        }

        // Permissions
        $canSave = $this->_canSave($element, $user);

        if ($isUnpublishedDraft) {
            $canSaveCanonical = $this->_canApplyUnpublishedDraft($element, $user);
        } else {
            $canSaveCanonical = ($isCanonical || $element->isProvisionalDraft) ? $canSave : $elementsService->canSave($canonical, $user);
        }

        $canCreateDrafts = $elementsService->canCreateDrafts($canonical, $user);
        $canDeleteDraft = $isDraft && !$element->isProvisionalDraft && $elementsService->canDelete($element, $user);
        $canDuplicateCanonical = $elementsService->canDuplicate($canonical, $user);
        $canDeleteCanonical = $elementsService->canDelete($canonical, $user);
        $canDeleteForSite = (
            $isMultiSiteElement &&
            count($propSiteIds) > 1 &&
            (($isCurrent && $canDeleteCanonical) || ($canDeleteDraft && $isNewSite)) &&
            $elementsService->canDeleteForSite($element, $user)
        );

        // Preview targets
        $previewTargets = (
            $element->id &&
            (
                ($isDraft && $canSave) ||
                ($isCurrent && $canCreateDrafts)
            )
        ) ? $element->getPreviewTargets() : [];
        $enablePreview = $previewTargets && !$this->request->isMobileBrowser(true);

        if ($previewTargets) {
            if ($isDraft && !$element->isProvisionalDraft) {
                Craft::$app->getSession()->authorize("previewDraft:$element->draftId");
            } elseif ($isRevision) {
                Craft::$app->getSession()->authorize("previewRevision:$element->revisionId");
            } else {
                Craft::$app->getSession()->authorize("previewElement:$canonical->id");
            }
        }

        // Screen prep
        [$docTitle, $title] = $this->_editElementTitles($element);
        $type = $element::lowerDisplayName();
        $enabledForSite = $element->getEnabledForSite();
        $hasRoute = $element->getRoute() !== null;
        $redirectUrl = $element->getPostEditUrl() ?? Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect();

        // Site statuses
        if ($canEditMultipleSites) {
            $siteStatuses = ElementHelper::siteStatusesForElement($element, true);
        } else {
            $siteStatuses = [
                $element->siteId => $element->enabled,
            ];
        }

        $security = Craft::$app->getSecurity();

        $response = $this->asCpScreen()
            ->editUrl($element->getCpEditUrl())
            ->docTitle($docTitle)
            ->title($title)
            ->contextMenu(fn() => $this->_contextMenu(
                $element,
                $isMultiSiteElement,
                $isUnpublishedDraft,
                $propSiteIds
            ))
            ->additionalButtons(fn() => $this->_additionalButtons(
                $element,
                $canonical,
                $isRevision,
                $canSave,
                $canSaveCanonical,
                $canCreateDrafts,
                $previewTargets,
                $enablePreview,
                $isCurrent,
                $isUnpublishedDraft,
                $isDraft
            ))
            ->notice($element->isProvisionalDraft ? fn() => $this->_draftNotice() : null)
            ->prepareScreen(
                fn(Response $response, string $containerId) => $this->_prepareEditor(
                    $element,
                    $canSave,
                    $response,
                    $containerId,
                    fn(?FieldLayoutForm $form) => $this->_editorContent($element, $isUnpublishedDraft, $canSave, $form),
                    fn(?FieldLayoutForm $form) => $this->_editorSidebar($element, $mergeCanonicalChanges, $canSave),
                    fn(?FieldLayoutForm $form) => [
                        'additionalSites' => $addlEditableSites,
                        'canCreateDrafts' => $canCreateDrafts,
                        'canEditMultipleSites' => $canEditMultipleSites,
                        'canSaveCanonical' => $canSaveCanonical,
                        'canonicalId' => $canonical->id,
                        'draftId' => $element->draftId,
                        'draftName' => $isDraft ? $element->draftName : null,
                        'elementType' => get_class($element),
                        'enablePreview' => $enablePreview,
                        'enabledForSite' => $element->enabled && $enabledForSite,
                        'hashedCpEditUrl' => $security->hashData('{cpEditUrl}'),
                        'isLive' => $isCurrent && !$element->getIsDraft() && $element->enabled && $enabledForSite && $hasRoute,
                        'isProvisionalDraft' => $element->isProvisionalDraft,
                        'isUnpublishedDraft' => $isUnpublishedDraft,
                        'previewTargets' => $previewTargets,
                        'previewToken' => $previewTargets ? $security->generateRandomString() : null,
                        'revisionId' => $element->revisionId,
                        'siteId' => $element->siteId,
                        'siteStatuses' => $siteStatuses,
                        'siteToken' => (!Craft::$app->getIsLive() || !$element->getSite()->enabled) ? $security->hashData((string)$element->siteId) : null,
                        'visibleLayoutElements' => $form ? $form->getVisibleElements() : [],
                    ]
                )
            );

        if ($canSave) {
            if ($isUnpublishedDraft) {
                if ($canSaveCanonical) {
                    $response
                        ->submitButtonLabel(Craft::t('app', 'Create {type}', [
                            'type' => $element::lowerDisplayName(),
                        ]))
                        ->action('elements/apply-draft')
                        ->redirectUrl("$redirectUrl#");
                } else {
                    $response
                        ->action('elements/save-draft')
                        ->redirectUrl("$redirectUrl#");
                }
            } elseif ($element->isProvisionalDraft) {
                $response
                    ->action('elements/apply-draft')
                    ->redirectUrl("$redirectUrl#");
            } elseif ($isDraft) {
                $response
                    ->submitButtonLabel(Craft::t('app', 'Save {type}', [
                        'type' => Craft::t('app', 'draft'),
                    ]))
                    ->action('elements/save-draft')
                    ->redirectUrl("{cpEditUrl}");
            } else {
                $response
                    ->action('elements/save')
                    ->redirectUrl("$redirectUrl#");
            }

            $response
                ->saveShortcutRedirectUrl('{cpEditUrl}')
                ->addAltAction(
                    $isUnpublishedDraft && $canSaveCanonical
                        ? Craft::t('app', 'Create and continue editing')
                        : Craft::t('app', 'Save and continue editing'),
                    [
                        'redirect' => '{cpEditUrl}',
                        'shortcut' => true,
                        'retainScroll' => true,
                        'eventData' => ['autosave' => false],
                    ]
                );

            if ($isCurrent) {
                $newElement = $element->createAnother();
                if ($newElement && $elementsService->canSave($newElement, $user)) {
                    $response->addAltAction(
                        $isUnpublishedDraft && $canSaveCanonical
                            ? Craft::t('app', 'Create and add another')
                            : Craft::t('app', 'Save and add another'),
                        [
                            'shortcut' => true,
                            'shift' => true,
                            'eventData' => ['autosave' => false],
                            'params' => ['addAnother' => 1],
                        ]
                    );
                }

                if ($canSaveCanonical) {
                    if ($isUnpublishedDraft) {
                        $response->addAltAction(Craft::t('app', 'Save {type}', [
                            'type' => Craft::t('app', 'draft'),
                        ]), [
                            'action' => 'elements/save-draft',
                            'redirect' => "$redirectUrl#",
                            'eventData' => ['autosave' => false],
                        ]);
                    } elseif ($canDuplicateCanonical) {
                        $response->addAltAction(Craft::t('app', 'Save as a new {type}', compact('type')), [
                            'action' => 'elements/duplicate', // todo
                            'redirect' => '{cpEditUrl}',
                        ]);
                    }
                }

                if ($canDeleteForSite) {
                    $response->addAltAction(Craft::t('app', 'Delete {type} for this site', [
                        'type' => $isUnpublishedDraft ? Craft::t('app', 'draft') : $type,
                    ]), [
                        'destructive' => true,
                        'action' => 'elements/delete-for-site',
                        'redirect' => "$redirectUrl#",
                        'confirm' => Craft::t('app', 'Are you sure you want to delete the {type} for this site?', [
                            'type' => $isUnpublishedDraft ? Craft::t('app', 'draft') : $type,
                        ]),
                    ]);
                }

                if ($canDeleteCanonical) {
                    $response->addAltAction(Craft::t('app', 'Delete {type}', [
                        'type' => $isUnpublishedDraft ? Craft::t('app', 'draft') : $type,
                    ]), [
                        'destructive' => true,
                        'action' => $isUnpublishedDraft ? 'elements/delete-draft' : 'elements/delete',
                        'redirect' => "$redirectUrl#",
                        'confirm' => Craft::t('app', 'Are you sure you want to delete this {type}?', [
                            'type' => $isUnpublishedDraft ? Craft::t('app', 'draft') : $type,
                        ]),
                    ]);
                }
            } elseif ($isDraft && $canDeleteDraft) {
                if ($canDeleteForSite) {
                    $response->addAltAction(Craft::t('app', 'Delete {type} for this site', [
                        'type' => Craft::t('app', 'draft'),
                    ]), [
                        'destructive' => true,
                        'action' => 'elements/delete-for-site',
                        'redirect' => "$redirectUrl#",
                        'confirm' => Craft::t('app', 'Are you sure you want to delete the {type} for this site?', compact('type')),
                    ]);
                }

                $response->addAltAction(Craft::t('app', 'Delete {type}', [
                    'type' => Craft::t('app', 'draft'),
                ]), [
                    'destructive' => true,
                    'action' => 'elements/delete-draft',
                    'redirect' => $canonical->getCpEditUrl(),
                    'confirm' => Craft::t('app', 'Are you sure you want to delete this {type}?', [
                        'type' => Craft::t('app', 'draft'),
                    ]),
                ]);
            }
        }

        return $response;
    }

    /**
     * Returns an element revisions index screen.
     *
     * @param int $elementId
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 4.4.0
     */
    public function actionRevisions(int $elementId): Response
    {
        $this->requireCpRequest();

        /** @var Element|DraftBehavior|RevisionBehavior|Response|null $element */
        $element = $this->_element($elementId, null, false);

        if (!$element) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        if ($element->getIsUnpublishedDraft()) {
            throw new BadRequestHttpException('Unpublished drafts don\'t have revisions');
        }

        if (!$element->hasRevisions()) {
            throw new BadRequestHttpException('Element doesn\'t have revisions');
        }

        return $this->asCpScreen()
            ->title(Craft::t('app', 'Revisions for “{title}”', [
                'title' => $element->getUiLabel(),
            ]))
            ->prepareScreen(function(Response $response, string $containerId) use ($element) {
                // Give the element a chance to do things here too
                $element->prepareEditScreen($response, $containerId);

                /** @var CpScreenResponseBehavior $behavior */
                $behavior = $response->getBehavior(CpScreenResponseBehavior::NAME);
                if (!empty($behavior->crumbs)) {
                    $behavior->crumbs[] = [
                        'label' => $element->getUiLabel(),
                        'url' => $element->getCpEditUrl(),
                    ];
                }
            })
        ->contentTemplate('_elements/revisions', [
            'element' => $element,
            'revisionsQuery' => $element::find()
                ->revisionOf($element)
                ->site('*')
                ->preferSites([$element->siteId])
                ->unique()
                ->status(null)
                ->andWhere(['!=', 'elements.dateCreated', Db::prepareDateForDb($element->dateUpdated)])
                ->with(['revisionCreator']),
        ]);
    }

    /**
     * Returns the page title and document title that should be used for Edit Element pages.
     *
     * @param ElementInterface $element
     * @return string[]
     * @since 3.7.0
     */
    private function _editElementTitles(ElementInterface $element): array
    {
        $title = trim((string)$element->title);

        if ($title === '') {
            if (!$element->id || $element->getIsUnpublishedDraft()) {
                $title = Craft::t('app', 'Create a new {type}', [
                    'type' => $element::lowerDisplayName(),
                ]);
            } else {
                $title = Craft::t('app', 'Edit {type}', [
                    'type' => $element::displayName(),
                ]);
            }
        }

        $docTitle = $title;

        if ($element->getIsDraft()) {
            /** @var ElementInterface|DraftBehavior $element */
            if ($element->isProvisionalDraft) {
                $docTitle .= ' — ' . Craft::t('app', 'Edited');
            } else {
                $docTitle .= " ($element->draftName)";
            }
        } elseif ($element->getIsRevision()) {
            /** @var ElementInterface|RevisionBehavior $element */
            $docTitle .= ' (' . $element->getRevisionLabel() . ')';
        }

        return [$docTitle, $title];
    }

    private function _contextMenu(
        ElementInterface $element,
        bool $isMultiSiteElement,
        bool $isUnpublishedDraft,
        array $propSiteIds,
    ): ?string {
        $showDrafts = !$isUnpublishedDraft;

        if (
            $isMultiSiteElement ||
            $showDrafts ||
            ($element->hasRevisions() && $element::find()->revisionOf($element)->status(null)->exists())
        ) {
            return Craft::$app->getView()->renderTemplate('_includes/revisionmenu.twig', [
                'element' => $element,
                'showDrafts' => $showDrafts,
                'supportedSiteIds' => $propSiteIds,
                'showSiteLabel' => $isMultiSiteElement,
            ], View::TEMPLATE_MODE_CP);
        }

        return null;
    }

    private function _additionalButtons(
        ElementInterface $element,
        ElementInterface $canonical,
        bool $isRevision,
        bool $canSave,
        bool $canSaveCanonical,
        bool $canCreateDrafts,
        ?array $previewTargets,
        bool $enablePreview,
        bool $isCurrent,
        bool $isUnpublishedDraft,
        bool $isDraft,
    ): string {
        $components = [];

        // Preview (View will be added later by JS)
        if ($canSave && $previewTargets) {
            $components[] =
                Html::beginTag('div', [
                    'class' => ['preview-btn-container', 'btngroup'],
                ]) .
                ($enablePreview
                    ? Html::beginTag('button', [
                        'type' => 'button',
                        'class' => ['preview-btn', 'btn'],
                        'aria' => [
                            'label' => Craft::t('app', 'Preview'),
                        ],
                    ]) .
                    Html::tag('span', Craft::t('app', 'Preview'), ['class' => 'label']) .
                    Html::tag('span', options: ['class' => ['spinner', 'spinner-absolute']]) .
                    Html::endTag('button')
                    : '') .
                Html::endTag('div');
        }

        // Create a draft
        if ($isCurrent && !$isUnpublishedDraft && $canCreateDrafts) {
            if ($canSave) {
                $components[] = Html::button(Craft::t('app', 'Create a draft'), [
                    'class' => ['btn', 'formsubmit'],
                    'data' => [
                        'action' => 'elements/save-draft',
                        'redirect' => Craft::$app->getSecurity()->hashData('{cpEditUrl}'),
                        'params' => ['dropProvisional' => 1],
                    ],
                ]);
            } else {
                $components[] = Html::beginForm() .
                    Html::actionInput('elements/save-draft') .
                    Html::redirectInput('{cpEditUrl}') .
                    Html::hiddenInput('elementId', (string)$canonical->id) .
                    Html::beginTag('div', ['class' => 'secondary-buttons']) .
                    Html::button(Craft::t('app', 'Create a draft'), [
                        'class' => ['btn', 'secondary', 'formsubmit'],
                    ]) .
                    Html::endTag('div') .
                    Html::endForm();
            }
        }

        // Apply draft
        if ($isDraft && !$isCurrent && $canSave && $canSaveCanonical) {
            $components[] = Html::button(Craft::t('app', 'Apply draft'), [
                'class' => ['btn', 'secondary', 'formsubmit'],
                'data' => [
                    'action' => 'elements/apply-draft',
                    'redirect' => Craft::$app->getSecurity()->hashData('{cpEditUrl}'),
                ],
            ]);
        }

        // Revert content from this revision
        if ($isRevision && $canSaveCanonical) {
            $components[] = Html::beginForm() .
                Html::actionInput('elements/revert') .
                Html::redirectInput('{cpEditUrl}') .
                Html::hiddenInput('elementId', (string)$canonical->id) .
                Html::hiddenInput('revisionId', (string)$element->revisionId) .
                Html::beginTag('div', ['class' => 'secondary-buttons']) .
                Html::button(Craft::t('app', 'Revert content from this revision'), [
                    'class' => ['btn', 'secondary', 'formsubmit'],
                ]) .
                Html::endTag('div') .
                Html::endForm();
        }

        $components[] = $element->getAdditionalButtons();

        return implode("\n", array_filter($components));
    }

    private function _prepareEditor(
        ElementInterface $element,
        bool $canSave,
        Response $response,
        string $containerId,
        callable $contentFn,
        callable $sidebarFn,
        callable $jsSettingsFn,
    ) {
        $fieldLayout = $element->getFieldLayout();
        $form = $fieldLayout?->createForm($element, !$canSave, [
            'registerDeltas' => true,
        ]);

        /** @var Response|CpScreenResponseBehavior $response */
        $response
            ->tabs($form?->getTabMenu() ?? [])
            ->content($contentFn($form))
            ->sidebar($sidebarFn($form));

        if ($canSave && !$element->getIsRevision()) {
            $this->view->registerJsWithVars(fn($settingsJs) => <<<JS
new Craft.ElementEditor($('#$containerId'), $settingsJs);
JS, [
                $jsSettingsFn($form),
            ]);
        }

        // Give the element a chance to do things here too
        $element->prepareEditScreen($response, $containerId);
    }

    private function _editorContent(
        ElementInterface $element,
        bool $isUnpublishedDraft,
        bool $canSave,
        ?FieldLayoutForm $form,
    ): string {
        $components = [];

        if ($form) {
            $components[] = $form->render();
        }

        if ($canSave) {
            if ($element->id) {
                $components[] = Html::hiddenInput('elementId', (string)$element->getCanonicalId());
            }

            if ($element->siteId) {
                $components[] = Html::hiddenInput('siteId', (string)$element->siteId);
            }

            if ($element->fieldLayoutId) {
                $components[] = Html::hiddenInput('fieldLayoutId', (string)$element->fieldLayoutId);
            }

            if ($isUnpublishedDraft && $this->_fresh) {
                $components[] = Html::hiddenInput('fresh', '1');
            }
        }

        $html = implode("\n", $components);

        // Trigger a defineEditorContent event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_EDITOR_CONTENT)) {
            $event = new DefineElementEditorHtmlEvent([
                'element' => $element,
                'html' => $html,
                'static' => !$canSave,
            ]);
            $this->trigger(self::EVENT_DEFINE_EDITOR_CONTENT, $event);
            $html = $event->html;
        }

        return $html;
    }

    private function _editorSidebar(
        ElementInterface $element,
        bool $mergedCanonicalChanges,
        bool $canSave,
    ): string {
        $components = [];

        if ($mergedCanonicalChanges) {
            $components[] =
                Html::beginTag('div', [
                    'class' => ['meta', 'warning'],
                ]) .
                Html::tag('p', Craft::t('app', 'Recent changes to the Current revision have been merged into this draft.')) .
                Html::endTag('div');
        }

        /** @var ElementInterface|DraftBehavior|RevisionBehavior $element */
        $components[] = $element->getSidebarHtml(!$canSave);

        if ($this->id) {
            $components[] = Cp::metadataHtml($element->getMetadata());
        }

        return implode("\n", $components);
    }

    private function _draftNotice(): string
    {
        return
            Html::beginTag('div', [
                'class' => 'draft-notice',
            ]) .
            Html::tag('div', '', [
                'class' => ['draft-icon'],
                'aria' => ['hidden' => 'true'],
                'data' => ['icon' => 'edit'],
            ]) .
            Html::tag('p', Craft::t('app', 'Showing your unsaved changes.')) .
            Html::button(Craft::t('app', 'Discard'), [
                'class' => ['discard-changes-btn', 'btn'],
            ]) .
            Html::endTag('div');
    }

    /**
     * Saves an element.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 4.0.0
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        /** @var Element|null $element */
        $element = $this->_element();

        if (!$element || $element->getIsDraft() || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;

        $this->_applyParamsToElement($element);
        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        if (!$elementsService->canSave($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to save this element.');
        }

        if ($element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        $isNotNew = $element->id;
        if ($isNotNew) {
            $lockKey = "element:$element->id";
            $mutex = Craft::$app->getMutex();
            if (!$mutex->acquire($lockKey, 15)) {
                throw new ServerErrorHttpException('Could not acquire a lock to save the element.');
            }
        }

        try {
            $success = $elementsService->saveElement($element);
        } catch (UnsupportedSiteException $e) {
            $element->addError('siteId', $e->getMessage());
            $success = false;
        } finally {
            if ($isNotNew) {
                $mutex->release($lockKey);
            }
        }

        if (!$success) {
            return $this->_asFailure($element, Craft::t('app', 'Couldn’t save {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        }

        // See if the user happens to have a provisional element. If so delete it.
        $provisional = $element::find()
            ->provisionalDrafts()
            ->draftOf($element->id)
            ->draftCreator($user)
            ->siteId($element->siteId)
            ->status(null)
            ->one();

        if ($provisional) {
            $elementsService->deleteElement($provisional, true);
        }

        if (!$this->request->getAcceptsJson()) {
            // Tell all browser windows about the element save
            Craft::$app->getSession()->broadcastToJs([
                'event' => 'saveElement',
                'id' => $element->id,
            ]);
        }

        return $this->_asSuccess(Craft::t('app', '{type} saved.', [
            'type' => $element::displayName(),
        ]), $element, addAnother: true);
    }

    /**
     * Duplicates an element.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 4.0.0
     */
    public function actionDuplicate(): ?Response
    {
        $this->requirePostRequest();

        /** @var Element|DraftBehavior|null $element */
        $element = $this->_element();

        if (
            !$element ||
            ($element->getIsDraft() && !$element->isProvisionalDraft && !$element->getIsUnpublishedDraft()) ||
            $element->getIsRevision()
        ) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;

        $elementsService = Craft::$app->getElements();

        if (!$elementsService->canDuplicate($element)) {
            throw new ForbiddenHttpException('User not authorized to duplicate this element.');
        }

        $clonedElement = clone $element;
        $clonedElement->draftId = null;
        $clonedElement->isProvisionalDraft = false;

        try {
            $newElement = $elementsService->duplicateElement($clonedElement);
        } catch (InvalidElementException $e) {
            return $this->_asFailure($e->element, Craft::t('app', 'Couldn’t duplicate {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        } catch (Throwable $e) {
            throw new ServerErrorHttpException('An error occurred when duplicating the element.', 0, $e);
        }

        // If the original element is a provisional draft,
        // delete the draft as the changes are likely no longer wanted.
        if ($element->isProvisionalDraft) {
            Craft::$app->getElements()->deleteElement($element);
        }

        return $this->_asSuccess(Craft::t('app', '{type} duplicated.', [
            'type' => $element::displayName(),
        ]), $newElement);
    }

    /**
     * Deletes an element.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 4.0.0
     */
    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();

        /** @var Element|null $element */
        $element = $this->_element();

        // If this is a provisional draft, delete the canonical
        if ($element && $element->isProvisionalDraft) {
            $element = $element->getcanonical(true);
        }

        if (!$element || $element->getIsDraft() || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;

        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        if (!$elementsService->canDelete($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to delete this element.');
        }

        if (!$elementsService->deleteElement($element)) {
            return $this->_asFailure($element, Craft::t('app', 'Couldn’t delete {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        }

        return $this->_asSuccess(Craft::t('app', '{type} deleted.', [
            'type' => $element::displayName(),
        ]), $element);
    }

    /**
     * Deletes an element for a single site.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 4.0.0
     */
    public function actionDeleteForSite(): Response
    {
        $this->requirePostRequest();

        /** @var Element|null $element */
        $element = $this->_element();

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;

        $elementsService = Craft::$app->getElements();

        if (!$elementsService->canDeleteForSite($element)) {
            throw new ForbiddenHttpException('User not authorized to delete the element for this site.');
        }

        $elementsService->deleteElementForSite($element);

        return $this->_asSuccess(Craft::t('app', '{type} deleted for site.', [
            'type' => $element->getIsDraft() && !$element->isProvisionalDraft ? Craft::t('app', 'Draft') : $element::displayName(),
        ]), $element);
    }

    /**
     * Saves a draft.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 4.0.0
     */
    public function actionSaveDraft(): ?Response
    {
        $this->requirePostRequest();

        /** @var Element|DraftBehavior|null $element */
        $element = $this->_element();

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        if (!$element->getIsDraft() && !$this->_provisional) {
            if (!$elementsService->canCreateDrafts($element, $user)) {
                throw new ForbiddenHttpException('User not authorized to create drafts for this element.');
            }
        } elseif (!$this->_canSave($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to save this element.');
        }

        $this->element = $element;

        if (!$element->getIsDraft() && $this->_provisional) {
            // Make sure a provisional draft doesn't already exist for this element/user combo
            $provisionalExists = $element::find()
                ->provisionalDrafts()
                ->draftOf($element->id)
                ->draftCreator($user->id)
                ->site('*')
                ->status(null)
                ->exists();

            if ($provisionalExists) {
                throw new BadRequestHttpException("A provisional draft already exists for element/user $element->id/$user->id.");
            }
        }

        return Craft::$app->getDb()->transaction(function() use ($element, $user, $elementsService): ?Response {
            // Are we creating the draft here?
            if (!$element->getIsDraft()) {
                /** @var Element|DraftBehavior $element */
                $draft = Craft::$app->getDrafts()->createDraft($element, $user->id, null, null, [], $this->_provisional);
                $draft->setCanonical($element);
                $element = $this->element = $draft;
            }

            $this->_applyParamsToElement($element);

            // Make sure nothing just changed that would prevent the user from saving
            if (!$this->_canSave($element, $user)) {
                throw new ForbiddenHttpException('User not authorized to save this element.');
            }

            if ($this->_dropProvisional) {
                $element->isProvisionalDraft = false;
            }

            $element->setScenario(Element::SCENARIO_ESSENTIALS);

            if (!$elementsService->saveElement($element)) {
                return $this->_asFailure($element, Craft::t('app', 'Couldn’t save {type}.', [
                    'type' => Craft::t('app', 'draft'),
                ]));
            }

            $creator = $element->getCreator();

            $data = [
                'canonicalId' => $element->getCanonicalId(),
                'draftId' => $element->draftId,
                'timestamp' => Craft::$app->getFormatter()->asTimestamp($element->dateUpdated, 'short', true),
                'creator' => $creator?->getName(),
                'draftName' => $element->draftName,
                'draftNotes' => $element->draftNotes,
                'duplicatedElements' => Elements::$duplicatedElementIds,
                'modifiedAttributes' => $element->getModifiedAttributes(),
            ];

            if ($this->request->getIsCpRequest()) {
                [$docTitle, $title] = $this->_editElementTitles($element);

                $view = Craft::$app->getView();

                $namespace = $this->request->getHeaders()->get('X-Craft-Namespace');
                $fieldLayout = $element->getFieldLayout();
                $form = $fieldLayout->createForm($element, false, [
                    'namespace' => $namespace,
                    'registerDeltas' => false,
                    'visibleElements' => $this->_visibleLayoutElements,
                ]);
                $missingElements = [];
                foreach ($form->tabs as $tab) {
                    if (!$tab->getUid()) {
                        continue;
                    }

                    $elementInfo = [];

                    foreach ($tab->elements as [$layoutElement, $isConditional, $elementHtml]) {
                        /** @var FieldLayoutComponent $layoutElement */
                        /** @var bool $isConditional */
                        /** @var string|bool $elementHtml */
                        if ($isConditional) {
                            $elementInfo[] = [
                                'uid' => $layoutElement->uid,
                                'html' => $elementHtml,
                            ];
                        }
                    }

                    $missingElements[] = [
                        'uid' => $tab->getUid(),
                        'id' => $tab->getId(),
                        'elements' => $elementInfo,
                    ];
                }

                $tabs = $form->getTabMenu();
                if (count($tabs) > 1) {
                    $selectedTab = isset($tabs[$this->_selectedTab]) ? $this->_selectedTab : null;
                    $tabHtml = $view->namespaceInputs(fn() => $view->renderTemplate('_includes/tabs.twig', [
                        'tabs' => $tabs,
                        'selectedTab' => $selectedTab,
                    ], View::TEMPLATE_MODE_CP), $namespace);
                } else {
                    $tabHtml = null;
                }

                $data += [
                    'docTitle' => $docTitle,
                    'title' => $title,
                    'tabs' => $tabHtml,
                    'previewTargets' => $element->getPreviewTargets(),
                    'missingElements' => $missingElements,
                    'initialDeltaValues' => $view->getInitialDeltaValues(),
                    'headHtml' => $view->getHeadHtml(),
                    'bodyHtml' => $view->getBodyHtml(),
                ];
            }

            // Make sure the user is authorized to preview the draft
            Craft::$app->getSession()->authorize("previewDraft:$element->draftId");

            return $this->_asSuccess(Craft::t('app', '{type} saved.', [
                'type' => Craft::t('app', 'Draft'),
            ]), $element, $data);
        });
    }

    /**
     * Applies a draft to its canonical element.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 4.0.0
     */
    public function actionApplyDraft(): ?Response
    {
        $this->requirePostRequest();
        $elementsService = Craft::$app->getElements();

        /** @var Element|DraftBehavior|null $element */
        $element = $this->_element();

        if (!$element || !$element->getIsDraft()) {
            throw new BadRequestHttpException('No draft was identified by the request.');
        }

        $this->element = $element;

        $this->_applyParamsToElement($element);
        $user = static::currentUser();

        if (!$elementsService->canSave($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to save this draft.');
        }

        $isUnpublishedDraft = $element->getIsUnpublishedDraft();

        if ($isUnpublishedDraft) {
            if (!$this->_canApplyUnpublishedDraft($element, $user)) {
                throw new ForbiddenHttpException('User not authorized to create this element.');
            }
        } elseif (!$elementsService->canSave($element->getCanonical(true), $user)) {
            throw new ForbiddenHttpException('User not authorized to save this element.');
        }

        // Validate and save the draft
        if ($element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        if (!$elementsService->saveElement($element)) {
            return $this->_asAppyDraftFailure($element);
        }

        if (!$isUnpublishedDraft) {
            $lockKey = "element:$element->canonicalId";
            $mutex = Craft::$app->getMutex();
            if (!$mutex->acquire($lockKey, 15)) {
                throw new ServerErrorHttpException('Could not acquire a lock to save the element.');
            }
        }

        try {
            $canonical = Craft::$app->getDrafts()->applyDraft($element);
        } catch (InvalidElementException) {
            return $this->_asAppyDraftFailure($element);
        } finally {
            if (!$isUnpublishedDraft) {
                $mutex->release($lockKey);
            }
        }

        if (!$this->request->getAcceptsJson()) {
            // Tell all browser windows about the element save
            $session = Craft::$app->getSession();
            $session->broadcastToJs([
                'event' => 'saveElement',
                'id' => $canonical->id,
            ]);
            if (!$isUnpublishedDraft) {
                $session->broadcastToJs([
                    'event' => 'deleteDraft',
                    'canonicalId' => $element->getCanonicalId(),
                    'draftId' => $element->draftId,
                ]);
            }
        }

        if ($isUnpublishedDraft) {
            $message = Craft::t('app', '{type} created.', [
                'type' => $element::displayName(),
            ]);
        } elseif ($element->isProvisionalDraft) {
            $message = Craft::t('app', '{type} saved.', [
                'type' => $element::displayName(),
            ]);
        } else {
            $message = Craft::t('app', 'Draft applied.');
        }

        return $this->_asSuccess($message, $canonical, addAnother: true);
    }

    private function _asAppyDraftFailure(ElementInterface $element): ?Response
    {
        if ($element->getIsUnpublishedDraft()) {
            $message = Craft::t('app', 'Couldn’t create {type}.', [
                'type' => $element::lowerDisplayName(),
            ]);
        } elseif ($element->isProvisionalDraft) {
            $message = Craft::t('app', 'Couldn’t save {type}.', [
                'type' => $element::lowerDisplayName(),
            ]);
        } else {
            $message = Craft::t('app', 'Couldn’t apply draft.');
        }

        return $this->_asFailure($element, $message);
    }

    /**
     * Deletes a draft.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 4.0.0
     */
    public function actionDeleteDraft(): ?Response
    {
        $this->requirePostRequest();

        /** @var Element|DraftBehavior|null $element */
        $element = $this->_element();

        if (!$element || !$element->getIsDraft()) {
            throw new BadRequestHttpException('No draft was identified by the request.');
        }

        $this->element = $element;

        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        if (!$elementsService->canDelete($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to delete this draft.');
        }

        if (!$elementsService->deleteElement($element, true)) {
            return $this->_asFailure($element, Craft::t('app', 'Couldn’t delete {type}.', [
                'type' => Craft::t('app', 'draft'),
            ]));
        }

        if ($element->isProvisionalDraft) {
            $message = Craft::t('app', 'Changes discarded.');
        } else {
            $message = Craft::t('app', '{type} deleted.', [
                'type' => Craft::t('app', 'Draft'),
            ]);
        }

        if (!$this->request->getAcceptsJson()) {
            // Tell all browser windows about the draft deletion
            Craft::$app->getSession()->broadcastToJs([
                'event' => 'deleteDraft',
                'canonicalId' => $element->getCanonicalId(),
                'draftId' => $element->draftId,
            ]);
        }

        return $this->_asSuccess($message, $element);
    }

    /**
     * Reverts an element’s content to a revision.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 4.0.0
     */
    public function actionRevert(): Response
    {
        $this->requirePostRequest();

        /** @var Element|RevisionBehavior|null $element */
        $element = $this->_element();

        if (!$element || !$element->getIsRevision()) {
            throw new BadRequestHttpException('No revision was identified by the request.');
        }

        $this->element = $element;

        $user = static::currentUser();

        if (!Craft::$app->getElements()->canSave($element->getCanonical(true), $user)) {
            throw new ForbiddenHttpException('User not authorized to save this element.');
        }

        $canonical = Craft::$app->getRevisions()->revertToRevision($element, $user->id);

        return $this->_asSuccess(Craft::t('app', '{type} reverted to past revision.', [
            'type' => $element::displayName(),
        ]), $canonical);
    }

    /**
     * Returns the HTML for a single element
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionGetElementHtml(): Response
    {
        $this->requireAcceptsJson();

        $element = $this->_element();

        if (!$element) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;

        $context = $this->_context ?? 'field';
        $thumbSize = $this->_thumbSize;

        if (!in_array($thumbSize, [Cp::ELEMENT_SIZE_SMALL, Cp::ELEMENT_SIZE_LARGE], true)) {
            $thumbSize = $this->_viewMode === 'thumbs' ? Cp::ELEMENT_SIZE_LARGE : Cp::ELEMENT_SIZE_SMALL;
        }

        $html = Cp::elementHtml($element, $context, $thumbSize);
        $headHtml = $this->getView()->getHeadHtml();

        return $this->asJson(compact('html', 'headHtml'));
    }

    /**
     * Returns the requested element, populated with any posted attributes.
     *
     * @param int|null $elementId
     * @param string|null $elementUid
     * @param bool|null $provisional
     * @param bool $strictSite
     * @return ElementInterface|Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    private function _element(?int $elementId = null, ?string $elementUid = null, ?bool $provisional = null, bool $strictSite = true): ElementInterface|Response|null
    {
        $elementId = $elementId ?? $this->_elementId;
        $elementUid = $elementUid ?? $this->_elementUid;

        $sitesService = Craft::$app->getSites();
        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        if ($this->_siteId) {
            $site = $sitesService->getSiteById($this->_siteId, true);
            if (!$site) {
                throw new BadRequestHttpException("Invalid side ID: $this->_siteId");
            }
            if (Craft::$app->getIsMultiSite() && !$user->can("editSite:$site->uid")) {
                throw new ForbiddenHttpException('User not authorized to edit content for this site.');
            }
        } else {
            $site = Cp::requestedSite();
            if (!$site) {
                throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
            }
        }

        if ($this->_elementType) {
            $elementType = $this->_elementType;
        } elseif ($elementId || $elementUid) {
            if ($elementId) {
                $elementType = $elementsService->getElementTypeById($elementId);
            } else {
                $elementType = $elementsService->getElementTypeByUid($elementUid);
            }
            if (!$elementType) {
                throw new BadRequestHttpException($elementId ? "Invalid element ID: $elementId" : "Invalid element UUID: $elementUid");
            }
        } else {
            throw new BadRequestHttpException('Request missing required param.');
        }

        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $this->_validateElementType($elementType);

        if ($strictSite) {
            $siteId = $site->id;
            $preferSites = null;
        } else {
            $siteId = $sitesService->getEditableSiteIds();
            $preferSites = [$site->id];
        }

        // Loading an existing element?
        if ($this->_draftId || $this->_revisionId) {
            $element = $elementType::find()
                ->draftId($this->_draftId)
                ->revisionId($this->_revisionId)
                ->provisionalDrafts($this->_provisional)
                ->siteId($siteId)
                ->preferSites($preferSites)
                ->unique()
                ->status(null)
                ->one();

            if (!$element) {
                throw new BadRequestHttpException($this->_draftId ? "Invalid draft ID: $this->_draftId" : "Invalid revision ID: $this->_revisionId");
            }
        } elseif ($elementId || $elementUid) {
            if ($elementId) {
                // First check for a provisional draft, if we're open to it
                if ($provisional) {
                    $element = $elementType::find()
                        ->provisionalDrafts()
                        ->draftOf($elementId)
                        ->draftCreator($user)
                        ->siteId($siteId)
                        ->preferSites($preferSites)
                        ->unique()
                        ->status(null)
                        ->one();
                }

                if (!isset($element) || !$this->_canSave($element, $user)) {
                    $element = $elementType::find()
                        ->id($elementId)
                        ->siteId($siteId)
                        ->preferSites($preferSites)
                        ->unique()
                        ->status(null)
                        ->one();
                }
            } else {
                $element = $elementType::find()
                    ->uid($elementUid)
                    ->siteId($siteId)
                    ->preferSites($preferSites)
                    ->unique()
                    ->status(null)
                    ->one();
            }

            if (!$element) {
                throw new BadRequestHttpException($elementId ? "Invalid element ID: $elementId" : "Invalid element UUID: $elementUid");
            }
        } else {
            return null;
        }

        if (!$element->canView(static::currentUser())) {
            throw new ForbiddenHttpException('User not authorized to edit this element.');
        }

        if (!$strictSite && $element->siteId !== $site->id) {
            return $this->redirect($element->getCpEditUrl());
        }

        return $element;
    }

    /**
     * Ensures the given element type is valid.
     *
     * @param string $elementType
     * @phpstan-param class-string<ElementInterface> $elementType
     * @throws BadRequestHttpException
     */
    private function _validateElementType(string $elementType): void
    {
        if (!Component::validateComponentClass($elementType, ElementInterface::class)) {
            $message = (new InvalidTypeException($elementType, ElementInterface::class))->getMessage();
            throw new BadRequestHttpException($message);
        }
    }

    /**
     * Applies the request params to the given element.
     *
     * @param ElementInterface $element
     * @throws ForbiddenHttpException
     */
    private function _applyParamsToElement(ElementInterface $element): void
    {
        if (isset($this->_enabledForSite)) {
            if (is_array($this->_enabledForSite)) {
                // Make sure they are allowed to edit all of the posted site IDs
                $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
                if (array_diff(array_keys($this->_enabledForSite), $editableSiteIds)) {
                    throw new ForbiddenHttpException('User not authorized to edit element statuses for all the submitted site IDs.');
                }

                // Set the global status to true if it's enabled for *any* sites, or if already enabled.
                $element->enabled = in_array(true, $this->_enabledForSite) || $element->enabled;
            }

            $element->setEnabledForSite($this->_enabledForSite);
        } elseif (isset($this->_enabled)) {
            $element->enabled = $this->_enabled;
        }

        if ($this->_fresh) {
            $element->setIsFresh();

            if ($element->getIsUnpublishedDraft()) {
                $element->propagateAll = true;
            }
        }

        if ($element->getIsDraft()) {
            /** @var ElementInterface|DraftBehavior $element */
            if (isset($this->_draftName)) {
                $element->draftName = $this->_draftName;
            }
            if (isset($this->_notes)) {
                $element->draftNotes = $this->_notes;
            }
        } elseif (isset($this->_notes)) {
            $element->setRevisionNotes($this->_notes);
        }

        $scenario = $element->getScenario();
        $element->setScenario(Element::SCENARIO_LIVE);
        $element->setAttributes($this->_attributes);

        if ($this->_slug !== null) {
            $element->slug = $this->_slug;
        }

        $element->setScenario($scenario);

        // Now that the element is fully configured, make sure the user can actually view it
        if (!Craft::$app->getElements()->canView($element)) {
            throw new ForbiddenHttpException('User not authorized to edit this element.');
        }

        // Set the custom field values
        $element->setFieldValuesFromRequest($this->_fieldsLocation);
    }

    /**
     * Returns whether an element can be saved by the given user.
     *
     * If the element is a provisional draft, the canonical element will be used instead.
     *
     * @param ElementInterface $element
     * @param User $user
     * @return bool
     */
    private function _canSave(ElementInterface $element, User $user): bool
    {
        if ($element->getIsRevision()) {
            return false;
        }

        if ($element->isProvisionalDraft) {
            $element = $element->getCanonical(true);
        }

        return Craft::$app->getElements()->canSave($element, $user);
    }

    /**
     * Returns whether an unpublished draft can shed its draft status by the given user.
     *
     * @param ElementInterface $element
     * @param User $user
     * @return bool
     */
    private function _canApplyUnpublishedDraft(ElementInterface $element, User $user): bool
    {
        $fakeCanonical = clone $element;
        $fakeCanonical->draftId = null;
        return Craft::$app->getElements()->canSave($fakeCanonical, $user);
    }

    /**
     * @throws Throwable
     * @throws ServerErrorHttpException
     */
    private function _asSuccess(string $message, ElementInterface $element, array $data = [], bool $addAnother = false): Response
    {
        /** @var Element $element */
        // Don't call asModelSuccess() here so we can avoid including custom fields in the element data
        $data += [
            'modelName' => 'element',
            'element' => $element->toArray($element->attributes()),
        ];
        $response = $this->asSuccess($message, $data, $this->getPostedRedirectUrl($element), [
            'details' => !$element->dateDeleted ? Cp::elementHtml($element) : null,
        ]);

        if ($addAnother && $this->_addAnother) {
            $user = static::currentUser();
            $newElement = $element->createAnother();

            if (!$newElement || !Craft::$app->getElements()->canSave($newElement, $user)) {
                throw new ServerErrorHttpException('Unable to create a new element.');
            }

            if (!$newElement->slug) {
                $newElement->slug = ElementHelper::tempSlug();
            }

            $newElement->setScenario(Element::SCENARIO_ESSENTIALS);

            if (!Craft::$app->getDrafts()->saveElementAsDraft($newElement, $user->id, null, null, false)) {
                throw new ServerErrorHttpException(sprintf('Unable to create a new element: %s', implode(', ', $element->getErrorSummary(true))));
            }

            $url = $newElement->getCpEditUrl();

            if ($url) {
                $url = UrlHelper::urlWithParams($url, ['fresh' => 1]);
            } else {
                $url = UrlHelper::actionUrl('elements/edit', [
                    'draftId' => $newElement->draftId,
                    'siteId' => $newElement->siteId,
                    'fresh' => 1,
                ]);
            }

            $response->redirect($url);
        }

        return $response;
    }

    private function _asFailure(ElementInterface $element, string $message): ?Response
    {
        $data = [
            'modelName' => 'element',
            'element' => $element->toArray($element->attributes()),
            'errors' => $element->getErrors(),
        ];

        return $this->asFailure($message, $data, ['element' => $element]);
    }
}
