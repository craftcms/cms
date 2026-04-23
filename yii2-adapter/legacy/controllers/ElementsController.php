<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Event as YiiEvent;
use craft\events\DefineElementEditorHtmlEvent;
use craft\services\Drafts;
use craft\web\Controller;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Events\DefineElementEditorContent;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ElementActivity as ElementActivityFacade;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB as DbFacade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Throwable;
use yii\helpers\Markdown;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use function CraftCms\Cms\t;

/**
 * Elements controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0
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
        $value = Arr::pull($this->_attributes, $name, $this->request->getQueryParam($name));
        if ($value === null && $default !== null && $this->request->getIsPost()) {
            return $default;
        }
        return $value;
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

        if ($element->errors()->isNotEmpty()) {
            $allErrors = $element->errors()->getMessages();
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
                            if (preg_match(sprintf('/\[%s\]$/', Str::uuidPattern()), $keyParts[count($keyParts) - 3])) {
                                $key = implode('.', array_slice($keyParts, -2));
                            }
                        }

                        $errorItem = null;
                        if ($error !== null) {
                            $error = Markdown::processParagraph(htmlspecialchars($error));
                            $errorItem = Html::beginTag('li');
                            $errorItem .= Html::a(t($error), '#', [
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
                $heading = t('Found {num, number} {num, plural, =1{error} other{errors}}', [
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
                        'aria-label' => t('Error'),
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

        // Check save permissions before and after applying POST params to the element
        // in case the request was tampered with.
        Gate::authorize('save', $element);

        $this->_applyParamsToElement($element);

        Gate::authorize('save', $element);

        if ($element->enabled && $element->getEnabledForSite()) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        }

        $isNotNew = $element->id;
        if ($isNotNew) {
            $mutex = Cache::lock("element:$element->id", 15);
            if (!$mutex->get()) {
                throw new ServerErrorHttpException('Could not acquire a lock to save the element.');
            }
        }

        if ($element instanceof NestedElementInterface && property_exists($element, 'updateSearchIndexForOwner')) {
            $element->updateSearchIndexForOwner = true;
        }

        try {
            $namespace = $this->request->getHeaders()->get('X-Craft-Namespace');
            // crossSiteValidate only if it's multisite, element supports drafts and we're not in a slideout
            $success = Elements::saveElement(
                $element,
                crossSiteValidate: ($namespace === null && Sites::isMultiSite() && Gate::check('createDrafts', $element)),
            );
        } catch (UnsupportedSiteException $e) {
            $element->errors()->add('siteId', $e->getMessage());
            $success = false;
        } finally {
            if ($isNotNew) {
                $mutex->release();
            }
        }

        if (!$success) {
            return $this->_asFailure($element, mb_ucfirst(t('Couldn’t save {type}.', [
                'type' => $element::lowerDisplayName(),
            ])));
        }

        ElementActivityFacade::trackActivity($element, ElementActivityType::Save);

        // See if the user happens to have a provisional element. If so delete it.
        $provisional = $element::find()
            ->provisionalDrafts()
            ->draftOf($element->id)
            ->draftCreator(static::currentUser())
            ->siteId($element->siteId)
            ->status(null)
            ->one();

        if ($provisional) {
            Elements::deleteElement($provisional, true);
        }

        if (!$this->request->getAcceptsJson()) {
            // Tell all browser windows about the element save
            Craft::$app->getSession()->broadcastToJs([
                'event' => 'saveElement',
                'id' => $element->id,
            ]);
        }

        return $this->_asSuccess(t('{type} saved.', [
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
        $user = static::currentUser();

        // Check save permissions before and after applying POST params to the element
        // in case the request was tampered with.
        Gate::authorize('save', $element);

        // Get the new owner and make sure it's a derivative element,
        // and that its canonical element is the nested element's primary owner
        $owner = Elements::getElementById($this->_newOwnerId, siteId: $element->siteId);
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

        Gate::authorize('save', $owner);

        // Get the old sort order
        $sortOrder = DbFacade::table(Table::ELEMENTS_OWNERS)
            ->where('elementId', $element->id)
            ->where('ownerId', $element->getOwnerId())
            ->value('sortOrder');

        $element->setSortOrder($sortOrder);

        DbFacade::beginTransaction();

        try {
            // Remove existing ownership data for the element within the canonical owner,
            // and for its canonical element within the derivative
            DbFacade::table(Table::ELEMENTS_OWNERS)
                ->where(['elementId' => $element->id, 'ownerId' => $owner->getCanonicalId()])
                ->orWhere(['elementId' => $element->getCanonicalId(), 'ownerId' => $owner->id])
                ->delete();

            // Remove existing ownership data for the element within the canonical owner
            DbFacade::table(Table::ELEMENTS_OWNERS)
                ->where([
                    'elementId' => $element->id,
                    'ownerId' => $owner->getCanonicalId(),
                ])
                ->delete();

            // Remove the draft data, but preserve the canonicalId
            $element->setPrimaryOwner($owner);
            $element->setOwner($owner);
            Elements::saveElement($element);

            $this->_applyParamsToElement($element);

            Gate::authorize('save', $element);

            if ($element->enabled && $element->getEnabledForSite()) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
            }

            try {
                $success = Elements::saveElement($element);
            } catch (UnsupportedSiteException $e) {
                $element->errors()->add('siteId', $e->getMessage());
                $success = false;
            }

            if (!$success) {
                DbFacade::rollBack();
                return $this->_asFailure($element, mb_ucfirst(t('Couldn’t save {type}.', [
                    'type' => $element::lowerDisplayName(),
                ])));
            }

            if ($element->getIsDraft()) {
                app(Drafts::class)->removeDraftData($element);
            }

            DbFacade::commit();
        } catch (Throwable $e) {
            DbFacade::rollBack();
            throw $e;
        }

        return $this->_asSuccess(t('{type} saved.', [
            'type' => $element::displayName(),
        ]), $element);
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
                $elementType = Elements::getElementTypeById($elementId);
                if (!$elementType) {
                    throw new BadRequestHttpException("Invalid element ID: $elementId");
                }
            } elseif ($elementUid) {
                $elementType = Elements::getElementTypeByUid($elementUid);
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
                $site = Sites::getSiteById($siteId, true);
                if (!$site) {
                    throw new BadRequestHttpException("Invalid side ID: $siteId");
                }
                if (Sites::isMultiSite() && !$user->can("editSite:$site->uid")) {
                    throw new ForbiddenHttpException('User not authorized to edit content for this site.');
                }
            } else {
                $site = app(RequestedSite::class)->get();
                if (!$site) {
                    throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
                }
            }

            if ($strictSite) {
                $siteId = $site->id;
                $preferSites = null;
            } else {
                $siteId = Sites::getEditableSiteIds()->all();
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
                if ($element && $user->can('view', $element)) {
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

        if (!$user->can('view', $element)) {
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
     * Ensures the given element type is valid.
     *
     * @param class-string<ElementInterface> $elementType
     * @throws BadRequestHttpException
     */
    private function _validateElementType(string $elementType): void
    {
        if (!ComponentHelper::validateComponentClass($elementType, ElementInterface::class)) {
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
                $editableSiteIds = Sites::getEditableSiteIds()->all();
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
            /** @var ElementInterface $element */
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

        $scenario = $element->ruleset->getScenario();
        $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        $element->setAttributesFromRequest($this->_attributes + array_filter(['fieldId' => $this->_fieldId]));

        if ($this->_slug !== null) {
            $element->slug = $this->_slug;
        }

        $element->ruleset->useScenario($scenario);

        // Now that the element is fully configured, make sure the user can actually view it
        if (!Gate::check('view', $element)) {
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

        return $user->can('save', $element);
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
                ? app(ElementHtml::class)->elementChipHtml($element, ['hyperlink' => true])
                : null,
        ]);

        if ($supportsAddAnother && $this->_addAnother) {
            $user = static::currentUser();
            $newElement = $element->createAnother();

            if (!$newElement || !Gate::check('save', $newElement)) {
                throw new ServerErrorHttpException('Unable to create a new element.');
            }

            if (!$newElement->slug) {
                $newElement->slug = ElementHelper::tempSlug();
            }

            $newElement->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

            if (!app(\CraftCms\Cms\Element\Drafts::class)->saveElementAsDraft($newElement, $user->id, null, null, false)) {
                throw new ServerErrorHttpException(sprintf('Unable to create a new element: %s', implode(', ', $element->getErrorSummary(true))));
            }

            $url = $newElement->getCpEditUrl();

            if ($url) {
                $url = Url::urlWithParams($url, ['fresh' => 1]);
            } else {
                $url = Url::actionUrl('elements/edit', [
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
            'errors' => $element->errors()->getMessages(),
            'errorSummary' => $this->_errorSummary($element),
            'invalidNestedElementIds' => $element->getInvalidNestedElementIds(),
        ];

        return $this->asFailure($message, $data, ['element' => $element]);
    }

    public static function registerEvents(): void
    {
        Event::listen(function(DefineElementEditorContent $event) {
            if (!YiiEvent::hasHandlers(ElementsController::class, ElementsController::EVENT_DEFINE_EDITOR_CONTENT)) {
                return;
            }

            $yiiEvent = new DefineElementEditorHtmlEvent([
                'element' => $event->element,
                'html' => $event->html,
                'static' => $event->static,
            ]);

            YiiEvent::trigger(ElementsController::class, ElementsController::EVENT_DEFINE_EDITOR_CONTENT, $yiiEvent);

            $event->html = $yiiEvent->html;
        });
    }
}
