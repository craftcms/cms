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
use craft\models\UserGroup;
use craft\services\ElementSources;
use craft\services\ProjectConfig;
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
        $this->requireAdmin();

        return true;
    }

    /**
     * Returns all the info needed by the Customize Sources modal.
     *
     * @return Response
     */
    public function actionGetCustomizeSourcesModalData(): Response
    {
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

            if ($source['type'] === ElementSources::TYPE_CUSTOM) {
                if (isset($source['condition'])) {
                    $condition = $conditionsService->createCondition(ArrayHelper::remove($source, 'condition'));
                    $condition->mainTag = 'div';
                    $condition->name = "sources[{$source['key']}][condition]";
                    $condition->forProjectConfig = true;
                    $condition->queryParams = ['site', 'status'];
                    $condition->addRuleLabel = Craft::t('app', 'Add a filter');

                    $view->startJsBuffer();
                    $conditionBuilderHtml = $condition->getBuilderHtml();
                    $conditionBuilderJs = $view->clearJsBuffer();
                    $source += compact('conditionBuilderHtml', 'conditionBuilderJs');
                }

                if (isset($source['userGroups']) && $source['userGroups'] === false) {
                    $source['userGroups'] = [];
                }
            }
        }
        unset($source);

        // Get the available table attributes
        $availableTableAttributes = [];

        foreach ($sourcesService->getAvailableTableAttributes($elementType) as $key => $labelInfo) {
            $availableTableAttributes[] = [$key, $labelInfo['label']];
        }

        $condition = $elementType::createCondition();
        $condition->id = '__ID__';
        $condition->name = 'sources[__SOURCE_KEY__][condition]';
        $condition->mainTag = 'div';
        $condition->forProjectConfig = true;
        $condition->queryParams = ['site', 'status'];
        $condition->addRuleLabel = Craft::t('app', 'Add a filter');

        $view->startJsBuffer();
        $conditionBuilderHtml = $condition->getBuilderHtml();
        $conditionBuilderJs = $view->clearJsBuffer();

        $userGroups = collect(Craft::$app->getUserGroups()->getAllGroups())
            ->map(fn(UserGroup $group) => [
                'label' => Craft::t('site', $group->name),
                'value' => $group->uid,
            ])
            ->all();

        return $this->asJson([
            'sources' => $sources,
            'availableTableAttributes' => $availableTableAttributes,
            'elementTypeName' => $elementType::displayName(),
            'conditionBuilderHtml' => $conditionBuilderHtml,
            'conditionBuilderJs' => $conditionBuilderJs,
            'userGroups' => $userGroups,
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
        $elementType = $this->elementType();

        // Get the old source configs
        $projectConfig = Craft::$app->getProjectConfig();
        $oldSourceConfigs = $projectConfig->get(ProjectConfig::PATH_ELEMENT_SOURCES . ".$elementType") ?? [];
        $oldSourceConfigs = ArrayHelper::index(array_filter($oldSourceConfigs, fn($s) => $s['type'] !== ElementSources::TYPE_HEADING), 'key');

        $conditionsService = Craft::$app->getConditions();

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
            } elseif (isset($source['key'])) {
                $isCustom = str_starts_with($source['key'], 'custom:');
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
                            'condition' => $conditionsService->createCondition($postedSettings['condition'])->getConfig(),
                        ];

                        if (isset($postedSettings['userGroups']) && $postedSettings['userGroups'] !== '*') {
                            $sourceConfig['userGroups'] = is_array($postedSettings['userGroups']) ? $postedSettings['userGroups'] : false;
                        }
                    }
                } elseif (isset($oldSourceConfigs[$source['key']])) {
                    $sourceConfig += $oldSourceConfigs[$source['key']];
                } elseif ($isCustom) {
                    // Ignore it
                    continue;
                }

                $newSourceConfigs[] = $sourceConfig;
            }
        }

        $projectConfig->set(ProjectConfig::PATH_ELEMENT_SOURCES . ".$elementType", $newSourceConfigs);
        return $this->asSuccess();
    }
}
