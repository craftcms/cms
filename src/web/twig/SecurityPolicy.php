<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

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
use yii\base\BaseObject;

/**
 * Security policy
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.17.0
 */
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
     * @param string[] $tags
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
     * @param string[] $filters
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
     * @param string[] $functions
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
     * @param class-string[] $classes
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
     * @param array<class-string,string[]> $methods
     */
    public function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = [];
        foreach ($methods as $class => $m) {
            $this->allowedMethods[$class] = array_map('strtolower', $m);
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
     * @param array<class-string,string[]> $properties
     */
    public function setAllowedProperties(array $properties): void
    {
        $this->allowedProperties = $properties;
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($tags as $tag) {
            if (!in_array($tag, $this->allowedTags)) {
                if ('extends' === $tag) {
                    trigger_deprecation('twig/twig', '3.12', 'The "extends" tag is always allowed in sandboxes, but won\'t be in 4.0, please enable it explicitly in your sandbox policy if needed.');
                } elseif ('use' === $tag) {
                    trigger_deprecation('twig/twig', '3.12', 'The "use" tag is always allowed in sandboxes, but won\'t be in 4.0, please enable it explicitly in your sandbox policy if needed.');
                } else {
                    throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);
                }
            }
        }

        foreach ($filters as $filter) {
            if (!in_array($filter, $this->allowedFilters)) {
                throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (!in_array($function, $this->allowedFunctions)) {
                throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }

    public function checkMethodAllowed($obj, $method): void
    {
        if ($obj instanceof Template || $obj instanceof Markup) {
            return;
        }

        if (
            $this->isClassAllowed($obj) ||
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

        $class = get_class($obj);
        throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $class), $class, $method);
    }

    private function checkForAllowedAttributeInMethod($obj, string $method, bool $checkInterfaces = true): bool
    {
        try {
            $classRef = new ReflectionClass($obj);
            $methodRef = $classRef->getMethod($method);

            if (!empty($methodRef->getAttributes(AllowedInSandbox::class))) {
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

        if ($obj instanceof BaseObject) {
            try {
                $this->checkMethodAllowed($obj, "get$property");
                return;
            } catch (SecurityNotAllowedMethodError) {
            }
        }

        $class = get_class($obj);
        throw new SecurityNotAllowedPropertyError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $class), $class, $property);
    }

    private function checkForAllowedAttributeInProperty($obj, string $property, bool $checkInterfaces = true): bool
    {
        try {
            $classRef = new ReflectionClass($obj);
            $propertyRef = $classRef->getProperty($property);

            if (!empty($propertyRef->getAttributes(AllowedInSandbox::class))) {
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

    private function isClassAllowed($obj): bool
    {
        // see if the class has the AllowedInSandbox attribute
        if ($this->checkForAllowedAttributeInClass($obj)) {
            return true;
        }

        foreach ($this->allowedClasses as $class) {
            if ($obj instanceof $class) {
                return true;
            }
        }

        return false;
    }

    private function checkForAllowedAttributeInClass($obj, bool $checkInterfaces = true): bool
    {
        try {
            $ref = new ReflectionClass($obj);
            if (!empty($ref->getAttributes(AllowedInSandbox::class))) {
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
