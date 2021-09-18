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
        $conditionsService = Craft::$app->getConditions();
        $view = Craft::$app->getView();

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

            if ($source['type'] === ElementSources::TYPE_CUSTOM && isset($source['condition'])) {
                $condition = $conditionsService->createCondition(ArrayHelper::remove($source, 'condition'));
                $view->startJsBuffer();
                $conditionBuilderHtml = $condition->getBuilderHtml([
                    'mainTag' => 'div',
                    'baseInputName' => "sources[{$source['key']}][condition]",
                ]);
                $conditionBuilderJs = $view->clearJsBuffer();
                $source += compact('conditionBuilderHtml', 'conditionBuilderJs');
            }
        }
        unset($source);

        // Get the available table attributes
        $availableTableAttributes = [];

        foreach ($sourcesService->getAvailableTableAttributes($elementType) as $key => $labelInfo) {
            $availableTableAttributes[] = [$key, $labelInfo['label']];
        }

        $view->startJsBuffer();
        $conditionBuilderHtml = $elementType::createCondition()->getBuilderHtml([
            'mainTag' => 'div',
            'baseInputName' => 'sources[SOURCE_KEY][condition]',
        ]);
        $conditionBuilderJs = $view->clearJsBuffer();

        return $this->asJson([
            'sources' => $sources,
            'availableTableAttributes' => $availableTableAttributes,
            'elementTypeName' => $elementType::displayName(),
            'conditionBuilderHtml' => $conditionBuilderHtml,
            'conditionBuilderJs' => $conditionBuilderJs,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
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
                            'condition' => $postedSettings['condition'],
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
