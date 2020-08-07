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
use craft\elements\Category;
use craft\errors\InvalidTypeException;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The ElementsController class is a controller that handles various element related actions including retrieving and
 * saving element and their corresponding HTML.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementsController extends BaseElementsController
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireAcceptsJson();

        return true;
    }

    /**
     * Renders and returns the body of an ElementSelectorModal.
     *
     * @return Response
     */
    public function actionGetModalBody(): Response
    {
        $sourceKeys = $this->request->getParam('sources');
        $elementType = $this->elementType();
        $context = $this->context();

        $showSiteMenu = $this->request->getParam('showSiteMenu', 'auto');

        if ($showSiteMenu !== 'auto') {
            $showSiteMenu = (bool)$showSiteMenu;
        }

        if (is_array($sourceKeys)) {
            $sourceKeys = array_flip($sourceKeys);
            $allSources = Craft::$app->getElementIndexes()->getSources($elementType);
            $sources = [];
            $nextHeading = null;

            foreach ($allSources as $source) {
                if (isset($source['heading'])) {
                    // Queue the heading up to be included only if one of the following sources were requested
                    $nextHeading = $source;
                } else if (isset($sourceKeys[$source['key']])) {
                    if ($nextHeading !== null) {
                        $sources[] = $nextHeading;
                        $nextHeading = null;
                    }
                    $sources[] = $source;
                    unset($sourceKeys[$source['key']]);
                }
            }

            // Did we miss any source keys? (This could happen if some are nested)
            if (!empty($sourceKeys)) {
                foreach (array_keys($sourceKeys) as $key) {
                    $source = ElementHelper::findSource($elementType, $key, $context);
                    if ($source !== null) {
                        $sources[$key] = $source;
                    }
                }
            }
        } else {
            $sources = Craft::$app->getElementIndexes()->getSources($elementType);
        }

        if (!empty($sources) && count($sources) === 1) {
            $firstSource = reset($sources);
            $showSidebar = !empty($firstSource['nested']);
        } else {
            $showSidebar = !empty($sources);
        }

        return $this->asJson([
            'html' => $this->getView()->renderTemplate('_elements/modalbody', [
                'context' => $context,
                'elementType' => $elementType,
                'sources' => $sources,
                'showSidebar' => $showSidebar,
                'showSiteMenu' => $showSiteMenu,
            ])
        ]);
    }

    /**
     * Returns the HTML for an element editor HUD.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested element cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit the requested element
     */
    public function actionGetEditorHtml(): Response
    {
        $element = $this->_getEditorElement();
        $includeSites = (bool)$this->request->getBodyParam('includeSites', false);

        return $this->_getEditorHtmlResponse($element, $includeSites);
    }

    /**
     * Saves an element.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested element cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit the requested element
     */
    public function actionSaveElement(): Response
    {
        $element = $this->_getEditorElement();

        // Figure out where the data will be in POST
        $namespace = $this->request->getRequiredBodyParam('namespace');

        // Configure the element
        $params = $this->request->getBodyParam($namespace, []);
        ArrayHelper::remove($params, 'fields');
        Craft::configure($element, $params);

        // Set the custom field values
        $element->setFieldValuesFromRequest($namespace . '.fields');

        // Now save it
        if ($element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        if (Craft::$app->getElements()->saveElement($element)) {
            $response = [
                'success' => true,
                'id' => $element->id,
                'siteId' => $element->siteId,
                'newTitle' => (string)$element,
                'cpEditUrl' => $element->getCpEditUrl(),
            ];

            // Should we be including table attributes too?
            $sourceKey = $this->request->getBodyParam('includeTableAttributesForSource');

            if ($sourceKey) {
                $attributes = Craft::$app->getElementIndexes()->getTableAttributes(get_class($element), $sourceKey);

                // Drop the first one
                array_shift($attributes);

                foreach ($attributes as $attribute) {
                    $response['tableAttributes'][$attribute[0]] = $element->getTableAttributeHtml($attribute[0]);
                }
            }

            return $this->asJson($response);
        }

        return $this->_getEditorHtmlResponse($element, false);
    }

    /**
     * Returns the HTML for a Categories field input, based on a given list of selected category IDs.
     *
     * @return Response
     */
    public function actionGetCategoriesInputHtml(): Response
    {
        $categoryIds = $this->request->getParam('categoryIds', []);

        /** @var Category[] $categories */
        $categories = [];

        if (!empty($categoryIds)) {
            $categories = Category::find()
                ->id($categoryIds)
                ->siteId($this->request->getParam('siteId'))
                ->anyStatus()
                ->all();

            // Fill in the gaps
            $categoriesService = Craft::$app->getCategories();
            $categoriesService->fillGapsInCategories($categories);

            // Enforce the branch limit
            if ($branchLimit = $this->request->getParam('branchLimit')) {
                $categoriesService->applyBranchLimitToCategories($categories, $branchLimit);
            }
        }

        $html = $this->getView()->renderTemplate('_components/fieldtypes/Categories/input',
            [
                'elements' => $categories,
                'id' => $this->request->getParam('id'),
                'name' => $this->request->getParam('name'),
                'selectionLabel' => $this->request->getParam('selectionLabel'),
            ]);

        return $this->asJson([
            'html' => $html,
        ]);
    }

    /**
     * Returns the HTML for a single element
     *
     * @return Response
     */
    public function actionGetElementHtml(): Response
    {
        $elementId = $this->request->getRequiredBodyParam('elementId');
        $siteId = $this->request->getBodyParam('siteId', null);
        $size = $this->request->getBodyParam('size', null);
        $viewMode = $this->request->getBodyParam('viewMode', null);
        $context = $this->request->getBodyParam('context', 'field');
        $element = Craft::$app->getElements()->getElementById($elementId, null, $siteId);

        $view = $this->getView();
        $html = $view->renderTemplate('_elements/element', compact(
            'element',
            'size',
            'viewMode',
            'context'
        ));

        return $this->asJson([
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
        ]);
    }

    /**
     * Returns the element that is currently being edited.
     *
     * @return ElementInterface
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    private function _getEditorElement(): ElementInterface
    {
        $elementsService = Craft::$app->getElements();

        $elementId = $this->request->getBodyParam('elementId');
        /** @noinspection PhpUnhandledExceptionInspection */
        $siteId = $this->request->getBodyParam('siteId') ?: Craft::$app->getSites()->getCurrentSite()->id;

        // Determine the element type
        $elementType = $this->request->getBodyParam('elementType');

        if ($elementType === null && $elementId !== null) {
            $elementType = $elementsService->getElementTypeById($elementId);
        }

        if ($elementType === null) {
            throw new BadRequestHttpException('Request missing required body param');
        }

        // Make sure it's a valid element type
        // TODO: should probably move the code inside try{} to a helper method
        try {
            if (!is_subclass_of($elementType, ElementInterface::class)) {
                throw new InvalidTypeException($elementType, ElementInterface::class);
            }
        } catch (InvalidTypeException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        // Instantiate the element
        $attributes = $this->request->getBodyParam('attributes', []);
        $element = $this->_getEditorElementInternal($elementId, $elementType, $siteId, $attributes);

        $site = Craft::$app->getSites()->getSiteById($siteId);

        // Make sure the user is allowed to edit this site
        $userSession = Craft::$app->getUser();
        if (Craft::$app->getIsMultiSite() && $elementType::isLocalized() && !$userSession->checkPermission('editSite:' . $site->uid)) {
            // Find the first site the user does have permission to edit
            $elementSiteIds = [];
            $newSiteId = null;

            foreach (ElementHelper::supportedSitesForElement($element) as $siteInfo) {
                $elementSiteIds[] = $siteInfo['siteId'];
            }

            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                if (in_array($siteId, $elementSiteIds, false) && $userSession->checkPermission('editSite:' . $site->uid)) {
                    $newSiteId = $siteId;
                    break;
                }
            }

            if ($newSiteId === null) {
                // Couldn't find an editable site supported by the element
                throw new ForbiddenHttpException('The user doesn’t have permission to edit this element');
            }

            // Apply the new site
            $siteId = $newSiteId;

            if ($elementId !== null) {
                $element = $this->_getEditorElementInternal($elementId, $elementType, $siteId, $attributes);
            } else {
                $element->siteId = $siteId;
            }
        }

        // Make sure it's editable
        // (ElementHelper::isElementEditable() is overkill here since we've already verified the user can edit the element's site)
        if (!$element->getIsEditable()) {
            throw new ForbiddenHttpException('The user doesn’t have permission to edit this element');
        }

        // Prevalidate?
        if ($this->request->getBodyParam('prevalidate') && $element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
            $element->validate();
        }

        return $element;
    }

    /**
     * Returns the editor element populated with the posted attributes.
     *
     * @param int|null $elementId
     * @param string $elementType
     * @param int $siteId
     * @param array $attributes
     * @return ElementInterface
     * @throws BadRequestHttpException
     */
    private function _getEditorElementInternal(int $elementId = null, string $elementType, int $siteId, array $attributes): ElementInterface
    {
        if ($elementId !== null) {
            $element = Craft::$app->getElements()->getElementById($elementId, $elementType, $siteId);

            if (!$element) {
                throw new BadRequestHttpException('No element exists with the ID ' . $elementId);
            }
        } else {
            $element = new $elementType();
        }

        // Populate it with any posted attributes
        Craft::configure($element, $attributes);
        $element->siteId = $siteId;

        return $element;
    }

    /**
     * Returns the editor HTML response for a given element.
     *
     * @param ElementInterface $element
     * @param bool $includeSites
     * @return Response
     * @throws ForbiddenHttpException if the user is not permitted to edit content in any of the sites supported by this element
     */
    private function _getEditorHtmlResponse(ElementInterface $element, bool $includeSites): Response
    {
        $siteIds = ElementHelper::editableSiteIdsForElement($element);

        if (empty($siteIds)) {
            throw new ForbiddenHttpException('User not permitted to edit content in any of the sites supported by this element');
        }

        $response = [];

        if ($includeSites) {
            $response['sites'] = [];

            foreach ($siteIds as $siteId) {
                $site = Craft::$app->getSites()->getSiteById($siteId);

                $response['sites'][] = [
                    'id' => $siteId,
                    'name' => Craft::t('site', $site->name),
                ];
            }
        }

        $response['siteId'] = $element->siteId;

        $view = $this->getView();
        $namespace = 'editor_' . StringHelper::randomString(10);
        $view->setNamespace($namespace);

        $response['html'] = '<input type="hidden" name="namespace" value="' . $namespace . '">';

        if ($element->id !== null) {
            $response['html'] .= '<input type="hidden" name="elementId" value="' . $element->id . '">';
        }

        if ($element->siteId !== null) {
            $response['html'] .= '<input type="hidden" name="siteId" value="' . $element->siteId . '">';
        }

        $response['html'] .= '<div class="meta">' .
            $view->namespaceInputs((string)$element->getEditorHtml()) .
            '</div>';

        // Set the field layout id to a discrete value, in case we know it.
        if ($element->fieldLayoutId !== null) {
            $response['html'] .= '<input type="hidden" name="fieldLayoutId" value="' . $element->fieldLayoutId . '">';
        }

        $response['headHtml'] = $view->getHeadHtml();
        $response['footHtml'] = $view->getBodyHtml();
        $response['deltaNames'] = $view->getDeltaNames();

        return $this->asJson($response);
    }
}
