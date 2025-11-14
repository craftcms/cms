<?php

namespace craft\base\conditions;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
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
     * @var int[]|string|null
     * @see getElementIds()
     * @see setElementIds()
     */
    private array|string|null $_elementIds = null;

    /**
     * Returns the element type that can be selected.
     *
     * @return class-string<ElementInterface>
     */
    abstract protected function elementType(): string;

    public function setAttributes($values, $safeOnly = true): void
    {
        if (isset($values['elementId'])) {
            $values['elementIds'] = ArrayHelper::remove($values, 'elementId');
        }

        parent::setAttributes($values, $safeOnly);
    }

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
     * Returns whether multiple elements can be selected.
     *
     * @return bool
     * @since 5.6.0
     */
    protected function allowMultiple(): bool
    {
        return false;
    }

    /**
     * Defines the element select config.
     *
     * @return array
     * @since 5.5.0
     */
    protected function elementSelectConfig(): array
    {
        $elements = $this->_elements();
        return [
            'name' => 'elementIds',
            'elements' => $elements,
            'elementType' => $this->elementType(),
            'sources' => $this->sources(),
            'criteria' => $this->criteria(),
            'condition' => $this->selectionCondition(),
            'single' => !$this->allowMultiple(),
        ];
    }

    /**
     * @param bool $parse Whether to parse the value for an environment variable
     * @return int[]|string
     * @since 5.6.0
     */
    public function getElementIds(bool $parse = true): array|string
    {
        if ($parse && is_string($this->_elementIds)) {
            $elementIds = App::parseEnv($this->_elementIds);
            if ($this->condition instanceof ElementCondition && isset($this->condition->referenceElement)) {
                $referenceElement = $this->condition->referenceElement;
            } else {
                $referenceElement = new stdClass();
            }
            $elementIds = Craft::$app->getView()->renderObjectTemplate($elementIds, $referenceElement);
            return array_values(array_filter(array_map(
                fn(string $elementId) => (int)trim($elementId),
                explode(',', $elementIds),
            )));
        }

        return $this->_elementIds ?? [];
    }

    /**
     * @param array|int|string|null $elementIds
     * @phpstan-param array<int|string>|int|string|null $elementIds
     * @since 5.6.0
     */
    public function setElementIds(array|int|string|null $elementIds): void
    {
        if (is_array($elementIds)) {
            $elementIds = array_map(fn($id) => (int)$id, $elementIds);
        } elseif (is_numeric($elementIds)) {
            $elementIds = [(int)$elementIds];
        }

        $this->_elementIds = $elementIds ?: null;
    }

    /**
     * @param bool $parse Whether to parse the value for an environment variable
     * @return int|string|null
     */
    public function getElementId(bool $parse = true): int|string|null
    {
        $elementIds = $this->getElementIds($parse);
        return (is_array($elementIds) && !empty($elementIds)) ? $elementIds[0] : null;
    }

    /**
     * @param array|int|string|null $elementId
     * @phpstan-param array<int|string>|int|string|null $elementId
     */
    public function setElementId(array|int|string|null $elementId): void
    {
        $this->setElementIds($elementId);
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
            $value = $this->getElementIds(false);
            if (is_array($value)) {
                $value = join(',', $value);
            }
            $type = $this->elementType()::displayName();

            return Cp::autosuggestFieldHtml([
                'suggestEnvVars' => true,
                'suggestionFilter' => fn($value) => is_int($value) && $value > 0,
                'required' => true,
                'id' => 'elementIds',
                'class' => 'code',
                'name' => 'elementIds',
                'value' => $value,
                'tip' => $this->allowMultiple()
                    ? Craft::t('app', 'This can be set to an environment variable, or a Twig template that outputs comma-separated IDs.')
                    : Craft::t('app', 'This can be set to an environment variable, or a Twig template that outputs an ID.'),
                'placeholder' => $this->allowMultiple()
                    ? Craft::t('app', '{type} ID(s)', ['type' => $type])
                    : Craft::t('app', '{type} ID', ['type' => $type]),
            ]);
        }

        return Cp::elementSelectHtml($this->elementSelectConfig());
    }

    /**
     * @return ElementInterface[]
     */
    private function _elements(): array
    {
        $elementIds = $this->getElementIds();
        if (empty($elementIds)) {
            return [];
        }

        return $this->elementType()::find()
            ->site('*')
            ->preferSites(array_filter([Cp::requestedSite()?->id]))
            ->unique()
            ->id($elementIds)
            ->status(null)
            ->limit($this->allowMultiple() ? null : 1)
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
     * @phpstan-param ElementInterface|int|array<ElementInterface|int>|null $value
     * @return bool
     */
    protected function matchValue(mixed $value): bool
    {
        $elementIds = $this->getElementIds();

        if (empty($elementIds)) {
            return true;
        }

        if (!$value) {
            return false;
        }

        if ($value instanceof ElementInterface) {
            return in_array($value->id, $elementIds);
        }

        if (is_numeric($value)) {
            return in_array((int)$value, $elementIds);
        }

        if (is_array($value)) {
            foreach ($value as $val) {
                if (
                    $val instanceof ElementInterface && in_array($val->id, $elementIds) ||
                    is_numeric($val) && in_array((int)$val, $elementIds)
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
