<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use yii\web\Response;

/**
 * The ElementIndexSettingsController class is a controller that handles various element index settings-related actions.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementIndexSettingsController extends BaseElementsController
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
     * Returns all the info needed by the Customize Sources modal.
     *
     * @return Response
     */
    public function actionGetCustomizeSourcesModalData(): Response
    {
        $this->requirePermission('customizeSources');

        $elementType = $this->elementType();

        // Get the source info
        $elementIndexesService = Craft::$app->getElementIndexes();
        $sources = $elementIndexesService->getSources($elementType);

        foreach ($sources as &$source) {
            if (array_key_exists('heading', $source)) {
                continue;
            }

            // Available custom field attributes
            $source['availableTableAttributes'] = [];
            foreach ($elementIndexesService->getSourceTableAttributes($elementType, $source['key']) as $key => $labelInfo) {
                $source['availableTableAttributes'][] = [$key, $labelInfo['label']];
            }

            // Selected table attributes
            $tableAttributes = $elementIndexesService->getTableAttributes($elementType, $source['key']);
            $source['tableAttributes'] = [];

            foreach ($tableAttributes as $attribute) {
                $source['tableAttributes'][] = [
                    $attribute[0],
                    $attribute[1]['label']
                ];
            }

            // Header column info
            if ($firstAttribute = reset($tableAttributes)) {
                list (, $attributeInfo) = $firstAttribute;
                // Is there a custom header col heading?
                if (isset($attributeInfo['defaultLabel'])) {
                    $source['headerColHeading'] = $attributeInfo['label'];
                    $source['defaultHeaderColHeading'] = $attributeInfo['defaultLabel'];
                } else {
                    $source['defaultHeaderColHeading'] = $attributeInfo['label'];
                }
            }
        }
        unset($source);

        // Get the available table attributes
        $availableTableAttributes = [];

        foreach ($elementIndexesService->getAvailableTableAttributes($elementType) as $key => $labelInfo) {
            $availableTableAttributes[] = [$key, $labelInfo['label']];
        }

        return $this->asJson([
            'sources' => $sources,
            'availableTableAttributes' => $availableTableAttributes,
        ]);
    }

    /**
     * Saves the Customize Sources modal settings.
     *
     * @return Response
     */
    public function actionSaveCustomizeSourcesModalSettings(): Response
    {
        $this->requirePermission('customizeSources');

        $elementType = $this->elementType();

        $sourceOrder = $this->request->getBodyParam('sourceOrder', []);
        $sources = $this->request->getBodyParam('sources', []);

        // Normalize to the way it's stored in the DB
        foreach ($sourceOrder as $i => $source) {
            if (isset($source['heading'])) {
                $sourceOrder[$i] = ['heading', $source['heading']];
            } else {
                $sourceOrder[$i] = ['key', $source['key']];
            }
        }

        // Remove the blank table attributes
        foreach ($sources as &$source) {
            $source['tableAttributes'] = array_filter($source['tableAttributes']);
        }
        unset($source);

        $settings = [
            'sourceOrder' => $sourceOrder,
            'sources' => $sources,
        ];

        if (Craft::$app->getElementIndexes()->saveSettings($elementType, $settings)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asErrorJson(Craft::t('app', 'A server error occurred.'));
    }
}
