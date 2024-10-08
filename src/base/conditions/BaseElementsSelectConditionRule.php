<?php

namespace craft\base\conditions;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\helpers\App;
use craft\helpers\Cp;
use stdClass;
use yii\base\Exception;

/**
 * BaseElementsSelectConditionRule provides a base implementation for element query condition rules that are composed of an multi-element select input.
 *
 * @property int|null $elementId
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
abstract class BaseElementsSelectConditionRule extends BaseConditionRule
{
    /**
     * @var string|array|null
     * @see getElementIds()
     * @see setElementIds()
     */
    private string|array|null $_elementIds = null;

    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_IN;

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
     * @inheritdoc
     */
    protected function operators(): array
    {
        return array_merge(parent::operators(), [
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
        ]);
    }

    /**
     * @param bool $parse Whether to parse the value for an environment variable
     * @return array|string|null
     * @throws Exception
     * @throws \Throwable
     */
    public function getElementIds(bool $parse = true): array|string|null
    {
        if ($parse && is_string($this->_elementIds)) {
            $elementId = App::parseEnv($this->_elementIds);
            if ($this->condition instanceof ElementCondition && isset($this->condition->referenceElement)) {
                $referenceElement = $this->condition->referenceElement;
            } else {
                $referenceElement = new stdClass();
            }

            $elementIds = Craft::$app->getView()->renderObjectTemplate($elementId, $referenceElement);

            if (str_contains($elementIds, ',')) {
                $elementIds = explode(',', $elementIds);
            }

            return $elementIds;
        }

        return $this->_elementIds;
    }

    /**
     * @param array|string|null $elementIds
     * @phpstan-param array<int|string>|string|null $elementIds
     */
    public function setElementIds(array|string|null $elementIds): void
    {
        $this->_elementIds = $elementIds ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'elementIds' => $this->getElementIds(false),
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
                'suggestionFilter' => fn($value) => is_string($value) && strlen($value) > 0,
                'required' => true,
                'id' => 'elementIds',
                'class' => 'code',
                'name' => 'elementIds',
                'value' => $this->getElementIds(false),
                'tip' => Craft::t('app', 'This can be set to an environment variable, or a Twig template that outputs a comma separated list of IDs.'),
                'placeholder' => Craft::t('app', '{type} IDs', [
                    'type' => $this->elementType()::displayName(),
                ]),
            ]);
        }

        $elements = $this->_elements();

        return Cp::elementSelectHtml([
            'name' => 'elementIds',
            'elements' => $elements ?: [],
            'elementType' => $this->elementType(),
            'sources' => $this->sources(),
            'criteria' => $this->criteria(),
            'condition' => $this->selectionCondition(),
            'single' => false,
        ]);
    }

    /**
     * @return ElementInterface[]|null
     * @throws Exception
     * @throws \Throwable
     */
    private function _elements(): ?array
    {
        $elementIds = $this->getElementIds();
        if (!$elementIds) {
            return null;
        }

        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType();
        return $elementType::find()
            ->id($elementIds)
            ->status(null)
            ->all();
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['elementIds'], 'safe'];
        return $rules;
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param ElementInterface|int|array|null $value
     * @return bool
     * @throws Exception
     * @throws \Throwable
     */
    protected function matchValue(mixed $value): bool
    {
        $elementIds = $this->getElementIds();

        if (!$elementIds) {
            return true;
        }

        if (!$value) {
            return false;
        }

        if ($value instanceof ElementInterface) {
            $value = [$value->id];
        } elseif (is_numeric($value)) {
            $value = [(int)$value];
        } elseif (is_array($value)) {
            $values = [];
            foreach ($value as $val) {
                if ($val instanceof ElementInterface) {
                    $values[] = $val->id;
                } elseif (is_numeric($val)) {
                    $values[] = (int)$val;
                }
            }
            $value = $values;
        }

        return match ($this->operator) {
            self::OPERATOR_IN => !empty(array_intersect($value, $elementIds)),
            self::OPERATOR_NOT_IN => empty(array_intersect($value, $elementIds)),
            default => false,
        };
    }
}
