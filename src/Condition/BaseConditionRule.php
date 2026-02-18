<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

/**
 * BaseConditionRule provides a base implementation for condition rules.
 *
 * @property bool $isNew Whether the rule is new
 * @property-read string $html The rule’s HTML for a condition builder
 * @property-read string $uiLabel The rule’s option label
 */
abstract class BaseConditionRule extends Component implements ConditionRuleInterface
{
    protected const string OPERATOR_EQ = '=';

    protected const string OPERATOR_NE = '!=';

    protected const string OPERATOR_LT = '<';

    protected const string OPERATOR_LTE = '<=';

    protected const string OPERATOR_GT = '>';

    protected const string OPERATOR_GTE = '>=';

    protected const string OPERATOR_BEGINS_WITH = 'bw';

    protected const string OPERATOR_ENDS_WITH = 'ew';

    protected const string OPERATOR_CONTAINS = '**';

    protected const string OPERATOR_IN = 'in';

    protected const string OPERATOR_NOT_IN = 'ni';

    protected const string OPERATOR_EMPTY = 'empty';

    protected const string OPERATOR_NOT_EMPTY = 'notempty';

    /**
     * @var string|null UUID
     */
    public ?string $uid = null;

    /**
     * @var string The selected operator.
     */
    public string $operator;

    /**
     * @var bool Whether to reload the condition builder when the operator changes
     */
    protected bool $reloadOnOperatorChange = false;

    private ConditionInterface $_condition;

    public ConditionInterface $condition {
        get => $this->getCondition();
        set {
            $this->setCondition($value);
        }
    }

    /**
     * @see getAutofocus()
     * @see setAutofocus()
     */
    private bool $_autofocus = false;

    public bool $autofocus {
        get => $this->getAutofocus();
        set {
            $this->setAutofocus($value);
        }
    }

    public array $config {
        get => $this->getConfig();
    }

    public string $html {
        get => $this->getHtml();
    }

    public function __construct(object|array $config = [])
    {
        parent::__construct($config);

        if (! isset($this->uid)) {
            $this->uid = Str::uuid()->toString();
        }
    }

    public static function supportsProjectConfig(): bool
    {
        return true;
    }

    public function getLabelHint(): ?string
    {
        return null;
    }

    public function showLabelHint(): bool
    {
        return false;
    }

    public function getCondition(): ConditionInterface
    {
        return $this->_condition;
    }

    public function setCondition(ConditionInterface $condition): void
    {
        $this->_condition = $condition;
    }

    public function getGroupLabel(): ?string
    {
        return null;
    }

    public function getConfig(): array
    {
        $config = [
            'class' => static::class,
            'uid' => $this->uid,
        ];

        if (! empty($this->operators())) {
            $config['operator'] = $this->operator;
        }

        return $config;
    }

    /**
     * Returns the operators that should be allowed for this rule.
     */
    protected function operators(): array
    {
        return [];
    }

    /**
     * Returns the input HTML.
     */
    protected function inputHtml(): string
    {
        return '';
    }

    /**
     * Returns the option label for a given operator.
     */
    protected function operatorLabel(string $operator): string
    {
        return match ($operator) {
            self::OPERATOR_EQ => t('equals'),
            self::OPERATOR_NE => t('does not equal'),
            self::OPERATOR_LT => t('is less than'),
            self::OPERATOR_LTE => t('is less than or equals'),
            self::OPERATOR_GT => t('is greater than'),
            self::OPERATOR_GTE => t('is greater than or equals'),
            self::OPERATOR_BEGINS_WITH => t('begins with'),
            self::OPERATOR_ENDS_WITH => t('ends with'),
            self::OPERATOR_CONTAINS => t('contains'),
            self::OPERATOR_IN => t('is one of'),
            self::OPERATOR_NOT_IN => t('is not one of'),
            self::OPERATOR_EMPTY => t('is empty'),
            self::OPERATOR_NOT_EMPTY => t('has a value'),
            default => $operator,
        };
    }

    public function getHtml(): string
    {
        $operators = $this->operators();

        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-start'],
            ]).
            (count($operators) > 1
                ? (
                    Html::hiddenLabel(t('Operator'), 'operator').
                    Cp::selectHtml([
                        'id' => 'operator',
                        'name' => 'operator',
                        'value' => $this->operator,
                        'options' => array_map(fn ($operator) => ['value' => $operator, 'label' => $this->operatorLabel($operator)], $operators),
                        'inputAttributes' => [
                            'hx' => [
                                'post' => $this->reloadOnOperatorChange ? UrlHelper::actionUrl('conditions/render') : false,
                            ],
                        ],
                    ])
                )
                : Html::hiddenInput('operator', reset($operators))
            ).
            $this->inputHtml().
            Html::endTag('div');
    }

    #[\Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'uid' => ['nullable'],
            'condition' => ['nullable'],
            'operator' => ['nullable', Rule::in($this->operators())],
        ]);
    }

    public function getAutofocus(): bool
    {
        return $this->_autofocus;
    }

    public function setAutofocus(bool $autofocus = true): void
    {
        $this->_autofocus = $autofocus;
    }
}
