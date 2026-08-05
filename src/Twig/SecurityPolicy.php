<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use ReflectionClass;
use ReflectionException;
use Twig\Markup;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Template;

class SecurityPolicy implements SecurityPolicyInterface
{
    /** @var string[] */
    private array $allowedTags = [];

    /** @var string[] */
    private array $allowedFilters = [];

    /** @var string[] */
    private array $allowedFunctions = [];

    /** @var array<class-string,string[]> */
    private array $allowedMethods = [];

    /** @var array<class-string,string[]> */
    private array $allowedProperties = [];

    /** @var class-string[] */
    private array $allowedClasses = [];

    /**
     * @param  list<string>  $allowedTags
     * @param  list<string>  $allowedFilters
     * @param  list<string>  $allowedFunctions
     * @param  array<class-string, list<string>>  $allowedMethods
     * @param  array<class-string, list<string>>  $allowedProperties
     * @param  list<class-string>  $allowedClasses
     */
    public function __construct(
        array $allowedTags = [],
        array $allowedFilters = [],
        array $allowedFunctions = [],
        array $allowedMethods = [],
        array $allowedProperties = [],
        array $allowedClasses = [],
    ) {
        $this->setAllowedTags($allowedTags);
        $this->setAllowedFilters($allowedFilters);
        $this->setAllowedFunctions($allowedFunctions);
        $this->setAllowedMethods($allowedMethods);
        $this->setAllowedProperties($allowedProperties);
        $this->setAllowedClasses($allowedClasses);
    }

    /**
     * @return string[]
     */
    public function getAllowedTags(): array
    {
        return $this->allowedTags;
    }

    /**
     * @param  string[]  $tags
     */
    public function setAllowedTags(array $tags): void
    {
        $this->allowedTags = $tags;
    }

    /**
     * @return string[]
     */
    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    /**
     * @param  string[]  $filters
     */
    public function setAllowedFilters(array $filters): void
    {
        $this->allowedFilters = $filters;
    }

    /**
     * @return string[]
     */
    public function getAllowedFunctions(): array
    {
        return $this->allowedFunctions;
    }

    /**
     * @param  string[]  $functions
     */
    public function setAllowedFunctions(array $functions): void
    {
        $this->allowedFunctions = $functions;
    }

    /**
     * @return class-string[]
     */
    public function getAllowedClasses(): array
    {
        return $this->allowedClasses;
    }

    /**
     * @param  class-string[]  $classes
     */
    public function setAllowedClasses(array $classes): void
    {
        $this->allowedClasses = $classes;
    }

    /**
     * @return array<class-string,string[]>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * @param  array<class-string,string[]>  $methods
     */
    public function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = [];
        foreach ($methods as $class => $m) {
            $this->allowedMethods[$class] = array_map(strtolower(...), $m);
        }
    }

    /**
     * @return array<class-string,string[]>
     */
    public function getAllowedProperties(): array
    {
        return $this->allowedProperties;
    }

    /**
     * @param  array<class-string,string[]>  $properties
     */
    public function setAllowedProperties(array $properties): void
    {
        $this->allowedProperties = $properties;
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($tags as $tag) {
            if (! in_array($tag, $this->allowedTags)) {
                if ($tag === 'extends') {
                    trigger_deprecation('twig/twig', '3.12', 'The "extends" tag is always allowed in sandboxes, but won\'t be in 4.0, please enable it explicitly in your sandbox policy if needed.');
                } elseif ($tag === 'use') {
                    trigger_deprecation('twig/twig', '3.12', 'The "use" tag is always allowed in sandboxes, but won\'t be in 4.0, please enable it explicitly in your sandbox policy if needed.');
                } else {
                    throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);
                }
            }
        }

        foreach ($filters as $filter) {
            if (! in_array($filter, $this->allowedFilters)) {
                throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (! in_array($function, $this->allowedFunctions)) {
                throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }

    public function checkMethodAllowed($obj, $method): void
    {
        if ($obj instanceof AllowableInSandbox && $obj->methodAllowedInSandbox($method)) {
            return;
        }

        if ($obj instanceof Template || $obj instanceof Markup) {
            return;
        }

        if (
            ($this->isClassAllowed($obj) && ! str_starts_with($method, '__') && ! $this->isDynamicMacroMethod($obj, $method)) ||
            $this->checkForAllowedAttributeInMethod($obj, $method)
        ) {
            return;
        }

        $method = strtolower($method);
        foreach ($this->allowedMethods as $class => $methods) {
            if ($obj instanceof $class && in_array($method, $methods)) {
                return;
            }
        }

        $class = $obj::class;
        throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $class), $class, $method);
    }

    private function isDynamicMacroMethod(object $obj, string $method): bool
    {
        if (! is_callable([$obj::class, 'hasMacro']) || ! $obj::class::hasMacro($method)) {
            return false;
        }

        try {
            return ! new ReflectionClass($obj)->hasMethod($method);
        } catch (ReflectionException) {
            return true;
        }
    }

    /** @param object|class-string|ReflectionClass<object> $obj */
    private function checkForAllowedAttributeInMethod(object|string $obj, string $method, bool $checkInterfaces = true): bool
    {
        try {
            $classRef = new ReflectionClass($obj);
            $methodRef = $classRef->getMethod($method);

            if (! empty($methodRef->getAttributes(AllowedInSandbox::class))) {
                return true;
            }

            $parentClass = $classRef->getParentClass();
            if ($parentClass && $this->checkForAllowedAttributeInMethod($parentClass, $method, false)) {
                return true;
            }

            if ($checkInterfaces) {
                foreach ($classRef->getInterfaceNames() as $interfaceName) {
                    if ($this->checkForAllowedAttributeInMethod($interfaceName, $method, false)) {
                        return true;
                    }
                }
            }
        } catch (ReflectionException) {
        }

        return false;
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        if ($obj instanceof AllowableInSandbox && $obj->propertyAllowedInSandbox($property)) {
            return;
        }

        if (
            $this->isClassAllowed($obj) ||
            $this->checkForAllowedAttributeInProperty($obj, $property)
        ) {
            return;
        }

        foreach ($this->allowedProperties as $class => $properties) {
            if ($obj instanceof $class && in_array($property, $properties)) {
                return;
            }
        }

        if (is_object($obj)) {
            try {
                $this->checkMethodAllowed($obj, "get$property");

                return;
            } catch (SecurityNotAllowedMethodError) {
            }
        }

        $class = $obj::class;
        throw new SecurityNotAllowedPropertyError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $class), $class, $property);
    }

    /** @param object|class-string|ReflectionClass<object> $obj */
    private function checkForAllowedAttributeInProperty(object|string $obj, string $property, bool $checkInterfaces = true): bool
    {
        try {
            $classRef = new ReflectionClass($obj);
            $propertyRef = $classRef->getProperty($property);

            if (! empty($propertyRef->getAttributes(AllowedInSandbox::class))) {
                return true;
            }

            $parentClass = $classRef->getParentClass();
            if ($parentClass && $this->checkForAllowedAttributeInProperty($parentClass, $property, false)) {
                return true;
            }

            if ($checkInterfaces) {
                foreach ($classRef->getInterfaceNames() as $interfaceName) {
                    if ($this->checkForAllowedAttributeInProperty($interfaceName, $property, false)) {
                        return true;
                    }
                }
            }
        } catch (ReflectionException) {
        }

        return false;
    }

    private function isClassAllowed(object $obj): bool
    {
        // see if the class has the AllowedInSandbox attribute
        if ($this->checkForAllowedAttributeInClass($obj)) {
            return true;
        }

        return array_any($this->allowedClasses, fn ($class) => $obj instanceof $class);
    }

    /** @param object|class-string|ReflectionClass<object> $obj */
    private function checkForAllowedAttributeInClass(object|string $obj, bool $checkInterfaces = true): bool
    {
        try {
            $ref = new ReflectionClass($obj);
            if (! empty($ref->getAttributes(AllowedInSandbox::class))) {
                return true;
            }

            $parentClass = $ref->getParentClass();
            if ($parentClass && $this->checkForAllowedAttributeInClass($parentClass, false)) {
                return true;
            }

            if ($checkInterfaces) {
                foreach ($ref->getInterfaceNames() as $interfaceName) {
                    if ($this->checkForAllowedAttributeInClass($interfaceName, false)) {
                        return true;
                    }
                }
            }
        } catch (ReflectionException) {
        }

        return false;
    }
}
