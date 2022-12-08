<?php

namespace craft\console\generators;

use Craft;
use craft\base\conditions\BaseConditionRule;
use craft\base\conditions\BaseDateRangeConditionRule;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\conditions\BaseNumberConditionRule;
use craft\base\conditions\BaseSelectConditionRule;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use yii\helpers\Inflector;

/**
 * Creates a new element condition rule.
 */
class ElementConditionRule extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $baseClass;
    private ?string $param;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Rule name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Rule namespace:', [
            'default' => "$this->baseNamespace\\elements\\conditions",
        ]);

        $types = [
            'date' => [
                'label' => 'Date range',
                'baseClass' => BaseDateRangeConditionRule::class,
            ],
            'element' => [
                'label' => 'Element select',
                'baseClass' => BaseElementSelectConditionRule::class,
            ],
            'lightswitch' => [
                'label' => 'Lightswitch',
                'baseClass' => BaseLightswitchConditionRule::class,
            ],
            'multi' => [
                'label' => 'Multi-select',
                'baseClass' => BaseMultiSelectConditionRule::class,
            ],
            'number' => [
                'label' => 'Number',
                'baseClass' => BaseNumberConditionRule::class,
            ],
            'select' => [
                'label' => 'Select',
                'baseClass' => BaseSelectConditionRule::class,
            ],
            'text' => [
                'label' => 'Text',
                'baseClass' => BaseTextConditionRule::class,
            ],
            'other' => [
                'label' => 'Other',
                'baseClass' => BaseConditionRule::class,
            ],
        ];
        $type = $this->controller->select('Which type of rule is this?', ArrayHelper::getColumn($types, 'label'));
        $this->baseClass = $types[$type]['baseClass'];

        $this->param = $this->controller->prompt('Which element query param should the rule modify?', [
            'format' => '/^[a-z]\w*$/i',
        ]) ?: null;

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(ElementConditionRuleInterface::class)
            ->addUse(ElementInterface::class)
            ->addUse(ElementQueryInterface::class)
            ->addUse($this->baseClass);

        $class = $this->createClass($this->className, $this->baseClass, [
            self::CLASS_IMPLEMENTS => [
                ElementConditionRuleInterface::class,
            ],
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->addComment("$this->displayName element condition rule");

        $this->writePhpClass($namespace);

        $this->controller->stdout(PHP_EOL);
        $this->controller->success(<<<MD
**Element condition rule created!**
Register it for use in a condition by including it in the condition’s `conditionRuleTypes()` method.
MD);
        return true;
    }

    private function methods(): array
    {
        // List any methods that should be copied into generated element condition rules from craft\base\conditions\BaseConditionRule
        // (see `craft\console\generators\BaseGenerator::createClass()`)
        return array_filter([
            'getLabel' => sprintf('return %s;', $this->messagePhp($this->displayName)),
            'getExclusiveQueryParams' => $this->param
                ? <<<PHP
return ['$this->param'];
PHP
                : null,
            'modifyQuery' => $this->param
                ? <<<PHP
\$query->$this->param(\$this->queryParamValue());
PHP
                : <<<PHP
// Modify the element query based on \$this->queryParamValue()
// \$query->myQueryParam(\$this->queryParamValue());
PHP,
            'matchElement' => <<<PHP
// Match the element based on one of its attributes
// return \$this->matchValue(\$element->myAttribute);
PHP,
            'options' => (new ReflectionClass($this->baseClass))->hasMethod('options')
                ? <<<PHP
// Return the selectable options
return [
    // ...
];
PHP
                : null,
            'elementType' => $this->baseClass === BaseElementSelectConditionRule::class
                ? <<<PHP
// Return the element type to select
// ...
PHP
                : null,
            'sources' => $this->baseClass === BaseElementSelectConditionRule::class ? 'return null;' : null,
            'criteria' => $this->baseClass === BaseElementSelectConditionRule::class ? 'return null;' : null,
        ]);
    }
}
