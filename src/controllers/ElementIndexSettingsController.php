<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\services\ElementSources;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use yii\base\NotSupportedException;
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

        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType();

        // Get the source info
        $sourcesService = Craft::$app->getElementSources();
        $sources = $sourcesService->getSources($elementType, ElementSources::CONTEXT_INDEX);

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

        try {
            $conditionBuilder = $elementType::createCondition();
        } catch (NotSupportedException $e) {
            $conditionBuilder = null;
        }

        $response = [
            'sources' => $sources,
            'availableTableAttributes' => $availableTableAttributes,
            'elementTypeName' => $elementType::displayName(),
        ];

        if ($conditionBuilder) {
            $view = Craft::$app->getView();
//            $view->registerAssetBundle(ConditionBuilderAsset::class);
            $view->startJsBuffer();
            $conditionBuilderHtml = $conditionBuilder->getBuilderHtml([
                'mainTag' => 'div',
                'baseInputName' => 'sources[SOURCE_KEY][criteria]',
            ]);
            $conditionBuilderJs = $view->clearJsBuffer();
            $response += [
                'conditionBuilderHtml' => $conditionBuilderHtml,
                'conditionBuilderJs' => $conditionBuilderJs,
                'headHtml' => $view->getHeadHtml(),
                'bodyHtml' => $view->getBodyHtml(),
            ];
        }

        return $this->asJson($response);
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
        $oldSourceConfigs = ArrayHelper::index(array_filter($oldSourceConfigs, fn($s) => $s['type'] !== ElementSources::TYPE_HEADING), 'key');

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
                $isCustom = strpos($source['key'], 'custom:') === 0;
                $sourceConfig = [
                    'type' => $isCustom ? ElementSources::TYPE_CUSTOM : ElementSources::TYPE_NATIVE,
                    'key' => $source['key'],
                ];

                // Were new settings posted?
                if (isset($sourceSettings[$source['key']])) {
                    $postedSettings = $sourceSettings[$source['key']];
                    $sourceConfig['tableAttributes'] = array_values(array_filter($postedSettings['tableAttributes'] ?? []));

                    if ($isCustom) {
                        $sourceConfig += [
                            'label' => $postedSettings['label'],
                        ];
                    }
                } else if (isset($oldSourceConfigs[$source['key']])) {
                    $sourceConfig += $oldSourceConfigs[$source['key']];
                } else if ($isCustom) {
                    // Ignore it
                    continue;
                }
                $newSourceConfigs[] = $sourceConfig;
            }
        }

        $projectConfig->set("elementSources.$elementType", $newSourceConfigs);
        return $this->asJson(['success' => true]);
    }
}
