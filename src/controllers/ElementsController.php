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
use craft\base\NestedElementInterface;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\NestedElementQueryInterface;
use craft\elements\User;
use craft\enums\MenuItemType;
use craft\errors\InvalidElementException;
use craft\errors\InvalidTypeException;
use craft\errors\UnsupportedSiteException;
use craft\events\DefineElementEditorHtmlEvent;
use craft\events\DraftEvent;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\models\ElementActivity;
use craft\models\FieldLayoutForm;
use craft\services\Drafts;
use craft\web\Controller;
use craft\web\CpScreenResponseBehavior;
use craft\web\UrlManager;
use craft\web\View;
use Illuminate\Support\Collection;
use Throwable;
use yii\helpers\Markdown;
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
    private ?int $_fieldId = null;
    private ?int $_ownerId = null;
    private ?int $_newOwnerId = null;
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
    private array $_staticLayoutElements;
    private ?string $_selectedTab = null;
    private bool $_applyParams;
    private bool $_prevalidate;
    private bool $_asUnpublishedDraft;
    private bool $_deleteProvisionalDraft;
    private ?bool $_updateSearchIndexImmediately = null;

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
        $this->_fieldId = $this->_param('fieldId') ?: null;
        $this->_ownerId = $this->_param('ownerId') ?: null;
        $this->_newOwnerId = $this->_param('newOwnerId') ?: null;
        $this->_siteId = $this->_param('siteId');
        $this->_enabled = $this->_param('enabled', $this->_param('setEnabled', true) ? true : null);
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
        $this->_staticLayoutElements = $this->_param('staticLayoutElements') ?? [];
        $this->_selectedTab = $this->_param('selectedTab');
        $this->_applyParams = $this->_param('applyParams', true) || !$this->request->getIsPost();
        $this->_prevalidate = (bool)$this->_param('prevalidate');
        $this->_asUnpublishedDraft = (bool)$this->_param('asUnpublishedDraft');
        $this->_deleteProvisionalDraft = (bool)$this->_param('deleteProvisionalDraft');
        $this->_updateSearchIndexImmediately = $this->_param('updateSearchIndexImmediately');

        unset($this->_attributes['failMessage']);
        unset($this->_attributes['redirect']);
        unset($this->_attributes['successMessage']);
        unset($this->_attributes[$this->_fieldsLocation]);

        return true;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    private function _param(string $name, mixed $default = null): mixed
    {
        $value = ArrayHelper::remove($this->_attributes, $name) ?? $this->request->getQueryParam($name);
        if ($value === null && $default !== null && $this->request->getIsPost()) {
            return $default;
        }
        return $value;
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
        $this->_elementId = $elementId ?? $this->_elementId;
        $this->_elementUid = $elementUid ?? $this->_elementUid;
        $element = $this->_element();

        if ($element instanceof Response) {
            return $element;
        }

        $this->element = $element;
        $url = $element->getCpEditUrl();

        if (!$url) {
            throw new ServerErrorHttpException('The element doesn’t have an edit page.');
        }

        $editUrl = UrlHelper::removeParam(UrlHelper::cpUrl('edit'), 'site');
        if (str_starts_with($url, $editUrl)) {
            /** @var UrlManager $urlManager */
            $urlManager = Craft::$app->getUrlManager();
            return $this->runAction('edit', array_merge($urlManager->getRouteParams(), [
                'elementId' => $element->id,
            ]));
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
        $element = $this->_createElement();
        $user = static::currentUser();

        // Save it
        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($element, $user->id, null, null, false)) {
            return $this->_asFailure($element, StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t create {type}.', [
                'type' => $element::lowerDisplayName(),
            ])));
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
            $this->_elementId = $elementId ?? $this->_elementId;
            /**
             * @var Element|DraftBehavior|RevisionBehavior|Response|null $element
             * @phpstan-ignore varTag.nativeType
             */
            $element = $this->_element(checkForProvisionalDraft: true, strictSite: $strictSite);

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
        $sitesService = Craft::$app->getSites();
        $supportedSites = ElementHelper::supportedSitesForElement($element, true);
        $allEditableSiteIds = $sitesService->getEditableSiteIds();
        $propSites = array_values(array_filter($supportedSites, fn($site) => $site['propagate']));
        $propSiteIds = array_column($propSites, 'siteId');
        $propEditableSiteIds = array_intersect($propSiteIds, $allEditableSiteIds);
        $addlEditableSites = array_values(array_filter($supportedSites, fn($site) => !$site['propagate'] && in_array($site['siteId'], $allEditableSiteIds)));
        $canEditMultipleSites = count($propEditableSiteIds) > 1 || $addlEditableSites;

        // Permissions
        $canSave = $this->_canSave($element, $user);
        $canSaveCanonical = $elementsService->canSaveCanonical($element, $user);
        $canCreateDrafts = $elementsService->canCreateDrafts($canonical, $user);
        $canDuplicate = !$isRevision && $elementsService->canDuplicateAsDraft($element, $user);

        // Preview targets
        $previewTargets = $element->id ? $element->getPreviewTargets() : [];
        $enablePreview = (
            !empty($previewTargets) &&
            !$this->request->isMobileBrowser(true) &&
            (
                ($isDraft && $canSave) ||
                ($isCurrent && $canCreateDrafts)
            )
        );

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
        $enabledForSite = $element->getEnabledForSite();
        $hasRoute = $element->getRoute() !== null;
        $redirectUrl = $this->request->getValidatedQueryParam('returnUrl') ?? UrlHelper::cpReferralUrl() ?? ElementHelper::postEditUrl($element);

        // Site statuses
        if ($canEditMultipleSites) {
            $siteStatuses = ElementHelper::siteStatusesForElement($element, true);
        } else {
            $siteStatuses = [
                $element->siteId => $element->enabled,
            ];
        }

        $security = Craft::$app->getSecurity();
        $notice = null;
        if ($element->isProvisionalDraft) {
            $notice = fn() => $this->_draftNotice();
        } elseif ($isRevision) {
            $notice = fn() => $this->_revisionNotice($element::lowerDisplayName());
        }

        if ($element->enabled && $element->id) {
            $enabledSiteIds = array_flip($elementsService->getEnabledSiteIdsForElement($element->id));
        } else {
            $enabledSiteIds = [];
        }

        $response = $this->asCpScreen()
            ->editUrl($element->getCpEditUrl())
            ->docTitle($docTitle)
            ->title($title)
            ->site($element::isLocalized() ? $element->getSite() : null)
            ->selectableSites(array_map(fn(int $siteId) => [
                'site' => $sitesService->getSiteById($siteId),
                'status' => isset($enabledSiteIds[$siteId]) ? 'enabled' : 'disabled',
            ], $propEditableSiteIds))
            ->crumbs($this->_crumbs($element))
            ->contextMenuItems(fn() => $this->_contextMenuItems(
                $element,
                $isUnpublishedDraft,
                $canCreateDrafts,
            ))
            ->toolbarHtml(
                // if we're in a slideout, we don't want to add the .flex-grow to the header toolbar
                // as it'll mess with the width available for the tabs
                // see https://github.com/craftcms/cms/issues/17260
                ($this->_isSlideout() ? '' : Html::tag('div', options: ['class' => 'flex-grow'])) .
                Html::tag('div', options: ['class' => 'activity-container']),
            )
            ->additionalButtonsHtml(fn() => $this->_additionalButtons(
                $element,
                $canonical,
                $isRevision,
                $canSave,
                $canSaveCanonical,
                $canCreateDrafts,
                $canDuplicate,
                $previewTargets,
                $enablePreview,
                $isCurrent,
                $isUnpublishedDraft,
                $isDraft
            ))
            ->actionMenuItems(fn() => $this->_actionMenuItems($element, $previewTargets))
            ->noticeHtml($notice)
            ->errorSummary(fn() => $this->_errorSummary($element))
            ->prepareScreen(
                fn(Response $response, string $containerId) => $this->_prepareEditor(
                    $element,
                    $isUnpublishedDraft,
                    $canSave,
                    $response,
                    $containerId,
                    fn(?FieldLayoutForm $form) => $this->_editorContent($element, $canSave, $form),
                    fn(?FieldLayoutForm $form) => $this->_editorSidebar($element, $mergeCanonicalChanges, $canSave),
                    fn(?FieldLayoutForm $form) => [
                        'additionalSites' => $addlEditableSites,
                        'canCreateDrafts' => $canCreateDrafts,
                        'canEditMultipleSites' => $canEditMultipleSites,
                        'canSave' => $canSave,
                        'canSaveCanonical' => $canSaveCanonical,
                        'elementId' => $element->id,
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
                        'previewParamValue' => $previewTargets ? $security->hashData(StringHelper::randomString(10)) : null,
                        'revisionId' => $element->revisionId,
                        'fieldId' => $element instanceof NestedElementInterface ? $element->getField()?->id : null,
                        'ownerId' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
                        'siteId' => $element->siteId,
                        'siteStatuses' => $siteStatuses,
                        'siteToken' => (!Craft::$app->getIsLive() || !$element->getSite()->enabled) ? $security->hashData((string)$element->siteId) : null,
                        'visibleLayoutElements' => $form?->getVisibleElements() ?? [],
                        'staticLayoutElements' => $form?->getStaticElements() ?? [],
                        'updatedTimestamp' => $element->dateUpdated?->getTimestamp(),
                        'canonicalUpdatedTimestamp' => $canonical->dateUpdated?->getTimestamp(),
                        'isStatic' => $isRevision || !$canSave,
                    ]
                )
            );

        if ($canSave) {
            if ($isUnpublishedDraft) {
                if ($canSaveCanonical) {
                    $response
                        ->submitButtonLabel(StringHelper::upperCaseFirst(Craft::t('app', 'Create {type}', [
                            'type' => $element::lowerDisplayName(),
                        ])))
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
                    ->submitButtonLabel(StringHelper::upperCaseFirst(Craft::t('app', 'Save {type}', [
                        'type' => Craft::t('app', 'draft'),
                    ])))
                    ->action('elements/save-draft')
                    ->redirectUrl("{cpEditUrl}");
            } else {
                $response
                    ->action('elements/save')
                    ->redirectUrl("$redirectUrl#");
            }

            $response
                ->saveShortcutRedirectUrl('{cpEditUrl}')
                ->altActions($element->getAltActions());
        }

        return $response;
    }

    /**
     * Displays a standalone Live Preview editor for an element.
     *
     * @param int $elementId
     * @since 5.6.0
     */
    public function actionPreview(int $elementId): Response
    {
        $this->requireCpRequest();

        $this->_elementId = $elementId;
        /**
         * @var Element|DraftBehavior|RevisionBehavior|Response|null $element
         * @phpstan-ignore varTag.nativeType
         */
        $element = $this->_element(checkForProvisionalDraft: true);

        if ($element instanceof Response) {
            return $element;
        }

        if (!$element) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;

        // Screen prep
        $redirectUrl = $this->request->getValidatedQueryParam('returnUrl') ?? ElementHelper::postEditUrl($element);

        $this->getView()->registerJsWithVars(fn(
            $elementType,
            $elementId,
            $draftId,
            $revisionId,
            $siteId,
            $redirectUrl,
        ) => <<<JS
(() => {
  const preview = new Craft.Preview({
    elementType: $elementType,
    elementId: $elementId,
    draftId: $draftId,
    revisionId: $revisionId,
    siteId: $siteId,
    standaloneMode: true,
    redirectUrl: $redirectUrl,
  });
  preview.open();
})();
JS, [
            $element::class,
            $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id,
            !$element->isProvisionalDraft ? $element->draftId : null,
            $element->revisionId,
            $element->siteId,
            $redirectUrl,
        ], View::POS_END);

        [$docTitle, $title] = $this->_editElementTitles($element);

        return $this->renderTemplate('_layouts/base.twig', [
            'docTitle' => $docTitle,
            'title' => $title,
        ]);
    }

    /**
     * Copies field/attribute values on an element from one site to another.
     *
     * @return Response
     * @since 5.6.0
     */
    public function actionCopyValuesFromSite(): Response
    {
        $this->requireCpRequest();

        /** @var Element|Response|null $element */
        $element = $this->_element(checkForProvisionalDraft: true);

        if ($element instanceof Response) {
            return $element;
        }

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $copyFromSiteId = (int)$this->request->getRequiredBodyParam('fromSiteId');
        $site = Craft::$app->getSites()->getSiteById($copyFromSiteId);
        if (!$site) {
            throw new BadRequestHttpException("Invalid site ID: $copyFromSiteId");
        }
        $this->requirePermission("editSite:$site->uid");

        $layoutElementUid = $this->request->getRequiredBodyParam('layoutElementUid');
        $namespace = $this->request->getBodyParam('namespace');

        $fromElement = $element::find()
            ->id($element->id)
            ->structureId($element->structureId)
            ->siteId($copyFromSiteId)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->one();

        if (!$fromElement) {
            throw new UnsupportedSiteException($element, $copyFromSiteId, 'Attempting to copy element content from an unsupported site.');
        }

        $layoutElement = $element->getFieldLayout()->getElementByUid($layoutElementUid);
        if (!$layoutElement instanceof BaseField || !$layoutElement->isCrossSiteCopyable($element)) {
            throw new BadRequestHttpException("Invalid layout element UUID: $layoutElementUid");
        }
        if ($layoutElement instanceof CustomField) {
            $layoutElement->getField()->copyCrossSiteValue($fromElement, $element);
        } else {
            $attribute = $layoutElement->attribute();
            $element->$attribute = $fromElement->$attribute;
        }

        $view = $this->getView();
        $html = $view->namespaceInputs(fn() => $layoutElement->formHtml($element), $namespace);

        if ($html) {
            $html = Html::modifyTagAttributes($html, [
                'data' => [
                    'layout-element' => $layoutElement->uid,
                ],
            ]);
        }

        return $this->_asSuccess(Craft::t('app', 'Field value copied.'), $element, [
            'fieldHtml' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
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

        $this->_elementId = $elementId;
        /**
         * @var Element|DraftBehavior|RevisionBehavior|Response|null $element
         * @phpstan-ignore varTag.nativeType
         */
        $element = $this->_element();

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
            ->crumbs([
                ...$this->_crumbs($element, false),
                [
                    'label' => Craft::t('app', 'Revisions'),
                    'current' => true,
                ],
            ])
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
        if ($element::hasTitles() && $element->title !== null && $element->title !== '') {
            $title = $element->title;
        } elseif (!$element->id || $element->getIsUnpublishedDraft()) {
            $title = Craft::t('app', 'Create a new {type}', [
                'type' => $element::lowerDisplayName(),
            ]);
        } else {
            $title = $element->getUiLabel();
        }

        $docTitle = $element->getUiLabel();

        if ($element->getIsDraft() && !$element->getIsUnpublishedDraft()) {
            /** @var ElementInterface&DraftBehavior $element */
            if ($element->isProvisionalDraft) {
                $docTitle .= ' — ' . Craft::t('app', 'Edited');
            } else {
                $docTitle .= " ($element->draftName)";
            }
        } elseif ($element->getIsRevision()) {
            /** @var ElementInterface&RevisionBehavior $element */
            $docTitle .= ' (' . $element->getRevisionLabel() . ')';
        }

        // Include site name if localized
        if ($element::isLocalized() && Craft::$app->getIsMultiSite()) {
            $docTitle .= sprintf(' - %s', $element->getSite()->getUiLabel());
        }

        return [$docTitle, $title];
    }

    private function _crumbs(ElementInterface $element, bool $current = true): array
    {
        if ($element->isProvisionalDraft) {
            $crumbs = $element->getCanonical(true)->getCrumbs();
        } else {
            $crumbs = $element->getCrumbs();
        }

        return [
            ...$crumbs,
            [
                'html' => Cp::elementChipHtml($element, [
                    'showDraftName' => !$current,
                    'class' => 'chromeless',
                    'hyperlink' => true,
                ]),
                'current' => $current,
            ],
        ];
    }

    private function _contextMenuItems(
        ElementInterface $element,
        bool $isUnpublishedDraft,
        bool $canCreateDrafts,
    ): array {
        if ($element->isProvisionalDraft) {
            $element = $element->getCanonical(true);
        }

        if (!$element->id || $element->getIsUnpublishedDraft()) {
            return [];
        }

        $elementsService = Craft::$app->getElements();

        if (!$isUnpublishedDraft) {
            $user = Craft::$app->getUser()->getIdentity();

            $drafts = $element::find()
                ->draftOf($element)
                ->siteId($element->siteId)
                ->status(null)
                ->orderBy(['dateUpdated' => SORT_DESC])
                ->with(['draftCreator'])
                ->collect()
                ->filter(fn(ElementInterface $draft) => $elementsService->canView($draft, $user))
                ->all();
        } else {
            $drafts = [];
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $revisionsPageUrl = null;
        $hasMoreRevisions = false;

        if ($element->hasRevisions() && $generalConfig->maxRevisions !== 1) {
            $revisionsQuery = $element::find()
                ->revisionOf($element)
                ->siteId($element->siteId)
                ->status(null)
                ->offset(1)
                ->limit($generalConfig->maxRevisions ? min($generalConfig->maxRevisions - 1, 10) : 10)
                ->orderBy(['dateCreated' => SORT_DESC])
                ->with(['revisionCreator']);

            $revisions = $revisionsQuery->all();
            $revisionsPageUrl = $element->getCpRevisionsUrl();

            if ($revisionsPageUrl) {
                $hasMoreRevisions = ($revisionsQuery->count() - 1) > 0;
            }
        } else {
            $revisions = [];
        }

        // if we're viewing a revision, make sure it's in the list
        if (
            $element->getIsRevision() &&
            !ArrayHelper::contains($revisions, fn(ElementInterface $revision) => $revision->id === $element->id)
        ) {
            $revisions[] = $element;
        }

        if (empty($drafts) && empty($revisions) && !$canCreateDrafts) {
            return [];
        }

        $formatter = Craft::$app->getFormatter();

        $baseParams = $this->request->getQueryParams();
        unset($baseParams['draftId'], $baseParams['revisionId'], $baseParams['siteId'], $baseParams['fresh']);
        if (isset($generalConfig->pathParam)) {
            unset($baseParams[$generalConfig->pathParam]);
        }

        $isDraft = $element->getIsDraft();
        $isRevision = $element->getIsRevision();
        $cpEditUrl = UrlHelper::cpUrl($element->getCpEditUrl(), [
            'draftId' => null,
            'revisionId' => null,
        ]);

        /** @var (ElementInterface&RevisionBehavior)|null $revision */
        $revision = $element->getCurrentRevision();
        $creator = $revision?->getCreator();
        $timestamp = $formatter->asTimestamp($revision->dateCreated ?? $element->dateUpdated, Locale::LENGTH_SHORT, true);

        $items = [
            [
                'heading' => Craft::t('app', 'Context'),
                'headingTag' => 'h2',
                'headingAttributes' => ['class' => ['visually-hidden']],
                'listAttributes' => ['class' => ['revision-group-current']],
                'items' => [
                    [
                        'label' => Craft::t('app', 'Current'),
                        'description' => $creator
                            ? Craft::t('app', 'Saved {timestamp} by {creator}', [
                                'timestamp' => $timestamp,
                                'creator' => $creator->name,
                            ])
                            : Craft::t('app', 'Last saved {timestamp}', [
                                'timestamp' => $timestamp,
                            ]),
                        'url' => $cpEditUrl,
                        'selected' => !$isDraft && !$isRevision,
                    ],
                ],
            ],
        ];

        if (!empty($drafts)) {
            $items[] = [
                'heading' => Craft::t('app', 'Drafts'),
                'listAttributes' => ['class' => ['revision-group-drafts']],
                'items' => array_map(function($draft) use ($element, $formatter, $cpEditUrl, $baseParams) {
                    /** @var ElementInterface&DraftBehavior $draft */
                    $creator = $draft->getCreator();
                    $timestamp = $formatter->asTimestamp($draft->dateUpdated, Locale::LENGTH_SHORT, true);
                    $timestampWithDate = $formatter->asDatetime($draft->dateUpdated, Locale::LENGTH_SHORT);

                    return [
                        'label' => $draft->draftName,
                        'description' => $creator
                            ? Template::raw(Craft::t('app', 'Saved <time title="{timestampWithDate}">{timestamp}</time> by {creator}', [
                                'timestampWithDate' => $timestampWithDate,
                                'timestamp' => $timestamp,
                                'creator' => $creator->name,
                            ]))
                            : Template::raw(Craft::t('app', 'Last saved <time title="{timestampWithDate}">{timestamp}</time>', [
                                'timestampWithDate' => $timestampWithDate,
                                'timestamp' => $timestamp,
                            ])),
                        'url' => UrlHelper::urlWithParams($cpEditUrl, array_merge($baseParams, [
                            'draftId' => $draft->draftId,
                        ])),
                        'selected' => $draft->id === $element->id,
                    ];
                }, $drafts),
            ];
        }

        if (!empty($revisions)) {
            $items[] = [
                'heading' => Craft::t('app', 'Recent Revisions'),
                'listAttributes' => ['class' => ['revision-group-revisions']],
                'items' => array_map(function($revision) use ($element, $formatter, $cpEditUrl, $baseParams) {
                    /** @var ElementInterface&RevisionBehavior $revision */
                    $creator = $revision->getCreator();
                    $timestamp = $formatter->asTimestamp($revision->dateCreated, Locale::LENGTH_SHORT, true);
                    $timestampWithDate = $formatter->asDatetime($revision->dateCreated, Locale::LENGTH_SHORT);

                    return [
                        'label' => $revision->getRevisionLabel(),
                        'description' => $creator
                            ? Template::raw(Craft::t('app', 'Saved <time title="{timestampWithDate}">{timestamp}</time> by {creator}', [
                                'timestampWithDate' => $timestampWithDate,
                                'timestamp' => $timestamp,
                                'creator' => $creator->name,
                            ]))
                            : Template::raw(Craft::t('app', 'Saved <time title="{timestampWithDate}">{timestamp}</time>', [
                                'timestampWithDate' => $timestampWithDate,
                                'timestamp' => $timestamp,
                            ])),
                        'url' => UrlHelper::urlWithParams($cpEditUrl, array_merge($baseParams, [
                            'revisionId' => $revision->revisionId,
                        ])),
                        'selected' => $revision->id === $element->id,
                    ];
                }, $revisions),
            ];
        }

        if ($hasMoreRevisions) {
            $items[] = ['type' => MenuItemType::HR];
            $items[] = [
                'label' => Craft::t('app', 'View all revisions'),
                'url' => $revisionsPageUrl,
                'attributes' => [
                    'class' => ['go'],
                ],
            ];
        }

        return $items;
    }

    private function _additionalButtons(
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
                ]) .
                ($enablePreview
                    ? Html::beginTag('button', [
                        'type' => 'button',
                        'class' => ['preview-btn', 'btn'],
                    ]) .
                    Html::tag('span', Craft::t('app', 'Preview'), ['class' => 'label']) .
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
                    Html::button(Craft::t('app', 'Create a draft'), [
                        'class' => ['btn', 'formsubmit'],
                    ]) .
                    Html::endForm();
            }
        }

        if (!$canSave && $canDuplicate) {
            // save as a new is now available to people who can create drafts
            $components[] = Html::beginForm() .
                Html::actionInput('elements/duplicate') .
                Html::redirectInput('{cpEditUrl}') .
                Html::hiddenInput('elementId', (string)$canonical->id) .
                Html::hiddenInput('asUnpublishedDraft', '1') .
                Html::button(Craft::t('app', 'Save as a new {type}', ['type' => $element::lowerDisplayName()]), [
                    'class' => ['btn', 'formsubmit'],
                ]) .
                Html::endForm();
        }

        // Apply draft
        if ($isDraft && !$isCurrent && $canSave && $canSaveCanonical) {
            $components[] = Html::button(Craft::t('app', 'Apply draft'), [
                'class' => ['btn', 'secondary', 'formsubmit', 'tooltip-draft-btn'],
                'data' => [
                    'action' => 'elements/apply-draft',
                    'redirect' => Craft::$app->getSecurity()->hashData('{cpEditUrl}'),
                ],
            ]);
        }

        // Revert content from this revision
        if ($isRevision && $canSaveCanonical && $element->hasRevisions()) {
            $components[] = Html::beginForm() .
                Html::actionInput('elements/revert') .
                Html::redirectInput('{cpEditUrl}') .
                Html::hiddenInput('elementId', (string)$canonical->id) .
                Html::hiddenInput('revisionId', (string)$element->revisionId) .
                Html::button(Craft::t('app', 'Revert content from this revision'), [
                    'class' => ['btn', 'formsubmit', 'revision-draft-btn'],
                ]) .
                Html::endForm();
        }

        $components[] = $element->getAdditionalButtons();

        return implode("\n", array_filter($components));
    }

    private function _prepareEditor(
        ElementInterface $element,
        bool $isUnpublishedDraft,
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
        $contentHtml = $contentFn($form);
        $sidebarHtml = $sidebarFn($form);

        /** @var CpScreenResponseBehavior|null $behavior */
        $behavior = $response->getBehavior(CpScreenResponseBehavior::NAME);

        if ($contentHtml === '' && $sidebarHtml !== '' && $this->request->getAcceptsJson()) {
            $contentHtml = Html::tag('div', $sidebarHtml, [
                'class' => 'details',
            ]);
            $sidebarHtml = '';
            $behavior->slideoutBodyClass = 'so-full-details';
        }

        if ($canSave) {
            $components = [];

            if ($element->id) {
                // don't use the canonical ID if this is a normal element that's keeping track of its canonical
                // e.g. nested Matrix entries that were duplicated for an owner's draft
                $id = $element->getIsDraft() || $element->getIsRevision() ? $element->getCanonicalId() : $element->id;
                $components[] = Html::hiddenInput('elementId', (string)$id);
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

            if ($this->_updateSearchIndexImmediately) {
                $components[] = Html::hiddenInput('updateSearchIndexImmediately', '1');
            }

            $components[] = $contentHtml;
            $contentHtml = implode("\n", $components);
        }

        $behavior->tabs($form?->getTabMenu() ?? []);
        $behavior->contentHtml($contentHtml);
        $behavior->metaSidebarHtml($sidebarHtml);

        $settings = $jsSettingsFn($form);

        if ($this->_isSlideout()) {
            $this->view->registerJsWithVars(fn($settings) => <<<JS
$('#$containerId').data('elementEditorSettings', $settings);
JS, [
                $settings,
            ]);
        } else {
            $this->view->registerJsWithVars(fn($settings) => <<<JS
new Craft.ElementEditor($('#$containerId'), $settings);
JS, [
                $settings,
            ]);
        }

        // Give the element a chance to do things here too
        $element->prepareEditScreen($response, $containerId);
    }

    private function _editorContent(
        ElementInterface $element,
        bool $canSave,
        ?FieldLayoutForm $form,
    ): string {
        $html = $form?->render() ?? '';

        // Fire a 'defineEditorContent' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_EDITOR_CONTENT)) {
            $event = new DefineElementEditorHtmlEvent([
                'element' => $element,
                'html' => $html,
                'static' => !$canSave,
            ]);
            $this->trigger(self::EVENT_DEFINE_EDITOR_CONTENT, $event);
            $html = $event->html;
        }

        return trim($html);
    }

    /**
     * Return html for errors summary box
     *
     * @param ElementInterface $element
     * @return string
     */
    private function _errorSummary(ElementInterface $element): string
    {
        $html = '';

        if ($element->hasErrors()) {
            $allErrors = $element->getErrors();
            $allKeys = array_keys($allErrors);

            // only show "top-level" errors
            // if you e.g. have an assets field which is set to validate related assets,
            // you should only see the top-level "Fix validation errors on the related asset" error
            // and not the details of what's wrong with the selected asset;
            foreach ($allKeys as $key) {
                $lastNestedKey = substr_replace($key, '', strrpos($key, '.'));
                $lastNestedKey = substr_replace($lastNestedKey, '', strrpos($lastNestedKey, '['));
                if (!empty($lastNestedKey)) {
                    if (in_array($lastNestedKey, $allKeys)) {
                        unset($allErrors[$key]);
                    }
                }
            }
            $errorsList = [];
            $tabs = $element->getFieldLayout()->getTabs();
            foreach ($allErrors as $key => $errors) {
                foreach ($errors as $error) {
                    // this is true in case of e.g. cross site validation error
                    if (preg_match('/^\s?\<a /', $error)) {
                        $errorItem = Html::beginTag('li');
                        $errorItem .= $error;
                        $errorItem .= Html::endTag('li');
                    } else {
                        // get tab uid for this error
                        $tabUid = null;
                        $bracketPos = strpos($key, '[');
                        $fieldKey = substr($key, 0, $bracketPos ?: null);
                        foreach ($tabs as $tab) {
                            foreach ($tab->getElements() as $layoutElement) {
                                if ($layoutElement instanceof BaseField && $layoutElement->attribute() === $fieldKey) {
                                    $tabUid = $tab->uid;
                                    break 2;
                                }
                            }
                        }

                        // If the error is for a recursively-nested Matrix field,
                        // manipulate the key to only reference the nested Matrix field, entry and inner field
                        // Before: foo[<uuid>].bar[<uuid>].baz
                        // After:  bar[<uuid>].baz
                        if (substr_count($key, '.') > 1) {
                            $keyParts = explode('.', $key);
                            if (preg_match(sprintf('/\[%s\]$/', StringHelper::UUID_PATTERN), $keyParts[count($keyParts) - 3])) {
                                $key = implode('.', array_slice($keyParts, -2));
                            }
                        }

                        $errorItem = null;
                        if ($error !== null) {
                            $error = Markdown::processParagraph(htmlspecialchars($error));
                            $errorItem = Html::beginTag('li');
                            $errorItem .= Html::a(Craft::t('app', $error), '#', [
                                'data' => [
                                    'field-error-key' => $key,
                                    'layout-tab' => $tabUid,
                                ],
                            ]);
                            $errorItem .= Html::endTag('li');
                        }
                    }

                    if ($errorItem !== null) {
                        $errorsList[] = $errorItem;
                    }
                }
            }

            if (!empty($errorsList)) {
                $heading = Craft::t('app', 'Found {num, number} {num, plural, =1{error} other{errors}}', [
                    'num' => count($errorsList),
                ]);

                $html = Html::beginTag('div', [
                        'class' => ['error-summary'],
                        'tabindex' => '-1',
                    ]) .
                    Html::beginTag('div') .
                    Html::tag('span', '', [
                        'class' => 'notification-icon',
                        'data-icon' => 'alert',
                        'aria-label' => Craft::t('app', 'Error'),
                        'role' => 'img',
                    ]) .
                    Html::tag('h2', $heading) .
                    Html::endTag('div') .
                    Html::beginTag('ul', [
                        'class' => ['errors'],
                    ]) .
                    implode('', $errorsList) .
                    Html::endTag('ul') .
                    Html::endTag('div');
            }
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

        $components[] = $element->getSidebarHtml(!$canSave);

        if ($this->id) {
            $components[] = Cp::metadataHtml($element->getMetadata());
        }

        return trim(implode("\n", $components));
    }

    /**
     * Returns an array of action menu items for the element.
     *
     * @param ElementInterface $element
     * @param array $previewTargets
     * @return array
     */
    private function _actionMenuItems(ElementInterface $element, array $previewTargets): array
    {
        if (!$element->id) {
            return [];
        }

        $hideViewAction = !empty($previewTargets) && !$this->_isSlideout();

        return array_filter(
            $element->getActionMenuItems(),
            function(array $item) use ($hideViewAction) {
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

    /**
     * Returns whether this is for a slideout.
     *
     * @return bool
     */
    private function _isSlideout(): bool
    {
        return $this->request->getHeaders()->has('X-Craft-Container-Id');
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

    private function _revisionNotice($elementType): string
    {
        return
            Html::beginTag('div', [
                'class' => 'content-notice',
            ]) .
            Html::tag('div', '', [
                'class' => ['content-notice-icon'],
                'aria' => ['hidden' => 'true'],
                'data' => ['icon' => 'lightbulb'],
            ]) .
            Html::tag('p', Craft::t(
                'app',
                'You’re viewing a revision. None of the {type}’s fields are editable.',
                [
                    'type' => $elementType,
                ]
            )) .
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

        $element = $this->_element();

        if ($element instanceof Response) {
            return $element;
        }

        if (!$element || $element->getIsDraft() || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;
        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        // Check save permissions before and after applying POST params to the element
        // in case the request was tampered with.
        if (!$elementsService->canSave($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to save this element.');
        }

        $this->_applyParamsToElement($element);

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

        if ($element instanceof NestedElementInterface && property_exists($element, 'updateSearchIndexForOwner')) {
            $element->updateSearchIndexForOwner = true;
        }

        try {
            $namespace = $this->request->getHeaders()->get('X-Craft-Namespace');
            // crossSiteValidate only if it's multisite, element supports drafts and we're not in a slideout
            $success = $elementsService->saveElement(
                $element,
                crossSiteValidate: ($namespace === null && Craft::$app->getIsMultiSite() && $elementsService->canCreateDrafts($element, $user)),
            );
        } catch (UnsupportedSiteException $e) {
            $element->addError('siteId', $e->getMessage());
            $success = false;
        } finally {
            if ($isNotNew) {
                $mutex->release($lockKey);
            }
        }

        if (!$success) {
            return $this->_asFailure($element, StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                'type' => $element::lowerDisplayName(),
            ])));
        }

        $elementsService->trackActivity($element, ElementActivity::TYPE_SAVE);

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
        ]), $element, supportsAddAnother: true);
    }

    /**
     * Saves a nested element for a derivative of its owner.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 5.5.0
     */
    public function actionSaveNestedElementForDerivative(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (!isset($this->_newOwnerId)) {
            throw new BadRequestHttpException('No new owner was identified by the request.');
        }

        /** @var Element|null $element */
        $element = $this->_element();

        if (
            !$element instanceof NestedElementInterface ||
            !$element->getOwnerId() ||
            !$element->getIsDraft() ||
            $element->getIsCanonical()
        ) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;
        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        // Check save permissions before and after applying POST params to the element
        // in case the request was tampered with.
        if (!$elementsService->canSave($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to save this element.');
        }

        // Get the new owner and make sure it's a derivative element,
        // and that its canonical element is the nested element's primary owner
        $owner = $elementsService->getElementById($this->_newOwnerId, siteId: $element->siteId);
        if ($owner->getIsCanonical()) {
            throw new BadRequestHttpException('The owner element must be a derivative.');
        }
        if ($owner->getCanonicalId() !== $element->getPrimaryOwnerId()) {
            // the owner might be a derivative of another canonical element
            $canonicalOwner = $owner->getCanonical();
            if ($canonicalOwner->getCanonicalId() !== $element->getPrimaryOwnerId()) {
                throw new BadRequestHttpException('The canonical owner element must be the primary owner of the nested element.');
            }
        }
        if (!$elementsService->canSave($owner, $user)) {
            throw new ForbiddenHttpException('User not authorized to save the owner element.');
        }

        // Get the old sort order
        $sortOrder = (new Query())
            ->select('sortOrder')
            ->from(Table::ELEMENTS_OWNERS)
            ->where([
                'elementId' => $element->id,
                'ownerId' => $element->getOwnerId(),
            ])
            ->scalar() ?: null;
        $element->setSortOrder($sortOrder);

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Remove existing ownership data for the element within the canonical owner,
            // and for its canonical element within the derivative
            Db::delete(Table::ELEMENTS_OWNERS, [
                'or',
                ['elementId' => $element->id, 'ownerId' => $owner->getCanonicalId()],
                ['elementId' => $element->getCanonicalId(), 'ownerId' => $owner->id],
            ]);

            // Remove existing ownership data for the element within the canonical owner
            Db::delete(Table::ELEMENTS_OWNERS, [
                'elementId' => $element->id,
                'ownerId' => $owner->getCanonicalId(),
            ]);

            // Remove the draft data, but preserve the canonicalId
            $element->setPrimaryOwner($owner);
            $element->setOwner($owner);
            $elementsService->saveElement($element);

            $this->_applyParamsToElement($element);

            if (!$elementsService->canSave($element, $user)) {
                throw new ForbiddenHttpException('User not authorized to save this element.');
            }

            if ($element->enabled && $element->getEnabledForSite()) {
                $element->setScenario(Element::SCENARIO_LIVE);
            }

            try {
                $success = $elementsService->saveElement($element);
            } catch (UnsupportedSiteException $e) {
                $element->addError('siteId', $e->getMessage());
                $success = false;
            }

            if (!$success) {
                $transaction->rollBack();
                return $this->_asFailure($element, StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                    'type' => $element::lowerDisplayName(),
                ])));
            }

            if ($element->getIsDraft()) {
                Craft::$app->getDrafts()->removeDraftData($element);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $this->_asSuccess(Craft::t('app', '{type} saved.', [
            'type' => $element::displayName(),
        ]), $element);
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

        /** @var (ElementInterface&DraftBehavior)|null $element */
        $element = $this->_element();

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;

        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        // save as a new is now available to people who can create drafts
        $asUnpublishedDraft = $this->_asUnpublishedDraft && $element::hasDrafts();
        if ($asUnpublishedDraft) {
            $authorized = $elementsService->canDuplicateAsDraft($element, $user);
        } else {
            $authorized = $elementsService->canDuplicate($element, $user);
        }

        if (!$authorized) {
            throw new ForbiddenHttpException('User not authorized to duplicate this element.');
        }

        $newAttributes = [
            'isProvisionalDraft' => false,
            'draftId' => null,
        ];

        if ($asUnpublishedDraft &&
            ($element->getIsCanonical() || $element->isProvisionalDraft) &&
            $element->slug === $element->getCanonical()->slug
        ) {
            $newAttributes += [
                'slug' => null,
            ];
        }

        if ($element instanceof NestedElementInterface) {
            $newAttributes += [
                'primaryOwnerId' => $element->getOwnerId(),
                'ownerId' => $element->getOwnerId(),
                'sortOrder' => null,
            ];
        }

        try {
            $newElement = $elementsService->duplicateElement(
                $element,
                $newAttributes,
                asUnpublishedDraft: $asUnpublishedDraft,
            );
        } catch (InvalidElementException $e) {
            return $this->_asFailure($e->element, Craft::t('app', 'Couldn’t duplicate {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        } catch (Throwable $e) {
            throw new ServerErrorHttpException('An error occurred when duplicating the element.', 0, $e);
        }

        // If the original element is a provisional draft,
        // delete the draft as the changes are likely no longer wanted.
        if ($this->_deleteProvisionalDraft && $element->isProvisionalDraft) {
            Craft::$app->getElements()->deleteElement($element);
        }

        return $this->_asSuccess(Craft::t('app', '{type} duplicated.', [
            'type' => $element::displayName(),
        ]), $newElement);
    }

    /**
     * Duplicates multiple elements with the given new attributes.
     *
     * @return Response|null
     * @since 5.7.0
     */
    public function actionBulkDuplicate(): ?Response
    {
        $this->requirePostRequest();

        $elementInfo = $this->request->getRequiredBodyParam('elements');
        $newAttributes = $this->request->getRequiredBodyParam('newAttributes');

        $newElementInfo = [];

        Craft::$app->getDb()->transaction(function() use ($elementInfo, $newAttributes, &$newElementInfo) {
            $elementsService = Craft::$app->getElements();
            $elementsService->ensureBulkOp(function() use ($elementInfo, $newAttributes, &$newElementInfo, $elementsService) {
                foreach ($elementInfo as $info) {
                    $element = $this->_element($info);

                    if (!$element instanceof ElementInterface) {
                        Craft::warning(sprintf('Unable to duplicate element: %s', Json::encode($info)), __METHOD__);
                        continue;
                    }

                    $safeNewAttributes = Collection::make($newAttributes)
                        ->only($element->safeAttributes())
                        ->all();

                    try {
                        $newElement = $elementsService->duplicateElement(
                            $element,
                            $safeNewAttributes + $element::baseBulkDuplicateAttributes(),
                            false,
                            checkAuthorization: true,
                        );
                    } catch (InvalidElementException $e) {
                        return $this->_asFailure($e->element, Craft::t('app', 'Couldn’t duplicate {type}.', [
                            'type' => $element::lowerDisplayName(),
                        ]));
                    } catch (ForbiddenHttpException $e) {
                        throw $e;
                    } catch (Throwable $e) {
                        throw new ServerErrorHttpException('An error occurred when duplicating the element.', 0, $e);
                    }

                    $newElementInfo[] = $newElement->toArray($newElement->attributes());
                }
            });
        });

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $elementInfo[0]['type'];
        return $this->asSuccess(StringHelper::upperCaseFirst(Craft::t('app', '{type} duplicated.', [
            'type' => count($elementInfo) === 1 ? $elementType::displayName() : $elementType::pluralDisplayName(),
        ])), [
            'newElements' => $newElementInfo,
        ]);
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
            $element = $element->getCanonical(true);
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
        $element = $this->_element(checkForProvisionalDraft: true);

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $this->element = $element;

        $elementsService = Craft::$app->getElements();

        if (!$elementsService->canDeleteForSite($element)) {
            throw new ForbiddenHttpException('User not authorized to delete the element for this site.');
        }

        $elementsService->deleteElementForSite($element);

        if ($element->isProvisionalDraft) {
            // see if the canonical element exists for this site
            $canonical = $element->getCanonical();
            if ($canonical->id !== $element->id) {
                $element = $canonical;
                $elementsService->deleteElementForSite($element);
            }
        }

        return $this->_asSuccess(Craft::t('app', '{type} deleted for site.', [
            'type' => $element->getIsDraft() && !$element->isProvisionalDraft ? Craft::t('app', 'Draft') : $element::displayName(),
        ]), $element);
    }

    /**
     * Validates an element.
     *
     * @return Response|null
     * @since 5.8.0
     */
    public function actionValidate(): ?Response
    {
        $this->requirePostRequest();

        /**
         * @var Element|DraftBehavior|Response|null $element
         * @phpstan-ignore varTag.nativeType
         */
        $element = $this->_element();

        // this can happen if we're creating e.g. nested entry in a matrix field (cards or element index)
        // and we hit "create entry" before the autosave kicks in
        if ($element instanceof Response) {
            return $element;
        }

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $element->setScenario(Element::SCENARIO_LIVE);

        if (!$element->validate()) {
            return $this->_asFailure($element, Craft::t('app', '{type} validation failed.', [
                'type' => $element::displayName(),
            ]));
        }

        return $this->_asSuccess(Craft::t('app', '{type} validation successful.', [
            'type' => $element::displayName(),
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

        /**
         * @var Element|DraftBehavior|Response|null $element
         * @phpstan-ignore varTag.nativeType
         */
        $element = $this->_element();

        // this can happen if we're creating e.g. nested entry in a matrix field (cards or element index)
        // and we hit "create entry" before the autosave kicks in
        if ($element instanceof Response) {
            return $element;
        }

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
            $existingProvisionalDraft = $element::find()
                ->provisionalDrafts()
                ->draftOf($element->id)
                ->draftCreator($user->id)
                ->site('*')
                ->status(null)
                ->one();

            if ($existingProvisionalDraft) {
                Craft::warning("Overwriting an existing provisional draft for element/user $element->id/$user->id", __METHOD__);
                $elementsService->deleteElement($existingProvisionalDraft, true);
            }
        }

        // Keep track of all newly-created draft IDs
        $draftElementIds = [];
        $draftElementUids = [];
        $draftsService = Craft::$app->getDrafts();
        $draftsService->on(Drafts::EVENT_AFTER_CREATE_DRAFT, function(DraftEvent $event) use (&$draftElementIds,  &$draftElementUids) {
            $draftElementIds[$event->canonical->id] = $event->draft->id;
            $draftElementUids[$event->canonical->uid] = $event->draft->uid;
        });

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Are we creating the draft here?
            if (!$element->getIsDraft()) {
                /** @var Element|DraftBehavior $element */
                $draft = $draftsService->createDraft($element, $user->id, null, null, [], $this->_provisional);
                $draft->setCanonical($element);
                $element = $this->element = $draft;
            }

            // keep track of the original field layout ID, in case it changes here
            $oldFieldLayoutId = $element->getFieldLayout()?->id;

            $this->_applyParamsToElement($element);

            // Make sure nothing just changed that would prevent the user from saving
            if (!$this->_canSave($element, $user)) {
                throw new ForbiddenHttpException('User not authorized to save this element.');
            }

            if ($this->_dropProvisional) {
                $element->isProvisionalDraft = false;
            }

            $element->setScenario(Element::SCENARIO_ESSENTIALS);

            // If the field layout ID changed, save all content
            $saveContent = $element->getFieldLayout()?->id !== $oldFieldLayoutId;

            if (!$elementsService->saveElement($element, saveContent: $saveContent)) {
                $transaction->rollBack();
                return $this->_asFailure($element, StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                    'type' => Craft::t('app', 'draft'),
                ])));
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $elementsService->trackActivity($element, ElementActivity::TYPE_SAVE);

        $creator = $element->getCreator();

        $data = [
            'canonicalId' => $element->getCanonicalId(),
            'elementId' => $element->id,
            'draftId' => $element->draftId,
            'timestamp' => Craft::$app->getFormatter()->asTimestamp($element->dateUpdated, 'short', true),
            'creator' => $creator?->getName(),
            'draftName' => $element->draftName,
            'draftNotes' => $element->draftNotes,
            'modifiedAttributes' => $element->getModifiedAttributes(),
            'draftElementIds' => $draftElementIds,
            'draftElementUids' => $draftElementUids,
        ];

        if ($this->request->getIsCpRequest()) {
            [$docTitle, $title] = $this->_editElementTitles($element);
            $previewTargets = $element->getPreviewTargets();
            $data += $this->_fieldLayoutData($element, [
                'registerDeltas' => true,
            ]);
            $data += [
                'docTitle' => $docTitle,
                'title' => $title,
                'previewTargets' => $previewTargets,
                'previewParamValue' => $previewTargets ? Craft::$app->getSecurity()->hashData(StringHelper::randomString(10)) : null,
                'deltaNames' => Craft::$app->getView()->getDeltaNames(),
                'initialDeltaValues' => Craft::$app->getView()->getInitialDeltaValues(),
                'updatedTimestamp' => $element->dateUpdated->getTimestamp(),
                'canonicalUpdatedTimestamp' => $element->getCanonical()->dateUpdated->getTimestamp(),
            ];
        }

        // Make sure the user is authorized to preview the draft
        Craft::$app->getSession()->authorize("previewDraft:$element->draftId");

        return $this->_asSuccess(Craft::t('app', '{type} saved.', [
            'type' => Craft::t('app', 'Draft'),
        ]), $element, $data, true);
    }

    /**
     * Ensures that a provisional draft exists for the element, unless it’s already a draft.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionEnsureDraft(): Response
    {
        $this->requirePostRequest();

        /**
         * @var Element|DraftBehavior|null $element
         * @phpstan-ignore varTag.nativeType
         */
        $element = $this->_element(checkForProvisionalDraft: true);

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        if ($element->getIsDraft()) {
            return $this->asSuccess(data: [
                'elementId' => $element->id,
            ]);
        }

        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        if (!$elementsService->canCreateDrafts($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to create drafts for this element.');
        }

        $this->element = $element;

        // Make sure a provisional draft doesn't already exist for this element/user combo
        $provisionalId = $element::find()
            ->provisionalDrafts()
            ->draftOf($element->id)
            ->draftCreator($user->id)
            ->site('*')
            ->status(null)
            ->ids()[0] ?? null;

        if ($provisionalId) {
            return $this->asSuccess(data: [
                'elementId' => $provisionalId,
            ]);
        }

        /** @var Element|DraftBehavior $element */
        $draft = Craft::$app->getDrafts()->createDraft($element, $user->id, provisional: true);

        return $this->asSuccess(data: [
            'elementId' => $draft->id,
        ]);
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

        /**
         * @var Element|DraftBehavior|Response|null $element
         * @phpstan-ignore varTag.nativeType
         */
        $element = $this->_element();

        // this can happen if creating element via slideout, and we hit "create entry" before the autosave kicks in
        if ($element instanceof Response) {
            return $element;
        }

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

        if (!$elementsService->canSaveCanonical($element, $user)) {
            throw new ForbiddenHttpException($isUnpublishedDraft
                ? 'User not authorized to create this element.'
                : 'User not authorized to save this element.');
        }

        // Validate and save the draft
        if ($element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        // if we're about to apply an unpublished draft, set propagateRequired to true
        if ($isUnpublishedDraft) {
            $element->propagateRequired = true;
        }

        $element->applyingDraft = true;

        $namespace = $this->request->getHeaders()->get('X-Craft-Namespace');
        if (!$elementsService->saveElement($element, crossSiteValidate: ($namespace === null && Craft::$app->getIsMultiSite()))) {
            return $this->_asAppyDraftFailure($element);
        }

        $element->applyingDraft = false;

        if (!$isUnpublishedDraft) {
            $lockKey = "element:$element->canonicalId";
            $mutex = Craft::$app->getMutex();
            if (!$mutex->acquire($lockKey, 15)) {
                throw new ServerErrorHttpException('Could not acquire a lock to save the element.');
            }
        }

        $attributes = [];
        if ($element instanceof NestedElementInterface) {
            $attributes['updateSearchIndexForOwner'] = true;
        }

        try {
            $element->propagateRequired = false;
            $canonical = Craft::$app->getDrafts()->applyDraft($element, $attributes);
        } catch (InvalidElementException) {
            return $this->_asAppyDraftFailure($element);
        } finally {
            if (!$isUnpublishedDraft) {
                $mutex->release($lockKey);
            }
        }

        $elementsService->trackActivity($canonical, ElementActivity::TYPE_SAVE);

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

        return $this->_asSuccess($message, $canonical, supportsAddAnother: true);
    }

    private function _asAppyDraftFailure(ElementInterface $element): ?Response
    {
        if ($element->getIsUnpublishedDraft()) {
            $message = StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t create {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        } elseif ($element->isProvisionalDraft) {
            $message = StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
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

        $element = $this->_element();

        if ($element instanceof Response) {
            return $element;
        }

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

        /**
         * @var Element|RevisionBehavior|null $element
         * @phpstan-ignore varTag.nativeType
         */
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
        Craft::$app->getElements()->trackActivity($canonical, ElementActivity::TYPE_SAVE);

        return $this->_asSuccess(Craft::t('app', '{type} reverted to past revision.', [
            'type' => $element::displayName(),
        ]), $canonical);
    }

    /**
     * Returns an element’s missing field layout components.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 4.6.0
     */
    public function actionUpdateFieldLayout(): ?Response
    {
        $this->requirePostRequest();
        $this->requireCpRequest();

        if ($this->_elementId || $this->_elementUid) {
            $element = $this->_element();
        } else {
            $element = $this->_createElement();
        }

        // Prevalidate?
        if ($this->_prevalidate && $element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
            $element->validate();
        }

        /**
         * see https://github.com/craftcms/cms/issues/14635#issuecomment-2349006694 for details
         * @var Element|DraftBehavior|Response|null $element
         * @phpstan-ignore varTag.nativeType
         */
        if ($element instanceof Response) {
            return $element;
        }

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        if (!$elementsService->canView($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to view this element.');
        }

        $this->element = $element;
        $this->_applyParamsToElement($element);

        // Make sure nothing just changed that would prevent the user from saving
        if (!$elementsService->canView($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to view this element.');
        }

        $data = $this->_fieldLayoutData($this->element);

        $data += [
            'initialDeltaValues' => Craft::$app->getView()->getInitialDeltaValues(),
        ];

        return $this->_asSuccess('Field layout updated.', $element, $data, true);
    }

    private function _fieldLayoutData(ElementInterface $element, array $formConfig = []): array
    {
        $view = Craft::$app->getView();
        $namespace = $this->request->getHeaders()->get('X-Craft-Namespace');
        $fieldLayout = $element->getFieldLayout();
        $form = $fieldLayout->createForm($element, false, $formConfig + [
            'namespace' => $namespace,
            'registerDeltas' => false,
            'visibleElements' => $this->_visibleLayoutElements,
            'staticElements' => $this->_staticLayoutElements,
        ]);
        $missingElements = [];
        foreach ($form->tabs as $tab) {
            if (!$tab->getUid()) {
                continue;
            }

            $elementInfo = [];

            foreach ($tab->elements as [$layoutElement, $isConditional, $elementHtml, $isStatic]) {
                /** @var FieldLayoutComponent $layoutElement */
                /** @var bool $isConditional */
                /** @var string|bool $elementHtml */
                /** @var bool $isStatic */
                if ($isConditional) {
                    $elementInfo[] = [
                        'uid' => $layoutElement->uid,
                        'html' => $elementHtml,
                        'static' => $isStatic,
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

        return [
            'tabs' => $tabHtml,
            'missingElements' => $missingElements,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ];
    }

    /**
     * Returns any recent activity for an element, and records that the user is viewing the element.
     *
     * @return Response
     * @since 4.5.0
     */
    public function actionRecentActivity(): Response
    {
        $element = $this->_element();

        if ($element instanceof Response) {
            return $element;
        }

        if (!$element || $element->getIsRevision()) {
            throw new BadRequestHttpException('No element was identified by the request.');
        }

        $elementsService = Craft::$app->getElements();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $activity = $elementsService->getRecentActivity($element, $currentUser->id);
        $elementsService->trackActivity($element, ElementActivity::TYPE_VIEW, $currentUser);

        return $this->asJson([
            'activity' => array_map(function(ElementActivity $record) use ($element) {
                $recordIsCanonical = $record->element->getIsCanonical() || $record->element->isProvisionalDraft;
                $recordIsCanonicalAndPublished = $recordIsCanonical && !$record->element->getIsUnpublishedDraft();
                $isSameOrUpstream = $element->id === $record->element->id || $recordIsCanonical;

                if ($isSameOrUpstream) {
                    $messageParams = [
                        'user' => $record->user->getName(),
                        'type' => $recordIsCanonicalAndPublished ? $element::lowerDisplayName() : Craft::t('app', 'draft'),
                    ];
                    $message = match ($record->type) {
                        ElementActivity::TYPE_VIEW => Craft::t('app', '{user} is viewing this {type}.', $messageParams),
                        ElementActivity::TYPE_EDIT, ElementActivity::TYPE_SAVE => Craft::t('app', '{user} is editing this {type}.', $messageParams),
                    };
                } else {
                    $messageParams = [
                        'user' => $record->user->getName(),
                        'type' => $element::lowerDisplayName(),
                    ];
                    $message = match ($record->type) {
                        ElementActivity::TYPE_VIEW => Craft::t('app', '{user} is viewing a draft of this {type}.', $messageParams),
                        ElementActivity::TYPE_EDIT, ElementActivity::TYPE_SAVE => Craft::t('app', '{user} is editing a draft of this {type}.', $messageParams),
                    };
                }

                return [
                    'userId' => $record->user->id,
                    'userName' => $record->user->getName(),
                    'userThumb' => $record->user->getThumbHtml(26),
                    'type' => $record->type,
                    'message' => $message,
                ];
            }, $activity),
            'updatedTimestamp' => $element->dateUpdated->getTimestamp(),
            'canonicalUpdatedTimestamp' => $element->getCanonical()->dateUpdated->getTimestamp(),
        ]);
    }

    /**
     * Returns the requested element, populated with any posted attributes.
     *
     * @param array|null $elementInfo
     * @param bool $checkForProvisionalDraft
     * @param bool $strictSite
     * @return ElementInterface|Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    private function _element(
        ?array $elementInfo = null,
        bool $checkForProvisionalDraft = false,
        bool $strictSite = true,
    ): ElementInterface|Response|null {
        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        $elementType = $elementInfo['type'] ?? $this->_elementType;
        $elementId = $elementInfo['id'] ?? $this->_elementId;
        $elementUid = $elementInfo['uid'] ?? $this->_elementUid;
        $fieldId = $elementInfo['fieldId'] ?? $this->_fieldId;
        $ownerId = $elementInfo['ownerId'] ?? $this->_ownerId;
        $siteId = $elementInfo['siteId'] ?? $this->_siteId;
        $draftId = $elementInfo['draftId'] ?? $this->_draftId;
        $revisionId = $elementInfo['revisionId'] ?? $this->_revisionId;
        $provisional = $elementInfo['isProvisionalDraft'] ?? $this->_provisional;

        if (!$elementType) {
            if ($elementId) {
                $elementType = $elementsService->getElementTypeById($elementId);
                if (!$elementType) {
                    throw new BadRequestHttpException("Invalid element ID: $elementId");
                }
            } elseif ($elementUid) {
                $elementType = $elementsService->getElementTypeByUid($elementUid);
                if (!$elementType) {
                    throw new BadRequestHttpException("Invalid element UUID: $elementUid");
                }
            } else {
                throw new BadRequestHttpException('Request missing required param.');
            }
        }

        /** @var class-string<ElementInterface> $elementType */
        $this->_validateElementType($elementType);

        if ($elementType::isLocalized()) {
            if ($siteId) {
                $site = Craft::$app->getSites()->getSiteById($siteId, true);
                if (!$site) {
                    throw new BadRequestHttpException("Invalid side ID: $siteId");
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

            if ($strictSite) {
                $siteId = $site->id;
                $preferSites = null;
            } else {
                $siteId = Craft::$app->getSites()->getEditableSiteIds();
                $preferSites = [$site->id];
            }
        } else {
            $siteId = $preferSites = null;
        }

        // Loading an existing element?
        if ($draftId || $revisionId) {
            $query = $this->_elementQuery($elementType, $fieldId, $ownerId)
                ->draftId($draftId)
                ->revisionId($revisionId)
                ->provisionalDrafts($provisional)
                ->siteId($siteId)
                ->preferSites($preferSites)
                ->unique()
                ->status(null);

            if ($revisionId) {
                $query->trashed(null);
            }

            $element = $query->one();

            if (!$element) {
                // check for the canonical element as a fallback
                $element = $this->_elementById(
                    $elementId,
                    $elementUid,
                    $fieldId,
                    $ownerId,
                    false,
                    $elementType,
                    $user,
                    $siteId,
                    $preferSites,
                );
                if ($element && $elementsService->canView($element, $user)) {
                    if (!$this->request->getAcceptsJson()) {
                        return $this->redirect($element->getCpEditUrl());
                    }
                    return $element;
                }
                throw new BadRequestHttpException($draftId ? "Invalid draft ID: $draftId" : "Invalid revision ID: $revisionId");
            }
        } elseif ($elementId || $elementUid) {
            $element = $this->_elementById(
                $elementId,
                $elementUid,
                $fieldId,
                $ownerId,
                $checkForProvisionalDraft,
                $elementType,
                $user,
                $siteId,
                $preferSites,
            );
            if (!$element) {
                throw new BadRequestHttpException($elementId ? "Invalid element ID: $elementId" : "Invalid element UUID: $elementUid");
            }
        } else {
            return null;
        }

        if (!$elementsService->canView($element, $user)) {
            throw new ForbiddenHttpException('User not authorized to edit this element.');
        }

        if (
            !$strictSite &&
            isset($site) &&
            $element->siteId !== $site->id &&
            !$this->request->getAcceptsJson()
        ) {
            return $this->redirect($element->getCpEditUrl());
        }

        return $element;
    }

    /**
     * @param int|null $elementId
     * @param string|null $elementUid
     * @param int|null $fieldId
     * @param int|null $ownerId
     * @param bool $checkForProvisionalDraft
     * @param class-string<ElementInterface> $elementType
     * @param User $user
     * @param int|array|null $siteId
     * @param array|null $preferSites
     * @return ElementInterface|null
     */
    private function _elementById(
        ?int $elementId,
        ?string $elementUid,
        ?int $fieldId,
        ?int $ownerId,
        bool $checkForProvisionalDraft,
        string $elementType,
        User $user,
        int|array|null $siteId,
        ?array $preferSites,
    ): ?ElementInterface {
        if ($elementId) {
            // First check for a provisional draft, if we're open to it
            if ($checkForProvisionalDraft) {
                $element = $this->_elementQuery($elementType, $fieldId, $ownerId)
                    ->provisionalDrafts()
                    ->draftOf($elementId)
                    ->draftCreator($user)
                    ->siteId($siteId)
                    ->preferSites($preferSites)
                    ->unique()
                    ->status(null)
                    ->one();

                if ($element && $this->_canSave($element, $user)) {
                    return $element;
                }
            }

            $element = $this->_elementQuery($elementType, $fieldId, $ownerId)
                ->id($elementId)
                ->siteId($siteId)
                ->preferSites($preferSites)
                ->unique()
                ->drafts(null)
                ->provisionalDrafts(null)
                ->revisions(null)
                ->status(null)
                ->one();

            if ($element) {
                return $element;
            }

            // finally, check for an unpublished draft
            // (see https://github.com/craftcms/cms/issues/14199)
            return $this->_elementQuery($elementType, $fieldId, $ownerId)
                ->id($elementId)
                ->siteId($siteId)
                ->preferSites($preferSites)
                ->unique()
                ->draftOf(false)
                ->status(null)
                ->one();
        }

        if ($elementUid) {
            $element = $this->_elementQuery($elementType, $fieldId, $ownerId)
                ->uid($elementUid)
                ->siteId($siteId)
                ->preferSites($preferSites)
                ->unique()
                ->status(null)
                ->one();

            if ($element) {
                return $element;
            }

            // check for an unpublished draft if we got this far
            // (e.g. newly added matrix "block" or where autosaveDrafts is off)
            // https://github.com/craftcms/cms/issues/15985
            return $this->_elementQuery($elementType, $fieldId, $ownerId)
                ->uid($elementUid)
                ->siteId($siteId)
                ->preferSites($preferSites)
                ->unique()
                ->status(null)
                ->draftOf(false)
                ->one();
        }

        return null;
    }

    /**
     * @param class-string<ElementInterface> $elementType
     * @param int|null $fieldId
     * @param int|null $ownerId
     * @return ElementQueryInterface
     */
    private function _elementQuery(string $elementType, ?int $fieldId, ?int $ownerId): ElementQueryInterface
    {
        $query = $elementType::find();
        if ($query instanceof NestedElementQueryInterface) {
            $query
                ->fieldId($fieldId)
                ->ownerId($ownerId);
        }
        return $query;
    }

    /**
     * Creates a new element.
     *
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    private function _createElement(): ElementInterface
    {
        if (!$this->_elementType) {
            throw new BadRequestHttpException('Request missing required body param.');
        }

        $this->_validateElementType($this->_elementType);

        /** @var ElementInterface $element */
        $element = $this->element = Craft::createObject($this->_elementType);
        if (isset($this->_siteId) && $element::isLocalized()) {
            $element->siteId = $this->_siteId;
        }
        if (isset($this->_ownerId) && $element instanceof NestedElementInterface) {
            $element->setOwnerId($this->_ownerId);
        }
        $element->setAttributesFromRequest($this->_attributes + array_filter(['fieldId' => $this->_fieldId]));

        if (!Craft::$app->getElements()->canSave($element)) {
            throw new ForbiddenHttpException('User not authorized to create this element.');
        }

        if (!$element->slug) {
            $element->slug = ElementHelper::tempSlug();
        }

        return $element;
    }

    /**
     * Ensures the given element type is valid.
     *
     * @param class-string<ElementInterface> $elementType
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
        if (!$this->_applyParams) {
            return;
        }

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
            /** @var ElementInterface&DraftBehavior $element */
            if (isset($this->_draftName)) {
                $element->draftName = $this->_draftName;
            }
            if (isset($this->_notes)) {
                $element->draftNotes = $this->_notes;
            }
        } elseif (isset($this->_notes)) {
            $element->setRevisionNotes($this->_notes);
        }

        if ($this->_updateSearchIndexImmediately !== null) {
            $element->updateSearchIndexImmediately = $this->_updateSearchIndexImmediately;
        }

        $scenario = $element->getScenario();
        $element->setScenario(Element::SCENARIO_LIVE);
        $element->setAttributesFromRequest($this->_attributes + array_filter(['fieldId' => $this->_fieldId]));

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
     * @throws Throwable
     * @throws ServerErrorHttpException
     */
    private function _asSuccess(
        string $message,
        ElementInterface $element,
        array $data = [],
        bool $supportsAddAnother = false,
    ): Response {
        /** @var Element $element */
        // Don't call asModelSuccess() here so we can avoid including custom fields in the element data
        $data += [
            'modelName' => 'element',
            'element' => $element->toArray($element->attributes()),
        ];
        $response = $this->asSuccess($message, $data, $this->getPostedRedirectUrl($element), [
            'details' => !$element->dateDeleted
                ? Cp::elementChipHtml($element, ['hyperlink' => true])
                : null,
        ]);

        if ($supportsAddAnother && $this->_addAnother) {
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
            'errorSummary' => $this->_errorSummary($element),
            'invalidNestedElementIds' => $element->getInvalidNestedElementIds(),
        ];

        return $this->asFailure($message, $data, ['element' => $element]);
    }
}
