<?php

namespace craft\base\conditions;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\helpers\App;
use craft\helpers\Cp;
use stdClass;

/**
 * BaseElementSelectConditionRule provides a base implementation for element query condition rules that are composed of an element select input.
 *
 * @property int|null $elementId
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseElementSelectConditionRule extends BaseConditionRule
{
    /**
     * @var int|string|null
     * @see getElementId()
     * @see setElementId()
     */
    private int|string|null $_elementId = null;

    /**
     * Returns the element type that can be selected.
     *
     * @return string
     */
    abstract protected function elementType(): string;

    /**
     * Returns the element source(s) that the element can be selected from.
     *
     * @return array|null
     */
    protected function sources(): ?array
    {
        return null;
    }

    /**
     * Returns the element condition that filters which elements can be selected.
     *
     * @return ElementConditionInterface|null
     */
    protected function selectionCondition(): ?ElementConditionInterface
    {
        return null;
    }

    /**
     * Returns the criteria that determines which elements can be selected.
     *
     * @return array|null
     */
    protected function criteria(): ?array
    {
        return null;
    }

    /**
     * @param bool $parse Whether to parse the value for an environment variable
     * @return int|string|null
     */
    public function getElementId(bool $parse = true): int|string|null
    {
        if ($parse && is_string($this->_elementId)) {
            $elementId = App::parseEnv($this->_elementId);
            if ($this->condition instanceof ElementCondition && isset($this->condition->referenceElement)) {
                $referenceElement = $this->condition->referenceElement;
            } else {
                $referenceElement = new stdClass();
            }
            return Craft::$app->getView()->renderObjectTemplate($elementId, $referenceElement);
        }
        return $this->_elementId;
    }

    /**
     * @param array|int|string|null $elementId
     * @phpstan-param array<int|string>|int|string|null $elementId
     */
    public function setElementId(array|int|string|null $elementId): void
    {
        if (is_array($elementId)) {
            $elementId = reset($elementId);
        }

        $this->_elementId = $elementId ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'elementId' => $this->getElementId(false),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        if ($this->getCondition()->forProjectConfig) {
            return Cp::autosuggestFieldHtml([
                'suggestEnvVars' => true,
                'suggestionFilter' => fn($value) => is_int($value) && $value > 0,
                'required' => true,
                'id' => 'elementId',
                'class' => 'code',
                'name' => 'elementId',
                'value' => $this->getElementId(false),
                'tip' => Craft::t('app', 'This can be set to an environment variable, or a Twig template that outputs an ID.'),
                'placeholder' => Craft::t('app', '{type} ID', [
                    'type' => $this->elementType()::displayName(),
                ]),
            ]);
        }

        $element = $this->_element();

        return Cp::elementSelectHtml([
            'name' => 'elementId',
            'elements' => $element ? [$element] : [],
            'elementType' => $this->elementType(),
            'sources' => $this->sources(),
            'criteria' => $this->criteria(),
            'condition' => $this->selectionCondition(),
            'single' => true,
        ]);
    }

    /**
     * @return ElementInterface|null
     */
    private function _element(): ?ElementInterface
    {
        $elementId = $this->getElementId();
        if (!$elementId) {
            return null;
        }

        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType();
        return $elementType::find()
            ->id($elementId)
            ->status(null)
            ->one();
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['elementId'], 'number'];
        return $rules;
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param ElementInterface|int|array|null $value
     * @phpstan-param ElementInterface|int|array<ElementInterface|int>|null $value
     * @return bool
     */
    protected function matchValue(mixed $value): bool
    {
        $elementId = $this->getElementId();

        if (!$elementId) {
            return true;
        }

        if (!$value) {
            return false;
        }

        if ($value instanceof ElementInterface) {
            return $value->id === $elementId;
        }

        if (is_numeric($value)) {
            return (int)$value === (int)$elementId;
        }

        if (is_array($value)) {
            foreach ($value as $val) {
                if (
                    $val instanceof ElementInterface && $val->id === $elementId ||
                    is_numeric($val) && (int)$val === (int)$elementId
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
