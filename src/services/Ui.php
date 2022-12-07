<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\base\Component;
use craft\helpers\StringHelper;
use craft\ui\attributes\AsTwigComponent;
use craft\ui\components\Button;
use craft\ui\components\InputColor;
use craft\ui\components\InputCopyText;
use craft\ui\components\InputDate;
use craft\ui\components\InputDateTime;
use craft\ui\components\InputHiddenText;
use craft\ui\components\InputPasswordText;
use craft\ui\components\InputSelect;
use craft\ui\components\InputSelectize;
use craft\ui\components\InputText;
use craft\ui\components\InputTextArea;
use craft\ui\components\InputTime;
use Exception;
use ReflectionClass;
use ReflectionException;

class Ui extends Component
{
    /**
     * @var array Available components indexed by name.
     */
    private array $_componentsByName = [];

    /**
     * All the registered component classes.
     *
     * @return string[]
     */
    public function getAllComponentTypes(): array
    {
        return [
            // Button
            Button::class,

            // Inputs
            InputText::class,
            InputTextArea::class,
            InputPasswordText::class,
            InputHiddenText::class,
            InputCopyText::class,
            InputDate::class,
            InputTime::class,
            InputDateTime::class,
            InputColor::class,
            InputSelect::class,
            InputSelectize::class,
        ];
    }

    public function getComponentTypesByName(): array
    {
        return $this->_componentsByName;
    }

    /**
     * @inerhitdoc
     */
    public function init(): void
    {
        parent::init();
        $this->_registerComponentTypes();
    }

    /**
     * Create and render a component
     *
     * @param string $name Name of the component to render
     * @param array $props Props to pass along to the component
     * @return string
     */
    public function createAndRender(string $name, array $props = []): string
    {
        if (!$class = $this->getComponentClass($name)) {
            return '';
        }

        $mounted = $class::create($props);
        return $mounted->render();
    }

    /**
     * Get the main component object.
     *
     * @param string $name Name of the component
     * @return string|null
     */
    public function getComponentClass(string $name): ?string
    {
        if (!isset($this->_componentsByName[$name])) {
            return null;
        }

        return $this->_componentsByName[$name];
    }

    /**
     * Return props data for a given component.
     *
     * @param string $name Component name
     * @return array
     * @throws ReflectionException
     */
    public function propsDataFor(string $name): array
    {
        $component = $this->getComponentClass($name);
        $reflectionClass = new ReflectionClass($component);
        $props = [];

        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $props[] = [
                'name' => $property->getName(),
                'type' => $property->getType(),
                'required' => !$property->getType()->allowsNull(),
                'default' => $property->getDefaultValue(),
                'description' => StringHelper::docDescription($property->getDocComment()),
            ];
        }

        return $props;
    }

    /**
     * Iterate over the component types and index them by attribute name.
     *
     * @return void
     * @throws \ReflectionException
     */
    private function _registerComponentTypes(): void
    {
        foreach ($this->getAllComponentTypes() as $class) {
            $reflectionClass = new \ReflectionClass($class);
            $attributes = $reflectionClass->getAttributes(AsTwigComponent::class);
            if (empty($attributes)) {
                throw new Exception("$class is missing `#[AsTwigComponent()]` attribute");
            }
            /** @var AsTwigComponent $attribute */
            $attribute = $attributes[0]->newInstance();

            $this->_componentsByName[$attribute->name] = $class;
        }
    }
}
