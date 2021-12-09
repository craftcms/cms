<?php

namespace craft\base\conditions;

use craft\base\ElementInterface;
use craft\helpers\Cp;

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
     * @inheritdoc
     */
    public static function supportsProjectConfig(): bool
    {
        return false;
    }

    /**
     * @var int|null
     * @see getElementId()
     * @see setElementId()
     */
    private ?int $_elementId = null;

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
     * Returns the criteria that determines which elements can be selected.
     *
     * @return array|null
     */
    protected function criteria(): ?array
    {
        return null;
    }

    /**
     * @return int|null
     */
    public function getElementId(): ?int
    {
        return $this->_elementId;
    }

    /**
     * @param string|int $elementId
     */
    public function setElementId($elementId): void
    {
        if (is_array($elementId)) {
            $elementId = reset($elementId);
        }

        $this->_elementId = $elementId ? (int)$elementId : null;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'elementId' => $this->_elementId,
        ]);
    }

    /**
     * @inheritdochandleException
     */
    public function getHtml(array $options = []): string
    {
        return Cp::elementSelectHtml([
            'name' => 'elementId',
            'elements' => $this->_element(),
            'elementType' => $this->elementType(),
            'sources' => $this->sources(),
            'criteria' => $this->criteria(),
            'single' => true,
        ]);
    }

    /**
     * @return ElementInterface|null
     */
    private function _element(): ?ElementInterface
    {
        if (!$this->_elementId) {
            return null;
        }

        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType();
        return $elementType::find()
            ->id($this->_elementId)
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
}
