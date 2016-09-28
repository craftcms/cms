<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\elements\Category;
use craft\app\errors\InvalidTypeException;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\ElementHelper;
use craft\app\helpers\StringHelper;
use craft\app\services\Elements;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The ElementsController class is a controller that handles various element related actions including retrieving and
 * saving element and their corresponding HTML.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementsController extends BaseElementsController
{
    // Public Methods
    // =========================================================================

    /**
     * Renders and returns the body of an ElementSelectorModal.
     *
     * @return Response
     */
    public function actionGetModalBody()
    {
        $sourceKeys = Craft::$app->getRequest()->getParam('sources');
        $elementType = $this->getElementType();
        $context = $this->getContext();

        if (is_array($sourceKeys)) {
            $sources = [];

            foreach ($sourceKeys as $key) {
                $source = $elementType::getSourceByKey($key, $context);

                if ($source) {
                    $sources[$key] = $source;
                }
            }
        } else {
            $sources = Craft::$app->getElementIndexes()->getSources($elementType);
        }

        if (!empty($sources) && count($sources) === 1) {
            $firstSource = ArrayHelper::getFirstValue($sources);
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
    public function actionGetEditorHtml()
    {
        /*$elementId = Craft::$app->getRequest()->getRequiredBodyParam('elementId');
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');
        $elementType = Craft::$app->getElements()->getElementTypeById($elementId);
        $element = Craft::$app->getElements()->getElementById($elementId, $elementType, $siteId);

        if (!$element) {
            throw new NotFoundHttpException('Element could not be found');
        }

        if (!$element->getIsEditable()) {
            throw new ForbiddenHttpException('User is not permitted to edit this element');
        }*/

        $element = $this->_getEditorElement();
        $includeSites = (bool)Craft::$app->getRequest()->getBodyParam('includeSites', false);

        return $this->_getEditorHtmlResponse($element, $includeSites);
    }

    /**
     * Saves an element.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested element cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit the requested element
     */
    public function actionSaveElement()
    {
        /** @var Element $element */
        $element = $this->_getEditorElement();
        $namespace = Craft::$app->getRequest()->getRequiredBodyParam('namespace');
        $params = Craft::$app->getRequest()->getBodyParam($namespace);

        if (isset($params['title'])) {
            $element->title = $params['title'];
            unset($params['title']);
        }

        if (isset($params['fields'])) {
            $fields = $params['fields'];
            $element->setFieldValuesFromPost($fields);
            unset($params['fields']);
        }

        // Either way, at least tell the element where its content comes from
        $element->setContentPostLocation($namespace.'.fields');

        // Now save it
        if ($element::saveElement($element, $params)) {
            $response = [
                'success' => true,
                'id' => $element->id,
                'siteId' => $element->siteId,
                'newTitle' => (string)$element,
                'cpEditUrl' => $element->getCpEditUrl(),
            ];

            // Should we be including table attributes too?
            $sourceKey = Craft::$app->getRequest()->getBodyParam('includeTableAttributesForSource');

            if ($sourceKey) {
                $attributes = Craft::$app->getElementIndexes()->getTableAttributes($element->className(), $sourceKey);

                // Drop the first one
                array_shift($attributes);

                foreach ($attributes as $attribute) {
                    $response['tableAttributes'][$attribute[0]] = $element->getTableAttributeHtml($element, $attribute[0]);
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
    public function actionGetCategoriesInputHtml()
    {
        $request = Craft::$app->getRequest();
        $categoryIds = $request->getParam('categoryIds', []);

        // Fill in the gaps
        $categoryIds = Craft::$app->getCategories()->fillGapsInCategoryIds($categoryIds);

        if ($categoryIds) {
            $categories = Category::find()
                ->id($categoryIds)
                ->siteId($request->getParam('siteId'))
                ->status(null)
                ->enabledForSite(false)
                ->limit($request->getParam('limit'))
                ->all();
        } else {
            $categories = [];
        }

        $html = Craft::$app->getView()->renderTemplate('_components/fieldtypes/Categories/input',
            [
                'elements' => $categories,
                'id' => $request->getParam('id'),
                'name' => $request->getParam('name'),
                'selectionLabel' => $request->getParam('selectionLabel'),
            ]);

        return $this->asJson([
            'html' => $html,
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the element that is currently being edited.
     *
     * @return ElementInterface
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    private function _getEditorElement()
    {
        $request = Craft::$app->getRequest();
        $elementsService = Craft::$app->getElements();

        $elementId = $request->getBodyParam('elementId');
        $siteId = $request->getBodyParam('siteId') ?: Craft::$app->getSites()->currentSite->id;

        // Determine the element type
        $elementType = $request->getBodyParam('elementType');

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
        if ($elementId !== null) {
            $element = $elementsService->getElementById($elementId, $elementType, $siteId);

            if (!$element) {
                throw new BadRequestHttpException('No element exists with the ID '.$elementId);
            }
        } else {
            $element = $elementType::create([]);
        }

        /** @var Element $element */
        // Make sure the user is allowed to edit this site
        $userService = Craft::$app->getUser();
        if (Craft::$app->getIsMultiSite() && $elementType->isLocalized() && !$userService->checkPermission('editSite:'.$element->siteId)) {
            // Find the first site the user does have permission to edit
            $elementSiteIds = [];
            $newSiteId = null;

            foreach (ElementHelper::getSupportedSitesForElement($element) as $siteInfo) {
                $elementSiteIds[] = $siteInfo['siteId'];
            }

            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                if (in_array($siteId, $elementSiteIds) && $userService->checkPermission('editSite:'.$siteId)) {
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
                $element = $elementsService->getElementById($elementId, $elementType, $siteId);
            } else {
                $element->siteId = $siteId;
            }
        }

        // Populate it with any posted attributes
        $attributes = $request->getBodyParam('attributes', []);
        $attributes['siteId'] = $siteId;

        if ($attributes) {
            $element->setAttributes($attributes);
        }

        // Make sure it's editable
        // (ElementHelper::isElementEditable() is overkill here since we've already verified the user can edit the element's site)
        if (!$element->getIsEditable()) {
            throw new ForbiddenHttpException('The user doesn’t have permission to edit this element');
        }

        return $element;
    }

    /**
     * Returns the editor HTML response for a given element.
     *
     * @param ElementInterface $element
     * @param boolean          $includeSites
     *
     * @return Response
     * @throws ForbiddenHttpException if the user is not permitted to edit content in any of the sites supported by this element
     */
    private function _getEditorHtmlResponse(ElementInterface $element, $includeSites)
    {
        /** @var Element $element */
        $siteIds = ElementHelper::getEditableSiteIdsForElement($element);

        if (!$siteIds) {
            throw new ForbiddenHttpException('User not permitted to edit content in any of the sites supported by this element');
        }

        if ($includeSites) {
            if (count($siteIds) > 1) {
                $response['siteIds'] = [];

                foreach ($siteIds as $siteId) {
                    $site = Craft::$app->getSites()->getSiteById($siteId);

                    $response['sites'][] = [
                        'id' => $siteId,
                        'name' => Craft::t('site', $site->name),
                    ];
                }
            } else {
                $response['sites'] = null;
            }
        }

        $response['siteId'] = $element->siteId;

        $namespace = 'editor_'.StringHelper::randomString(10);
        Craft::$app->getView()->setNamespace($namespace);

        $response['html'] = '<input type="hidden" name="namespace" value="'.$namespace.'">';

        if ($element->id) {
            $response['html'] .= '<input type="hidden" name="elementId" value="'.$element->id.'">';
        }

        if ($element->siteId) {
            $response['html'] .= '<input type="hidden" name="siteId" value="'.$element->siteId.'">';
        }

        $response['html'] .= '<div class="meta">'.
            Craft::$app->getView()->namespaceInputs($element::getEditorHtml($element)).
            '</div>';

        $view = Craft::$app->getView();
        $response['headHtml'] = $view->getHeadHtml();
        $response['footHtml'] = $view->getBodyHtml();

        return $this->asJson($response);
    }
}
