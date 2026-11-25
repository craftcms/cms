<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\base\conditions\ConditionInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\events\DefineShowFieldLayoutComponentInFormEvent;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\models\FieldLayout;

/**
 * FieldLayoutComponent is the base class for classes representing field layout components (tabs or elements) in terms of objects.
 *
 * @property ElementConditionInterface|null $elementCondition The element condition for this layout element
 * @property UserCondition|null $userCondition The user condition for this layout element
 * @property FieldLayout $layout The layout this element belongs to
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class FieldLayoutComponent extends Model
{
    /**
     * @event DefineShowFieldLayoutComponentInFormEvent The event that is triggered when determining whether the component should be shown in a field layout.
     * @see showInForm()
     * @since 5.3.0
     */
    public const EVENT_DEFINE_SHOW_IN_FORM = 'defineShowInForm';

    /**
     * @var UserCondition
     */
    private static UserCondition $defaultUserCondition;

    /**
     * @var ElementConditionInterface[]
     */
    private static array $defaultElementConditions = [];

    /**
     * @return UserCondition
     */
    private static function defaultUserCondition(): UserCondition
    {
        return self::$defaultUserCondition ??= User::createCondition();
    }

    /**
     * @param class-string<ElementInterface> $elementType
     * @return ElementConditionInterface
     */
    private static function defaultElementCondition(string $elementType): ElementConditionInterface
    {
        return self::$defaultElementConditions[$elementType] ??= $elementType::createCondition();
    }

    /**
     * @var string|null The UUID of the layout element.
     */
    public ?string $uid = null;

    /**
     * @var string|null The element type the field layout is for
     * @since 5.0.0
     */
    public ?string $elementType = null;

    /**
     * @var FieldLayout The field layout tab this element belongs to
     * @see getLayout()
     * @see setLayout()
     */
    private FieldLayout $_layout;

    /**
     * @var UserCondition|class-string<UserCondition>|array|null
     * @phpstan-var UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null
     * @see getUserCondition()
     * @see setUserCondition()
     */
    private mixed $_userCondition = null;

    /**
     * @var ElementConditionInterface|class-string<ElementConditionInterface>|array|null
     * @phpstan-var ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null
     * @see getElementCondition()
     * @see setElementCondition()
     */
    private mixed $_elementCondition = null;

    /**
     * Returns the layout this element belongs to.
     *
     * @return FieldLayout
     */
    public function getLayout(): FieldLayout
    {
        return $this->_layout;
    }

    /**
     * Sets the layout this element belongs to.
     *
     * @param FieldLayout $layout
     */
    public function setLayout(FieldLayout $layout): void
    {
        $this->_layout = $layout;
    }

    /**
     * Returns whether this element can be conditional.
     *
     * @return bool
     */
    protected function conditional(): bool
    {
        return true;
    }

    /**
     * Returns whether this element has any conditions.
     *
     * @return bool
     */
    public function hasConditions(): bool
    {
        return $this->getUserCondition() || $this->getElementCondition();
    }

    /**
     * Returns the user condition for this layout element.
     *
     * @return UserCondition|null
     */
    public function getUserCondition(): ?UserCondition
    {
        if (isset($this->_userCondition) && !$this->_userCondition instanceof UserCondition) {
            $this->_userCondition = $this->normalizeCondition($this->_userCondition);
        }

        return $this->_userCondition;
    }

    /**
     * Sets the user condition for this layout element.
     *
     * @param UserCondition|class-string<UserCondition>|array|null $userCondition
     * @phpstan-param UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null $userCondition
     */
    public function setUserCondition(mixed $userCondition): void
    {
        $this->_userCondition = $userCondition;
    }

    /**
     * Returns the element condition for this layout element.
     *
     * @return ElementConditionInterface|null
     */
    public function getElementCondition(): ?ElementConditionInterface
    {
        if (isset($this->_elementCondition) && !$this->_elementCondition instanceof ElementConditionInterface) {
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
     * @param ElementConditionInterface|class-string<ElementConditionInterface>|array|null $elementCondition
     * @phpstan-param ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null $elementCondition
     */
    public function setElementCondition(mixed $elementCondition): void
    {
        $this->_elementCondition = $elementCondition;
    }

    /**
     * Normalizes a condition.
     *
     * @template T of ConditionInterface
     * @param T|class-string<T>|array|null $condition
     * @phpstan-param T|class-string<T>|array{class:class-string<T>}|null $condition
     * @return T|null
     * @since 5.7.0
     */
    protected function normalizeCondition(mixed $condition): ?ConditionInterface
    {
        if ($condition !== null) {
            if (!$condition instanceof ConditionInterface) {
                $condition = Craft::$app->getConditions()->createCondition($condition);
            }

            if (!$condition->getConditionRules()) {
                $condition = null;
            }
        }

        return $condition;
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['elementType']);
        $fields['userCondition'] = fn() => $this->getUserCondition()?->getConfig();
        $fields['elementCondition'] = fn() => $this->getElementCondition()?->getConfig();
        return $fields;
    }

    /**
     * Returns whether the layout element has settings.
     *
     * @return bool
     * @since 5.0.0
     */
    public function hasSettings()
    {
        return $this->conditional();
    }

    /**
     * Returns the settings HTML for the layout element.
     *
     * ::: tip
     * Subclasses should override [[settingsHtml()]] instead of this method.
     * :::
     *
     * @return string
     * @since 4.0.0
     */
    public function getSettingsHtml(): string
    {
        return implode("\n<hr>\n", array_filter([
            $this->settingsHtml(),
            $this->conditionalSettingsHtml(),
        ]));
    }

    /**
     * Returns the settings HTML for the layout element.
     *
     * @return string|null
     */
    protected function settingsHtml(): ?string
    {
        return null;
    }

    /**
     * Returns the conditional settings HTML for the layout element.
     *
     * @return string|null
     * @since 5.7.0
     */
    protected function conditionalSettingsHtml(): ?string
    {
        if (!$this->conditional()) {
            return null;
        }

        $html = Html::beginTag('fieldset', ['class' => 'pane']) .
            Html::tag('legend', Craft::t('app', 'Visibility Conditions')) .
            Html::beginTag('div');

        $userCondition = $this->getUserCondition() ?? self::defaultUserCondition();
        $userCondition->mainTag = 'div';
        $userCondition->id = 'user-condition';
        $userCondition->name = 'userCondition';
        $userCondition->forProjectConfig = true;

        $html .= Cp::fieldHtml($userCondition->getBuilderHtml(), [
            'label' => Craft::t('app', 'Current User Condition'),
            'instructions' => Craft::t('app', 'Only show for users who match the following rules:'),
        ]);

        // Do we know the element type?
        /** @var class-string<ElementInterface>|string|null $elementType */
        $elementType = $this->elementType ?? $this->getLayout()->type;

        if ($elementType && is_subclass_of($elementType, ElementInterface::class)) {
            $elementCondition = $this->getElementCondition();
            if (!$elementCondition) {
                $elementCondition = clone self::defaultElementCondition($elementType);
                $elementCondition->setFieldLayouts([$this->getLayout()]);
            }
            $elementCondition->mainTag = 'div';
            $elementCondition->id = 'element-condition';
            $elementCondition->name = 'elementCondition';
            $elementCondition->forProjectConfig = true;

            $html .= Cp::fieldHtml($elementCondition->getBuilderHtml(), [
                'label' => Craft::t('app', '{type} Condition', [
                    'type' => $elementType::displayName(),
                ]),
                'instructions' => Craft::t('app', 'Only show when editing {type} that match the following rules:', [
                    'type' => $elementType::pluralLowerDisplayName(),
                ]),
            ]);
        }

        $html .= Html::endTag('div') .
            Html::endTag('fieldset');

        return $html;
    }

    /**
     * Returns whether the layout element should be shown in an edit form for the given element.
     *
     * This will only be called if the field layout component has been saved with a [[uid|UUID]] already.
     *
     * @param ElementInterface|null $element
     * @return bool
     * @since 4.0.0
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        // Fire a 'defineShowInForm' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_SHOW_IN_FORM)) {
            $event = new DefineShowFieldLayoutComponentInFormEvent([
                'fieldLayout' => $this->getLayout(),
                'element' => $element,
            ]);
            $this->trigger(self::EVENT_DEFINE_SHOW_IN_FORM, $event);
            if (!$event->showInForm || $event->handled) {
                return $event->showInForm;
            }
        }

        if ($this->conditional()) {
            $userCondition = $this->getUserCondition();
            $elementCondition = $this->getElementCondition();

            if ($userCondition) {
                $currentUser = Craft::$app->getUser()->getIdentity();
                if ($currentUser && !$userCondition->matchElement($currentUser)) {
                    return false;
                }
            }

            if ($elementCondition && $element && !$elementCondition->matchElement($element)) {
                return false;
            }
        }

        return true;
    }
}
