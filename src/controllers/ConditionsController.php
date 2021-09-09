<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\conditions\BaseCondition;
use craft\helpers\Json;
use craft\web\Controller;

/**
 * The ConditionsController class is a controller that handles various condition related actions including managing
 * rendering of the condition, and adding and removing rules, and returning the result.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read string|\craft\conditions\BaseCondition $builderHtml
 */
class ConditionsController extends Controller
{
    /**
     * @inheritdoc
     */
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * @var BaseCondition
     */
    private BaseCondition $_condition;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->loadCondition();

        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function actionRender(): string
    {
        return $this->renderBuilderHtml();
    }

    /**
     * @return string
     */
    public function actionAddRule(): string
    {
        $ruleType = collect($this->_condition->getConditionRuleTypes())->first();
        $rule = Craft::$app->getConditions()->createConditionRule(['type' => $ruleType]);
        $rule->setCondition($this->_condition);

        $this->_condition->addConditionRule($rule);

        return $this->renderBuilderHtml();
    }

    /**
     * @return string
     */
    public function actionRemoveRule(): string
    {
        $ruleUid = Craft::$app->getRequest()->getRequiredBodyParam('uid');

        $conditionRules = $this->_condition->getConditionRules()->filter(function($rule) use ($ruleUid) {
            $uid = $rule->uid;
            return $uid != $ruleUid;
        })->all();

        $this->_condition->setConditionRules($conditionRules);

        return $this->renderBuilderHtml();
    }

    /**
     * @return BaseCondition
     */
    protected function loadCondition(): BaseCondition
    {
        $config = Craft::$app->getRequest()->getRequiredBodyParam('condition');

        return $this->_condition = Craft::$app->getConditions()->createCondition($config);
    }

    /**
     * @return string
     */
    protected function renderBuilderHtml(): string
    {
        $options = Craft::$app->getRequest()->getBodyParam('options', []);
        return $this->_condition->getBuilderHtml(Json::decodeIfJson($options));
    }
}
