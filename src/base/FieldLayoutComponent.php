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
use craft\helpers\Cp;
use craft\helpers\StringHelper;
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
     * @var string The UUID of the layout element.
     */
    public string $uid;

    /**
     * @var FieldLayout The field layout tab this element belongs to
     * @see getLayout()
     * @see setLayout()
     */
    private FieldLayout $_layout;

    /**
     * @var UserCondition|null
     * @see getUserCondition()
     * @see setUserCondition()
     */
    private ?UserCondition $_userCondition = null;

    /**
     * @var ElementConditionInterface|null
     * @see getElementCondition()
     * @see setElementCondition()
     */
    private ?ElementConditionInterface $_elementCondition = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->uid)) {
            $this->uid = StringHelper::UUID();
        }
    }

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
        return isset($this->_userCondition) || isset($this->_elementCondition);
    }

    /**
     * Returns the user condition for this layout element.
     *
     * @return UserCondition|null
     */
    public function getUserCondition(): ?UserCondition
    {
        return $this->_userCondition;
    }

    /**
     * Sets the user condition for this layout element.
     *
     * @param UserCondition|string|array{class: string}|null $userCondition
     */
    public function setUserCondition(mixed $userCondition): void
    {
        $this->_userCondition = $this->_normalizeCondition($userCondition);
    }

    /**
     * Returns the element condition for this layout element.
     *
     * @return ElementConditionInterface|null
     */
    public function getElementCondition(): ?ElementConditionInterface
    {
        return $this->_elementCondition;
    }

    /**
     * Sets the element condition for this layout element.
     *
     * @param ElementConditionInterface|string|array{class: string}|null $elementCondition
     */
    public function setElementCondition(mixed $elementCondition): void
    {
        $this->_elementCondition = $this->_normalizeCondition($elementCondition);
    }

    /**
     * Normalizes a condition.
     *
     * @param ConditionInterface|string|array{class: string}|null $condition
     * @return ConditionInterface|null
     */
    private function _normalizeCondition(mixed $condition): ?ConditionInterface
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

        if ($this->_userCondition) {
            $fields['userCondition'] = fn() => $this->_userCondition->getConfig();
        }
        if ($this->_elementCondition) {
            $fields['elementCondition'] = fn() => $this->_elementCondition->getConfig();
        }

        return $fields;
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
        $html = (string)$this->settingsHtml();

        if ($this->conditional()) {
            if ($html !== '') {
                $html .= '<hr>';
            }

            $userCondition = $this->_userCondition ?? User::createCondition();
            $userCondition->mainTag = 'div';
            $userCondition->id = 'user-condition';
            $userCondition->name = 'userCondition';
            $userCondition->forProjectConfig = true;

            $html .= Cp::fieldHtml($userCondition->getBuilderHtml(), [
                'label' => Craft::t('app', 'Current User Condition'),
                'instructions' => Craft::t('app', 'Only show for users who match the following rules:'),
            ]);

            // Do we know the element type?
            /** @var ElementInterface|string|null $elementType */
            $elementType = $this->getLayout()->type;

            if ($elementType) {
                $elementCondition = $this->_elementCondition ?? $elementType::createCondition();
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
                    ])
                ]);
            }
        }

        return $html;
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
     * Returns whether the layout element should be shown in an edit form for the given element.
     *
     * @param ElementInterface|null $element
     * @return bool
     * @since 4.0.0
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        if ($this->conditional()) {
            if ($this->_userCondition) {
                $currentUser = Craft::$app->getUser()->getIdentity();
                if ($currentUser && !$this->_userCondition->matchElement($currentUser)) {
                    return false;
                }
            }

            if ($this->_elementCondition && $element && !$this->_elementCondition->matchElement($element)) {
                return false;
            }
        }

        return true;
    }
}
