<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\conditions\ConditionInterface;
use craft\conditions\ConditionRuleInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
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
     * @var string|null
     */
    private ?string $_namespace;

    /**
     * @var array
     */
    private array $_options = [];

    /**
     * @var ConditionInterface
     */
    private ConditionInterface $_condition;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->_namespace = $this->request->getBodyParam('namespace');
        $this->_options = Json::decodeIfJson($this->request->getBodyParam('options')) ?? [];

        if ($this->_namespace) {
            $config = $this->request->getBodyParam($this->_namespace);
        } else {
            $config = $this->request->getBodyParams();
            unset($config['namespace'], $config['options'], $config['uid']);
        }

        $this->_condition = Craft::$app->getConditions()->createCondition($config);

        return parent::beforeAction($action);
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
        /** @var ConditionRuleInterface|null $rule */
        $rule = collect($this->_condition->getSelectableConditionRules($this->_options))
            ->sortBy(fn(ConditionRuleInterface $rule) => $rule->getLabel())
            ->first();

        if ($rule) {
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
        $conditionRules = collect($this->_condition->getConditionRules())
            ->filter(fn(ConditionRuleInterface $rule) => $rule->uid !== $ruleUid)
            ->all();
        $this->_condition->setConditionRules($conditionRules);
        return $this->renderBuilderHtml();
    }

    /**
     * @return string
     */
    protected function renderBuilderHtml(): string
    {
        return Craft::$app->getView()->namespaceInputs(function() {
            return $this->_condition->getBuilderInnerHtml($this->_options);
        }, $this->_namespace);
    }
}
