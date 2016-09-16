<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use yii\web\Response;

/**
 * The ElementIndexSettingsController class is a controller that handles various element index settings-related actions.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementIndexSettingsController extends BaseElementsController
{
    // Public Methods
    // =========================================================================

    /**
     * Returns all the info needed by the Customize Sources modal.
     *
     * @return Response
     */
    public function actionGetCustomizeSourcesModalData()
    {
        $this->requireAdmin();

        $elementType = $this->getElementType();

        // Get the source info
        $elementIndexesService = Craft::$app->getElementIndexes();
        $sources = $elementIndexesService->getSources($elementType);

        foreach ($sources as &$source) {
            if (array_key_exists('heading', $source)) {
                continue;
            }

            $tableAttributes = $elementIndexesService->getTableAttributes($elementType, $source['key']);
            $source['tableAttributes'] = [];

            foreach ($tableAttributes as $attribute) {
                $source['tableAttributes'][] = [
                    $attribute[0],
                    $attribute[1]['label']
                ];
            }
        }

        // Get the available table attributes
        $availableTableAttributes = [];

        foreach ($elementIndexesService->getAvailableTableAttributes($elementType) as $key => $labelInfo) {
            $availableTableAttributes[] = [
                $key,
                Craft::t('site', $labelInfo['label'])
            ];
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
    public function actionSaveCustomizeSourcesModalSettings()
    {
        $this->requireAdmin();

        $elementType = $this->getElementType();

        $request = Craft::$app->getRequest();
        $sourceOrder = $request->getBodyParam('sourceOrder', []);
        $sources = $request->getBodyParam('sources', []);

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

        $settings = [
            'sourceOrder' => $sourceOrder,
            'sources' => $sources,
        ];

        if (Craft::$app->getElementIndexes()->saveSettings($elementType, $settings)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asErrorJson(Craft::t('app', 'An unknown error occurred.'));
    }
}
