<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use Craft;
use craft\base\conditions\ConditionInterface;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\users\UserCondition;
use craft\helpers\Cp;
use CraftCms\Cms\Component\Concerns\ConfigConstructor;
use CraftCms\Cms\FieldLayout\Events\DefineShowInForm;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Support\Facades\Auth;
use Yiisoft\Arrays\ArrayableTrait;

use function CraftCms\Cms\t;

abstract class FieldLayoutComponent implements Validatable
{
    use ArrayableTrait;
    use ConfigConstructor;
    use Validates;

    private static UserCondition $defaultUserCondition;

    /**
     * @var ElementConditionInterface[]
     */
    private static array $defaultElementConditions = [];

    public ?ElementConditionInterface $elementCondition {
        get => $this->getElementCondition();
        set(ElementConditionInterface|string|array|null $value) {
            $this->setElementCondition($value);
        }
    }

    public ?UserCondition $userCondition {
        get => $this->getUserCondition();
        set(UserCondition|string|array|null $value) {
            $this->setUserCondition($value);
        }
    }

    public ?FieldLayout $layout {
        get => $this->getLayout();
        set {
            $this->setLayout($value);
        }
    }

    /**
     * @var string|null The UUID of the layout element.
     */
    public ?string $uid = null;

    /**
     * @var string|null The element type the field layout is for
     */
    public ?string $elementType = null;

    /**
     * @var FieldLayout The field layout tab this element belongs to
     *
     * @see getLayout()
     * @see setLayout()
     */
    private ?FieldLayout $_layout = null;

    /**
     * @var UserCondition|class-string<UserCondition>|array|null
     *
     * @phpstan-var UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null
     *
     * @see getUserCondition()
     * @see setUserCondition()
     */
    private mixed $_userCondition = null;

    /**
     * @var ElementConditionInterface|class-string<ElementConditionInterface>|array|null
     *
     * @phpstan-var ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null
     *
     * @see getElementCondition()
     * @see setElementCondition()
     */
    private mixed $_elementCondition = null;

    private static function defaultUserCondition(): UserCondition
    {
        return self::$defaultUserCondition ??= User::createCondition();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private static function defaultElementCondition(string $elementType): ElementConditionInterface
    {
        return self::$defaultElementConditions[$elementType] ??= $elementType::createCondition();
    }

    public function getLayout(): ?FieldLayout
    {
        return $this->_layout;
    }

    public function setLayout(FieldLayout $layout): void
    {
        $this->_layout = $layout;
    }

    protected function conditional(): bool
    {
        return true;
    }

    public function hasConditions(): bool
    {
        if ($this->getUserCondition()) {
            return true;
        }

        return (bool) $this->getElementCondition();
    }

    public function getUserCondition(): ?UserCondition
    {
        if (isset($this->_userCondition) && ! $this->_userCondition instanceof UserCondition) {
            $this->_userCondition = $this->normalizeCondition($this->_userCondition);
        }

        return $this->_userCondition;
    }

    /**
     * Sets the user condition for this layout element.
     *
     * @param  UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null  $userCondition
     */
    public function setUserCondition(mixed $userCondition): void
    {
        $this->_userCondition = $userCondition;
    }

    public function getElementCondition(): ?ElementConditionInterface
    {
        if (isset($this->_elementCondition) && ! $this->_elementCondition instanceof ElementConditionInterface) {
            if (is_string($this->_elementCondition)) {
                $this->_elementCondition = ['class' => $this->_elementCondition];
            }

            $this->_elementCondition = array_merge(
                ['fieldLayouts' => [$this->getLayout()]],
                $this->_elementCondition,
            );

            $this->_elementCondition = $this->normalizeCondition($this->_elementCondition);
        }

        return $this->_elementCondition;
    }

    /**
     * Sets the element condition for this layout element.
     *
     * @param  ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null  $elementCondition
     */
    public function setElementCondition(mixed $elementCondition): void
    {
        $this->_elementCondition = $elementCondition;
    }

    /**
     * Normalizes a condition.
     *
     * @template T of ConditionInterface
     *
     * @param  ConditionInterface|class-string<T>|array{class:class-string<T>}|null  $condition
     * @return T|null
     */
    protected function normalizeCondition(mixed $condition): ?ConditionInterface
    {
        if ($condition === null) {
            return null;
        }

        if (! $condition instanceof ConditionInterface) {
            $condition = Craft::$app->getConditions()->createCondition($condition);
        }

        if (! $condition->getConditionRules()) {
            return null;
        }

        return $condition;
    }

    public function fields(): array
    {
        $fields = $this->getAttributes();
        unset($fields['elementType']);
        $fields['userCondition'] = fn () => $this->getUserCondition()?->getConfig();
        $fields['elementCondition'] = fn () => $this->getElementCondition()?->getConfig();

        return $fields;
    }

    public function hasSettings(): bool
    {
        return $this->conditional();
    }

    /**
     * Returns the settings HTML for the layout element.
     *
     * ::: tip
     * Subclasses should override [[settingsHtml()]] instead of this method.
     * :::
     */
    final public function getSettingsHtml(): string
    {
        return implode("\n<hr>\n", array_filter([
            $this->settingsHtml(),
            $this->conditionalSettingsHtml(),
        ]));
    }

    protected function settingsHtml(): ?string
    {
        return null;
    }

    protected function conditionalSettingsHtml(): ?string
    {
        if (! $this->conditional()) {
            return null;
        }

        $html = Html::beginTag('fieldset', ['class' => 'pane']).
            Html::tag('legend', t('Visibility Conditions')).
            Html::beginTag('div');

        $userCondition = $this->getUserCondition() ?? self::defaultUserCondition();
        $userCondition->mainTag = 'div';
        $userCondition->id = 'user-condition';
        $userCondition->name = 'userCondition';
        $userCondition->forProjectConfig = true;

        $html .= Cp::fieldHtml($userCondition->getBuilderHtml(), [
            'label' => t('Current User Condition'),
            'instructions' => t('Only show for users who match the following rules:'),
        ]);

        // Do we know the element type?
        /** @var class-string<ElementInterface>|string|null $elementType */
        $elementType = $this->elementType ?? $this->getLayout()->type;

        if ($elementType && is_subclass_of($elementType, ElementInterface::class)) {
            $elementCondition = $this->getElementCondition();
            if (! $elementCondition) {
                $elementCondition = clone self::defaultElementCondition($elementType);
                $elementCondition->setFieldLayouts([$this->getLayout()]);
            }
            $elementCondition->mainTag = 'div';
            $elementCondition->id = 'element-condition';
            $elementCondition->name = 'elementCondition';
            $elementCondition->forProjectConfig = true;

            $html .= Cp::fieldHtml($elementCondition->getBuilderHtml(), [
                'label' => t('{type} Condition', [
                    'type' => $elementType::displayName(),
                ]),
                'instructions' => t('Only show when editing {type} that match the following rules:', [
                    'type' => $elementType::pluralLowerDisplayName(),
                ]),
            ]);
        }

        return $html.(Html::endTag('div').Html::endTag('fieldset'));
    }

    /**
     * Returns whether the layout element should be shown in an edit form for the given element.
     *
     * This will only be called if the field layout component has been saved with a [[uid|UUID]] already.
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        event($event = new DefineShowInForm(
            fieldLayoutComponent: $this,
            fieldLayout: $this->getLayout(),
            element: $element,
        ));

        if (! $event->showInForm || $event->handled) {
            return $event->showInForm;
        }

        if (! $this->conditional()) {
            return true;
        }

        $userCondition = $this->getUserCondition();
        $elementCondition = $this->getElementCondition();

        if ($userCondition) {
            $currentUser = Auth::user();
            if ($currentUser && ! $userCondition->matchElement($currentUser)) {
                return false;
            }
        }

        if ($elementCondition && $element && ! $elementCondition->matchElement($element)) {
            return false;
        }

        return true;
    }
}
