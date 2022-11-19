<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Component;
use craft\base\ModelInterface;
use craft\events\ComponentPreRenderEvent;
use craft\helpers\ArrayHelper;
use craft\ui\attributes\AsTwigComponent;
use craft\ui\attributes\ExposeInTemplate;
use craft\ui\ComponentAttributes;
use craft\ui\ComponentMetadata;
use craft\ui\components\Test;
use craft\ui\MountedComponent;
use Exception;
use Twig\Extension\EscaperExtension;

class Ui extends Component
{
    /**
     * @event PreRenderEvent The event fired before a UI component is rendered.
     */
    public const EVENT_COMPONENT_BEFORE_RENDER = 'beforeRenderComponent';

    /**
     * @var bool If the Twig safe class has been registerred
     */
    private bool $safeClassesRegistered = false;

    /**
     * @var array Available components indexed by name.
     */
    private array $_componentsByName = [];

    /**
     * @var array Available component metadata indexed by name.
     */
    private array $_metadataByName = [];

    /**
     * @var string Format passed into `sprintf` with template location
     */
    private string $_componentTemplateFormat = '_ui/%s.twig';

    /**
     * All the registered component classes.
     *
     * @return string[]
     */
    public function getAllComponentTypes(): array
    {
        return [
            // Test
            Test::class,
        ];
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
        $mounted = $this->_createComponent($name, $props);
        return $this->_render($mounted);
    }

    /**
     * Get the main component object.
     *
     * @param string $name Name of the component
     * @return object
     * @throws \yii\base\InvalidConfigException
     */
    public function getComponent(string $name): object
    {
        if (!isset($this->_componentsByName[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown component "%s". The registered components are: %s', $name, implode(', ', array_keys($this->_componentsByName))));
        }

        return Craft::createObject($this->_componentsByName[$name]);
    }

    /**
     * Get the metadata for a given component
     *
     * @param string $name Component name
     * @return ComponentMetadata
     */
    public function metadataFor(string $name): ComponentMetadata
    {
        if (!$metadata = $this->_metadataByName[$name] ?? null) {
            throw new \InvalidArgumentException(sprintf('Unknown component "%s". The registered components are: %s', $name, implode(', ', array_keys($this->_componentsByName))));
        }

        return new ComponentMetadata($metadata);
    }

    /**
     * Prepare variables for an embed context
     *
     * @param string $name Component name
     * @param array $props Component props
     * @param array $context Initial context
     * @return array
     */
    public function embeddedContext(string $name, array $props, array $context): array
    {
        $context[ComponentPreRenderEvent::EMBEDDED] = true;

        return $this->_preRender($this->_createComponent($name, $props), $context)->variables;
    }


    /**
     * Creates a component
     *
     * The process of creating a component runs the component through any/all
     * preMount methods as well as the main mount method. Once the component
     * has been mounted, attributes are collected into a [[ComponentAttributes]]
     * class.
     *
     * @param string $name Name of the component to render.
     * @param array $data Data passed into the component
     * @return MountedComponent The mounted component
     */
    private function _createComponent(string $name, array $data = []): MountedComponent
    {
        // Get the configuration object for `$name`
        /** @var ModelInterface $component */
        $component = $this->getComponent($name);

        // Run `preMount` against that config
        $data = $this->_preMount($component, $data);

        // Run `mount`
        $this->_mount($component, $data);

        // Map props onto the component class
        // set data that wasn't set in mount on the component directly
        foreach ($data as $property => $value) {
            if ($component->canSetProperty($property)) {
                $component->{$property} = $value;

                unset($data[$property]);
            }
        }

        // Validate the component
        $component->validate();

        // Run `postMount`
        $data = $this->_postMount($component, $data);

        // Collect remaining props into attributes
        $attributesVar = $this->metadataFor($name)->attributesVar;
        $attributes = $data[$attributesVar] ?? [];
        unset($data[$attributesVar]);

        // Return the `MountedComponent` instance
        return new MountedComponent([
            'name' => $name,
            'component' => $component,
            'attributes' => new ComponentAttributes(ArrayHelper::merge($attributes, $data)),
        ]);
    }

    /**
     * Renders a mounted component.
     *
     * @param MountedComponent $mounted
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    private function _render(MountedComponent $mounted): string
    {
        // Call `preRender`
        $event = $this->_preRender($mounted);

        // Render the component
        return Craft::$app->getView()->renderTemplate($event->template, $event->variables);
    }

    /**
     * Finalize variables and template before rendering the component.
     *
     * @param MountedComponent $mounted
     * @param array $context
     * @return ComponentPreRenderEvent
     */
    private function _preRender(MountedComponent $mounted, array $context = []): ComponentPreRenderEvent
    {
        // Register the safe class against our attributes so we can output them without the raw filter
        if (!$this->safeClassesRegistered) {
            $twig = Craft::$app->getView()->getTwig();
            $twig->getExtension(EscaperExtension::class)->addSafeClass(ComponentAttributes::class, ['html']);

            $this->safeClassesRegistered = true;
        }

        $component = $mounted->component;
        $metadata = $this->metadataFor($mounted->name);

        // Collect the variables for our component
        $variables = array_merge(
        // first so values can be overridden
            $context,

            // add the component as "this"
            ['this' => $component],

            // add computed properties proxy
            // ['computed' => new ComputedPropertiesProxy($component)],

            // add attributes
            [$metadata->attributesVar => $mounted->attributes],

            // expose public properties and properties marked with ExposeInTemplate attribute
            iterator_to_array($this->_exposedVariables($component, $metadata->exposePublicProps)),
        );

        // Fire an event to let plugins alter those variables
        $event = new ComponentPreRenderEvent([
            'mounted' => $mounted,
            'metadata' => $metadata,
            'variables' => $variables,
        ]);

        if ($this->hasEventHandlers(self::EVENT_COMPONENT_BEFORE_RENDER)) {
            $this->trigger(self::EVENT_COMPONENT_BEFORE_RENDER, $event);
        }

        // Return the event object
        return $event;
    }

    /**
     * Collect all the properties that should be added to the component.
     *
     * @param object $component
     * @param bool $exposePublicProps
     * @return \Iterator
     */
    private function _exposedVariables(object $component, bool $exposePublicProps): \Iterator
    {
        if ($exposePublicProps) {
            yield from get_object_vars($component);
        }

        $class = new \ReflectionClass($component);

        foreach ($class->getProperties() as $property) {
            if (!$attribute = $property->getAttributes(ExposeInTemplate::class)[0] ?? null) {
                continue;
            }

            $attribute = $attribute->newInstance();

            /** @var ExposeInTemplate $attribute */
            $value = $attribute->getter ? $component->{rtrim($attribute->getter, '()')}() : $component->{$property->name};

            yield $attribute->name ?? $property->name => $value;
        }

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$attribute = $method->getAttributes(ExposeInTemplate::class)[0] ?? null) {
                continue;
            }

            $attribute = $attribute->newInstance();

            /** @var ExposeInTemplate $attribute */
            $name = $attribute->name ?? (str_starts_with($method->name, 'get') ? lcfirst(substr($method->name, 3)) : $method->name);

            if ($method->getNumberOfRequiredParameters()) {
                throw new \LogicException(sprintf('Cannot use %s on methods with required parameters (%s::%s).', ExposeInTemplate::class, $component::class, $method->name));
            }

            yield $name => $component->{$method->name}();
        }
    }

    /**
     * Call any preMount methods registered on the component.
     *
     * PreMount methods accept a priority argument which can be used to determine
     * the order in which they are called.
     *
     * @param object $component The unmounted component
     * @param array $data Data passed to the component
     * @return array
     */
    private function _preMount(object $component, array $data): array
    {
        foreach (AsTwigComponent::preMountMethods($component) as $method) {
            $data = $component->{$method->name}($data);
        }

        return $data;
    }

    /**
     * Mount the component
     *
     * Mounting allows you to indirectly set properties on the component. Any
     * variables used in the mount will be removed from the $data variable.
     *
     * @param object $component The unmounted component.
     * @param array $data Data passed to the component.
     * @return void
     */
    private function _mount(object $component, array &$data): void
    {
        try {
            $method = (new \ReflectionClass($component))->getMethod('mount');
        } catch (\ReflectionException $e) {
            // no hydrate method
            return;
        }

        $parameters = [];

        foreach ($method->getParameters() as $refParameter) {
            $name = $refParameter->getName();

            if (\array_key_exists($name, $data)) {
                $parameters[] = $data[$name];

                // remove the data element so it isn't used to set the property directly.
                unset($data[$name]);
            } elseif ($refParameter->isDefaultValueAvailable()) {
                $parameters[] = $refParameter->getDefaultValue();
            } else {
                throw new \LogicException(sprintf('%s::mount() has a required $%s parameter. Make sure this is passed or make give a default value.', \get_class($component), $refParameter->getName()));
            }
        }

        $component->mount(...$parameters);
    }

    private function _postMount(object $component, array $data): array
    {
        foreach (AsTwigComponent::postMountMethods($component) as $method) {
            $data = $component->{$method->name}($data);
        }

        return $data;
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
            $this->_metadataByName[$attribute->name] = [
                'name' => $attribute->name,
                'template' => $attribute->template ?? sprintf($this->_componentTemplateFormat, str_replace(':', '/', $attribute->name)),
                'class' => $class,
                'exposePublicProps' => $attribute->exposePublicProps,
                'attributesVar' => $attribute->attributesVar,
            ];
        }
    }
}
