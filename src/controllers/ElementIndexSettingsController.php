<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\services\ElementSources;
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
    public function beforeAction($action): bool
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
        $sourcesService = Craft::$app->getElementSources();
        $sources = $sourcesService->getSources($elementType);

        foreach ($sources as &$source) {
            if ($source['type'] === ElementSources::TYPE_HEADING) {
                continue;
            }

            // Available custom field attributes
            $source['availableTableAttributes'] = [];
            foreach ($sourcesService->getSourceTableAttributes($elementType, $source['key']) as $key => $labelInfo) {
                $source['availableTableAttributes'][] = [$key, $labelInfo['label']];
            }

            // Selected table attributes
            $tableAttributes = $sourcesService->getTableAttributes($elementType, $source['key']);
            array_shift($tableAttributes);
            $source['tableAttributes'] = array_map(fn($a) => [$a[0], $a[1]['label']], $tableAttributes);
        }
        unset($source);

        // Get the available table attributes
        $availableTableAttributes = [];

        foreach ($sourcesService->getAvailableTableAttributes($elementType) as $key => $labelInfo) {
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

        // Get the old source configs
        $projectConfig = Craft::$app->getProjectConfig();
        $oldSourceConfigs = $projectConfig->get("elementSources.$elementType") ?? [];
        $oldSourceConfigs = ArrayHelper::index(array_filter($oldSourceConfigs, fn($s) => $s['type'] === ElementSources::TYPE_NATIVE), 'key');

        $sourceOrder = $this->request->getBodyParam('sourceOrder', []);
        $sourceSettings = $this->request->getBodyParam('sources', []);
        $newSourceConfigs = [];

        // Normalize to the way it's stored in the DB
        foreach ($sourceOrder as $source) {
            if (isset($source['heading'])) {
                $newSourceConfigs[] = [
                    'type' => ElementSources::TYPE_HEADING,
                    'heading' => $source['heading'],
                ];
            } else if (isset($source['key'])) {
                $sourceConfig = [
                    'type' => ElementSources::TYPE_NATIVE,
                    'key' => $source['key'],
                ];
                // Were new settings posted?
                if (isset($sourceSettings[$source['key']])) {
                    $sourceConfig['tableAttributes'] = array_values(array_filter($sourceSettings[$source['key']]['tableAttributes'] ?? []));
                } else if (isset($oldSourceConfigs[$source['key']])) {
                    $sourceConfig += $oldSourceConfigs[$source['key']];
                }
                $newSourceConfigs[] = $sourceConfig;
            }
        }

        $projectConfig->set("elementSources.$elementType", $newSourceConfigs);
        return $this->asJson(['success' => true]);
    }
}
