<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use Nette\PhpGenerator\PhpNamespace;
use yii\base\Application;
use yii\helpers\Inflector;

/**
 * Creates a new component generator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class Generator extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;
    private string $lowerDisplayName;
    private string $ucfirstDisplayName;
    private string $baseClassName;
    private ?string $baseClassAlias = null;
    private string $defaultNamespace;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Generator name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Generator namespace:', [
            'default' => "$this->baseNamespace\\console\\generators",
        ]);

        $this->displayName = Inflector::camel2words($this->className);
        $this->lowerDisplayName = strtolower($this->displayName);
        $this->ucfirstDisplayName = ucfirst($this->lowerDisplayName);
        $pluralLowerDisplayName = Inflector::pluralize($this->lowerDisplayName);

        $baseClass = $this->classPrompt("Base class for generated $pluralLowerDisplayName:", [
            'required' => true,
            'ensureExists' => true,
        ]);

        // Are we going to have a class name conflict when importing the base class?
        [, $this->baseClassName] = App::classParts($baseClass);
        if ($this->baseClassName === $this->className) {
            $this->baseClassAlias = "Base$this->baseClassName";
        }

        $this->defaultNamespace = $this->namespacePrompt("Default namespace for generated $pluralLowerDisplayName (relative to the base namespace):", [
            'ensureContained' => false,
            'default' => str_replace(' ', '', $pluralLowerDisplayName),
        ]);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseGenerator::class)
            ->addUse(Inflector::class)
            ->addUse(PhpNamespace::class)
            ->addUse($baseClass, $this->baseClassAlias);

        $class = $this->createClass($this->className, BaseGenerator::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);
        $class->addComment("Creates a new $this->lowerDisplayName.");

        $class->addProperty('className')
            ->setPrivate()
            ->setType('string');

        $class->addProperty('namespace')
            ->setPrivate()
            ->setType('string');

        $class->addProperty('displayName')
            ->setPrivate()
            ->setType('string');

        $baseGeneratorClass = BaseGenerator::class;

        $class->addMethod('constants')
            ->setPrivate()
            ->setReturnType('array')
            ->setBody(<<<PHP
// List any constants that should be copied into generated $pluralLowerDisplayName from $baseClass
// (see `$baseGeneratorClass::createClass()`)
return [];
PHP);

        $class->addMethod('properties')
            ->setPrivate()
            ->setReturnType('array')
            ->setBody(<<<PHP
// List any properties that should be copied into generated $pluralLowerDisplayName from $baseClass
// (see `$baseGeneratorClass::createClass()`)
return [];
PHP);

        $methodsMethod = $class->addMethod('methods')
            ->setPrivate()
            ->setReturnType('array')
            ->setBody(
                <<<PHP
// List any methods that should be copied into generated $pluralLowerDisplayName from $baseClass
// (see `$baseGeneratorClass::createClass()`)

PHP);
        if (is_subclass_of($baseClass, Component::class)) {
            $methodsMethod->addBody(<<<PHP
return [
    'displayName' => sprintf('return %s;', \$this->messagePhp(\$this->displayName)),
];
PHP);
        } else {
            $methodsMethod->addBody(<<<PHP
return [];
PHP);
        }

        $this->writePhpClass($namespace);

        $message = "**$this->ucfirstDisplayName generator created!**";
        if (!$this->module instanceof Application) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Register it for Craft’s `make` command by adding the following code to `$moduleFile`:

```
use craft\\console\\controllers\\MakeController;
use craft\\events\\RegisterComponentTypesEvent;
use yii\\base\\Event;
use $this->namespace\\$this->className;

Event::on(
    MakeController::class,
    MakeController::EVENT_REGISTER_GENERATOR_TYPES,
    function(RegisterComponentTypesEvent \$event) {
        \$event->types[] = $this->className::class;
    }
);
```
MD;
        }

        $this->controller->success($message);
        return true;
    }

    private function methods(): array
    {
        $baseClass = $this->baseClassAlias ?? $this->baseClassName;
        $slashedDefaultNamespace = addslashes($this->defaultNamespace);

        return [
            'run' => <<<PHP
\$this->className = \$this->classNamePrompt('$this->ucfirstDisplayName name:', [
    'required' => true,
]);

\$this->namespace = \$this->namespacePrompt('$this->ucfirstDisplayName namespace:', [
    'default' => "\$this->baseNamespace\\\\$slashedDefaultNamespace",
]);

\$this->displayName = Inflector::camel2words(\$this->className);

\$namespace = (new PhpNamespace(\$this->namespace))
    ->addUse(Craft::class)
    ->addUse($baseClass::class);

\$class = \$this->createClass(\$this->className, $baseClass::class, [
    self::CLASS_CONSTANTS => \$this->constants(),
    self::CLASS_PROPERTIES => \$this->properties(),
    self::CLASS_METHODS => \$this->methods(),
]);
\$namespace->add(\$class);

\$class->addComment("\$this->displayName $this->lowerDisplayName");

\$this->writePhpClass(\$namespace);

\$this->controller->success("**$this->ucfirstDisplayName created!**");
return true;
PHP,
        ];
    }
}
