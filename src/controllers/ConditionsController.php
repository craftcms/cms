<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\conditions\BaseCondition;
use craft\web\Controller;

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
     * @inheritdoc
     */
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE; // TODO move this to the the condition config?

    private BaseCondition $_condition;

    /**
     * @return string
     */
    public function actionRender(): string
    {
        // Render condition
        $this->_condition = $this->loadCondition();

        return $this->_condition->getBuilderHtml();
    }

    /**
     * @return string
     */
    public function actionAddRule(): string
    {
        $this->loadCondition();

        $ruleType = collect($this->_condition->getConditionRuleTypes())->first();
        $rule = Craft::$app->getConditions()->createConditionRule(['type' => $ruleType]);
        $rule->setCondition($this->_condition);

        $this->_condition->addConditionRule($rule);

        return $this->_condition->getBuilderHtml();
    }

    /**
     * @return string
     */
    public function actionRemoveRule()
    {
        $this->loadCondition();

        $ruleUid = Craft::$app->getRequest()->getRequiredBodyParam('uid');

        $conditionRules = $this->_condition->getConditionRules()->filter(function($rule) use ($ruleUid) {
            $uid = $rule->uid;
            return $uid != $ruleUid;
        })->all();

        $this->_condition->setConditionRules($conditionRules);

        return $this->_condition->getBuilderHtml();
    }

    /**
     * @return BaseCondition
     */
    protected function loadCondition(): BaseCondition
    {
        $config = Craft::$app->getRequest()->getBodyParam('condition');

        return $this->_condition = Craft::$app->getConditions()->createCondition($config);
    }
}
