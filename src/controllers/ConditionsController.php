<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\conditions\ConditionInterface;
use craft\base\conditions\ConditionRuleInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\Controller;
use Illuminate\Support\Collection;

/**
 * The ConditionsController class is a controller that handles various condition related actions including managing
 * rendering of the condition, and adding and removing rules, and returning the result.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ConditionsController extends Controller
{
    /**
     * @var ConditionInterface
     */
    private ConditionInterface $_condition;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $baseConfig = Json::decodeIfJson($this->request->getBodyParam('config'));
        $config = $this->request->getBodyParam($baseConfig['name']);
        $newRuleType = ArrayHelper::remove($config, 'new-rule-type');
        $conditionsService = Craft::$app->getConditions();
        $this->_condition = $conditionsService->createCondition($config);
        Craft::configure($this->_condition, $baseConfig);

        if ($newRuleType) {
            $newRuleType = Json::decodeIfJson($newRuleType);
            $rule = $conditionsService->createConditionRule($newRuleType);
            $rule->setAutofocus();
            $this->_condition->addConditionRule($rule);
        }

        return parent::beforeAction($action);
    }

    /**
     * @return string
     */
    public function actionRender(): string
    {
        return $this->_condition->getBuilderInnerHtml();
    }

    /**
     * @return string
     * @deprecated in 4.1.0
     */
    public function actionAddRule(): string
    {
        /** @var ConditionRuleInterface|null $rule */
        $rule = Collection::make($this->_condition->getSelectableConditionRules())
            ->sortBy(fn(ConditionRuleInterface $rule) => $rule->getLabel())
            ->first();

        if ($rule) {
            $rule->setAutofocus();
            $this->_condition->addConditionRule($rule);
        }

        return $this->_condition->getBuilderInnerHtml();
    }

    /**
     * @return string
     */
    public function actionRemoveRule(): string
    {
        $ruleUid = $this->request->getRequiredBodyParam('uid');
        $conditionRules = Collection::make($this->_condition->getConditionRules())
            ->filter(fn(ConditionRuleInterface $rule) => $rule->uid !== $ruleUid)
            ->all();
        $this->_condition->setConditionRules($conditionRules);
        return $this->_condition->getBuilderInnerHtml(true);
    }
}
