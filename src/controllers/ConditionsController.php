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
     * @var array
     */
    private $_options = [];

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
        $this->_options = Json::decodeIfJson($this->request->getBodyParam('options', []));
        $conditionRuleTypes = Json::decodeIfJson($this->request->getBodyParam('conditionRuleTypes'));

        // Make baseInputName into a param path
        $this->_options['baseInputName'] = $this->_options['baseInputName'] ?? 'condition';
        $baseInputNamePath = str_replace(['[', ']'], ['.', ''], $this->_options['baseInputName']);

        $config = $this->request->getBodyParam($baseInputNamePath);

        $this->_condition = Craft::$app->getConditions()->createCondition($config);
        if ($conditionRuleTypes !== null) {
            $this->_condition->setConditionRuleTypes($conditionRuleTypes);
        }

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
        if ($ruleType) {
            $rule = Craft::$app->getConditions()->createConditionRule(['type' => $ruleType]);
            $rule->setCondition($this->_condition);

            $this->_condition->addConditionRule($rule);
        }

        return $this->renderBuilderHtml();
    }

    /**
     * @return string
     */
    public function actionRemoveRule(): string
    {
        $ruleUid = $this->request->getRequiredBodyParam('uid');

        $conditionRules = $this->_condition->getConditionRules()->filter(function($rule) use ($ruleUid) {
            $uid = $rule->uid;
            return $uid != $ruleUid;
        })->all();

        $this->_condition->setConditionRules($conditionRules);

        return $this->renderBuilderHtml();
    }

    /**
     * @return string
     */
    protected function renderBuilderHtml(): string
    {
        return $this->_condition->getBuilderInnerHtml($this->_options);
    }
}
